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

---

# Activate the fallback (3 copy-paste edits, then build)

Limit: the fallback copy sits on the same server as the main files. It only helps when the main font/icon files are missing or broken. It does not help during an internet blackout.

## 1. `vite.config.js` (line 56)

Before:
```js
                // {src: 'resources/fallback', dest: '.'},
```

After:
```js
                {src: 'resources/fallback', dest: '.'},
```

Then run `npm run build`. Files land in `public/build/fallback/`.

## 2. `resources/css/core/theme.css` (lines 1-17)

Before:
```css
@font-face {
    font-family: 'Yekan';
    src: url('../../assets/fonts/Yekan.woff2') format('woff2'),
    url('../../assets/fonts/Yekan.woff') format('woff'),
    url('../../assets/fonts/Yekan.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: 'IranYekan';
    src: url('../../assets/fonts/Iranyekan.woff') format('woff'),
    url('../../assets/fonts/Iranyekan.ttf') format('ttf');
    font-weight: normal;
    font-style: normal;
}
```

After:
```css
@font-face {
    font-family: 'Yekan';
    src: url('../../assets/fonts/Yekan.woff2') format('woff2'),
    url('../../assets/fonts/Yekan.woff') format('woff'),
    url('../../assets/fonts/Yekan.ttf') format('truetype'),
    url('/build/fallback/fonts/Yekan.woff2') format('woff2'),
    url('/build/fallback/fonts/Yekan.woff') format('woff'),
    url('/build/fallback/fonts/Yekan.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: 'IranYekan';
    src: url('../../assets/fonts/Iranyekan.woff') format('woff'),
    url('../../assets/fonts/Iranyekan.ttf') format('truetype'),
    url('/build/fallback/fonts/Iranyekan.woff') format('woff'),
    url('/build/fallback/fonts/Iranyekan.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
}
```

(The old `format('ttf')` was invalid, so it is corrected to `format('truetype')`.)

## 3. `resources/views/components/dashboard/meta-tags.blade.php` (line 36)

Before:
```html
<link rel="stylesheet" href="/build/assets/material-symbols/rounded.css">
```

After:
```html
<link rel="stylesheet" href="/build/assets/material-symbols/rounded.css"
      onerror="this.onerror=null;this.href='/build/fallback/material-symbols/rounded.css'">
```

## Check

After `npm run build`, confirm these exist:
- `public/build/fallback/fonts/Yekan.woff2`
- `public/build/fallback/material-symbols/rounded.css`
- `public/build/fallback/material-symbols/material-symbols-rounded.woff2`

If `npm run build` can't find the real icon font, first copy `resources/fallback/material-symbols/*` into `node_modules/material-symbols/` (see `README.md`).
