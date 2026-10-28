# IF-Untitled — Phase B plan: PHP templates end to end

Source: `designs/*.png` (Adobe XD export, desktop 1920 px): Home · Studio · Advisory · Work-Archive · Work-Single ·
Journal-Archive · Contact. Data model, URLs, fields and backend live in
[`../implementation/implementation.plan.md`](../implementation/implementation.plan.md) — this plan only says **how each
route looks and is built**. Where the designs change the model, the change is listed in **§5** and back-ported to the
implementation plan once confirmed (that plan stays the single source for fields).

How-to rules (file names, BEM, components, Router ids) come from the skills — see the table at the top of the
implementation plan.

**When:** Phase B starts after Phase A (implementation plan §2 backend + §3 seed) is done — templates are built against
seeded data only.

**Status:** draft from the first design read — items marked ⏳ wait for an answer (§7); build with the stated default.

---

## 1. Design system (tokens)

Measured on the 1920 px artboards. Tokens go to `src/styles/abstracts/_variables.scss` (+ CSS custom properties).

| Token        | Value                                                                                                                                                                                                     |
| ------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Grid         | **5 columns**, gutter **10 px** (`--grid-gutter`, every layout grid), no outer margin — images bleed to the viewport edge (column = 376 px at 1920)                                                       |
| Text inset   | 12 px from a column edge (measured 10–20; text never touches the viewport edge)                                                                                                                           |
| Archive grid | **3 columns**, same 10 px gutter (Work + Journal)                                                                                                                                                         |
| Colours      | `#000` / `#fff` only; map greys come from the map style. Thin outline `1px` mid-grey (outlined text box)                                                                                                  |
| Themes       | **light** (default: white bg, black text) · **dark** (black bg, white text) — Journal archive and Contact are dark                                                                                        |
| Typography   | **Display**: GT Flexa Expanded Bold (headings, statement boxes) · **Text**: GT America Standard Medium / Bold (body, metadata). Files already in `src/assets/fonts/` — **Trial** versions (licence ⏳ Q1) |
| Type scale   | display XL (page titles "Studio", "Work") · display L (section titles) · display M (statement boxes, card titles) · body · small (metadata labels, footer)                                                |

Layout primitive: one `.grid` utility (5 col) with `--col-start` / `--col-span` custom properties, reused by every
image/text row (Home, Project, Studio, Advisory). Below the tablet breakpoint every row is full width, stacked
(⏳ Q8 — no mobile designs).

## 2. Global

| Part   | Design                                                                                                                                                                                                                                                                                                      | Build                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| ------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Header | Thin bar, 5 items (Studio · Advisory · Work · Journal · Contact) each at the start of a grid column, small text, no 01–05 numbers. **Submenu:** on hover of Studio / Advisory a simple list of the sub-items appears directly below the parent (same small text, no panel)                                  | `components/nav-bar.php`, `primary_menu` depth 2. CSS `:hover` + `:focus-within` opens it (keyboard); `PrimaryNav` adds `aria-expanded` and tap-to-open on touch (first tap opens, second follows the link)                                                                                                                                                                                                                                            |
| Logo   | `logo.svg` ("IF-UNTITLED ARCHITECTS", 2 lines, 366 × 84 viewBox, one path) **always visible**: `position: fixed` at the bottom-left of the viewport on every page, over the content while scrolling                                                                                                         | Source `.github/plans/phase-b/logo/logo.svg` → inlined in `components/site-logo.php` (fill `currentColor`, drop `id` / `data-name`, `role="img"` + `<title>`), linked to Home; Replaces the text logo in `nav-bar.php`. Colour: white + `mix-blend-mode: difference` so it reads on white, black and photos without JS; `z-index` above content, below the open submenu; only the logo itself is clickable. Smaller size below `md` (no mobile design) |
| Footer | 5 columns: **Menu** (footer menu) · **Studio** (Architecture · Urban Design · Interior Design) · **Address** (London lines, then "Athens" + lines) · **Enquiries** (email, WhatsApp) · **Social** + **Portals** stacked in col 5 · centred "©{year} IF_UNTITLED. All rights reserved." · dark on dark pages | `components/site-footer.php`, data from Theme Options; Studio column ⏳ Q4                                                                                                                                                                                                                                                                                                                                                                             |

