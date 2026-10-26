---
applyTo: "wp-content/themes/if-untitled/src/**/*.scss"
description: "SCSS architecture, BEM naming and mixin usage for the if-untitled theme"
---

# SCSS conventions

## Module system (mandatory)

- Use `@use` / `@forward` only. **Never `@import`** (removed in Dart Sass 3).
- Every partial that needs tokens/mixins starts with `@use 'abstracts' as *;`
  (`src/styles` is on Sass `loadPaths`, configured in `vite.config.js`).
- Use Sass built-in modules: `@use 'sass:math'; math.div()` (never `/` for division), `sass:map`, `sass:list`.
- Don't use the deprecated Sass `if()` function — use `@if/@else` blocks.
- Deprecations are **fatal** in the build (`fatalDeprecations` in `vite.config.js`).
- **No CSS framework.** Don't install Bootstrap/Tailwind or copy their CSS; extend our own `base/`, `layouts/`, `utilities/`.
- Linted by Stylelint (`npm run lint:css`, config `stylelint.config.js`) and formatted by Prettier (`npm run format`).

## Folder layout (`src/styles/`)

| Folder        | Purpose                                                                                                    | Outputs CSS?                      |
| ------------- | ---------------------------------------------------------------------------------------------------------- | --------------------------------- |
| `abstracts/`  | `_variables`, `_functions`, `_mixins` (forwarded by `_index.scss`)                                         | **No** — never add selectors here |
| `base/`       | fonts, `:root` custom properties, `_reset` (our minimal modern reset), typography                          | yes                               |
| `layouts/`    | `#app`, `.container` (max-width `$container-max-width`, `padding-inline: var(--gutter)`), `.single-layout` | yes                               |
| `components/` | one partial per `components/*.php`                                                                         | yes                               |
| `pages/`      | one partial per template, scoped by `main#{route-id}`                                                      | yes                               |
| `utilities/`  | helpers like `.screen-reader-text`, `[data-reveal]`                                                        | yes                               |

New partial `_foo.scss` → add `@use 'foo';` to that folder's `_index.scss`. `src/style.scss` only `@use`s folders.

## Naming — BEM

- `.block`, `.block__element`, `.block--modifier`. Nest with `&__element` / `&--modifier`; max ~3 levels deep.
- State classes set by JS: `.is-*` (`.is-entered`, `.is-revealed`) or the existing `.visible` / `.show`.
- Page partials scope with `main#{route-id} { … }`.

## Tokens & mixins (use them, don't hard-code)

- Colors: `$c_black`, `$c_white`, `$c_grey`, `$c_light`, `$c_accent` (snake_case with `c_` prefix).
  Runtime equivalents: `var(--c-accent)` etc. in `base/_root.scss`.
- Spacing: `var(--gutter)`, `var(--nav-height)`, `var(--footer-height)`.
- Type: `@include font-bold(24);` / `font-medium()` / `font-regular()` — size in px numbers, output in `em`.
  Weights come from `--font-bold|medium|regular`. Helpers `em()` / `rem()`.
- Fonts (`base/_fonts.scss`, `.woff2` only): body base = **GT America Medium (500)** set on `body` in `_reset`;
  bold = GT America 700 (`$base_font_family`, `--font-base`);
  display = GT Flexa 500 via `@include font-display(48);` (`$display_font_family`, `--font-display`).
  There is no 400 file, so `font-regular` renders with the 500 face. Add a new weight as a `.woff2` plus an `@font-face` block.
- Responsive: **mobile-first** with `@include media-breakpoint-up(lg) { … }` (≥ 992px).
  `media-breakpoint-down(md)` = below 768px; `-between(sm, lg)` = 576–991.98px; `-only(md)` = 768–991.98px.
  Breakpoints: xs 0, sm 576, md 768, lg 992, xl 1200, xxl 1400 (mirror in `MediaBreakpoints.js`).
- Motion: `@include transition(opacity .3s $ease-out);` (auto-disables for reduced motion). Easings `$ease-out`, `$ease-in-out`.
- Hover: `@include hover { … }` (only on hover-capable devices).
- Also: `@include visually-hidden;`, `@include hide-scrollbar;`.

## Style

- 4-space indent, one declaration per line, blank line between nested blocks (Prettier + Stylelint enforce this).
- Modern syntax: `rgb(19 19 19)` space notation, `clip-path` not `clip`, logical properties (`margin-inline`, `inset`), no vendor prefixes (Vite/Lightning CSS handles them).
- Selectors: BEM classes and kebab-case ids only (`selector-class-pattern` / `selector-id-pattern`).
- Order inside a rule: `@include` typography → positioning → box model → visuals → transitions → nested states/elements → media queries.
- Assets: `url('@/assets/fonts/x.woff2')` / `url('@/assets/images/x.svg')` — Vite resolves and fingerprints them.

## Skills (step-by-step recipes in `.github/skills/`)

| Task                                        | Skill                    |
| ------------------------------------------- | ------------------------ |
| New component partial (+ PHP part, JS)      | `create-theme-component` |
| New page partial `pages/_{route-id}.scss`   | `create-page-template`   |
| Sass build errors, missing styles, upgrades | `vite-build-verify`      |
