# Offline fallback assets

Dormant carbon copy of the font and icon files the app serves locally. Not published to `public/` while the copy block in `vite.config.js` stays commented out.

| Folder | Contents | Source |
|---|---|---|
| `fonts/` | Yekan (woff, woff2, ttf, css), Iranyekan (woff, ttf) | `resources/assets/fonts/` |
| `material-symbols/` | `rounded.css`, `material-symbols-rounded.woff2` (the real 5 MB icon font) | `node_modules/material-symbols/` |

## Why it exists

`resources/assets/fonts/material-symbols-rounded.woff2` is a 0-byte placeholder; the real icon font only exists in `node_modules`. If `node_modules` is lost while offline, `npm install` cannot restore it, and every icon renders as raw text.

## Rainy day

1. To publish the copy: in `vite.config.js`, uncomment the `resources/fallback` block inside `viteStaticCopy`, then run `npm run build`. The files land in `public/build/fallback/`.
   The production service worker (`injectManifest`) precaches only js/css/html with a 2 MiB cap, so the copy adds just two tiny CSS files to precache and never the 5 MB font. If woff2 is ever added to `injectManifest.globPatterns`, also add `injectManifest: { globIgnores: ['**/fallback/**'] }`; the `workbox.globPatterns` key in `vite.config.js` has no effect on the production build.
2. To restore the icon font after losing `node_modules`: copy `resources/fallback/material-symbols/*` to `node_modules/material-symbols/` (no build needed for this step; `npm run build` itself needs `node_modules` intact).
