```markdown
# Static Asset Architecture

Conventions for `resources/assets/` — static, non-hashed files copied verbatim to `public/build/assets/` by Vite, referenced by literal `asset('build/assets/...')` paths (not the Vite manifest).

## Directory Structure

```
resources/assets/
├── audio/
├── fonts/
├── img/
│   ├── <tenant>/     — per-tenant role-named files, see Tenant-scoped assets below
│   └── bg/
├── js/
│   └── lib/
└── video/
    └── <tenant>/     — per-tenant sequentially-numbered files, see below
```

**Flat vs. nested:** single-use images (favicon, hero) live at `img/` root as the generic/platform-default fallback; thematic groups get a subfolder (`img/bg/`). Tenant subfolders (`img/<tenant>/`, `video/<tenant>/`) are the one exception to "max 2 levels" — they're a separate categorization axis (per-company branding), not a thematic group. Reserve subfolders for 5+ related assets otherwise.

## Tenant-scoped assets — see `config/tenantPattern.md` for the full mechanism

`img/<tenant>/` and `video/<tenant>/` (`<tenant>` = a slug matching a `config/tenants.php` entry, e.g. `fateh`, `persol`) are auto-discovered at config-resolution time via `tenantAsset()`/`tenantVideos()` (`app/Helpers/index.php`) — no filename is ever hardcoded in PHP or Blade. Two different naming conventions apply inside a tenant folder:
- **Images**: role-named, extension-agnostic — `logo.*`, `light.*`, `dark.*`, `favicon.*`, `user-background.*`, `admin-background.*`. Matched by basename only (`glob("{$dir}/{$role}.*")`), so `.jpg`/`.png`/`.svg`/`.webp` all work interchangeably per tenant.
- **Videos**: sequentially numbered, no fixed count — `1.<ext>`, `2.<ext>`, ... as many as that tenant has.
- A tenant may keep an `extra/` subfolder for unused/reference assets — the glob only checks the tenant's immediate folder, so `extra/` is correctly ignored by the mechanism.

Onboarding a new tenant is purely additive: add the folder + files, add one `config/tenants.php` entry, set `APP_TENANT`. Never hardcode a tenant's asset path outside `config/tenants.php`.

## Build Pipeline

`vite-plugin-static-copy` copies each `resources/assets/{audio,video,img,fonts,js}` tree to `public/assets/`, preserving structure, non-hashed. `resources/js/` and `resources/css/` are Vite-processed/hashed separately — never mix.

```javascript
viteStaticCopy({
    targets: [
        {src: 'resources/assets/audio', dest: 'assets'},
        {src: 'resources/assets/video', dest: 'assets'},
        {src: 'resources/assets/img', dest: 'assets'},
        {src: 'resources/assets/fonts', dest: 'assets'},
        {src: 'resources/assets/js', dest: 'assets'},
        {
            src: 'node_modules/material-symbols/rounded.css',
            dest: 'assets/material-symbols'
        },
        {
            src: 'node_modules/material-symbols/material-symbols-rounded.woff2',
            dest: 'assets/material-symbols'
        },
    ]
})
```

## Reference Patterns

```blade
<img src="{{ asset('build/assets/img/logo.svg') }}" alt="Logo">

<style>
    @font-face {
        font-family: 'CustomFont';
        src: url('{{ asset('build/assets/fonts/custom.woff2') }}') format('woff2');
    }
</style>

<audio x-ref="notification" src="{{ asset('build/assets/audio/notification.mp3') }}"></audio>
<video src="{{ asset('build/assets/video/hero-bg.mp4') }}" autoplay muted loop></video>
<script src="{{ asset('build/assets/js/lib/confetti.browser.min.js') }}"></script>
```

Tenant-scoped brand assets are never hand-written as literal paths like the above — always go through `config('app.*')` (resolved per-tenant in `config/tenants.php`) or `tenantVideos()`, e.g. `asset(config('app.company_logo'))`, not `asset('build/assets/img/fateh/logo.png')`.

```css
.hero {
    background-image: url('/build/assets/img/hero-bg.jpg');
}
```

## Categorization

| Asset Type | Location | Processing |
|------------|----------|------------|
| UI sound effects | `audio/ui/` | Copied as-is |
| Notification tones | `audio/alerts/` | Copied as-is |
| Custom web fonts | `fonts/` | Copied as-is |
| Vendored browser-global JS | `js/lib/` | Copied as-is (assigned to `window`, loaded via literal `<script src=asset(...)>`) |
| Per-tenant brand logos/backgrounds/favicon | `img/<tenant>/` | Copied as-is; role-named, see Tenant-scoped assets above |
| Generic/platform-default logos | `img/` root | Copied as-is; fallback when a tenant lacks a role file |
| Hero/background images (non-tenant) | `img/` root or `img/bg/` | Copied as-is |
| Icon system (SVG) | `img/icons/` | Potential future: sprite generation |
| Per-tenant auth-page video playlist | `video/<tenant>/` | Copied as-is; sequentially numbered, see Tenant-scoped assets above |
| Looping backgrounds (non-tenant) | `video/ambient/` | Copied as-is |
| Tutorial content | `video/tutorials/` | Copied as-is |

## Anti-Patterns

| Violation | Correction |
|-----------|-----------|
| Importing assets from `resources/assets/` in JS/CSS | Use `resources/` sibling folders for Vite-processed assets; `assets/` is static copy only |
| Hashing filenames manually | Vite hashes JS/CSS; `assets/` contents stay literal for direct reference |
| Deep nesting (`img/dashboard/widgets/icons/`) | Max 2 levels: `img/{category}/file` |
| Mixing processed and static assets | Keep `resources/js/img/` (processed) separate from `resources/assets/img/` (static) |

## Service Worker Integration

Static assets in `public/assets/` require explicit Workbox runtime-caching config:

```javascript
registerRoute(
    ({ request }) => request.destination === 'image' ||
                    request.destination === 'video' ||
                    request.destination === 'font',
    new CacheFirst({
        cacheName: 'static-assets',
        plugins: [
            new ExpirationPlugin({
                maxEntries: 100,
                maxAgeSeconds: 365 * 24 * 60 * 60
            })
        ]
    })
)
```

---
```