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

| Topic            | Decision                                                                                                 |
| ---------------- | -------------------------------------------------------------------------------------------------------- |
| Journal          | Default WordPress **posts** (Posts page = Journal). A curated editorial space, not a blog/news feed — any cadence, manual order. Categories relabelled "Entry types". |
| Clients          | `client` post type (private) — relatable from projects; one curated list in Theme Options feeds the `clients` component. |
| Authors / team   | `team_member` post type — Studio team and journal authors.                                               |
| Home             | Sortable sections, each pointing to any project / journal post / page, with optional text and link.      |
| Media            | One flexible field `content_blocks` (shared by projects and journal) — editor picks the layout per row.  |
| Contact          | No form. Minimal page with a Google Maps embed + contact details.                                        |
| Language         | English only.                                                                                            |
| Project metadata | Exactly four fields: **Location, Client, Year, Status** (no size / typology / collaborators).            |

---

## 1. Data model

### 1.1 Content types

| Type             | Key                | Public URL                                              | Native fields used                                  |
| ---------------- | ------------------ | ------------------------------------------------------- | --------------------------------------------------- |
| Project          | `project`          | `/projects/` · `/projects/{slug}/`                      | title, featured image, menu order                   |
| Project category | `project_category` | `/projects/category/{slug}/`                            | name, slug, description                             |
| Journal entry    | `post`             | `/journal/` · `/journal/{slug}/`                        | title, date, status, excerpt, featured image, menu order |
| Entry type       | `category`         | `/journal/type/{slug}/`                                 | name, slug, description                             |
| Client           | `client`           | — (private)                                             | title = name, featured image = logo, menu order     |
| Team member      | `team_member`      | — (private)                                             | title = name, featured image = portrait, menu order |
| Pages            | `page`             | `/`, `/studio/`, `/advisory/`, `/contact/`, `/journal/` | title                                               |

Seeded terms — Project categories (in order): Mix Use Spaces · Urban Houses · Villas & Hospitality · Commercial & F&B ·
Art & Experience Design · Advisory Projects. Entry types: Project notes · Field dispatches · Essays & positions ·
References · Publications.

### 1.1.1 Journal brief (client)

A curated, editorial space — not a blog or news feed. Published at any cadence; combines images, video and text in a
clean format. **Each entry:** lead image or video, title, short intro text, date; full entry on click.
**Entry types:** Project notes (process, material detail, behind the work) · Field dispatches (advisory assignments,
workshops, site visits) · Essays & positions (ideas, reflections, spatial thinking) · References (books, places,
precedents) · Publications. No strict categorisation required — tag filtering may be added over time; at launch the filter bar is All + 5 entry types (1.6).
**Order:** one manual drag & drop order (`menu_order`), date created (newest first) when not set.

### 1.2 Fields (ACF) — the complete list

Built with the `create-acf-field-group` skill (naming, locations, return formats). Project-specific list:

**Content blocks** — one field group, location _post type = project OR post_. Flexible content `content_blocks`:

| Layout       | Sub-fields                         |
| ------------ | ---------------------------------- |
| `image`      | `image`, `caption`                 |
| `image_pair` | `image_left`, `image_right`        |
| `video`      | `youtube_url`, `poster`, `caption` |
| `text`       | `text` (wysiwyg, basic toolbar)    |

**Project** — `group_project`

| Field              | Type                                                                  | Req. |
| ------------------ | --------------------------------------------------------------------- | ---- |
| `project_category` | taxonomy (radio, save + load terms — single category, no custom code) | ✓    |
| `project_intro`    | textarea — 2–3 lines for cards and showcases                          | ✓    |
| `project_text`     | wysiwyg — brief / approach / outcome ("read more")                    |      |
| `project_metadata` | group — see below                                                     |      |

`project_metadata` (ACF group, one "Metadata" box in the editor; template: `get_field('project_metadata')['location']`):

| Sub-field  | Label    | Type                                                                                                  | Req. |
| ---------- | -------- | ----------------------------------------------------------------------------------------------------- | ---- |
| `location` | Location | text (e.g. "Athens, Greece")                                                                          |      |
| `client`   | Client   | post object → `client`, multiple, returns IDs                                                         |      |
| `year`     | Year     | number, 4 digits (min 1990, max current year + 5)                                                     |      |
| `status`   | Status   | select (enum): `in_progress` In progress · `completed` Completed · `planned` Planned (default `completed`). Shown publicly in the metadata. Not the same as WP post status (Draft/Published = visibility) | ✓    |

