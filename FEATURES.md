# Ad Placr: features, WordPress to Teme

Source read (read only): the WordPress plugin Ad Placr 2.x by Krafty Sprouts Media, at
`Local Sites\yenimi\app\public\wp-content\plugins\ad-placr`.

Each feature is marked:

- **same in Teme**: behaves the same for the site owner.
- **different in Teme**: the same job, done another way; the cell says how.
- **can't yet**: Teme has no socket for it today; the cell names the missing socket.

Teme version this was written against: 0.8.0-alpha.1 (the contracts carry `@since 0.9.0` for insertions, see FINDINGS F-03).

> **Update, 2026-11-22.** The marks below were made against Teme 0.8.0-alpha.1 and describe what 0.1.0 of the plugin builds. Teme 1.0.0 has since added sockets for several "can't yet" rows: page-level positions (header, footer, sticky and rails, front page and listing pages), admin pages and repeaters (an ad list and statistics), inline-script approval, URL rules, and hooks for other plugins. None of these is built yet. The list is in `FINDINGS.md` ("Re-check against Teme 1.0.0") and the order to build in is in `REPORT.md`.

## Two things in the brief that the WordPress plugin does not have

| Asked about | In the WordPress plugin | In Teme |
|---|---|---|
| `ads.txt` | **Not built.** It is a roadmap line (`roadmap.md`, "ads.txt management") and nothing in `includes/` touches it. | Teme core serves `/ads.txt` itself, from Settings › Site code. A plugin has no socket to write it (handbook, "Content inside articles"). Nothing to port; the plugin's settings page tells people to use that box. |
| Per-post switches | **Not built.** No per-post meta box or switch exists. Ads are chosen by rules on the Ad, never on the post. | Teme has a built-in per-item field, **No inserted content** (`insertions_off`). It turns every plugin insertion off for one article, so every Ad Placr ad respects it with no code. Teme gives more than WordPress here. |

## Settings (WordPress option `ad_placr_settings`)

| # | WordPress feature | Mark | In Teme |
|---|---|---|---|
| S1 | Mobile breakpoint (px, 200 to 1024, default 782) | same | Number setting `mobile_breakpoint`, same bounds. |
| S2 | Tablet breakpoint (px, above mobile, to 1600, default 1024) | same | Number setting `tablet_breakpoint`; kept above the mobile value in code. |
| S3 | Statistics on/off ("analytics_enabled") | same | Boolean setting `analytics_enabled`, off by default, as in WordPress. |
| S4 | Retention days (30, 60, 90, 180, 365) | same | Select setting `retention_days`; a scheduled job prunes. |
| S5 | Settings screen under the Ads menu | different | Teme draws the screen from the manifest: **Settings › Plugin settings › Ad Placr** (checked in the admin: all 105 fields appear, as one flat list; FINDINGS F-17), plus a Tools link declared in the manifest. A plugin cannot draw its own admin page. |
| S6 | System status card (events stored, next cleanup) | can't yet | A read-only value or admin-page socket for plugins. Settings are inputs only. |
| S7 | Settings removed on uninstall (`uninstall.php`) | different | Teme keeps a plugin's saved values when it is disabled; `teme:plugins:remove-data` removes its tables. No uninstall hook for settings. |
| S8 | Filter `ad_placr_mobile_breakpoint`, `ad_placr_tablet_breakpoint`, `ad_placr_analytics_retention_days` | can't yet | Plugins cannot publish hooks of their own for other plugins to use. |

## Ads (custom post type `ad_placr_ad`)

