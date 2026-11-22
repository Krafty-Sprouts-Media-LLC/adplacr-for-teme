# Findings

Every place the handbook was missing, wrong or unclear, and every missing socket ("wall"). Each says what I expected and what I did instead. Teme itself was never changed.

Sources used: the handbook (`docs/guides/developers/`), `docs/reference/`, `packages/teme-contracts/`, and (for the examples the testing page points to) `tests/Fixtures/Plugins/` and `tests/Feature/Guides/Developers/ExamplesTest.php`. Where I had to look further to get unstuck, the entry says so.

Teme under test: `main` at commit 964c7ea, CHANGELOG head 0.8.0-alpha.1.

## Re-check against Teme 1.0.0 (2026-11-22)

Everything below this section was written against Teme 0.8.0-alpha.1 and is kept as the record of what the first build ran into. Teme 1.0.0 was released after it and the teme-dev copy was updated, so a Sonnet reviewer re-read the 1.0.0 handbook, references and contracts for each finding. I checked the key claims myself (F-01, the page-insertion socket, the testing kit, F-16). Nothing in the plugin was changed for these beyond tests and `requires.teme` (DECISIONS D-14).

| Finding | Teme 1.0.0 |
|---|---|
| F-01 `TEME_VERSION` | Fixed: the setup page now says to set it, and the contracts reference names the version. |
| F-02 ports | Fixed: the compose file reads `TEME_DB_PORT`, `TEME_TEST_DB_PORT`, `TEME_VALKEY_TEST_PORT`. |
| F-03 version numbers | Fixed: every `@since` is 1.0.0 and the page says "from Teme 1.0.0". |
| F-04 plugin autoloading | Unchanged: still not written down. |
| F-05 relative plugin path | Fixed (changelog): paths resolve against the project and a skipped plugin says why. The page still shows `../my-plugin` without stating the rule. |
| F-06 forgetting a plugin | Partly: the changelog has "Forget a plugin whose code is gone"; the handbook does not say whether it covers a renamed package. |
| F-07 stub pages, route and job keys, migrations | Fixed: public routes, jobs and tables pages are full, and the route keys are in the manifest reference. |
| F-08 inline scripts | Partly: an administrator can approve inline scripts (`InlineScripts`, `scripts: true`), but the page says the approval control is not drawn on the form yet. |
| F-09 translating manifest strings | Fixed: manifest words translate from the plugin's `lang/<locale>.json`. |
| F-10 cache purge | Fixed in the handbook ("needs no `notify()`"). |
| F-11 admin pages, repeaters | Socket exists: `contributes.area` with figures, chart and table blocks; repeater settings. Some panels are "still to come". |
| F-12 plugin tests | Fixed: the `teme/testing` kit (`TemeTestCase`, `plugin()->enable()`, `runJob`, `runSchedules`, `fetch`) and `php artisan test ../my-plugin/tests`. |
| F-13 `PluginFixtures` on Windows | Not re-checked; the handbook no longer points at it. |
| F-14 per-article switch ID | Fixed: the page and fields reference give the ID. |
| F-15 secret for a verified POST | Fixed: a `custom` verifier's `secret` is optional. |
| F-16 route handlers and settings | Fixed in `public-routes.md` ("built as your plugin's class"), but `tables.md` still says a handler "is built with none of your plugin's doors" (F-20). |
| F-17 `visibleWhen` | Fixed: supported, plus `settingsSections` and `repeater`. |
| F-18 smaller gaps | Mostly fixed. The capability table in `concepts/manifest.md` still omits `pages.insert` and `fediverse.*`. |
| F-19 when a schedule is due | Fixed: `jobs.md` says it, and `runSchedules($moment)` fires one in a test. |

Walls from FEATURES.md that now have a socket in 1.0.0 (none of these is built yet):

- Header, footer, sticky footer and side rails: `PageInsertionContributor` with `PagePosition::head()`, `bodyStart()`, `bodyEnd()` and `fixed(edge, width)`, capability `pages.insert` (`plugins/page-insertions.md`). Rails are 160 or 300 wide.
- Front page, blog index and archive: `PageFacts` plus `afterItem(n)` and `everyItems(n, max)`; the theme must draw the `between-items` component.
- Sidebar widget: partly, `PagePosition::region()` around a theme's region; there are still no widgets.
- Admin ad list, repeater form, duplicate and statistics charts: `contributes.area` and repeater settings.
- URL rule: `path` on `ArticleFacts` and `PageFacts`.
- Hooks for other plugins: `contributes.hooks`.
- Still missing: shortcode and block (no plugin cards), logged-in/guest rule (pages are cached for everyone), a general cache purge.

New small findings on 1.0.0:

