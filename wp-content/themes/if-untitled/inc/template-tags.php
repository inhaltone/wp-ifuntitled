<?php
/**
 * Small reusable helpers for templates. Prefix everything with `if_untitled_`.
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

/**
 * Whether a date string is in the past.
 */
function if_untitled_is_date_passed(string $date): bool
{
    try {
        return new DateTimeImmutable($date) < new DateTimeImmutable('now', wp_timezone());
    } catch (Exception) {
        return false;
    }
}

/**
 * Render a component from /components with typed args.
 * Usage: if_untitled_component('project-card', ['priority' => true]);
 */
function if_untitled_component(string $name, array $args = []): void
{
    get_template_part('components/' . $name, null, $args);
}

/**
 * The 11-character video ID of a YouTube URL (watch, youtu.be, embed, shorts, live), or '' if it isn't one.
 */
function if_untitled_youtube_id(string $url): string
{
    $pattern = '~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([\w-]{11})~';

    return preg_match($pattern, $url, $match) ? $match[1] : '';
}

/**
 * A column range "start-end" (content_grid) as [start, end], or null if invalid.
 *
 * @return array{0: int, 1: int}|null
 */
function if_untitled_column_range(string $range): ?array
{
    if (!preg_match('/^([1-5])-([1-5])$/', $range, $match) || $match[1] > $match[2]) {
        return null;
    }
    return [(int) $match[1], (int) $match[2]];
}

/**
 * Responsive <img> for an attachment ID. $context sets `sizes` (how wide the image renders on screen).
 * $args: ['priority' => bool (LCP image: eager + fetchpriority high), 'alt' => string, 'class' => string]
 */
function if_untitled_image(int $id, string $context = 'full', array $args = []): string
{
    if (!$id) {
        return '';
    }

    // Keep in sync with $grid-breakpoints (SCSS) and BREAKPOINTS (MediaBreakpoints.js): md 768, lg 992.
    if (preg_match('/^span-([1-5])$/', $context, $match)) {
        $sizes = sprintf('(min-width: 768px) %dvw, 100vw', (int) $match[1] * 20);
    } else {
        $sizes = match ($context) {
            'card'  => '(min-width: 992px) 33vw, (min-width: 768px) 50vw, 100vw',
            'logo'  => '240px',
            default => '100vw',
        };
    }

    $attr = [
        'sizes' => $sizes,
        'class' => trim('image__img ' . ($args['class'] ?? '')),
    ];
    if (isset($args['alt'])) {
        $attr['alt'] = $args['alt'];
    }
    // Explicit both ways: core's heuristic would leave the first images of the main query eager (+ fetchpriority high)
    // even when they sit below a full-viewport intro.
    if (!empty($args['priority'])) {
        $attr['loading']       = 'eager';
        $attr['fetchpriority'] = 'high';
    } else {
        $attr['loading'] = 'lazy';
    }

    // 'large' is only the fallback src; srcset carries every proportional size up to 2560.
    return wp_get_attachment_image($id, 'large', false, $attr);
}

/**
 * Normalises a content_grid media block (image | video | youtube) for components/media.php.
 * Falls back to a plain image when the video / URL is missing or invalid.
 */
function if_untitled_media(array $block): array
{
    $type  = $block['media_type'] ?? 'image';
    $media = ['type' => 'image', 'image' => (int) ($block['image'] ?? 0)];

    if ($type === 'video') {
        $video_id = (int) ($block['video'] ?? 0);
        $src      = $video_id ? wp_get_attachment_url($video_id) : '';
        if ($src) {
            $media += ['src' => $src, 'mime' => (string) get_post_mime_type($video_id)];
            $media['type'] = 'video';
        }
    } elseif ($type === 'youtube') {
        $youtube_id = if_untitled_youtube_id((string) ($block['youtube_url'] ?? ''));
        if ($youtube_id) {
            $media['youtube_id'] = $youtube_id;
            $media['type']       = 'youtube';
        }
    }

    return $media;
}

/**
 * A linked post (post object / relationship ID) if it is published, else null.
 *
 * @return array{id: int, url: string, title: string, image: int, type: string}|null
 */
function if_untitled_resolve_item(mixed $id): ?array
{
    $post = $id ? get_post((int) $id) : null;
    if (!$post || get_post_status($post) !== 'publish') {
        return null;
    }
    return [
        'id'    => $post->ID,
        'url'   => (string) get_permalink($post),
        'title' => get_the_title($post),
        'image' => (int) get_post_thumbnail_id($post),
        'type'  => $post->post_type,
    ];
}

