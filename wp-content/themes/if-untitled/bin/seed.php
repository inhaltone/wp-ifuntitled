<?php
/**
 * Content seed (implementation plan §3): reads only the curated CSVs in the seeds folder.
 *
 * Idempotent — items are matched by slug and updated. Run from the theme folder: `bin/seed.sh [seeds-dir]`.
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/lib.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$if_untitled_seeds = if_untitled_seeds_dir($args);

// Preconditions.
global $wpdb;
if (!function_exists('update_field')) {
    WP_CLI::error('ACF Pro is not active.');
}
if (!$wpdb->get_var("SHOW COLUMNS FROM {$wpdb->terms} LIKE 'term_order'")) {
    WP_CLI::error('The ordering plugin (Simple Custom Post Order) is not active — step 2.1.');
}
if (apply_filters('max_srcset_image_width', 2048, [0, 0]) !== 2560) {
    WP_CLI::error('inc/media.php is not loaded — step 2.4.');
}
if (!(int) get_option('page_on_front') || !get_page_by_path('studio')) {
    WP_CLI::error('Site structure missing — run `wp eval-file bin/setup.php` first (step 2.3).');
}

/**
 * Finds a post of a type by slug (any status).
 */
function if_untitled_seed_find(string $type, string $slug): int
{
    $ids = get_posts([
        'post_type'        => $type,
        'name'             => $slug,
        'post_status'      => 'any',
        'posts_per_page'   => 1,
        'fields'           => 'ids',
        'suppress_filters' => true,
    ]);
    return (int) ($ids[0] ?? 0);
}

/**
 * Creates or updates a post by slug.
 */
function if_untitled_seed_upsert_post(string $type, array $data): int
{
    $data['post_type'] = $type;
    $data['post_name'] = sanitize_title($data['post_title']);
    $data['ID']        = if_untitled_seed_find($type, $data['post_name']);
    $id                = $data['ID'] ? wp_update_post($data, true) : wp_insert_post($data, true);
    if (is_wp_error($id)) {
        WP_CLI::error($id);
    }
    return (int) $id;
}

/**
 * Imports an image once: `seeds/images/{file}` if present, else downloads the URL. The URL is stored in
 * `_if_untitled_source` so a re-run reuses the attachment.
 */
function if_untitled_seed_image(string $seeds, string $file, string $url, int $parent, string $title): int
{
    $source = $url ?: $file;
    if ($source === '') {
        return 0;
    }
    $existing = get_posts([
        'post_type'        => 'attachment',
        'post_status'      => 'inherit',
        'meta_key'         => '_if_untitled_source',
        'meta_value'       => $source,
        'posts_per_page'   => 1,
        'fields'           => 'ids',
        'suppress_filters' => true,
    ]);
    if ($existing) {
        return (int) $existing[0];
    }

    $file  = $file ?: basename((string) wp_parse_url($url, PHP_URL_PATH));
    $local = "{$seeds}/images/{$file}";
    if (is_readable($local)) {
        $tmp = wp_tempnam($file);
        copy($local, $tmp);
    } elseif ($url) {
        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            WP_CLI::warning("Image skipped ({$tmp->get_error_message()}): {$url}");
            return 0;
        }
    } else {
        WP_CLI::warning("Image not found: {$local}");
        return 0;
    }

    $id = media_handle_sideload(['name' => $file, 'tmp_name' => $tmp], $parent, $title);
    if (is_wp_error($id)) {
        wp_delete_file($tmp);
        WP_CLI::warning("Image skipped ({$id->get_error_message()}): {$source}");
        return 0;
    }
    update_post_meta($id, '_if_untitled_source', $source);
    update_post_meta($id, '_wp_attachment_image_alt', $title);
    WP_CLI::log("  image: {$file}");
    return $id;
}

// Project categories.
foreach (if_untitled_read_csv("{$if_untitled_seeds}/categories.csv") as $if_untitled_row) {
    if_untitled_upsert_term('project_category', $if_untitled_row);
}
WP_CLI::log('Project categories: done');

// Locations: country › city.
$if_untitled_cities = [];
foreach (if_untitled_read_csv("{$if_untitled_seeds}/locations.csv") as $if_untitled_row) {
    $if_untitled_country = if_untitled_upsert_term('project_location', [
        'name' => $if_untitled_row['country'],
        'slug' => sanitize_title($if_untitled_row['country']),
    ]);
    $if_untitled_slug    = sanitize_title($if_untitled_row['city']);
    $if_untitled_city    = term_exists($if_untitled_slug, 'project_location', $if_untitled_country);
    $if_untitled_city    = $if_untitled_city ?: wp_insert_term($if_untitled_row['city'], 'project_location', [
        'slug'   => $if_untitled_slug,
        'parent' => $if_untitled_country,
    ]);
    if (is_wp_error($if_untitled_city)) {
        WP_CLI::error($if_untitled_city);
    }
    $if_untitled_cities["{$if_untitled_row['city']}, {$if_untitled_row['country']}"] = (int) $if_untitled_city['term_id'];
}
WP_CLI::log('Locations: done');

