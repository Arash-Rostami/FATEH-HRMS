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