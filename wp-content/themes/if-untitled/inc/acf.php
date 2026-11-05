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
    // Advisory featured: projects in the Advisory Projects category only.
    if (($field['name'] ?? '') === 'advisory_featured') {
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
 * Submitted field values (keyed by field key) → keyed by field name. Seamless clones are flattened, group clones
 * (content_grid pairs: image_1, text_2, …) nested under their own name.
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
            if (($field['display'] ?? '') === 'group') {
                $named[$field['name']] = if_untitled_acf_named_values($value);
            } else {
                $named += if_untitled_acf_named_values($value);
            }
            continue;
        }
        $named[$field['name']] = $value;
    }
    return $named;
}

// content_grid rows: text blocks need a heading or body; the blocks of a row must not overlap.
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
        $ranges = [];

        foreach (if_untitled_content_grid_parts($row['acf_fc_layout'], if_untitled_acf_named_values($row)) as [$kind, $fields]) {
            if ($kind === 'text'
                && trim((string) ($fields['heading'] ?? '')) === '' && trim((string) ($fields['body'] ?? '')) === '') {
                /* translators: %d: row number */
                return sprintf(__('Row %d: every text needs a heading or a body.', 'if-untitled'), $number);
            }
            $range = if_untitled_column_range((string) ($fields["{$kind}_columns"] ?? ''));
            foreach ($range ? $ranges : [] as $other) {
                if ($range[0] <= $other[1] && $other[0] <= $range[1]) {
                    /* translators: %d: row number */
                    return sprintf(__('Row %d: the columns of the blocks overlap.', 'if-untitled'), $number);
                }
            }
            if ($range) {
                $ranges[] = $range;
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

// content_grid image block: the image is the poster of a video / YouTube block. Both labels are rendered and the
// admin style below shows the one matching the selected media type (ACF labels can't depend on another field).
add_filter('acf/prepare_field/type=image', function ($field) {
    if (!is_array($field) || !str_contains($field['wrapper']['class'] ?? '', 'if-untitled-media-poster')) {
        return $field;
    }
    $field['label']        = sprintf(
        '<span class="if-untitled-media-label-image">%s</span><span class="if-untitled-media-label-poster">%s</span>',
        esc_html__('Image', 'if-untitled'),
        esc_html__('Poster image', 'if-untitled')
    );
    $field['instructions'] = sprintf(
        '<span class="if-untitled-media-label-poster">%s</span>',
        esc_html__('A frame of the video (YouTube: 16:9). Reserves the space and is shown until the video plays.', 'if-untitled')
    );
    return $field;
});

add_action('acf/input/admin_head', function (): void {
    $video = '.acf-fields:has(> .if-untitled-media-type :is(input[value="video"], input[value="youtube"]):checked) > .if-untitled-media-poster';
    ?>
    <style>
        .if-untitled-media-poster .if-untitled-media-label-poster,
        .if-untitled-media-poster .description:has(> .if-untitled-media-label-poster) { display: none; }
        <?php echo $video; // Static selector. ?> .if-untitled-media-label-image { display: none; }
        <?php echo $video; ?> .if-untitled-media-label-poster { display: inline; }
        <?php echo $video; ?> .description:has(> .if-untitled-media-label-poster) { display: block; }
    </style>
    <?php
});

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
