---
name: create-page-template
description: Add a new WordPress page template, archive or single template to the if-untitled theme with matching route id, page SCSS and page-scoped JS. Use when asked to create a new page (about, contact, landing), front-page/home/category templates, an archive-{cpt}.php, taxonomy-{tax}.php or single-{cpt}.php, or when deciding template file names.
---

# Create a page / archive / single template

Theme root: `wp-content/themes/if-untitled/`. Pick a kebab-case **route id** (Stylelint rejects non-kebab ids) (e.g. `about`, `archive-project`, `project`).

## 1. Choose the file name (WordPress template hierarchy)

| Need                                             | File                                                                            | Route id (`main#id`)       |
| ------------------------------------------------ | ------------------------------------------------------------------------------- | -------------------------- |
| Selectable page template (pages with ACF fields) | `page-templates/{id}.php` — file name must **not** start with `page-`           | `{id}`                     |
| One specific page, no ACF fields                 | `page-{slug}.php` in the theme root (picked automatically by slug)              | `{slug}`                   |
| Any other page (legal, privacy)                  | `page.php`                                                                      | `page`                     |
| Homepage                                         | `front-page.php` (Settings → Reading → static front page)                       | `front-page`               |
| Posts page (blog)                                | `home.php` — the **posts page**, not the homepage                               | blog name, e.g. `journal`  |
| Post category archive                            | `category.php` (same markup/route as `home.php` when used as a filter)          | same as `home.php`         |
| Single post                                      | `single.php`                                                                    | e.g. `journal-entry`       |
| CPT archive                                      | `archive-{post_type}.php`                                                       | `archive-{post_type}`      |
| CPT taxonomy archive                             | `taxonomy-{taxonomy}.php` (same route as its CPT archive when used as a filter) | `archive-{post_type}`      |
| CPT single                                       | `single-{post_type}.php`                                                        | `{post_type}` (kebab-case) |
| Not found / fallback                             | `404.php` / `index.php` (required)                                              | `not-found` / —            |

Naming rules:

- Selectable templates need the headers `Template Name: …` and `Template Post Type: page`. ACF page field groups
  use the location _Page Template is {Template Name}_ (see `create-acf-field-group`).
- Pages with ACF fields always use a named template in `page-templates/`, never `page-{slug}.php` / `page-{id}.php`:
  slugs/IDs differ between environments and ACF's _Page Template_ rule only sees named templates.
- Inside `page-templates/` file names must **not** start with `page-` (WordPress would treat them as slug
  templates); only one folder level is supported.
- File names are kebab-case, except hierarchy names that embed a key verbatim (`taxonomy-project_category.php`,
  `single-team_member.php`).
- Private post types (`public => false`) get **no** archive/single template.
- Filtered archives (All + terms): follow `create-archive-filter`.

Official references: [Template Hierarchy](https://developer.wordpress.org/themes/basics/template-hierarchy/) ·
[Page Templates](https://developer.wordpress.org/themes/classic-themes/templates/page-template-files/)

## 2. Template skeleton

```php
<?php
/**
 * Template Name: About
 * Template Post Type: page
 *
 * @package if-untitled
 */

get_header();
?>
<main id="{id}" class="{id}">
    <?php while (have_posts()) : the_post(); ?>
        <div class="{id}__layout grid">
            <h1 class="{id}__title grid__item" style="--col-start: 1; --col-span: 1"><?php the_title(); ?></h1>
            <div class="{id}__content grid__item" style="--col-start: 2; --col-span: 3">
                <?php get_template_part('components/text-render'); ?>
            </div>
        </div>
    <?php endwhile; ?>
</main>
<?php
get_footer();
```

- Templates render, helpers fetch: no queries or data shaping in the template — put them in an
  `if_untitled_*()` helper in `inc/template-tags.php` or in `pre_get_posts`.
- Reuse the 5-column `.grid` (`.grid__item` + `--col-start` / `--col-span`) and components (`page-intro`, `content-grid`, `project-card`,
  `journal-card`, `text-render`) before inventing new markup;
  load parts with `if_untitled_component('{name}', $args)`.
- Archives: loop with the type's card (`components/project-card`, `components/journal-card`) inside one shared `.grid.grid--cards`, then pagination
  (`if_untitled_paged_links()` / `the_posts_pagination()`).
- ACF fields: follow `create-acf-field-group` (guard `function_exists('get_field')`, image IDs →
  `wp_get_attachment_image()`, escape late).

## 3. Page styles — `src/styles/pages/_{id}.scss`

```scss
@use "abstracts" as *;

// Replace "about" with your route id.
main#about {
  // page-only overrides; reusable parts belong in components/
}
```

Register with `@use '{id}';` in `src/styles/pages/_index.scss`.

## 4. Page JS (only if needed)

- Add `static {CONST} = new Router('{id}');` to `src/js/utils/Router.js`.
- In `src/main.js`: `onPageMounted(Router.{CONST}.routeId, ({ element }) => { … });`

## 5. Verify

```bash
cd wp-content/themes/if-untitled && npm run format && npm run check
```