/**
 * Project metadata as display strings, always in this order: Location · Client · Year · Status.
 * Empty values stay '' (the 4-column variant keeps an empty cell).
 *
 * @return array{location: string, client: string, year: string, status: string}
 */
function if_untitled_project_metadata(?int $post_id = null): array
{
    $meta = function_exists('get_field') ? (get_field('project_metadata', $post_id) ?: []) : [];

    $city     = !empty($meta['location']) ? get_term((int) $meta['location'], 'project_location') : null;
    $country  = $city instanceof WP_Term && $city->parent ? get_term($city->parent, 'project_location') : null;
    $location = $city instanceof WP_Term
        ? implode(', ', array_filter([$city->name, $country instanceof WP_Term ? $country->name : '']))
        : '';

    $clients  = array_filter(array_map(
        fn($id): string => get_post_status((int) $id) === 'publish' ? get_the_title((int) $id) : '',
        (array) ($meta['client'] ?? [])
    ));
    $statuses = [
        'in_progress' => __('In progress', 'if-untitled'),
        'completed'   => __('Completed', 'if-untitled'),
        'planned'     => __('Planned', 'if-untitled'),
    ];

    return [
        'location' => $location,
        'client'   => implode(', ', $clients),
        'year'     => (string) ($meta['year'] ?? ''),
        'status'   => $statuses[$meta['status'] ?? ''] ?? '',
    ];
}

/**
 * Labels for the project metadata keys (same order as if_untitled_project_metadata()).
 *
 * @return array<string, string>
 */
function if_untitled_project_metadata_labels(): array
{
    return [
        'location' => __('Location', 'if-untitled'),
        'client'   => __('Client', 'if-untitled'),
        'year'     => __('Year', 'if-untitled'),
        'status'   => __('Status', 'if-untitled'),
    ];
}

/**
 * Previous / next published project in the Work "All" order (menu order, then newest), looping first ↔ last.
 */
function if_untitled_adjacent_project(string $direction = 'next', ?int $post_id = null): ?array
{
    static $ids = null;
    $ids ??= get_posts([
        'post_type'                => 'project',
        'post_status'              => 'publish',
        'posts_per_page'           => -1,
        'fields'                   => 'ids',
        'orderby'                  => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'scpo_ignore_custom_order' => true,
    ]);
    $index = array_search($post_id ?? get_the_ID(), $ids, true);
    if ($index === false || count($ids) < 2) {
        return null;
    }
    $step = $direction === 'prev' ? -1 : 1;
    return if_untitled_resolve_item($ids[($index + $step + count($ids)) % count($ids)]);
}

/**
 * Content grid rows, ready to render: invalid ranges and empty blocks are dropped.
 * Each block gets its grid placement (`start`, `span`) and image `context` (render-responsive-image).
 *
 * @return array<int, array{layout: string, link: ?array, image: ?array, text: ?array}>
 */
function if_untitled_content_grid(?int $post_id = null): array
{
    $rows = function_exists('get_field') ? (get_field('content_grid', $post_id) ?: []) : [];
    $grid = [];

    foreach ($rows as $row) {
        $layout = $row['acf_fc_layout'] ?? '';
        $image  = null;
        $text   = null;

        if (in_array($layout, ['image', 'image_text'], true)) {
            $range = if_untitled_column_range((string) ($row['image_columns'] ?? ''));
            if ($range && (int) ($row['image'] ?? 0)) {
                $span  = $range[1] - $range[0] + 1;
                $image = ['block' => $row, 'start' => $range[0], 'span' => $span, 'context' => "span-{$span}"];
            }
        }
        if (in_array($layout, ['text', 'image_text'], true)) {
            $range = if_untitled_column_range((string) ($row['text_columns'] ?? ''));
            if ($range && (trim((string) ($row['heading'] ?? '')) !== '' || trim((string) ($row['body'] ?? '')) !== '')) {
                $text = [
                    'heading' => (string) $row['heading'],
                    'body'    => (string) $row['body'],
                    'size'    => $row['body_size'] ?: 'small',
                    'box'     => $row['box'] ?: 'none',
                    'align_x' => $row['align_x'] ?: 'start',
                    'align_y' => $row['align_y'] ?: 'top',
                    'start'   => $range[0],
                    'span'    => $range[1] - $range[0] + 1,
                ];
            }
        }
        if ($image || $text) {
            $grid[] = [
                'layout' => $layout,
                'link'   => if_untitled_resolve_item($row['link'] ?? 0),
                'image'  => $image,
                'text'   => $text,
            ];
        }
    }

    return $grid;
}

