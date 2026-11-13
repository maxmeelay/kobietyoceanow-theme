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