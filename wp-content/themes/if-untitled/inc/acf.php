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

// Same key for the admin location picker (Theme Options → Locations) as for the Contact map.
add_filter('acf/fields/google_map/api', function (array $api): array {
    $key = if_untitled_google_maps()['key'];
    if ($key !== '') {
        $api['key'] = $key;
    }
    return $api;
});

/**
 * Query args for post object / relationship pickers: published only; no post type set = any public content.
 */
function if_untitled_acf_picker_query(array $args, array $field): array
{
    $args['post_status'] = 'publish';
    if (empty($field['post_type'])) {
        $args['post_type'] = array_values(array_diff(get_post_types(['public' => true]), ['attachment']));
    }
    // Advisory showcase: projects in the Advisory Projects category only.
    $parent = !empty($field['parent']) && function_exists('acf_get_field') ? acf_get_field($field['parent']) : null;
    if (($parent['name'] ?? '') === 'advisory_features') {
        $args['tax_query'] = [[
            'taxonomy' => 'project_category',
            'field'    => 'slug',
            'terms'    => 'advisory-projects',
        ]];
    }
    return $args;
}
add_filter('acf/fields/post_object/query', 'if_untitled_acf_picker_query', 10, 2);
add_filter('acf/fields/relationship/query', 'if_untitled_acf_picker_query', 10, 2);

/**
 * Submitted field values (keyed by field key, clones flattened) → keyed by field name.
 */
function if_untitled_acf_named_values(array $values): array
{
    $named = [];
    foreach ($values as $key => $value) {
        if (!is_string($key) || !str_starts_with($key, 'field_')) {
            continue;
        }
        // Seamless clones submit "field_{clone}_field_{sub}": the last key is the real field.
        $field = acf_get_field(substr($key, strrpos($key, 'field_')));
        if (!$field) {
            continue;
        }
        if ($field['type'] === 'clone' && is_array($value)) {
            $named += if_untitled_acf_named_values($value);
            continue;
        }
        $named[$field['name']] = $value;
    }
    return $named;
}

// content_grid rows: text blocks need a heading or body; image + text must not overlap.
add_filter('acf/validate_value/name=content_grid', function ($valid, $value) {
    if ($valid !== true || !is_array($value)) {
        return $valid;
    }
    $number = 0;
    foreach ($value as $row) {
        if (!is_array($row) || ($row['acf_fc_layout'] ?? '') === '') {
            continue;
        }
        $number++;
        $layout = $row['acf_fc_layout'];
        $fields = if_untitled_acf_named_values($row);

        if (in_array($layout, ['text', 'image_text'], true)
            && trim((string) ($fields['heading'] ?? '')) === '' && trim((string) ($fields['body'] ?? '')) === '') {
            /* translators: %d: row number */
            return sprintf(__('Row %d: the text needs a heading or a body.', 'if-untitled'), $number);
        }

        if ($layout === 'image_text') {
            $image = if_untitled_column_range((string) ($fields['image_columns'] ?? ''));
            $text  = if_untitled_column_range((string) ($fields['text_columns'] ?? ''));
            if ($image && $text && $image[0] <= $text[1] && $text[0] <= $image[1]) {
                /* translators: %d: row number */
                return sprintf(__('Row %d: the image and the text columns overlap.', 'if-untitled'), $number);
            }
        }
    }
    return $valid;
}, 10, 2);

add_filter('acf/validate_value/name=youtube_url', function ($valid, $value) {
    if ($valid === true && $value !== '' && $value !== null && !if_untitled_youtube_id((string) $value)) {
        return __('Enter a YouTube video URL (youtube.com/watch?v=… or youtu.be/…).', 'if-untitled');
    }
    return $valid;
}, 10, 2);

// A project location is a city (a child term), never a country.
add_filter('acf/validate_value/type=taxonomy', function ($valid, $value, array $field) {
    if ($valid !== true || ($field['taxonomy'] ?? '') !== 'project_location' || empty($value)) {
        return $valid;
    }
    foreach ((array) $value as $term_id) {
        $term = get_term((int) $term_id, 'project_location');
        if ($term instanceof WP_Term && !$term->parent) {
            return __('Pick a city, not a country.', 'if-untitled');
        }
    }
    return $valid;
}, 10, 3);
