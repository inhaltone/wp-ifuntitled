---
applyTo: "wp-content/themes/if-untitled/{vite.config.js,vite-plugins/**,inc/vite.php,package.json}"
description: "Vite build pipeline and WordPress integration rules"
---

# Vite pipeline

- Vite 8 (Rolldown). Use `build.rolldownOptions` (not `rollupOptions`). Node ≥ 20.19.
- Single entry: `src/main.js` (manifest key `src/main.js`, referenced by `IF_UNTITLED_VITE_ENTRY` in `inc/vite.php`).
  If you add another entry, add it to `rolldownOptions.input` **and** enqueue it in `inc/vite.php`.
- Manifest lives at `dist/.vite/manifest.json` (Vite ≥ 5). `inc/vite.php` enqueues entry CSS (+ CSS of imported chunks),
  the entry as a script module (`wp_enqueue_script_module`), and `modulepreload` for imported chunks.
- `base` is `/` in dev and `./` in build so assets resolve relative to the theme’s `dist/` regardless of install path.
- `server.origin` must equal the dev URL so CSS `url()` assets load from Vite in dev.
- `server.cors.origin` allows `WP_URL` (from `.env.local`) plus `localhost` / `*.test` / `*.local`. Don't set `cors: true`.
- `build.modulePreload.polyfill` is `false` (PHP prints `modulepreload`; all target browsers support it) and
  `assetsInlineLimit: 0` keeps fonts/images as separate cacheable files.
- `css.preprocessorOptions.scss.fatalDeprecations` turns Sass deprecations into errors — don't remove entries to make a build pass; fix the SCSS.
- `vite-plugins/wordpress.js` writes/removes the `hot` file and sends `full-reload` on `.php` changes. Keep it dependency-free.
- Dev settings come from `.env.local` (`WP_URL`, `VITE_DEV_HOST`, `VITE_DEV_PORT`). Document new vars in `.env.example`.
- Node version: `.nvmrc` + `engines` (Vite 8 needs `^20.19.0 || >=22.12.0`).
- Upgrades: `npm i -D vite@latest sass-embedded@latest`, then `npm run check` and fix any issues. Check the
  Vite migration guide for breaking changes.
- Verifying builds, dev mode, troubleshooting and upgrades: follow the **`vite-build-verify`** skill.
