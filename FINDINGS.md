# Findings

Every place the handbook was missing, wrong or unclear, and every missing socket ("wall"). Each says what I expected and what I did instead. Teme itself was never changed.

Sources used: the handbook (`docs/guides/developers/`), `docs/reference/`, `packages/teme-contracts/`, and (for the examples the testing page points to) `tests/Fixtures/Plugins/` and `tests/Feature/Guides/Developers/ExamplesTest.php`. Where I had to look further to get unstuck, the entry says so.

Teme under test: `main` at commit 964c7ea, CHANGELOG head 0.8.0-alpha.1.

## Handbook gaps

**F-01. A fresh dev checkout reports Teme 0.1.0, so the handbook's own first plugin cannot be enabled.**
Expected: after "Set up a development site", `teme:plugins:enable` works for a plugin asking `^0.8`, as the first-plugin page shows. Got: `It needs Teme ^0.8; this is Teme 0.1.0.` The version comes from `TEME_VERSION`, which the setup page never mentions (it is not in the `.env.example` lines the page copies, only in a comment further down). Did: set `TEME_VERSION=0.8.0-alpha.1` in teme-dev's `.env`. Fix: say so on the setup page, or default the checkout's version to its CHANGELOG head.

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
`public-routes.md`, `jobs.md` and `content-write.md` say "lands with ...". The generated manifest reference lists `routes[].id/method/path/handler` only. The keys `verify` (`secret`, `hmac`, `custom`), `throttle`, `maxBody`, `cache` and `when`, the plugin tables' `database/migrations` folder, and that plugin code may use the `DB` facade, appear only in test fixtures under `tests/Fixtures/Plugins/`. I used those fixtures as the examples the testing page points to. Did: built statistics from the fixtures. Fix: write the pages, and generate the route keys into the reference (the schema allows extra route properties, so the generator cannot see them).

**F-12. The testing page does not say how a plugin's own tests get picked up.**
"Your plugin's tests run inside this copy of Teme" is all it says. Teme's `phpunit.xml` lists only `tests/Unit`, `tests/Feature`, `tests/Architecture` and `tests/Browser`, and the helpers the example uses (`lifecycleDraft`, `listsPublish`) live in Teme's own `tests/Pest.php`. See D-10 for what I did. Fix: show a plugin-side `phpunit.xml` and which helpers are meant for outsiders.

## Walls (missing sockets)

**F-08. Inline `<script>` in ad code is removed.**
Expected (from WordPress): paste any ad-network snippet. Got: the cleaner keeps `<script src>` (and adds its origin to the CSP: confirmed, it appeared in `script-src`), `<iframe>` (added to `frame-src`), `<ins>`, `<style>` and `data-*`, and removes `onclick`, `javascript:` links and any `<script>` without `src`. That is documented and sensible, but AdSense's per-unit `(adsbygoogle = window.adsbygoogle || []).push({})` is exactly such a script, so manual AdSense units draw but never fill. Auto ads (one `src` script) and iframe-based networks work. Did: nothing to work around; noted in FEATURES A4 and the setting description. Socket wanted: a way for an administrator to agree to a named inline script (hash into the CSP), as Site code does.

**F-09. Manifest strings cannot be translated.**
The rules say every word a person reads goes through `__()`. Setting labels, descriptions and the plugin label live in `teme-plugin.json`, which is data. Nothing says how a `lang` folder maps onto them. Did: English strings in the manifest; the plugin draws no text of its own on the public site.

**F-10. A plugin cannot ask for its cached pages to be purged after its settings change. (To verify; see the note.)**
`InsertionsChanged::notify()` must be called "inside an Operation", and a plugin cannot define an Operation. `SettingsChanged` arrives after the save, outside one. Core purges the `insertions` pages when a plugin is enabled or disabled, but not when its saved settings change. With the page cache on, a saved ad edit would not show until the pages expire (default one hour). The local site has the page cache off (`APP_ENV=local`), so this is not yet tested; see REPORT.md for the result. Socket wanted: purge on `SettingsChanged` for plugins with `content.insert`.

**F-11. A plugin cannot add an admin screen, a list, a custom record type or a repeating field.**
The `contributes` keys are `settings`, `admin` (a Tools link to the plugin's own settings or an `https://` address) and `csp`. The contracts define richer setting types (`pairs`, `hours`, `person`, `media`, `timezone`), but the manifest's `type` list has eleven and none repeats. So "many ads, each with its own rules" has to be a fixed set of numbered slots (D-05), and there is nowhere to show statistics or run actions such as Duplicate. Socket wanted: a repeater setting type, or admin pages declared by a plugin.
