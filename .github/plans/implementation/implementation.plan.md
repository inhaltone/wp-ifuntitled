# IF-Untitled — Implementation plan

Source: `IF-Untitled_Website_Outline.docx` (client outline, May 2026 — copy texts for the seed from it). Where they differ, this plan wins.

**Flow:** decisions → data model → backend → seed → front-end. Each step is done (and committed) before the next starts.

| Phase | Scope                                              | Plan                                               | Done when                                             |
| ----- | -------------------------------------------------- | -------------------------------------------------- | ----------------------------------------------------- |
| **A** | Backend (§2) → seed (§3) — no templates            | this file                                          | every page in 1.4 has data from the CSVs              |
| **B** | Front-end (§4): helpers → components → routes → QA | [`phase-b.plan.md`](../phase-b/phase-b.plan.md) §4 | each route matches its design; `npm run check` passes |

The seed CSVs live in `phase-b/seeds/` (with the designs) but are used in Phase A.

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
| Images (sizes, srcset, lazy load, LCP)                     | skill `render-responsive-image`                                   |
| Video / YouTube (image variant in `content_grid`)          | skill `render-video`                                              |
| Build / verify                                             | skill `vite-build-verify`                                         |
| File-scoped rules                                          | `.github/instructions/{php,scss,javascript,vite}.instructions.md` |

## Principles (project-specific)

1. **Assume, don't block.** Every open question has a default; build with the default, change it if the answer differs.
2. **Prove the model before templates.** Data model → seed → front-end; no template is written against an untested model.

## Decisions (confirmed)

| Topic            | Decision                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| ---------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Journal          | Default WordPress **posts** (Posts page = Journal). A curated editorial space, not a blog/news feed — any cadence, manual order. Categories relabelled "Entry types".                                                                                                                                                                                                                                                                                                                                           |
| Clients          | `client` post type (private) — relatable from projects; one curated list in Theme Options feeds the `clients` component.                                                                                                                                                                                                                                                                                                                                                                                        |
| Team             | `team_member` post type — Studio team (no journal authors).                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| Home             | Hero (front page featured image + small description = page content) + the shared `content_grid`; each block may link to any public content (internal links rule).                                                                                                                                                                                                                                                                                                                                               |
| Media            | One shared flexible content `content_grid` (layouts image / text / image + text on a 5-column grid; text with heading / body, box and alignment, placed like the image) for projects, journal entries and Home.                                                                                                                                                                                                                                                                                                 |
| Contact          | No form. Google Map (Maps JavaScript API, custom marker per location) with the locations as text overlaid on the right.                                                                                                                                                                                                                                                                                                                                                                                         |
| Language         | English only.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| Navigation       | Native WP menus: header `primary_menu` (depth 2 — Studio / Advisory submenus to page anchors), footer `footer_menu` (depth 1).                                                                                                                                                                                                                                                                                                                                                                                  |
| Internal links   | **Anything on the site is related with an ACF post object** (one item) or relationship (ordered list), returning IDs — never a typed URL. Default scope = every public post type (`project`, `post`, `page`, future public CPTs), published only; resolved by one helper `if_untitled_resolve_item()` (URL, title, featured image, excerpt). Narrower scope only where the content demands it (Advisory → Advisory Projects, client, team). Typed URLs only for external sites (`client_url`, social, portals). |
| Project metadata | Exactly four fields: **Location, Client, Year, Status** (no size / typology / collaborators). Location is picked from one shared list (private `project_location` taxonomy: **country › city**, searchable select), always rendered **"City, Country"** ("Mykonos, Greece").                                                                                                                                                                                                                                    |

---

## 1. Data model

### 1.1 Content types

| Type             | Key                | Public URL                                              | Native fields used                                                                                           |
| ---------------- | ------------------ | ------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| Project          | `project`          | `/work/` · `/work/{slug}/`                              | title, featured image, excerpt, menu order                                                                   |
| Project category | `project_category` | `/work/category/{slug}/`                                | name, slug, description                                                                                      |
| Journal entry    | `post`             | `/journal/` · `/journal/{slug}/`                        | title, date, status, excerpt, featured image, menu order                                                     |
| Entry type       | `category`         | `/journal/category/{slug}/`                             | name, slug, description                                                                                      |
| Tag              | `post_tag`         | `/journal/tag/{slug}/`                                  | name, slug                                                                                                   |
| Project location | `project_location` | — (private)                                             | hierarchical: country (parent, short name: Greece · UK · USA) › city (child); rendered "City, Country"       |
| Client           | `client`           | — (private)                                             | title = name, featured image = logo                                                                          |
| Team member      | `team_member`      | — (private)                                             | title = name, featured image = portrait                                                                      |
| Pages            | `page`             | `/`, `/studio/`, `/advisory/`, `/contact/`, `/journal/` | title, content (= Home hero description; intro on Studio / Advisory / Contact), featured image (= Home hero) |

Seeded terms — Project categories (in order): Mixed-Use Spaces · Urban Houses · Villas & Hospitality · Commercial & F&B ·
Art & Experience Design · Advisory Projects · Unclassified (holding category for projects not yet classified — shown in the filter only while it has projects). All seed terms come from `seeds/*.csv` (§3). Entry types (name — term description): Project notes — process, material detail, behind the work · Field dispatches —
from advisory assignments, workshops, site visits · Essays & positions — ideas, reflections, spatial thinking ·
References — books, places, precedents that matter to the studio · Publications. Project locations: seeded from `seeds/locations.csv` (§3); editors add new ones from the field.

### 1.1.1 Journal brief (client)

A curated, editorial space — not a blog or news feed. Published at any cadence; combines images, video and text in a
clean format. **Each entry:** lead image, title, short intro text, date; full entry on click.
**Entry types:** Project notes (process, material detail, behind the work) · Field dispatches (advisory assignments,
workshops, site visits) · Essays & positions (ideas, reflections, spatial thinking) · References (books, places,
precedents) · Publications. No strict categorisation required — tag filtering may be added over time; at launch the filter bar is All + 5 entry types (1.6).
**Order:** one manual drag & drop order (`menu_order`), date created (newest first) when not set.