/**
 * Archive intro text (HTML): the term description on a term view, else the Theme Options intro (`work_intro`,
 * `journal_intro`).
 */
function if_untitled_archive_intro(string $option): string
{
    if (is_category() || is_tag() || is_tax()) {
        $description = term_description();
        if (trim(wp_strip_all_tags($description)) !== '') {
            return $description;
        }
    }
    return function_exists('get_field') ? (string) get_field($option, 'option') : '';
}

/**
 * Previous / next page URLs of the main query ('' when there is none) for archive pagination.
 *
 * @return array{prev: string, next: string}
 */
function if_untitled_paged_links(): array
{
    global $wp_query;
    $paged = max(1, (int) get_query_var('paged'));
    return [
        'prev' => $paged > 1 ? (string) get_previous_posts_page_link() : '',
        'next' => $paged < (int) $wp_query->max_num_pages ? (string) get_next_posts_page_link() : '',
    ];
}

/**
 * Filter items for an archive: "All" + non-empty terms in their manual order.
 *
 * @return array<int, array{label: string, url: string, is_active: bool}>
 */
function if_untitled_archive_filter(string $taxonomy, string $all_url, string $all_label): array
{
    $terms        = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => true,
        'orderby'    => 'term_order',
        'order'      => 'ASC',
    ]);
    $is_term_view = $taxonomy === 'category' ? is_category() : is_tax($taxonomy);

    $items = [['label' => $all_label, 'url' => $all_url, 'is_active' => !$is_term_view]];

    foreach (is_wp_error($terms) ? [] : $terms as $term) {
        $items[] = [
            'label'     => $term->name,
            'url'       => get_term_link($term),
            'is_active' => $is_term_view && get_queried_object_id() === $term->term_id,
        ];
    }

    return $items;
}

/**
 * A team member, ready to render; null if unpublished.
 *
 * @return array{name: string, role: string, bio: string, image: int}|null
 */
function if_untitled_team_member(int $id): ?array
{
    if (get_post_status($id) !== 'publish') {
        return null;
    }
    return [
        'name'  => get_the_title($id),
        'role'  => function_exists('get_field') ? (string) get_field('team_role', $id) : '',
        'bio'   => function_exists('get_field') ? (string) get_field('team_bio', $id) : '',
        'image' => (int) get_post_thumbnail_id($id),
    ];
}

/**
 * The Theme Options client list in its order (published clients only).
 *
 * @return array<int, array{name: string, url: string, logo: int}>
 */
function if_untitled_clients(): array
{
    $ids = function_exists('get_field') ? (get_field('clients', 'option') ?: []) : [];
    $out = [];
    foreach ($ids as $id) {
        if (get_post_status((int) $id) === 'publish') {
            $out[] = [
                'name' => get_the_title((int) $id),
                'url'  => (string) get_field('client_url', (int) $id),
                'logo' => (int) get_post_thumbnail_id((int) $id),
            ];
        }
    }
    return $out;
}

/**
 * Office locations from Theme Options (footer, Contact).
 *
 * @return array<int, array{city: string, address: string[], phone: string, phone_href: string, email: string, lat: ?float, lng: ?float}>
 */
function if_untitled_locations(): array
{
    $rows = function_exists('get_field') ? (get_field('locations', 'option') ?: []) : [];
    return array_map(fn(array $row): array => [
        'city'       => (string) ($row['city'] ?? ''),
        'address'    => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($row['address'] ?? ''))))),
        'phone'      => (string) ($row['phone'] ?? ''),
        'phone_href' => preg_replace('/[^\d+]/', '', (string) ($row['phone'] ?? '')),
        'email'      => (string) ($row['email'] ?? ''),
        'lat'        => isset($row['map']['lat']) ? (float) $row['map']['lat'] : null,
        'lng'        => isset($row['map']['lng']) ? (float) $row['map']['lng'] : null,
    ], $rows);
}

/**
 * Unique anchor ids from titles (`sanitize_title()`; duplicates get -2, -3 …).
 *
 * @param string[] $titles
 * @return string[]
 */
function if_untitled_anchors(array $titles): array
{
    $used = [];
    return array_map(function (string $title) use (&$used): string {
        $base   = sanitize_title($title) ?: 'section';
        $anchor = $base;
        for ($i = 2; in_array($anchor, $used, true); $i++) {
            $anchor = "{$base}-{$i}";
        }
        $used[] = $anchor;
        return $anchor;
    }, $titles);
}

/**
 * The Studio page's services (Studio + footer), each with its anchor and URL. The page is found by its
 * template, never by ID / slug.
 *
 * @return array<int, array{title: string, subtitle: string, description: string, image: int, anchor: string, url: string}>
 */
