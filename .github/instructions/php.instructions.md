---
applyTo: "wp-content/themes/if-untitled/**/*.php"
description: "WordPress PHP template and inc/ conventions for the if-untitled theme"
---

# PHP / WordPress conventions

## Files

- Every file starts with a docblock containing a one-line summary and `@package if-untitled`.
- Files in `inc/` begin with `defined('ABSPATH') || exit;`.
- `functions.php` only defines constants and `require_once`s files from `inc/`. New concerns → new `inc/{concern}.php`.
- Template parts live in `components/` and are loaded with `get_template_part('components/{name}', null, $args)`
  or the helper `if_untitled_component('{name}', $args)`. Read args via `$args['key'] ?? default`.

## Templates

- Wrap with `get_header();` … `get_footer();`. `header.php` opens `#app` + `#content`, `footer.php` closes them.
- Each template renders exactly one `<main id="{route-id}" class="{route-id}">`. The id is the hook used by
  JS (`Router`) and SCSS (`main#{route-id}`). Use kebab-case ids.
- File names and route ids follow the template-hierarchy table in the **`create-page-template`** skill
  (`page-templates/` for pages with ACF fields, `home.php` = posts page, no templates for private post types).
- Templates render, helpers fetch: queries and data shaping live in `if_untitled_*()` helpers in
  `inc/template-tags.php` or in `pre_get_posts`, not in templates.
- Use The Loop (`while (have_posts()) : the_post();`) even on singular templates.
- Use alternative syntax (`if (): … endif;`) in markup-heavy files; regular braces in `inc/`.

## Naming

- Functions: `if_untitled_{verb}_{noun}()`. Constants: `IF_UNTITLED_*`. Handles: `if-untitled-*`.
- Text domain: `'if-untitled'`. Menu locations: `primary_menu`, `footer_menu` (snake_case).
- Prefer closures for hook callbacks: `add_action('init', function (): void { … });`.
- Use type declarations and return types (PHP 8.1+).

## Security & output

- Escape late: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`. Template tags such as
  `the_title()`, `the_content()`, `the_permalink()` already escape/filter.
- Translate: `esc_html_e('Text', 'if-untitled')`, `__()`, `_x()`.
- Never echo raw `$_GET`/`$_POST`; sanitize with `sanitize_text_field()` etc. and verify nonces for forms.

## ACF

- Field naming, locations, return formats and rendering: follow the **`create-acf-field-group`** skill.
- Guard every ACF call: `if (function_exists('get_field'))` in shared code. Field groups are saved as JSON in
  `acf-json/` — commit those files.
- Image fields return **IDs**: render with `if_untitled_image()` / `components/image.php` (skill `render-responsive-image`),
  never raw URLs or hand-written `<img>`.

## Post types & taxonomies

- Registered in code in `inc/post-types.php` (no CPT UI plugin) with `'with_front' => false` — see the
  **`register-custom-post-type`** skill.

## Assets

- Never enqueue theme CSS/JS manually — everything goes through `src/main.js` and `inc/vite.php`.
- To get a URL for a Vite-processed asset in PHP use `if_untitled_asset('src/assets/images/x.svg')`
  (the file must be imported somewhere in JS/SCSS so it is in the manifest). Static, unprocessed
  files can go in `public/` and are copied to `dist/`.

## ACF Pro

- The plugin lives in `wp-content/plugins/advanced-custom-fields-pro` — never edit it; update it from wp-admin with the license key.
- Theme code must keep working if ACF is deactivated (guard with `function_exists()`), see `inc/acf.php`.

## Validation

- Run `npm run lint:php` from the theme folder after changes (full gate: `npm run check`).

## Skills (step-by-step recipes in `.github/skills/`)

| Task                                                                 | Skill                       |
| -------------------------------------------------------------------- | --------------------------- |
| New page / archive / single / taxonomy template, template file names | `create-page-template`      |
| Reusable template part in `components/`                              | `create-theme-component`    |
| Custom post type or taxonomy                                         | `register-custom-post-type` |
| ACF field group, Theme Options field                                 | `create-acf-field-group`    |
| "All + terms" filter on an archive                                   | `create-archive-filter`     |