### 1.2 Fields (ACF) — the complete list

Built with the `create-acf-field-group` skill (naming, locations, return formats). Project-specific list:

**Content grid** — `group_content_grid`, location _post type = project OR post OR page type = front page_. **Flexible
content** `content_grid` — the same field drives **Home**, projects and journal entries. One layout = one row on the
**5-column** grid; each layout only shows the fields it needs.

| Layout       | Blocks       | Example (Home design)                                                                     |
| ------------ | ------------ | ----------------------------------------------------------------------------------------- |
| `image`      | image        | large image cols 2–5                                                                      |
| `text`       | text         | a statement on its own                                                                    |
| `image_text` | image + text | "Studio" title + paragraph cols 1–2 · image cols 3–5; image cols 2–3 · black box cols 4–5 |

**Image block:** `media_type` (`image` · `video` · `youtube`) · `image` (ID, req. — the image, or the video's poster) ·
`video` (MP4 file, req. if video) · `youtube_url` (req. + valid if youtube) · `image_columns`. **Video exists only
here** — a variant of the image (skill `render-video`); cards, heroes and journal leads are always images.

What the visitor sees — every variant first shows the poster (`image`), which keeps the layout stable and is the
fallback without JS:

| Editor picks         | Use for                                       | Visitor sees                                                                                                        | Limits                                                                                           |
| -------------------- | --------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| `video` (MP4 upload) | silent loops (atmosphere, process, materials) | poster → video fades in when scrolled into view; muted, looping, no controls; pauses off-screen; small pause button | MP4 H.264, no audio, 10–30 s, < 10 MB; upload limit (Q24)                                        |
| `youtube` (URL)      | films with sound (interviews, project films)  | poster (16:9) + play button → click → YouTube's own player with sound and controls; focus moves to it               | nothing loads from YouTube before the click; iPhone may need a second tap; no-JS → opens YouTube |

**YouTube never autoplays** (decided): an autoplaying YouTube can't hide YouTube's branding, is heavy and contacts
YouTube without consent — silent loops are always an MP4 upload. Reduced motion (OS setting): `video` stays on the
poster until the visitor presses play.

**Text block** — placed like the image, and every look in the design is a combination of independent options:

| Sub-field      | Type                                                                                               | Default |
| -------------- | -------------------------------------------------------------------------------------------------- | ------- |
| `heading`      | text — display title ("Studio", "Urban Advisory", "Housing")                                       |         |
| `body`         | textarea — paragraph (heading and/or body, at least one)                                           |         |
| `body_size`    | button group: `small` (regular paragraph) · `large` (bold intro paragraph) · `display` (statement) | `small` |
| `box`          | button group: `none` · `outlined` (1px border) · `filled` (black, white text)                      | `none`  |
| `text_columns` | column range (below)                                                                               | `4-5`   |
| `align_x`      | button group: `start` · `center` · `end` — text inside its columns                                 | `start` |
| `align_y`      | button group: `top` · `center` · `bottom` — text inside the row height                             | `top`   |

Design check: "Studio" = heading + large body, top · "Urban Advisory" / "Filmakers House" / "Housing" = heading only,
centre (end / start / centre) · outlined statement = display body + outlined · outlined paragraph = small body + outlined ·
black box = display body + filled.

**Shared:**

- Column range (`image_columns`, `text_columns`) — **select of the 15 valid ranges** (`1-1` … `5-5`, label
  "Columns 2–4"), so an out-of-grid placement can't be entered. Image default `1-3`.
- `link` — on every layout, post object → any public content (Internal links), returns ID, optional — makes the heading and image a
  link (Home rows → Studio / Advisory / a project). Exception: a `youtube` block is never wrapped in the link (no
  link around its play button) — only the heading links (skill `render-video`).
- Boxes stretch to the row height (outlined / filled boxes match the image next to them).

Rules: in `image_text` the two ranges must not overlap (`acf/validate_value` on `content_grid`), plus
heading or body required (ACF can't express "one of two"). Rendered by `components/content-grid.php` (one partial per
block) with `--col-start` / `--col-span` custom properties parsed from the range and modifier classes for the options
(`.content-grid__text--filled`, `--align-x-end`, …). Blocks are output in column order, so below `md` every row stacks
full width in reading order. The image block and the text block are each defined once — inactive groups
`group_content_grid_image` / `group_content_grid_text` — and cloned (seamless) into the layouts, so `get_field()`
returns plain sub-field names (`heading`, `image_columns`, …).

**Project** — `group_project`. Native: title, featured image (cards, Home, prev/next), excerpt (= 2–3 line short
description, shown on the Advisory showcase — Q19). No video.
Body = `content_grid` (above).

| Field              | Type                                                                                                                                                                                        | Req. |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---- |
| `project_category` | taxonomy (radio, save + load terms — single category; native taxonomy box hidden with `meta_box_cb => false`)                                                                               | ✓    |
| `project_metadata` | group — Location, Client, Year, Status (see below)                                                                                                                                          |      |
| `project_specs`    | group — Date (text), Size (number, m²), Typology (text), Collaborators (text); project page hero only, below the metadata (`if_untitled_project_specs()`, `project-meta` `'specs' => true`) |      |
| `project_text`     | wysiwyg — brief / approach / outcome (shown open, no toggle)                                                                                                                                |      |

**"Metadata" everywhere in this plan = these four, always in this order: Location · Client · Year · Status.**

`project_metadata` (ACF group, one "Metadata" box in the editor; templates read it only through `if_untitled_project_metadata()`):

| Sub-field  | Label    | Type                                                                                                                                                                                                                                                                                                                              | Req. |
| ---------- | -------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---- |
| `location` | Location | taxonomy → `project_location`, select (stylised UI = search), single, _Create Terms_ (with parent) + _Save Terms_ + _Load Terms_ on, returns ID. Pick a **city** (a child term; validated). Rendered "{city}, {country}" by `if_untitled_project_metadata()` — never typed. Countries use the short common name (Greece, UK, USA) |      |
| `client`   | Client   | post object → `client`, multiple, returns IDs                                                                                                                                                                                                                                                                                     |      |
| `year`     | Year     | number, 4 digits (min 1990, max 2100)                                                                                                                                                                                                                                                                                             |      |
| `status`   | Status   | select (enum): `in_progress` In progress · `completed` Completed · `planned` Planned (default `completed`). Shown publicly in the metadata. Not the same as WP post status (Draft/Published = visibility)                                                                                                                         | ✓    |

**Journal entry** — no field group of its own. Native: title, date, excerpt (= short intro), featured image (= lead image),
category (= entry type), **tags** (native `post_tag`, `/journal/tag/{slug}/` — for future tag filtering).
The lead is the featured image; videos go in the body. Body = `content_grid` (its location rule covers `post`).
No authors.

**Client** — logo = featured image, name = title, `client_url` (url, optional).
**Team member** — `group_team_member`, location _Post Type = team_member_. Exactly four fields (no CV):

| Field | Source                | Type                                                                                        | Req. |
| ----- | --------------------- | ------------------------------------------------------------------------------------------- | ---- |
| Image | native featured image | portrait, labelled "Portrait" in wp-admin                                                   | ✓    |
| Name  | native post title     | placeholder "Full name"                                                                     | ✓    |
| Role  | ACF `team_role`       | text, max 120 (role + credentials, e.g. "Architect + Urban Design + Housing · AA Dip, TEE") | ✓    |
| Bio   | ACF `team_bio`        | textarea, plain text, auto paragraphs                                                       |      |

No drag order: the Studio page's `studio_team` field picks and orders members. Helper `if_untitled_team_member($post)` → `{name, image_id, role, bio}`.
**Term order** — no field: `project_category` and `category` terms are drag-ordered with the ordering plugin (`term_order`, see 1.6).

**Pages** (location = page template):

| Page     | Fields                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Home     | Hero = page featured image (full-bleed) + small description = page content · body = `content_grid` (above, optional `link` per block)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| Studio   | Intro = page title + content · `studio_features` repeater (no limit): `item` post object → any public content (returns ID), `title` text (falls back to the item title), `description` textarea · `studio_services` repeater: `title`, `subtitle` text, `description` textarea, `image` (optional, ID) — each row gets `id="{sanitize_title(title)}"` as anchor for the header submenu + footer · `studio_team` relationship → team_member (editor picks + orders)                                                                                                                                                                                                                                                                                                              |
| Advisory | Intro = page title + content (positioning paragraph: geographies Europe / MENA / Africa / Asia, institutional relationships UN / World Bank / IOM / UNDP, participatory co-design approach) · `advisory_features` repeater (no limit, 4 at launch) — **one row = one service area + its showcase project** (Service Areas and Showcase are the same component): `title` text (service area, required — e.g. "Social & Affordable Housing"), `subtitle` text (scope keywords — e.g. "policy diagnostics, housing strategy…"), `description` textarea (service paragraph), `item` post object → **project only**, filtered to category Advisory Projects, required (returns ID) — each row gets `id="{sanitize_title(title)}"` as anchor for the header submenu · no team section |
| Contact  | No ACF fields — intro = page title + content; renders the Theme Options `locations` on the map.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |

**Theme Options** (tabs: Clients · Locations & footer · Google Maps · Archives):

| Field             | Type                                                                                                                                                                                                        |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `clients`         | relationship → client, published only; picks which clients show and their order                                                                                                                             |
| `locations`       | repeater: `city` text · `address` textarea · `phone` text · `email` email · `map` ACF Google Map (lat/lng from address search). Used by Contact + footer; "Open in Google Maps" link generated from lat/lng |
| `enquiries`       | repeater: `label` text · `type` select `email` / `phone` · `value` text → `mailto:` / `tel:` link                                                                                                           |
| `social`          | repeater: `label` text · `url` url                                                                                                                                                                          |
| `portals`         | repeater: `label` text · `url` url                                                                                                                                                                          |
| `google_maps_key` | text — Maps JavaScript API key (browser key)                                                                                                                                                                |
| `google_maps_id`  | text — optional custom Map ID (style)                                                                                                                                                                       |
| `work_intro`      | textarea — Work archive intro                                                                                                                                                                               |
| `journal_intro`   | textarea — Journal archive intro                                                                                                                                                                            |

Google Maps: Theme Options → Google Maps (`google_maps_key`, `google_maps_id`), read by `if_untitled_google_maps()`. A Maps JavaScript key is public by design (it is in the page HTML), so it may live in the database; restrict it to the site's HTTP referrers and the Maps JavaScript API in Google Cloud. The `IF_UNTITLED_GOOGLE_MAPS_KEY` / `IF_UNTITLED_GOOGLE_MAPS_ID` constants in `wp-config.php` override the options per environment. The key feeds both the ACF location picker (`acf/fields/google_map/api` in `inc/acf.php`) and the front-end map (`data-*`); without a Map ID `DEMO_MAP_ID` is used; the map is dark via `colorScheme: DARK` in `ContactMap.js` and black-and-white via `filter: grayscale(1)` on `.contact-map__canvas` either way. Without a key or coordinates `components/contact-map.php` shows the panel only.

### 1.3 Relationships

```
Project ── 1 category ── n clients
Journal entry ── 1 entry type ── n tags
Home ── hero + content_grid rows ── 0..1 link per block (any public content)
Studio ── n featured items (any public content) ── n services (anchors → header submenu + footer) ── n team members (picked order) ── clients component
Advisory ── n service areas (anchors → header submenu) ── 1 showcase project each (Advisory Projects category) ── clients component
Primary menu (`primary_menu`) ── Studio › 2 service anchors · Advisory › 4 service-area anchors · Work › project categories (generated in code, `wp_nav_menu_objects` in `inc/setup.php`) · Journal · Contact
Theme Options ── n clients (the single list the clients component renders)
Theme Options ── n locations (Contact map + footer)
```

### 1.4 What each page shows

| Page / route                  | Sections → data (source)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Global                        | Header: logo (→ Home), **native WP menu** location `primary_menu` (Appearance → Menus, depth 2, no numbers — Phase B §2): Studio › Architecture & Interior Design · Experience Curation (custom links `/studio/#{anchor}`) · Advisory › the 4 service areas (custom links `/advisory/#{anchor}`) · Work · Journal · Contact (simple links); submenu shown on hover of the parent as a simple list below it (`:focus-within` for keyboard, `PrimaryNav` for touch) · **Footer** (every page): menu (location `footer_menu`, depth 1 — can be the same menu as the header, no field) · Studio links (generated from `studio_services` titles → `/studio/#{anchor}`) · addresses (`locations`) · enquiries · social · portals (Theme Options) |
| Home `front-page`             | hero (featured image, logo over it, small description from the page content) · `content_grid` rows in editor order; a row with `link` makes its title/image link to the item                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| Studio `studio`               | featured items (`studio_features`: item image + URL via `if_untitled_resolve_item()`, row title, description) · services (`studio_services`, anchored) · team (`studio_team` order: image, name, role, bio) · clients (component)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| Advisory `advisory`           | intro (page content) · service areas (`advisory_features`, anchored): row title, subtitle, description + showcase project (image, title, metadata from `project_metadata`: location, client/partner, year, status, short description, link to the project in Work) · clients (component)                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| Work `archive-project`        | `work_intro` · filter All + 6 categories (+ Unclassified while used) · cards (featured image, title, category; metadata location, client, year, status; hover / focus = 8 px black line under the image + metadata, both absolute, no transitions; touch = metadata always) — filter component shared with Journal (cards differ: `project-card` / `journal-card`)                                                                                                                                                                                                                                                                                                                                                                         |
| Project `project`             | hero: featured image · title · metadata (location, client, year, status) · **category below the metadata** · `content_grid` (5-column image/text rows) · `project_text` · previous / next (menu order, looping — custom helper, `get_adjacent_post()` only knows dates)                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| Journal `journal`             | `journal_intro` · filter All + 5 entry types (same mechanism as Work) · **3-column grid** of cards (featured image, title, excerpt, date, entry type)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| Journal entry `journal-entry` | lead image · title, date, entry type, tags · `content_grid`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| Contact `contact`             | Full map: one custom marker per `locations` row (fit bounds); location text (city, address, phone, email, Google Maps link) overlaid right-aligned, `position: absolute`. Without JS / key the text list still shows                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |

### 1.5 Template files

File names and naming rules follow the `create-page-template` skill. Routes for this project:

| Route (`main#id`) | File                            | How WordPress picks it                                  | ACF location rule                       |
| ----------------- | ------------------------------- | ------------------------------------------------------- | --------------------------------------- |
| `front-page`      | `front-page.php`                | Settings → Reading → static front page                  | Page Type = Front Page (`content_grid`) |
| `journal`         | `home.php`                      | Settings → Reading → posts page ("Journal")             | — (intro in Theme Options)              |
| `journal`         | `category.php`                  | entry-type archive `/journal/category/{slug}/`          | —                                       |
| `journal`         | `tag.php`                       | tag archive `/journal/tag/{slug}/` (no filter bar yet)  | —                                       |
| `journal-entry`   | `single.php`                    | single post (journal entry)                             | Post Type = post                        |
| `archive-project` | `archive-project.php`           | `archive-{post_type}.php`                               | —                                       |
| `archive-project` | `taxonomy-project_category.php` | `taxonomy-{taxonomy}.php` (key with underscore)         | —                                       |
| `project`         | `single-project.php`            | `single-{post_type}.php`                                | Post Type = project                     |
| `studio`          | `page-templates/studio.php`     | assigned in Page → Template (`Template Name: Studio`)   | Page Template = Studio                  |
| `advisory`        | `page-templates/advisory.php`   | assigned in Page → Template (`Template Name: Advisory`) | Page Template = Advisory                |
| `contact`         | `page-templates/contact.php`    | assigned in Page → Template (`Template Name: Contact`)  | — (data from Theme Options)             |
| `page`            | `page.php`                      | any other page (privacy policy, legal)                  | —                                       |
| `not-found`       | `404.php`                       | no match                                                | —                                       |
| —                 | `index.php`                     | required final fallback                                 | —                                       |

Project notes: no `single-client.php` / `single-team_member.php` (private post types); shared markup lives in
`components/` (`archive-filter`, `content-grid`, `project-card`, `journal-card`, `clients`, …). The `clients` component reads the Theme Options list and is placed in code on any template that needs it (Studio, Advisory today).

### 1.6 Archive filters

Implemented with the `create-archive-filter` skill (term-archive URLs, `if_untitled_archive_filter()`,
`components/archive-filter.php`, `aria-current`; term order = plugin `term_order`). Project values:

| Archive | "All" URL   | Term URL                    | Taxonomy                              | Templates (same markup via component)                  | Posts per page                                                     |
| ------- | ----------- | --------------------------- | ------------------------------------- | ------------------------------------------------------ | ------------------------------------------------------------------ |
| Work    | `/work/`    | `/work/category/{slug}/`    | `project_category` (6 + Unclassified) | `archive-project.php`, `taxonomy-project_category.php` | all (`-1`), menu order ASC, then date DESC                         |
| Journal | `/journal/` | `/journal/category/{slug}/` | `category` = Entry types (5)          | `home.php`, `category.php`                             | 12, menu order ASC then date DESC, infinite scroll over `/page/2/` |

- **One archive pattern:** Work and Journal are both a **3-column card grid** with the **same filter** — one
  `archive-filter` component (markup, SCSS, JS), one grid; only the card differs (`project-card` / `journal-card`).
- **Intro text:** "All" shows `work_intro` / `journal_intro`; a term view shows the term description, if set.
- **Intro alignment:** every page intro (Studio, Advisory, Work, Journal) starts at the grid column of its own header menu item
  (Studio col 1 · Advisory col 2 · Work col 3 · Journal col 4 · Contact col 5).

### 1.7 URLs & SEO

One pattern for both sections — `/{section}/` · `/{section}/{slug}/` · `/{section}/category/{term}/` — so the URL
mirrors the header label (Work, Journal) and the filter.

| URL                                                       | Shows                                                              | Index                                                              |
| --------------------------------------------------------- | ------------------------------------------------------------------ | ------------------------------------------------------------------ |
| `/`                                                       | Home                                                               | ✓                                                                  |
| `/studio/` · `/advisory/` · `/contact/`                   | pages                                                              | ✓                                                                  |
| `/work/` · `/work/category/{term}/`                       | Work archive, filtered                                             | ✓ (term description = meta description)                            |
| `/work/{project}/`                                        | project                                                            | ✓                                                                  |
| `/journal/` · `/journal/category/{term}/` · `…/page/{n}/` | Journal archive, filtered, paged                                   | ✓ (pages self-canonical)                                           |
| `/journal/{entry}/`                                       | journal entry                                                      | ✓                                                                  |
| `/journal/tag/{tag}/`                                     | tag archive (no filter bar yet)                                    | `noindex, follow` until tag filtering ships                        |
| `/journal/author/…` · `/journal/{yyyy}/…`                 | core author / date archives — not used (dates still show on cards) | **301 → `/journal/`** (`template_redirect`; also hides user names) |
| attachment pages                                          | —                                                                  | off (`wp_attachment_pages_enabled` = 0)                            |
| `client` / `team_member` / `project_location`             | —                                                                  | no URLs (`public => false`), not in the sitemap                    |

Rules:

- `register_taxonomy('project_category')` **before** `register_post_type('project')` (rewrite slugs `work` /
  `work/category`, `with_front => false`) so `/work/category/{term}/` wins over the project rules.
- Reserved slugs: `category`, `tag`, `page` can't be a project or journal entry slug (`wp_unique_post_slug` filter).
- No page may use the slug `work` (the project archive owns it).
- **Slugs are words, never IDs:** lowercase, hyphenated, from the title (`sanitize_title()`); a numeric-only slug is not allowed. Trailing slash everywhere (WP default); one URL per item.
- The old website's URLs are **not** maintained or redirected (fresh start).
- Filters, pagination and prev / next are real `<a href>` links — crawlable without JS; infinite scroll only enhances.
- One `<h1>` per route; image `alt` from the media library; `srcset` everywhere.
- The SEO Framework (Q14): titles, meta description (excerpt → term description → intro), Open Graph image (featured
  image), XML sitemap (pages, projects, entries, project categories, entry types — no users, tags or private types);
  core sitemap off.

---

## 2. Backend — Phase A

| Step | Work                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 | Done when                          |
| ---- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------- |
| 2.1  | Update ACF Pro to the current release (license). Install WP-CLI (`brew install wp-cli`). Install a free drag & drop order plugin (writes `menu_order`) for `project` and `post` + the `project_category` and `category` terms (clients and team are ordered by their relationship fields).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | No ACF notices in `debug.log`      |
| 2.2  | `inc/post-types.php` (skill `register-custom-post-type`): `project`, `client`, `team_member`, `project_category`, `project_location` (private, hierarchical country › city: `public => false`, `show_ui`, no rewrite, core meta box off — edited only through the ACF field). Relabel Posts → Journal, Categories → Entry types. Disable comments. Block editor off for `post`, `project`, `team_member` and `page` (page content = plain intro text). `add_post_type_support('post', 'page-attributes')`. `pre_get_posts` (main query; Work = project archive + `project_category`; Journal = `home` + `category` + `tag`): projects `-1`, journal 12; both `orderby => ['menu_order' => 'ASC', 'date' => 'DESC']` (new, unordered entries have `menu_order` 0 → appear first, newest first).                                                                                                                                                                       | Types visible in wp-admin          |
| 2.3  | **`bin/setup.php`** (theme folder, `wp eval-file bin/setup.php`, idempotent — run on every environment): permalinks `/journal/%postname%/`, category / tag base left empty (= `category` / `tag`, see 1.7), attachment pages off, core sample post / page deleted. **Creates the pages empty first** (slugs `home`, `journal`, `studio`, `advisory`, `contact`, templates assigned) — the seed (§3) only fills them by slug — then Home = front page, Journal = posts page. **No "Uncategorized"**: creates the 5 entry types from `entry-types.csv`, sets default category = Project notes (`default_category`), then deletes Uncategorized (core can't delete the default term, so the order matters). Menu "Primary" (both locations): Studio › 2 service anchors · Advisory · Work · Journal · Contact — Advisory sub-items added once the service areas exist (Q18). URL rules from 1.7 (redirects, tag `noindex`, reserved / numeric slugs) in `inc/urls.php`. | 1.7 URL map: 200 / 301 as listed   |
| 2.4  | `inc/media.php` (skill `render-responsive-image`): core proportional sizes only (no crops), srcset up to 2560, WebP sub-sizes — **before any upload**.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               | `wp media image-size` = core sizes |
| 2.5  | ACF field groups from 1.2 (skill `create-acf-field-group`). `inc/acf.php`: relationship / post object filters (published only; "any public content" = `get_post_types(['public' => true])` minus `attachment`, so new public CPTs appear automatically); `acf/validate_value` so `image_text` rows don't overlap, each text block has a heading or body, and a project `location` is a city (term with a parent).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    | `acf-json/*.json` committed        |
| 2.6  | Keep docs in sync: if the code diverges from a skill/instruction (new rule, renamed helper), update that skill in the same commit — not this plan.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   | Skills match code                  |

## 3. Seed — Phase A (`bin/seed.sh`, WP-CLI, local only)

**Rule: the seed reads only curated CSVs in [`.github/plans/phase-b/seeds/`](../phase-b/seeds/)** — no data in code, no
other source. Data is improved by editing those files (the client's Excel is curated into them — Q18), then re-running
the seed. A data set without a CSV yet is not seeded. Goal: a fast, repeatable import instead of entering content by hand.

Scripts live in the theme: `wp-content/themes/if-untitled/bin/seed.sh` → `wp eval-file bin/seed.php [seeds-dir]`, run
from the theme folder (default seeds dir: the repo's `.github/plans/phase-b/seeds`, i.e. `../../../.github/plans/phase-b/seeds`).

- **Idempotent:** items are matched by slug / name and updated, never duplicated; safe to re-run after every CSV edit.
- **ACF** values via `update_field()` only (never raw post meta).
- **Slugs = words, never IDs:** every slug is `sanitize_title()` of the title / name (lowercase, hyphenated). The old
  site's URLs are **not** kept or redirected (`source_*` columns are provenance only).
- **Preconditions** (the seed stops with an error otherwise): ACF Pro active, the ordering plugin active (2.1 — it
  creates `term_order`), `inc/media.php` loaded (2.4 — so image sub-sizes are final), settings from 2.3 applied (incl. the empty pages).

| File              | Status | Columns                                                                                                                                                                           | Creates                                                                                                                                                                 |
| ----------------- | ------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `categories.csv`  | ✅     | `order, name, slug, description`                                                                                                                                                  | `project_category` terms, `term_order` = `order`                                                                                                                        |
| `locations.csv`   | ✅     | `country, city` (one row per city; short country names: Greece · UK · USA)                                                                                                        | `project_location` terms: country (parent) › city (child)                                                                                                               |
| `projects.csv`    | ✅ 28  | `title, category, location, client, year, status, excerpt, featured_image, project_text, publish` + provenance `source_slug, source_url, featured_image_url, gallery_urls, notes` | projects (row order = `menu_order`), clients, images                                                                                                                    |
| `entry-types.csv` | ✅ 5   | `order, name, slug, description`                                                                                                                                                  | Journal `category` terms, `term_order` = `order`; Project notes becomes the default category, then Uncategorized is deleted (2.3)                                       |
| `clients.csv`     | ⏳     | `order, name, url, logo`                                                                                                                                                          | `client` posts + the Theme Options `clients` list in this order                                                                                                         |
| `team.csv`        | ✅ 3   | `order, name, role, bio, image` + provenance `source_url, image_url, notes`                                                                                                       | `team_member` posts + Studio `studio_team` in `order`                                                                                                                   |
| `pages.csv`       | ⏳     | `slug, title, template, content, featured_image`                                                                                                                                  | pages (Home, Studio, Advisory, Journal, Contact, Privacy)                                                                                                               |
| `advisory.csv`    | ⏳     | `order, title, subtitle, description, project` (project title)                                                                                                                    | Advisory `advisory_features` (4 service areas + showcase projects). Blocked until `projects.csv` has Advisory Projects (Q18) — none today; the seed skips with a notice |
| `journal.csv`     | ⏳     | `title, date, category, tags, excerpt, featured_image, content, publish`                                                                                                          | journal entries                                                                                                                                                         |
| `options.csv`     | ⏳     | `key, value` (locations, enquiries, social, portals, intros)                                                                                                                      | Theme Options                                                                                                                                                           |

Menus (`primary_menu`, `footer_menu`) and reading settings / permalinks are part of 2.3 (structure, not content).

`projects.csv`, per row:

1. **Terms:** `category` → a `name` in `categories.csv` (blank = not allowed; the curated file uses **Unclassified**
   until the project is classified). `location` ("City, Country", split on the last comma) → a row in `locations.csv`
   (blank = no location; an unknown place stops the seed — add it to `locations.csv` first). `client` (`|`-separated) → `client` posts (created if missing).
2. **Project:** created / updated by slug, `menu_order` = row number, excerpt, `project_text`, `project_metadata`
   (location city term ID, client IDs, year, status: Completed / In progress / Planned → enum, empty → `completed`),
   `publish` (`yes` → Published, else Draft).
3. **Images:** featured image from `seeds/images/{featured_image}` if the file exists (git-ignored, local only), else
   downloaded from `featured_image_url` (`media_sideload_image()`); each `gallery_urls` image the same way, attached to
   the project and added as an `image` row (`1-5`, placeholder layout) in `content_grid`. Every attachment stores its
   source in `_if_untitled_source`; a re-run reuses it instead of importing again. Title / alt = project title.

`team.csv`, per row: `team_member` created / updated by slug (`sanitize_title(name)`), `role` + `bio` → ACF, portrait =
featured image (same image rules as projects: `seeds/images/{image}` else `image_url`). After all rows, the Studio page's
`studio_team` is set to the members in `order` (the page exists from 2.3).

Implementation notes: every row is validated (category, location, status) before anything is written. Images are
keyed by their URL in `_if_untitled_source`, so a featured image that is also the first gallery image is imported once
(Hikaru's portrait reuses Annika's file until her own is added to `team.csv`). Content grid rows are written with flat
sub-field names (seamless clones — the shape `get_field()` returns). First run ≈ 5 min, 257 attachments; PHP
`exif_read_data` warnings on some old camera files are harmless.

Reference only (not seed data) — the client's outline, to curate `projects.csv` against:

| Category                | Projects                                                                                                                                        |
| ----------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| Mixed-Use Spaces        | Thission Lofts · Palco Factory · Hellenic Xalymvoyrgia Offices                                                                                  |
| Urban Houses            | Filmmakers House · Curtains · Brick Soleil · Under One Roof · Gore House · Douros Loft · Actors Loft · Richards House                           |
| Villas & Hospitality    | Pommot Hotel · Kal 124 — Andromachis · Kastri House · Double Decker · Eugenias Keli House · Kythira Houses · Francas House · Dada · Agave House |
| Commercial & F&B        | Inate · Toyota Flagship Store · Eleftheroudakis · Fabbrica Shop · Vasilainas Restaurant                                                         |
| Art & Experience Design | Her Data · Summer Palace · Polis Initiative · Simparch · Traces of Commerce A+B · Grid Off — Lights On! · Sleep of Ulro                         |
| Advisory Projects       | Mobile Van (+ 3 more from the Excel — Q18)                                                                                                      |

Proves the model end-to-end before any template is written. **Done when** every page in 1.4 has data from the CSVs.

## 4. Front-end — Phase B

Starts when Phase A (§2 + §3) is done. How each route looks and the build order live in
[`../phase-b/phase-b.plan.md`](../phase-b/phase-b.plan.md); this section keeps only the shared rules and helpers.

Each route = the file from **1.5** + `<main id="{route}">` + `Router` id in JS + `src/styles/pages/_{route}.scss`
(skill `create-page-template`).

Helpers first (`inc/template-tags.php`): `if_untitled_image()` (skill `render-responsive-image`), `if_untitled_project_metadata()`, `if_untitled_team_member()`, `if_untitled_clients()`,
`if_untitled_adjacent_project()`, `if_untitled_youtube_id()` + `if_untitled_media()` (skill `render-video`), `if_untitled_content_grid()`, `if_untitled_resolve_item()`, `if_untitled_archive_filter()`, `if_untitled_locations()`, `if_untitled_studio_services()` (shared by Studio + footer; finds the Studio page by its template `page-templates/studio.php`, not by ID/slug; anchors must be unique; anchored sections get `scroll-margin-top` for the header).
Card image = featured image on both archives (Work + Journal), never a `content_grid` image or video; without a featured image the card renders without image.

**Build order:** helpers (above) → then the components and routes in the order of
[Phase B §4](../phase-b/phase-b.plan.md#4-components-build-order) — the single build order, not repeated here.

Skills per step: routes → `create-page-template`; shared components → `create-theme-component`;
filters (Work + Journal, values in 1.6) → `create-archive-filter`; field reads → `create-acf-field-group` §5;
verification → `vite-build-verify`.

### 4.1 Checkpoint — 2026-10-28 (commit `3caf2ac`)

**Done:** components #1–#11 (Phase B §4) · routes Project, Work (+ category filter), Studio, Advisory, Journal
(+ category / tag, infinite scroll), Journal entry, Contact (map), Home, page / 404 / index fallbacks. `npm run check`
passes; every route returns 200 (404 for unknown paths under `/journal/`, `/work/`).

Added beyond the helper list above: `if_untitled_archive_intro()`, `if_untitled_paged_links()`,
`if_untitled_studio_features()`, `if_untitled_advisory_features()`, `if_untitled_menu_item_is_current()` (Work current on
projects, Journal only on journal content); components `location`, `contact-map`, `media`, `services`; constants
`IF_UNTITLED_GOOGLE_MAPS_KEY` / `IF_UNTITLED_GOOGLE_MAPS_ID`. Removed `archive.php`, `post-card`, `.container`,
`.single-layout`.

**Remaining:**

1. ✅ QA (2026-10-28, `0be0697`) — responsive, a11y basics, images: explicit `loading="lazy"` on every non-priority
   image (core's heuristic left below-the-fold images eager). Lighthouse (mobile): performance 99–100,
   accessibility 100 after 24px touch targets on footer / location links. Remaining best-practices / SEO flags are
   environment (HTTPS, HTTP/2, cache headers), the missing site icon (favicon → set in Customizer) and meta description
   (The SEO Framework, Q14 — not installed yet).
2. ✅ Theme README — `page-templates/`, optional constants, grid / `text-style()` conventions.
3. ✅ Phase B plan §4 — status line.
4. Content — Home (featured image, description, grid rows), Contact intro + Maps key (Theme Options → Google Maps; Athens marker is the city centre until its address is set), Advisory
   features, clients, journal entries, Privacy page (draft).
5. ✅ `text-render` on the type scale (h2 display M, h3–h4 display S, `currentcolor` rules for dark mode).
6. ✅ Unknown root paths return 404 (`3c8a9ac`, `inc/urls.php`): with the `/journal/%postname%/` base no rewrite rule
   matched them, so core rendered the front page and 301'd to `/`.
7. Open questions still ⏳: Q1, Q5, Q9, Q13, Q18.

---

## Open questions

✅ answered · ⏳ still open (building with the stated default until answered).

| #   | Question                                                                                                                | Answer / default                                                                                                                                                                                                                                                                                                                                                                                                                                |
| --- | ----------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | ✅ Project status vs publishing                                                                                         | Visibility = native WP post status (Draft / Published). Project status = ACF enum `in_progress` · `completed` · `planned`, shown in metadata                                                                                                                                                                                                                                                                                                    |
| 2   | ✅ Prev / next project                                                                                                  | All projects, by menu order (same order as Work "All"), looping first ↔ last                                                                                                                                                                                                                                                                                                                                                                    |
| 3   | ✅ Work card content                                                                                                    | Featured image, title, category (`project_category`, GT America medium), `project_metadata` (location, client, year, status), plain text; hover / focus shows it plus an 8 px black line under the image (both absolute → no height change, no transitions); always on touch                                                                                                                                                                    |
| 4   | ✅ Work "All" order                                                                                                     | One manual drag & drop order via a free plugin (e.g. Simple Custom Post Order — writes native `menu_order`); unordered projects fall back to date created, newest first. **The theme owns front-end order** (`pre_get_posts`, 2.2); the plugin only writes `menu_order` / `term_order` in wp-admin; it has no auto-sort switch, so the theme always sets an explicit `orderby` (term order still read via `term_order`, 1.6)                    |
| 5   | ✅ Clients on Studio / Advisory                                                                                         | One list in Theme Options (`clients`); shared `clients` component placed in code per template. Typographic list: names (GT America bold) in a vertical list in col 2, linking to `client_url` in a new tab (logos not shown for now). No `client_type`                                                                                                                                                                                          |
| 6   | ✅ Team CV                                                                                                              | No CV; team member = image, name, role, bio                                                                                                                                                                                                                                                                                                                                                                                                     |
| 7   | ✅ Publications / References fields                                                                                     | Same fields as every journal entry — entry types are only categories; no type-specific fields for now                                                                                                                                                                                                                                                                                                                                           |
| 8   | ✅ Journal pagination                                                                                                   | Infinite scroll, 12 per batch, as progressive enhancement: native `/page/N/` links stay in the HTML (no-JS + SEO); JS (`IntersectionObserver` + `fetch` of the next page) appends cards and updates the URL with `history.replaceState`. Works per entry type. Footer must stay reachable — load on sentinel, stop at the last page                                                                                                             |
| 9   | ✅ Journal "Archived" entries                                                                                           | Native Draft / Private — hidden, no extra field. Also confirmed: filter bar All + 5 entry types at launch; cards show featured image, title, intro (excerpt), date, entry type (Q16)                                                                                                                                                                                                                                                            |
| 10  | ✅ Home layout                                                                                                          | Designs: hero (page featured image + small description from the page content) + the shared `content_grid` with an optional `link` per block — no separate Home sections                                                                                                                                                                                                                                                                         |
| 11  | ✅ Footer / contact details                                                                                             | Footer on every page, all content from Theme Options: menu (native), Studio service links, `locations`, `enquiries`, `social`, `portals`                                                                                                                                                                                                                                                                                                        |
| 12  | ✅ Contact map                                                                                                          | ACF Google Map field per location (Theme Options `locations`) + Maps JavaScript API with a custom marker, lazy-loaded on the Contact route only; location text overlaid on the right. Needs `IF_UNTITLED_GOOGLE_MAPS_KEY`                                                                                                                                                                                                                       |
| 13  | ⏳ Cookie consent / privacy policy for YouTube and the Google Maps JS API? **Launch blocker** (GDPR)                    | **TODO — before launch.** YouTube is click-to-load only (`youtube-nocookie`, nothing loads before the click — never autoplay); all embeds go through `YoutubeVideo` (skill `render-video`) so a consent step is added in one place. Main open point: the Maps JS API on Contact                                                                                                                                                                 |
| 14  | ✅ SEO plugin                                                                                                           | The SEO Framework (lightweight) — install at the QA step                                                                                                                                                                                                                                                                                                                                                                                        |
| 15  | ✅ `content_grid` placement                                                                                             | Designs: column range (1–5) per image / text block, flexible content layouts image · text · image + text; text = heading / body (size), box none / outlined / filled, horizontal + vertical alignment (1.2)                                                                                                                                                                                                                                     |
| 16  | ✅ Journal archive cards                                                                                                | Same filter mechanism as the Work archive (`create-archive-filter`); 3-column grid; card image = featured image (videos live only in `content_grid`, never on cards). Work cards also use the featured image                                                                                                                                                                                                                                    |
| 17  | ✅ Header menu                                                                                                          | Native WP menu (location `primary_menu`, depth 2): **Studio** › Architecture & Interior Design · Experience Curation (anchors of `studio_services`) · **Advisory** › the 4 service areas (anchors of `advisory_features`) · **Work** · **Journal** · **Contact** (simple links). Sub-items are custom links to page anchors, edited in Appearance → Menus; anchor ids = `sanitize_title(title)`, so renaming a row means updating the menu link |
| 18  | ⏳ Seed data from the client's Excel                                                                                    | **TODO — Excel to be delivered.** Curate it into `.github/plans/phase-b/seeds/*.csv` (the seed's only source, §3): projects, metadata, excerpts, Advisory service areas + 4 showcase projects, clients. Until then: `projects.csv` scraped from the old site + `categories.csv` — it has **no Advisory Projects** yet, so the Advisory page can't be seeded (each row requires a showcase project)                                              |
| 19  | ⏳ Advisory showcase "short description (2–3 lines)"                                                                    | Default: native project **excerpt** (one source, reusable on cards later); the row `description` is the service-area paragraph                                                                                                                                                                                                                                                                                                                  |
| 20  | ⏳ Advisory clients are institutional (UN agencies, World Bank, IOM, UNDP, municipalities, NGOs) — same list as Studio? | Default: one Theme Options list (Q5). If Studio and Advisory need different lists, add a second relationship field `advisory_clients` (no `client_type`)                                                                                                                                                                                                                                                                                        |
| 21  | ⏳ Header submenu anchors                                                                                               | **TODO — design a solution later.** Anchors are implemented in code now (`id="{sanitize_title(title)}"`); menu sub-items are custom links, so renaming a row breaks its link until then                                                                                                                                                                                                                                                         |
| 22  | ✅ Old website URLs                                                                                                     | Not maintained: no redirects, fresh URLs (slugs from titles, never IDs — 1.7)                                                                                                                                                                                                                                                                                                                                                                   |
| 23  | ⏳ Linking to archives (Work, Journal, a category filter)                                                               | A post object can't pick archives. Default: not needed (rows link to pages / projects / entries). If needed, switch that field to ACF `page_link` with "Allow archives URLs"                                                                                                                                                                                                                                                                    |
| 24  | ⏳ Hosting, deployment, staging, backups                                                                                | **TODO.** Decide host (PHP ≥ 8.1, WP-CLI, SSH), how `dist/` is built and deployed (built in CI or on the server — never committed), a staging copy, and backups (DB + `uploads/`). Server `upload_max_filesize` / `post_max_size` ≥ 64 MB for MP4 videos (skill `render-video`)                                                                                                                                                                 |
| 25  | ⏳ Analytics                                                                                                            | Default: none. If wanted, a cookieless tool (e.g. Plausible) avoids extra consent; Google Analytics needs Q13 first                                                                                                                                                                                                                                                                                                                             |
| 26  | ⏳ Site icon & social sharing image                                                                                     | Default: Site icon via Customizer (512 × 512 PNG from the logo); default share image (1200 × 630) in The SEO Framework (Q14), per page = featured image. Assets from the designer                                                                                                                                                                                                                                                               |
