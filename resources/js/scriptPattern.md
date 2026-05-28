# JavaScript Architecture & PWA Strategy

## 1. The Core Philosophy
The JavaScript architecture is built to support a highly interactive, near-instantaneous Progressive Web App (PWA) powered by Laravel Livewire and Alpine.js.

**Core JS Principles:**
1. **Zero UI Flash (Reactive Hot-Swapping):** We do not wait for Alpine components to download before rendering HTML. We use a centralized Proxy Hub to eagerly register component blueprints.
2. **Strict State Segregation:** UI interaction logic (toggling a modal) is strictly separated from global application state (Dark Mode).
3. **PWA-First Routing:** The Service Worker handles aggressive asset caching (fonts, css, js) but explicitly avoids caching HTML to prevent CSRF token mismatches and Livewire hydration failures.

---

## 2. Directory Structure & Layers

```
resources/js/
├── app.js                      # Vite entry point: Orchestrates initialization
├── sw.js                       # Service Worker: Workbox caching rules
├── core/                       # The Engine Room
│   ├── bootstrap.js            # Axios/Laravel CSRF setup
│   └── theme-manager.js        # DOM manipulation for themes & view-transitions
└── components/                 # The Alpine.js Ecosystem
    └── alpine/
        ├── main.js             # The Proxy Hub (Eager Registration)
        ├── stores/             # Global Singletons (App-wide state)
        └── data/               # Component Factories (UI element logic)
```

---

## 3. The Alpine.js Ecosystem

### 3.1 The Proxy Hub Architecture (`main.js`)
If you define `x-data="calculator"` in a Blade view, Alpine will crash if the `calculator` function isn't registered before Alpine boots. To solve this, and to prevent UI jank, we use a **Registration Hub**.

`main.js` imports every single data component and store, hooking into `alpine:init` to register them deterministically.

```javascript
// main.js
import registerThemeStore from './stores/theme.js'
import sidebar from "./data/sidebar.js";

export default function initAlpine() {
    document.addEventListener('alpine:init', () => {
        // 1. Register Global Stores
        registerThemeStore(Alpine)

        // 2. Register UI Component Proxies
        Alpine.data('sidebar', sidebar)
    })
}
```
*Why?* This guarantees that by the time the DOM is parsed and Alpine looks for `x-data="sidebar"`, the blueprint is already held in memory, resulting in instant hydration.

### 3.2 Global Stores (`stores/`)
Stores hold state that must survive the destruction of a specific DOM element, or state that must be shared across disparate components.

```javascript
// stores/theme.js
export default (Alpine) => {
    Alpine.store('appTheme', {
        current: 'default',
        set(theme) {
            // Reaches out to the Core engine room
            window.ThemeManager?.setTheme(theme);
        }
    });
}
```
*Usage in Blade:* `<div :class="$store.appTheme.current === 'dark' ? 'bg-black' : 'bg-white'">`

### 3.3 Component Factories (`data/`)
Data files are strictly factories returning an object. They govern local UI interactions.
**Naming Rule:** The exported function must be registered in `main.js` with a string key that exactly matches the filename (e.g., `password.js` -> `Alpine.data('password', password)`).

```javascript
// data/password.js
export default () => ({
    visible: false,
    toggle() { this.visible = !this.visible }
})
```

---

## 4. Progressive Web App (PWA) & Service Worker

The application uses Workbox via the Vite PWA Plugin (`injectManifest` strategy).

### 4.1 The Service Worker (`sw.js`)
We use `injectManifest` rather than `generateSW` because we need manual control over the Service Worker lifecycle. We inject Vite's hashed assets into Workbox's precache mechanism.

```javascript
import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching'
import { clientsClaim } from 'workbox-core'

self.skipWaiting() // Force the waiting service worker to become the active service worker.
clientsClaim()     // Claim control of uncontrolled clients immediately.

cleanupOutdatedCaches()
// Vite injects the manifest list here during build
precacheAndRoute(self.__WB_MANIFEST) 
```

### 4.2 Vite Configuration Rules
In `vite.config.js`, the Workbox configuration is highly specific:
*   **Heavy Assets Only:** We aggressively cache `/build/assets/**/*.{js,css,woff2,png}`.
*   **SPA Bypass (`navigateFallback: null`):** We strictly disable HTML caching. If a user goes offline, we do *not* serve a cached `index.html`.
    * *Why?* Serving cached HTML causes Livewire to fail (stale CSRF tokens, mismatched DOM diffing keys). The server must always handle HTML requests.

---

## 5. Developer Decision Matrix

| When you need to... | Do this... | Why? |
| :--- | :--- | :--- |
| Build a complex dropdown menu | Create `data/dropdown.js` and register it in `main.js`. | Keeps Blade views clean and localizes the toggle logic to the component. |
| Manage the user's selected language globally | Create `stores/locale.js`. | Multiple disconnected components (header, footer, settings) need to read this state. |
| Import a massive charting library like `Three.js` | Import it dynamically inside the specific component's `init()` function. | Prevents bloating `app.js` and ruining the initial Time-To-Interactive (TTI) metrics. |
| Cache a new font file | Ensure it is imported via CSS or Vite, and falls under the Workbox `globPatterns`. | The Service Worker only precaches assets it knows about during the Vite build step. |

---

## 6. Absolute Anti-Patterns (Do Not Do This)

❌ **Do not write `<script>` tags inside Blade views for Alpine logic.**
*Why?* It breaks Content Security Policy (CSP), cannot be minified by Vite, and litters the DOM. Always extract logic to `js/components/alpine/data/`.

❌ **Do not cache HTML responses or `/api` routes in the Service Worker.**
*Why?* The application relies on Livewire and Laravel Session state. Caching these requests will result in 419 Page Expired errors and broken form submissions.

❌ **Do not use `window.Theme = 'dark'` for global state.**
*Why?* Alpine cannot react to plain Window object mutations. Always use `Alpine.store('appTheme').set('dark')` so the UI re-renders automatically.
