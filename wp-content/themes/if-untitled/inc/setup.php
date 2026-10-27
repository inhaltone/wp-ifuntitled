<?php
/**
 * Theme supports, menus and head cleanup.
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

add_action('after_setup_theme', function (): void {
    load_theme_textdomain('if-untitled', IF_UNTITLED_DIR . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets']);

    register_nav_menus([
        'primary_menu' => __('Primary Menu', 'if-untitled'),
        'footer_menu'  => __('Footer Menu', 'if-untitled'),
    ]);
});

// Head cleanup.
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');

// Classic theme without theme.json: drop global-styles/classic-theme CSS.
// wp-block-library is kept so block content from the_content() still renders correctly.
add_action('wp_enqueue_scripts', function (): void {
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
}, 100);

// Flag JS availability early (CSS uses html.js to show the transition overlay).
add_action('wp_head', function (): void {
    wp_print_inline_script_tag("document.documentElement.classList.add('js');");
}, 0);

// Dark theme (black background, white text) on the Journal archive and Contact (designs).
add_filter('body_class', function (array $classes): array {
    if (is_home() || is_category() || is_tag() || is_page_template('page-templates/contact.php')) {
        $classes[] = 'is-dark';
    }
    return $classes;
});