// Projects. Validate every row before writing anything.
$if_untitled_projects = if_untitled_read_csv("{$if_untitled_seeds}/projects.csv");
$if_untitled_statuses = ['' => 'completed', 'completed' => 'completed', 'in progress' => 'in_progress', 'planned' => 'planned'];
foreach ($if_untitled_projects as $if_untitled_i => $if_untitled_row) {
    $if_untitled_line = $if_untitled_i + 2;
    if (!get_term_by('name', $if_untitled_row['category'], 'project_category')) {
        WP_CLI::error("projects.csv line {$if_untitled_line}: unknown category \"{$if_untitled_row['category']}\".");
    }
    $if_untitled_place = preg_replace('/\s*,\s*([^,]+)$/', ', $1', $if_untitled_row['location']);
    if ($if_untitled_place !== '' && !isset($if_untitled_cities[$if_untitled_place])) {
        WP_CLI::error("projects.csv line {$if_untitled_line}: unknown location \"{$if_untitled_row['location']}\" — add it to locations.csv.");
    }
    if (!isset($if_untitled_statuses[strtolower($if_untitled_row['status'])])) {
        WP_CLI::error("projects.csv line {$if_untitled_line}: unknown status \"{$if_untitled_row['status']}\".");
    }
}

foreach ($if_untitled_projects as $if_untitled_i => $if_untitled_row) {
    $if_untitled_title = $if_untitled_row['title'];
    WP_CLI::log("Project: {$if_untitled_title}");

    $if_untitled_id = if_untitled_seed_upsert_post('project', [
        'post_title'   => $if_untitled_title,
        'post_excerpt' => $if_untitled_row['excerpt'],
        'post_status'  => strtolower($if_untitled_row['publish']) === 'yes' ? 'publish' : 'draft',
        'menu_order'   => $if_untitled_i + 1,
    ]);

    $if_untitled_clients = [];
    foreach (array_filter(array_map('trim', explode('|', $if_untitled_row['client']))) as $if_untitled_client) {
        $if_untitled_clients[] = if_untitled_seed_find('client', sanitize_title($if_untitled_client))
            ?: if_untitled_seed_upsert_post('client', ['post_title' => $if_untitled_client, 'post_status' => 'publish']);
    }

    $if_untitled_place = preg_replace('/\s*,\s*([^,]+)$/', ', $1', $if_untitled_row['location']);
    update_field('project_category', get_term_by('name', $if_untitled_row['category'], 'project_category')->term_id, $if_untitled_id);
    update_field('project_text', $if_untitled_row['project_text'], $if_untitled_id);
    update_field('project_metadata', [
        'location' => $if_untitled_cities[$if_untitled_place] ?? '',
        'client'   => $if_untitled_clients,
        'year'     => $if_untitled_row['year'],
        'status'   => $if_untitled_statuses[strtolower($if_untitled_row['status'])],
    ], $if_untitled_id);

    $if_untitled_thumb = if_untitled_seed_image(
        $if_untitled_seeds,
        $if_untitled_row['featured_image'],
        $if_untitled_row['featured_image_url'],
        $if_untitled_id,
        $if_untitled_title
    );
    if ($if_untitled_thumb) {
        set_post_thumbnail($if_untitled_id, $if_untitled_thumb);
    }

    $if_untitled_grid = [];
    foreach (array_filter(array_map('trim', explode('|', $if_untitled_row['gallery_urls']))) as $if_untitled_url) {
        $if_untitled_image = if_untitled_seed_image($if_untitled_seeds, '', $if_untitled_url, $if_untitled_id, $if_untitled_title);
        if ($if_untitled_image) {
            // Seamless clones take flat sub-field names, the same shape get_field() returns.
            $if_untitled_grid[] = [
                'acf_fc_layout' => 'image',
                'media_type'    => 'image',
                'image'         => $if_untitled_image,
                'image_columns' => '1-5',
            ];
        }
    }
    update_field('content_grid', $if_untitled_grid, $if_untitled_id);
}
WP_CLI::log(count($if_untitled_projects) . ' projects: done');

// Team, then the Studio page's team list in CSV order.
$if_untitled_team = if_untitled_read_csv("{$if_untitled_seeds}/team.csv");
usort($if_untitled_team, fn(array $a, array $b): int => (int) $a['order'] <=> (int) $b['order']);
$if_untitled_members = [];
foreach ($if_untitled_team as $if_untitled_row) {
    WP_CLI::log("Team member: {$if_untitled_row['name']}");
    $if_untitled_id = if_untitled_seed_upsert_post('team_member', [
        'post_title'  => $if_untitled_row['name'],
        'post_status' => 'publish',
        'menu_order'  => (int) $if_untitled_row['order'],
    ]);
    update_field('team_role', $if_untitled_row['role'], $if_untitled_id);
    update_field('team_bio', $if_untitled_row['bio'], $if_untitled_id);
    $if_untitled_portrait = if_untitled_seed_image(
        $if_untitled_seeds,
        $if_untitled_row['image'],
        $if_untitled_row['image_url'],
        $if_untitled_id,
        $if_untitled_row['name']
    );
    if ($if_untitled_portrait) {
        set_post_thumbnail($if_untitled_id, $if_untitled_portrait);
    }
    $if_untitled_members[] = $if_untitled_id;
}
update_field('studio_team', $if_untitled_members, get_page_by_path('studio')->ID);
WP_CLI::log('Studio team: ' . count($if_untitled_members) . ' members');

// Data sets without a curated CSV yet.
foreach (['clients', 'pages', 'advisory', 'journal', 'options'] as $if_untitled_set) {
    if (!is_readable("{$if_untitled_seeds}/{$if_untitled_set}.csv")) {
        WP_CLI::log("Skipped {$if_untitled_set}.csv (not curated yet).");
    }
}

WP_CLI::success('Seed applied.');
