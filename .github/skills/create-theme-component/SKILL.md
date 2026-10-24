---
name: create-theme-component
description: Scaffold a new UI component for the if-untitled WordPress theme — PHP template part, BEM SCSS partial and (optionally) an interactive ES class wired into main.js. Use when asked to add a section, block, widget, card, slider, accordion, modal or any reusable piece of UI.
---

# Create a theme component

Theme root: `wp-content/themes/if-untitled/`. Use kebab-case `{name}` (e.g. `image-gallery`) and PascalCase `{Name}` (e.g. `ImageGallery`).

## 1. PHP template part — `components/{name}.php`
```php
<?php
/**
 * {Short description}.
 * Optional $args: ['title' => string]
 *
 * @package if-untitled
 */

$title = $args['title'] ?? '';
?>
<section class="{name}" data-{name}>
    <?php if ($title) : ?>
        <h2 class="{name}__title"><?php echo esc_html($title); ?></h2>
    <?php endif; ?>
    <div class="{name}__body">…</div>
</section>
```
- Add `data-{name}` only if there is JS. Escape every dynamic value.
- Render it from a template with `if_untitled_component('{name}', ['title' => …]);`.

## 2. SCSS partial — `src/styles/components/_{name}.scss`
```scss
@use 'abstracts' as *;

.{name} {
    padding: 40px 0;

    &__title {
        @include font-bold(20);

        @include media-breakpoint-up(lg) {
            @include font-bold(32);
        }
    }

    &.is-active { … }
}
```
Then register it: add `@use '{name}';` to `src/styles/components/_index.scss`.

## 3. (Interactive only) JS class — `src/js/components/{Name}.js`
Follow the component pattern from `.github/instructions/javascript.instructions.md`
(options-object constructor with defaults, early return when markup missing, `main()`, class-based state).

Wire it in `src/main.js`:
- Small & used on most pages → import at top and instantiate inside `onMounted()`.
- Heavy or rare → `lazyComponent('[data-{name}]', () => import('@/js/components/{Name}.js'), { selector: '[data-{name}]' });`
- Only on one template → inside `onPageMounted(Router.X.routeId, …)`.

Don't reach for libraries (no Bootstrap, Swiper, jQuery…). Only if the user explicitly asks for one: `npm i {lib}` in the
theme folder and import it **only** inside the component, lazy-loaded with `lazyComponent()` so it is code-split.

## 4. Verify
```bash
cd wp-content/themes/if-untitled
npm run format     # Prettier
npm run check      # format:check + ESLint + Stylelint + php -l + build (Sass deprecations are fatal)
```
Class names must be BEM (Stylelint enforces it). Check `dist/.vite/manifest.json` contains a separate chunk if you lazy-loaded the component.
