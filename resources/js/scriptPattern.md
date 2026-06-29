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

### 3.4 Shared Mixins (`mixins/`)
Cross-cutting UI behaviors that several factories reuse live in `mixins/` as factories returning a plain object, spread into a data factory's return value with `...mixinName()`. The canonical example is `maximize.js`, which backs the per-widget "maximize" feature (`.max-widget` fixed overlay + hiding `#header/#navbar/#footer` via `.layout-hidden`, plus a `window resize` dispatch for charts). It exposes a single reusable `applyMaximize(active)` helper; the default `toggleMaximize()` toggles a single `max` boolean through it.

```javascript
// data/dms.js — single boolean variant
import maximizeMixin from "../mixins/maximize.js";
export default function dms() {
    return { ...maximizeMixin(), /* … */ }
}
```

**Named-slot variant** — when a page has several independently-maximizable widgets (energy chart, taskboard columns), one shared backdrop requires a single active slot rather than N booleans. The factory still spreads `...maximizeMixin()` to pull in `applyMaximize`, then declares a `maximized<Thing>: null` slot and overrides `toggleMaximize(name)` to set the active slot and reuse `applyMaximize()`. The view binds one root `<template x-if="maximized<Thing>">` backdrop and each item's `:class="{ '…': maximized<Thing> === name }"`. This makes maximization mutually exclusive by construction (two `position:fixed` overlays cannot coexist) and avoids the bug where a bare `@click="toggleMaximize"` passes the `MouseEvent` as a `context` argument. Items carry no `x-data` of their own — every expression resolves up to the single parent factory scope (Alpine scope inheritance), so there is one source of truth for state.