**Journal entry** — `group_journal`: `journal_video` (YouTube URL — if set, the lead is a video, else the featured
image) · `journal_authors` (relationship → team_member). Intro = native excerpt, type = native category.

**Client** — logo = featured image, name = title, `client_url` (url, optional).
**Team member** — `group_team_member`, location _Post Type = team_member_. Exactly four fields (no CV):

| Field | Source                          | Type                                     | Req. |
| ----- | ------------------------------- | ---------------------------------------- | ---- |
| Image | native featured image           | portrait, labelled "Portrait" in wp-admin | ✓    |
| Name  | native post title               | placeholder "Full name"                  | ✓    |
| Role  | ACF `team_role`                 | text, max 80 (e.g. "Founding architect") | ✓    |
| Bio   | ACF `team_bio`                  | textarea, plain text, auto paragraphs    |      |

Order = native menu order. Helper `if_untitled_team_member($post)` → `{name, image_id, role, bio}`.
**Term order** — `sort_order` (number) on `project_category` and `category` terms (filter order, see 1.6).

**Pages** (location = page template):

| Page     | Fields                                                                                                                                                                                                                                           |
| -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Home     | `home_sections` flexible content — layouts `feature` (full-bleed) and `showcase` (preview card); each: `item` post object (project / post / page), `text` (optional override), `image` (optional override), `link_label`                         |
| Studio   | `studio_intro` wysiwyg · `studio_intro_link` link · `studio_showcases` repeater max 2 (`heading`, `text`, `project` → project) · `studio_services` repeater (`title`, `items` textarea, one per line) · `studio_team` relationship → team_member |
| Advisory | `advisory_intro` wysiwyg · `advisory_services` repeater, 4 rows (`title`, `text`) · `advisory_showcase` relationship → project (max 4, Advisory Projects only)                                                                                   |
| Contact  | `contact_map` group: `embed_url` (url — Google Maps → Share → _Embed a map_ `src`, must start with `https://www.google.com/maps/embed`) · `link_url` (url — "Open in Google Maps" link)                                                       |

**Theme Options** — `clients` (relationship → client, published only, order = drag order in the field) · `contact_email` · `contact_address` · `contact_instagram` · `contact_whatsapp` · `work_intro` · `journal_intro`.
Google Maps embed needs no API key or token.

### 1.3 Relationships

```
Project ── 1 category ── n clients
Journal entry ── 1 entry type ── n authors (Team member)
Home section ── 1 item (Project | Journal entry | Page)
Studio ── 2 showcase projects ── n team members ── clients component
Advisory ── ≤4 showcase projects ── clients component
Theme Options ── n clients (the single list the clients component renders)
```

### 1.4 What each page shows

| Page / route                  | Sections → data (source)                                                                                                      |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| Global                        | Header: logo, menu 01–05 (native menu, CSS counter numbering) · Footer (every page) with contact details: email, address, Instagram, WhatsApp (Theme Options) |
| Home `front-page`             | `home_sections` in editor order; each row resolves title, intro/excerpt, image, URL, type label from its `item`               |
| Studio `studio`               | intro + link · 2 showcases (project image/title/URL) · services · team (image, name, role, bio) · clients (component)      |
| Advisory `advisory`           | intro · 4 service areas · 4 showcases (title, client, location, year, intro, link — from the project) · clients (component)       |
| Work `archive-project`        | `work_intro` · filter All + 6 categories · cards (featured image, title, metadata: location, client, year, status)                                            |
| Project `project`             | `content_blocks` image sequence · title, category · "read more" `project_text` · metadata · previous / next (menu order)      |
| Journal `journal`             | `journal_intro` · filter All + 5 entry types · cards (lead image/video, title, excerpt, date, entry type)                     |
| Journal entry `journal-entry` | lead · title, date, entry type, authors · `content_blocks`                                                                    |
| Contact `contact`             | Google Maps iframe (`contact_map`) + "Open in Google Maps" link · contact details (options)                                                                        |

### 1.5 Template files

File names and naming rules follow the `create-page-template` skill. Routes for this project:

