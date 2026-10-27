<?php
/**
 * Site structure (implementation plan 2.3): permalinks, pages, reading settings, entry types, menus.
 *
 * Idempotent — run on every environment from the theme folder: `wp eval-file bin/setup.php [seeds-dir]`.
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

$if_untitled_seeds = $args[0] ?? dirname(__DIR__, 4) . '/.github/plans/phase-b/seeds';

/**
 * Reads a CSV with a header row into associative rows.
 *
 * @return array<int, array<string, string>>
 */
function if_untitled_setup_csv(string $file): array
{
    if (!is_readable($file)) {
        WP_CLI::error("Missing {$file}");
    }
    $handle = fopen($file, 'r');
    $head   = fgetcsv($handle, null, ',', '"', '');
    $rows   = [];
    while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
        if ($row !== [null]) {
            $rows[] = array_combine($head, array_map('trim', $row));
        }
    }
    fclose($handle);
    return $rows;
}

// Permalinks and media.
update_option('permalink_structure', '/journal/%postname%/');
update_option('category_base', '');
update_option('tag_base', '');
update_option('wp_attachment_pages_enabled', 0);
WP_CLI::log('Permalinks: /journal/%postname%/');

// Core sample content.
foreach (['post' => 'hello-world', 'page' => 'sample-page'] as $if_untitled_type => $if_untitled_slug) {
    $if_untitled_sample = get_page_by_path($if_untitled_slug, OBJECT, $if_untitled_type);
    if ($if_untitled_sample) {
        wp_delete_post($if_untitled_sample->ID, true);
        WP_CLI::log("Deleted sample {$if_untitled_type}: {$if_untitled_slug}");
    }
}

// Empty pages — the seed only fills them by slug.
$if_untitled_pages = [
    'home'     => ['Home', ''],
    'journal'  => ['Journal', ''],
    'studio'   => ['Studio', 'page-templates/studio.php'],
    'advisory' => ['Advisory', 'page-templates/advisory.php'],
    'contact'  => ['Contact', 'page-templates/contact.php'],
];
$if_untitled_page_ids = [];
foreach ($if_untitled_pages as $if_untitled_slug => [$if_untitled_title, $if_untitled_template]) {
    $if_untitled_page = get_page_by_path($if_untitled_slug);
    $if_untitled_id   = $if_untitled_page ? $if_untitled_page->ID : wp_insert_post([
        'post_type'   => 'page',
        'post_status' => 'publish',
        'post_title'  => $if_untitled_title,
        'post_name'   => $if_untitled_slug,
    ], true);
    if (is_wp_error($if_untitled_id)) {
        WP_CLI::error($if_untitled_id);
    }
    // Set directly: core rejects a template whose file doesn't exist yet (built in Phase B).
    if ($if_untitled_template) {
        update_post_meta($if_untitled_id, '_wp_page_template', $if_untitled_template);
    }
    $if_untitled_page_ids[$if_untitled_slug] = $if_untitled_id;
    WP_CLI::log(($if_untitled_page ? 'Page exists: ' : 'Page created: ') . $if_untitled_slug);
}

update_option('show_on_front', 'page');
update_option('page_on_front', $if_untitled_page_ids['home']);
update_option('page_for_posts', $if_untitled_page_ids['journal']);

// Entry types: create, order, make Project notes the default, then delete Uncategorized.
global $wpdb;
$if_untitled_term_order = (bool) $wpdb->get_var("SHOW COLUMNS FROM {$wpdb->terms} LIKE 'term_order'");
foreach (if_untitled_setup_csv("{$if_untitled_seeds}/entry-types.csv") as $if_untitled_row) {
    $if_untitled_term = term_exists($if_untitled_row['slug'], 'category');
    $if_untitled_term = $if_untitled_term
        ? wp_update_term((int) $if_untitled_term['term_id'], 'category', [
            'name'        => $if_untitled_row['name'],
            'description' => $if_untitled_row['description'],
        ])
        : wp_insert_term($if_untitled_row['name'], 'category', [
            'slug'        => $if_untitled_row['slug'],
            'description' => $if_untitled_row['description'],
        ]);
    if (is_wp_error($if_untitled_term)) {
        WP_CLI::error($if_untitled_term);
    }
    if ($if_untitled_term_order) {
        $wpdb->update($wpdb->terms, ['term_order' => (int) $if_untitled_row['order']], ['term_id' => $if_untitled_term['term_id']]);
    }
    WP_CLI::log("Entry type: {$if_untitled_row['name']}");
}
update_option('default_category', get_term_by('slug', 'project-notes', 'category')->term_id);
$if_untitled_uncategorized = get_term_by('slug', 'uncategorized', 'category');
if ($if_untitled_uncategorized) {
    wp_delete_term($if_untitled_uncategorized->term_id, 'category');
    WP_CLI::log('Deleted Uncategorized');
}

// Menu = structure only; Advisory sub-items are added once the service areas exist (Q18).
$if_untitled_menu    = wp_get_nav_menu_object('Primary');
$if_untitled_menu_id = $if_untitled_menu ? $if_untitled_menu->term_id : wp_create_nav_menu('Primary');
if (!wp_get_nav_menu_items($if_untitled_menu_id)) {
    $if_untitled_add_page = fn(string $slug, int $parent = 0): int => wp_update_nav_menu_item($if_untitled_menu_id, 0, [
        'menu-item-object'    => 'page',
        'menu-item-object-id' => $if_untitled_page_ids[$slug],
        'menu-item-type'      => 'post_type',
        'menu-item-parent-id' => $parent,
        'menu-item-status'    => 'publish',
    ]);

    $if_untitled_studio = $if_untitled_add_page('studio');
    foreach (['Architecture & Interior Design', 'Experience Curation'] as $if_untitled_service) {
        wp_update_nav_menu_item($if_untitled_menu_id, 0, [
            'menu-item-title'     => $if_untitled_service,
            'menu-item-url'       => home_url('/studio/#' . sanitize_title($if_untitled_service)),
            'menu-item-type'      => 'custom',
            'menu-item-parent-id' => $if_untitled_studio,
            'menu-item-status'    => 'publish',
        ]);
    }
    $if_untitled_add_page('advisory');
    wp_update_nav_menu_item($if_untitled_menu_id, 0, [
        'menu-item-title'  => 'Work',
        'menu-item-object' => 'project',
        'menu-item-type'   => 'post_type_archive',
        'menu-item-status' => 'publish',
    ]);
    $if_untitled_add_page('journal');
    $if_untitled_add_page('contact');
    WP_CLI::log('Menu created: Primary');
}
// One menu for both locations; the footer renders depth 1.
set_theme_mod('nav_menu_locations', [
    'primary_menu' => $if_untitled_menu_id,
    'footer_menu'  => $if_untitled_menu_id,
]);

flush_rewrite_rules(false);
WP_CLI::success('Site structure applied.');
