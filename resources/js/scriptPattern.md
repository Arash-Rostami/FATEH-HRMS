# JavaScript Architecture & PWA Strategy

## 1. Core Philosophy
A highly interactive PWA powered by Laravel Livewire and Alpine.js.

1. **Zero UI Flash (Reactive Hot-Swapping):** HTML renders before Alpine downloads — a centralized Proxy Hub eagerly registers component blueprints.
2. **Strict State Segregation:** UI interaction logic (toggling a modal) is separated from global app state (Dark Mode).
3. **PWA-First Routing:** The Service Worker aggressively caches fonts/css/js but never HTML (prevents CSRF token mismatches and Livewire hydration failures).

---

## 2. Directory Structure & Layers

```
resources/js/
├── app.js                      # Vite entry point: orchestrates initialization
├── sw.js                       # Service Worker: Workbox caching rules
├── core/                       # The engine room
│   ├── bootstrap.js            # Axios/Laravel CSRF setup
│   └── theme-manager.js        # DOM manipulation for themes & view-transitions
└── components/
    └── alpine/
        ├── main.js             # The Proxy Hub (eager registration)
        ├── stores/             # Global singletons (app-wide state)
        ├── data/               # Component factories (UI element logic)
        └── mixins/             # Cross-cutting reusable behaviors
```

---

## 3. The Alpine.js Ecosystem

### 3.1 The Proxy Hub (`main.js`)
Alpine crashes if an `x-data="calculator"` function isn't registered before Alpine boots. `main.js` imports every data component and store, hooking into `alpine:init` to register them deterministically.

```javascript
import registerThemeStore from './stores/theme.js'
import sidebar from "./data/sidebar.js";

export default function initAlpine() {
    document.addEventListener('alpine:init', () => {
        registerThemeStore(Alpine)
        Alpine.data('sidebar', sidebar)
    })
}
```
By the time Alpine looks for `x-data="sidebar"`, the blueprint is already in memory — instant hydration.

### 3.2 Global Stores (`stores/`)
Stores hold state that survives DOM-element destruction or is shared across components.

```javascript
export default (Alpine) => {
    Alpine.store('appTheme', {
        current: 'default',
        set(theme) { window.ThemeManager?.setTheme(theme); }
    });
}
```
*Usage:* `<div :class="$store.appTheme.current === 'dark' ? 'bg-black' : 'bg-white'">`

### 3.3 Component Factories (`data/`)
Strictly factories returning an object; they govern local UI interactions. **Naming rule:** the registered string key must exactly match the filename (`password.js` → `Alpine.data('password', password)`).

```javascript
export default () => ({
    visible: false,
    toggle() { this.visible = !this.visible }
})
```

### 3.4 Shared Mixins (`mixins/`)
Cross-cutting behaviors reused by several factories, as factories returning a plain object, spread into a data factory's return value with `...mixinName()`.

**`maximize.js`** — backs the per-widget "maximize" feature (`.max-widget` fixed overlay + hiding `#header/#navbar/#footer` via `.layout-hidden`, plus a `window resize` dispatch for charts). Exposes `applyMaximize(active)`; default `toggleMaximize()` toggles a single `max` boolean through it.

```javascript
import maximizeMixin from "../mixins/maximize.js";
export default function dms() {
    return { ...maximizeMixin() }
}
```

**`clipboard.js` — the secure-context trap:** `navigator.clipboard` is `undefined` outside a secure context (HTTPS, or `localhost`/`127.0.0.1`). Over plain HTTP via a LAN IP, `navigator.clipboard.writeText(...)` throws a synchronous `TypeError` before `.then()`/`.catch()` attach — a copy button silently fails. The shared mixin `copyText(text, message, type)` guards `navigator.clipboard && window.isSecureContext` first, falling back to a hidden-`<textarea>` + `document.execCommand('copy')` (`fallbackCopyText`) otherwise. Both paths surface success/failure through a single overridable hook `_copyToast(message, type)`, whose default dispatches the global `toast` window event (`<x-ui.modals.toast/>`).

```javascript
import clipboardMixin from "../mixins/clipboard.js";
export default function calculator() {
    return { ...clipboardMixin(), copyLedger() { this.copyText(text, 'رسید محاسبات کپی شد.'); } }
}
```

