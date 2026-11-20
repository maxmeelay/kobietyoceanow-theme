<?php
// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';

// Initialize Timber.
Timber\Timber::init();

add_action('wp_enqueue_scripts', function () {
    $stylesheet_path = get_stylesheet_directory() . '/style.css';
    wp_enqueue_style(
        'kobietyoceanow-theme',
        get_stylesheet_uri(),
        [],
        file_exists($stylesheet_path) ? (string) filemtime($stylesheet_path) : null
    );
});

add_action('wp_footer', function() {
    ?>
<style>
@media (max-width: 991px) {


.intro .tracking-sm,
.intro .tracking-\[0\.35em\] {
    letter-spacing: 0;
}

}
</style>
<?php
});

add_filter('acf/settings/save_json', function ($path) {
    return get_stylesheet_directory() . '/acf-json';
});

add_filter('acf/settings/load_json', function ($paths) {
    $custom_path = get_stylesheet_directory() . '/acf-json';

    if (!in_array($custom_path, $paths, true)) {
        $paths[] = $custom_path;
    }

    return $paths;
});

add_filter('timber/twig', function ($twig) {
    $twig->addFunction(new \Twig\TwigFunction('ko_home_url', function () {
        return function_exists('pll_home_url') ? pll_home_url() : home_url('/');
    }));
    $twig->addFunction(new \Twig\TwigFunction('ko_mailto', function ($to, $subject, $body) {
        $to = sanitize_email((string) $to);
        if ($to === '') {
            return '';
        }

        return 'mailto:' . $to . '?subject=' . rawurlencode((string) $subject) . '&body=' . rawurlencode((string) $body);
    }));

    return $twig;
});

add_filter('render_block', function ($content, $block) {
    if (($block['blockName'] ?? '') !== 'core/post-template'
        || !is_page([511, 547, 1498])) {
        return $content;
    }

    $content = preg_replace_callback('/<li\b([^>]*)>(.*?)<\/li>/s', function ($matches) {
        if (!preg_match('/\bpost-(\d+)\b/', $matches[1], $post_match)) {
            return $matches[0];
        }

        $post_id = (int) $post_match[1];
        $post = get_post($post_id);
        if (!$post instanceof WP_Post) {
            return $matches[0];
        }

        $permalink = get_permalink($post);
        $image = get_the_post_thumbnail($post_id, 'large', [
            'class' => 'ko-archive-card__image',
            'loading' => 'lazy',
        ]);
        $image_markup = $image ?: '<span class="ko-archive-card__image-placeholder" aria-hidden="true"></span>';
        $excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt($post)), 26, '…');

        return '<li' . $matches[1] . '><article class="ko-archive-card group">'
            . '<a class="ko-archive-card__media" href="' . esc_url($permalink) . '">' . $image_markup . '</a>'
            . '<h2 class="ko-archive-card__title"><a href="' . esc_url($permalink) . '">' . esc_html(get_the_title($post)) . '</a></h2>'
            . '<p class="ko-archive-card__excerpt">' . esc_html($excerpt) . '</p>'
            . '<a class="ko-archive-card__read-more wp-block-read-more" href="' . esc_url($permalink) . '" target="_self">Zobacz wpis<span class="screen-reader-text">: ' . esc_html(get_the_title($post)) . '</span></a>'
            . '</article></li>';
    }, $content);

    if (is_string($content)) {
        $content = preg_replace('/(<ul\b)([^>]*class=["\'])([^"\']*)(["\'])/', '$1$2$3 ko-archive-grid$4', $content, 1);
    }

    return $content;
}, 20, 2);

add_filter('render_block', function ($content, $block) {
    if (!function_exists('pll_current_language')
        || pll_current_language('slug') !== 'en'
        || !is_front_page()) {
        return $content;
    }

    if (($block['blockName'] ?? '') === 'core/post-content'
        || (($block['blockName'] ?? '') === 'core/block'
            && (int) ($block['attrs']['ref'] ?? 0) === 838)) {
        return strtr($content, [
            'Kontakt' => 'Contact',
            'Porozmawiajmy' => "Let's talk",
            'Masz pytania? Chcesz nawiązać współpracę,' => 'Have questions? Want to collaborate,',
            'dołączyć do naszych działań lub dowiedzieć się więcej?' => 'get involved in our work or find out more?',
            'e-mail' => 'Email',
            'telefon' => 'Phone',
            'adres Fundacji' => 'Foundation address',
            'Numer konta' => 'Bank account',
            'media społecznościowe' => 'Social media',
            'Nie pytaj, czy dasz radę.' => "Don't ask if you can do it.",
            'Zapytaj, od czego zacząć.' => 'Ask where to begin.',
        ]);
    }

    if (($block['blockName'] ?? '') !== 'core/template-part') {
        return $content;
    }

    $translations = match ($block['attrs']['slug'] ?? '') {
        'nag-wek' => [
            'O Patronce' => 'Our Patron',
            'O Nas' => 'About Us',
            'Z nami' => 'Join Us',
            'Dziennik' => 'Journal',
            'Aktualności' => 'News',
            'Rejs' => 'The Voyage',
            'Partnerzy' => 'Partners',
            'Wesprzyj' => 'Support Us',
            'Kontakt' => 'Contact',
        ],
        'stopka' => [
            'Nawigacja' => 'Navigation',
            'O Patronce' => 'Our Patron',
            'O Nas' => 'About Us',
            'Z nami' => 'Join Us',
            'Dziennik' => 'Journal',
            'Aktualności' => 'News',
            'Rejs' => 'The Voyage',
            'Partnerzy' => 'Partners',
            'Dołącz' => 'Join Us',
            'Wesprzyj' => 'Support Us',
            'Kontakt' => 'Contact',
            'Statut' => 'Statute',
            'Polityka prywatności' => 'Privacy Policy',
            'Wszystkie prawa zastrzeżone' => 'All rights reserved',
        ],
        default => [],
    };

    return $translations ? strtr($content, $translations) : $content;
}, 20, 2);
