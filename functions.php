<?php
// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';

// Initialize Timber.
Timber\Timber::init();

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
