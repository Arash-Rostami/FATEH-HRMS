# Admin Panel (Filament) Design System & CSS Architecture

## 1. Design Vision & Integration Philosophy
**Goal: completely mask Filament's default aesthetic** so the user cannot tell they've crossed from the custom Livewire User Panel into the Filament Admin Panel.

1. **Total Theme Synchronization:** the Admin panel strictly obeys the Light/Dark mode and Color Theme selected in the User Panel.
2. **Soft Geometry:** Filament's sharp corners and flat borders are overwritten with extreme rounding (`rounded-2xl`, `rounded-3xl`) and soft, radiant drop shadows.
3. **Immersive Motion:** custom `animation.css` keyframes are injected into Filament's internal classes so pages, tables, and modals glide into view.

---

## 2. Directory & Theming Pipeline

```
resources/css/core/
├── filament.css
├── notification.css
├── theme.css
├── animation.css
├── ambient.css
├── color.css
└── dashboard.css
```

`filament.css` is the engine (Filament overrides and token mapping). `notification.css` is the overlay (customizing Filament's isolated notification UI). `theme.css` and `animation.css` are inherited from the User Panel.

### 2.1 Vite Injection Strategy
`filament.css` is the master custom Vite theme for Filament. CSS cascades require importing Filament's core first, then custom tokens, then overrides.

```css
@import "../../../vendor/filament/filament/resources/css/theme.css";
@import "./theme.css";
@import "./animation.css";
```

(the snippet above is the Filament-relevant middle of the file — `filament.css` also imports `swiper-bundle`, `tailwindcss` and `material-symbols` around those three lines)

---

## 3. Token Mapping: Bridging MD3 to Filament
Filament expects strict Tailwind color variables (`--primary-500`); it does not understand our MD3 system (`--md-sys-color-primary`). We map our dynamic tokens into Filament's expected structure inside `:root`.

### 3.1 The Color-Mix Translation Layer
Filament-required shades (e.g. `primary-600` for hovers) are generated with CSS `color-mix()` to darken/lighten the active MD3 token on the fly.

```css
:root {
    --primary-50: var(--md-sys-color-primary-container);
    --primary-500: var(--md-sys-color-primary);
    --primary-600: color-mix(in srgb, var(--md-sys-color-primary), black 10%);
    --primary-950: color-mix(in srgb, var(--md-sys-color-primary), black 65%);
}
```
*Developer guideline:* add a new theme in `theme.css` — no need to update `filament.css`. The `color-mix()` math generates the Filament palette from the new primary automatically.

---

## 4. UI Morphing: Overriding Filament Core (`.fi-*`)

### 4.1 Reshaping Cards and Inputs
Filament cards are rounded heavily with custom Radiant Shadows (tinted with the primary color, not gray).

```css
@layer components {
    .fi-card {
        @apply !rounded-[1.5rem] !bg-[var(--md-sys-color-surface)]
        !border !border-[var(--md-sys-color-outline-variant)]/50
        !shadow-[0_4px_24px_color-mix(in_srgb,var(--md-sys-color-primary),_transparent_90%)];
    }

    .fi-input {
        @apply !rounded-xl !bg-[var(--md-sys-color-surface-variant)]/30
        !border-none !shadow-none;
    }
}
```

### 4.2 Injecting Cinematic Motion
Pre-defined animations attach to Filament's structural classes so data flows rather than snaps.

```css
.fi-ta-table {
    animation: slideUpFade 0.4s cubic-bezier(0.4, 0, 0.2, 1) both !important;
}

.fi-ta-row {
    animation: slideUpFade 0.3s cubic-bezier(0.4, 0, 0.2, 1) both !important;
}
.fi-ta-row:nth-child(1) { animation-delay: 0.05s !important; }
.fi-ta-row:nth-child(2) { animation-delay: 0.10s !important; }
```

### 4.2.1 Uncollapse width overshoot — final shape: elastic collapse, instant uncollapse (2026-10-15)
The desktop uncollapse of the admin sidebar read as "wider, then narrower" (overshoot + settle). Diagnosis by bisecting theme motion: ALL theme-side sidebar motion was disabled at once so the panel ran on Filament's pure vendor behavior; the user tested and the overshoot was GONE — culprit = a theme rule. Re-enabling the collapse batch (pill `width/padding .6s`, label fades, group-items transitions, toggle-icon rotate + the open-state freeze) brought the overshoot BACK; stripping only the pill's `width`/`padding` components killed it — **the pill's geometry transition was the culprit**, and the blanket `transition: none` freeze on the open state did not neutralize it.

The fix the user asked for — keep the elastic collapse, land uncollapse instantly — does NOT rely on that freeze for geometry: the pill's `width`/`padding` transition is attached ONLY to the closed state (`.fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item > a`, full `width/padding .6s` + colors). A CSS transition plays only when the TARGET state carries the transition definition, so collapse (target = closed) animates elastically while uncollapse (target = open, colors-only transition) snaps. The open-state freeze block stays in the md+ `@media (min-width: 768px)` section as belt-and-braces, and all diagnosis-disabled rules were restored (`slideInRight` load animation, `.fi-logo` `fadeInDown`, label fades, group-items transitions, icon rotate) behind no markers — the `[sidebar-motion-diagnosis]` era is over. Lesson: for one-directional motion between two states, put the transition on the less-trafficked target state itself rather than trying to suppress it with `transition: none` on the other state.

Compiled-CSS anchors confirmed during the hunt: vendor ships `lg:transition-none` on the aside (its own width never animates at desktop — descendants carried all the motion); the aside's open/close class is toggled plainly by Alpine (`x-bind:class`, `isOpen/isOpenDesktop` persisted in localStorage, no transitions, no JS width writes); the bare `.fi-sidebar { width: 100% !important }` rules live only in `max-width: 400px`/`639px` (mobile) blocks; `--sidebar-width`/`--collapsed-sidebar-width` are inline vars on the base layout (the collapsed one is dead weight — no CSS references it). Decision rule the user set: add the collapse-side batch first and judge; if the overshoot returns on uncollapse, re-enable the remaining disabled rules one at a time. Once the culprit is nailed, delete the leftover `sidebar-motion-diagnosis` markers and rewrite this section to the final state.

### 4.3 The "No Shell" Pattern
When rendering a Filament component (complex Table or Form) inside a custom User Panel view, the `.no-shell` utility strips Filament's structural borders/backgrounds so it sits naked inside custom wrappers.

```css
.no-shell [class*="fi-"],
.no-shell [class*="ring"],
.no-shell [class*="shadow"] {
    @apply border-0 shadow-none outline-none ring-0;
}
```

### 4.4 Infolist Inner Panel — Explicit Marker Class, Not a `:has()` Guess (2026-09-21)
An infolist's outer `Section::make()` render as TWO nested elements, not one: `Filament\Schemas\Components\Section`'s own blade wraps a layout-only `<div class="fi-sc-section">` (no visual styling — `flex flex-col gap-2`, nothing else) around an inner `<x-filament::section>` that actually renders as `<section class="fi-section">` and carries all real background/shadow/padding. `->extraAttributes()` on `Section::make()` lands on the OUTER `fi-sc-section` div, never on the inner `.fi-section` — so a same-class CSS rule written against `.fi-sc-section` (or a `:has()` guess targeting it) visually does nothing; verified by putting `background: magenta` on the naive selector first and seeing it appear only as a thin sliver at the panel's edge (the outer wrapper's `gap-2`), with the real `.fi-section` card opaquely covering the rest.

