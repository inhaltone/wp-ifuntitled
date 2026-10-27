<?php
/**
 * URL rules: unused archives, reserved slugs, indexing (implementation plan 1.7).
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

// Author and date archives are not used: send them to the Journal (also hides user names).
add_action('template_redirect', function (): void {
    if (!is_author() && !is_date()) {
        return;
    }
    $journal = (int) get_option('page_for_posts');
    wp_safe_redirect($journal ? get_permalink($journal) : home_url('/'), 301);
    exit;
});

// Tag archives stay out of the index until tag filtering ships.
add_filter('wp_robots', function (array $robots): array {
    if (is_tag()) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
});

/**
 * Slugs that would clash with archive URLs, per post type.
 *
 * @return string[]
 */
function if_untitled_reserved_slugs(string $post_type): array
{
    return match ($post_type) {
        'post', 'project' => ['category', 'tag', 'page'],
        'page'            => ['work'],
        default           => [],
    };
}

// Slugs are words, never IDs: reserved and numeric-only slugs get a suffix.
add_filter('wp_unique_post_slug', function (string $slug, int $post_id, string $status, string $post_type, int $parent): string {
    $top_level = $post_type !== 'page' || $parent === 0;
    $reserved  = $top_level && in_array($slug, if_untitled_reserved_slugs($post_type), true);
    if (!$reserved && !ctype_digit($slug)) {
        return $slug;
    }
    $prefix = $post_type === 'post' ? 'entry' : $post_type;
    return wp_unique_post_slug("{$prefix}-{$slug}", $post_id, $status, $post_type, $parent);
}, 10, 5);
