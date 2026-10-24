import { defineConfig, loadEnv } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import wordpress from './vite-plugins/wordpress.js';

const src = (p = '') => fileURLToPath(new URL(`./src/${p}`, import.meta.url));

export default defineConfig(({ command, mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const host = env.VITE_DEV_HOST || 'localhost';
    const port = Number(env.VITE_DEV_PORT || 3000);
    const origin = `http://${host}:${port}`;

    return {
        plugins: [wordpress()],

        // Dev: assets are served from the Vite server. Build: relative URLs so the
        // theme works regardless of the WordPress install path / theme folder name.
        base: command === 'serve' ? '/' : './',
        publicDir: 'public',

        resolve: {
            alias: {
                '@': src(),
            },
        },

        css: {
            devSourcemap: true,
            preprocessorOptions: {
                scss: {
                    // Lets every partial do `@use 'abstracts' as *;`
                    loadPaths: [src('styles')],
                    // Turn every Sass deprecation into a hard error so they never slip in.
                    fatalDeprecations: ['import', 'global-builtin', 'slash-div', 'if-function'],
                },
            },
        },

        server: {
            host,
            port,
            strictPort: true,
            origin,
            // Allow the WordPress site (another origin) to load modules from Vite.
            cors: {
                origin: [
                    env.WP_URL,
                    /^https?:\/\/(?:localhost|127\.0\.0\.1|[\w.-]+\.(?:test|local|localhost))(?::\d+)?$/,
                ].filter(Boolean),
            },
            hmr: { host },
        },

        build: {
            outDir: 'dist',
            emptyOutDir: true,
            manifest: true,
            sourcemap: false,
            // inc/vite.php prints <link rel="modulepreload"> itself; every target browser supports it.
            modulePreload: { polyfill: false },
            // Keep fonts/images as separate cacheable files instead of base64 in CSS/JS.
            assetsInlineLimit: 0,
            rolldownOptions: {
                input: {
                    main: src('main.js'),
                },
            },
        },
    };
});
