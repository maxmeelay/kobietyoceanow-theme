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
