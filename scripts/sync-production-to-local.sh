#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [[ -f .env ]]; then
    set -a
    # shellcheck source=/dev/null
    source .env
    set +a
else
    set -a
    # shellcheck source=/dev/null
    source .env.example
    set +a
fi

LOCAL_PORT="${LOCAL_PORT:-8081}"
LOCAL_SITE_URL="${LOCAL_SITE_URL:-http://tower-3420:8081}"
KO_DATA_DIR="${KO_DATA_DIR:-$HOME/.local/share/kobietyoceanow}"
KO_DATA_DIR="${KO_DATA_DIR/#\~/$HOME}"
SSH_HOST="${SSH_HOST:-s46.cyberfolks.pl}"
SSH_USER="${SSH_USER:-codedrop}"
SSH_PORT="${SSH_PORT:-222}"
REMOTE_WP_PATH="${REMOTE_WP_PATH:-domains/kobietyoceanow.pl/public_html}"
REMOTE_SITE_URL="${REMOTE_SITE_URL:-https://www.kobietyoceanow.pl}"
SSH_TARGET="${SSH_USER}@${SSH_HOST}"
SSH_OPTIONS=(-p "$SSH_PORT" -o StrictHostKeyChecking=yes -o BatchMode=yes)
RSYNC_SSH="ssh -p $SSH_PORT -o StrictHostKeyChecking=yes -o BatchMode=yes"
BACKUP_DIR="$KO_DATA_DIR/backups"
DUMP="$BACKUP_DIR/production-$(date +%Y%m%d-%H%M%S).sql"

mkdir -p "$KO_DATA_DIR/uploads" "$KO_DATA_DIR/plugins" "$BACKUP_DIR"
chmod 700 "$KO_DATA_DIR" "$KO_DATA_DIR/uploads" "$KO_DATA_DIR/plugins" "$BACKUP_DIR"

remote_wp() {
    ssh "${SSH_OPTIONS[@]}" "$SSH_TARGET" "cd '$REMOTE_WP_PATH' && wp $*"
}

PRODUCTION_HOME="$(remote_wp 'option get home')"
PRODUCTION_SITEURL="$(remote_wp 'option get siteurl')"
REMOTE_PREFIX="$(remote_wp 'db prefix')"
REMOTE_VERSION="$(remote_wp 'core version')"
[[ "$PRODUCTION_HOME" == "$REMOTE_SITE_URL" && "$PRODUCTION_SITEURL" == "$REMOTE_SITE_URL" ]] || {
    printf 'Production URLs differ from configured REMOTE_SITE_URL; refusing sync.\n' >&2
    printf 'home=%s siteurl=%s configured=%s\n' "$PRODUCTION_HOME" "$PRODUCTION_SITEURL" "$REMOTE_SITE_URL" >&2
    exit 1
}
[[ "$REMOTE_PREFIX" =~ ^[A-Za-z0-9_]+$ ]] || {
    printf 'Unexpected remote table prefix: %s\n' "$REMOTE_PREFIX" >&2
    exit 1
}
printf 'Production WordPress %s, home/siteurl %s, table prefix %s.\n' "$REMOTE_VERSION" "$PRODUCTION_HOME" "$REMOTE_PREFIX"

printf 'Syncing uploads and plugins without deleting local files...\n'
rsync -a -e "$RSYNC_SSH" "$SSH_TARGET:$REMOTE_WP_PATH/wp-content/uploads/" "$KO_DATA_DIR/uploads/"
rsync -a -e "$RSYNC_SSH" "$SSH_TARGET:$REMOTE_WP_PATH/wp-content/plugins/" "$KO_DATA_DIR/plugins/"

docker compose build wordpress
DB_VOLUME="$(docker compose config --volumes | grep -Fx db_data)"
DB_VOLUME="${DB_VOLUME:-kobietyoceanow-theme}_${DB_VOLUME}"
if docker volume inspect "$DB_VOLUME" >/dev/null 2>&1; then
    printf 'Local database volume %s already exists; refusing to import over it. Back it up and review its contents first.\n' "$DB_VOLUME" >&2
    exit 1
