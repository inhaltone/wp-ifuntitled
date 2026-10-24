---
name: register-custom-post-type
description: Register a custom post type and/or taxonomy in code for the if-untitled theme, with archive/single templates and ACF fields. Use when asked for new content types such as projects, works, events, team members or news.
---

# Register a custom post type

Register in code (version-controlled), not with a CPT UI plugin.

## 1. `inc/post-types.php`
Create it if missing and require it from `functions.php` (`require_once IF_UNTITLED_DIR . '/inc/post-types.php';`).

```php
<?php
/**
 * Custom post types and taxonomies.
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

add_action('init', function (): void {
    register_post_type('project', [
        'labels'       => [
            'name'          => __('Projects', 'if-untitled'),
            'singular_name' => __('Project', 'if-untitled'),
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'projects'],
        'menu_icon'    => 'dashicons-portfolio',
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('project_category', ['project'], [
        'labels'            => ['name' => __('Project categories', 'if-untitled')],
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'project-category'],
    ]);
});
```
- Post type key: singular, lowercase, ≤ 20 chars, no dashes. Slugs: plural kebab-case.
- After adding/changing rewrites tell the user to re-save **Settings → Permalinks** (or `wp rewrite flush`).

## 2. Query tweaks (optional)
Use `pre_get_posts` in `inc/post-types.php` instead of custom `WP_Query` in templates:
```php
add_action('pre_get_posts', function (WP_Query $q): void {
    if (!is_admin() && $q->is_main_query() && $q->is_post_type_archive('project')) {
        $q->set('posts_per_page', -1);
        $q->set('orderby', 'menu_order');
        $q->set('order', 'ASC');
    }
});
```

## 3. Templates
Follow the `create-page-template` skill for `archive-project.php` (route id `archive-project`) and
`single-project.php` (route id `project`).

## 4. ACF fields
ACF Pro is installed at `wp-content/plugins/advanced-custom-fields-pro` (activate it in wp-admin). Create field groups in
the admin with *Location → Post Type is {cpt}*; they are saved to the theme's `acf-json/` automatically — commit the JSON.
Never edit the plugin's files.

## 5. Verify
`npm run check` in the theme folder.
