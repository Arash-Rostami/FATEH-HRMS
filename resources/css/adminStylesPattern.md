# Admin Panel (Filament) CSS Architecture Documentation

## Overview
This document defines the CSS architectural patterns applied to the Filament Admin Panel. It outlines how Filament's core styles are modified, injected, and tightly synchronized with the overarching customized Material Design 3 (MD3) token system used in the rest of the application.

---

## 1. Directory Structure & Inclusions
The core Admin Panel stylesheets reside in `resources/css/core/`.

```
resources/css/
└── core/
    ├── filament.css       # Core Overrides & Filament Theme Registration
    ├── notification.css   # Filament Notification styling & overriding
    ├── theme.css          # Inherited MD3 Tokens from User Panel
    └── animation.css      # Inherited Motion Primitives from User Panel
```

### 1.1 The Integration Pipeline
The `filament.css` file acts as a custom Vite theme for Filament. It heavily imports both native Filament architecture and the project's custom core files to ensure the Admin Panel behaves identically to the User Panel visually.

```css
/* resources/css/core/filament.css */
@import "../../../vendor/filament/filament/resources/css/theme.css";
@import "./theme.css";       /* Bridges MD3 Tokens into Admin */
@import "./animation.css";   /* Bridges standard motion into Admin */
```

---

## 2. Token Mapping to Filament (`filament.css`)
Filament internally relies on a strict set of Tailwind colors (`primary`, `secondary`, `gray`, etc.). Instead of defining explicit HEX values in `tailwind.config.js`, we map Filament's expected CSS variables directly to our dynamic MD3 tokens using CSS `color-mix()`.

### 2.1 Color Palette Syncing
This guarantees that when the global theme changes (e.g., from 'Jade' to 'Magneta'), the Admin Panel updates perfectly in real-time.

```css
:root {
    /* Mapping Filament's internal primary scale to MD3 custom properties */
    --primary-50: var(--md-sys-color-primary-container);
    --primary-500: var(--md-sys-color-primary);
    --primary-600: color-mix(in srgb, var(--md-sys-color-primary), black 10%);
    --primary-950: color-mix(in srgb, var(--md-sys-color-primary), black 65%);

    /* Secondary mapping */
    --secondary-50: var(--md-sys-color-secondary-container);
    --secondary-500: var(--md-sys-color-secondary);
}
```

---

## 3. Class Overrides and Theming
We enforce a highly rounded, glassmorphic, fluid aesthetic across Filament by aggressively overriding internal `.fi-*` classes.

### 3.1 Component Shaping & Shadows
We alter the core geometry of Filament panels, inputs, and modals to match our enterprise "ultra-modern" design language.

```css
@layer components {
    /* Make cards heavily rounded with custom MD3 borders */
    .fi-card {
        @apply !rounded-[1.5rem] !bg-[var(--md-sys-color-surface)]
        !border !border-[var(--md-sys-color-outline-variant)]/50
        !shadow-[0_4px_24px_color-mix(in_srgb,var(--md-sys-color-primary),_transparent_90%)];
    }

    /* Soft, rounded input fields */
    .fi-input {
        @apply !rounded-xl !bg-[var(--md-sys-color-surface-variant)]/30
        !border-none !shadow-none;
    }
}
```

### 3.2 Injecting Animations
We bind our pre-defined motion keyframes directly to Filament component mounting states via overriding classes.

```css
/* Slide up animation for tables */
.fi-ta-table {
    animation: slideUpFade 0.4s cubic-bezier(0.4, 0, 0.2, 1) both !important;
}

/* Staggering table rows */
.fi-ta-row {
    animation: slideUpFade 0.3s cubic-bezier(0.4, 0, 0.2, 1) both !important;
}
.fi-ta-row:nth-child(1) { animation-delay: 0.05s !important; }
.fi-ta-row:nth-child(2) { animation-delay: 0.10s !important; }
```

### 3.3 The "No Shell" Layout Pattern
Sometimes Filament components are embedded directly into custom Blade views without the Admin sidebar. The `.no-shell` utility strips out borders and backgrounds for seamless integration.

```css
.no-shell [class*="fi-"],
.no-shell [class*="ring"],
.no-shell [class*="shadow"] {
    @apply border-0 shadow-none outline-none ring-0;
}
```

---

## 4. Notification Overrides (`notification.css`)
Filament's notification architecture loads as an independent package, meaning its colors must be mapped independently of the main admin panel.

### 4.1 Custom Theme Declaration
We redefine the internal notification variables using the `@theme inline` directive, linking them to our MD3 custom properties to ensure consistency.

```css
@theme inline {
    /* Mapping info/success colors to primary/container if needed, or explicitly setting them */
    --color-primary-500: var(--md-sys-color-primary);
    --color-primary-900: var(--md-sys-color-on-primary-container);

    /* Base structure variables */
    --color-gray-900: var(--md-sys-color-primary-container);
}
```

### 4.2 Modal Shaping
We override the notification modal window behavior to slide in elegantly from the right, adhering to RTL (Right-to-Left) constraints.

```css
:root {
    --sys-z-modal: 9999;
    --sys-anim-standard: cubic-bezier(0.2, 0, 0, 1);
}

.fi-modal-window {
    direction: rtl;
    background-color: var(--md-sys-color-primary-container) !important;
    border-radius: 0 1rem 1rem 0 !important; /* Flush left border */
    animation: slideUpFade 0.25s var(--sys-anim-standard) both !important;
}
```

---

## 5. Decision Matrix

| Scenario | Location | Rationale |
|----------|----------|-----------|
| Overriding the border radius of a Filament Select box | `filament.css` | Global override on `.fi-select` applies to all admin pages. |
| Fixing a z-index issue on Filament's notification toast | `notification.css` | Notifications operate on a separate UI tier; scope overrides here. |
| Adding a new primary shade for a Filament badge | `filament.css` (`:root` map) | Create an explicit mapping to a `color-mix()` token for `--primary-X`. |
| Creating a completely custom Widget layout | Inline / Custom CSS | If it doesn't affect standard `.fi-*` classes, keep it isolated to the widget's view. |

---

## 6. Anti-Patterns

| Violation | Correction |
|-----------|------------|
| Hardcoding `#ff0000` into `filament.css` overrides. | Map the Tailwind variable inside `:root` to a dynamic CSS token like `--md-sys-color-error`. |
| Writing duplicate animations in `filament.css`. | Import `./animation.css` and use existing utilities or keyframes. |
| Using `@apply bg-white` for cards. | Use `@apply bg-[var(--md-sys-color-surface)]` to respect Light/Dark modes seamlessly. |
| Modifying `$primary` in a `tailwind.config.js` file for Filament. | Keep the CSS mapping layer in `filament.css` as the source of truth for color injection, powered by `theme.css`. |