The other half of the original bug still holds: `Tabs` defaults to `contained(true)`, drawing its own hardcoded `bg-white dark:bg-gray-900` card (vendor default, never MD3-mapped) *around* the inner `.fi-section` — a tabbed infolist showed a different panel color than a non-tabbed one purely from this un-overridden vendor default.

Fix — the SAME explicit marker class on every infolist's outer `Section::make()` AND on every `Tabs::make()` (only the ~8 resources whose infolist actually uses Tabs need the second one):

```php
// every infolist's outer Section (and every per-tab Section, if Tabs are used)
Section::make()
    ->extraAttributes(['class' => 'fi-infolist-panel'])
    ->schema([...]);

// every infolist's Tabs — same marker class, scopes the card-strip rule below
Tabs::make()
    ->extraAttributes(['class' => 'fi-infolist-panel'])
    ->tabs([...]);
```

**`->contained(false)` was tried first and reverted — it's the wrong lever.** It doesn't just remove `Tabs`'s background; the vendor CSS (`filament/support/resources/css/components/tabs.css`) also routes the *tab-button row* through a completely different, unrelated branch when not contained (`.fi-tabs:not(.fi-contained) { @apply mx-auto rounded-xl bg-white p-2 shadow-sm ring-1 ...; }`) — `mx-auto` re-centers the whole tab strip, which is how a one-line "remove this card" change turned into "why are my tabs suddenly centered instead of RTL-aligned" as a completely separate, non-obvious regression. Stay in `contained(true)` (the default) and strip the outer wrapper's own paint with CSS instead — the tab-button row's styling/alignment is entirely untouched by this:

