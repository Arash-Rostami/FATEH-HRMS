# User Panel Design System & CSS Architecture

## 1. Design Vision & UX Philosophy
A futuristic, ultra-modern (2026-era) Enterprise HRMS — moving away from flat enterprise software, prioritizing depth, cinematic motion, and tactile feedback.

1. **Glassmorphism over Solid Flats:** UI layers feel like frosted glass plates stacked in 3D, heavily utilizing `color-mix()` for translucency.
2. **Colored Depth over Gray Shadows:** shadows are never flat black/gray — they're tinted with the primary color to simulate ambient light bleed (radiance).
3. **Cinematic, Non-Blocking Motion:** interactions feel instantaneous. Entrance animations are staggered for hierarchy, but durations stay short (200–300ms) so the UI never locks up.

---

## 2. Directory & Scope

```
resources/css/core/
├── theme.css
├── dashboard.css
└── animation.css
```

`theme.css` is the brain (MD3 tokens, custom palettes, base resets). `dashboard.css` is the body (widget layouts, modals, forms, scrollbars). `animation.css` is the motion (staggered entrances, micro-interactions).

---

## 3. The Dynamic Token System (`theme.css`)
No static HEX codes in components — a heavily customized interpretation of Material Design 3 (MD3). The Admin Panel doc (`adminStylesPattern.md` §3) describes the same MD3 token system; the facts below are the User-Panel-specific surface.

### 3.1 The Custom Palette Architecture
Multiple robust themes (e.g. *Jade, Magneta, Ember, Obsidian*), each providing Light and Dark variants mapped to `[data-theme="X"]`.

**How to implement color:** never use Tailwind's default colors (`bg-blue-500`). Use the semantic CSS variables:
*   `var(--md-sys-color-primary)` — brand action color.
*   `var(--md-sys-color-surface)` — base component background.
*   `var(--md-sys-color-surface-variant)` — secondary component backgrounds.
*   `var(--md-sys-color-on-surface-variant)` — muted text.

### 3.2 Semantic "Tool" Colors
Beyond MD3 roles, the HRMS requires distinct visual buckets for different data types:
*   `--tool-amethyst-*`: deep analytics or premium features.
*   `--tool-sapphire-*`: live, active, or online states.
*   `--tool-sage-*`: success, growth, or finalized states.
*   `--tool-gold-*`: warnings, pending items, or highlights.

*Guideline:* for a badge, chart, or indicator, choose one of these semantic Tool token sets rather than arbitrarily picking a primary color.

