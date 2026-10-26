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
        'rewrite'      => ['slug' => 'projects', 'with_front' => false],
        'menu_icon'    => 'dashicons-portfolio',
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'],
        'show_in_rest' => true,
    ]);

    // Private type: editable in wp-admin, no front-end URLs (data is queried by pages).
    register_post_type('client', [
        'labels'              => [
            'name'          => __('Clients', 'if-untitled'),
            'singular_name' => __('Client', 'if-untitled'),
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_rest'        => true,
        'exclude_from_search' => true,
        'rewrite'             => false,
        'menu_icon'           => 'dashicons-groups',
        'supports'            => ['title', 'thumbnail', 'page-attributes'],
    ]);

    register_taxonomy('project_category', ['project'], [
        'labels'            => ['name' => __('Project categories', 'if-untitled')],
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'projects/category', 'with_front' => false],
    ]);
});
```

Naming & URL rules:

- Post type key: singular, lowercase, ≤ 20 chars, underscores not dashes (`team_member`). Taxonomy key: same
  rules, ≤ 32 chars (`project_category`). URL slugs: plural kebab-case.
- Term archives nest under the CPT archive (`projects/category` → `/projects/category/{slug}/`).
- Always `'with_front' => false` on CPTs and taxonomies: a custom permalink structure such as `/journal/%postname%/`
  would otherwise prefix every CPT URL (`/journal/projects/…`).
- Use native `supports` (title, thumbnail, excerpt, `page-attributes` for menu order) before adding ACF fields.
- Private types (`public => false`, `show_ui => true`) get no archive/single templates.
- After adding/changing rewrites tell the user to re-save **Settings → Permalinks** (or `wp rewrite flush`).

### Built-in types (optional, same file)

```php
// Relabel Posts (e.g. "Journal") — menu + labels.
add_filter('post_type_labels_post', function (object $labels): object {
    $labels->name = $labels->menu_name = __('Journal', 'if-untitled');
    return $labels;
});

// Classic editor for types that are built from ACF fields.
add_filter('use_block_editor_for_post_type', fn(bool $use, string $type): bool
    => in_array($type, ['post', 'project'], true) ? false : $use, 10, 2);
```

Relabel categories with `taxonomy_labels_category`. Disable comments with `remove_post_type_support($type, 'comments')`
plus `comments_open` / `pings_open` filters returning `false`.

## 2. Query tweaks (optional)

Use `pre_get_posts` in `inc/post-types.php` instead of custom `WP_Query` in templates (cover the term archive too):

```php
add_action('pre_get_posts', function (WP_Query $q): void {
    if (!is_admin() && $q->is_main_query()
        && ($q->is_post_type_archive('project') || $q->is_tax('project_category'))) {
        $q->set('posts_per_page', -1);
        $q->set('orderby', 'menu_order');
        $q->set('order', 'ASC');
    }
});
```

## 3. Templates

Follow the `create-page-template` skill for `archive-project.php` (route id `archive-project`),
`taxonomy-project_category.php` (same route) and `single-project.php` (route id `project`).
For an "All + categories" filter bar follow `create-archive-filter`.

## 4. ACF fields

Follow the `create-acf-field-group` skill (location _Post Type is {cpt}_, naming, return formats, `acf-json/`).
Custom term order: a `sort_order` number field on the taxonomy.

## 5. Verify

`npm run check` in the theme folder.
