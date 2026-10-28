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

/*
 * BEM classes for menus rendered with the custom wp_nav_menu() arg `if_untitled_block` ({block}__item,
 * {block}__link, {block}__submenu, state classes is-current / has-submenu) instead of WordPress' generic ones.
 */
add_filter('nav_menu_css_class', function (array $classes, WP_Post $item, stdClass $args, int $depth): array {
    if (empty($args->if_untitled_block)) {
        return $classes;
    }
    $block  = $args->if_untitled_block;
    $anchor = str_contains((string) $item->url, '#'); // in-page anchors are never "current"
    return array_filter([
        "{$block}__item",
        $depth > 0 ? "{$block}__item--sub" : '',
        in_array('menu-item-has-children', $classes, true) ? 'has-submenu' : '',
        !$anchor && if_untitled_menu_item_is_current($item, $classes) ? 'is-current' : '',
    ]);
}, 10, 4);

/**
 * Current section: WordPress' own flags, except that the posts page (Journal) is only the parent of journal content
 * (core marks it on every non-page post) and the Work archive is also the parent of projects and their terms.
 */
function if_untitled_menu_item_is_current(WP_Post $item, array $classes): bool
{
    if (array_intersect(['current-menu-item', 'current-menu-ancestor'], $classes)) {
        return true;
    }
    if ($item->type === 'post_type_archive') {
        $taxonomies = get_object_taxonomies((string) $item->object);
        return is_singular($item->object) || ($taxonomies && is_tax($taxonomies));
    }
    return in_array('current_page_parent', $classes, true)
        && (is_singular('post') || is_category() || is_tag() || is_date() || is_author());
}

add_filter('nav_menu_item_id', fn(string $id, WP_Post $item, stdClass $args): string => empty($args->if_untitled_block) ? $id : '', 10, 3);

add_filter('nav_menu_link_attributes', function (array $atts, WP_Post $item, stdClass $args): array {
    if (!empty($args->if_untitled_block)) {
        $atts['class'] = "{$args->if_untitled_block}__link";
        if (str_contains((string) $item->url, '#')) {
            unset($atts['aria-current']);
        }
    }
    return $atts;
}, 10, 3);

add_filter('nav_menu_submenu_css_class', fn(array $classes, stdClass $args): array => empty($args->if_untitled_block) ? $classes : ["{$args->if_untitled_block}__submenu"], 10, 2);

/*
 * Work (the project archive menu item) gets its submenu from code: the project categories in the Work filter order
 * (if_untitled_archive_filter(), non-empty only), so new categories appear without editing the menu.
 */
add_filter('wp_nav_menu_objects', function (array $items, stdClass $args): array {
    if (empty($args->if_untitled_block) || (int) ($args->depth ?? 0) === 1) {
        return $items;
    }
    $out = [];
    foreach ($items as $item) {
        $out[] = $item;
        if ($item->type !== 'post_type_archive' || $item->object !== 'project' || (int) $item->menu_item_parent) {
            continue;
        }
        $terms = if_untitled_archive_filter('project_category', '', '');
        array_shift($terms); // "All" = Work itself
        if (!$terms) {
            continue;
        }
        $item->classes[] = 'menu-item-has-children';
        foreach ($terms as $i => $term) {
            $out[] = new WP_Post((object) [
                'ID'               => -1 - $i,
                'db_id'            => -1 - $i,
                'menu_item_parent' => (string) $item->db_id,
                'post_type'        => 'nav_menu_item',
                'type'             => 'taxonomy',
                'object'           => 'project_category',
                'title'            => $term['label'],
                'url'              => $term['url'],
                'classes'          => $term['is_active'] ? ['current-menu-item'] : [],
                'target'           => '',
                'attr_title'       => '',
                'description'      => '',
                'xfn'              => '',
                'current'          => $term['is_active'],
            ]);
        }
    }
    return $out;
}, 10, 2);