### 3.3 Extended MD3 role tokens — `success` / `warning` (added 2026-08-27)
MD3 ships `primary/secondary/tertiary/error` role tokens but **not** `success`/`warning`. This app extends the role set in `theme.css` with `--md-sys-color-success[-container]/-on-success-container` and `--md-sys-color-warning[-container]/-on-warning-container`, each defined for **both** `:root` (light) and `:root.dark` (dark) — emerald `#10b981` / amber `#f59e0b` light bases with inverted container pairs in dark (mirroring the `error` token's four-tier shape). They are **role tokens, not Tool tokens**: use them for the semantic meaning (success = start/done-good, warning = change/attention) the same way `error` is used for deadlines/danger — the Project calendar lifecycle legend (start=success, change=warning, deadline=error, completed=primary) consumes exactly these four, so a day-cell icon, its legend chip, and its list-view marker all read the same color across light/dark without a per-call `color-mix`. The existing `--tool-sage-*`/`--tool-gold-*` palettes (§3.2) remain the choice for arbitrary badges/charts where the meaning is "growth" or "pending" but not a lifecycle role; `success`/`warning` are reserved for cases that pair explicitly with `error`/`primary` as a 4-state set.

---

## 4. Crafting the UI: Layouts & Components (`dashboard.css`)

### 4.1 Achieving True Glassmorphism
The signature look of User Panel modals and cards relies on a specific `color-mix()` formula. The light-mode example below is the canonical pattern; the dark-mode block is the **one documented exception** to the §7 no-static-rgb rule — dark mode drops translucency for stark contrast and deep space, so a pure-black shadow is intentional here (matches `dashboard.css` lines 678/688).

```css
.modal-inner-card {
    background: color-mix(in srgb, var(--md-sys-color-surface) 92%, transparent);
    border: 1px solid color-mix(in srgb, var(--md-sys-color-outline-variant) 60%, transparent);
    box-shadow: 0 8px 32px color-mix(in srgb, var(--md-sys-color-primary) 25%, transparent);
}

.dark .modal-inner-card {
    background: var(--md-sys-color-surface);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6);
}
```

### 4.2 Accessibility & Interactive States
*   **Focus Rings:** `*:focus-visible` is globally overridden to a thick, offset primary ring. Never disable this.
*   **Scrollbars:** hidden by default, revealed on hover — the global reset in `dashboard.css` sets `scrollbar-width: none` on all elements and switches to a thin primary-tinted bar only while hovered (`*:hover`). This applies to every scroll container (bare `overflow-auto`, compound `container-scrollbar.custom-scrollbar`, `msg-scrollbar`/`contact-scrollbar`); `scrollbar-hide`/`custom-scrollbar` stay permanently hidden via more-specific rules. Never add inline `style="scrollbar-width: thin; …"` overrides — they break the hover-reveal default on Firefox.

### 4.3 Shared component styling tokens (`<x-ui.empty>` + solid-button dropdown)
The shared empty-state component and the solid-button dropdown used for single-dimension filters consume the same MD tokens as the rest of the system — no hardcoded colors:
*   `<x-ui.empty>`: icon/text on `var(--md-sys-color-on-surface-variant)`, optional watermark on `var(--md-sys-color-on-surface)` at `opacity-[0.04]` (the low opacity — not a surface-tier token — is what keeps the decorative watermark subordinate to the icon/text), animated icon via the existing `.animate-pulse` utility.
*   Solid-button dropdown: active/open button = `var(--md-sys-color-primary)` background, idle = `var(--md-sys-color-surface-container-highest)`, popup container = `var(--md-sys-color-surface-container-high)`, option text = `var(--md-sys-color-on-surface-variant)`, divider = `var(--md-sys-color-outline-variant)`. Matches the reports card/list solid segmented-toggle vocabulary — one visual language across all filter controls.

---

## 5. Motion & Micro-Interactions (`animation.css`)

### 5.1 Performance First (Compositor-Only)
Never animate `width`, `height`, `padding`, or `margin` — causes expensive layout reflows (jank). **Rule:** only animate `transform` (scale, translate) and `opacity`. The pre-registered `slideUpFade` keyframe (used in §5.2) follows this rule — `transform: translateY` + `opacity`, never geometry.

```css
.slide-up { animation: slideUpFade 0.45s both; }
```

### 5.2 Staggered Entrances
For lists or grids (dashboard widgets), use the nth-child staggering pattern:
```css
.widget-grid > *:nth-child(1) { animation: slideUpFade 0.45s both; animation-delay: 0.05s; }
.widget-grid > *:nth-child(2) { animation: slideUpFade 0.45s both; animation-delay: 0.12s; }
```

### 5.3 Semantic Utility Classes
Always use the pre-registered utilities rather than inline `@keyframes`:
*   `.animate-bubble-in`: chat messages or popovers.
*   `.animate-toast-in`: floating notifications.
*   `.animate-pulse-ring`: "online" or "live" status indicators.

---

## 6. Developer Decision Matrix

| When you need to... | Do this... | Why? |
| :--- | :--- | :--- |
| Add a new primary brand color | Add a block in `theme.css` under `[data-theme='name']` | Centralizes color logic; allows real-time theme switching. |
| Build a new dashboard widget card | Use `@apply bg-[var(--md-sys-color-surface)] shadow-[...]` with `color-mix` | Instantly adapts to Light/Dark modes and theme shifts. |
| Make a dropdown slide in smoothly | Add `.animate-slide-up` and `.animate-delay-100` classes | Reuses optimized, GPU-accelerated motion primitives. |
| Style a specific Livewire component | Scope styles in `dashboard.css` using a specific `.my-widget` class | Prevents CSS global pollution. |

---

## 7. Absolute Anti-Patterns

❌ **Do not use static HEX or RGB values (`#333` or `rgba(0,0,0,0.5)`).** Breaks Dark Mode and Theme switching. Use `var(--md-sys-color-...)` and `color-mix()`.

❌ **Do not write inline `@keyframes` inside component CSS.** Bloats CSS and causes inconsistent motion curves. Move it to `animation.css` as a reusable `.animate-*` utility.

❌ **Do not create flat, unstyled scrollbars.** Breaks the immersive UI. Rely on the global scrollbar reset in `dashboard.css`.

❌ **Do not set long animation durations (`1s`, `800ms`) for UI interactions.** Enterprise users move fast — "Cancel" must disappear in `200ms`. Do not block workflow with slow animations.

---

## 8. Focus-flash bg underlay
`.record-focus-flash` (toggled on a message/record row when `scrollToMessage`/focus lands on it) carries the outline ring + a theme-adaptive **bg underlay**: `background: color-mix(in srgb, var(--md-sys-color-primary) 12%, transparent);`. The underlay sits behind the bubble content (the class is on the row wrapper, the bubble is a nested div with its own bg), so it never clashes with the bubble's gradient/surface bg — it only tints the row's padding/gap area, reinforcing the ring without hardcoding a color. The 12% primary tint adapts to dark/light + theme switch automatically. General rule: any focus/flash highlight composes `color-mix` over `--md-sys-color-primary` (or the matching semantic token) — never a hardcoded hex/rgb.

## 9. Layout containment on high-churn scroll/interaction regions

`contain: layout` — never `paint`/`content`/`strict`, which clip any child that overflows the container's bounds and would break tooltips/dropdowns/drag-ghost previews — added to `.calendar-day-column` (the per-day event-pill wrapper in `livewire/dashboard/tab/calendar/week.blade.php`/`day.blade.php`, which drag/resize mutates frequently). `contain: layout` has zero visual effect on its own in isolation — it only limits how far the browser needs to propagate a layout recalculation when something inside changes.

**Real gotcha, caught in review before shipping — `contain: layout` (like `transform`/`filter`) makes the element a new containing block for any `position: fixed`/`absolute` descendant.** `#msg-viewport` (the chat message list, `channel.js`/`contact.js`, see `resources/js/scriptPattern.md` §10) was also a candidate for this containment, but `contact/messages.blade.php` nests its "scroll to bottom" FAB (`class="fixed bottom-28 left-8 ..."`) *inside* `#msg-viewport` — containing that element would have silently re-anchored the FAB to the scrolling pane instead of the browser viewport, a real visual regression. (Channel's equivalent FAB is a `position: absolute` sibling of `#msg-viewport`, so it would have been unaffected — the two modules aren't structured identically here.) `#msg-viewport` was left uncontained rather than restructuring the blade to move the FAB out first. **Rule: before adding `contain: layout` (or `transform`/`filter`) to any container, grep it and its descendants for `position: fixed`/`absolute` — a contained/transformed ancestor changes where those descendants anchor.**