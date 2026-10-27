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
