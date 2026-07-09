# User Panel Design System & CSS Architecture

## 1. The Design Vision & UX Philosophy
The User Panel is designed as a futuristic, ultra-modern (2026-era) Enterprise HRMS. It strictly moves away from flat, lifeless enterprise software, prioritizing depth, cinematic motion, and tactile feedback.

**Core UI Principles:**
1. **Glassmorphism over Solid Flats:** UI layers should feel like physical, frosted glass plates stacked in a 3D space, heavily utilizing `color-mix()` for translucency.
2. **Colored Depth over Gray Shadows:** Shadows must never be flat black or gray. They should be tinted with the application's primary color to simulate ambient light bleed (radiance).
3. **Cinematic, Non-Blocking Motion:** Interactions must feel instantaneous. Entrance animations are staggered to build hierarchy, but transition durations are kept short (200-300ms) to ensure the UI never locks up or feels sluggish.

---

## 2. Directory & Scope

```
resources/css/core/
├── theme.css       # The brain: MD3 Tokens, Custom Palettes, Base Resets
├── dashboard.css   # The body: Widget layouts, Modals, Forms, Scrollbars
└── animation.css   # The motion: Staggered entrances, Micro-interactions
```

---

## 3. The Dynamic Token System (`theme.css`)
Our application does not use static HEX codes in components. We use a heavily customized interpretation of Material Design 3 (MD3).

### 3.1 The Custom Palette Architecture
We define multiple robust themes (e.g., *Jade, Magneta, Ember, Obsidian*). Every theme provides a Light and Dark variant mapped to `[data-theme="X"]`.

**How to implement color:**
Never use Tailwind's default colors (`bg-blue-500`). Use the semantic CSS variables:
*   `var(--md-sys-color-primary)` - Brand action color.
*   `var(--md-sys-color-surface)` - Base component background.
*   `var(--md-sys-color-surface-variant)` - Secondary component backgrounds.
*   `var(--md-sys-color-on-surface-variant)` - Muted text.

### 3.2 Semantic "Tool" Colors
Beyond MD3 roles, the HRMS requires distinct visual buckets for different data types. We use "Tool" tokens:
*   `--tool-amethyst-*`: Used for deep analytics or premium features.
*   `--tool-sapphire-*`: Used for live, active, or online states.
*   `--tool-sage-*`: Used for success, growth, or finalized states.
*   `--tool-gold-*`: Used for warnings, pending items, or highlights.

*Developer Guideline:* If you are building a badge, chart, or indicator, choose one of these semantic Tool token sets rather than arbitrarily picking a primary color.

---

## 4. Crafting the UI: Layouts & Components (`dashboard.css`)

### 4.1 Achieving True Glassmorphism
The signature look of the User Panel modals and cards relies on a very specific formula. To create a new component that matches the system, use `color-mix()`:

```css
.future-card {
    /* 1. Translucent Background */
    background: color-mix(in srgb, var(--md-sys-color-surface) 92%, transparent);
    
    /* 2. Frosted Border */
    border: 1px solid color-mix(in srgb, var(--md-sys-color-outline-variant) 60%, transparent);
    
    /* 3. Radiant Shadow (Tinted with Primary) */
    box-shadow: 0 8px 32px color-mix(in srgb, var(--md-sys-color-primary) 25%, transparent);
}

.dark .future-card {
    /* Dark mode drops translucency for stark contrast and deep space */
    background: var(--md-sys-color-surface);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6);
}
```

### 4.2 Accessibility & Interactive States
*   **Focus Rings:** We globally override `*:focus-visible` to provide a thick, offset primary ring. Never disable this.
*   **Scrollbars:** Custom thin scrollbars are tinted with the primary color at 20% opacity so they blend seamlessly into the active theme.

### 4.3 Shared component styling tokens (`<x-ui.empty>` + solid-button dropdown)