| Route (`main#id`) | File                            | How WordPress picks it                                  | ACF location rule          |
| ----------------- | ------------------------------- | ------------------------------------------------------- | -------------------------- |
| `front-page`      | `front-page.php`                | Settings → Reading → static front page                  | Page Type = Front Page     |
| `journal`         | `home.php`                      | Settings → Reading → posts page ("Journal")             | — (intro in Theme Options) |
| `journal`         | `category.php`                  | entry-type archive `/journal/type/{slug}/`              | —                          |
| `journal-entry`   | `single.php`                    | single post (journal entry)                             | Post Type = post           |
| `archive-project` | `archive-project.php`           | `archive-{post_type}.php`                               | —                          |
| `archive-project` | `taxonomy-project_category.php` | `taxonomy-{taxonomy}.php` (key with underscore)         | —                          |
| `project`         | `single-project.php`            | `single-{post_type}.php`                                | Post Type = project        |
| `studio`          | `page-templates/studio.php`     | assigned in Page → Template (`Template Name: Studio`)   | Page Template = Studio     |
| `advisory`        | `page-templates/advisory.php`   | assigned in Page → Template (`Template Name: Advisory`) | Page Template = Advisory   |
| `contact`         | `page-templates/contact.php`    | assigned in Page → Template (`Template Name: Contact`)  | Page Template = Contact    |
| `page`            | `page.php`                      | any other page (privacy policy, legal)                  | —                          |
| `not-found`       | `404.php`                       | no match                                                | —                          |
| —                 | `index.php`                     | required final fallback                                 | —                          |

Project notes: no `single-client.php` / `single-team_member.php` (private post types); shared markup lives in
`components/` (`archive-filter`, `content-blocks`, `project-card`, `journal-card`, `clients`, …). The `clients` component reads the Theme Options list and is placed in code on any template that needs it (Studio, Advisory today).

### 1.6 Archive filters

Implemented with the `create-archive-filter` skill (term-archive URLs, `if_untitled_archive_filter()`,
`components/archive-filter.php`, `sort_order` term order, `aria-current`). Project values:

| Archive | "All" URL    | Term URL                     | Taxonomy                          | Templates (same markup via component)                  | Posts per page            |
| ------- | ------------ | ---------------------------- | --------------------------------- | ------------------------------------------------------ | ------------------------- |
| Work    | `/projects/` | `/projects/category/{slug}/` | `project_category` (6 categories) | `archive-project.php`, `taxonomy-project_category.php` | all (`-1`), menu order ASC, then date DESC |
| Journal | `/journal/`  | `/journal/type/{slug}/`      | `category` = Entry types (5)      | `home.php`, `category.php`                             | 12, menu order ASC then date DESC, infinite scroll over `/page/2/` |

- **Intro text:** "All" shows `work_intro` / `journal_intro`; a term view shows the term description, if set.

---

## 2. Backend

| Step | Work                                                                                                                                                                                                                                                                            | Done when                             |
| ---- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------- |
| 2.1  | Update ACF Pro to the current release (license). Install WP-CLI (`brew install wp-cli`). Install a free drag & drop order plugin (writes `menu_order`) for `project`, `post`, `client`, `team_member`.                                                                                                                                                                                        | No ACF notices in `debug.log`         |
| 2.2  | `inc/post-types.php` (skill `register-custom-post-type`): `project`, `client`, `team_member`, `project_category`. Relabel Posts → Journal, Categories → Entry types. Disable comments. Block editor off for `post`, `project` and `team_member`. `add_post_type_support('post', 'page-attributes')`. `pre_get_posts`: projects all; projects and journal `orderby => ['menu_order' => 'ASC', 'date' => 'DESC']`. | Types visible in wp-admin             |
| 2.3  | Settings: permalinks `/journal/%postname%/`, category base `type`; Home = front page, Journal = posts page.                                                                                                                                                                     | All URLs in 1.1 return 200            |
| 2.4  | `inc/setup.php`: image sizes (`card` 800w, `full` 2400w) — **before any upload**.                                                                                                                                                                                               | Sizes listed in `wp media image-size` |
| 2.5  | ACF field groups from 1.2 (skill `create-acf-field-group`). `inc/acf.php`: relationship filters (published only; Advisory showcase → Advisory Projects).                                                                                                                        | `acf-json/*.json` committed           |
| 2.6  | Keep docs in sync: if the code diverges from a skill/instruction (new rule, renamed helper), update that skill in the same commit — not this plan.                                                                                                                              | Skills match code                     |