**DOM-sourced item key + lifted per-item state** — for a repeated item (taskboard column) that needs per-item state (collapse), do not give each item its own `x-data` with inline `localStorage`. Lift the state to the parent factory as a map keyed by the item's DOM identifier (`collapsed: {}`), seed it from `localStorage` in `init()` by scanning `[data-column]` elements, and expose `isCollapsed(name)` / `toggleCollapsed(name)`. The item key is read from the DOM, not from a PHP-rendered string: a `col(el)` helper returns `el?.closest('[data-column]')?.dataset.column`, and every Alpine expression uses `col($el)` (or `$el.dataset.column` on the item element itself). This keeps a single data source and avoids the quoting pitfall where `{{ $column }}` interpolated *inside a nested JS string literal* — e.g. `'[data-column=\" {{ $column }}\"]'` — breaks the entire `:class` / `x-show` binding. Rule: put the PHP value only in a plain HTML attribute (`data-column="{{ $column }}"`), never inside an Alpine expression's string literal.

**Partial-width maximize** — `.max-widget` is a full-screen fixed overlay (`inset: 1.5rem`), which collapses narrow items (a 280px taskboard column gets `width: auto` and barely changes) instead of enlarging them. For such items use `.max-widget-column` (centered, `width: clamp(60vw, 70vw, 75vw)`, `height: 85vh`, `z-index: 999999`) so the maximized item occupies 60–75% of the viewport, not the whole screen.

**One reusable backdrop (`<x-ui.modals.max-backdrop/>`)** — a single Blade component backs both maximize modes. It declares two expression props — `state` (truthy-when-maximized expression, default `"max"`) and `close` (click handler expression, default `"toggleMaximize()"`) — and renders `<template x-if="{!! $state !!}"><div class="max-backdrop" @click="{!! $close !!}"></div></template>` (raw echo so quotes in the expression survive). Single-boolean modules (contact, dms) include it with no props and rely on the `max` boolean + the mixin `toggleMaximize()`. Named-slot modules (energy `maximizedWidget`, taskboard `maximizedColumn`) pass `state="maximized<Thing>" close="toggleMaximize(null)"`. The `close` default is an *explicit call* (`toggleMaximize()`), never a bare function ref — a bare `@click="toggleMaximize"` makes Alpine inject the `MouseEvent` as the first argument, which the named-slot `toggleMaximize(name)` would then store as the active slot (the original silent-close bug).

**Endless/DB-driven list maximize (feeds)** — the named-slot + DOM-sourced-key patterns compose for an infinite, server-paginated list (feeds carousel): the factory spreads `...maximizeMixin()`, declares `maximizedFeed: null`, overrides `toggleMaximize(name)` to key on the slot, and adds a `feed(el)` helper reading `el?.closest('[data-feed]')?.dataset.feed`. Because the list is endless, the slot key is the feed **id** read from the DOM (`data-feed="{{ $feed->id }}"`), never a hardcoded name — and `data-feed` is kept **distinct** from any sibling data attribute other logic already scans (`feeds` also has `data-feed-id` for the active-card tracker; `updateActiveItem()` reads `dataset.feedId`, so the two never collide). Use the **partial-width** `.max-widget-column` (not full-screen `.max-widget`) on the per-item wrapper, mirroring the taskboard column — its ancestors carry no transform, so `position:fixed` escapes the carousel to the viewport and the centered panel (60–75vw × 85vh) floats front-most over the dimming `.max-backdrop`. `.max-widget-column` sets its own `transform: translate(-50%,-50%) !important`, which already overrides the carousel's resting `scale-105`/`scale-95` (non-important) — so do **not** add `!scale-100` (two same-specificity `!important` transforms is a fragile cascade). The carousel wrapper also carries `grayscale`/`opacity` from its active/inactive `:class`, which `.max-widget-column` does **not** touch, so the maximize `:class` key still cancels those: `'max-widget-column !grayscale-0 !opacity-100': maximizedFeed === feed($el)`. Rule: pick the maximize class whose own `!important` declarations cover as many of the item's resting visual effects as possible, then `!`-cancel only what it doesn't cover; never stack two `!important` transforms.

**No intermediate stacking-context ancestor between the backdrop/card and the page root (the z-10 trap)** — the `<x-ui.modals.max-backdrop>` (`position:fixed; z-999998`) and the maximized card (`z-999999`) must resolve at the **page-root** stacking context so the card paints cleanly above the backdrop, above everything else — exactly like taskboard, whose root `<div x-data="taskboard()" class="... relative ...">` is `relative` with **no z-index** (so it is *not* a stacking context) and whose backdrop sits at the root (line 6). A `position:fixed` element's stacking-context parent is its nearest ancestor stacking context in the DOM (fixed changes the containing block, *not* the stacking parent). The first feeds attempt gave the scroll container `x-ref="timeline"` a `z-10` class — as a flex item, `z-10` makes it a **stacking context**, which **traps** the fixed backdrop and card down at `z-10` in the page-root context. Even though the card is above the backdrop *within* that trap (`999999 > 999998`), the whole trap sits low: the backdrop+card land at page-root z-10 instead of z-999998/999999, so they render *under* any page-root element above z-10 (the prev/next buttons at z-40, sticky filters at z-20, etc.), and the backdrop reads as an "overlay" sheet rather than a page-root backdrop behind a page-root card. Empirically: commenting the backdrop out removed the overlay look, and taskboard/contact (same `.max-backdrop` CSS, no trap) never showed it — proving the cause is the trap, not the backdrop's color/opacity. **Fix: give the scroll container `relative` (positioned, no z-index) instead of `z-10`** — `relative` is *not* a stacking context, so the fixed backdrop/card escape to the page-root context (matching taskboard), while `relative` still paints the timeline above the decorative `z-0` absolute lines (both are step-6 positioned descendants, DOM order puts the later timeline above the earlier lines). The dashboard chrome (`#navbar`/`#header`/`#footer`) is hidden by `maximizeMixin.applyMaximize` adding `.layout-hidden` (`visibility:hidden`). Rule: a maximize host must not put a z-index on the scroll container that holds the maximized items — that turns it into a stacking context and traps the fixed overlay low; use `relative` (or no positioning) so the fixed backdrop/card resolve at the page root.

**Hide sibling chrome that would ghost through the semi-transparent backdrop** — once the `z-10` trap is removed (scroll container is `relative`, not a stacking context), the prev/next scroll buttons (`absolute z-40`, siblings of the scroll container) resolve at the page-root context *below* the backdrop (`40 < 999998`) and the card (`999999`), so they no longer paint over the card. But `.max-backdrop` is only 60% opaque (`color-mix(primary 60%, transparent)`), so anything at `z < 999998` — including those `z-40` buttons — shows through the backdrop at 40%, i.e. faint ghost buttons visible over the dimmed carousel. Fix: gate them with `x-show="!maximizedFeed"` so they're removed from the layout on maximize rather than ghosting through. (When the trap still existed, the same `x-show` was needed for the stronger reason that the buttons sat *above* the trapped card; removing the trap changed the mechanism from "above the card" to "ghosting through the backdrop", but the remedy — hide them — is unchanged.) This is the same family of issue as the sticky `z-20` filters (hidden via `:class`), but the buttons are *siblings* of the scroll container rather than nested chrome — the remedy is the same (hide them), the mechanism differs (`x-show` vs `:class` `hidden`; `x-show` is fine here since the buttons carry no Livewire morph concerns).

**Backdrop fade must match the card's entrance (the `max-backdrop--sync` opt-in)** — the shared `.max-backdrop` fades in over `0.1s` (`animation: fadeIn 0.1s`), but `.max-widget-column` fades in over `0.4s`. With a 0.1s backdrop and a 0.4s card, the scene is fully dark at 0.1s while the card is still 25% visible — the dark layer reads as an *overlay* with content fading in on top, not a *backdrop* behind an emerging card. Fix: an opt-in `class` prop on `<x-ui.modals.max-backdrop>` (default `''`, so every other module's 0.1s backdrop is byte-for-byte unchanged) lets a module pass `class="max-backdrop--sync"`; the scoped rule `.max-backdrop.max-backdrop--sync { animation-duration: 0.4s }` lengthens just that instance's fade to match the card, so scene-darkening and card-emergence finish together. Note `.max-widget-column`'s `transform: translate(-50%,-50%) !important` overrides `slideScaleIn`'s transform keyframes (per the cascade, `!important` author beats the animation origin), so only the card's **opacity** animates — it stays centered throughout; the backdrop's `fadeIn` is likewise opacity-only, so matching their durations is the right coordination. Match the duration to the card the module actually uses (feeds/taskboard columns 0.4s; full-screen `.max-widget` 0.6s would want 0.6s if its overlay feel is ever re-visited).

