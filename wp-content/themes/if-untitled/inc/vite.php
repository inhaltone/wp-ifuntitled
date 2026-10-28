<?php
/**
 * Vite integration.
 *
 * Development: `npm run dev` writes a `hot` file (theme root) with the dev-server
 * origin. When present, the Vite client + entry are loaded from the dev server (HMR).
 * Production: assets are resolved from dist/.vite/manifest.json (`npm run build`).
 *
 * Force a mode from wp-config.php with: define('IF_UNTITLED_VITE_DEV', false|true);
 *
 * @link https://vite.dev/guide/backend-integration
 * @package if-untitled
 */

defined('ABSPATH') || exit;

const IF_UNTITLED_VITE_ENTRY = 'src/main.js';
const IF_UNTITLED_DIST       = 'dist';

// The 2 faces of the first paint (body text + display titles); the bold face loads on demand.
const IF_UNTITLED_PRELOAD_FONTS = [
    'src/assets/fonts/GT-America-Standard-Medium-Trial.woff2',
    'src/assets/fonts/GT-Flexa-Expanded-Bold-Trial.woff2',
];

/**
 * Dev-server origin (e.g. http://localhost:3000) or null when not in dev mode.
 */
function if_untitled_vite_dev_server(): ?string
{
    static $server = false;
    if ($server !== false) {
        return $server;
    }

    $server   = null;
    $hot_file = IF_UNTITLED_DIR . '/hot';

    if (defined('IF_UNTITLED_VITE_DEV') && IF_UNTITLED_VITE_DEV === false) {
        return $server;
    }
    // WordPress reports 'production' by default, so only trust an *explicit* production setting.
    $env_is_explicit = defined('WP_ENVIRONMENT_TYPE') || getenv('WP_ENVIRONMENT_TYPE') !== false;
    if ($env_is_explicit && wp_get_environment_type() === 'production' && !defined('IF_UNTITLED_VITE_DEV')) {
        return $server;
    }
    if (is_readable($hot_file)) {
        $server = untrailingslashit(trim((string) file_get_contents($hot_file))) ?: null;
    } elseif (defined('IF_UNTITLED_VITE_DEV') && IF_UNTITLED_VITE_DEV === true) {
        $server = 'http://localhost:3000';
    }

    return $server;
}

/**
 * Parsed Vite manifest (empty array when missing).
 */
function if_untitled_vite_manifest(): array
{
    static $manifest = null;
    if ($manifest !== null) {
        return $manifest;
    }

    $path     = IF_UNTITLED_DIR . '/' . IF_UNTITLED_DIST . '/.vite/manifest.json';
    $manifest = is_readable($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];

    return $manifest;
}

/**
 * Public URL of a file inside /dist.
 */
function if_untitled_dist_uri(string $file): string
{
    return IF_UNTITLED_URI . '/' . IF_UNTITLED_DIST . '/' . ltrim($file, '/');
}

/**
 * URL of a source asset (e.g. 'src/assets/images/logo.svg') for use in PHP templates.
 * The asset must be imported somewhere in JS/SCSS (or be an entry) to be in the manifest.
 */
function if_untitled_asset(string $src): string
{
    $server = if_untitled_vite_dev_server();
    if ($server) {
        return $server . '/' . ltrim($src, '/');
    }
    $manifest = if_untitled_vite_manifest();

    return isset($manifest[$src]['file']) ? if_untitled_dist_uri($manifest[$src]['file']) : '';
}

/**
 * Recursively collect CSS files and JS chunks imported by a manifest entry.
 *
 * @return array{css: string[], preload: string[]}
 */
function if_untitled_vite_collect(string $key, array $manifest, array &$seen = []): array
{
    $out = ['css' => [], 'preload' => []];
    if (isset($seen[$key]) || !isset($manifest[$key])) {
        return $out;
    }
    $seen[$key] = true;
    $chunk      = $manifest[$key];

    $out['css'] = $chunk['css'] ?? [];
    foreach ($chunk['imports'] ?? [] as $import) {
        $child          = if_untitled_vite_collect($import, $manifest, $seen);
        $out['css']     = array_merge($out['css'], $child['css']);
        $out['preload'] = array_merge($out['preload'], [$manifest[$import]['file']], $child['preload']);
    }

    return ['css' => array_values(array_unique($out['css'])), 'preload' => array_values(array_unique($out['preload']))];
}

add_action('wp_head', function (): void {
    $server = if_untitled_vite_dev_server();
    if ($server) {
        printf('<script type="module" src="%s"></script>' . "\n", esc_url($server . '/@vite/client'));
        printf('<script type="module" src="%s"></script>' . "\n", esc_url($server . '/' . IF_UNTITLED_VITE_ENTRY));
        return;
    }

    foreach (IF_UNTITLED_PRELOAD_FONTS as $font) {
        $url = if_untitled_asset($font);
        if ($url) {
            printf('<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url($url));
        }
    }

    $manifest = if_untitled_vite_manifest();
    foreach (if_untitled_vite_collect(IF_UNTITLED_VITE_ENTRY, $manifest)['preload'] as $file) {
        printf('<link rel="modulepreload" href="%s">' . "\n", esc_url(if_untitled_dist_uri($file)));
    }
}, 1);

add_action('wp_enqueue_scripts', function (): void {
    if (if_untitled_vite_dev_server()) {
        return;
    }

    $manifest = if_untitled_vite_manifest();
    if (!isset($manifest[IF_UNTITLED_VITE_ENTRY])) {
        if (current_user_can('manage_options')) {
            trigger_error('if-untitled: dist/.vite/manifest.json missing — run `npm run build` or `npm run dev`.', E_USER_WARNING);
        }
        return;
    }

    // Hashed filenames handle cache-busting, so no ?ver= query string (null).
    foreach (if_untitled_vite_collect(IF_UNTITLED_VITE_ENTRY, $manifest)['css'] as $i => $css) {
        wp_enqueue_style('if-untitled-' . $i, if_untitled_dist_uri($css), [], null);
    }

    wp_enqueue_script_module('if-untitled-main', if_untitled_dist_uri($manifest[IF_UNTITLED_VITE_ENTRY]['file']), [], null);
});
