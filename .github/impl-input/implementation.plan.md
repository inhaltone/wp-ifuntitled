# IF-Untitled — Implementation plan

Sources: `implementation.notes.md` (developer notes), `IF-Untitled_Website_Outline.docx` (client outline).

**Flow:** decisions → data model → backend → seed → front-end. Each step is done (and committed) before the next starts.

This file holds **project decisions only** (what to build). _How_ to build it — naming, file conventions, code
patterns — lives in the skills and instructions; follow them instead of repeating them here:

| Concern                                                    | Where                                                             |
| ---------------------------------------------------------- | ----------------------------------------------------------------- |
| Repo-wide principles (native first, single source, YAGNI…) | `.github/copilot-instructions.md` → _Content modelling_           |
| Template files, route ids, `page-templates/` naming        | skill `create-page-template`                                      |
| Post types, taxonomies, `with_front`, permalinks           | skill `register-custom-post-type`                                 |
| ACF naming, locations, return formats, rendering           | skill `create-acf-field-group`                                    |
| Archive filter bar (All + terms)                           | skill `create-archive-filter`                                     |
| Components (PHP part + SCSS + JS)                          | skill `create-theme-component`                                    |
| Build / verify                                             | skill `vite-build-verify`                                         |
| File-scoped rules                                          | `.github/instructions/{php,scss,javascript,vite}.instructions.md` |

## Principles (project-specific)

1. **Assume, don't block.** Every open question has a default; build with the default, change it if the answer differs.
2. **Prove the model before templates.** Data model → seed → front-end; no template is written against an untested model.

## Decisions (confirmed)

| Topic            | Decision                                                                                                                                                              |
| ---------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Journal          | Default WordPress **posts** (Posts page = Journal). A curated editorial space, not a blog/news feed — any cadence, manual order. Categories relabelled "Entry types". |
| Clients          | `client` post type (private) — relatable from projects; one curated list in Theme Options feeds the `clients` component.                                              |
| Team             | `team_member` post type — Studio team (no journal authors).                                                                                                           |
| Home             | Sortable sections, each pointing to any project / journal post / page, with optional text and link.                                                                   |
| Media            | One shared repeater `content_grid` (image/text rows on a 5-column grid) for projects and journal entries.                                                             |
| Contact          | No form. Google Map (Maps JavaScript API, custom marker per location) with the locations as text overlaid on the right.                                               |
| Language         | English only.                                                                                                                                                         |
| Navigation       | Native WP menus: header `primary_menu` (depth 2 — Studio / Advisory submenus to page anchors), footer `footer_menu` (depth 1).                                        |
| Project metadata | Exactly four fields: **Location, Client, Year, Status** (no size / typology / collaborators).                                                                         |

---

## 1. Data model

### 1.1 Content types

| Type             | Key                | Public URL                                              | Native fields used                                       |
| ---------------- | ------------------ | ------------------------------------------------------- | -------------------------------------------------------- |
| Project          | `project`          | `/projects/` · `/projects/{slug}/`                      | title, featured image, menu order                        |
| Project category | `project_category` | `/projects/category/{slug}/`                            | name, slug, description                                  |
| Journal entry    | `post`             | `/journal/` · `/journal/{slug}/`                        | title, date, status, excerpt, featured image, menu order |
| Entry type       | `category`         | `/journal/type/{slug}/`                                 | name, slug, description                                  |
| Tag              | `post_tag`         | `/journal/tag/{slug}/`                                  | name, slug                                               |
| Client           | `client`           | — (private)                                             | title = name, featured image = logo                      |
| Team member      | `team_member`      | — (private)                                             | title = name, featured image = portrait                  |
| Pages            | `page`             | `/`, `/studio/`, `/advisory/`, `/contact/`, `/journal/` | title                                                    |

Seeded terms — Project categories (in order): Mix Use Spaces · Urban Houses · Villas & Hospitality · Commercial & F&B ·
Art & Experience Design · Advisory Projects. Entry types (name — term description): Project notes — process, material detail, behind the work · Field dispatches —
from advisory assignments, workshops, site visits · Essays & positions — ideas, reflections, spatial thinking ·
References — books, places, precedents that matter to the studio · Publications.

### 1.1.1 Journal brief (client)