**Aggregate-emoji givers as a native tooltip** — hovering an aggregated emoji button (`@foreach($feed->reactions->groupBy('emoji') as $emoji => $reactions)`) reveals who gave that emoji via a plain `title="کسانی که واکنش دادند: {{ $reactions->pluck('user.name')->filter()->implode('، ') }}"` on the button, **not** a positioned popover. Reason: the feed card root is `overflow-hidden` and its body is `overflow-y-auto`, so any `position: absolute` popover panel (the `ui.modals.popover` pattern) is clipped wherever the emoji sits near the card's edges — a browser-native `title` tooltip floats free of every `overflow` ancestor and needs no positioning logic. Givers are already eager-loaded (`reactions.user` in the `Feeds` computed), so `pluck('user.name')` adds no queries. The button keeps its own `wire:click="toggleReaction(...)"` so hover-reveal and click-toggle never collide (different gestures). A `hover` prop was briefly added to `ui.modals.popover` for this and then reverted — rule: for a read-only hover reveal inside an `overflow`-clipped container, prefer a native `title` over an absolute popover; reserve `ui.modals.popover` (click-toggle) for menus that float rich content where clipping isn't a risk.

**Hide the tab's own chrome on maximize** — when a feeds card maximizes, the feeds top chrome (page title + focus-banner + filters) is hidden by wrapping those three includes in `<div :class="{ 'hidden': maximizedFeed }" class="flex flex-col gap-6">`, sharing the component's `maximizedFeed` state. This is *not* the shared dashboard-shell hide (`#navbar/#header/#footer` → `.layout-hidden`, toggled by `maximizeMixin.applyMaximize` for every tab); it's a feeds-local Alpine toggle because that chrome lives inside this Livewire component. It's needed beyond the backdrop because the filters are `sticky top-0 z-20` — a stacking context above the timeline's `z-10` that holds the maximized card, so the filters would otherwise paint *over* the card. Use a `:class` binding with Tailwind `hidden` (`display: none`), **not** `x-show`/`x-transition` (the transition can leave the element visible if `transitionend` doesn't fire cleanly) and **not** `x-if` (removing the subtree fights Livewire morph and re-initializes the filters' own `x-data` on restore). `:class` is the same mechanism the card's own `max-widget-column` `:class` already uses, so it's proven reactive; `display: none` also removes the chrome's space so the timeline expands into the focus view, whereas the dashboard shell's `visibility: hidden` would leave a gap. The `flex flex-col gap-6` wrapper preserves the root's `gap-6` rhythm whether shown or hidden.

**Input maximize: prop vs. direct trigger/overlay (the `$refs` rule)** — `<x-ui.forms.textarea>` exposes a `maximizable` prop that bundles the whole feature: a `wire:ignore.self` root with `x-data="{ value: @entangle($attributes->wire('model')->value() ?? '').live, fullscreen: false }"`, `pl-10` padding, and the reusable `<x-ui.forms.maximize-trigger>` + `<x-ui.forms.maximize-overlay>`. For a **plain** input (e.g. the comment *edit* box — no bespoke `x-data`, no `$refs`), just use the prop: `<x-ui.forms.textarea wire:model="commentForm.content" label="ویرایش نظر" :maximizable="true"/>` — no hand-added trigger/overlay/entangle. For a **bespoke** input whose `<textarea>` is referenced by `$refs` from a sibling/parent `x-data` (the feeds main comment box: the emoji picker calls `this.$refs.commentInput` for `insertEmoji`), **do not** swap it onto the component: the component's own `x-data` root would sit between the picker and the `<textarea>`, and Alpine registers `x-ref="commentInput"` on the *component's* scope, so the picker's `$refs.commentInput` resolves `undefined` and emoji insertion silently breaks. Instead keep the bespoke `<textarea>` untouched and reuse only the two existing components — `<x-ui.forms.maximize-trigger>` + `<x-ui.forms.maximize-overlay>` — adding the two required keys (`value: @entangle(...).live, fullscreen: false`) to the bespoke `x-data` so the trigger/overlay have their scope. Rule: the `maximizable` prop is for plain inputs; a `$refs`-driven bespoke input reuses the trigger/overlay components directly, because the maximize state must share the bespoke `x-data` — never nest a second `x-data` between a `$refs` consumer and its target.

**Feed blades stay clean: logic in dedicated factories, entangle spread inline** — the bespoke comment-input (`comments.blade.php`) and the per-card reaction strip (`actions.blade.php`) carried inline `x-data` with full method bodies (emoji-picker positioning, `insertEmoji`, auto-grow, enter-to-send, reaction pagination) plus re-declared emoji arrays. That logic now lives in two `data/` factories registered in `main.js`: `feedComposer` (comment composer: `showEmoji`, `fullscreen`, `panelStyle`, `emojis`, `toggleEmoji`/`insertEmoji`/`onEnter`/`autoGrow`) and `feedReactions` (reaction strip: `page`, `per`, `emojis`, `selected`, `next()`). The blades keep only markup + directive refs to the factory's state/methods. The Livewire entangle stays **inline in the blade** (it's a Blade-only directive) and the factory is **spread** into the x-data object alongside it: `x-data="{ value: @entangle('newComments.<id>').live, ...feedComposer(<id>) }"`. Why spread rather than `x-data="feedComposer(<id>, @entangle(...).live)"` (entangle passed as an argument): `@entangle` compiles to a `Livewire.find(...).entangle(...)` call, and Livewire's Alpine plugin recognizes an entangled property by scanning the `x-data` *expression string* for that call — the entangle must appear directly in the object literal, not buried inside a function-argument value the plugin doesn't inspect. Spreading `...feedComposer(<id>)` brings in every method/state key (including `fullscreen`, a plain boolean safe to spread), while `value` (the entangle) stays where the plugin can see it. The factory deliberately omits `value`; it owns `fullscreen` and all methods. The reaction strip has no entangle, so it uses a clean `x-data="feedReactions(<feedId>, '<selectedEmoji>')"`. The `$refs` rule still holds: `feedComposer`'s `insertEmoji`/`onEnter` use `this.$refs.commentInput`, so the `<textarea x-ref="commentInput">` stays a direct child of the same `x-data` scope — no nested `x-data` (e.g. the `maximizable` textarea component) is inserted between them.

