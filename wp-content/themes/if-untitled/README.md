# if-untitled

Custom WordPress theme powered by **Vite 8** + **SCSS (Dart Sass, `@use` modules)** + vanilla ES modules.
No CSS/JS framework: reset, layout, breakpoint mixins and utilities are our own (`src/styles/`).

## Requirements

- Node `^20.19 || >=22.12` (see `.nvmrc`), npm
- PHP ≥ 8.1, WordPress ≥ 6.5 (uses `wp_enqueue_script_module`)
- ACF Pro (bundled in `wp-content/plugins/advanced-custom-fields-pro`, activate it and update it from wp-admin)

## Getting started

```bash
cd wp-content/themes/if-untitled
npm install
cp .env.example .env.local   # set WP_URL to your local WordPress URL
npm run dev                  # Vite dev server on http://localhost:3000 (HMR + PHP live reload)
npm run build                # production build → dist/
npm run lint                 # ESLint + Stylelint + php -l
npm run format               # Prettier
npm run check                # format:check + lint + build (run before committing)
```

Activate **if-untitled** in _Appearance → Themes_. It is the only theme shipped in this repo; to stop WordPress core
updates from re-adding the default `twentytwenty*` themes, add this to `wp-config.php`:

```php
define('CORE_UPGRADE_SKIP_NEW_BUNDLED', true);
```

Then apply the site structure (permalinks, pages, reading settings, entry types, menu) with WP-CLI — idempotent, run
it on every environment:

```bash
wp eval-file bin/setup.php
```

Locally, fill the site with content from the curated CSVs in `.github/plans/phase-b/seeds/` (projects, terms, team,
images downloaded from the old site — a few minutes the first time). Idempotent: matched by slug, images reused:

```bash
bin/seed.sh            # or: bin/seed.sh /path/to/seeds
```

## How dev / prod switching works

| Mode        | Trigger                                             | What WordPress loads                                                            |
| ----------- | --------------------------------------------------- | ------------------------------------------------------------------------------- |
| Development | `npm run dev` writes a `hot` file in the theme root | `<script type="module">` for `@vite/client` + `src/main.js` from the dev server |
| Production  | no `hot` file                                       | hashed CSS/JS from `dist/.vite/manifest.json`                                   |

No manual `IS_VITE_DEVELOPMENT` toggle is needed. Force a mode in `wp-config.php` with
`define('IF_UNTITLED_VITE_DEV', false);` (or `true`). When `WP_ENVIRONMENT_TYPE` is **explicitly** set
to `production` (constant or env var) the `hot` file is ignored. WordPress defaults to `production` when nothing is
set, so a plain local install still picks up the dev server.

The custom plugin `vite-plugins/wordpress.js` writes/removes the `hot` file and triggers a full
reload whenever a `.php` file changes.

## Structure

```
if-untitled/
├── functions.php            # thin bootstrap → requires inc/*
├── inc/
│   ├── vite.php             # dev server / manifest loader
│   ├── setup.php            # theme supports, menus, head cleanup
│   ├── post-types.php       # CPTs, taxonomies, Journal labels, archive queries
│   ├── urls.php             # redirects, noindex, reserved slugs
│   ├── template-tags.php    # if_untitled_* helpers
│   └── acf.php              # optional ACF options page + acf-json
├── bin/
│   ├── setup.php            # WP-CLI: site structure (wp eval-file bin/setup.php)
│   ├── seed.sh / seed.php   # WP-CLI: local content seed from the CSVs
│   └── lib.php              # CSV / term helpers shared by both
├── components/              # template parts: if_untitled_component('x', $args)
├── page-templates/          # selectable page templates (Studio, Advisory, Contact)
├── *.php                    # template hierarchy files, each renders <main id="route-id">
├── src/
│   ├── main.js              # single Vite entry (imports style.scss)
│   ├── style.scss           # @use base, layouts, components, pages, utilities
│   ├── js/utils/            # Hooks, Router, MediaBreakpoints, RouterTransition, LazyComponent
│   ├── js/components/       # ES classes: new Component({ ...options })
│   ├── styles/abstracts/    # variables, functions, mixins (no CSS output)
│   ├── styles/{base,layouts,components,pages,utilities}/
│   └── assets/{fonts,images}
├── public/                  # copied as-is to dist/
├── vite.config.js
└── vite-plugins/wordpress.js
```

## Optional constants (`wp-config.php`)

| Constant                      | Purpose                                                                                                                                                        |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `IF_UNTITLED_VITE_DEV`        | force dev (`true`) or prod (`false`) asset loading                                                                                                             |
| `IF_UNTITLED_GOOGLE_MAPS_KEY` | overrides Theme Options → Google Maps → API key (Contact map + location picker)                                                                                |
| `IF_UNTITLED_GOOGLE_MAPS_ID`  | overrides Theme Options → Google Maps → Map ID (optional Google Cloud style; empty = dark black-and-white code style without place labels, in `ContactMap.js`) |

## Tooling

| File                  | Purpose                                                                       |
| --------------------- | ----------------------------------------------------------------------------- |
| `eslint.config.js`    | ESLint 10 flat config (browser globals for `src/`, node for configs)          |
| `stylelint.config.js` | `stylelint-config-standard-scss` + BEM class / kebab-case id rules            |
| `.prettierrc.json`    | 4-space, single quotes, 120 cols                                              |
| `vite.config.js`      | Sass deprecations are **fatal**; no modulepreload polyfill; no asset inlining |

Git ignores are defined once in the repository root `.gitignore` (`dist/`, `hot`, `node_modules/`, `.env*`…).

## Conventions (short)

- **PHP**: prefix functions `if_untitled_`, escape all output, templates render `<main id="…">`. Templates render,
  helpers in `inc/template-tags.php` fetch. Layout = the 5-column `.grid` (`.grid__item` + `--col-start` / `--col-span`).
- **SCSS**: BEM (`block__element--modifier`), one partial per component, every partial starts with
  `@use 'abstracts' as *;`, use `media-breakpoint-up()` + `text-style()` (type scale in `abstracts/_variables.scss`).
- Fonts: GT America (body, 500/700) and GT Flexa Expanded (display, 700; GT Flexa Medium 500 kept but unused) are self-hosted `.woff2` files in `src/assets/fonts`, registered in `base/_fonts.scss`.
- **JS**: one ES class per file with an options-object constructor and `main()`; wire it up in
  `main.js` via `onMounted` / `onPageMounted(Router.X.routeId, …)`; heavy components via `lazyComponent()`.

See [`/.github/copilot-instructions.md`](../../../.github/copilot-instructions.md) for full guidelines and agent skills.