The shared empty-state component (`<x-ui.empty>`) and the solid-button dropdown used for single-dimension filters consume the same MD tokens as the rest of the system — no hardcoded colors:
*   `<x-ui.empty>`: icon/text on `var(--md-sys-color-on-surface-variant)`, optional watermark on `var(--md-sys-color-surface-variant)`, animated icon via the existing `.animate-pulse` utility.
*   Solid-button dropdown: active/open button = `var(--md-sys-color-primary)` background, idle = `var(--md-sys-color-surface-container-highest)`, popup container = `var(--md-sys-color-surface-container-high)`, option text = `var(--md-sys-color-on-surface-variant)`, divider = `var(--md-sys-color-outline-variant)`. This matches the reports card/list solid segmented-toggle vocabulary — one visual language across all filter controls.

---

## 5. Motion & Micro-Interactions (`animation.css`)

### 5.1 Performance First (Compositor-Only)
Never animate `width`, `height`, `padding`, or `margin`. This causes expensive layout reflows (jank).
**Rule:** Only animate `transform` (scale, translate) and `opacity`.

*Bad:*
```css
@keyframes slideDown { from { height: 0; } to { height: 100px; } }
```
*Good:*
```css
@keyframes slideDown { from { transform: scaleY(0); } to { transform: scaleY(1); } }
```

### 5.2 Staggered Entrances
When rendering lists or grids (like dashboard widgets), use our nth-child staggering pattern to create a cascading entrance effect:
```css
.widget-grid > *:nth-child(1) { animation: slideUpFade 0.45s both; animation-delay: 0.05s; }
.widget-grid > *:nth-child(2) { animation: slideUpFade 0.45s both; animation-delay: 0.12s; }
```

### 5.3 Semantic Utility Classes
Always use the pre-registered utility classes rather than writing inline `@keyframes`:
*   `.animate-bubble-in`: For chat messages or popovers.
*   `.animate-toast-in`: For floating notifications.
*   `.animate-pulse-ring`: For "online" or "live" status indicators.

---

## 6. Developer Decision Matrix

| When you need to... | Do this... | Why? |
| :--- | :--- | :--- |
| Add a new primary brand color | Add a block in `theme.css` under `[data-theme='name']` | Keeps all color logic centralized and allows real-time theme switching. |
| Build a new dashboard widget card | Use `@apply bg-[var(--md-sys-color-surface)] shadow-[...]` using `color-mix` | Ensures the widget instantly adapts to Light/Dark modes and theme shifts. |
| Make a dropdown slide in smoothly | Add `.animate-slide-up` and `.animate-delay-100` classes to the element | Reuses highly optimized, GPU-accelerated motion primitives. |
| Style a specific Livewire component | Scope styles in `dashboard.css` using a specific `.my-widget` class | Prevents CSS global pollution and keeps the core framework clean. |

---

## 7. Absolute Anti-Patterns (Do Not Do This)

❌ **Do not use static HEX or RGB values (`#333` or `rgba(0,0,0,0.5)`).**
*Why?* It breaks Dark Mode and Theme switching immediately. Use `var(--md-sys-color-...)` and `color-mix()`.

❌ **Do not write inline `@keyframes` inside component CSS.**
*Why?* It bloats the CSS and causes inconsistent motion curves. Move it to `animation.css` and create a reusable `.animate-*` utility.

❌ **Do not create flat, unstyled scrollbars.**
*Why?* It breaks the immersive UI experience. Rely on the global scrollbar reset in `dashboard.css`.

❌ **Do not set long animation durations (e.g., `1s` or `800ms`) for UI interactions.**
*Why?* Enterprise users move fast. If they click "Cancel", the modal must disappear in `200ms`. Do not block their workflow with slow, dramatic animations.

---

## 8. Focus-flash bg underlay

`.record-focus-flash` (the class toggled on a message/record row when `scrollToMessage`/focus lands on it) carries the outline ring + `record-focus-flash` keyframe, plus a theme-adaptive **bg underlay**: `background: color-mix(in srgb, var(--md-sys-color-primary) 10%, transparent);`. The underlay sits behind the bubble content (the class is on the row wrapper, the bubble is a nested div with its own bg), so it never clashes with the bubble's gradient/surface bg — it only tints the row's padding/gap area, reinforcing the ring without hardcoding a color. The 10% primary tint adapts to dark/light + theme switch automatically. General rule: any focus/flash highlight composes `color-mix` over `--md-sys-color-primary` (or the matching semantic token) — never a hardcoded hex/rgb.