**Emoji dictionary centralized in `stores/emoji.js`** — no blade re-declares emoji arrays. Alongside the categorized `emojis` export (used by `contact.js`), two light, hand-picked `const` exports serve the feeds module: `feedEmojis` (the composer picker grid) and `feedReactions` (the 21 quick-reaction set). `feedComposer`/`feedReactions` import them directly (same pattern as `contact.js` importing `emojis`). The old PHP `$emojis` reorder — float the user's current reaction to page 0 — moved into `feedReactions`' object initializer, keyed by the `selectedEmoji` still passed from the blade (`$userReaction?->emoji`, PHP-sourced). Rule: emoji data has exactly one source (`stores/emoji.js`); feed blades and feed factories import it, never re-declare it inline — neither as an Alpine array (`emojis: [...]`) nor as a PHP array (`$emojis = [...]`) pushed through `json_encode`.

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

## 7. Modal Open Animation & Heavy Slot Content

The shared modal shell (`.custom-modal`) opens by animating **`width`/`height`** (geometry), not `transform` — see `resources/css/core/dashboard.css`. Geometry animation cannot run on the compositor, so the browser re-runs layout on the modal subtree **every animation frame** for ~1s.

⚠️ **The slot content stays laid-out while invisible.** `.custom-modal-content` is `visibility: hidden` + `opacity: 0` until the geometry finishes, but `visibility: hidden` still performs layout (unlike `display: none`). So a heavy form in the slot is reflowed ~60× during the open animation even though it isn't painted. The dominant cost is **text re-wrapping** of the slot's fields at each intermediate width. A wider final layout (e.g. `!w-full` on `modal-inner-card` instead of the default 66.666%) raises per-frame reflow cost and can push a previously-smooth modal into jank.