**F-20. `tables.md` contradicts `public-routes.md` about route handlers and doors.** `tables.md` says a public route's handler "is built with none of your plugin's doors"; `public-routes.md` says it is built as the plugin class is, with the settings reader or `Jobs` if it asks. My F-16 workaround (a snapshot table kept by a `SettingsChanged` listener) is therefore no longer needed on 1.0.0, if `public-routes.md` is the correct page.

**F-21. 1.0 adds a console step that old instructions do not have.** A plugin with `schedules` is not enabled from the console unless `--as=<administrator's email>` is given, and `Jobs::queue()` refuses when there is no person (a route handler cannot queue a job). This is documented in `jobs.md`, but the setup and first-plugin pages' enable commands do not mention it, and a plugin enabled earlier (as this one was) shows "Its schedules don't run" until it is disabled and enabled again.

## Handbook gaps (written against 0.8.0-alpha.1)

**F-01. A fresh dev checkout reports Teme 0.1.0, so the handbook's own first plugin cannot be enabled.**
Expected: after "Set up a development site", `teme:plugins:enable` works for a plugin asking `^0.8`, as the first-plugin page shows. Got: `It needs Teme ^0.8; this is Teme 0.1.0.` The version comes from `TEME_VERSION`, which no guide, reference page or `.env.example` mentions (I found it in `config/teme.php`). The testing page says only "set Teme's version to one the manifest's `requires.teme` accepts", without saying how. Did: set `TEME_VERSION=0.8.0-alpha.1` in teme-dev's `.env`. Fix: say so on the setup page, or default the checkout's version to its CHANGELOG head.

**F-02. The setup page assumes ports 5432, 5433 and 6380 are free.**
Expected: `docker compose up -d` just works. Got: it would collide with any other Postgres or another Teme checkout. No override is documented, and `phpunit.xml` hard-codes 5433 and 6380. Did: ran the same services from `dev/docker-compose.yml` on other ports and passed `DB_PORT`/`REDIS_PORT` to the tests through the environment (works because phpunit does not override set variables). Fix: read the ports from `.env`, or document the override.

**F-03. Which Teme version does the insertion socket need?**
`plugins/insertions.md` says the contracts exist "from Teme 0.8.0". Every contract class under `Insertions/` says `@since 0.9.0`. The hooks page says to set `requires.teme` to at least the highest `@since` you use, which would be `^0.9`, a version that does not exist yet (CHANGELOG head is 0.8.0-alpha.1). Did: `requires.teme` is `^0.8`, which the running 0.8.0-alpha.1 accepts and which works. Fix: make the page and the tags agree, and say whether pre-releases satisfy `^0.8`.