## 3. Routes

Each route = template file (implementation plan §1.5) + `main#{route}` + `Router` id + `src/styles/pages/_{route}.scss`.

### 3.1 Home `front-page` (light)

- **Hero:** full-bleed image (viewport height), logo over it.
- **Rows** on the 5-column grid — the same visual language as the project grid: images of span 2–4 at any start column,
  next to text. Text kinds seen: **title** (display L, e.g. "Studio", "Urban Advisory", "Filmakers House", "Housing",
  some with a short paragraph), **outlined box** (statement in display M, 1px border), **filled box** (black, white
  display text), **plain paragraph**.
- Rows link to Studio / Advisory / a project (the title is the link).
- ⇒ Home is a **content grid with links** — closes implementation-plan Q10 (§5 change 2).

### 3.2 Studio `studio` (light)

| Section  | Design                                                                                                                                          | Data                                               |
| -------- | ----------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------- |
| Intro    | "Studio" display XL + bold paragraph, cols 1–2                                                                                                  | page title + content (§5 change 3)                 |
| Features | Rows: title (display L) + paragraph in cols 1–2, image right (cols 4–5 or 3–5): Approach · Architecture & Interior Design · Experience Curation | `studio_features` (title, description, item image) |
| Services | Label "Services" col 1, two **empty outlined boxes** cols 2–3 / 4–5                                                                             | `studio_services` — box content ⏳ Q5              |
| Team     | Label "Team" col 1, members in cols 2–3 / 4–5: landscape image, heading "Name + role" (one line), small bio                                     | `studio_team` (image, name, role, bio)             |
| Clients  | Label "Clients" col 1, logos in a row (natural size, cols 2–5)                                                                                  | `clients` component                                |

Pattern "**section label in col 1, content in cols 2–5**" → one `section-head` modifier, reused on Advisory.

### 3.3 Advisory `advisory` (light)

- **Intro:** "Advisory" display XL + bold paragraph, cols 2–3 (page title + content).
- **4 thematics** (anchors for the header submenu): left cols 1–2 — title (display L), subtitle (bold), description;
  pinned to the bottom of the row: **project title + metadata** (Location · Client · Year · Status, small). Right: project
  image, cols 3–5 or 4–5 (alternating span). Data: `advisory_features` + the linked project's featured image and
  `project_metadata`.
- **Clients** (same component as Studio).

### 3.4 Work archive `archive-project` (light)

- **Filter:** vertical list top-left (col 1): "All Projects" (bold) · "Filter" label · 6 categories; active = bold with
  "→" prefix. Same `archive-filter` component, vertical modifier.
- **Intro:** "Work" display XL + paragraph, cols 3–4 (`work_intro` / term description).
- **Cards:** 3 columns, image cropped to **2:3 portrait** (same as Journal cards; landscape images are centre-cropped), title
  (display M), metadata as 4 labelled columns: Location · Client · Year · Status (empty value = empty cell).

### 3.5 Project `project` (light)

- **Hero:** featured image cols 2–5; col 1 = title (display L) + metadata as a label/value list (Location, Client,
  Year, Status), vertically centred.
- **Grid:** `content_grid` rows — measured spans: cols 4–5, 1–4, 3–4, 3–5, 4–5, 2–3 + filled box 4–5, 3–5. Spans 2, 3 and 4
  at many start columns ⇒ the model's big/small × left/center/right is **not enough** (§5 change 1).
