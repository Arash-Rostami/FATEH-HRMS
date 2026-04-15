```markdown
## 10. Static Asset Architecture (`resources/assets/`)

### 10.1 Directory Structure

```
resources/assets/
├── audio/              # Audio assets (UI sounds, notifications)
├── fonts/              # Custom typefaces (non-Google Fonts)
├── img/                # Static images
│   ├── bg   /          # All bgs of dashboard 
└── video/              # Background videos, tutorials
```

### 10.2 Organization Principles

**Flat vs. Nested Decision Matrix:**

| Scenario | Structure | Example |
|----------|-----------|---------|
| Single-use image (favicon, hero) | Root of `img/` | `img/favicon.svg` |
| Thematic image group | Subfolder under `img/` | `img/bg/` |
| Domain-specific assets | Domain-named subfolder | `img/dashboard/` |
| Vector icons | `img/icons/` | `img/icons/close.svg` |

**Rationale:** Deep nesting creates friction for rapid prototyping. Reserve subfolders for collections exceeding 5+ related assets.

### 10.3 Build Pipeline

**Vite Plugin:** `vite-plugin-static-copy`

**Configuration:**
```javascript
// vite.config.js
import { viteStaticCopy } from 'vite-plugin-static-copy'

export default {
    plugins: [
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/assets/*',
                    dest: 'assets'
                }
            ]
        })
    ]
}
```

**Behavior:**
- Copies entire `resources/assets/` tree to `public/assets/`
- Preserves directory structure
- Non-hashed (unlike `resources/js/` and `resources/css/` assets)
- Referenced by literal path, not Vite manifest

### 10.4 Reference Patterns

**Blade Components:**
```blade
<!-- Images -->
<img src="{{ asset('assets/img/logo.svg') }}" alt="Logo">

<!-- Fonts -->
<style>
    @font-face {
        font-family: 'CustomFont';
        src: url('{{ asset('assets/fonts/custom.woff2') }}') format('woff2');
    }
</style>

<!-- Audio (Alpine.js interaction) -->
<audio x-ref="notification" src="{{ asset('assets/audio/notification.mp3') }}"></audio>

<!-- Video -->
<video src="{{ asset('assets/video/hero-bg.mp4') }}" autoplay muted loop></video>
```

**CSS References:**
```css
/* Relative to final public location */
.hero {
    background-image: url('/assets/img/hero-bg.jpg');
}
```

### 10.5 Categorization Guidelines

| Asset Type | Location | Processing |
|------------|----------|------------|
| UI sound effects | `audio/ui/` | Copied as-is |
| Notification tones | `audio/alerts/` | Copied as-is |
| Custom web fonts | `fonts/` | Copied as-is |
| Brand logos | `img/logos/` | Copied as-is |
| Hero/background images | `img/` root | Copied as-is |
| Icon system (SVG) | `img/icons/` | Potential future: sprite generation |
| Looping backgrounds | `video/ambient/` | Copied as-is |
| Tutorial content | `video/tutorials/` | Copied as-is |

### 10.6 Anti-Patterns

| Violation | Correction |
|-----------|------------|
| Importing assets from `resources/assets/` in JS/CSS | Use `resources/` sibling folders for Vite-processed assets; `assets/` is for static copy only |
| Hashing filenames manually | Vite handles hashing for JS/CSS; `assets/` contents remain literal for direct reference |
| Deep nesting (`img/dashboard/widgets/icons/`) | Maximum 2 levels: `img/{category}/file` |
| Mixing processed and static assets | Keep `resources/js/img/` (processed) separate from `resources/assets/img/` (static) |

### 10.7 Service Worker Integration

Static assets in `public/assets/` require explicit Workbox configuration for runtime caching:

```javascript
// sw.js
registerRoute(
    ({ request }) => request.destination === 'image' || 
                    request.destination === 'video' ||
                    request.destination === 'font',
    new CacheFirst({
        cacheName: 'static-assets',
        plugins: [
            new ExpirationPlugin({
                maxEntries: 100,
                maxAgeSeconds: 365 * 24 * 60 * 60  // 1 year
            })
        ]
    })
)
```

---

```
