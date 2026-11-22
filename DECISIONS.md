# Decisions

Each choice made while the owner was away, and why. Newest last.

**D-01. Names.** Composer name and manifest `name`: `krafty-sprouts/adplacr` (publisher name decided by the owner). Plugin `code`: `adplacr`, so tables are `adplacr_*`, the settings group is `plugin.adplacr`, and every CSS class, id and JS global starts with `adplacr`. PHP namespace `KraftySprouts\AdPlacr`. Manifest `author` is "Krafty Sprouts Media, LLC"; no `author_url` until the owner gives the address.

**D-02. Isolated database stack.** teme-dev's own `docker-compose.yml` needs ports 5432, 5433 and 6380, which another Teme checkout's containers (`teme-join-*`) already hold on this machine. I did not touch those containers. `dev/docker-compose.yml` runs the same three services on 5442, 5443 and 6390 under the project name `adplacr-dev`. teme-dev's `.env` sets `DB_PORT=5442`; the tests get `DB_PORT=5443` and `REDIS_PORT=6390` from the environment (phpunit does not override variables that are already set). No teme-dev code changed. See F-02.

**D-03. `TEME_VERSION=0.8.0-alpha.1` in teme-dev's `.env`.** A fresh checkout reports Teme 0.1.0, and the plugin asks for `^0.8`, so it could not be enabled. The value is the version in teme-dev's own CHANGELOG. See F-01.

**D-04. Absolute path in `TEME_PLUGIN_PATHS`.** The handbook's relative example (`../my-plugin`) works in the console but not under a web server. See F-05.

**D-05. Five ad slots.** The plugin cannot add a content type or an admin list screen (F-11). The only editing surface is the plugin's declared settings, which are flat fields. So an "ad" is a numbered slot of about 20 settings, five slots, generated from one definition (`build/manifest.php`) so they cannot drift. Five is enough for typical sites (the WordPress demo data uses fewer) and keeps the settings page usable; changing the constant and regenerating adds more.

**D-06. A scaffold class existed before its tests.** Teme scans the plugin class when enabling, so a class had to exist to enable the plugin on teme-dev and learn what Teme accepts. It is a do-nothing `AdPlacr` class. Every behaviour added to it afterwards starts from a failing test, and no probe code was kept.

**D-07. Two versions per slot, not unlimited.** Same reason as D-05. Versions A and B, each with a weight; B is active when it has code.

**D-08. Statistics are daily counters, not event rows.** Table `adplacr_daily` (day, slot, version, impressions, clicks). WordPress stores one row per event. Counters give the same totals, hold no per-visit data, and need no personal-data handling. The dashboard cannot be shown yet (FEATURES T7), so storing less is the safer default.

**D-09. Visitor-state, URL and listing-page rules are left out.** `ArticleFacts` carries no viewer, address or page kind, so these cannot be evaluated honestly. They are marked "can't yet" in FEATURES.md rather than faked.

**D-10. Tests run through teme-dev's own Pest binary.** The handbook says plugin tests run inside Teme but not how a folder outside it is picked up (F-12). The plugin has its own `phpunit.xml` and `tests/Pest.php`, and `dev/test.ps1` runs `teme-dev\vendor\bin\pest` against them. Pure logic gets plain unit tests; the Teme integration tests extend Teme's own `Tests\TestCase`, as the handbook's example test does.

**D-12. Statistics read a snapshot table, not the settings.** A public route's handler cannot read settings (F-16), so `adplacr_live` lists the ad versions that may be counted and `SettingsChanged` rewrites it. Side benefit: with statistics off the table is empty, so the route can never write, whatever a visitor sends. The beacon also needs an unused secret setting, `tracking_key`, because Teme requires one for a verified POST (F-15).

**D-13. No Subresource Integrity on the tracking script tag or in test ad code.** The editor's security hints suggest `integrity=` on external scripts. The tracking script is served from the site's own address (`'self'`), and an administrator's pasted ad-network scripts change without notice, so a fixed hash would break the ad. The script tag has neither `integrity` nor `crossorigin`.

**D-14. Teme 1.0.0 (2026-11-22): `requires.teme` is now `^1.0`.** After this plugin was first built, the teme-dev copy moved to Teme 1.0.0. The contracts the plugin uses are now `@since 1.0.0`, and the handbook's rule is to require at least the highest `@since`, so the manifest asks for `^1.0` (it was `^0.8`). Tests set `teme.version` to `1.0.0`. Two 1.0 changes touched the tests, not the plugin's code: enabling a plugin that has scheduled work now needs `--as=<administrator's email>` (the tests create the administrator first and pass it), and `JobContext` gained `personId()` (the test's fake implements it).

**D-15. The shared dev site was left as it is.** teme-dev's `.env` is shared with other plugins' sessions and says `TEME_VERSION=0.8.0-alpha.1`, so on that site this plugin now shows "Can't be used" (needs `^1.0`). The fix is in REPORT.md; I did not change the shared `.env` or re-enable the plugin there.

**D-11. Renaming the package (owner's note).** The plugin had been enabled on teme-dev as `kraftysprouts/adplacr`. Steps taken: disabled it, renamed, enabled `krafty-sprouts/adplacr` (consent screen accepted with `--agree`). Teme kept the old disabled record, and it blocked the new name because the code `adplacr` was already taken (F-06). There is no command to forget a plugin, so I deleted that one disabled row from the teme-dev database. Nothing else was touched.
