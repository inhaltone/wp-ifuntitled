---
name: vite-build-verify
description: Build, verify and troubleshoot the Vite + SCSS asset pipeline of the if-untitled theme (dev server, HMR, manifest, CORS, missing styles, Sass errors, upgrading Vite). Use when assets don't load, the build fails, styles are missing, or when upgrading dependencies.
---

# Build, verify & troubleshoot the asset pipeline

All commands run in `wp-content/themes/if-untitled/`.

## Verify a change
```bash
npm run format                     # Prettier (JS/SCSS/JSON/MD)
npm run check                      # format:check + lint:js + lint:css + lint:php + build
cat dist/.vite/manifest.json       # "src/main.js" entry with "file" and "css"
```

## Verify dev mode (non-interactive)
Start `npm run dev` in an **async/background** shell, then:
```bash
cat hot                                                    # → http://localhost:3000
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:3000/@vite/client   # 200
curl -s -D - -o /dev/null -H "Origin: $WP_URL" http://localhost:3000/src/main.js | grep -i access-control
```
Stop the server by PID (`lsof -ti tcp:3000 -sTCP:LISTEN` → `kill <pid>`); the `hot` file must disappear.

## Troubleshooting
| Symptom | Cause / fix |
|---|---|
| Site unstyled, no errors | No `hot` file and no `dist/` → run `npm run dev` or `npm run build`. |
| Site still loads dev scripts after stopping Vite | Stale `hot` file (crash) → delete `hot`. |
| CORS error in browser console | Set `WP_URL` in `.env.local` to the exact site origin; restart `npm run dev`. |
| Fonts/images 404 in dev | `server.origin` must match host/port; reference assets as `url('@/assets/…')`. |
| `Port 3000 is in use` | `strictPort` is on. Free the port or set `VITE_DEV_PORT` (also updates `hot`). |
| `Undefined mixin/variable` | Partial is missing `@use 'abstracts' as *;`. |
| Build fails with a Sass deprecation error | Deprecations are fatal: convert to `@use`, `@if` blocks, `math.div()`, `sass:map`/`sass:list` functions. |
| Stylelint `selector-class-pattern` | Rename to BEM `.block__element--modifier`. |
| Page stays white / overlay never fades | JS failed to load (check console / stale `hot`); the CSS failsafe hides `.loader` after 3s. |
| New partial has no effect | Not registered in its folder's `_index.scss`. |
| Prod 404 on chunks | `base` must stay `'./'` for builds; don't change `outDir` without updating `IF_UNTITLED_DIST`. |
| PHP edits don't reload | Only `.php` files inside the theme (Vite root, excluding `node_modules`) trigger a reload (`vite-plugins/wordpress.js`). |
| Dev scripts not printed although `hot` exists | `WP_ENVIRONMENT_TYPE` is explicitly `production`, or `IF_UNTITLED_VITE_DEV` is `false` in `wp-config.php`. |
| Clicking a link leaves a white overlay | Add `data-no-transition` to the link; file links (`.pdf`, `.zip`…), external, `target="_blank"` and wp-admin links are skipped automatically. |

## Upgrading
```bash
npm outdated
npm i -D vite@latest sass-embedded@latest eslint@latest @eslint/js@latest globals@latest stylelint@latest stylelint-config-standard-scss@latest prettier@latest
npm run check
```
Read the Vite migration guide (https://vite.dev/guide/migration) for majors; config keys such as
`build.rolldownOptions` and the manifest path (`dist/.vite/manifest.json`) are the usual breaking points.
Update `README.md` and `.github/instructions/vite.instructions.md` if anything changes.
