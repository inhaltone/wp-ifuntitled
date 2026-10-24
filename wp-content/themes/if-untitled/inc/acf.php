<?php
/**
 * Advanced Custom Fields integration (optional — safe when ACF is inactive).
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

add_action('acf/init', function (): void {
    if (function_exists('acf_add_options_page')) {
        acf_add_options_page([
            'page_title' => __('Theme Options', 'if-untitled'),
            'menu_slug'  => 'if-untitled-options',
            'capability' => 'edit_theme_options',
        ]);
    }
});

// Save/load ACF field groups as JSON inside the theme (version-controlled).
add_filter('acf/settings/save_json', fn() => IF_UNTITLED_DIR . '/acf-json');
add_filter('acf/settings/load_json', function (array $paths): array {
    $paths[] = IF_UNTITLED_DIR . '/acf-json';
    return $paths;
});