## 3. Seed (`bin/seed.sh`, WP-CLI, local only)

Terms · 33 project drafts from the outline (categories + menu order) · clients · Annika + Martha · pages with templates ·
reading settings + permalinks · primary menu · 3–5 back-dated journal posts · placeholder images.
Proves the model end-to-end before any template is written. **Done when** every page in 1.4 has data.

## 4. Front-end

Each route = the file from **1.5** + `<main id="{route}">` + `Router` id in JS + `src/styles/pages/_{route}.scss`
(skill `create-page-template`).

Helpers first (`inc/template-tags.php`): `if_untitled_project_metadata()`, `if_untitled_team_member()`, `if_untitled_clients()`,
`if_untitled_adjacent_project()`, `if_untitled_youtube_id()`, `if_untitled_content_blocks()`, `if_untitled_resolve_item()`, `if_untitled_archive_filter()`.

Order: **global** (nav, footer with contact details) → **shared components** (archive filter, content blocks, YouTube player — click-to-load
`youtube-nocookie`, project card, journal card, clients) → **Work + Project** → **Studio** → **Advisory** →
**Journal** → **Contact** (Google Maps iframe, `loading="lazy"`) → **Home** → **QA** (a11y, empty states, srcset, 404, Lighthouse).

Skills per step: routes → `create-page-template`; shared components → `create-theme-component`;
filters (Work + Journal, values in 1.6) → `create-archive-filter`; field reads → `create-acf-field-group` §5;
verification → `vite-build-verify`.

---

## Open questions

✅ answered · ⏳ still open (building with the stated default until answered).

| #   | Question                                                           | Answer / default                                                            |
| --- | ------------------------------------------------------------------ | -------------------------------------------------------------------------- |
| 1   | ✅ Project status vs publishing                                     | Visibility = native WP post status (Draft / Published). Project status = ACF enum `in_progress` · `completed` · `planned`, shown in metadata |
| 2   | ✅ Prev / next project                                              | All projects, by menu order (same order as Work "All"), looping first ↔ last |
| 3   | ✅ Work card content                                                | Featured image, title, `project_metadata` (location, client, year, status) |
| 4   | ✅ Work "All" order                                                | One manual drag & drop order via a free plugin (e.g. Simple Custom Post Order — writes native `menu_order`); unordered projects fall back to date created, newest first |
| 5   | ✅ Clients on Studio / Advisory                                     | One list in Theme Options (`clients`); shared `clients` component placed in code per template. Item = logo (featured image) linking to `client_url` in a new tab; name = alt text / fallback without logo. No `client_type` |
| 6   | ✅ Team CV                                                          | No CV; team member = image, name, role, bio                                           |
| 7   | ✅ Publications / References fields                                 | Same fields as every journal entry — entry types are only categories; no type-specific fields for now |
| 8   | ✅ Journal pagination                                               | Infinite scroll, 12 per batch, as progressive enhancement: native `/page/N/` links stay in the HTML (no-JS + SEO); JS (`IntersectionObserver` + `fetch` of the next page) appends cards and updates the URL with `history.replaceState`. Works per entry type. Footer/contact banner must stay reachable — load on sentinel, stop at the last page |
| 9   | ✅ Journal "Archived" entries                                       | Native Draft / Private — hidden, no extra field. Also confirmed: filter bar All + 5 entry types at launch; cards show lead image/video, title, intro (excerpt), date, entry type |
| 10  | ⏳ Home: more layouts than feature / showcase? Video hero?          | **Open — waiting for designs.** Build `feature` + `showcase`; add layouts when designs land |
| 11  | ✅ Contact banner                                                   | Part of the site footer (`footer.php` → `components/site-footer.php`), on every page; content from Theme Options (`contact_email`, `contact_address`, `contact_instagram`, `contact_whatsapp`) |
| 12  | ✅ Contact map                                                      | Google Maps instead of Mapbox: ACF URLs on the Contact template (`contact_map.embed_url`, `link_url`) → `<iframe loading="lazy">` + link. Editor changes the location without code; no token |
| 13  | ⏳ Cookie consent / privacy policy for YouTube and Google Maps?     | **Open — discuss later.** Meanwhile YouTube is click-to-load (`youtube-nocookie`); keep embeds behind a single helper so consent can be added in one place |
| 14  | ✅ SEO plugin                                                       | The SEO Framework (lightweight) — install at the QA step                   |
