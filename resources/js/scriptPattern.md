# JavaScript Architecture Documentation

## Overview
This document defines the modular JavaScript architecture, establishing clear separation between vendor libraries, Alpine.js proxy component registration, global state management, and the Service Worker implementation for the Progressive Web App (PWA).

---

## 1. Directory Structure

```
resources/js/
├── app.js                      # Vite entry point & orchestration
├── bootstrap.js                # Laravel/Axios defaults
├── sw.js                       # Service Worker (Workbox precaching)
├── core/                       # Third-party vendor libraries
│   ├── chart.js
│   ├── theme-manager.js        # Core theme logic
│   └── ...
└── components/
    └── alpine/
        ├── main.js             # Alpine proxy registration hub
        ├── stores/             # Global state definitions
        │   ├── app.js
        │   ├── theme.js
        │   └── background.js
        └── data/               # Component-level Alpine data logic
            ├── password.js
            ├── sidebar.js
            ├── search.js
            └── ... (30+ specialized proxy components)
```

---

## 2. Core Layer (`js/core/`)

**Purpose:** Vendor libraries and root application logic imported as static dependencies.

**Characteristics:**
- Domain-agnostic or core infrastructure logic.
- Imported directly into consuming modules or `app.js`.

**Examples:**
- `theme-manager.js`: Controls view-transition logic and setting HTML attributes for the MD3 theme.
- Charting wrappers or heavy vendor bundles (`chart.js`).

---

## 3. Components Layer (`js/components/alpine/`)

### 3.1 Architectural Pattern: Reactive Hot-Swap & Proxy Hub
Alpine.js follows a **centralized proxy registration** model. All data components and stores are registered through a single hub (`main.js`) to ensure deterministic initialization order. To prevent UI flashing, the system eagerly registers components, decoupling the HTML markup from the JS load time.

### 3.2 Registration Hub (`main.js`)

**Responsibilities:**
- Global store registration (`Alpine.store`).
- Component data factory registration (`Alpine.data`).
- Deterministic event listener attachment for `alpine:init`.

**Structure:**
```javascript
import registerAppStore from './stores/app.js'
import registerThemeStore from './stores/theme.js'

import sidebar from "./data/sidebar.js";
import password from "./data/password.js";

export default function initAlpine() {
    document.addEventListener('alpine:init', () => {
        // Global stores (singleton state)
        registerAppStore(Alpine)
        registerThemeStore(Alpine)

        // Component data factories (instantiated per-element)
        Alpine.data('sidebar', sidebar)
        Alpine.data('password', password)
    })
}
```

### 3.3 Global Stores (`js/components/alpine/stores/`)

**Purpose:** Cross-cutting state accessible via the `$store` magic property. Used for data that outlives the DOM element (e.g., Application Themes, Background state).

**Pattern:**
```javascript
// stores/theme.js
export const THEME_COLORS = [ /* array of color defs */ ];

export default (Alpine) => {
    Alpine.store('appTheme', {
        current: window.ThemeManager ? window.ThemeManager.getUserTheme() : 'default',
        mode: window.ThemeManager ? window.ThemeManager.getUserMode() : 'light',
        
        set(theme) {
            window.ThemeManager?.setTheme(theme);
        }
    });
}
```

### 3.4 Component Data (`js/components/alpine/data/`)

**Purpose:** Reusable data factories bound to DOM elements via `x-data`.
**Naming Rule:** The exported function name (or file name) strictly matches the string key used in `main.js`.

**Example:**
```javascript
// data/password.js
export default () => ({
    visible: false,
    strength: 0,
    
    toggle() {
        this.visible = !this.visible
    }
})
```

**Blade Integration:**
```html
<div x-data="password">
    <input :type="visible ? 'text' : 'password'">
    <button @click="toggle">Toggle Visibility</button>
</div>
```

---

## 4. Application Entry (`app.js`)

**Purpose:** The single Vite entry point initializing the Alpine ecosystem and Core services.

**Implementation:**
```javascript
import './core/bootstrap.js';
import ThemeManager from './core/theme-manager.js';
import initAlpine from './components/alpine/main.js';

ThemeManager.init();
initAlpine();
```

---

## 5. Service Worker & PWA Configuration

**Build Tool:** Vite PWA Plugin using the `injectManifest` strategy.
**Rationale:** `injectManifest` allows us to write custom caching logic in `sw.js` (like intercepting Livewire requests gracefully) while Vite automatically handles hashing and injecting the precache manifest for our CSS/JS/Fonts.

### 5.1 Service Worker (`sw.js`)
The `sw.js` file relies on Workbox to control precaching of static assets securely.

```javascript
import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching'
import { clientsClaim } from 'workbox-core'

self.skipWaiting()
clientsClaim()

cleanupOutdatedCaches()
// Injects the Vite asset manifest dynamically
precacheAndRoute(self.__WB_MANIFEST)
```

### 5.2 Vite Configuration Example
The PWA configuration explicitly caches heavy assets (fonts, images) and ensures SPA routing doesn't conflict with Laravel.

```javascript
// vite.config.js snippet
VitePWA({
    strategies: 'injectManifest',
    srcDir: 'resources/js',
    filename: 'sw.js',
    registerType: 'autoUpdate',
    outDir: 'public',
    workbox: {
        cleanupOutdatedCaches: true,
        globPatterns: ['build/assets/**/*.{js,css,woff2,png,svg,jpg,ttf,woff}'],
        navigateFallback: null  // Bypasses SPA fallback; Laravel handles 404s/Livewire
    }
})
```

---

## 6. Decision Matrix

| Scenario | Location | Rationale |
|----------|----------|-----------|
| Global Dark Mode state | `stores/theme.js` | Needs to be accessed by multiple disconnected UI elements. |
| Toggle behavior of a dropdown | `data/menu.js` | Scoped interaction limited specifically to the dropdown's DOM context. |
| Parsing dates with `date-fns` | `core/` | 3rd party vendor library mapping; imported where needed. |
| Registering a new widget interaction | `data/newWidget.js` | Create a new proxy data component and register it in `main.js`. |
| Caching a new font | `vite.config.js` | Glob patterns under `VitePWA` define what `sw.js` injects. |

---

## 7. Anti-Patterns

| Violation | Correction |
|-----------|------------|
| Writing `<script>` tags inside Blade views for Alpine logic. | Extract logic to `resources/js/components/alpine/data/` and bind via `x-data`. |
| Calling `Alpine.data()` directly inside `app.js`. | Register through the centralized hub in `main.js` to ensure deterministic booting. |
| Caching HTML responses in the Service Worker. | Use `navigateFallback: null`. Caching HTML breaks CSRF tokens and Livewire hydration. |
| Mixing Component state and Global state. | If a sidebar needs to know the user's theme, it must read from `Alpine.store('appTheme')`, not track it internally. |

