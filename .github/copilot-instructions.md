# Copilot instructions — if-untitled

This repository is a full WordPress install. **Only edit the custom theme** at
`wp-content/themes/if-untitled/` unless explicitly asked. Never modify WordPress core
(`wp-admin/`, `wp-includes/`, root `wp-*.php`) or third-party plugins/themes (`advanced-custom-fields-pro`,
`akismet`, …). Plugins are updated through WordPress, never edited by hand.
`if-untitled` is the **only** theme in `wp-content/themes/` — don't add or restore default themes (`twentytwenty*`).

## Stack

- Classic (PHP) WordPress theme, WordPress ≥ 6.5, PHP ≥ 8.1. No block theme / `theme.json`.
- **Vite 8** (Rolldown) as the asset pipeline, single entry `src/main.js`.
- **SCSS** compiled by `sass-embedded` using the **module system** (`@use` / `@forward`). `@import` is forbidden.
- Vanilla **ES modules** (no jQuery, no JS framework).
- **No CSS framework** (no Bootstrap/Tailwind). Layout, reset and utilities are our own SCSS in `src/styles/`.
- **ACF Pro** is installed in `wp-content/plugins/advanced-custom-fields-pro` (tracked in git) and is the way to add custom fields.
- Tooling: ESLint (flat config), Stylelint (`stylelint-config-standard-scss` + BEM rules), Prettier. Node version in `.nvmrc`.

## Commands (run inside `wp-content/themes/if-untitled/`)

| Task | Command |
|------|---------|
| Install | `npm install` |
| Dev server (HMR + PHP live reload, port 3000) | `npm run dev` |
| Production build → `dist/` | `npm run build` |
| Lint JS / SCSS / PHP | `npm run lint:js` · `npm run lint:css` · `npm run lint:php` (all: `npm run lint`) |
| Auto-format JS/SCSS/JSON/MD | `npm run format` |
| **Everything (CI-style)** | `npm run check` = format:check + lint + build |

Before finishing any task run **`npm run check`** and make sure it passes. Sass deprecations are configured as
**fatal** (`fatalDeprecations` in `vite.config.js`), so the build fails on `@import`, global built-ins, `/` division, `if()`.
Run `npm run format` instead of hand-formatting.

## Architecture

```
functions.php        thin bootstrap → require inc/*.php
inc/vite.php         dev (hot file) vs prod (dist/.vite/manifest.json) asset loading
inc/setup.php        theme supports, nav menus, head cleanup
inc/template-tags.php  if_untitled_* helpers
inc/acf.php          ACF options page + acf-json sync (guarded)
components/*.php     template parts
*.php                template-hierarchy files; each renders <main id="{route-id}">
src/main.js          entry: imports style.scss, wires components with hooks
src/js/utils/        Hooks, Router (route-id enum), MediaBreakpoints, RouterTransition, LazyComponent
src/js/components/   one ES class per file
src/styles/          abstracts/ base/ layouts/ components/ pages/ utilities/  (each has _index.scss)
```

### The PHP ↔ SCSS ↔ JS contract

A feature usually touches three places that share names:

1. **PHP** — `components/{name}.php` (or a template) renders BEM markup, root class `.{name}`.
2. **SCSS** — `src/styles/components/_{name}.scss`, registered in `components/_index.scss`.
3. **JS** (only if interactive) — `src/js/components/{PascalName}.js`, instantiated in `src/main.js`.

Page-specific code is keyed by the `<main id>` of the template: `Router.{CONST}` in
`src/js/utils/Router.js` ↔ `main#{id}` in `src/styles/pages/_{id}.scss` ↔ `onPageMounted(Router.{CONST}.routeId, …)`.

Breakpoints are duplicated in `src/styles/abstracts/_variables.scss` (`$grid-breakpoints`) and
`src/js/utils/MediaBreakpoints.js` (`BREAKPOINTS`). **Change both together.**

## Dev / prod switching

`npm run dev` writes `if-untitled/hot` (contains `http://localhost:3000`); `inc/vite.php` sees it and prints
`@vite/client` + `src/main.js` script tags. Without it, the manifest is used. Never hard-code a dev toggle;
never commit `hot` or `dist/` (both ignored by the root `.gitignore`). Override in `wp-config.php` with `define('IF_UNTITLED_VITE_DEV', true|false);`.
An explicitly set `WP_ENVIRONMENT_TYPE=production` ignores the `hot` file (unset = still honoured, since WP defaults to production).

## General rules

- Prefix PHP functions, hooks, handles and text-domain with `if_untitled_` / `if-untitled`.
- Escape everything on output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`), translate user-facing strings.
- Prefer small, focused files that mirror existing ones; follow existing naming and formatting (4-space indent).
- Don't add npm dependencies without a clear reason; prefer native browser APIs and modern CSS
  (custom properties, `:is()/:where()`, logical properties like `margin-inline`, `clip-path`, `inset`).
- There is a **single `.gitignore` at the repo root**; don't add per-folder `.gitignore` files. WordPress core is tracked;
  `wp-config.php`, `.env*` (except `.env.example`), `wp-content/uploads/`, caches, `node_modules/`, `dist/`, `hot` are ignored.
- Keep `README.md` in the theme in sync when changing workflow, commands or structure.

Detailed, file-scoped rules live in `.github/instructions/*.instructions.md`.
Step-by-step recipes live in `.github/skills/*/SKILL.md`.