| # | WordPress feature | Mark | In Teme |
|---|---|---|---|
| A1 | Many ads, each its own record, with Add / Edit / Trash | different | A fixed number of ad **slots** (5) inside the plugin's settings group. A plugin cannot add a content type or an admin list screen. To remove an ad, empty its code or set it to Paused. |
| A2 | Ad name | same | Text setting per slot. |
| A3 | Active / Paused (post status is the single authority) | same | `status` select per slot, default Paused. |
| A4 | Ad code (raw ad-network HTML/JS) | different | Textarea per slot. Teme cleans inserted markup: inline `<script>` bodies, `on*` attributes and `javascript:` links are removed. `<script src>`, `<iframe>`, `<img>`, `<style>` and `data-*` survive, and their origins are added to the page's CSP. Snippets that depend on an inline script (AdSense's `push({})`) lose it. See FINDINGS F-08. |
| A5 | Optional mobile ad code | same | `mobile_code` textarea; shown only at or under the mobile breakpoint, by CSS. |
| A6 | Several weighted ad versions (rotation / A/B) | different | Two versions per slot (A and B), each with a weight. WordPress allows any number. |
| A7 | Per-version name and enabled flag | different | Version B is on when it has code; no separate name. |
| A8 | Stable version IDs for statistics | same | `1a`, `1b`: the slot number, then the version letter. |
| A9 | Alignment (none, left, center, right) for in-content positions | same | Select per slot; wrapper CSS. |
| A10 | Private admin notes | different | The `name` field and each setting's description cover it; no notes box. |
| A11 | Duplicate ad, row actions, Activate/Pause, bulk actions | can't yet | An admin list or row-action socket. |
| A12 | Save validation ("add code before activating", "choose a location"), same-location warning | can't yet | A validation or notice socket for plugin settings. The plugin fails closed instead: an incomplete Active slot draws nothing. |
| A13 | Ads list columns (location, status, versions, impressions, clicks, CTR) | can't yet | Same missing admin-page socket as S6. |
| A14 | Preview and embed (shortcode) help boxes | can't yet | No admin screen socket. The setting descriptions carry the help text. |

## Display locations (position registry)

| # | WordPress location | Mark | In Teme |
|---|---|---|---|
| P1 | `in_content_after_paragraph` (after paragraph N) | same | `Position::afterParagraph(N)`. Teme counts top-level paragraphs only, never one inside a list, quote or table. |
| P2 | `in_content_before_paragraph` (before paragraph N) | different | Teme has no "before paragraph". Before paragraph N is after paragraph N-1; before paragraph 1 is `beforeContent()`. |
| P3 | `before_post_content` | same | `Position::beforeContent()`. |
| P4 | `after_post_content` | same | `Position::afterContent()`. |
| P5 | Every N paragraphs; before/after a heading; near the end | **new in Teme** | WordPress Ad Placr has none of these. Teme's `everyParagraphs` (cap 10), `beforeHeading`, `afterHeading` and `nearEnd` are offered as extra locations. |
| P6 | `before_header`, `after_header`, `before_footer`, `after_footer` | can't yet | A plugin socket for the page's head / body-start / body-end. The handbook says core and plugins can write those places, but names no plugin contract. Administrators can paste code in Settings › Site code. |
| P7 | `sticky_footer`, `sticky_left_rail`, `sticky_right_rail` | can't yet | Same socket as P6 (fixed-position chrome outside the article). |
| P8 | `front_page_*`, `blog_index_*`, `archive_*` | can't yet | Insertions are drawn only inside published articles. No listing-page socket for plugins. |
| P9 | `sidebar_widget` and the Ad Placr widget | can't yet | Teme has no widgets. Themes declare **regions**; a plugin cannot fill one. |
| P10 | `manual_shortcode` `[ad_placr ad="123"]` | can't yet | Teme stores content as editor JSON, with no shortcodes (project rule 7). A card or content-filter socket for plugins would do it. |
| P11 | `manual_block` (Gutenberg block) | can't yet | No plugin cards or blocks yet. |
| P12 | Filterable position registry (`ad_placr_positions`) | can't yet | Same as S8. |

## Display rules (the `_ad_placr_targeting` blob)

| # | WordPress rule | Mark | In Teme |
|---|---|---|---|
| R1 | Post types (singular) | same | `types` setting: content-type keys, comma separated. Empty means all. |
| R2 | Page contexts: front page, blog index, archive, search | can't yet | Ads only appear in articles, so a context rule has nothing to choose. |
| R3 | Logged-in / guest | can't yet | `ArticleFacts` says nothing about the visitor, and pages are cached for everyone. A visitor-state socket. |
| R4 | URL contains | can't yet | `ArticleFacts` has no address or slug. |
| R5 | Categories / tags (include lists) | different | `terms` setting: `taxonomy:id` pairs, matched against `ArticleFacts::termIdsByTaxonomy`. IDs are Teme's ULIDs, not numbers. |
| R6 | Schedule (start, end) | same | `start` and `end` text settings, read in the site's time zone (`general.timezone`). |
| R7 | Devices: desktop, tablet, mobile (CSS, no user-agent sniffing) | same | `devices` multiselect; the wrapper hides itself by media query at the two breakpoints. |
| R8 | Paragraph number, slot id | same | `paragraph` number setting; the slot id is the slot number. |
| R9 | Empty rule families fail open | same | Same rule, tested. |
| R10 | Unknown position / malformed active ad fails closed | same | Same rule, tested. |
| R11 | Language, authors | **new in Teme** | `ArticleFacts` carries them; the `languages` setting uses language. Authors are left out. |

