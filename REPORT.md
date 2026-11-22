# Report: Ad Placr on TemeCMS

Package `krafty-sprouts/adplacr`, code `adplacr`, version 0.1.0. Built against Teme 0.8.0-alpha.1 and updated on 2026-11-22 to Teme 1.0.0 (`requires.teme` is now `^1.0`; see "Where I stopped").
The WordPress plugin was only read. I changed nothing in teme-dev's code. On 2026-11-22 teme-dev's own code was updated to Teme 1.0.0 by another session.

## What works

All of this is covered by tests against the real Teme code. I also watched four things work in the browser pane on the teme-dev site: ads placed in an article, the desktop-to-mobile code swap at 375 px, impressions and a click being counted, and the settings screen. A/B choice, device hiding, the display rules and alignment were tested but not looked at in a browser.

- **Ads inside articles.** Up to five ad slots, each Paused or Active, placed after paragraph N, before paragraph N, before or after the article, every few paragraphs (up to 10 times), before or after the Nth heading of a level, or near the end. The last four are new; WordPress Ad Placr only had paragraph and article locations here.
- **Mobile code, device choice, alignment.** Desktop and mobile code swap by screen width with CSS (checked at 375 px). Hiding on tablet, mobile or desktop, and left, center or right alignment, use the same media-query approach, so a cached page is right for every visitor.
- **Two weighted versions per ad** (A/B). The pick is by weight, one per page draw.
- **Display rules.** Content type, language, categories and tags (by Teme's term IDs), and a start and end time in the site's time zone. An empty rule never excludes anything; an incomplete Active ad draws nothing.
- **Per-article off switch.** Teme's built-in "No inserted content" field is respected with no code of ours.
- **Settings.** Mobile and tablet breakpoints, statistics on/off, retention. Teme draws the screen: Settings › Plugin settings › Ad Placr (all 105 fields appear).
- **Statistics (opt-in).** A small script reports an impression at 50% visible and a click, once each per ad per page. Counts are daily totals per ad and version in the plugin's own table (no visitor data), and a daily job removes old days. Saving the settings keeps the list of countable versions current. Seen working: three impressions and one click counted from a real browser.
- **Teme's own safeguards apply**: the ad code is cleaned by Teme (see below), CSP origins for ad scripts and iframes are added automatically, and cached pages are redrawn after a settings save (checked with the page cache on).

## What does not work yet, and why

Details and the exact missing sockets are in `FEATURES.md` (every WordPress feature, marked) and `FINDINGS.md`.

- **Header, footer, sticky footer, side rails, front page, blog index and archive locations; sidebar widget; shortcode; block.** Teme only gives plugins one place to put content: inside a published article. There is no plugin socket for the page's head, body start or end, for listing pages, widgets or shortcodes.
- **Visitor rules (logged in or guest) and URL rules.** `ArticleFacts` carries neither, and pages are cached for everyone.
- **Inline `<script>` in ad code is removed by Teme.** AdSense's per-unit `push({})` snippet is exactly that, so manual AdSense units draw but do not fill. Auto ads and iframe or script-by-address networks work (F-08).
- **Unlimited ads, duplicate, trash, list screen, save-time warnings, statistics dashboard and per-ad stats.** A plugin cannot add admin pages, lists or repeating fields (F-11); the screen is one flat list of 105 fields (F-17).
- **Hooks for other plugins** (`ad_placr_impression` and the filters): plugins cannot publish hooks.
- **`ads.txt` and per-post switches** do not exist in the WordPress plugin; Teme core already has both.
- **Not verified:** that Teme's schedule runner fires the daily prune job (F-19; the handler and manifest are tested, the wiring is not), the Tools link, the 2 KB body limit and the throttle, and anything on a non-Windows machine.

## Tests

| | Tests | Assertions |
|---|---|---|
| Unit (plain PHP) | 76 | 643 |
| Feature (real Teme site: enable, save settings, publish, request the page, beacon route, tables) | 15 | 72 |
| **Total** | **91 passed, 0 failed** | **715** |

Re-run on 2026-11-22 against Teme 1.0.0: all 91 pass. Two 1.0 changes needed test edits only (enabling now needs `--as=<administrator's email>`, and `JobContext` has a new `personId()` method).

PHPStan level 6 on `src`: no errors. Pint (Teme's own config): applied. The manifest's settings are generated and a test fails if the file is out of date.

## Try it on the teme-dev site

The site is at `C:\Users\kings\Downloads\Projects\teme-plugins\teme-dev` and loads this folder. **Right now the plugin shows "Can't be used" there**: the plugin asks for Teme `^1.0`, and that site's `.env` (shared with the other plugins' sessions, so I left it) still says `TEME_VERSION=0.8.0-alpha.1`. To use it, in teme-dev set `TEME_VERSION=1.0.0` (the handbook's setup page says to, for a copy that is not a tagged release), then:

```powershell
php artisan teme:plugins:disable krafty-sprouts/adplacr
php artisan teme:plugins:enable krafty-sprouts/adplacr --agree --as=admin@example.test
```

Run those in `teme-dev`. The `--as` administrator (created by `php dev/reset-test-admin.php` below) is who the daily statistics job runs as. The plugin's saved settings and statistics are kept.

1. Start the database stack (it uses other ports than Teme's own compose file; see D-02):
   ```powershell
   docker compose -f dev/docker-compose.yml up -d
   ```
2. Serve the site: `powershell -File dev\serve.ps1`, then open http://127.0.0.1:8099/.
3. Sign in at `/admin/login`. For a test administrator with a fresh throwaway password, run `php dev/reset-test-admin.php` and use what it prints.
4. Open **Settings › Plugin settings › Ad Placr**, set an ad to Active, paste some code, save, and open `/probe-article/` (a six-paragraph demo article).
5. Run the tests: `powershell -File dev\test.ps1`.

## Outside this folder (all in teme-dev or Docker; nothing was pushed anywhere)

- Cloned Teme into `teme-dev`, ran `composer install`, `npm ci`, `npm run build`, `php artisan migrate`; edited its `.env` (not tracked): `TEME_VERSION`, an absolute `TEME_PLUGIN_PATHS`, `DB_PORT`, `APP_URL` (F-01, F-02, F-05).
- Started three containers named `adplacr-dev-*`. Another Teme checkout's containers (`teme-join-*`) were left alone.
- Deleted one disabled plugin row in the teme-dev database, left by the package rename (D-11, F-06).
- Created demo data on the dev site: a `dev@example.test` editor, the test administrator, and the demo article.
- One Sonnet subagent checked my handbook-gap claims against the handbook text; its corrections are in FINDINGS.

## For the owner

- `author_url` is left out until you give the address.
- Five ad slots and two versions per ad is a limit of Teme's settings screen, not of the code; change `Declaration::SLOTS` and run `php build/manifest.php` to change it (D-05).
- The biggest items to raise with the Teme maintainers: a way to add head, footer and listing-page content (F-18, P6 to P8), route handlers that can read settings (F-16), inline-script consent (F-08), admin pages or repeaters (F-11), and the handbook fixes in F-01, F-03, F-05, F-07 and F-12 to F-14.

## Where I stopped

0.1.0 is finished as far as Teme 0.8 allowed, and the tests pass on Teme 1.0.0. I stopped there on purpose. Teme 1.0.0 arrived after the build and added sockets that remove most of the "can't yet" rows, so building more now means choosing a direction. I did not start it.

**Not done, in the order I would do it** (one thing at a time, test first). Details and the 1.0 pages to read are in `FINDINGS.md`, "Re-check against Teme 1.0.0".

1. **Make the dev site run it.** Set `TEME_VERSION=1.0.0` there and re-enable with `--as` (see above), then open the settings screen and a demo article once more. Also read the 1.0 setup page: the reviewer reports that development now needs `schedule:work` and `queue:work` running for scheduled jobs (I have not run them).
2. **Move the plugin's tests to the testing kit.** 1.0 gives `teme/testing` (`TemeTestCase`, `plugin()->enable()`, `runSchedules`, `fetch`). That should replace my hand-made helpers, and `runSchedules` finally lets the daily prune job be tested through the schedule (F-19 is fixed).
3. **Drop the snapshot table.** If route handlers can really read the settings (`public-routes.md` says yes, `tables.md` says no: F-20), `adplacr_live` and the `SettingsChanged` listener can go; try it and see.
4. **Page-level locations** (`pages.insert`): header, footer, sticky footer and rails, front page, blog index and archive. These are the biggest missing part of the WordPress plugin.
5. **An admin area** (`contributes.area`): an ad list with repeater forms instead of five numbered slots, duplicate, and statistics charts from the stored daily counts. Do this after step 4, because some of 1.0's admin panels may still be incomplete ("still to come" in `settings.md`).
6. **Inline-script approval** for AdSense-style code, once Teme draws the approval control (the page says it does not yet).
7. **URL rule** from `path`, and **hooks for other plugins** (`contributes.hooks`).

Still not possible on 1.0.0: a shortcode or block (no plugin cards), a logged-in/guest rule (pages are cached for everyone), and a sidebar widget (only region positions).

**Open checks:** the Tools link in the admin, the 2 KB body limit and the throttle, A/B choice and alignment in a browser, anything outside Windows.

**Housekeeping for whoever continues:** this folder is pushed to the private repo `Krafty-Sprouts-Media-LLC/teme-adplacr` on `main`. Push only to that remote, and only when the owner asks. `AGENTS.md` has the commands. The memory note `adplacr-plugin-state` points here.
