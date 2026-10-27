<?php
/**
 * Shared helpers for the WP-CLI scripts in bin/ (setup, seed).
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

/**
 * Default seeds folder: the repo's .github/plans/phase-b/seeds.
 */
function if_untitled_seeds_dir(array $args): string
{
    return rtrim($args[0] ?? dirname(__DIR__, 4) . '/.github/plans/phase-b/seeds', '/');
}

/**
 * Reads a CSV with a header row into associative rows (values trimmed). Stops the script if the file is missing.
 *
 * @return array<int, array<string, string>>
 */
function if_untitled_read_csv(string $file): array
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

/**
 * Stores a manual term order (column added by the ordering plugin).
 */
function if_untitled_set_term_order(int $term_id, int $order): void
{
    global $wpdb;
    static $has_column = null;
    $has_column ??= (bool) $wpdb->get_var("SHOW COLUMNS FROM {$wpdb->terms} LIKE 'term_order'");
    if (!$has_column) {
        return;
    }
    $wpdb->update($wpdb->terms, ['term_order' => $order], ['term_id' => $term_id]);
}

/**
 * Creates or updates a term by slug (name, description, order).
 */
function if_untitled_upsert_term(string $taxonomy, array $row): int
{
    $existing = term_exists($row['slug'], $taxonomy);
    $args     = ['description' => $row['description'] ?? ''];
    $term     = $existing
        ? wp_update_term((int) $existing['term_id'], $taxonomy, $args + ['name' => $row['name']])
        : wp_insert_term($row['name'], $taxonomy, $args + ['slug' => $row['slug']]);
    if (is_wp_error($term)) {
        WP_CLI::error($term);
    }
    if (isset($row['order']) && $row['order'] !== '') {
        if_untitled_set_term_order((int) $term['term_id'], (int) $row['order']);
    }
    return (int) $term['term_id'];
}
