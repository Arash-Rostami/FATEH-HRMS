# Icon Font Pipeline (`resources/icons/`)

Everything about the Material Symbols icon font lives in this one folder.

```
resources/icons/
├── build.mjs        # build step: scans the repo, cuts the font, writes generated/
├── logos.mjs        # tenant sibling-app logos -> identical 160x160 WebP in img/<tenant>/sibling/optimized/ (see config/tenantPattern.md)
├── guard.js         # tiny browser safety net (started by app.js, auth.js, core/filament.js)
├── safelist.json    # icons that must always be included (generic UI + DB-stored names)
├── __tests__/       # guard.test.js (browser safety net + report) + logos.test.js (logo build, real sharp)
└── generated/       # GITIGNORED, rebuilt every build — never edit, never upload
    ├── material-symbols-rounded.woff2       # light font, ≈225 KB (~840 icons)
    ├── material-symbols-rounded-full.woff2  # complete font, 5 MB, only fetched when the guard asks
    └── rounded.css                          # both @font-face rules + the .ms-full rule; first line `/* icons <hash> */` doubles as the up-to-date marker
```

## How it works

`npm run build` and `npm run dev` first run `node resources/icons/build.mjs` (`prebuild`/`predev`, also `npm run icons`).

1. **The font is the judge of what is an icon** — never the package's `index.d.ts`, which drops legacy aliases such as `expand_more`/`auto_awesome`/`expand_less` that the font still ligates (using it left the top bar raw on every page and made the guard load the 5 MB font everywhere; fixed 2026-11-03). The script scans `resources/views`, `resources/js`, `resources/assets/js`, `app`, `lang`, `config`, `routes` for every `[a-z0-9_]` token and keeps those that `fontkit` shapes to ONE private-use glyph, then unions `safelist.json`. `database/` is deliberately NOT scanned (the PaaS upload skips it, so local and server builds must see the same sources) — put icon names that exist only in the database in `safelist.json`.
2. `subset-font` cuts the 5 MB font to the icon set (`noLayoutClosure`, `GRAD`/`opsz` pinned; `FILL` 0..1 and `wght` 100..700 kept).
3. The result is re-opened and verified. **The build FAILS** if any name no longer shapes to one glyph or a sentinel icon (`expand_more`, `auto_awesome`, `keyboard_arrow_down`, `close`, `search`) is missing. It only WARNS about static text inside `.material-symbols-rounded` spans that is not an icon in any font (a raw-text bug in the view — fix the name; fixed 2026-11-03: `rectangle_stack`→`stacks` in the Project guide, `person_lock`→`lock_person` in the Reservation guide).
4. Unchanged icons ⇒ a ~1 s no-op (the hash in the first line of `rounded.css` is compared). `node resources/icons/build.mjs --report` only prints counts.
5. **Vite bundles it — nothing is copied by hand.** `resources/css/app.css` (user panel + auth) and `resources/css/core/filament.css` (admin) both `@import` the generated `rounded.css`; Vite resolves the two `url()`s and emits the fonts as content-hashed assets (`/build/assets/material-symbols-rounded-<hash>.woff2`). `meta-tags.blade.php` preloads the light font through `Vite::asset('resources/icons/generated/material-symbols-rounded.woff2')` (read from the manifest, so it always matches the css url). The full font is only a hashed file on disk until `.ms-full` makes the browser fetch it.

## Cache safety

The server serves `/build/assets/` with `max-age=31536000, immutable` (`perf.conf`, see `temp/winterra/paas-setup.md`). That is safe here by construction: the fonts and css are Vite-hashed, so a new build = new file names. Do NOT reintroduce hand-copied fixed-name font files or `?v=` plumbing (that was the 2026-11-03 first design and a stale-cache hazard).

## The safety net (`guard.js`)

Does nothing at load. Once the browser is idle and `document.fonts.ready` has settled it checks `.material-symbols-rounded` elements and marks ONLY an element whose text looks like an icon name and is visibly raw (the width of its TEXT (measured with a Range, not the element box, so a stretched watermark icon is not fooled) > 2.2× its font size) with `ms-full` — that element alone switches to the full font, which the browser then downloads and caches by itself (`font-display: block`). Never the whole page (it would make every correct icon wait on 5 MB). **Reporting (2026-11-03):** the first time it marks a name on a page it also `POST`s it once to `/icon-miss` (`IconMissController`, public so login/auth pages can use it; `throttle:20,1`; CSRF header from the `csrf-token` meta; `fetch` with `keepalive`), and the server validates `^[a-z0-9_]{1,60}$` (422 otherwise), dedupes per name for a day (`Cache::add`) and writes ONE `laravel.log` WARNING «Icon missing from the font subset» with the icon and the page path — so a miss is never silent in production. Fix it by adding the name to `safelist.json` (if it only lives in the DB / is built dynamically) or by rebuilding after a new name was added to code. Tests: `tests/Feature/Http/IconMissTest.php` + the two report tests in `guard.test.js`. It re-checks only after a throttled click or `livewire:navigated`, skips elements already marked, ships no data, warns in dev, and `watchIcons()` returns a teardown. Expected to fire "once in a million" — if it fires on normal pages the scan missed something; add the name to `safelist.json`.

## Deploy

The server builds it: `npm ci && npm run build` (devDependencies `subset-font` + `fontkit` + `sharp` (sibling-logo WebP) must install; run `npm install` on every dev PC). Upload by folder: `resources/icons/` (without `generated/`), `resources/assets/img/<tenant>/sibling/` (your logo ORIGINALS only — never its `optimized/` subfolder, the root build regenerates it), `resources/js/` (app.js, auth.js, core/filament.js start the guard), `resources/css/` (app.css + core/filament.css import the generated css), `resources/views/` (meta-tags preload; the two fixed admin guides), plus root files `package.json`, `package-lock.json`, `vite.config.js`, `vitest.config.js`, `.gitignore`. Check the build log for `subset font: … icons verified`. The service worker never precaches either font file (`woff2` is absent from `public/sw.js`).
