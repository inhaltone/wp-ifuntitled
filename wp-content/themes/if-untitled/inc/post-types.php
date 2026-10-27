<?php
/**
 * Custom post types, taxonomies and tweaks to the built-in types.
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

add_action('init', function (): void {
    // Taxonomy first: its rewrite rules must win over the post type's.
    register_taxonomy('project_category', ['project'], [
        'labels'            => [
            'name'          => __('Project categories', 'if-untitled'),
            'singular_name' => __('Project category', 'if-untitled'),
            'menu_name'     => __('Categories', 'if-untitled'),
        ],
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        // Single category, picked through the ACF radio field.
        'meta_box_cb'       => false,
        'rewrite'           => ['slug' => 'work/category', 'with_front' => false],
    ]);

    // Private vocabulary, country › city: no URLs, edited only through its ACF taxonomy field.
    register_taxonomy('project_location', ['project'], [
        'labels'            => [
            'name'          => __('Locations', 'if-untitled'),
            'singular_name' => __('Location', 'if-untitled'),
            'parent_item'   => __('Country', 'if-untitled'),
        ],
        'hierarchical'      => true,
        'public'            => false,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'meta_box_cb'       => false,
        'rewrite'           => false,
    ]);

    register_post_type('project', [
        'labels'       => [
            'name'          => __('Projects', 'if-untitled'),
            'singular_name' => __('Project', 'if-untitled'),
            'add_new_item'  => __('Add new project', 'if-untitled'),
            'edit_item'     => __('Edit project', 'if-untitled'),
            'all_items'     => __('All projects', 'if-untitled'),
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'work', 'with_front' => false],
        'menu_icon'    => 'dashicons-portfolio',
        'menu_position' => 5,
        'supports'     => ['title', 'thumbnail', 'excerpt', 'page-attributes'],
        'show_in_rest' => true,
    ]);

    // Private types: editable in wp-admin, no front-end URLs (data is queried by pages).
    register_post_type('client', [
        'labels'              => [
            'name'           => __('Clients', 'if-untitled'),
            'singular_name'  => __('Client', 'if-untitled'),
            'add_new_item'   => __('Add new client', 'if-untitled'),
            'edit_item'      => __('Edit client', 'if-untitled'),
            'featured_image' => __('Logo', 'if-untitled'),
            'set_featured_image'    => __('Set logo', 'if-untitled'),
            'remove_featured_image' => __('Remove logo', 'if-untitled'),
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_rest'        => true,
        'exclude_from_search' => true,
        'rewrite'             => false,
        'menu_icon'           => 'dashicons-groups',
        'supports'            => ['title', 'thumbnail'],
    ]);

    register_post_type('team_member', [
        'labels'              => [
            'name'                  => __('Team', 'if-untitled'),
            'singular_name'         => __('Team member', 'if-untitled'),
            'add_new_item'          => __('Add new team member', 'if-untitled'),
            'edit_item'             => __('Edit team member', 'if-untitled'),
            'featured_image'        => __('Portrait', 'if-untitled'),
            'set_featured_image'    => __('Set portrait', 'if-untitled'),
            'remove_featured_image' => __('Remove portrait', 'if-untitled'),
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_rest'        => true,
        'exclude_from_search' => true,
        'rewrite'             => false,
        'menu_icon'           => 'dashicons-id',
        'supports'            => ['title', 'thumbnail'],
    ]);

    // Journal entries get a manual order (menu_order).
    add_post_type_support('post', 'page-attributes');

    // No comments anywhere.
    foreach (get_post_types() as $type) {
        remove_post_type_support($type, 'comments');
        remove_post_type_support($type, 'trackbacks');
    }
});

add_filter('enter_title_here', fn(string $text, WP_Post $post): string
    => $post->post_type === 'team_member' ? __('Full name', 'if-untitled') : $text, 10, 2);

// Posts → Journal, Categories → Entry types.
add_filter('post_type_labels_post', function (object $labels): object {
    $labels->name          = __('Journal', 'if-untitled');
    $labels->menu_name     = __('Journal', 'if-untitled');
    $labels->all_items     = __('All entries', 'if-untitled');
    $labels->singular_name = __('Journal entry', 'if-untitled');
    $labels->add_new_item  = __('Add new entry', 'if-untitled');
    $labels->edit_item     = __('Edit entry', 'if-untitled');
    $labels->featured_image        = __('Lead image', 'if-untitled');
    $labels->set_featured_image    = __('Set lead image', 'if-untitled');
    $labels->remove_featured_image = __('Remove lead image', 'if-untitled');
    return $labels;
});

add_filter('taxonomy_labels_category', function (object $labels): object {
    $labels->name          = __('Entry types', 'if-untitled');
    $labels->menu_name     = __('Entry types', 'if-untitled');
    $labels->singular_name = __('Entry type', 'if-untitled');
    $labels->all_items     = __('All entry types', 'if-untitled');
    $labels->add_new_item  = __('Add new entry type', 'if-untitled');
    $labels->edit_item     = __('Edit entry type', 'if-untitled');
    return $labels;
});

// Classic editor: content is a plain intro or built from ACF fields.
add_filter('use_block_editor_for_post_type', fn(bool $use, string $type): bool
    => in_array($type, ['post', 'project', 'team_member', 'page'], true) ? false : $use, 10, 2);

// Comments off: closed everywhere, hidden from the admin.
add_filter('comments_open', '__return_false', 20);
add_filter('pings_open', '__return_false', 20);
add_filter('comments_array', '__return_empty_array', 10);
add_action('admin_menu', function (): void {
    remove_menu_page('edit-comments.php');
});
add_action('admin_bar_menu', function (WP_Admin_Bar $bar): void {
    $bar->remove_node('comments');
}, 100);

// The theme owns the front-end order; the ordering plugin only writes menu_order / term_order in wp-admin.
add_action('pre_get_posts', function (WP_Query $q): void {
    if (is_admin() || !$q->is_main_query()) {
        return;
    }
    $is_work    = $q->is_post_type_archive('project') || $q->is_tax('project_category');
    $is_journal = $q->is_home() || $q->is_category() || $q->is_tag();
    if (!$is_work && !$is_journal) {
        return;
    }
    $q->set('posts_per_page', $is_work ? -1 : 12);
    // Unordered items have menu_order 0 → first, newest first.
    $q->set('orderby', ['menu_order' => 'ASC', 'date' => 'DESC']);
}, 20);