function if_untitled_studio_services(): array
{
    static $services = null;
    if ($services !== null) {
        return $services;
    }
    $page     = get_posts([
        'post_type'        => 'page',
        'post_status'      => 'publish',
        'posts_per_page'   => 1,
        'fields'           => 'ids',
        'meta_key'         => '_wp_page_template',
        'meta_value'       => 'page-templates/studio.php',
        'suppress_filters' => true,
    ]);
    $rows     = $page && function_exists('get_field') ? (get_field('studio_services', $page[0]) ?: []) : [];
    $rows     = array_values(array_filter($rows, fn(array $row): bool => trim((string) ($row['title'] ?? '')) !== ''));
    $anchors  = if_untitled_anchors(array_column($rows, 'title'));
    $services = [];
    foreach ($rows as $i => $row) {
        $services[] = [
            'title'       => (string) $row['title'],
            'subtitle'    => (string) ($row['subtitle'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'image'       => (int) ($row['image'] ?? 0),
            'anchor'      => $anchors[$i],
            'url'         => get_permalink($page[0]) . '#' . $anchors[$i],
        ];
    }
    return $services;
}

/**
 * Studio feature rows: the item's image + URL, the row title (falls back to the item title) and description.
 * Rows whose item is missing / unpublished keep their text, without image.
 *
 * @return array<int, array{title: string, description: string, image: int, url: string}>
 */
function if_untitled_studio_features(?int $post_id = null): array
{
    $rows = function_exists('get_field') ? (get_field('studio_features', $post_id) ?: []) : [];
    return array_values(array_filter(array_map(function (array $row): ?array {
        $item  = if_untitled_resolve_item($row['item'] ?? 0);
        $title = trim((string) ($row['title'] ?? '')) ?: ($item['title'] ?? '');
        if ($title === '' && !$item) {
            return null;
        }
        return [
            'title'       => $title,
            'description' => (string) ($row['description'] ?? ''),
            'image'       => $item['image'] ?? 0,
            'url'         => $item['url'] ?? '',
        ];
    }, $rows)));
}

/**
 * Advisory service areas: title (anchored for the header submenu), subtitle, description and the showcase project
 * (image = its featured image).
 *
 * @return array<int, array{title: string, subtitle: string, description: string, anchor: string, project: ?array}>
 */
function if_untitled_advisory_features(?int $post_id = null): array
{
    $rows    = function_exists('get_field') ? (get_field('advisory_features', $post_id) ?: []) : [];
    $rows    = array_values(array_filter($rows, fn(array $row): bool => trim((string) ($row['title'] ?? '')) !== ''));
    $anchors = if_untitled_anchors(array_column($rows, 'title'));
    return array_map(fn(array $row, string $anchor): array => [
        'title'       => (string) $row['title'],
        'subtitle'    => (string) ($row['subtitle'] ?? ''),
        'description' => (string) ($row['description'] ?? ''),
        'anchor'      => $anchor,
        'project'     => if_untitled_resolve_item($row['item'] ?? 0),
    ], $rows, $anchors);
}

/**
 * Enquiry contacts from Theme Options (footer), each with its link: mailto:, tel: or WhatsApp.
 *
 * @return array<int, array{label: string, href: string}>
 */
function if_untitled_enquiries(): array
{
    $rows = function_exists('get_field') ? (get_field('enquiries', 'option') ?: []) : [];
    $out  = [];
    foreach ($rows as $row) {
        $value = trim((string) ($row['value'] ?? ''));
        $href  = match ($row['type'] ?? '') {
            'email'    => is_email($value) ? 'mailto:' . $value : '',
            'phone'    => 'tel:' . preg_replace('/[^\d+]/', '', $value),
            'whatsapp' => 'https://wa.me/' . preg_replace('/\D/', '', $value),
            default    => '',
        };
        if ($value !== '' && $href !== '') {
            $out[] = ['label' => (string) ($row['label'] ?? '') ?: $value, 'href' => $href];
        }
    }
    return $out;
}

/**
 * External label + URL lists from Theme Options (`social`, `portals`).
 *
 * @return array<int, array{label: string, url: string}>
 */
function if_untitled_external_links(string $field): array
{
    $rows = function_exists('get_field') ? (get_field($field, 'option') ?: []) : [];
    $rows = array_map(fn(array $row): array => [
        'label' => (string) ($row['label'] ?? ''),
        'url'   => (string) ($row['url'] ?? ''),
    ], $rows);
    return array_values(array_filter($rows, fn(array $row): bool => $row['label'] !== '' && $row['url'] !== ''));
}