**Overriding `_copyToast`:** `settings.js`'s double-click-copy has its own floating `.toast-floating` element; it spreads `...clipboardMixin()` for the guard+fallback then overrides only `_copyToast()`. Rule: never duplicate the guard/fallback; override `_copyToast` for a bespoke toast.

**Scope — data factories only, not stores:** the mixin spreads into a `data/` factory's return object (Alpine component scope, where `this.$dispatch` and per-instance overrides live). Do NOT spread it into a `stores/` singleton. The one remaining bare `navigator.clipboard.writeText` lives in `stores/filament-menu.js`'s `share()` intentionally: admin-panel-only (always HTTPS in prod, localhost in dev → `isSecureContext` reliably true), with a `navigator.canShare?.()` primary path and `window.FilamentNotification` toast. Leave it.

**Named-slot variant:** when a page has several independently-maximizable widgets (energy chart, taskboard columns), one shared backdrop requires a single active slot rather than N booleans. The factory spreads `...maximizeMixin()` for `applyMaximize`, declares `maximized<Thing>: null`, and overrides `toggleMaximize(name)` to set the active slot and reuse `applyMaximize()`. The view binds one root `<template x-if="maximized<Thing>">` backdrop and each item's `:class="{ '…': maximized<Thing> === name }"`. Maximization is mutually exclusive by construction (two `position:fixed` overlays cannot coexist) and avoids the bug where a bare `@click="toggleMaximize"` passes the `MouseEvent` as `context`. Items carry no `x-data` — every expression resolves up to the single parent factory scope (Alpine scope inheritance): one source of truth.

**DOM-sourced item key + lifted per-item state:** for a repeated item (taskboard column) needing per-item state (collapse), don't give each item its own `x-data` with inline `localStorage`. Lift state to the parent factory as a map keyed by the item's DOM identifier (`collapsed: {}`), seed it from `localStorage` in `init()` by scanning `[data-column]` elements, expose `isCollapsed(name)`/`toggleCollapsed(name)`. The key is read from the DOM via `col(el)` = `el?.closest('[data-column]')?.dataset.column`; every Alpine expression uses `col($el)` (or `$el.dataset.column` on the item). This avoids the quoting pitfall where `{{ $column }}` interpolated *inside a nested JS string literal* (e.g. `'[data-column=\" {{ $column }}\"]'`) breaks the entire `:class`/`x-show` binding. Rule: put the PHP value only in a plain HTML attribute (`data-column="{{ $column }}"`), never inside an Alpine expression's string literal.

**Partial-width maximize:** `.max-widget` is full-screen (`inset: 1.5rem`), which collapses narrow items (a 280px taskboard column barely changes). Use `.max-widget-column` instead (centered, `width: clamp(60vw, 70vw, 75vw)`, `height: 85vh`, `z-index: 999999`) so the maximized item occupies 60–75% of the viewport.

**One reusable backdrop (`<x-ui.modals.max-backdrop/>`):** a single Blade component backs both modes. Two expression props — `state` (truthy-when-maximized, default `"max"`) and `close` (click handler, default `"toggleMaximize()"`) — render `<template x-if="{!! $state !!}"><div class="max-backdrop" @click="{!! $close !!}"></div></template>` (raw echo so quotes survive). Single-boolean modules (contact, dms) include it with no props. Named-slot modules (energy `maximizedWidget`, taskboard `maximizedColumn`) pass `state="maximized<Thing>" close="toggleMaximize(null)"`. The `close` default is an *explicit call* — a bare `@click="toggleMaximize"` makes Alpine inject the `MouseEvent` as the first argument, which `toggleMaximize(name)` would store as the active slot (the original silent-close bug).

