# JavaScript Architecture Documentation

## Overview
This document defines the modular JavaScript architecture, establishing clear separation between vendor libraries, Alpine.js component registration, and global state management.

---

## 1. Directory Structure

```
resources/js/
├── app.js                      # Vite entry point & orchestration
├── core/                       # Third-party vendor libraries
│   ├── chart.js
│   └── ...
└── components/
└── alpine/
├── main.js             # Alpine registration hub
├── stores/             # Global state definitions
│   ├── app.js
│   ├── theme.js
│   └── background.js
└── data/               # Component-level Alpine data
├── password.js
├── greeting.js
├── scrollManager.js
├── mobile.js
├── search.js
├── menu.js
├── timer.js
├── settings.js
├── palette.js
├── fullscreen.js
├── googleTranslate.js
├── occasion.js
├── sidebar.js
├── share.js
├── background.js
├── feed.js
├── links.js
├── gallery.js
├── report.js
├── filters.js
├── taskboard.js
├── profile.js
├── calculator.js
├── stopwatch.js
├── dms.js
├── ths.js
├── reservation.js
├── radio.js
├── contact.js
└── energyChart.js
```

---

## 2. Core Layer (`js/core/`)

**Purpose:** Vendor libraries imported as static dependencies.

**Characteristics:**
- No application logic
- Imported directly into consuming modules or `app.js`
- Version-locked via `package.json`

**Examples:**
- Charting libraries (Chart.js, D3)
- Utility libraries (Lodash, date-fns)
- Validation engines

**Import Pattern:**
```javascript
import Chart from '../core/chart.js';
// or
import { format } from '../core/date-fns.js';
```

---

## 3. Components Layer (`js/components/alpine/`)

### 3.1 Architectural Pattern
Alpine.js follows a **centralized registration** model. All data components and stores are registered through a single hub (`main.js`) to ensure deterministic initialization order.

### 3.2 Registration Hub (`main.js`)

**Responsibilities:**
- Global store registration
- Component data factory registration
- Event listener attachment for `alpine:init`

**Structure:**
```javascript
import registerAppStore from './stores/app.js'
import registerThemeStore from './stores/theme.js'
import registerBackgroundStore from './stores/background.js'

import password from "./data/password.js";
import greeting from "./data/greeting.js";
// ... additional component imports

export default function initAlpine() {
    document.addEventListener('alpine:init', () => {
        // Global stores (singleton state)
        registerAppStore(Alpine)
        registerThemeStore(Alpine)
        registerBackgroundStore(Alpine)

        // Component data factories (instantiated per-element)
        Alpine.data('password', password)
        Alpine.data('greeting', greeting)
        // ... additional registrations
    })
}
```

### 3.3 Global Stores (`js/components/alpine/stores/`)

**Purpose:** Cross-cutting state accessible via `$store` magic property.

**Pattern:**
```javascript
// stores/theme.js
export default function registerThemeStore(Alpine) {
    Alpine.store('theme', {
        mode: 'system',
        
        init() {
            this.syncWithSystem()
        },
        
        syncWithSystem() {
            // Implementation
        },
        
        setMode(value) {
            this.mode = value
        }
    })
}
```

**Consumption:**
```html
<div x-data x-text="$store.theme.mode"></div>
<button @click="$store.theme.setMode('dark')">Dark Mode</button>
```

### 3.4 Component Data (`js/components/alpine/data/`)

**Purpose:** Reusable data factories bound to DOM elements via `x-data`.

**Contract:**
- Export default function returning state object and methods
- Filename matches registration key in `main.js`
- Linked to Blade component root element

**Example:**
```javascript
// data/password.js
export default () => ({
    visible: false,
    strength: 0,
    
    toggle() {
        this.visible = !this.visible
    },
    
    calculateStrength(value) {
        // Implementation
    }
})
```

**Blade Integration:**
```blade
<!-- Linked to root element of relevant Blade component -->
<div x-data="password">
    <input :type="visible ? 'text' : 'password'" @input="calculateStrength($event.target.value)">
    <button @click="toggle">Toggle Visibility</button>
    <span x-text="strength"></span>
</div>
```

---

## 4. Application Entry (`app.js`)

**Purpose:** Vite entry point initializing the Alpine ecosystem.

**Implementation:**
```javascript
import './bootstrap';
import initAlpine from './components/alpine/main.js'

// Initialize Alpine.js registration
initAlpine()
```

**Rationale:** Lazy initialization via function call ensures `alpine:init` listener attaches before Alpine loads, preventing race conditions.

---

## 5. Classification Matrix

| Element | Location | Registration | Scope |
|---------|----------|--------------|-------|
| Chart.js library | `core/chart.js` | Direct import | Module-level |
| Theme state (dark/light) | `stores/theme.js` | `Alpine.store()` | Global singleton |
| Password visibility toggle | `data/password.js` | `Alpine.data()` | Per-element instance |
| Calculator logic | `data/calculator.js` | `Alpine.data()` | Per-element instance |
| Background animation state | `stores/background.js` | `Alpine.store()` | Global singleton |

---

## 6. Naming Conventions

| Context | Pattern | Example |
|---------|---------|---------|
| Store files | `camelCase.js` | `theme.js` |
| Store registration | `register{PascalCase}Store` | `registerThemeStore` |
| Data files | `camelCase.js` | `scrollManager.js` |
| Data registration key | `camelCase` | `Alpine.data('scrollManager', ...)` |
| Multi-word data | `camelCase` | `googleTranslate.js` |

---

## 7. Anti-Patterns

| Violation | Correction |
|-----------|------------|
| Inline `x-data` in Blade | Extract to `js/components/alpine/data/` |
| Direct `Alpine.data()` in `app.js` | Route through `main.js` hub |
| Store logic in component data | Promote to `stores/` |
| Vendor library in `components/` | Move to `core/` |
| DOM manipulation outside Alpine lifecycle | Use `x-init` or `$nextTick` |

---

## 8. Lifecycle Guarantees

**Initialization Order:**
1. `app.js` executes, attaches `alpine:init` listener
2. Alpine.js loads and emits `alpine:init`
3. `main.js` callback executes:
    - Stores registered (singleton instantiation)
    - Data factories registered (no instantiation yet)
4. DOM ready, Alpine instantiates components on `x-data` elements

---

Configuration Strategy

**Build Tool:** Vite PWA Plugin with `injectManifest` strategy.

**Rationale:** `injectManifest` provides full control over service worker logic while Vite handles precache manifest generation. Alternative `generateSW` is insufficient for applications requiring custom caching strategies or background sync.

### Vite Configuration
There is a service worker that picks up files in layouts.auth  cache data and provide them in the other layouts (layouts.app) as well. (<x-service-worker />)
```javascript
// vite.config.js
import { VitePWA } from 'vite-plugin-pwa'

export default {
    plugins: [
        VitePWA({
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            registerType: 'autoUpdate',
            outDir: 'public',
            
            manifest: {
                name: 'Intra Dashboard',
                short_name: 'Intra',
                description: 'The digital home for your work.',
                theme_color: '#000000',
                background_color: '#000000',
                display: 'standalone',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: 'assets/img/mining.svg',
                        sizes: 'any',
                        type: 'image/svg+xml'
                    }
                ]
            },
            
            workbox: {
                cleanupOutdatedCaches: true,
                globPatterns: [
                    'build/assets/**/*.{js,css,woff2,png,svg,jpg,ttf,woff}'
                ],
                navigateFallback: null  // Disables SPA fallback; server handles 404s
            },
            
            devOptions: {
                enabled: true,
                type: 'module'
            }
        })
    ]
}
```
