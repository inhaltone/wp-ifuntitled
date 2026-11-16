<?php
/**
 * SEO without a plugin: meta description, canonical, Open Graph and Twitter card on every view, filled automatically
 * from native content (excerpt → content, archive intro, featured image → Site Icon). Title tag, robots and the XML
 * sitemap are core's.
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

/**
 * Meta description of the current view (plain text, ~30 words).
 */
function if_untitled_seo_description(): string
{
    if (is_singular()) {
        $post = get_queried_object();
        $text = match (true) {
            post_password_required($post) => '',
            has_excerpt($post)            => $post->post_excerpt,
            $post->post_type === 'project' && function_exists('get_field') => (string) get_field('project_text', $post->ID),
            default                       => $post->post_content,
        };
    } elseif (is_home() || is_category() || is_tag()) {
        $text = if_untitled_archive_intro('journal_intro');
    } elseif (is_post_type_archive('project') || is_tax('project_category')) {
        $text = if_untitled_archive_intro('work_intro');
    }
    $text = wp_trim_words(strip_shortcodes($text ?? ''), 30);
    return $text !== '' ? $text : get_bloginfo('description');
}

// The Work archive is called "Work" (header, <h1>), not "Projects".
add_filter('post_type_archive_title', fn(string $title, string $post_type): string => $post_type === 'project' ? __('Work', 'if-untitled') : $title, 10, 2);

// Canonical is printed below for every view (core only does singular); the ?p= shortlink is not needed.
remove_action('wp_head', 'rel_canonical');
remove_action('wp_head', 'wp_shortlink_wp_head');

add_filter('wp_robots', function (array $robots): array {
    if (is_404()) {
        $robots['noindex'] = true;
    }
    return $robots;
});

add_action('wp_head', function (): void {
    $url = match (true) {
        is_404(), is_search() => '',
        is_singular()         => (string) wp_get_canonical_url(),
        default               => strtok(get_pagenum_link(max(1, (int) get_query_var('paged')), false), '?'),
    };
    $post_id     = is_singular() ? get_queried_object_id() : (is_home() ? (int) get_option('page_for_posts') : 0);
    $image_id    = ($post_id ? (int) get_post_thumbnail_id($post_id) : 0) ?: (int) get_option('site_icon');
    $image       = $image_id ? wp_get_attachment_image_src($image_id, 'large') : false;
    $title       = wp_get_document_title();
    $description = if_untitled_seo_description();
    $is_article  = is_singular('post');

    $tags = [
        'description'         => $description,
        'og:locale'           => get_locale(),
        'og:type'             => $is_article ? 'article' : 'website',
        'og:site_name'        => get_bloginfo('name'),
        'og:title'            => $title,
        'og:description'      => $description,
        'og:url'              => $url,
        'og:image'            => $image[0] ?? '',
        'og:image:width'      => $image[1] ?? '',
        'og:image:height'     => $image[2] ?? '',
        'og:image:alt'        => $image ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : '',
        'article:published_time' => $is_article ? get_the_date('c') : '',
        'article:modified_time'  => $is_article ? get_the_modified_date('c') : '',
        'twitter:card'        => $image && $image_id !== (int) get_option('site_icon') ? 'summary_large_image' : 'summary',
        'twitter:title'       => $title,
        'twitter:description' => $description,
        'twitter:image'       => $image[0] ?? '',
    ];

    if ($url) {
        echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
    }
    foreach ($tags as $key => $value) {
        if ((string) $value !== '') {
            // Open Graph uses `property`, the rest `name`.
            $attr = str_starts_with($key, 'og:') || str_starts_with($key, 'article:') ? 'property' : 'name';
            printf('<meta %s="%s" content="%s">' . "\n", $attr, esc_attr($key), esc_attr((string) $value));
        }
    }
}, 2);

// Core XML sitemap (/wp-sitemap.xml): no users (author URLs redirect) and no tags (noindex, inc/urls.php).
add_filter('wp_sitemaps_add_provider', fn($provider, string $name) => $name === 'users' ? false : $provider, 10, 2);
add_filter('wp_sitemaps_taxonomies', function (array $taxonomies): array {
    unset($taxonomies['post_tag']);
    return $taxonomies;
});