**Endless/DB-driven list maximize (feeds):** named-slot + DOM-sourced-key compose for an infinite, server-paginated list (feeds carousel). Factory spreads `...maximizeMixin()`, declares `maximizedFeed: null`, overrides `toggleMaximize(name)`, adds `feed(el)` = `el?.closest('[data-feed]')?.dataset.feed`. Because the list is endless, the slot key is the feed **id** (`data-feed="{{ $feed->id }}"`), never a hardcoded name — and `data-feed` is kept **distinct** from other data attributes (`feeds` also has `data-feed-id` for the active-card tracker; `updateActiveItem()` reads `dataset.feedId`, so they never collide). Use the **partial-width** `.max-widget-column` on the per-item wrapper. `.max-widget-column` sets its own `transform: translate(-50%,-50%) !important`, which already overrides the carousel's resting `scale-105`/`scale-95` (non-important) — do **not** add `!scale-100` (two same-specificity `!important` transforms is fragile). The carousel wrapper's `grayscale`/`opacity` from its `:class` are not touched by `.max-widget-column`, so the maximize `:class` key cancels them: `'max-widget-column !grayscale-0 !opacity-100': maximizedFeed === feed($el)`. Rule: pick the maximize class whose own `!important` declarations cover as many resting visual effects as possible, then `!`-cancel only what it doesn't cover; never stack two `!important` transforms.

**The z-10 trap (no intermediate stacking-context ancestor):** `<x-ui.modals.max-backdrop>` (`position:fixed; z-999998`) and the maximized card (`z-999999`) must resolve at the **page-root** stacking context — like taskboard, whose root `<div x-data="taskboard()" class="... relative ...">` is `relative` with **no z-index** (not a stacking context). A `position:fixed` element's stacking-context parent is its nearest ancestor stacking context (fixed changes the containing block, *not* the stacking parent). Giving the feeds scroll container `x-ref="timeline"` a `z-10` makes it a stacking context that **traps** the fixed backdrop+card at page-root z-10 — under the prev/next buttons (z-40), sticky filters (z-20), etc. **Fix: `relative` (positioned, no z-index) instead of `z-10`** — `relative` is not a stacking context, so fixed backdrop/card escape to page-root (matching taskboard), while still painting the timeline above the decorative `z-0` absolute lines (DOM order). Dashboard chrome (`#navbar`/`#header`/`#footer`) is hidden by `maximizeMixin.applyMaximize` adding `.layout-hidden` (`visibility:hidden`). Rule: a maximize host must not put a z-index on the scroll container holding the maximized items.

**Hide sibling chrome ghosting through the semi-transparent backdrop:** once the trap is removed, prev/next scroll buttons (`absolute z-40`, siblings of the scroll container) resolve below the backdrop (`40 < 999998`) — but `.max-backdrop` is only 60% opaque (`color-mix(primary 60%, transparent)`), so anything at `z < 999998` shows through at 40% (faint ghost buttons). Fix: gate with `x-show="!maximizedFeed"` to remove them from layout on maximize. Same family as the sticky `z-20` filters (hidden via `:class`), but these are *siblings* of the scroll container (`x-show` is fine — no Livewire morph concerns).

**Backdrop fade must match the card's entrance (`max-backdrop--sync`):** `.max-backdrop` fades in over `0.1s`, but `.max-widget-column` over `0.4s` — with a 0.1s backdrop and 0.4s card, the scene is fully dark at 0.1s while the card is 25% visible (reads as overlay, not backdrop). An opt-in `class` prop on `<x-ui.modals.max-backdrop>` (default `''`) lets a module pass `class="max-backdrop--sync"`; the scoped rule `.max-backdrop.max-backdrop--sync { animation-duration: 0.4s }` matches just that instance. `.max-widget-column`'s `transform: translate(-50%,-50%) !important` overrides `slideScaleIn`'s transform keyframes, so only the card's **opacity** animates — matching the backdrop's opacity-only `fadeIn` is the right coordination. Match the card duration (feeds/taskboard 0.4s; full-screen `.max-widget` 0.6s).

