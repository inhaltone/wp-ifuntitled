---
applyTo: "wp-content/themes/if-untitled/src/**/*.js"
description: "Vanilla ES module component pattern, hooks and router conventions"
---

# JavaScript conventions

## Modules
- Native ES modules, no jQuery, no frameworks. Import with the `@` alias (`@/js/utils/Hooks.js`) and **always include the `.js` extension**.
- 4-space indent, single quotes, semicolons, `const`/`let`, `===`, arrow callbacks — enforced by ESLint
  (`eslint.config.js`, `npm run lint:js`) and Prettier (`npm run format`).
- One default-exported class per file in `src/js/components/` (PascalCase filename = class name).
  Pure helpers go in `src/js/utils/` as named exports.

## Component pattern
```js
export default class Accordion {
    className = 'is-open';                     // public config as class fields

    constructor({ selector = '.accordion', duration = 300 } = {}) {   // options object with defaults
        this.elements = document.querySelectorAll(selector);
        if (this.elements.length === 0) return; // guard: no markup → no-op
        this.duration = duration;
        this.main();
    }

    main() { /* attach listeners */ }          // entry method, always named main()
    #privateHelper() { }                        // private with #
}
```
- Constructors never throw when markup is missing — return early.
- Toggle state with classes (`.is-*`, `.visible`, `.show`) and keep ARIA (`aria-expanded`, `aria-current`) in sync. CSS owns the visuals.
- Prefer event delegation on `document` for many links/buttons.
- Singletons (one per page, e.g. `RouterTransition`) use a `static #instance`.

## Wiring in `src/main.js`
- Global components → inside `onMounted(() => { … })`.
- Page-specific → `onPageMounted(Router.ARCHIVE.routeId, ({ element }) => { … })`.
  Add new route ids to the `Router` enum in `src/js/utils/Router.js` — they must match `<main id>` in PHP.
- Things that need images/fonts loaded → `onLoaded()`.
- Heavy/rare components (sliders, maps, galleries) → code-split:
  `lazyComponent('[data-gallery]', () => import('@/js/components/Gallery.js'), { selector: '[data-gallery]' });`
- Breakpoint logic → `mediaBreakpointUp('lg')` / `mediaBreakpointDown('md')`; motion → `prefersReducedMotion()`.

## Progressive enhancement
- Content must be visible and usable without JS. JS-only initial states (hidden, offset) must be scoped to `html.js`
  (the class is added inline in `<head>` by `inc/setup.php`), e.g. `.js [data-reveal] { opacity: 0; }`.
- Links that must bypass the page transition get `data-no-transition`.

## Data from PHP
- Pass data via `data-*` attributes (read with `element.dataset`). For larger payloads print a
  `<script type="application/json" id="…">` in PHP and `JSON.parse` it. Avoid globals.

## Don'ts
- No `console.log` left in committed code (`no-console` lint rule; `console.warn/error` allowed).
- No inline `style` manipulation for things a class can do.
- No UI frameworks/plugins (Bootstrap JS, jQuery, Swiper…) unless the user explicitly asks; build small classes instead.
- Don't import CSS from components except a third-party library's own CSS when one has been explicitly approved.

## Skills (step-by-step recipes in `.github/skills/`)
| Task | Skill |
|------|-------|
| New interactive component (+ PHP part, SCSS) | `create-theme-component` |
| Page-scoped JS / new `Router` id | `create-page-template` |
| Archive filters (no JS required) | `create-archive-filter` |
| Images / lazy loading (native, no JS) | `render-responsive-image` |
| Video / YouTube players | `render-video` |
| Dev server / HMR / chunk problems | `vite-build-verify` |