**F-04. How a plugin's classes get loaded is not written down.**
Expected: a line saying Teme reads the plugin's `composer.json` PSR-4 map. Spent a while on this because the handbook's own test does `require_once` on the plugin file, which looks like Teme does not autoload. It does (no change to teme-dev's `composer.json` was needed). The rules page also says Teme "refuses a package that has Composer load files on every request" without saying what is allowed. Fix: one sentence on the first-plugin page.

**F-05. A relative `TEME_PLUGIN_PATHS` entry works in the console and silently fails on the web.**
The setup page shows `../my-plugin`. Paths resolve against the process's working directory: the repository root for `artisan`, but `public/` under `php -S`, Herd or any web server. So `teme:plugins` says **Enabled** while every page request skips the plugin with the state `code_gone`, and nothing is shown to the person (no log line, no banner, no message on the Plugins page that I could find). Took the longest to find. Did: absolute path in `.env`. Fix: resolve against the project root, and report a skipped enabled plugin where people look.

**F-06. No way to forget a renamed or abandoned plugin.**
After renaming the package, the old disabled record kept the code `adplacr`, so `teme:plugins:enable` for the new name failed with `The plugin code "adplacr" is already used by kraftysprouts/adplacr.` `teme:plugins:remove-data` only drops tables and says `has no tables to remove`. Did: deleted the one row from the dev database. Fix: a `teme:plugins:forget` command, or let a package of the same code replace a disabled one.

**F-07. The pages for public routes, jobs and writing content are stubs, and the manifest reference omits keys that exist.**
`public-routes.md` and `content-write.md` are stubs ("lands with ...", and the first says plugins "have no public route socket"), and `jobs.md` is a stub that says not to declare jobs, while `concepts/manifest.md` documents `jobs` and `schedules` in full: the pages contradict each other. The rules page describes the route fences ("A POST must name how it is verified", the 1 MB to 10 MB body limit) but never gives key names. The generated manifest reference lists `routes[].id/method/path/handler` only. The keys `verify` (`secret`, `hmac`, `custom`), `throttle`, `maxBody`, `cache` and `when`, the plugin tables' `database/migrations` folder, and that plugin code may use the `DB` facade, appear only in test fixtures under `tests/Fixtures/Plugins/`. (A Sonnet reviewer checked this finding against the handbook and corrected my first wording.) I used those fixtures as the examples the testing page points to. Did: built statistics from the fixtures. Fix: write the pages, and generate the route keys into the reference (the schema allows extra route properties, so the generator cannot see them).

**F-12. The testing page does not say how a plugin's own tests get picked up.**
The setup page says "Your plugin's tests run inside this copy of Teme", and the testing page gives steps (point `teme.plugin_paths`, set the version, enable, publish) but nothing about how a folder outside Teme is run. Teme's `phpunit.xml` lists only `tests/Unit`, `tests/Feature`, `tests/Architecture` and `tests/Browser`, and the helpers the example uses (`lifecycleDraft`, `listsPublish`) live in Teme's own `tests/Pest.php`. Also, Pest reads `tests/Pest.php` from the **site** it runs from, never from the plugin, so my first attempt at shared helpers and a `pest()->extend(TestCase::class)` in the plugin's own `Pest.php` was silently ignored (every test failed with `Call to undefined function`). Did: helpers load from the PHPUnit bootstrap, and Feature tests opt in with `uses(Tests\TestCase::class)` (D-10). Fix: show a plugin-side `phpunit.xml` and bootstrap in the handbook, say that `Pest.php` is the site's, and say which helpers are meant for outsiders.

**F-13. `PluginFixtures::use()` mangles an absolute path on Windows, so the handbook's own example test cannot run there.**
The testing page points at the Examples test, which calls `PluginFixtures::use($path)` with an absolute `base_path(...)`. The helper prepends its own `tests/Fixtures/plugins/` folder unless the path starts with `/`, so on Windows the result was `...\tests\Fixtures\plugins\C:\Users\...\adplacr` and discovery found nothing (the enable command said `No plugin is named "krafty-sprouts/adplacr"`). Did: set `config(['teme.plugin_paths' => [...]])` and flush the registry, which is what the testing page's prose says to do anyway. Fix: use `Path::isAbsolute`-style detection, and have the page show the config line rather than the fixture helper.

**F-14. The per-article switch is documented by handle, but saving takes its ID, and the generated reference does not list it.**
`plugins/insertions.md` names the field **No inserted content**, handle `insertions_off`. Saving a draft through `SaveDraft` with `['insertions_off' => true]` fails with `These fields do not exist on a post: insertions_off`; the field is stored by its ID, `01K5Z0F1ED00000000000000XF`. `docs/reference/fields.md` (generated) lists the Post and Page fields with IDs but not this one, so an outsider cannot find the ID from the reference. I found it by searching Teme's source, which an outsider following the rules should not need to do. With the ID, the switch works as documented: no contributor is asked and no ad is drawn (tested). Fix: regenerate the fields reference with it, and say on the insertions page that stored values use the ID.

**F-15. Every verified public POST must name a secret setting, even when there is no secret to keep.**
The ad-seen beacon comes from anonymous visitors' browsers, which hold no secret. The `verify` block of a route needs `"secret": "<key of a declared secret setting>"` for every kind, including `custom`; without it the request fails with a 500 (`[plugin.adplacr.] is not a secret setting`), found only by reading the exception. Did: declared a `tracking_key` secret setting that the plugin never uses (Teme passes the verifier `''` while it is unset, and the request goes through), and said so in its description. A failed verification answers 401. Fix: let `custom` verifiers omit `secret`, and document the answer codes.

**F-16. A public route's handler is built with no arguments, so it cannot read the plugin's settings.**
Expected: a handler may ask for `SettingsReader` like the plugin class does (the rules say a constructor "may ask only for what Teme hands plugins"). Got: a handler whose constructor asks for `SettingsReader` makes every request answer 500, with no fault recorded against the plugin (`plugins.faults` stayed `{}`) and no message, so the cause was found by tracing. A handler with no constructor works. So the tracking route could not check "are statistics on, and is this a real ad version?" from the settings. Did: the plugin listens to `SettingsChanged` (no capability needed), and on each save of its group rewrites its own table `adplacr_live` with the countable ad versions (empty while statistics are off); the handler reads that table. It works and is tested, but it is a copy of the settings that can go stale if the settings change by a route that sends no `SettingsChanged` (an import, a restore). Socket wanted: the same doors for route handlers as for the plugin class, or a way to hand the plugin instance to its handlers.

**F-17. `visibleWhen` is accepted in a plugin's settings but has no effect, and the admin screen is one flat list.**
`Teme\Contracts\Settings\Setting` has `visibleWhen()` to show a field only while another has a value, and the settings page tells people to read the contract, so I declared it for the paragraph, repeat and heading fields (paragraph number only for paragraph locations, and so on). The settings page points to the generated manifest reference for every key, and that does not list it; the schema does not forbid it (extra keys are allowed), so Teme accepted the manifest and the plugin started. On the real admin screen (`/admin/settings/plugin.adplacr`) every field stayed visible whatever the location was (checked in the browser, caches cleared). Did: removed it again rather than ship a setting that does nothing. The screen draws all 105 fields in one list with no headings, so a five-ad plugin is long to scroll. Fix: support `visibleWhen` and a group heading for plugin settings, or reject unknown keys so authors learn it is unsupported.

**F-18. Smaller gaps a reviewer found that matter to an ad plugin.**
(1) `concepts/manifest.md`'s capability table omits `routes.public` (and `import.wordpress`, `fediverse.*`), though the schema, the rules page and the consent screen use them. (2) `plugins/insertions.md` never says article pages are page-cached, so an author cannot tell that a weighted random pick or per-device choice is frozen into the cached page; my CSS-based mobile switching is right for that reason, but I only learned the cache behaviour by testing (F-10). (3) The same page lists what the cleaner removes and keeps `<script src>`, `<iframe>` and `<img>`, but does not say whether `<style>`, `<ins>` or `data-*` survive; I tested and they do (F-08). (4) No page says how a plugin ships migrations, that enabling runs them, or what `owns.tables` does beyond the naming rule (F-07). (5) `jobs.md` documents none of `JobHandler`, `JobContext` or `Jobs`, which the contracts define.

**F-19. I could not make the daily job fire in a test, and nothing says when a schedule is "due".**
The manifest declares `{"job": "prune", "every": "day"}`, Teme accepts it, and the handler works when called (tested). I then ran `teme:plugins:run-due-schedules` in a test after a first baseline call and after travelling two days, and to the next midnight: each time `No plugin schedules were due.` The handbook's `every` list ("`day`") does not say when a day starts, what the first run is, or whether the command is meant to be run by hand. So the **wiring from schedule to handler is untested**; only the handler and the manifest are. Fix: say when each `every` value is due and how a test can fire one.

## Walls (missing sockets)

**F-08. Inline `<script>` in ad code is removed.**
Expected (from WordPress): paste any ad-network snippet. Got: the cleaner keeps `<script src>` (and adds its origin to the CSP: confirmed, it appeared in `script-src`), `<iframe>` (added to `frame-src`), `<ins>`, `<style>` and `data-*`, and removes `onclick`, `javascript:` links and any `<script>` without `src`. The removals are documented and sensible (what is kept beyond `<script src>`, `<iframe>` and `<img>` is not: F-18), but AdSense's per-unit `(adsbygoogle = window.adsbygoogle || []).push({})` is exactly such a script, so manual AdSense units draw but never fill. Auto ads (one `src` script) and iframe-based networks work. Did: nothing to work around; noted in FEATURES A4 and the setting description. Socket wanted: a way for an administrator to agree to a named inline script (hash into the CSP), as Site code does.

**F-09. Manifest strings cannot be translated.**
The rules say every word a person reads goes through `__()`. Setting labels, descriptions and the plugin label live in `teme-plugin.json`, which is data. Nothing says how a `lang` folder maps onto them. Did: English strings in the manifest; the plugin draws no text of its own on the public site.

**F-10. (Checked, not a wall.) Does a saved ad change show with the page cache on?**
I feared not: the handbook says to call `InsertionsChanged::notify()` "inside an Operation" when a plugin's rules change, and a plugin cannot define an Operation. Tested on the dev site with `PAGE_CACHE_ENABLED=true`: the article went `X-Teme-Cache: MISS`, then `HIT`, and after saving the plugin's settings, `MISS` again with the edited ad text. Article pages carry the tag `settings` (and `insertions`), and the settings save purges it. So a plugin whose rules live in its own settings needs no `notify()` at all. Fix: say so on the insertions page ("When your own rules change ..."), which now reads as if every plugin must call `notify()`.

**F-11. A plugin cannot add an admin screen, a list, a custom record type or a repeating field.**
The `contributes` keys are `settings`, `admin` (a Tools link to the plugin's own settings or an `https://` address) and `csp`. The contracts define richer setting types (`pairs`, `hours`, `person`, `media`, `timezone`), but the manifest's `type` list has eleven and none repeats. So "many ads, each with its own rules" has to be a fixed set of numbered slots (D-05), and there is nowhere to show statistics or run actions such as Duplicate. Socket wanted: a repeater setting type, or admin pages declared by a plugin.
