# User Panel CSS Architecture Documentation

## Overview
This document defines the modular CSS architecture for the User Panel, establishing strict separation between dynamic Material Design 3 (MD3) tokens, bespoke dashboard layouts, and scalable motion design primitives.

---

## 1. Directory Structure & Files
The core User Panel stylesheets reside in `resources/css/core/`.

```
resources/css/
├── app.css             # Vite entry point & orchestration
└── core/
    ├── theme.css       # Dynamic Design Tokens (Customized MD3)
    ├── dashboard.css   # Layout, Views, Widgets & Custom Modals
    └── animation.css   # Global Motion Primitives & Keyframes
```

---

## 2. Dynamic Theme Tokens (`theme.css`)
We use a **highly customized interpretation** of the Material Design 3 (MD3) color system. It is heavily expanded with dedicated semantic palettes tailored for an Enterprise HRMS dashboard.

### 2.1 Base Configuration
Theme tokens are exposed as global CSS custom properties and are bound dynamically based on the current theme and color mode context (`data-theme` attribute).

```css
@theme {
    --font-sans: 'Yekan', 'IranYekan', system-ui, sans-serif;
}

:root {
    --theme-transition-duration: 500ms;
    --theme-transition-easing: cubic-bezier(0.4, 0, 0.2, 1);

    /* Semantic functional colors (Example) */
    --online: var(--tool-sapphire-color);
}
```

### 2.2 Domain-Specific "Tool" Colors
Beyond the standard MD3 roles (`--md-sys-color-primary`, `--md-sys-color-surface`, etc.), we declare explicit "Tool" token sets to guarantee consistency across different widget semantics (e.g., charts, badges, labels):

*   `--tool-amethyst-*`
*   `--tool-sapphire-*`
*   `--tool-sage-*`
*   `--tool-gold-*`

### 2.3 Example Theme Implementation
Each theme registers a base block and a paired `.dark` block.

```css
/* "Jade" Theme Default (Light) */
[data-theme='jade'] {
    --md-sys-color-primary: #0F766E;
    --md-sys-color-on-primary: #ffffff;
    --md-sys-color-surface: #F0FAF8;

    /* Bespoke Application Variables */
    --header-bg: #002422;
    --header-border-color: #5CC8BC;

    --tool-sapphire-bg: #deeef2;
    --tool-sapphire-color: #3d7d90;

    accent-color: var(--md-sys-color-primary) !important;
}

/* "Jade" Theme Dark Mode */
[data-theme='jade'].dark {
    --md-sys-color-primary: #80D5CF;
    --md-sys-color-surface: #101C1B;
    /* ... reversed values */
}
```

---

## 3. Layout & Domain Styles (`dashboard.css`)
`dashboard.css` orchestrates the domain-specific logic. It strictly relies on the tokens provided by `theme.css`.

### 3.1 Base Reset and Focus States
We configure a global aesthetic override for accessibility and forms:
```css
*:focus-visible {
    outline: 2px solid var(--md-sys-color-primary);
    outline-offset: 2px;
    border-radius: 8px;
}
```

### 3.2 Glassmorphism & High-End UI Modals
The User Panel extensively employs Glassmorphism, dynamically mixing opacities with the MD3 palette using the `color-mix()` CSS function for perfect thematic blending.

```css
.modal-inner-card {
    background: color-mix(in srgb, var(--md-sys-color-surface) 92%, transparent);
    border: 1px solid color-mix(in srgb, var(--md-sys-color-outline-variant) 60%, transparent);
    box-shadow: 0 8px 32px color-mix(in srgb, var(--md-sys-color-primary) 25%, transparent);
}

.dark .modal-inner-card {
    /* Dark mode explicitly drops mixing in favor of deep solids and stark borders */
    background: var(--md-sys-color-surface);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6);
}
```

### 3.3 Scroll-Driven Animations & Sticky Headers
We utilize modern CSS features like View Timelines (`view-timeline`) for scroll-bound animations.

```css
.sticky-header {
    position: sticky;
    top: 0;
    z-index: 50;
    view-timeline: --scroll-timeline vertical;
    animation-name: slideDownFade;
    animation-fill-mode: both;
    animation-timeline: --scroll-timeline;
    animation-range: entry 0% entry 100px;
}
```

---

## 4. Motion Design (`animation.css`)
Motion primitives are centralized in `animation.css`. The system uses utility classes mapped to highly specific `@keyframes` to ensure the exact same interaction curve is available across every Livewire component.

### 4.1 Orchestrated View Entrance (Staggering)
Animations are frequently staggered to build hierarchy during component mounting:
```css
.page-wrapper > div > *:nth-child(1) {
    animation: slide-up-fade 0.45s cubic-bezier(0.4, 0, 0.2, 1) both;
    animation-delay: 0.05s;
}
.page-wrapper > div > *:nth-child(2) {
    animation-delay: 0.12s;
}
```

### 4.2 Utility Registration Pattern
All custom animations must be exported as utility classes (`.animate-*`).

```css
@keyframes bubbleIn {
    from { opacity: 0; transform: translateY(8px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.animate-bubble-in {
    animation: bubbleIn 0.25s cubic-bezier(0.2, 0, 0, 1) both !important;
}
```

### 4.3 Compositor-Only Animations (Performance)
Where possible, animations should avoid layout reflows (width, height, padding) and exclusively manipulate `transform` and `opacity`.

```css
/* Good: Uses scaleY (Compositor only) */
@keyframes replyBarIn {
    from { opacity: 0; transform: scaleY(0); }
    to { opacity: 1; transform: scaleY(1); }
}
```

---

## 5. Decision Matrix

| Scenario | Location | Rationale |
|----------|----------|-----------|
| Adding a new system color theme | `theme.css` | All dynamic tokens must cascade from root data-theme declarations. |
| Making a loading spinner pulse | `animation.css` | Keep `@keyframes` centralized so other widgets can reuse `.animate-pulse`. |
| Creating a customized Dashboard Widget | `dashboard.css` | Isolated structural layout styling goes here. |
| Styling a form select dropdown's focus | `dashboard.css` | Overriding base HTML elements globally for the User Panel. |
| Changing animation speed globally | `theme.css` | Modify `--theme-transition-duration` token. |

---

## 6. Anti-Patterns

| Violation | Correction |
|-----------|------------|
| Hardcoding `#C2185B` in a component | Use `var(--md-sys-color-primary)` so it updates on theme switch. |
| Writing `@keyframes` inside `dashboard.css` | Migrate keyframes and `.animate-*` utilities to `animation.css`. |
| Animating `height` or `width` properties | Refactor to use `transform: scaleY()` or `scaleX()` to avoid UI jank. |
| Generating opaque shadows `rgba(0,0,0,0.5)` | Use `color-mix(in srgb, var(--md-sys-color-primary) 25%, transparent)` for vibrant, theme-aware depth. |
| Creating dark-mode styles using `#000` | Rely on `.dark` block overrides in `theme.css` for `var(--md-sys-color-surface)`. |
