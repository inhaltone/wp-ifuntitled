---
name: create-acf-field-group
description: Add or change ACF Pro field groups for the if-untitled theme (post types, page templates, terms, Theme Options) with naming, location, return-format and rendering conventions, saved to acf-json. Use when asked to add custom fields, flexible content, repeaters, relationships or Theme Options settings.
---

# Create an ACF field group

Theme root: `wp-content/themes/if-untitled/`. ACF Pro lives in `wp-content/plugins/advanced-custom-fields-pro`
(never edit it; update from wp-admin with the license key).

## 0. Before adding a field

- **Native first.** Title, date, status, excerpt, featured image, menu order, categories/tags, author already exist —
  use them. ACF is only for what core lacks.
- **One source of truth.** Shared data (clients, projects, team) is its own post type and is _related/queried_, never
  copied into page fields.
- **YAGNI.** No field, layout or option until a template needs it.

## 1. Naming

| Item          | Convention                                                  | Example                                 |
| ------------- | ----------------------------------------------------------- | --------------------------------------- |
| Group key     | `group_{context}` (set in the JSON, not the random default) | `group_project`, `group_page_studio`    |
| Field name    | `snake_case`, prefixed with its context                     | `project_text`, `studio_team`           |
| Sub-fields    | short, unprefixed (the parent already scopes them)          | `project_metadata` → `location`, `year` |
| Shared fields | named after the concept, not a post type                    | `content_grid`                          |
| Choice values | `snake_case` value, human label                             | `in_progress : In progress`             |

**Never rename a field after content has been entered** — the stored meta key changes and data is orphaned.
Add a new field and migrate instead.

## 2. Location rules

| Target          | Rule                                                            |
| --------------- | --------------------------------------------------------------- |
| Post type       | _Post Type is {cpt}_ (combine with OR for shared groups)        |
| Front page      | _Page Type is Front Page_                                       |
| Other pages     | _Page Template is {Template Name}_ — see `create-page-template` |
| Terms           | _Taxonomy is {taxonomy}_                                        |
| Global settings | _Options Page is Theme Options_ (registered in `inc/acf.php`)   |

Never target a page/post **ID or slug** — they differ between environments.

## 3. Field settings

- **Return formats:** images/files → **ID**; post object / relationship → **IDs**; link → array.
- **Internal links are always a post object (one) or relationship (ordered list) returning IDs**, never a URL / `link`
  field; default scope = all public post types (published only), rendered through `if_untitled_resolve_item()`.
  URL / `link` fields are for external sites only.
- Relationship / post object: restrict to the post type(s) and add filters in `inc/acf.php` when needed
  (`acf/fields/relationship/query/name={field}`, e.g. published only, limit to a term).
- Taxonomy field that should set the post's real term: _Save Terms_ + _Load Terms_ on (no custom code).
- **Controlled values** (a place, a material — anything that must be spelled one way) are a **private taxonomy**
  (`register-custom-post-type`), picked with an ACF taxonomy field: select + stylised UI (searchable), _Create Terms_
  on, Save / Load Terms on, returns ID. Not a text field; not a hard-coded `select` (new values would need a deploy). Composite values are a hierarchy
  (country › city), joined by the helper — never typed as one string.
  Fixed enums that only code understands (`status`) stay a `select`.
- Group (`type: group`) for a block of related metadata → one box in the editor, read as an array.
- WYSIWYG: basic toolbar, media upload off unless needed. Mark required only what a template cannot do without.
- Secrets / per-environment values (API tokens) are **not** fields: use a constant in `wp-config.php` (`IF_UNTITLED_*`).

## 4. Save to JSON

Build/edit the group in wp-admin → it is written to `acf-json/group_*.json` automatically (`inc/acf.php`).
Commit the JSON. If the JSON is newer than the DB, use _Field Groups → Sync_. Never hand-edit field keys (`field_…`).

## 5. Read fields — helpers fetch, templates render

Data shaping lives in `inc/template-tags.php` as `if_untitled_*()` helpers; templates stay logic-free.

```php
function if_untitled_project_metadata(?int $post_id = null): array
{
    if (!function_exists('get_field')) {
        return [];
    }
    $meta     = get_field('project_metadata', $post_id) ?: [];
    $city     = !empty($meta['location']) ? get_term((int) $meta['location'], 'project_location') : null;
    $country  = $city instanceof WP_Term && $city->parent ? get_term($city->parent, 'project_location') : null;
    $location = $city instanceof WP_Term
        ? implode(', ', array_filter([$city->name, $country instanceof WP_Term ? $country->name : '']))
        : '';

    // Always this order: Location · Client · Year · Status.
    return [
        'location' => $location, // "City, Country"
        'clients'  => array_map('intval', $meta['client'] ?? []),
        'year'     => $meta['year'] ?? '',
        'status'   => $meta['status'] ?? '',
    ];
}
```

In templates:

```php
<?php echo if_untitled_image(get_post_thumbnail_id(), 'card', ['class' => 'project-card__image']); ?>
<p><?php echo esc_html(get_the_excerpt()); ?></p>
<div><?php echo wp_kses_post(get_field('project_text')); ?></div>
```

- Guard every ACF call in shared code: `function_exists('get_field')`. The theme must work with ACF deactivated.
- Images: always `if_untitled_image($id, $context)` (skill `render-responsive-image`) — never `wp_get_attachment_image()` with a hand-picked size, never `<img>` from URLs.
- Escape late: `esc_html`, `esc_url`, `esc_attr`, `wp_kses_post` (WYSIWYG).
- Options: `get_field('contact_email', 'option')`. Terms: `get_field('{field}', $term)`.
- Flexible content: loop `have_rows()` / `get_row_layout()` and render one `components/{layout}.php` per layout.

## 6. Verify

```bash
cd wp-content/themes/if-untitled && npm run check
```

Check the new `acf-json/*.json` is staged and the page renders with the fields empty (no notices).