fi

WORDPRESS_VOLUME="$(docker compose config --volumes | grep -Fx wordpress_data)"
WORDPRESS_VOLUME="${DB_VOLUME%_db_data}_${WORDPRESS_VOLUME}"
if docker volume inspect "$WORDPRESS_VOLUME" >/dev/null 2>&1; then
    printf 'Local WordPress volume already exists; refusing to initialize over it without review.\n' >&2
    exit 1
fi

docker compose up -d db
until docker compose exec -T db mariadb-admin ping -h 127.0.0.1 -uroot -p"${DB_ROOT_PASSWORD:-local-root-password}" --silent >/dev/null 2>&1; do
    sleep 2
done

docker compose up -d wordpress
until docker compose exec -T wordpress test -f /var/www/html/wp-includes/version.php; do sleep 2; done

printf 'Exporting production SQL through SSH to %s...\n' "$DUMP"
ssh "${SSH_OPTIONS[@]}" "$SSH_TARGET" "cd '$REMOTE_WP_PATH' && wp db export -" > "$DUMP"
chmod 600 "$DUMP"
[[ -s "$DUMP" ]] || { printf 'Production database export is empty.\n' >&2; exit 1; }
[[ "$(grep -cE "^CREATE TABLE \`?${REMOTE_PREFIX}" "$DUMP" || true)" -gt 0 ]] || {
    printf 'Export does not appear to contain tables for the confirmed prefix %s.\n' "$REMOTE_PREFIX" >&2
    exit 1
}

export WORDPRESS_TABLE_PREFIX="$REMOTE_PREFIX"
docker compose run --rm --no-deps -e WORDPRESS_TABLE_PREFIX="$REMOTE_PREFIX" wpcli db import "/dev/stdin" < "$DUMP"
docker compose run --rm -e WORDPRESS_TABLE_PREFIX="$REMOTE_PREFIX" wpcli search-replace \
    "$PRODUCTION_HOME" "$LOCAL_SITE_URL" --all-tables --skip-columns=guid

if [[ "$PRODUCTION_SITEURL" != "$PRODUCTION_HOME" ]]; then
    docker compose run --rm -e WORDPRESS_TABLE_PREFIX="$REMOTE_PREFIX" wpcli search-replace \
        "$PRODUCTION_SITEURL" "$LOCAL_SITE_URL" --all-tables --skip-columns=guid
fi
if [[ "$REMOTE_SITE_URL" == https://www.* ]]; then
    BARE_ORIGIN="${REMOTE_SITE_URL/https:\/\/www./https:\/\/}"
    for local_dump in "$DUMP"; do
        if grep -Fq "$BARE_ORIGIN" "$local_dump"; then
            docker compose run --rm -e WORDPRESS_TABLE_PREFIX="$REMOTE_PREFIX" wpcli search-replace \
                "$BARE_ORIGIN" "$LOCAL_SITE_URL" --all-tables --skip-columns=guid
            break
        fi
    done
fi

[[ "$(docker compose run --rm -e WORDPRESS_TABLE_PREFIX="$REMOTE_PREFIX" wpcli option get home)" == "$LOCAL_SITE_URL" ]]
[[ "$(docker compose run --rm -e WORDPRESS_TABLE_PREFIX="$REMOTE_PREFIX" wpcli option get siteurl)" == "$LOCAL_SITE_URL" ]]
docker compose run --rm -e WORDPRESS_TABLE_PREFIX="$REMOTE_PREFIX" wpcli core is-installed
docker compose run --rm -e WORDPRESS_TABLE_PREFIX="$REMOTE_PREFIX" wpcli db prefix
docker compose run --rm -e WORDPRESS_TABLE_PREFIX="$REMOTE_PREFIX" wpcli theme list --status=active
docker compose run --rm -e WORDPRESS_TABLE_PREFIX="$REMOTE_PREFIX" wpcli plugin list

docker compose ps
printf 'Local site is available at %s\n' "$LOCAL_SITE_URL"
