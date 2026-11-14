# Local Docker WordPress

The Compose stack serves WordPress at `http://tower-3420:8081` and binds only
to Windows loopback. MariaDB is private to the Compose network. The database
and WordPress core use separate named volumes; synchronized uploads, plugins,
and SQL backups live outside the repository in
`~/.local/share/kobietyoceanow/`.

Copy `.env.example` to `.env` and adjust local credentials or SSH settings if
needed. Start the production read-only synchronization with:

```sh
scripts/sync-production-to-local.sh
```

The script verifies production URLs and prefix over SSH before syncing files or
exporting the database. Production access is restricted to read-only WP-CLI
commands and `rsync` downloads. It streams the SQL export over SSH; no dump is
created on production. Local volumes are retained across runs and are never
removed by the script.

After the first production sync initializes the database, start services with
`docker compose up -d`; invoke WP-CLI with `docker compose run --rm wpcli
<command>`. Inspect health with `docker compose ps`.

On Windows, verify that port 8081 is unused before bringing up the stack.
Windows must forward only the current Tailscale IPv4 address to `127.0.0.1:8081`
and allow inbound TCP 8081 only on the Tailscale interface from
`100.64.0.0/10`. The WSL workspace cannot safely configure or verify the
elevated Windows portproxy and firewall rules; use the restricted commands
from the project setup prompt in an elevated PowerShell session if they are
absent. Confirm access from another tailnet device separately.
