# Admin Panel (Filament) Design System & CSS Architecture

## 1. The Design Vision & Integration Philosophy
Filament, by default, is an excellent but structurally rigid CMS framework. It looks like standard dashboard software. **Our goal is to completely mask Filament's default aesthetic**, morphing it so seamlessly into our application's UI that the user cannot tell they have crossed from the custom Livewire User Panel into the Filament Admin Panel.

**Core UI Principles for Admin:**
1. **Total Theme Synchronization:** The Admin panel must strictly obey the Light/Dark mode and Color Theme selected by the user in the User Panel.
2. **Soft Geometry:** Filament's default sharp corners and flat borders are overwritten with extreme rounding (`rounded-2xl`, `rounded-3xl`) and soft, radiant drop shadows.
3. **Immersive Motion:** Filament panels snap into place abruptly by default. We inject our custom `animation.css` keyframes into Filament's internal classes to ensure pages, tables, and modals glide into view smoothly.

---

## 2. Directory & Theming Pipeline

```
resources/css/core/
├── filament.css       # The Engine: Filament overrides and Token Mapping
├── notification.css   # The Overlay: Customizing Filament's isolated notification UI
├── theme.css          # Inherited from User Panel
└── animation.css      # Inherited from User Panel
```

### 2.1 The Vite Injection Strategy
`filament.css` acts as the master custom Vite theme for Filament.
**Crucial Concept:** Because CSS cascades, we must import Filament's core first, then our custom tokens, and finally our overrides.

```css
/* resources/css/core/filament.css */
@import "../../../vendor/filament/filament/resources/css/theme.css"; /* 1. Base Filament */
@import "./theme.css";       /* 2. Our MD3 Tokens */
@import "./animation.css";   /* 3. Our Motion Engine */
/* 4. Our custom .fi-* overrides follow below... */
```

---

## 3. Token Mapping: Bridging MD3 to Filament

Filament expects strict Tailwind color variables (`--primary-500`). It does not understand our MD3 system (`--md-sys-color-primary`). We must mathematically map our dynamic tokens into Filament's expected structure inside `:root`.

### 3.1 The Color-Mix Translation Layer
To generate the shades Filament requires (like `primary-600` for hovers), we use CSS `color-mix()` to dynamically darken or lighten our active MD3 token on the fly.

```css
:root {
    /* Base Container */
    --primary-50: var(--md-sys-color-primary-container);
    
    /* Core Action Color */
    --primary-500: var(--md-sys-color-primary);
    
    /* Auto-generated Hover/Active States */
    --primary-600: color-mix(in srgb, var(--md-sys-color-primary), black 10%);
    --primary-950: color-mix(in srgb, var(--md-sys-color-primary), black 65%);
}
```
*Developer Guideline:* If you add a new theme in `theme.css`, you do **not** need to update `filament.css`. The `color-mix()` math automatically generates the perfect Filament palette based on the new primary variable.

---

## 4. UI Morphing: Overriding Filament Core (`.fi-*`)

We aggressively target Filament's internal `.fi-` prefixed classes to reshape the CMS.

### 4.1 Reshaping Cards and Inputs (The Soft Aesthetic)
Filament cards are too boxy. We round them heavily and apply our custom Radiant Shadows (shadows tinted with the primary color, not gray).

```css
@layer components {
    /* Transforming the main Content Card */
    .fi-card {
        @apply !rounded-[1.5rem] !bg-[var(--md-sys-color-surface)] 
        !border !border-[var(--md-sys-color-outline-variant)]/50
        !shadow-[0_4px_24px_color-mix(in_srgb,var(--md-sys-color-primary),_transparent_90%)];
    }

    /* Softening all input fields */
    .fi-input {
        @apply !rounded-xl !bg-[var(--md-sys-color-surface-variant)]/30 
        !border-none !shadow-none;
    }
}
```

### 4.2 Injecting Cinematic Motion
We attach our pre-defined animations to Filament's structural classes so data doesn't just "appear"—it flows.

```css
/* Tables slide up smoothly */
.fi-ta-table {
    animation: slideUpFade 0.4s cubic-bezier(0.4, 0, 0.2, 1) both !important;
}

/* Staggering rows so they don't load in a single block */
.fi-ta-row {
    animation: slideUpFade 0.3s cubic-bezier(0.4, 0, 0.2, 1) both !important;
}
.fi-ta-row:nth-child(1) { animation-delay: 0.05s !important; }
.fi-ta-row:nth-child(2) { animation-delay: 0.10s !important; }
```

### 4.3 The "No Shell" Pattern
When rendering a Filament component (like a complex Table or Form) inside a custom User Panel view, we use the `.no-shell` utility to strip away Filament's structural borders and backgrounds, allowing it to sit naked inside our custom wrappers.

```css
.no-shell [class*="fi-"],
.no-shell [class*="ring"],
.no-shell [class*="shadow"] {
    @apply border-0 shadow-none outline-none ring-0;
}
```

---

## 5. Notification UI Override (`notification.css`)

Filament's notification package operates almost like a separate micro-frontend. It requires its own dedicated CSS file.

### 5.1 Modal Geometry and RTL Flow
Because our application is primarily Persian (RTL), we override the notification modal window to slide in from the right edge, snapping flush against the screen.

```css
.fi-modal-window {
    direction: rtl;
    background-color: var(--md-sys-color-primary-container) !important;
    border-radius: 0 1rem 1rem 0 !important; /* Flat on the right, rounded on the left */
    animation: slideUpFade 0.25s var(--sys-anim-standard) both !important;
}
```

---

## 6. Developer Decision Matrix

| When you need to... | Do this... | Why? |
| :--- | :--- | :--- |
| Style a new Filament plugin | Identify the `.fi-` class in DevTools, override it in `filament.css` using `@apply` and `!important`. | Filament plugins use default UI. We must force them to match our soft geometry. |
| Change the color of a Filament Badge | Do nothing. Use Filament's PHP `->color('primary')`. | Because we mapped `--primary-*` to MD3 in `:root`, the badge will automatically theme itself. |
| Use a Filament table in a custom Livewire view | Wrap the table in `<div class="no-shell">` | Strips the heavy CMS card borders so it blends into your custom page layout. |

---

## 7. Absolute Anti-Patterns (Do Not Do This)

❌ **Do not configure Filament colors in `AdminPanelProvider.php` using HEX codes.**
*Why?* It breaks the real-time theme switcher. Always configure Filament to look for CSS variables which we control in `filament.css`.

❌ **Do not use `@apply bg-white` or `bg-gray-900` to style Filament components.**
*Why?* Hardcoded colors break Light/Dark mode transitions. Always use `@apply bg-[var(--md-sys-color-surface)]`.

❌ **Do not rewrite `@keyframes` inside `filament.css`.**
*Why?* Redundant code and inconsistent motion curves. Always inherit the keyframes from `animation.css`.
