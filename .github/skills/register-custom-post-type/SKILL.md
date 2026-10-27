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
    // Taxonomy first: its rewrite rules must win over the post type's.
    register_taxonomy('project_category', ['project'], [
        'labels'            => ['name' => __('Project categories', 'if-untitled')],
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'work/category', 'with_front' => false],
    ]);

    // Private vocabulary (one spelling per value), country › city: no URLs, edited only through its ACF taxonomy field.
    register_taxonomy('project_location', ['project'], [
        'labels'            => ['name' => __('Locations', 'if-untitled')],
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
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'work', 'with_front' => false],
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
});
```

Naming & URL rules:

- Post type key: singular, lowercase, ≤ 20 chars, underscores not dashes (`team_member`). Taxonomy key: same
  rules, ≤ 32 chars (`project_category`). URL slugs: kebab-case, matching the header label (`work`, not `projects`).
- Item slugs are words from the title (`sanitize_title()`), **never IDs or numbers**.
- Term archives nest under the CPT archive (`work/category` → `/work/category/{slug}/`).
  Register the taxonomy **before** the post type so its rewrite rules win over the CPT's.
- Always `'with_front' => false` on CPTs and taxonomies: a custom permalink structure such as `/journal/%postname%/`
  would otherwise prefix every CPT URL (`/journal/work/…`).
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
    => in_array($type, ['post', 'project', 'team_member', 'page'], true) ? false : $use, 10, 2);
```

Relabel categories with `taxonomy_labels_category`. Disable comments with `remove_post_type_support($type, 'comments')`
plus `comments_open` / `pings_open` filters returning `false`.

## 2. Query tweaks (optional)

Use `pre_get_posts` in `inc/post-types.php` instead of custom `WP_Query` in templates (cover the term archives too).
The theme owns the front-end order; the ordering plugin (Simple Custom Post Order) writes `menu_order` / `term_order`
in wp-admin. It has no "auto sort" switch — it only sorts queries without an explicit `orderby`, so the theme hook
runs at priority 20 and always sets `orderby`; secondary queries (`get_posts()`) pass an explicit `orderby` array or
`'scpo_ignore_custom_order' => true`. Terms use its `term_order`:

```php
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
```

## 3. Templates

Follow the `create-page-template` skill for `archive-project.php` (route id `archive-project`),
`taxonomy-project_category.php` (same route) and `single-project.php` (route id `project`).
For an "All + categories" filter bar follow `create-archive-filter`.

## 4. ACF fields

Follow the `create-acf-field-group` skill (location _Post Type is {cpt}_, naming, return formats, `acf-json/`).
Manual order: posts and terms are drag-ordered with the ordering plugin (`menu_order` / `term_order`); lists picked
on a page (team, clients) are ordered by their relationship field instead.

## 5. Verify

`npm run check` in the theme folder.
