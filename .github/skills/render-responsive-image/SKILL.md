---
name: render-responsive-image
description: Render optimised, responsive images in the if-untitled theme — one image component and helper on top of wp_get_attachment_image() with the right WordPress sizes, srcset/sizes per grid context, native lazy loading, LCP priority, aspect ratios and WebP. Use whenever a template, card, content grid row, hero, logo or portrait outputs an image, or when asked about image sizes, srcset, lazy loading, performance or Lighthouse image warnings.
---

# Render a responsive image

Theme root: `wp-content/themes/if-untitled/`. Every image on the site goes through **one helper** and **one component**.
WordPress core already does the heavy lifting (`srcset`, `width`/`height`, `loading="lazy"`, `decoding="async"`,
`sizes="auto"` for lazy images) — the theme only adds what core can't know: **how wide the image is on screen** and
**which image is the LCP**. A `content_grid` image block can be a video instead — see skill `render-video`.

Never: raw URLs (`wp_get_attachment_image_url()` in `<img src>`), `the_post_thumbnail('full')`, a JS lazy-load library,
`data-src`, hard-cropped image sizes, or `<img>` written by hand.

## 1. Media setup — `inc/media.php`

New concern → new file, required from `functions.php` after `setup.php`.

```php
<?php
/**
 * Image sizes, srcset limits and output format.
 *
 * @package if-untitled
 */

defined('ABSPATH') || exit;

// Core's proportional sizes (300 · 768 · 1024 · 1536 · 2048 + the 2560 "-scaled" original) already cover a
// 1920 px design at 1x and 2x — register no extra sizes and never hard-crop (a crop changes the aspect ratio and
// drops the size from srcset). Crops are done in CSS (aspect-ratio + object-fit).

// Allow the 2560 px scaled original in srcset (default cap 2048) — full-bleed hero on retina.
add_filter('max_srcset_image_width', fn (): int => 2560);

// Generate WebP sub-sizes for JPEG uploads when the server supports it (the uploaded original stays untouched).
add_filter('image_editor_output_format', function (array $formats): array {
    if (wp_image_editor_supports(['mime_type' => 'image/webp'])) {
        $formats['image/jpeg'] = 'image/webp';
    }
    return $formats;
});
```

Fix this **before the first upload**. If it ever changes: `wp media regenerate --yes`.

## 2. Helper — `inc/template-tags.php`

```php
/**
 * Responsive <img> for an attachment ID. $context sets `sizes` (how wide the image renders on screen).
 * $args: ['priority' => bool (LCP image: eager + fetchpriority high), 'alt' => string, 'class' => string]
 */
function if_untitled_image(int $id, string $context = 'full', array $args = []): string
{
    if (!$id) {
        return '';
    }

    // Keep in sync with $grid-breakpoints (SCSS) and BREAKPOINTS (MediaBreakpoints.js): md 768, lg 992.
    if (preg_match('/^span-([1-5])$/', $context, $match)) {
        $sizes = sprintf('(min-width: 768px) %dvw, 100vw', (int) $match[1] * 20);
    } else {
        $sizes = match ($context) {
            'card'  => '(min-width: 992px) 33vw, (min-width: 768px) 50vw, 100vw',
            'logo'  => '240px',
            default => '100vw',
        };
    }

    $attr = [
        'sizes' => $sizes,
        'class' => trim('image__img ' . ($args['class'] ?? '')),
    ];
    if (isset($args['alt'])) {
        $attr['alt'] = $args['alt'];
    }
    if (!empty($args['priority'])) {
        $attr['loading']       = 'eager';
        $attr['fetchpriority'] = 'high';
    }

    // 'large' is only the fallback src; srcset carries every proportional size up to 2560.
    return wp_get_attachment_image($id, 'large', false, $attr);
}
```

Contexts — add one here instead of writing `sizes` in a template:

| Context    | Where                                                        | `sizes`                             |
| ---------- | ------------------------------------------------------------ | ----------------------------------- |
| `full`     | Home hero, full-bleed images (default)                       | `100vw`                             |
| `span-{n}` | `content_grid` image, project hero, Studio / Advisory images | `n × 20vw` from `md`, `100vw` below |
| `card`     | Work + Journal archive cards (3 cols `lg`, 2 `md`, 1 `sm`)   | `33vw` · `50vw` · `100vw`           |
| `logo`     | clients                                                      | fixed `240px`                       |

`content_grid` derives the span from its column range (`2-4` → `span-3`).

## 3. Component — `components/image.php`

The wrapper reserves the space (no layout shift) and applies a fixed ratio when the design crops. Render with
`if_untitled_component('image', ['id' => $id, 'context' => 'card', 'ratio' => '2x3'])`.

```php
<?php
/**
 * Responsive image with an optional fixed ratio.
 * $args: ['id' => int, 'context' => string, 'ratio' => '' | '2x3' | '3x4' | '4x3' | '16x9', 'priority' => bool, 'alt' => string]
 *
 * @package if-untitled
 */

$id = (int) ($args['id'] ?? 0);
if (!$id) {
    return;
}
$ratio = $args['ratio'] ?? '';
?>
<figure class="image<?php echo $ratio ? ' image--ratio-' . esc_attr($ratio) : ''; ?>">
    <?php // Core markup from wp_get_attachment_image(), already escaped. ?>
    <?php echo if_untitled_image($id, $args['context'] ?? 'full', $args); ?>
</figure>
```

SCSS — `src/styles/components/_image.scss`, registered in `components/_index.scss`:

```scss
@use 'abstracts' as *;

.image {
    margin: 0;

    &__img {
        display: block;
        inline-size: 100%;
        block-size: auto; // width/height attributes keep the ratio → no CLS
    }

    &--ratio-2x3 {
        aspect-ratio: 2 / 3;
    }

    &--ratio-3x4 {
        aspect-ratio: 3 / 4;
    }

    &--ratio-4x3 {
        aspect-ratio: 4 / 3;
    }

    &--ratio-16x9 {
        aspect-ratio: 16 / 9;
    }

    &[class*='--ratio-'] &__img {
        block-size: 100%;
        object-fit: cover;
    }
}
```

## 4. Rules

- **Natural ratio by default** (Work cards, `content_grid`); fixed ratio only where the design crops (Journal cards 2:3).
- **Exactly one `priority` image per route** — the LCP: Home hero, project featured image, the first archive card.
  Everything else stays lazy (core default). Never pass `priority` to every item of a loop.
- **Alt:** from the media library (core adds it). Pass `'alt' => ''` when the image is decorative or sits in a link
  whose text already names the target (cards: the title is in the same link).
- ACF image fields return **IDs** (`create-acf-field-group` skill); featured images via `get_post_thumbnail_id()`.
- Missing image → render nothing (the helper returns `''`); the layout must not break.
- No JS. Fade-in, LQIP placeholders etc. only if the design asks for them (YAGNI).

## 5. Verify

```bash
cd wp-content/themes/if-untitled
npm run check
wp media image-size           # only core sizes listed
```

In the browser (1920 px and a 375 px viewport): each `<img>` has `srcset`, the `sizes` of its context and
`width`/`height`; DevTools → Network → Img shows a file close to rendered width × DPR (never 2560 for a card).
Lighthouse: no "Properly size images", "Defer offscreen images" or "LCP image was lazily loaded"; CLS ≈ 0.
