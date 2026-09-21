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
└── animation.css
```

`filament.css` is the engine (Filament overrides and token mapping). `notification.css` is the overlay (customizing Filament's isolated notification UI). `theme.css` and `animation.css` are inherited from the User Panel.

### 2.1 Vite Injection Strategy
`filament.css` is the master custom Vite theme for Filament. CSS cascades require importing Filament's core first, then custom tokens, then overrides.

```css
@import "../../../vendor/filament/filament/resources/css/theme.css";
@import "./theme.css";
@import "./animation.css";
```

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
Centered modals have one fixed standard height in `filament.css` — never taller, never collapsed:

```css
.fi-modal:not(.fi-modal-slide-over):not(.fi-width-screen) > .fi-modal-window-ctn > .fi-modal-window {
    @apply !h-[85dvh] !overflow-y-auto;
}

.fi-modal:not(.fi-modal-slide-over):not(.fi-width-screen) .fi-modal-content {
    @apply !flex-1;
}
```

*Every centered modal (infolist/view/form/confirm) is exactly 85dvh; longer content scrolls inside the window, sparser content leaves breathing room with the footer pinned via `flex-1`.* Slide-overs (`fi-modal-slide-over`), full-screen (`fi-width-screen`), and the `#database-notifications` drawer (a slide-over) are excluded by the `:not()` guards — their geometry stays vendor/`notification.css`-owned. Vendor default is auto-height (window sizes to content), which is why sparse infolists used to collapse.

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