**Aggregate-emoji givers as a native tooltip:** hovering an aggregated emoji button (`@foreach($feed->reactions->groupBy('emoji') as $emoji => $reactions)`) reveals givers via `title="کسانی که واکنش دادند: {{ $reactions->pluck('user.name')->filter()->implode('، ') }}"`, **not** a positioned popover. The feed card root is `overflow-hidden` and body `overflow-y-auto`, so any `position: absolute` popover (`ui.modals.popover`) is clipped near edges; a native `title` floats free of every `overflow` ancestor. Givers are eager-loaded (`reactions.user` in the `Feeds` computed), so `pluck('user.name')` adds no queries. The button keeps `wire:click="toggleReaction(...)"` (different gestures, no collision). Rule: for a read-only hover reveal inside an `overflow`-clipped container, prefer a native `title`; reserve `ui.modals.popover` (click-toggle) for menus floating rich content where clipping isn't a risk.

**Hide the tab's own chrome on maximize:** feeds top chrome (page title + focus-banner + filters) is hidden by wrapping those includes in `<div :class="{ 'hidden': maximizedFeed }" class="flex flex-col gap-6">`. This is a feeds-local Alpine toggle (not the shared dashboard-shell hide), needed because the filters are `sticky top-0 z-20` — a stacking context above the timeline that would paint *over* the card. Use `:class` with Tailwind `hidden` (`display: none`), **not** `x-show`/`x-transition` (transition can leave the element visible if `transitionend` doesn't fire) and **not** `x-if` (fights Livewire morph, re-initializes filters' `x-data` on restore). `display: none` removes the chrome's space so the timeline expands into the focus view (the dashboard shell's `visibility: hidden` would leave a gap).

**Input maximize — prop vs. direct trigger/overlay (the `$refs` rule):** `<x-ui.forms.textarea>` exposes a `maximizable` prop bundling the whole feature (`wire:ignore.self` root, `x-data="{ value: @entangle(...).live, fullscreen: false }"`, `pl-10` padding, `<x-ui.forms.maximize-trigger>` + `<x-ui.forms.maximize-overlay>`). For a **plain** input (comment *edit* box — no bespoke `x-data`, no `$refs`): `<x-ui.forms.textarea wire:model="commentForm.content" label="ویرایش نظر" :maximizable="true"/>`. For a **bespoke** input whose `<textarea>` is referenced by `$refs` (feeds main comment box: emoji picker calls `this.$refs.commentInput` for `insertEmoji`), **do not** swap it onto the component — the component's own `x-data` would sit between picker and `<textarea>`, and Alpine registers `x-ref="commentInput"` on the component's scope, so `$refs.commentInput` resolves `undefined`. Keep the bespoke `<textarea>` untouched and reuse only `<x-ui.forms.maximize-trigger>` + `<x-ui.forms.maximize-overlay>`, adding `value: @entangle(...).live, fullscreen: false` to the bespoke `x-data`. Rule: the `maximizable` prop is for plain inputs; a `$refs`-driven bespoke input reuses the trigger/overlay components directly — never nest a second `x-data` between a `$refs` consumer and its target.

**Feed blades stay clean — logic in dedicated factories, entangle spread inline:** the comment-input (`comments.blade.php`) and reaction strip (`actions.blade.php`) inline `x-data` moved to two `data/` factories registered in `main.js`: `feedComposer` (`showEmoji`, `fullscreen`, `panelStyle`, `emojis`, `toggleEmoji`/`insertEmoji`/`onEnter`/`autoGrow`) and `feedReactions` (`page`, `per`, `emojis`, `selected`, `next()`). Blades keep only markup + directive refs. The Livewire entangle stays **inline in the blade** and the factory is **spread** alongside it: `x-data="{ value: @entangle('newComments.<id>').live, ...feedComposer(<id>) }"`. Why spread, not `x-data="feedComposer(<id>, @entangle(...).live)"`: `@entangle` compiles to `Livewire.find(...).entangle(...)`, and Livewire's Alpine plugin recognizes an entangled property by scanning the `x-data` *expression string* for that call — entangle must appear directly in the object literal, not buried in a function argument. The factory deliberately omits `value`; it owns `fullscreen` and all methods. The reaction strip has no entangle: `x-data="feedReactions(<feedId>, '<selectedEmoji>')"`. The `$refs` rule still holds: `feedComposer`'s `insertEmoji`/`onEnter` use `this.$refs.commentInput`, so `<textarea x-ref="commentInput">` stays a direct child of the same `x-data` scope — no nested `x-data` between them.

**Emoji dictionary centralized in `stores/emoji.js`:** no blade re-declares emoji arrays. Alongside the categorized `emojis` export (used by `contact.js`), two hand-picked `const` exports serve feeds: `feedEmojis` (composer picker grid) and `feedReactions` (21 quick-reaction set). `feedComposer`/`feedReactions` import them directly. The old PHP `$emojis` reorder (float the user's current reaction to page 0) moved into `feedReactions`' object initializer, keyed by `selectedEmoji` passed from the blade (`$userReaction?->emoji`, PHP-sourced). Rule: emoji data has exactly one source (`stores/emoji.js`); feed blades/factories import it, never re-declare inline — neither as an Alpine array (`emojis: [...]`) nor as a PHP array (`$emojis = [...]`) through `json_encode`.

---

## 4. PWA & Service Worker
Workbox via the Vite PWA Plugin (`injectManifest` strategy).

### 4.1 The Service Worker (`sw.js`)
`injectManifest` (not `generateSW`) for manual control over the lifecycle. Vite's hashed assets are injected into Workbox's precache.

```javascript
import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching'
import { clientsClaim } from 'workbox-core'

self.skipWaiting()
clientsClaim()
cleanupOutdatedCaches()
precacheAndRoute(self.__WB_MANIFEST)
```

### 4.2 Vite Configuration Rules
* **Heavy Assets Only:** aggressively cache `/build/assets/**/*.{js,css,woff2,png}`.
* **SPA Bypass (`navigateFallback: null`):** strictly disable HTML caching. Serving cached HTML causes Livewire to fail (stale CSRF tokens, mismatched DOM diffing keys). The server must always handle HTML.

---

## 5. Developer Decision Matrix

| When you need to... | Do this... | Why? |
| :--- | :--- | :--- |
| Build a complex dropdown menu | Create `data/dropdown.js`, register in `main.js`. | Keeps Blade clean; localizes toggle logic. |
| Manage selected language globally | Create `stores/locale.js`. | Disconnected components (header, footer, settings) read this state. |
| Import a massive charting library (Three.js) | Import dynamically inside the component's `init()`. | Prevents bloating `app.js` and ruining initial TTI. |
| Cache a new font file | Import via CSS or Vite; ensure it falls under Workbox `globPatterns`. | The SW only precaches assets it knows about at build time. |

---

## 6. Absolute Anti-Patterns

❌ **Do not write `<script>` tags in Blade for Alpine logic.** Breaks CSP, can't be minified by Vite, litters the DOM. Extract to `js/components/alpine/data/`.

❌ **Do not cache HTML responses or `/api` routes in the Service Worker.** Causes 419 Page Expired errors and broken form submissions.

❌ **Do not use `window.Theme = 'dark'` for global state.** Alpine cannot react to plain Window mutations. Use `Alpine.store('appTheme').set('dark')`.

## 7. Modal Open Animation & Heavy Slot Content
The shared modal shell (`.custom-modal`) opens by animating **`width`/`height`** (geometry), not `transform` (see `resources/css/core/dashboard.css`). Geometry animation can't run on the compositor, so the browser re-runs layout on the modal subtree every frame for ~1s.

⚠️ **The slot stays laid-out while invisible.** `.custom-modal-content` is `visibility: hidden` + `opacity: 0` until geometry finishes, but `visibility: hidden` still performs layout (unlike `display: none`). A heavy form is reflowed ~60× during the open animation even though it isn't painted. Dominant cost: text re-wrapping at each intermediate width. A wider final layout (`!w-full` on `modal-inner-card` vs. default 66.666%) raises per-frame reflow cost.

✅ **Defer the slot's layout until geometry completes** — scope to the one form, leave the shared modal untouched:

```blade
<div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl"
     x-data="{ tab: '{{ $defaultTab }}', ready: false }"
     x-effect="if (show && !ready) { setTimeout(() => { if (show) ready = true }, 1000) } else if (!show) { ready = false }"
     x-show="ready">
```

* `x-effect` reads the parent modal's entangled `show` (scope chain preserved through `x-teleport="body"`). 1000ms timer matches `.custom-modal-content`'s opacity delay (`ease 1s`); on close it resets `ready`.
* `x-show="ready"` keeps the form `display: none` during geometry → zero per-frame reflow. At 1s the form lays out **once**, exactly when scheduled to fade in. `wire:model` bindings stay intact.
* The timer re-checks `if (show)` so a close-during-pending or quick reopen can't leak a stale `ready = true`.
* **Adopters:** `livewire/dashboard/taskboard/form.blade.php`, `livewire/dashboard/tab/calendar/create.blade.php` (form modals — expanded sizing + defer on `modal-inner-card`); `livewire/dashboard/tab/status/about-me.blade.php` (defer on main content container only — its `max-w-2xl` profile frame and decorative absolute layers stay untouched); `livewire/dashboard/suggestion/create.blade.php` (defer on the flowchart modal's `w-full` image wrapper).
* **Do not** reach for `max-widget` (page-level fullscreen overlay) inside this modal — `.custom-modal` has `transform` + `contain: strict` + `overflow: hidden`, which re-contains a `fixed` descendant unpredictably. Expanding the card *within* the modal is the correct adaptation.

---

## 8. UI Sound Store (`stores/sound.js`) — outgoing send sound + per-chat mute
A single global Alpine store backs the outgoing (send) chat sound and per-chat mute for both contact and channel modules. Registered like other stores (`import registerSoundStore from './stores/sound.js'` + `registerSoundStore(Alpine)` in `main.js`); auto-inits via `init()`.

**Two scopes, disjoint id spaces:** `mutedChannels: []` + `mutedContacts: []` (array-backed, NOT `Set` — Alpine reacts to `Array` `splice`/`push` but not `Set.has`), persisted to `localStorage['chat-muted-channels']` / `localStorage['chat-muted-contacts']`. Every method takes `scope = 'channel'` (`_set`/`_key` pick list/storage-key); contact callers pass `'contact'`. Channel ids and contact user ids are disjoint, so two sets — never one.

**One sound, one cached `Audio`:** `_audio: null` lazily built on first play from `document.querySelector('[data-outgoing-sound]')?.dataset.outgoingSound` and cached (NOT a fresh `new Audio` per play — `currentTime = 0` restarts it, no per-send allocation/GC). `volume = 0.35`; `play().catch(() => {})` (send is a user gesture → autoplay unlocked). One `data-outgoing-sound` attr on each module root (`channel.blade.php`, `contact.blade.php`): `{{ asset('build/assets/audio/outgoing.mp3') }}` — URL-in-view precedent (same as `stopwatch.blade.php`'s `alarm.mp3`), no inline `<script>`. Asset copied by `vite-plugin-static-copy` (whole `resources/assets/audio` dir → `public/build/assets/audio`); standard name `outgoing.mp3`.

**Methods:** `isMuted(id, scope)` / `toggleMute(id, scope)` (push/splice + persist, `try/catch`-wrapped); `playOutgoing(id, scope)`.

**`playOutgoing` contract:** `id !== null && isMuted(id, scope)` → early return (per-chat mute; the active chat's id is passed so a muted chat is silent). `document.hidden` → early return. URL + `Audio` resolved lazily on first play (module root isn't in the DOM at `alpine:init`). Only one chat module is mounted at a time; both set the same URL, so the cache is valid across module switches.

**Caller-side fault isolation (mandatory):** `message-sent` handlers call `this.$store.sound?.playOutgoing(...)` with optional chaining, ordered **after** the critical UI state (`scrollToBottom` + `sending = false`) — the sound is non-essential and must never strand the composer in `sending = true`. `$store.*` resolves inside `@island` (channel header/sidebar mute live in islands) because stores are global, not scope-bound.

**No incoming (receive) sound:** incoming detection was evaluated and dropped. Contact side is blocked by Livewire's snapshot model — `#[Computed]` values (`lastMessageId`/`lastMessageIsMine`) are NOT serialized into the snapshot (only public properties, via `getPublicPropertiesDefinedOnSubclass`), so `$watch('$wire.lastMessageId')` never fires on the client; a correct fix needs `Livewire.hook('morph')` + per-row `data-mine` + switch/seed guards. Channel side is feasible (sidebar-poll piggyback reading `data-last-at`/`data-last-mine`) but would give inconsistent UX against a silent contact module. Outgoing-only is the deliberate minimal choice.

**Mute UI (both modules):**
- **Header toggle** (`channel/header.blade.php`, `contact/header.blade.php`): first control in the actions cluster, `:class="… ? '!bg-primary !text-on-primary' : 'hover:brightness-95'"` (muted = primary fill). Glyph swaps via `x-text` (`volume_off`/`volume_up`) — NOT Blade `{{ }}`. `aria-pressed` carries state; `aria-label`/`title` carry the **next action**. Channel uses `$header['id']`; contact uses `$activeContact->id` + `'contact'` scope. Client-only — no server round-trip, no IDOR.
- **Channel sidebar hover shortcut** (`channel/sidebar.blade.php`): the channel row is a `<div role="option" tabindex="0">` (NOT `<button>`) with `x-on:keydown.enter/space.prevent` → `selectChannel`, because a real mute `<button>` nests inside it (`<button>` content model forbids a `tabindex`/interactive descendant). The nested mute `<button>` (hover/focus-revealed via `opacity`/`scale` + `pointer-events-none` idle / `pointer-events-auto` on `group-hover`/`focus`/muted) carries `x-on:click.stop` + `x-on:keydown.enter.stop`/`x-on:keydown.space.stop` — `.stop` (no `.prevent`) stops keydown bubbling to the row's `selectChannel` WITHOUT suppressing the button's native synthesized click, so keyboard Enter/Space mutes (not selects). `pointer-events-none` on idle is mandatory: without it the invisible 24px target intercepts taps on touch devices (no `group-hover`), toggling mute instead of opening the channel. The icon `<span>` is `aria-hidden="true"`. The contact sidebar (`contact/sidebar.blade.php`) mirrors this: its row was converted from `<button wire:click="selectContact(...)">` to `<div role="option" tabindex="0" x-on:click="$wire.selectContact(...)" x-on:keydown.enter.prevent="$wire.selectContact(...)" x-on:keydown.space.prevent="$wire.selectContact(...)">` so the same per-row hover mute `<button>` (scope `'contact'`, labels 'بی‌صدا کردن مخاطب'/'باصدا کردن مخاطب') can nest inside — `<button>` cannot nest a `<button>`, and the contact row is a plain `@include` (NOT an `@island` like channel), so there is no Alpine `selectChannel`-style wrapper; the row calls `$wire.selectContact` directly via `x-on:click` (the exact equivalent of the old `wire:click`), with `enter`/`space` `.prevent` keyboard handlers so the `<div>` keeps the activation the native `<button>` provided. `wire:key` stays on the element (works on any tag); `group` is added to the row's `@class` so the mute button's `group-hover:` reveal fires.
- **Sidebar mute-all button (both modules):** `isAllMuted(ids, scope)` / `toggleAll(ids, scope)` — array-populating bulk mute writing the SAME `mutedChannels`/`mutedContacts` arrays per-item uses, so the two compose: `toggleAll` mutes-all by pushing every missing id (or un-mutes-all by reverse-iterating and splicing every id present — backward iteration avoids the index-shift bug); a per-item `toggleMute` that removes one id flips `isAllMuted` false, so the mute-all button auto-reverts to idle without a second store. `playOutgoing` is unchanged (it already consults `isMuted`). The button lives in each sidebar's title-row right cell, wrapped in `flex items-center gap-1.5` alongside the unread badge (badge first, button last/rightmost), guarded by `@if(count($list))`. It is 24px (`w-6 h-6` — sidebar scale, smaller than the 28px `min-w-[28px] min-h-[28px]` header action cluster, which shrank from 40px `min-w-10 min-h-10` with glyphs 18→16px to hold ~57% fill; the per-row hover mute is the same 24px `w-6 h-6` with a 14px glyph, ~58% fill) and mirrors the header mute vocabulary: primary fill + `volume_off` when all-muted, `surface-variant` + `hover:brightness-95` + `volume_up` when not, `aria-pressed = isAllMuted`, `aria-label`/`title` = next action ('بی‌صدا کردن همه …' / 'باصدا کردن همه …'), glyph via `x-text`. Rendered ids passed as inline JSON: `$allChannelIds = collect($channelList)->pluck('id')->map(fn($id) => (int) $id)->values()->toJson()` in the sidebar `@php` block, then `{{ $allChannelIds }}` in the binding — a non-string-literal function argument, so it does NOT trip the quoting pitfall, and it re-evaluates on Livewire poll/morph like the existing `:class` bindings that interpolate `{{ $contact['unread'] }}`. Channel scope `'channel'` / ids `$allChannelIds`; contact scope `'contact'` / ids `$allContactIds`. Client-only.

## 9. Browser push-notification opt-in store (`stores/push.js`) — foreground-only, zero backend
A minimal Alpine store gating the native `Notification` API behind a per-module toggle, scoped to **foreground/backgrounded-tab notifications only** — no Web Push subscription, no VAPID keys, no `push_subscriptions` table, no server-triggered send. Registered like `sound`/`theme` (`import registerPushStore from './stores/push.js'` + `registerPushStore(Alpine)` in `main.js`).

**Shape mirrors `sound.js`** — a single-flag-per-scope variant instead of an id-array variant, since `Notification` permission is browser-wide, not per-conversation: `supported` (feature-detected once, `typeof Notification !== 'undefined'`), `isEnabled(scope)` (true only when BOTH `Notification.permission === 'granted'` AND scoped `localStorage['chat-push-{scope}']` is `'1'` — the local flag lets a user opt back OUT without revoking the OS-level permission), `toggle(scope)` (requests `Notification.requestPermission()` only on enable; denied/dismissed leaves the flag unset), `notify(title, body, scope)` (fires `new Notification(...)` only when `document.hidden` is true AND `isEnabled(scope)` — the inverse of `sound.playOutgoing`'s hidden-tab guard; a foreground tab already has the in-app UI + outgoing sound, so it stays silent).

**UI (both sidebars):** a `w-6 h-6` icon toggle placed immediately after the existing mute-all button in the title-row right cell (same 24px/14px-glyph sizing, same `bg-[var(--md-sys-color-surface-variant)]` idle / `!bg-primary !text-on-primary` active vocabulary). Guarded by `x-show="$store.push.supported"` (Notification API absent on some browsers/contexts) rather than `@if` — support is a client-only fact Blade can't know at render time. Glyph swaps `notifications_off`/`notifications_active` via `x-text`, `aria-pressed`/`aria-label`/`title` follow the same "next action" convention. Channel scope `'channel'`, contact scope `'contact'` — same disjoint-scope rationale as the sound store.

**Trigger wiring — two mechanisms, because the two modules poll differently:**
- **Channel** has a real JS poll loop (`startPolling()`'s `setInterval` → `$wire.$island('sidebar').refreshUnread().then(...)`), so the trigger rides that `.then()` directly: `syncPushNotify()` reads a new `data-total-unread="{{ $totalUnread }}"` attribute off the sidebar `<aside>` (same `querySelector('[data-*]')` idiom `syncChannelCount()` uses for `data-channel-count`), diffs against `this._lastUnread`, calls `$store.push.notify(...)` only on an increase. Also called once in `init()` to seed the baseline (guarded by `this._lastUnread !== undefined`, so page load never fires a spurious notification).
- **Contact** has no JS poll loop — its root div uses bare `wire:poll.10s` with no callback. Converting it to a JS-driven poll would be a materially bigger, riskier change. Instead, `contact.js` observes the same `data-total-unread` attribute (added to `contact/sidebar.blade.php`'s `<aside>`) with a `MutationObserver({subtree: true, attributes: true, attributeFilter: ['data-total-unread']})` rooted at `document.body` — fires `syncPushNotify()` whenever Livewire's poll-driven morph patches that attribute, with **zero changes to `wire:poll.10s` itself**. Rooted at `document.body` (not the specific `<aside>`) deliberately, so it survives Livewire ever fully replacing that element rather than patching it in place. Disconnected in `destroy()` alongside the module's other listener cleanup.

Both `syncPushNotify()` implementations are otherwise identical (read attribute → diff → notify → update baseline) — the only difference is what triggers the read.