A curated, editorial space — not a blog or news feed. Published at any cadence; combines images, video and text in a
clean format. **Each entry:** lead image or video, title, short intro text, date; full entry on click.
**Entry types:** Project notes (process, material detail, behind the work) · Field dispatches (advisory assignments,
workshops, site visits) · Essays & positions (ideas, reflections, spatial thinking) · References (books, places,
precedents) · Publications. No strict categorisation required — tag filtering may be added over time; at launch the filter bar is All + 5 entry types (1.6).
**Order:** one manual drag & drop order (`menu_order`), date created (newest first) when not set.

### 1.2 Fields (ACF) — the complete list

Built with the `create-acf-field-group` skill (naming, locations, return formats). Project-specific list:

**Content grid** — `group_content_grid`, location _post type = project OR post_. Repeater `content_grid`: one row per grid line on a **5-column** grid. Each row has an image and/or a text (at least one):

| Sub-field   | Label     | Type                                                                                      | Req. |
| ----------- | --------- | ----------------------------------------------------------------------------------------- | ---- |
| `image`     | Image     | image (returns ID)                                                                        |      |
| `position`  | Position  | button group: `left` · `center` · `right` (default `left`)                                | ✓    |
| `size`      | Size      | button group: `big` (3 cols) · `small` (1 col) (default `big`)                            | ✓    |
| `text`      | Text      | textarea — independent text block in the same row (can stand without an image)            |      |
| `text_size` | Text size | select: `small` · `medium` · `large` (default `medium`; shown only when `text` is filled) |      |

Column spans (symmetric): big → left 1–3 · center 2–4 · right 3–5; small → left 1 · center 3 · right 5. Text takes
the remaining columns of the row (exact placement from the designs). Rendered by `components/content-grid.php`, shared by projects and journal entries.

**Project** — `group_project`. Native: title, featured image (cards, Home, prev/next). No intro, no video.
Body = `content_grid` (above).

| Field              | Type                                                                                                          | Req. |
| ------------------ | ------------------------------------------------------------------------------------------------------------- | ---- |
| `project_category` | taxonomy (radio, save + load terms — single category; native taxonomy box hidden with `meta_box_cb => false`) | ✓    |
| `project_metadata` | group — Location, Client, Year, Status (see below)                                                            |      |
| `project_text`     | wysiwyg — brief / approach / outcome ("read more")                                                            |      |

`project_metadata` (ACF group, one "Metadata" box in the editor; template: `get_field('project_metadata')['location']`):

| Sub-field  | Label    | Type                                                                                                                                                                                                      | Req. |
| ---------- | -------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---- |
| `location` | Location | text (e.g. "Athens, Greece")                                                                                                                                                                              |      |
| `client`   | Client   | post object → `client`, multiple, returns IDs                                                                                                                                                             |      |
| `year`     | Year     | number, 4 digits (min 1990, max 2100)                                                                                                                                                                     |      |
| `status`   | Status   | select (enum): `in_progress` In progress · `completed` Completed · `planned` Planned (default `completed`). Shown publicly in the metadata. Not the same as WP post status (Draft/Published = visibility) | ✓    |

**Journal entry** — `group_journal`. Native: title, date, excerpt (= short intro), featured image (= lead image),
category (= entry type), **tags** (native `post_tag`, `/journal/tag/{slug}/` — for future tag filtering).
ACF: `journal_video` (url, YouTube — if set the lead is the video, featured image = poster). Body = `content_grid`.
No authors.

**Client** — logo = featured image, name = title, `client_url` (url, optional).
**Team member** — `group_team_member`, location _Post Type = team_member_. Exactly four fields (no CV):

| Field | Source                | Type                                      | Req. |
| ----- | --------------------- | ----------------------------------------- | ---- |
| Image | native featured image | portrait, labelled "Portrait" in wp-admin | ✓    |
| Name  | native post title     | placeholder "Full name"                   | ✓    |
| Role  | ACF `team_role`       | text, max 80 (e.g. "Founding architect")  | ✓    |
| Bio   | ACF `team_bio`        | textarea, plain text, auto paragraphs     |      |

No drag order: the Studio page's `studio_team` field picks and orders members. Helper `if_untitled_team_member($post)` → `{name, image_id, role, bio}`.
**Term order** — no field: `project_category` and `category` terms are drag-ordered with the ordering plugin (`term_order`, see 1.6).