## Rendering

| # | WordPress feature | Mark | In Teme |
|---|---|---|---|
| D1 | Wrapper `div.ad-placr` with `data-ad-id`, `data-version-id` | different | Same markup, with Teme's naming rule: every CSS class and id starts with the plugin code, `adplacr-`. |
| D2 | Dual-slot markup (universal + mobile), scoped responsive CSS | same | Same, in an inline `<style>` (kept by Teme's cleaner). |
| D3 | One weighted pick per render | same | Same; random pick is injectable so it can be tested. |
| D4 | Ad code echoed raw (documented exception) | different | Teme cleans every insertion after the plugin hands it over, so the plugin never echoes anything. |
| D5 | Several ads at one location in menu order, then ID | same | Slot number order. |
| D6 | Main query only, not feeds, not admin | same | Teme draws insertions only on the public page of a published article. Previews, feeds, Markdown and the API never carry them. |
| D7 | Page cache safe (no user-agent logic) | same | Same CSS-only design. Teme purges cached article pages when the plugin is enabled, disabled, or its settings are saved (checked: FINDINGS F-10). As in WordPress, a cached page keeps the version it drew, so A/B choice changes when the page is next drawn, not per visitor. |

## Statistics

| # | WordPress feature | Mark | In Teme |
|---|---|---|---|
| T1 | Impression on 50% visible, one per element | same | Small script served by the plugin, `adplacr-tracking.js`. |
| T2 | Click on the ad, one per element | same | Same script. |
| T3 | Storage in a table, opt-in, no personal data | different | Daily counters per slot and version in the plugin's own table `adplacr_daily` (no per-event rows, no IP, no browser details). A second small table, `adplacr_live`, lists which versions may be counted, because a public route cannot read the plugin's settings (FINDINGS F-16). |
| T4 | Daily retention cleanup (cron) | same | Declared job and daily schedule. The handler is tested; I could not make Teme's schedule runner fire it in a test (FINDINGS F-19). |
| T5 | REST route `ad-placr/v1/track`, nonce-checked | different | Teme public route `POST /adplacr/track`: needs the plugin's own request header (so another site's page cannot post to it), a body of at most 2 KB, and a declared throttle of 120 (its unit is Teme's; not tested here, and the handbook does not say). Counts only ad versions that exist and are Active. Teme requires a secret setting behind it (`tracking_key`, unused; FINDINGS F-15). |
| T6 | Actions `ad_placr_impression` / `ad_placr_click` for other trackers | can't yet | Plugins cannot publish hooks (S8). |
| T7 | Dashboard with 7/30/90-day ranges, top ads | can't yet | Admin page socket (S6). The numbers are stored; nothing shows them yet. |
| T8 | Per-ad statistics box on the editor | can't yet | Same. |

## Admin and platform

| # | WordPress feature | Mark | In Teme |
|---|---|---|---|
| M1 | Access limited to `manage_options` | same | Teme: people with the `plugins.manage` permission see and save the plugin's settings. |
| M2 | Nonces on writes | same | Teme's settings Operation checks, records and (for secrets) re-asks for the password. |
| M3 | Translations | different | Teme asks for `__()` and a `lang` folder. Manifest labels are plain strings (FINDINGS F-09). |
| M4 | Updates from GitHub (plugin-update-checker) | different | Teme plugins are Composer packages. Nothing to port. |
| M5 | PHPCS / PHPStan | different | Pint and Pest, as the handbook recommends. |
| M6 | AI/abilities, WP-CLI | not in the WordPress plugin | n/a |