- **Statement box:** black box with white display M text next to an image (same "filled box" as Home).
- **Text:** `project_text` as plain paragraphs, cols 2–4, after the grid — shown open (no "read more" toggle in the design ⏳ Q6).
- **Category:** rendered below the metadata (decided — not in the design yet).
- **Not in the design:** previous / next ⏳ Q6.

### 3.6 Journal archive `journal` (dark)

- **Intro:** "Journal" display XL + paragraph in cols 4–5 (no vertical rule); black area above the grid.
- **Cards:** 3 columns, portrait images (2:3 — measured 629 × 979, cropped to a fixed ratio), title (bold), excerpt (small), date ("June 2026").
- **Filter:** entry types, vertical like Work (not in the design yet — Q7 ✅ keep); cards also show the entry type.
- Infinite scroll as in the implementation plan (Q8 there).

### 3.7 Contact `contact` (dark)

- **Map** fills the first viewport, **dark map style** (greys, labels muted), logo white bottom-left.
- **Panel** in col 5 over the map: "Contact" display L, intro paragraph, then the locations (address lines, phone,
  email; second block prefixed by the city). No markers visible in the export ⏳ Q9.
- Footer dark, directly under the map.
- Build: `ContactMap` with a code style (dark greys, no country / city / place labels, only road names) and dot overlay markers — no Map ID needed; an optional custom Map ID switches to its Google Cloud style (advanced markers).

### 3.8 Not designed (build from the system, review later)

Journal entry `journal-entry` · `page` (privacy) · `404` · header submenu open state · hover / focus states · tablet
and mobile.

## 4. Components (build order)

| #   | Component (`components/` + `_{name}.scss` + JS)        | Used by                                    | JS                                             |
| --- | ------------------------------------------------------ | ------------------------------------------ | ---------------------------------------------- |
| 1   | tokens, fonts, `.grid` utility, themes (`is-dark`)     | all                                        | —                                              |
| 2   | `nav-bar` (+ submenu), `site-logo`, `site-footer`      | all                                        | `PrimaryNav` (submenu: `aria-expanded`, touch) |
| 2b  | `image` (skill `render-responsive-image`)              | every image                                | — (native lazy loading)                        |
| 3   | `page-intro` (display XL title + paragraph, col start) | Studio, Advisory, Work, Journal, Contact   | —                                              |
| 4   | `content-grid` (image / title / text / box rows)       | Project, Journal entry, Home               | —                                              |
| 5   | `project-meta` (list + 4-column variants)              | Project hero, Work card, Advisory thematic | —                                              |
| 6   | `archive-filter` (vertical)                            | Work, Journal                              | —                                              |
| 7   | `project-card`, `journal-card`                         | Work, Journal                              | `InfiniteScroll` (Journal)                     |
| 8   | `section-head` (label col 1), `feature-row`            | Studio, Advisory                           | —                                              |
| 9   | `team`, `clients`                                      | Studio, Advisory                           | —                                              |
| 10  | `contact-map`                                          | Contact                                    | `ContactMap` (lazy, contact route only)        |
| 11  | `media` — MP4 loop / YouTube click (`render-video`)    | `content_grid` image blocks                | `AmbientVideo` · `YoutubeVideo` (lazy)         |

**This is the single Phase B build order.** Before #1: the helpers in implementation plan §4. Components in the
table order, then routes: Project → Work → Studio → Advisory → Journal → Journal entry → Contact (Maps JS API loaded
only on this route) → Home → 404 / page → QA (responsive, a11y, empty states, images per `render-responsive-image` §5,
Lighthouse). **Done when** each route matches its PNG at 1920 px with seeded data and
`npm run check` passes.

**Status (2026-10-28):** ✅ components #1–#11 · ✅ all routes (Project → 404 / page, plus search via `index.php`) ·
✅ QA: responsive (390 px), one `<h1>` / `<main>` per route, alt on every image, one priority image per route, rest lazy,
unknown URLs → 404, Lighthouse performance 99–100 / accessibility 100. ✅ Home PNG comparison (8 seeded rows; content grid `large` = 30 px and `display` =
38 px at 1920, centred display boxes keep ragged-left lines) · ⏳ PNG comparison for Advisory · ✅ live map (Theme Options key). Details: implementation plan §4.1.