```css
.fi-infolist-panel .fi-section {
    @apply !rounded-2xl
    !bg-[color-mix(in_srgb,var(--md-sys-color-primary-container),_var(--md-sys-color-surface)_35%)]
    !shadow-[var(--md-sys-elevation-1)];
}

.fi-sc-tabs.fi-infolist-panel.fi-contained {
    @apply !bg-transparent !shadow-none !ring-0;
}
```

The descendant selector on the first rule (`.fi-infolist-panel .fi-section`, not `.fi-infolist-panel` alone) is required because the class sits on the OUTER wrapper while the real visual element is the inner `.fi-section`. The second rule targets the Tabs wrapper directly (same element the class lands on for `Tabs::make()`, no nesting quirk there) and only strips its background/shadow/ring — `.fi-contained`'s OTHER effects (border-b removed, tab-list layout) are untouched, so alignment stays exactly as vendor-default. Background is a primary-container tint over `surface` — a mild, unmistakably theme-colored (not neutral-gray) wash, distinct from `.fi-modal-window`'s own plain `--md-sys-color-surface` background so the panel doesn't visually vanish into its own modal. One themed card either way, whether or not `Tabs` wraps it — matching the "one bare Section, no card-stacking" infolist rule in `filamentPattern.md`. Reference implementation: `DepartmentResource::infolist()` (Section-only), `UserResource::infolist()` (Tabs).

### 4.5 Modal Height Discipline
Centered content modals (infolist/view/form) have one fixed standard height in `filament.css` — never taller, never collapsed; small confirmation modals stay content-sized:

```css
.fi-modal:not(.fi-modal-slide-over):not(.fi-width-screen) > .fi-modal-window-ctn > .fi-modal-window:has(fieldset, .fi-in-entry) {
    @apply !h-[85dvh] !overflow-y-auto;
}

.fi-modal:not(.fi-modal-slide-over):not(.fi-width-screen) .fi-modal-content {
    @apply !flex-1;
}
```

*Content modals — infolist/view (`.fi-in-entry` is the root class of every infolist entry, from vendor `entry-wrapper.blade.php`) and form (`<fieldset>` wraps every Filament form schema) — are exactly 85dvh; longer content scrolls inside the window, sparser content leaves breathing room with the footer pinned via `flex-1`.* Confirmation and other small action modals contain neither marker, so the `:has()` guard excludes them and they keep vendor auto-height (content-sized, never super-tall). Slide-overs (`fi-modal-slide-over`), full-screen (`fi-width-screen`), and the `#database-notifications` drawer (a slide-over) are excluded by the `:not()` guards — their geometry stays vendor/`notification.css`-owned. `:has()` is the only scoping lever here: Filament gives all modals the same `.fi-modal-window` classes and hardcodes `role="dialog"`, so no class/width/role discriminator exists (community pattern, GitHub Discussion #14413). The `!flex-1` content rule is a no-op on auto-height windows.

---

## 5. Notification UI Override (`notification.css`)
Filament's notification package operates like a separate micro-frontend and requires its own dedicated CSS file.

### 5.1 Modal Geometry and RTL Flow
The application is primarily Persian (RTL); the notification modal window sits flush against the left edge of the screen — so the left corners are square and the right corners are rounded.

```css
.fi-modal-window {
    direction: rtl;
    background-color: var(--md-sys-color-primary-container) !important;
    border-radius: 0 1rem 1rem 0 !important;
    animation: slideUpFade 0.25s var(--sys-anim-standard) both !important;
}
```

---

## 6. Developer Decision Matrix

| When you need to... | Do this... | Why? |
| :--- | :--- | :--- |
| Style a new Filament plugin | Identify the `.fi-` class in DevTools, override it in `filament.css` using `@apply` and `!important`. | Filament plugins use default UI; we force them to match our soft geometry. |
| Change the color of a Filament Badge | Do nothing. Use Filament's PHP `->color('primary')`. | `--primary-*` is mapped to MD3 in `:root`, so the badge themes itself. |
| Use a Filament table in a custom Livewire view | Wrap the table in `<div class="no-shell">` | Strips the heavy CMS card borders so it blends into the custom page. |

---

## 7. Absolute Anti-Patterns

❌ **Do not configure Filament colors in `AdminPanelProvider.php` using HEX codes.** Breaks the real-time theme switcher. Configure Filament to look for CSS variables controlled in `filament.css`.

❌ **Do not use `@apply bg-white` or `bg-gray-900` to style Filament components.** Hardcoded colors break Light/Dark transitions. Always use `@apply bg-[var(--md-sys-color-surface)]`.

❌ **Do not rewrite `@keyframes` inside `filament.css`.** Redundant and inconsistent. Inherit keyframes from `animation.css`.