✅ **Defer the slot's layout until the geometry animation completes** — scope it to the one form that needs it, leave the shared modal untouched:

```blade
<div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl"
     x-data="{ tab: '{{ $defaultTab }}', ready: false }"
     x-effect="if (show && !ready) { setTimeout(() => { if (show) ready = true }, 1000) } else if (!show) { ready = false }"
     x-show="ready">
```

* `x-effect` reads the parent modal's entangled `show` (scope chain is preserved through `x-teleport="body"`, so a teleported slot still reaches the modal's `show`/`active`). On open it arms a 1000ms timer (matching the `.custom-modal-content` opacity delay of `ease 1s`); on close it resets `ready` so the next open starts deferred.
* `x-show="ready"` keeps the form `display: none` during the geometry window → nothing to lay out → zero per-frame reflow. At 1s the form is laid out **once**, exactly when it was already scheduled to fade in. `wire:model` bindings stay intact — `x-show` keeps the elements in the DOM.
* The timer callback re-checks `if (show)` so a close-during-pending or quick reopen can't leak a stale `ready = true`.
* **Adopters:** `livewire/dashboard/taskboard/form.blade.php` and `livewire/dashboard/tab/calendar/create.blade.php` (form modals — expanded `!w-full !max-w-none !p-5 md:!p-6` sizing + defer on the `modal-inner-card`); `livewire/dashboard/tab/status/about-me.blade.php` (defer on the main content container only — its `max-w-2xl` profile frame and decorative absolute layers stay untouched, since they reflow trivially); `livewire/dashboard/suggestion/create.blade.php` (defer on the flowchart modal's `w-full` image wrapper). For non-form modals only the defer applies — their purpose-specific widths are left as designed.
* **Do not** reach for `max-widget` (the page-level `position: fixed` fullscreen overlay) inside this modal — `.custom-modal` has `transform` + `contain: strict` + `overflow: hidden`, which re-contains a `fixed` descendant unpredictably (the z-10/transform trap). That pattern is for page-root widgets only. Expanding the card *within* the modal is the correct adaptation here.