## 5. Model changes (✅ confirmed, back-ported to the implementation plan)

| #   | Change                                                                                                                                                                                                                                                                                                                                         | Why (design)                                              |
| --- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------- |
| 1   | `content_grid` row = **`image`** (+ column range 1–5) and/or **`text`** (+ column range, `heading` / `body` + size, `box` none · outlined · filled, `align_x` / `align_y`). Replaces `position` / `size` / `text_size`. Built as **flexible content** (layouts `image` · `text` · `image_text`, column range select) — implementation plan 1.2 | Project + Home use spans 2–4 at any column; 4 text styles |
| 2   | Home = hero image + the same `content_grid` with an optional **`link`** (post object: any public content) per block. Drops `home_sections` / feature / showcase                                                                                                                                                                                | Home is the project grid language with links (closes Q10) |
| 3   | Page intro for Studio · Advisory · Contact: native page **title + content** (classic editor, plain paragraphs) — no new field                                                                                                                                                                                                                  | All three open with title + paragraph                     |
| 4   | Advisory service area shows its showcase project's title + `project_metadata` + excerpt — no new field (from `item`)                                                                                                                                                                                                                           | Advisory rows                                             |

## 6. Assets

- Design PNGs (~50 MB) are **not** in git (root `.gitignore`); share them outside the repo.
- Fonts: self-hosted `woff2` from `src/assets/fonts/` (`@font-face`, `font-display: swap`, preload the 2 main weights); drop the `.otf` copies from the build.
- Logo: `logo/logo.svg` (in git — small) is the source; the theme inlines it (Global → Logo).

## 7. Open questions

| #   | Question                                                                                       | Default until answered                                                                                                                                                                                                          |
| --- | ---------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Font licences: GT Flexa + GT America are **Trial** files — buy web licences before launch?     | Build with the trial `woff2`; swap files (same family names) when licensed — launch blocker                                                                                                                                     |
| 2   | ✅ Logo position                                                                               | Always visible — fixed bottom-left of the viewport on every page (blend-mode for contrast)                                                                                                                                      |
| 3   | ✅ Header submenu                                                                              | Shown on hover of the parent, simple list directly below it (no panel); no 01–05 numbers. Keyboard via `:focus-within`, touch via `PrimaryNav`                                                                                  |
| 4   | Footer "Studio" column = Architecture · Urban Design · Interior Design ≠ the 2 Studio services | Generated from `studio_services` (single source); designer to align the labels                                                                                                                                                  |
| 5   | Studio "Services": two empty boxes — what goes inside (title, subtitle, description, image)?   | Outlined box: title (display M) + description                                                                                                                                                                                   |
| 6   | Project: "read more" toggle, prev/next and category are not in the design — drop or keep?      | Keep prev/next (small, after the text); text shown open. Category ✅ rendered below the metadata                                                                                                                                |
| 7   | ✅ Journal filter                                                                              | Keep the entry-type filter (vertical, like Work) — the design gets updated; cards show the entry type                                                                                                                           |
| 8   | Mobile / tablet designs?                                                                       | Stack all grid rows full width under `md`; archives 2 cols `md`, 1 col `sm`. Below `md`: "Menu" top right → full-screen panel (submenus listed), filters collapse behind "Filter +", intros natural height (title, then filter) |
| 9   | Contact map: markers visible? Which city centred?                                              | Custom marker per location, fit bounds                                                                                                                                                                                          |
| 10  | ✅ Design PNGs in git                                                                          | No — `.github/plans/*/designs/` is ignored in the root `.gitignore` (keep the originals outside git)                                                                                                                            |