**Pages** (location = page template):

| Page     | Fields                                                                                                                                                                                                                                                                                                                                                                                                                                |
| -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Home     | `home_sections` flexible content — layouts `feature` (full-bleed) and `showcase` (preview card); each: `item` post object (project / post / page), `text` (optional override), `image` (optional override), `link_label`                                                                                                                                                                                                              |
| Studio   | `studio_features` repeater (no limit): `item` post object (project / post / page, returns ID), `title` text (falls back to the item title), `description` textarea · `studio_services` repeater: `title`, `subtitle` text, `description` textarea, `image` (optional, ID) — each row gets `id="{sanitize_title(title)}"` as anchor for the header submenu + footer · `studio_team` relationship → team_member (editor picks + orders) |
| Advisory | `advisory_features` repeater (no limit): `item` post object → **project only** (returns ID), `title` text (falls back to the project title), `subtitle` text, `description` textarea — each row gets `id="{sanitize_title(title)}"` as anchor for the header submenu (4 thematics at launch) · no team section                                                                                                                        |
| Contact  | No page fields — renders the Theme Options `locations` on the map.                                                                                                                                                                                                                                                                                                                                                                    |

**Theme Options** (tabs: Clients · Locations & footer · Archives):

| Field           | Type                                                                                                                                                                                                        |
| --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `clients`       | relationship → client, published only; picks which clients show and their order                                                                                                                             |
| `locations`     | repeater: `city` text · `address` textarea · `phone` text · `email` email · `map` ACF Google Map (lat/lng from address search). Used by Contact + footer; "Open in Google Maps" link generated from lat/lng |
| `enquiries`     | repeater: `label` text · `type` select `email` / `phone` · `value` text → `mailto:` / `tel:` link                                                                                                           |
| `social`        | repeater: `label` text · `url` url                                                                                                                                                                          |
| `portals`       | repeater: `label` text · `url` url                                                                                                                                                                          |
| `work_intro`    | textarea — Work archive intro                                                                                                                                                                               |
| `journal_intro` | textarea — Journal archive intro                                                                                                                                                                            |

Google Maps API key: `IF_UNTITLED_GOOGLE_MAPS_KEY` constant in `wp-config.php` (per environment, not in git, HTTP-referrer restricted); passed to ACF with `acf/fields/google_map/api` in `inc/acf.php` and to the front-end map via `data-*`.

### 1.3 Relationships

```
Project ── 1 category ── n clients
Journal entry ── 1 entry type ── n tags
Home section ── 1 item (Project | Journal entry | Page)
Studio ── n featured items (Project | Journal entry | Page) ── n services (anchors → header submenu + footer) ── n team members (picked order) ── clients component
Advisory ── n featured projects (anchors → header submenu) ── clients component
Primary menu (`primary_menu`) ── Studio › 2 service anchors · Advisory › 4 thematic anchors · Work · Journal · Contact
Theme Options ── n clients (the single list the clients component renders)
Theme Options ── n locations (Contact map + footer)
```

### 1.4 What each page shows

