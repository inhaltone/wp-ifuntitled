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
 * $args: ['priority' => bool (LCP image: eager + fetchpriority high), 'alt' => string, 'class' => string,
 *         'ratio' => '2x3' etc. (CSS crop; scales `sizes` for wider sources)]
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

    // A fixed ratio crops with object-fit: cover, so an image wider than the box renders wider than its column.
    // Scale `sizes` by that factor, or the browser picks a candidate that is too small (blurry).
    $cropped = false;
    if (preg_match('/^(\d+)x(\d+)$/', $args['ratio'] ?? '', $match)) {
        $meta   = wp_get_attachment_metadata($id);
        $factor = !empty($meta['width']) && !empty($meta['height'])
            ? ($meta['width'] / $meta['height']) / ($match[1] / $match[2])
            : 1;
        $cropped = $factor > 1.05;
        if ($cropped) {
            $sizes = preg_replace_callback(
                '/(\d+)(vw|px)\b(?!\))/',
                fn(array $size): string => (int) round($size[1] * $factor) . $size[2],
                $sizes
            );
        }
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
    $html = wp_get_attachment_image($id, 'large', false, $attr);

    // Core prepends `sizes="auto"` to lazy images, which uses the box width and ignores the scaled hint.
    return $cropped ? str_replace('sizes="auto, ', 'sizes="', $html) : $html;
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
 * Front page hero video (ACF hero_media_type = video), or null for the image hero.
 *
 * @return array{src: string, mime: string}|null
 */
function if_untitled_hero_video(int $post_id = 0): ?array
{
    if (!function_exists('get_field') || get_field('hero_media_type', $post_id ?: false) !== 'video') {
        return null;
    }
    $video_id = (int) get_field('hero_video', $post_id ?: false);
    $src      = $video_id ? wp_get_attachment_url($video_id) : '';

    return $src ? ['src' => $src, 'mime' => (string) get_post_mime_type($video_id)] : null;
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
        fn($id): string => get_post_status($id) === 'publish' ? get_the_title($id) : '',
        // Empty field → '' → 0, which core resolves to the current post: drop it.
        array_filter(array_map('intval', (array) ($meta['client'] ?? [])))
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
 * Project specs (project page hero only, below the metadata): display strings, '' = empty.
 *
 * @return array{date: string, size: string, typology: string, collaborators: string}
 */
function if_untitled_project_specs(?int $post_id = null): array
{
    $specs = function_exists('get_field') ? (get_field('project_specs', $post_id) ?: []) : [];
    $size  = (string) ($specs['size'] ?? '');

    return [
        'date'          => trim((string) ($specs['date'] ?? '')),
        'size'          => $size !== '' ? number_format_i18n((float) $size) . ' m²' : '',
        'typology'      => trim((string) ($specs['typology'] ?? '')),
        'collaborators' => trim((string) ($specs['collaborators'] ?? '')),
    ];
}

/**
 * Labels for the project metadata and specs keys (same order as if_untitled_project_metadata() / _specs()).
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
        // Specs.
        'date'          => __('Date', 'if-untitled'),
        'size'          => __('Size', 'if-untitled'),
        'typology'      => __('Typology', 'if-untitled'),
        'collaborators' => __('Collaborators', 'if-untitled'),
    ];
}

/**
 * Project category names, comma-separated ('' when none) — the Work card line under the title.
 */
function if_untitled_project_categories(?int $post_id = null): string
{
    $terms = get_the_terms($post_id ?? get_the_ID(), 'project_category');

    return is_array($terms) ? implode(', ', wp_list_pluck($terms, 'name')) : '';
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
 * The blocks of a content_grid row by layout: [kind ('image' | 'text'), sub-field values, link field name].
 * Single layouts and image_text read flat (seamless clones, one row link); the pairs read their group clones
 * (`image_1`/`image_2`, `text_1`/`text_2`), each with its own link (`link_1`/`link_2`).
 *
 * @return array<int, array{0: string, 1: array, 2: string}>
 */
function if_untitled_content_grid_parts(string $layout, array $row): array
{
    return match ($layout) {
        'image'       => [['image', $row, 'link']],
        'text'        => [['text', $row, 'link']],
        'image_text'  => [['image', $row, 'link'], ['text', $row, 'link']],
        'image_image' => [['image', (array) ($row['image_1'] ?? []), 'link_1'], ['image', (array) ($row['image_2'] ?? []), 'link_2']],
        'text_text'   => [['text', (array) ($row['text_1'] ?? []), 'link_1'], ['text', (array) ($row['text_2'] ?? []), 'link_2']],
        default       => [],
    };
}

/**
 * Content grid rows, ready to render: invalid ranges and empty blocks are dropped, blocks in column order.
 * Every block has its `kind`, grid placement (`start`, `span`) and `link` (resolved item or null). Images also get
 * `block` (media sub-fields), `context` (render-responsive-image), `title` (hover title: the linked item's title,
 * '' without a link), `label` (accessible video name) and `link_hidden` (a text block of the row repeats the link).
 * Row `heading` = its first text heading (section anchors).
 *
 * @return array<int, array{layout: string, heading: string, blocks: array<int, array>}>
 */
function if_untitled_content_grid(?int $post_id = null): array
{
    $rows = function_exists('get_field') ? (get_field('content_grid', $post_id) ?: []) : [];
    $grid = [];

    foreach ($rows as $row) {
        $layout = $row['acf_fc_layout'] ?? '';
        $blocks = [];

        foreach (if_untitled_content_grid_parts($layout, $row) as [$kind, $fields, $link_field]) {
            $range = if_untitled_column_range((string) ($fields["{$kind}_columns"] ?? ''));
            if (!$range) {
                continue;
            }
            $place = ['kind' => $kind, 'start' => $range[0], 'span' => $range[1] - $range[0] + 1];
            $link  = if_untitled_resolve_item($row[$link_field] ?? 0);

            if ($kind === 'image' && (int) ($fields['image'] ?? 0)) {
                $blocks[] = $place + [
                    'block'   => $fields,
                    'context' => "span-{$place['span']}",
                    'link'    => $link,
                    'title'   => $link['title'] ?? '',
                ];
            } elseif ($kind === 'text' && (trim((string) ($fields['heading'] ?? '')) !== '' || trim((string) ($fields['body'] ?? '')) !== '')) {
                $blocks[] = $place + [
                    'heading' => trim((string) $fields['heading']),
                    'body'    => (string) $fields['body'],
                    'size'    => ($fields['body_size'] ?? '') ?: 'small',
                    'box'     => ($fields['box'] ?? '') ?: 'none',
                    'align_x' => ($fields['align_x'] ?? '') ?: 'start',
                    'align_y' => ($fields['align_y'] ?? '') ?: 'top',
                    'link'    => $link,
                ];
            }
        }
        if (!$blocks) {
            continue;
        }

        $texts   = array_values(array_filter($blocks, fn(array $block): bool => $block['kind'] === 'text'));
        $heading = $texts[0]['heading'] ?? '';
        foreach ($blocks as &$block) {
            if ($block['kind'] === 'image') {
                // image_text: the text carries the same link (heading or "more"), so the image link is a duplicate.
                $block['link_hidden'] = $block['link'] && $layout === 'image_text' && $texts;
                $block['label']       = ($layout === 'image_text' ? $heading : '') ?: get_the_title();
            }
        }
        unset($block);
        usort($blocks, fn(array $a, array $b): int => $a['start'] <=> $b['start']);

        $grid[] = ['layout' => $layout, 'heading' => $heading, 'blocks' => $blocks];
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
 * @return array<int, array{name: string, url: string, logo: int, logo_ratio: float}>
 */
function if_untitled_clients(): array
{
    $ids = function_exists('get_field') ? (get_field('clients', 'option') ?: []) : [];
    $out = [];
    foreach ($ids as $id) {
        if (get_post_status((int) $id) === 'publish') {
            $logo  = (int) get_post_thumbnail_id((int) $id);
            $meta  = $logo ? wp_get_attachment_metadata($logo) : [];
            $out[] = [
                'name'       => get_the_title((int) $id),
                'url'        => (string) get_field('client_url', (int) $id),
                'logo'       => $logo,
                'logo_ratio' => !empty($meta['width']) && !empty($meta['height']) ? $meta['width'] / $meta['height'] : 1.0,
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
 * Google Maps settings: Theme Options (`google_maps_key`, `google_maps_id`); the IF_UNTITLED_GOOGLE_MAPS_KEY / _ID
 * constants (wp-config.php) override them per environment. Empty key = no map.
 *
 * @return array{key: string, map_id: string}
 */
function if_untitled_google_maps(): array
{
    $option = fn(string $name): string => function_exists('get_field') ? trim((string) get_field($name, 'option')) : '';

    return [
        'key'    => defined('IF_UNTITLED_GOOGLE_MAPS_KEY') ? (string) IF_UNTITLED_GOOGLE_MAPS_KEY : $option('google_maps_key'),
        'map_id' => defined('IF_UNTITLED_GOOGLE_MAPS_ID') ? (string) IF_UNTITLED_GOOGLE_MAPS_ID : $option('google_maps_id'),
    ];
}

/**
 * Unique anchor ids from titles (`sanitize_title()`; duplicates get -2, -3 …). `$used`: ids already taken on the page.
 *
 * @param string[] $titles
 * @param string[] $used
 * @return string[]
 */
function if_untitled_anchors(array $titles, array $used = []): array
{
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
 * The published page using a page template (Studio, Advisory), found by its template — never by ID / slug.
 */
function if_untitled_template_page(string $template): int
{
    static $pages = [];
    if (!isset($pages[$template])) {
        $ids = get_posts([
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'posts_per_page'   => 1,
            'fields'           => 'ids',
            'meta_key'         => '_wp_page_template',
            'meta_value'       => $template,
            'suppress_filters' => true,
        ]);
        $pages[$template] = (int) ($ids[0] ?? 0);
    }
    return $pages[$template];
}

/** Section anchors on Studio / Advisory (service anchors never reuse them). */
const IF_UNTITLED_SECTION_ANCHORS = ['services', 'team', 'clients', 'featured'];

/**
 * Services of a Studio / Advisory page: its content grid rows, each row with a heading anchored (`anchor`, '' without
 * heading) for the header submenu and the footer.
 *
 * @return array<int, array{layout: string, heading: string, blocks: array<int, array>, anchor: string}>
 */
function if_untitled_services_grid(int $post_id): array
{
    static $grids = [];
    if (isset($grids[$post_id])) {
        return $grids[$post_id];
    }
    $rows     = if_untitled_content_grid($post_id);
    $headings = array_filter(array_map(fn(array $row): string => $row['heading'], $rows));
    $anchors  = array_combine(array_keys($headings), if_untitled_anchors(array_values($headings), IF_UNTITLED_SECTION_ANCHORS));
    foreach ($rows as $i => &$row) {
        $row['anchor'] = $anchors[$i] ?? '';
    }
    unset($row);
    return $grids[$post_id] = $rows;
}

/**
 * The Studio page's services for the footer: each content grid row heading with its URL (page + anchor).
 *
 * @return array<int, array{title: string, url: string}>
 */
function if_untitled_studio_services(): array
{
    $page = if_untitled_template_page('page-templates/studio.php');
    if (!$page) {
        return [];
    }
    $rows = array_filter(if_untitled_services_grid($page), fn(array $row): bool => $row['anchor'] !== '');
    return array_values(array_map(fn(array $row): array => [
        'title' => $row['heading'],
        'url'   => get_permalink($page) . '#' . $row['anchor'],
    ], $rows));
}

/**
 * Featured items of a Studio / Advisory page (`studio_featured`, `advisory_featured` relationship, picked order):
 * each published item's title, excerpt, featured image and URL.
 *
 * @return array<int, array{title: string, description: string, image: int, url: string}>
 */
function if_untitled_featured(string $field, ?int $post_id = null): array
{
    $ids   = function_exists('get_field') ? (get_field($field, $post_id) ?: []) : [];
    $items = array_filter(array_map('if_untitled_resolve_item', (array) $ids));
    return array_values(array_map(fn(array $item): array => [
        'title'       => $item['title'],
        'description' => has_excerpt($item['id']) ? get_the_excerpt($item['id']) : '',
        'image'       => $item['image'],
        'url'         => $item['url'],
    ], $items));
}

/**
 * The sections of a Studio / Advisory page in render order, only those with content: label, anchor and the component that renders the content (`full`: content spans all columns below the label).
 * Studio: Services · Team · Clients · Featured. Advisory: Services · Clients · Featured.
 *
 * @return array<int, array{title: string, id: string, full: bool, content: array{name: string, args: array}}>
 */
function if_untitled_page_sections(int $post_id): array
{
    static $cache = [];
    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }
    $context = match (get_page_template_slug($post_id)) {
        'page-templates/studio.php'   => 'studio',
        'page-templates/advisory.php' => 'advisory',
        default                       => '',
    };
    if (!$context) {
        return $cache[$post_id] = [];
    }
    $acf      = function_exists('get_field');
    $services = if_untitled_services_grid($post_id);
    $team     = $context === 'studio' && $acf ? (get_field('studio_team', $post_id) ?: []) : [];
    $hidden   = $acf && get_field("{$context}_hide_featured", $post_id);
    $featured = $hidden ? [] : if_untitled_featured("{$context}_featured", $post_id);

    $sections = [
        'services' => [__('Services', 'if-untitled'), true, $services ? ['name' => 'content-grid', 'args' => ['rows' => $services]] : null],
        'team'     => [__('Team', 'if-untitled'), false, $team ? ['name' => 'team', 'args' => ['ids' => $team]] : null],
        'clients'  => [__('Clients', 'if-untitled'), false, if_untitled_clients() ? ['name' => 'clients', 'args' => []] : null],
        'featured' => [__('Featured', 'if-untitled'), false, $featured ? ['name' => 'featured', 'args' => ['items' => $featured]] : null],
    ];
    $out = [];
    foreach ($sections as $id => [$title, $full, $content]) {
        if ($content) {
            $out[] = ['title' => $title, 'id' => $id, 'full' => $full, 'content' => $content];
        }
    }
    return $cache[$post_id] = $out;
}

/**
 * Weight of bold text in the Studio / Advisory intro (Theme Options): 'bold' (default) or 'medium'.
 */
function if_untitled_intro_strong_weight(): string
{
    $weight = function_exists('get_field') ? get_field('intro_strong_weight', 'option') : '';
    return $weight === 'medium' ? 'medium' : 'bold';
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
