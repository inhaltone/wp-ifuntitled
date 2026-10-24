---
name: create-page-template
description: Add a new WordPress page template, archive or single template to the if-untitled theme with matching route id, page SCSS and page-scoped JS. Use when asked to create a new page (about, contact, landing), an archive-{cpt}.php or single-{cpt}.php.
---

# Create a page / archive / single template

Theme root: `wp-content/themes/if-untitled/`. Pick a kebab-case **route id** (Stylelint rejects non-kebab ids) (e.g. `about`, `archive-projects`, `project`).

## 1. Choose the file name (WordPress template hierarchy)
| Need | File |
|---|---|
| Selectable page template | `page-templates/{id}.php` or `page-{slug}.php` (auto for that slug) |
| CPT archive | `archive-{post_type}.php` |
| CPT single | `single-{post_type}.php` |
| Taxonomy | `taxonomy-{taxonomy}.php` |

Selectable templates need the header `Template Name: …`.

## 2. Template skeleton
```php
<?php
/**
 * Template Name: About
 *
 * @package if-untitled
 */

get_header();
?>
<main id="{id}" class="{id}">
    <?php while (have_posts()) : the_post(); ?>
        <div class="single-layout">
            <div class="single-layout__title">
                <h1><?php the_title(); ?></h1>
            </div>
            <div class="single-layout__content">
                <?php get_template_part('components/text-render'); ?>
            </div>
        </div>
    <?php endwhile; ?>
</main>
<?php
get_footer();
```
- Reuse layouts (`.container`, `.single-layout`) and components (`post-card`, `text-render`) before inventing new markup.
- Archives: loop with `components/post-card` inside `<ul class="post-list">`, then `the_posts_pagination()`.
- ACF fields (ACF Pro is installed): guard with `function_exists('get_field')`, escape output. Field groups sync to `acf-json/`.

## 3. Page styles — `src/styles/pages/_{id}.scss`
```scss
@use 'abstracts' as *;

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