| Page / route                  | Sections → data (source)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Global                        | Header: logo (→ Home), **native WP menu** location `primary_menu` (Appearance → Menus, depth 2, top level numbered 01–05 with a CSS counter): Studio › Architecture & Interior Design · Experience Curation (custom links `/studio/#{anchor}`) · Advisory › the 4 featured thematics (custom links `/advisory/#{anchor}`) · Work · Journal · Contact (simple links); submenus open as an accessible disclosure (`button[aria-expanded]`, works on touch + keyboard) · **Footer** (every page): menu (location `footer_menu`, depth 1 — can be the same menu as the header, no field) · Studio links (generated from `studio_services` titles → `/studio/#{anchor}`) · addresses (`locations`) · enquiries · social · portals (Theme Options) |
| Home `front-page`             | `home_sections` in editor order; each row resolves title, image, URL, type label from its `item`; text = row `text` override, else excerpt (journal/page) — projects have no intro                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| Studio `studio`               | featured items (`studio_features`: item image + URL via `if_untitled_resolve_item()`, row title, description) · services (`studio_services`, anchored) · team (`studio_team` order: image, name, role, bio) · clients (component)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| Advisory `advisory`           | featured projects (`advisory_features`: project image + URL, row title, subtitle, description) · clients (component)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| Work `archive-project`        | `work_intro` · filter All + 6 categories · cards (featured image, title, metadata: location, client, year, status) — filter component shared with Journal (cards differ: `project-card` / `journal-card`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| Project `project`             | `content_grid` (5-column image/text rows) · title, category · "read more" `project_text` · metadata · previous / next (menu order, looping — custom helper, `get_adjacent_post()` only knows dates)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| Journal `journal`             | `journal_intro` · filter All + 5 entry types (same mechanism as Work) · **3-column grid** of cards (featured image — also for video entries, title, excerpt, date, entry type)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| Journal entry `journal-entry` | lead (image or video) · title, date, entry type, tags · `content_grid`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| Contact `contact`             | Full map: one custom marker per `locations` row (fit bounds); location text (city, address, phone, email, Google Maps link) overlaid right-aligned, `position: absolute`. Without JS / key the text list still shows                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |

### 1.5 Template files

File names and naming rules follow the `create-page-template` skill. Routes for this project:

| Route (`main#id`) | File                            | How WordPress picks it                                  | ACF location rule           |
| ----------------- | ------------------------------- | ------------------------------------------------------- | --------------------------- |
| `front-page`      | `front-page.php`                | Settings → Reading → static front page                  | Page Type = Front Page      |
| `journal`         | `home.php`                      | Settings → Reading → posts page ("Journal")             | — (intro in Theme Options)  |
| `journal`         | `category.php`                  | entry-type archive `/journal/type/{slug}/`              | —                           |
| `journal`         | `tag.php`                       | tag archive `/journal/tag/{slug}/` (no filter bar yet)  | —                           |
| `journal-entry`   | `single.php`                    | single post (journal entry)                             | Post Type = post            |
| `archive-project` | `archive-project.php`           | `archive-{post_type}.php`                               | —                           |
| `archive-project` | `taxonomy-project_category.php` | `taxonomy-{taxonomy}.php` (key with underscore)         | —                           |
| `project`         | `single-project.php`            | `single-{post_type}.php`                                | Post Type = project         |
| `studio`          | `page-templates/studio.php`     | assigned in Page → Template (`Template Name: Studio`)   | Page Template = Studio      |
| `advisory`        | `page-templates/advisory.php`   | assigned in Page → Template (`Template Name: Advisory`) | Page Template = Advisory    |
| `contact`         | `page-templates/contact.php`    | assigned in Page → Template (`Template Name: Contact`)  | — (data from Theme Options) |
| `page`            | `page.php`                      | any other page (privacy policy, legal)                  | —                           |
| `not-found`       | `404.php`                       | no match                                                | —                           |
| —                 | `index.php`                     | required final fallback                                 | —                           |

Project notes: no `single-client.php` / `single-team_member.php` (private post types); shared markup lives in
`components/` (`archive-filter`, `content-grid`, `project-card`, `journal-card`, `clients`, …). The `clients` component reads the Theme Options list and is placed in code on any template that needs it (Studio, Advisory today).

### 1.6 Archive filters

Implemented with the `create-archive-filter` skill (term-archive URLs, `if_untitled_archive_filter()`,
`components/archive-filter.php`, `aria-current`; term order = plugin `term_order` instead of the skill's `sort_order` field). Project values:

| Archive | "All" URL    | Term URL                     | Taxonomy                          | Templates (same markup via component)                  | Posts per page                                                     |
| ------- | ------------ | ---------------------------- | --------------------------------- | ------------------------------------------------------ | ------------------------------------------------------------------ |
| Work    | `/projects/` | `/projects/category/{slug}/` | `project_category` (6 categories) | `archive-project.php`, `taxonomy-project_category.php` | all (`-1`), menu order ASC, then date DESC                         |
| Journal | `/journal/`  | `/journal/type/{slug}/`      | `category` = Entry types (5)      | `home.php`, `category.php`                             | 12, menu order ASC then date DESC, infinite scroll over `/page/2/` |

- **Intro text:** "All" shows `work_intro` / `journal_intro`; a term view shows the term description, if set.

---

## 2. Backend

| Step | Work                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     | Done when                             |
| ---- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------- |
| 2.1  | Update ACF Pro to the current release (license). Install WP-CLI (`brew install wp-cli`). Install a free drag & drop order plugin (writes `menu_order`) for `project` and `post` + the `project_category` and `category` terms (clients and team are ordered by their relationship fields).                                                                                                                                                                                                                                                                                               | No ACF notices in `debug.log`         |
| 2.2  | `inc/post-types.php` (skill `register-custom-post-type`): `project`, `client`, `team_member`, `project_category`. Relabel Posts → Journal, Categories → Entry types. Disable comments. Block editor off for `post`, `project` and `team_member`. `add_post_type_support('post', 'page-attributes')`. `pre_get_posts` (main query; Work = project archive + `project_category`; Journal = `home` + `category` + `tag`): projects `-1`, journal 12; both `orderby => ['menu_order' => 'ASC', 'date' => 'DESC']` (new, unordered entries have `menu_order` 0 → appear first, newest first). | Types visible in wp-admin             |
| 2.3  | Settings: permalinks `/journal/%postname%/`, category base `type`; Home = front page, Journal = posts page.                                                                                                                                                                                                                                                                                                                                                                                                                                                                              | All URLs in 1.1 return 200            |
| 2.4  | `inc/setup.php`: image sizes (`card` 800w, `xl` 2400w — `full` is reserved by core for the original) — **before any upload**.                                                                                                                                                                                                                                                                                                                                                                                                                                                            | Sizes listed in `wp media image-size` |
| 2.5  | ACF field groups from 1.2 (skill `create-acf-field-group`). `inc/acf.php`: relationship / post object filters (published only); `acf/validate_value` so each `content_grid` row has an image or a text.                                                                                                                                                                                                                                                                                                                                                                                  | `acf-json/*.json` committed           |
| 2.6  | Keep docs in sync: if the code diverges from a skill/instruction (new rule, renamed helper), update that skill in the same commit — not this plan.                                                                                                                                                                                                                                                                                                                                                                                                                                       | Skills match code                     |

## 3. Seed (`bin/seed.sh`, WP-CLI, local only)

Terms (in order, with descriptions) · 33 project drafts from the outline (categories + menu order + metadata + a few `content_grid` rows) ·
clients · Annika + Martha · pages with templates (Studio features/services/team, Advisory features) · Theme Options
(clients list, 1–2 locations, enquiries, social, portals, intros) · reading settings + permalinks · `primary_menu` + `footer_menu` (5 top items + Studio / Advisory sub-items to the anchors) ·
3–5 back-dated journal posts (one with `journal_video`, tags) · placeholder images.
Proves the model end-to-end before any template is written. **Done when** every page in 1.4 has data.

## 4. Front-end

Each route = the file from **1.5** + `<main id="{route}">` + `Router` id in JS + `src/styles/pages/_{route}.scss`
(skill `create-page-template`).

Helpers first (`inc/template-tags.php`): `if_untitled_project_metadata()`, `if_untitled_team_member()`, `if_untitled_clients()`,
`if_untitled_adjacent_project()`, `if_untitled_youtube_id()`, `if_untitled_content_grid()`, `if_untitled_resolve_item()`, `if_untitled_archive_filter()`, `if_untitled_locations()`, `if_untitled_studio_services()` (shared by Studio + footer; finds the Studio page by its template `page-templates/studio.php`, not by ID/slug; anchors must be unique; anchored sections get `scroll-margin-top` for the header).
Card image = featured image on both archives (Work + Journal), never the video or a `content_grid` image; without a featured image the card renders without image.

Order: **global** (nav with submenu disclosure — `PrimaryNav` JS component, footer from Theme Options) → **shared components** (archive filter, content grid, YouTube player — click-to-load
`youtube-nocookie`, project card, journal card, clients) → **Work + Project** → **Studio** → **Advisory** →
**Journal** → **Contact** (`ContactMap` JS component, Maps JavaScript API loaded only on this route) → **Home** → **QA** (a11y, empty states, srcset, 404, Lighthouse).

Skills per step: routes → `create-page-template`; shared components → `create-theme-component`;
filters (Work + Journal, values in 1.6) → `create-archive-filter`; field reads → `create-acf-field-group` §5;
verification → `vite-build-verify`.

---

## Open questions

✅ answered · ⏳ still open (building with the stated default until answered).

| #   | Question                                                                   | Answer / default                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| --- | -------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | ✅ Project status vs publishing                                            | Visibility = native WP post status (Draft / Published). Project status = ACF enum `in_progress` · `completed` · `planned`, shown in metadata                                                                                                                                                                                                                                                                                                         |
| 2   | ✅ Prev / next project                                                     | All projects, by menu order (same order as Work "All"), looping first ↔ last                                                                                                                                                                                                                                                                                                                                                                         |
| 3   | ✅ Work card content                                                       | Featured image, title, `project_metadata` (location, client, year, status)                                                                                                                                                                                                                                                                                                                                                                           |
| 4   | ✅ Work "All" order                                                        | One manual drag & drop order via a free plugin (e.g. Simple Custom Post Order — writes native `menu_order`); unordered projects fall back to date created, newest first                                                                                                                                                                                                                                                                              |
| 5   | ✅ Clients on Studio / Advisory                                            | One list in Theme Options (`clients`); shared `clients` component placed in code per template. Item = logo (featured image) linking to `client_url` in a new tab; name = alt text / fallback without logo. No `client_type`                                                                                                                                                                                                                          |
| 6   | ✅ Team CV                                                                 | No CV; team member = image, name, role, bio                                                                                                                                                                                                                                                                                                                                                                                                          |
| 7   | ✅ Publications / References fields                                        | Same fields as every journal entry — entry types are only categories; no type-specific fields for now                                                                                                                                                                                                                                                                                                                                                |
| 8   | ✅ Journal pagination                                                      | Infinite scroll, 12 per batch, as progressive enhancement: native `/page/N/` links stay in the HTML (no-JS + SEO); JS (`IntersectionObserver` + `fetch` of the next page) appends cards and updates the URL with `history.replaceState`. Works per entry type. Footer must stay reachable — load on sentinel, stop at the last page                                                                                                                  |
| 9   | ✅ Journal "Archived" entries                                              | Native Draft / Private — hidden, no extra field. Also confirmed: filter bar All + 5 entry types at launch; cards show featured image, title, intro (excerpt), date, entry type (Q16)                                                                                                                                                                                                                                                                 |
| 10  | ⏳ Home: more layouts than feature / showcase? Video hero?                 | **Open — waiting for designs.** Build `feature` + `showcase`; add layouts when designs land                                                                                                                                                                                                                                                                                                                                                          |
| 11  | ✅ Footer / contact details                                                | Footer on every page, all content from Theme Options: menu (native), Studio service links, `locations`, `enquiries`, `social`, `portals`                                                                                                                                                                                                                                                                                                             |
| 12  | ✅ Contact map                                                             | ACF Google Map field per location (Theme Options `locations`) + Maps JavaScript API with a custom marker, lazy-loaded on the Contact route only; location text overlaid on the right. Needs `IF_UNTITLED_GOOGLE_MAPS_KEY`                                                                                                                                                                                                                            |
| 13  | ⏳ Cookie consent / privacy policy for YouTube and the Google Maps JS API? | **Open — discuss later.** Meanwhile YouTube is click-to-load (`youtube-nocookie`); keep embeds behind a single helper so consent can be added in one place                                                                                                                                                                                                                                                                                           |
| 14  | ✅ SEO plugin                                                              | The SEO Framework (lightweight) — install at the QA step                                                                                                                                                                                                                                                                                                                                                                                             |
| 15  | ⏳ `content_grid` text placement                                           | **Open — designs.** Default: text fills the free columns on the opposite side of the image (big-left → cols 4–5, big-right → 1–2, big-center → below the image); text-only rows span cols 2–4                                                                                                                                                                                                                                                        |
| 16  | ✅ Journal archive cards                                                   | Same filter mechanism as the Work archive (`create-archive-filter`); 3-column grid; card image = featured image (also when the entry leads with a video — the video plays only on the entry page). Work cards also use the featured image                                                                                                                                                                                                            |
| 17  | ✅ Header menu                                                             | Native WP menu (location `primary_menu`, depth 2): **Studio** › Architecture & Interior Design · Experience Curation (anchors of `studio_services`) · **Advisory** › the 4 featured thematics (anchors of `advisory_features`) · **Work** · **Journal** · **Contact** (simple links). Sub-items are custom links to page anchors, edited in Appearance → Menus; anchor ids = `sanitize_title(title)`, so renaming a row means updating the menu link |
