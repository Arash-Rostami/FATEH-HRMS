# User Panel Design System & CSS Architecture

## 1. Design Vision & UX Philosophy
A futuristic, ultra-modern (2026-era) Enterprise HRMS — moving away from flat enterprise software, prioritizing depth, cinematic motion, and tactile feedback.

1. **Glass for overlays, flat opaque for cards:** modal/overlay shells (§4.1) are frosted glass via `color-mix()` translucency; page cards, tables and panels default to flat opaque MD3 surfaces, with glass only in the named exceptions of `viewPattern.md` §7 item 8.
2. **Colored Depth over Gray Shadows:** shadows are never flat black/gray — they're tinted with the primary color to simulate ambient light bleed (radiance).
3. **Cinematic, Non-Blocking Motion:** interactions feel instantaneous. Entrance animations are staggered for hierarchy, but durations stay short (200–300ms) so the UI never locks up.

---

## 2. Directory & Scope

```
resources/css/core/
├── theme.css
├── dashboard.css
├── animation.css
├── color.css
├── notification.css
└── ambient.css
```

`theme.css` is the brain (MD3 tokens, custom palettes, base resets). `dashboard.css` is the body (widget layouts, modals, forms, scrollbars). `animation.css` is the motion (staggered entrances, micro-interactions). The remaining three, all imported from `resources/css/app.css`: `color.css` (rich-color spans), `notification.css` (Filament notification overlay styling), `ambient.css` (ambient-effect layers). `filament.css` is the admin-panel entry (see `adminStylesPattern.md`).

---

## 3. The Dynamic Token System (`theme.css`)
No static HEX codes in components — a heavily customized interpretation of Material Design 3 (MD3). The Admin Panel doc (`adminStylesPattern.md` §3) describes the same MD3 token system; the facts below are the User-Panel-specific surface.

### 3.1 The Custom Palette Architecture
Seventeen themes (the default plus 16 named ones — full list and values in §15.2), each providing Light and Dark variants. The default lives on bare `:root` / `:root.dark`; every named theme maps to `[data-theme='X']` / `[data-theme='X'].dark`, and a named theme overrides only a subset of tokens (anything it omits inherits the default's value). `html.dark` is the mode switch, `html[data-theme]` the palette switch (`public/js/mode-manager.js`; the default theme removes the attribute). `color-scheme` is pinned to the app's chosen mode in the same two blocks (`:root { color-scheme: light }`, `:root.dark { color-scheme: dark }`) — it overrides the `light dark` meta in `meta-tags.blade.php`, so native UI (the non-searchable `<x-ui.forms.select>` popup, scrollbars, autofill) follows the app mode, never the OS preference; without it, a dark-OS user gets dark native popups while the app is light (fixed 2026-11-18).

**How to implement color:** never use Tailwind's default colors (`bg-blue-500`). Use the semantic CSS variables:
*   `var(--md-sys-color-primary)` — brand action color.
*   `var(--md-sys-color-surface)` — base component background.
*   `var(--md-sys-color-surface-variant)` — secondary component backgrounds.
*   `var(--md-sys-color-on-surface-variant)` — muted text.

### 3.2 Semantic "Tool" Colors
Beyond MD3 roles, the HRMS requires distinct visual buckets for different data types:
*   `--tool-amethyst-*`: deep analytics or premium features — and the app-wide "AI feature" family tone (AI code never reads these directly: use the aliases `--ai-color`/`--ai-bg`/`--ai-text` declared once in `theme.css`'s plain `:root`; the rule and the full list of AI elements are viewPattern §8.38) (BadgeLegendCatalog tone `amethyst`, the hamburger-menu AI tile halo since 2026-11-07; the Ths «نوشتن هوشمند» `.ai-suggested .md3-input` inline-start accent edge and loading shimmer, `dashboard.css`, 2026-11-08; the `x-ui.loaders.ai-shimmer` component shared with the AI Report generating skeleton, see `components/ui/loaders/loadingPattern.md`).
*   `--tool-sapphire-*`: live, active, or online states.
*   `--tool-sage-*`: success, growth, or finalized states.
*   `--tool-gold-*`: warnings, pending items, or highlights.

*Guideline:* for a badge, chart, or indicator, choose one of these semantic Tool token sets rather than arbitrarily picking a primary color.

### 3.3 Extended MD3 role tokens — `success` / `warning` (added 2026-08-27)
MD3 ships `primary/secondary/tertiary/error` role tokens but **not** `success`/`warning`. This app extends the role set in `theme.css` with `--md-sys-color-success[-container]/-on-success-container` and `--md-sys-color-warning[-container]/-on-warning-container`, each defined for **both** `:root` (light) and `:root.dark` (dark) — emerald `#10b981` / amber `#f59e0b` light bases with inverted container pairs in dark (mirroring the `error` token's four-tier shape). They are **role tokens, not Tool tokens**: use them for the semantic meaning (success = start/done-good, warning = change/attention) the same way `error` is used for deadlines/danger — the Project calendar lifecycle legend (start=success, change=warning, deadline=error, completed=primary) consumes exactly these four, so a day-cell icon, its legend chip, and its list-view marker all read the same color across light/dark without a per-call `color-mix`. The existing `--tool-sage-*`/`--tool-gold-*` palettes (§3.2) remain the choice for arbitrary badges/charts where the meaning is "growth" or "pending" but not a lifecycle role; `success`/`warning` are reserved for cases that pair explicitly with `error`/`primary` as a 4-state set.

---

## 4. Crafting the UI: Layouts & Components (`dashboard.css`)

### 4.0 Native select popups are theme-pinned, not OS-blue (`appearance: base-select`)
User panel only (this file is the user-panel entry's body — admin never loads it). Every native `<select>` popup (non-searchable `<x-ui.forms.select>`, member-picker dept filter, radio station, reminder hours) opts into Chrome's Customizable Select via an `@supports (appearance: base-select)` block in `dashboard.css` — **unlayered, at the very end of the file** (first version sat inside `@layer base` and did nothing: the `appearance-none` utility class on those selects lives in a later cascade layer and always beat the opt-in; unlayered author CSS beats every layer — never move this block back into a layer). The opt-in must be declared twice — on `select` and on `select::picker(select)` — per Chrome's branded-select guide, or the popup stays native: `option:hover` gets `primary-container`, `option:checked` gets `secondary-container`, and `::picker(select)` gets `surface-container` + `outline-variant` border + `rounded-2xl`/`p-1.5` (16px radius, 6px padding) with option rows at `px-3 py-2.5 rounded-xl` 13px text — the exact geometry of the searchable select's own popover, so both dropdown kinds look identical and hover follows the app theme instead of the browser's default blue. Non-Chromium browsers (and pre-135 Chrome) fall back to the legacy `box-shadow … inset` option hack just above that block, kept alive for them; never delete either half. **Cascade fix (2026-11-18):** that legacy `select option:hover, select option:checked { … !important }` rule lives in `@layer base`, and for `!important` declarations a layered rule BEATS an unlayered one, so it used to repaint hover AND checked in `primary-container` and erase the hover/checked distinction above; it is now wrapped in `@supports not (appearance: base-select)` so it only exists where the new block does not. The base-select block is scoped to `select:not([multiple]):not([size])` (selector, option rules, `::picker(select)`, `::picker-icon`) and `::picker` carries no `margin-top` (neutral — Chrome positions the popup itself, including when it opens upward). Selects carrying Tailwind `appearance-none` draw their own chevron, so their `::picker-icon` is hidden in the same block — a new raw select without that class keeps the native icon. Caveat: `multiple`/`size` selects are incompatible with `base-select` and the app DOES have them (`<x-ui.forms.select multiple>` — for example the project form's departments and the suggestion forms' department sets), which is exactly why the block excludes them; they keep the native listbox.

### 4.1 Achieving True Glassmorphism
The signature look of User Panel modals and cards relies on a specific `color-mix()` formula. Modal shells (hamburger menu, `<x-ui.modals.panel>`, ticket window, dialog, command palette, occasion card, `.modal-inner-card`) have **no outer border** — shared `.modal-lift` only (tight contact shadow + one wide soft shadow, neutral `on-surface`, not primary-tinted; dark uses the black stack). A pale `outline-variant` frame (and dialog's `glass-panel` white border, killed with `!border-0`) painted as a white line, worst along the colored header. Overlay family keeps the existing `primary/60` scrim and `animate-slide-down`, and leaves with `animate-slide-down-out` (same 30px, fade and slide together; do not reuse `.animate-slide-up` — that class is the dropdown entrance). Do not restyle that scrim. Page cards keep that border. The light-mode example below is the canonical pattern; the dark-mode block is the **one documented exception** to the §7 no-static-rgb rule — dark mode drops translucency for stark contrast and deep space, so a pure-black shadow is intentional here (matches `dashboard.css`'s `.dark .modal-inner-card`/`:hover` rules — line numbers not cited, since they drift with unrelated edits elsewhere in the file; grep `.dark .modal-inner-card` instead of trusting a pinned line number).

```css
.modal-inner-card {
    background: color-mix(in srgb, var(--md-sys-color-surface) 92%, transparent);
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
*   **Scroll anchoring:** suppress browser scroll-anchoring on a container with the `no-anchor` utility class (`dashboard.css`, `overflow-anchor: none`) — always as the blade-side class, never a JS `el.style.overflowAnchor` write (Livewire morph syncs `style` against server HTML, which has none, so an inline write is stripped by the next morph — possibly the very commit that prepends content and needs the guard). Consumers: `#project-tab-pane` + `#team-chat-viewport` (Project's prepend-anchor math and scroll-FAB depend on it).

### 4.3 Shared component styling tokens (`<x-ui.empty>` + solid-button dropdown)
The shared empty-state component and the solid-button dropdown used for single-dimension filters consume the same MD tokens as the rest of the system — no hardcoded colors:
*   `<x-ui.empty>`: icon/text on `var(--md-sys-color-on-surface-variant)`, optional watermark on `var(--md-sys-color-on-surface)` at `opacity-[0.04]` (the low opacity — not a surface-tier token — is what keeps the decorative watermark subordinate to the icon/text), animated icon via the existing `.animate-pulse` utility.
*   Solid-button dropdown: active/open button = `var(--md-sys-color-primary)` background, idle = `var(--md-sys-color-surface-container-highest)`, popup container = `var(--md-sys-color-surface-container-high)`, option text = `var(--md-sys-color-on-surface-variant)`, divider = `var(--md-sys-color-outline-variant)`. Matches the reports card/list solid segmented-toggle vocabulary — one visual language across all filter controls.

---

### 4.4 Form footer hierarchy (2026-10-14)
In RTL the FIRST button in DOM is the rightmost: footers are `cancel` first (right), `confirm` second (left). `.modal-btn-confirm` is the filled primary action; `.modal-btn-cancel` is a transparent outlined low-emphasis button — never red or filled, so a mis-click on Cancel is harder than on Confirm. Hand-rolled footers follow the same order and use `variant="ghost"` for cancel.

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
*   `.animate-pulse-ring`: "online" or "live" status indicators — small DOTS only (`w-1.5`–`w-3`): it scales the element to 1.8× and fades it out, so on a badge or text label it balloons past its box and flashes. One sanctioned non-dot consumer (2026-11-07): the hamburger-menu AI grid tile halo (`menu.blade.php`) — an `absolute inset-0` overlay span that COPIES the parent tile's own size and `rounded-xl` and sits inside the tile's clipping context, so the 1.8× scale reads as a radiating halo from the tile edge rather than a ballooning badge; the overlay must always mirror the parent's exact corner radius or the scaled-up ring shows square corners. The list-view tile (`modal/menu/item.blade.php`) deliberately uses `.animate-alert-soft` instead — opacity-only breathing, no scale, at the tighter list spacing.
*   `.animate-alert-soft`: calm attention for WIDE elements (urgent/stuck badges, urgent chip labels) — opacity-only breathing (1 ↔ 0.6, 2s ease-in-out infinite, no transform so nothing protrudes; reduced-motion off-switch). Consumers: workflow card «معطل مانده» badge, urgent chip labels in project report and taskboard card.
*   `.animate-shimmer` and `.animate-slide-up-fade` are declared `!important` and unlayered, so they carry NO reduced-motion off-switch of their own and a plain `motion-reduce:animate-none` utility loses to them: a consumer must write `motion-reduce:!animate-none` (Ths AI chips, 2026-11-08; the AI shimmer itself is the `x-ui.loaders.ai-shimmer` component, which owns this rule plus `motion-reduce:w-full` so reduced motion shows a static tint instead of a frozen bar — never re-paste it).
*   `.animate-shiver`: iOS-style wiggle (±5deg rotate + 1px translateX, 0.28s infinite, transform-only) for "edit mode" states such as the hamburger-menu pin mode; carries its own `prefers-reduced-motion` off-switch.
*   `.reaction-burst` (2026-11-16): a pure-CSS 5-particle emoji explosion for the chat reaction chip (channel `reactions.blade.php`). Every chip sets an inline `--burst-emoji` custom property; all particle pseudos (the chip's `::before`/`::after` plus its `.reaction-emoji`/`.reaction-count` children's) live under the `.burst-now` gate class ONLY — without it no pseudo is generated at all (`content` is part of the gated rule), so idle chips cost zero pseudos on every render. ONE shared `reactionBurst` keyframe drives all five particles via per-pseudo custom properties (`--bx`/`--by`/`--br`/`--bs`: up / up-right / up-left / right / left trajectories up to ~84px, varied rotation, 12–16px sizes, 0.7–0.95s durations, staggered delays) — never fork it back into per-particle keyframes. Compositor-only (transform+opacity), `pointer-events: none` so flying particles never block clicks, own `prefers-reduced-motion` off-switch. **The trigger is `channel.js`'s `react()`: it awaits the island toggle, then adds `burst-now` only when the chip now reads `reaction-mine` (the give landed — removal and failed toggles never burst) and a 1.1s timer removes the class again.** Only the giver's own browser bursts, once per give. Two rejected triggers, do not resurrect: count-keyed `wire:key` node re-creation (burst on room entry), and an optimistic pre-await burst (the morph strips the class mid-animation → stutter + double-fire). Never add a second particle family; a sixth particle extends the variable blocks.
*   `.animate-sidebar-slide`: put on the chat sidebar `<aside>` for ~300 ms while collapsing/expanding it; animates its DIRECT children (`> *`) with `translateX(28px)→0` + opacity (0.26 s, transform/opacity only, reduced-motion off-switch). Paired with the unlayered `.sb-*` rules at the end of `dashboard.css` (width snaps to 4.5rem; the animation, not a width transition, is what makes it feel like it slides) — see `resources/js/scriptPattern.md` "Collapsible sidebar rail".
*   `.animate-ticker`: teletext-style horizontal marquee (16s linear infinite, transform-only `translateX(-50%)` loop over duplicated content; `prefers-reduced-motion` off-switch). The consumer freezes it when idle via inline `animation-play-state: paused` (menu-footer audio chip ticker).
*   `@keyframes eq` (scaleY-only bar bounce): consumed via the Tailwind arbitrary class `animate-[eq_1s_ease-in-out_infinite]` (hamburger play-beacon equalizer, staggered by inline `animation-delay`) — NOT a registered `.animate-eq` utility, so its `prefers-reduced-motion` off-switch in `animation.css` targets the escaped arbitrary selector `.animate-\[eq_1s_ease-in-out_infinite\]`.
*   `.animate-logo-shine`: periodic glossy light-sweep confined to a logo/icon's own alpha shape. Per §5.1 (transform/opacity only, never `background-position`), the sweep is a `::before` pseudo-element — a diagonal white gradient bar 3× the host's width, `position: absolute; inset: 0` (over-constrained with `width: 300%`, so `right` resolves to `auto` and the bar simply extends further right) — animated via `logoShine`'s `transform: translateX(-100% → 100%)`, compositor-only. The host class itself just sets `overflow: hidden` (defense-in-depth; the mask below already confines painting) and carries no animation of its own. Left-to-right reads naturally from the transform values themselves (negative = further left, positive = further right) — 5.5s ease-in-out infinite with a 1.5s initial delay so it reads as an occasional graceful glint, not continuous motion; the reduced-motion off-switch targets `.animate-logo-shine::before`, not the host class. Apply it to a sibling `<div>` layered over the `<img>` (never the `<img>` itself), sized/positioned identically (`absolute inset-0`) and clipped to the logo's own pixels via `mask-image`/`-webkit-mask-image: url(...)` pointing at that same logo asset — this is what keeps the highlight inside the glyph instead of sweeping across a visible rectangle. The white gradient is a deliberate, precedented exception to this file's "no static rgb" rule (§7) — see `.animate-text-flicker`'s `textFlickerInGlow` white `text-shadow` glow for the same reasoning: a physical light-glint effect is white regardless of theme, not a themed UI color, and no MD3 token in `theme.css` is guaranteed bright across every custom theme. Consumer: the auth-page app logo (`components/auth/card-no-signup.blade.php`).

---

## 6. Developer Decision Matrix

| When you need to... | Do this... | Why? |
| :--- | :--- | :--- |
| Add a new primary brand color | Add a block in `theme.css` under `[data-theme='name']` | Centralizes color logic; allows real-time theme switching. |
| Build a new dashboard widget card | Use `@apply bg-[var(--md-sys-color-surface)] shadow-[...]` with `color-mix` | Instantly adapts to Light/Dark modes and theme shifts. |
| Make a dropdown slide in smoothly | Add `.animate-slide-up` and `.animate-delay-100` classes | Reuses optimized, GPU-accelerated motion primitives. |
| Style a specific Livewire component | Scope styles in `dashboard.css` using a specific `.my-widget` class | Prevents CSS global pollution. |
| Verify an arbitrary Tailwind class made it into the bundle | grep the COMPILED css for the escaped selector form: underscores stay, but `(`/`,`/`%` are backslash-escaped — `hover\:bg-\[color-mix\(in_srgb\,var\(--x\)_10\%\,transparent\)\]` | Grepping the raw class string (`(` unescaped) always reports "missing" — a false negative that looks like a purge bug but isn't (cost several misdiagnosed checks on 2026-09-12). |
| Apply a `repeating-linear-gradient(...)` (or any multi-function value nesting `color-mix()`/`var()` several levels deep, several commas) as a background | Use a plain `style="background-image: ...;"` attribute (real spaces, no underscore-escaping needed), NOT `bg-[repeating-linear-gradient(...)]` | A value this deeply nested can silently fail Tailwind's arbitrary-value candidate extraction — the class renders in the HTML with ZERO matching CSS generated, so the element shows no effect at all and it's easy to mistake for a contrast/opacity problem rather than the class never having compiled (hit on Reservation's avantgarde calendar's "fully booked" diagonal-hatch cells, 2026-10-05 — several rounds of tuning opacity values produced no visible change until the arbitrary class was replaced with a plain inline `style=` attribute, which rendered correctly immediately). Simple single-function values (`bg-[color-mix(in_srgb,var(--x)_10%,transparent)]`) are fine as arbitrary classes — the risk is specifically multi-function/multi-comma nesting.

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

## 10. `auto-rows-fr` grid items need `min-h-0` or they silently overflow-clip

The global menu launcher's grid page (`components/dashboard/modal/menu.blade.php`, tile row `grid grid-cols-3 sm:grid-cols-4 gap-2 sm:gap-2.5 h-full auto-rows-fr` inside an `overflow-hidden` ancestor) was rendering only ~2 of 3 rows per page. `auto-rows-fr` sizes implicit rows to an equal `1fr` share of the container's fixed `h-full` height, but a CSS grid *item*'s default `min-height` is `auto` (content-based), not `0` — so a tile whose intrinsic content (icon box + two text lines + `p-3` padding) is taller than its `1fr` share does not shrink to fit; it overflows its track instead. With 3 rows' worth of oversized tiles, the whole grid grows taller than the `h-full` cap and the excess gets silently clipped by the ancestor's `overflow-hidden` — no console warning, no layout error, just a missing row. Fixed by adding `min-h-0` to the tile (`<a>`) class list, letting the track's `1fr` share actually win over content size. **Rule: any grid/flex item living inside an `auto-rows-fr`/`flex-1` track that sits under an `overflow-hidden` ancestor needs `min-h-0` (or `min-w-0` for row axes) — otherwise oversized content silently steals space from sibling tracks instead of shrinking, and the overflow clips invisibly rather than erroring.**

## 11. A `currentColor`-derived chip background can never contrast against its own text (fixed 2026-09-14)

`.mention` (the `@Name` chip `BuildsMessageGroups::mentionify()` wraps mentions in, shared by Channel/Project chat) used `background: color-mix(in srgb, currentColor 18%, transparent)` with no explicit `color` — so the chip's background was always a translucent tint of the *same* color as its own text. Inside a "sent" bubble (`bg-[linear-gradient(...,var(--md-sys-color-primary)...)] text-[var(--md-sys-color-on-primary)]`), `currentColor` resolves to the light on-primary text color, so the mention text and its background tint were both derived from that one light color — a bold `@Name` barely distinguishable from its own chip. Fixed by dropping the `currentColor` self-reference entirely: pair `color: var(--md-sys-color-surface)` with `background: color-mix(in srgb, var(--md-sys-color-on-surface) 85%, transparent)` — `surface`/`on-surface` are already a guaranteed-contrast pair by MD3 convention, so *inverting* them (on-surface as the chip's background, surface as its text) synthesizes a reliably-legible "inverted" chip from tokens already defined in `theme.css`, without adding a new role token, and it self-adapts across every custom theme + light/dark variant the same way the pair it's built from already does. **Rule: never derive a chip/badge's background from `currentColor` when that same `currentColor` is also the text color — the pair can't contrast against itself by construction. For a badge that must read against an unpredictable/colored ambient background (a chat bubble, a themed card), use an inverted `surface`/`on-surface` pairing (or a dedicated container/on-container role token) instead, never `currentColor` alone.**

## 11a. A `<tr>`-level `::before` accent bar paints BELOW its own cells' backgrounds — fixed in Ths (2026-10-05)

`.taskboard-card--urgent::before` (the shared ambient-urgency accent bar, §3.2-adjacent — `inset-inline-start:0`, absolutely positioned) was authored for TaskBoard's plain `<div>` cards, where it works correctly. `livewire/dashboard/ths/ticket-table-row.blade.php` reused the same class on a `<tr>` inside a real `<table>` — and CSS's table-background painting order paints a **cell's own background on top of its row's background**, by spec, regardless of z-index. A row-level `::before` counts as the row's own background/content for this purpose, so the accent bar rendered *underneath* the first `<td>`'s own background, invisible for that cell's entire width — it only became visible once past the first column, reading as "the urgent marker starts from the second column, not the first." Fixed by moving the class + the `--urgency` custom property from the `<tr>` onto the first `<td>` itself (which also needs its own `position: relative`, since the `<tr>` no longer carries the positioning context for that cell's `::before`) — a cell's own pseudo-element always paints as part of that cell, never buried by this rule. **Rule: a `::before`/`::after` accent meant to mark an entire table row must live on a `<td>` (ideally the first, so it reads as "this row" at a glance), never on the `<tr>` itself — table background-paint order always favors the cell over the row, so a row-level decorative layer can be silently hidden behind whichever cell sits at that position.**

## 12. Dark-mode contrast conventions (2026-10-04 sweep)

Light mode is **frozen pixel-identical**: dark readability fixes ride `dark:` variants, `:root.dark` token values, or `.dark`-scoped rules only — never a change to the light classes/values themselves. Three load-bearing pieces of that sweep:

*   **`--presence-*` semantic tier** (`theme.css`): one token per `PresenceStatus` case (`onsite/remote/mission/busy/grumpy/angry/leave`). Light values are the enum's exact hexes (so chips, dots, and CSS consumers match the PHP source byte-for-byte); dark values are light 300/400-class shades picked per state for ≥4.5:1 on dark surfaces — not a uniform step (`#4ade80` emerald-400, `#93c5fd` blue-300, `#fcd34d` amber-300, etc.). `PresenceStatus::cssVar()` returns `var(--presence-<value>)` for CSS/style contexts; `hex()` stays real-hex because the Canvas API (ctx.strokeStyle/fillStyle) cannot resolve `var()`.
*   **Uniform dark chip pattern**: a solid Tailwind-color chip (`bg-{c}-500 text-white`) gains `dark:bg-{c}-400 dark:text-{c}-950` — the 400/950 pair is the one dark treatment applied app-wide to status badges, file-type chips, alarm buttons, and toggle icons (5.6–8.9:1 verified). Solid chips on the primary-colored navbar top bar instead use `dark:text-[var(--md-sys-color-on-primary)]` since their background is the theme's primary, not a Tailwind color.
*   **Sanitized onboarding prose** (`dashboard.css` `.onb-prose`): the light rules force `text-yellow`/`text-green` HTML to `#b45309`/`#15803d`; `.dark .onb-prose` counterparts force `#fbbf24`/`#4ade80` — the deliberate static-hex exception class (see §4.1's dark-mode precedent): sanitizer output classes are runtime data, not tokens, and the light rules already hardcode hex the same way.

## 13. Home stat tiles — calm vs awake (2026-11-03)
Each tile on the Home hero carries `data-state="idle|awake|warm"` (count 0 / 1–4 / 5+), and `dashboard.css` (the `.stat-tile` block at the end) does the rest — no Blade branch, so nothing flips under Livewire morph. **Idle** = quiet: dashed hollow icon ring, dimmed glyph and number, no hover glow. **Awake** = the §3.2 **sapphire** tool set ("live, active"): pale `--tool-sapphire-bg` icon badge, `--tool-sapphire-color` ink for glyph/number, a ring that reuses `.animate-alert-soft` (opacity-only breathing with its own reduced-motion off-switch — the compositor-only rule of §5.1, no new keyframes), a thin bottom accent bar, and the §1 "colored depth" shadow tinted with that ink. **Warm** (5+) switches to the **gold** tool set ("warnings, pending") the same way. Both come from two custom properties (`--stat-bg`/`--stat-ink`) set per `data-state`, so every theme and light/dark variant follows automatically — never a hex, never a raw primary fill. The old pinging corner dot (`animate-ping`) is gone; do not reintroduce a per-tile infinite scale animation.

## 14. AI Report: table density and the print page box (2026-11)
- **Density**: a table opts into the compact density by carrying its own marker class and getting one line in the `.app-density-compact` block of `dashboard.css`; AI Report's table (`tableClass="ai-report-table …"`) is `.app-density-compact .ai-report-table tbody td { @apply !py-0 }`, the same shape as `.reservation-command-table` / `.ths-ticket-table` / `.launchpad-table` / `.media-episode-table`. The toggle itself is `<x-ui.table.density-toggle/>` in the table tools.
- **Print**: the report's colour/break/header rules (`body:has(.ai-report-doc)`, `.ai-report-page + .ai-report-page`, `.ai-report-keep`, `.ai-report-print-head`) stay in the `@media print` block at the tail of `dashboard.css`, but the `@page` rule (A4, 14mm margin, `@bottom-center` page counter) is NOT in any stylesheet: it is an inline `<style media="print">@@page { … }</style>` written by `components/dashboard/ai-report.blade.php` inside `.ai-report-doc` (`@@` escapes Blade), so the page box travels with the document wherever it renders and the bundler never sees the margin-box syntax. Do not move it back into `dashboard.css`.

---

## 15. Measured size standard (in-repo) and portable UI spec (for embedded services)

This section is the only recorded size standard in the repo: §15.4-15.9 (typography, radius/spacing/elevation, component catalogue, breakpoints, RTL) are the catalogue every new user-panel class is copied from, and also the spec an embedded service can rebuild from. Goal for the latter: an external service (e.g. the document-search / AI page shown inside the dashboard's AI-assistant iframe) can rebuild the same look with no repo access. Everything below is measured from `theme.css`, `dashboard.css`, `animation.css` and the Blade components under `resources/views/components/ui/**` plus the user-panel modules (home, project, taskboard, channel, contact, calendar, reservation, ths/tickets, ads, ai-report, menu launcher, auth card, settings/profile, notifications). Sizes are Tailwind v4 classes with px equivalents (1rem = 16px; the user can scale the root, see §15.3). **Colors are always the `--md-sys-color-*` tokens — copy the token block (§15.1), never hard-code hex.**

### 15.1 Token list — default theme (light / dark)
Mode = class `dark` on `<html>`; both blocks are applied to `:root`. Tailwind arbitrary form used everywhere: `bg-[var(--md-sys-color-surface)]`, translucency via `color-mix(in srgb, <token> N%, transparent)`.

| Token | Light | Dark |
| :--- | :--- | :--- |
| `primary` | `#4e5f66` | `#b5cbd2` |
| `on-primary` | `#ffffff` | `#1f3238` |
| `primary-container` | `#d1e7ef` | `#37474d` |
| `on-primary-container` | `#0a1e24` | `#d1e7ef` |
| `secondary` | `#535f70` | `#bbc7db` |
| `on-secondary` | `#ffffff` | `#253140` |
| `secondary-container` | `#d7e3f7` | `#3b4858` |
| `on-secondary-container` | `#101c2b` | `#d7e3f7` |
| `tertiary` | `#6b5778` | `#d6bee4` |
| `on-tertiary` | `#ffffff` | `#3b2948` |
| `tertiary-container` | `#f2daff` | `#523f5f` |
| `on-tertiary-container` | `#251431` | `#f2daff` |
| `error` | `#ba1a1a` | `#ffb4ab` |
| `on-error` | `#ffffff` | `#690005` |
| `error-container` | `#ffdad6` | `#93000a` |
| `on-error-container` | `#410002` | `#ffdad6` |
| `success` / `on-success` | `#10b981` / `#ffffff` | `#6ee7b7` / `#003826` |
| `success-container` / `on-success-container` | `#d1fae5` / `#064e3b` | `#064e3b` / `#a7f3d0` |
| `warning` / `on-warning` | `#f59e0b` / `#ffffff` | `#fcd34d` / `#412006` |
| `warning-container` / `on-warning-container` | `#fef3c7` / `#422006` | `#5a4100` / `#fde68a` |
| `background` | `#fdfcff` | `#191c1d` |
| `on-background` | `#1a1c1e` | `#e1e3e4` |
| `surface` | `#f9f9fc` | `#101416` |
| `on-surface` | `#1a1c1e` | `#e1e3e4` |
| `surface-variant` | `#e1e2ec` | `#40484b` |
| `on-surface-variant` | `#44474f` | `#bfc8cc` |
| `surface-container-highest` | `#d4d5dc` | `#3b4244` |
| `surface-container-lowest` | `surface` mixed 4% white | `surface` mixed 4% black |
| `surface-container-low` / `-container` / `-high` | `surface` mixed with `surface-variant` 25% / 50% / 75% | `surface` mixed with `surface-container-highest` 25% / 50% / 75% |
| `outline` | `#74777f` | `#899296` |
| `outline-variant` | `#c4c6cf` | `#40484b` |
| `scrim`, `shadow` | (not defined — use `#000000`) | `#000000` |
| `--md-sys-elevation-1` | `0 1px 2px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.1)` | same |
| `--md-sys-motion-fast` | `200ms cubic-bezier(0.2, 0, 0, 1)` | same |
| `--header-bg` / `--header-border-color` | `#08191e` / `#FFA500` | `#08191e` / `#FFB347` |

Tool tokens (semantic data buckets, §3.2) — `bg` / `text` / `color`:

| Set | Light | Dark |
| :--- | :--- | :--- |
| amethyst | `#ebdcf9` / `#2c1a3d` / `#6d4c91` | `#38264c` / `#ebdcf9` / `#bba0d6` |
| sapphire | `#cce8e4` / `#0d302c` / `#2a6b64` | `#1a403b` / `#cce8e4` / `#77b5ae` |
| sage | `#e2e6c3` / `#25290f` / `#555c27` | `#353a16` / `#e2e6c3` / `#a3ab6e` |
| gold | `#f7e1c6` / `#3d260c` / `#8a5a23` | `#4d3211` / `#f7e1c6` / `#d1a673` |

Presence tokens `--presence-<state>` (light → dark): onsite `#00A44A` → `#4ade80`, remote `#002060` → `#93c5fd`, mission `#FE9900` → `#fcd34d`, busy `#B00000` → `#f87171`, grumpy `#7C3AED` → `#c4b5fd`, angry `#B00000` → `#f87171`, leave `#6B7280` → `#9ca3af`. Online dot = `bg-emerald-500 dark:bg-emerald-400`.

Rich-text color spans (`color.css`, `.rich-colors span.color[data-color]`, light → dark): primary `#1d4ed8` → `#60a5fa`, success `#15803d` → `#4ade80`, warning `#b45309` → `#fbbf24`, danger `#b91c1c` → `#f87171` (light values moved to the 700 step on 2026-11-07 for ≥4.5:1 on white; the nine mid-tone Tailwind names orange…sky in `color.css` moved from the 600 to the 700 step for the same reason; the admin editors' `TextColor::make(...)` swatches in the eight `*FormPresenter`/`CommentsRelationManager` files carry the same four light hexes so the picker preview matches what `color.css` renders — change both places together). `<mark>` = tertiary-container on on-tertiary-container, radius `0.2em`.

### 15.2 Themes — primary / on-primary / surface / on-surface (light + dark)
Key = the value passed as `theme` (`default` = no `data-theme`). A `—` in on-surface means the theme does not override it and inherits the default (`#1a1c1e` light / `#e1e3e4` dark). Each theme also overrides `primary-container`, `on-primary-container`, `surface-variant`, `background` and `--header-*` as it sees fit — an embedded service that cannot ship the whole `theme.css` block should at minimum mirror the four columns below plus `surface-variant`.

| Theme | primary L / D | on-primary L / D | surface L / D | on-surface L / D |
| :--- | :--- | :--- | :--- | :--- |
| default | `#4e5f66` / `#b5cbd2` | `#ffffff` / `#1f3238` | `#f9f9fc` / `#101416` | `#1a1c1e` / `#e1e3e4` |
| grey | `#2F2525` / `#B8A9A4` | `#fafafa` / `#2A2222` | `#F8F4F2` / `#1E1613` | `#1E1817` / `#EAE1DE` |
| midnight | `#2f3f63` / `#c8d7ff` | `#ffffff` / `#17243f` | `#f3f6fc` / `#0e1427` | `#171b24` / `#e2e8f4` |
| amethyst | `#6750A4` / `#D0BCFF` | `#FFFFFF` / `#381E72` | `#FAF8FF` / `#141218` | — |
| arctic | `#00A8CC` / `#5ED3F3` | `#ffffff` / `#003642` | `#F2FCFF` / `#0B1315` | — |
| blue | `#375E90` / `#A8C7FF` | `#FFFFFF` / `#002F65` | `#F8F9FF` / `#0B0E15` | — |
| nebula | `#496580` / `#A9C7E0` | `#ffffff` / `#16324A` | `#F4F8FC` / `#0E141A` | `#12181D` / `#E1E9F0` |
| obsidian | `#BFA14A` / `#F5D57A` | `#000000` / `#2B2100` | `#FAFAF7` / `#000000` | — |
| ocean | `#6B6B47` / `#C7C79B` | `#ffffff` / `#33341A` | `#F5F6EC` / `#1A1B10` | `#1C1D10` / `#E5E6D4` |
| sunset | `#9B4050` / `#FFB2BC` | `#FFFFFF` / `#5F1125` | `#FFF8F8` / `#191113` | — |
| silver | `#686D75` / `#C4C9D1` | `#ffffff` / `#23272D` | `#F3F4F7` / `#1A1B1D` | `#1C1E22` / `#E6E8EB` |
| graphite | `#5A5A5A` / `#B8B8B8` | `#ffffff` / `#2E2E2E` | `#F5F5F6` / `#1C1C1C` | `#1C1C1D` / `#E7E7E8` |
| sage | `#5f7d66` / `#b7d0bb` | `#ffffff` / `#1d3323` | `#f2f6f1` / `#111814` | `#171d18` / `#e2e8e3` |
| rosewood | `#8b5e74` / `#e0b7c7` | `#ffffff` / `#442333` | `#fbf5f7` / `#1c1418` | `#1d171a` / `#ece5e8` |
| ember | `#b45309` / `#ffb36a` | `#ffffff` / `#4a2300` | `#fcf5ef` / `#1b1512` | `#1d1715` / `#ece4de` |
| magneta | `#E8D59E` / `#E3C878` | `#2B230F` / `#3A2E0A` | `#FBF7EE` / `#1C1810` | `#26200F` / `#EDE6D4` |
| jade | `#0F766E` / `#80D5CF` | `#ffffff` / `#003D3A` | `#F0FAF8` / `#101C1B` | `#111D1C` / `#DCE9E7` |

Note: `magneta` and `obsidian` use a LIGHT primary in light mode with DARK on-primary text — never assume white on primary; always pair `primary` with `on-primary`.

### 15.3 Embedding the AI assistant (how theme/mode/context reach the iframe)
The dashboard widget (`components/dashboard/ai-assistant.blade.php` + `resources/js/components/alpine/data/aiAssistant.js`, see `viewPattern.md` §8.9) mounts an `<iframe>` pointing at the external service. `allow="clipboard-write; fullscreen; microphone"`, `referrerpolicy="strict-origin-when-cross-origin"`, `border-0`, fills the panel below a 44–48px primary-gradient header owned by the host.

| Param (query string) | Value | Source |
| :--- | :--- | :--- |
| `user` | `<app-name-slug>_<userId>` | `Str::slug(config('app.name_en')) . '_' . auth()->id()` |
| `lang` | `fa` (tenant-configurable) | `config('app.ai_assistant_lang')` |
| `role` | `admin` or `user` | `hasElevatedRole()` |
| `theme` | one of the 17 theme keys in §15.2 | `ThemeManager.getUserTheme()` → `localStorage['user-theme']` → tenant default → `default` |
| `mode` | `light` or `dark` | `ThemeManager.getUserMode()` → `localStorage['user-mode']` → `light` |
| `page` | module slug, regex `^[a-z0-9_-]{1,40}$`, lower-case, or empty | `?tab=` query param, else the first URL path segment (`dashboard`/`admin` → empty) |

- `theme`, `mode` and `page` are baked into the URL **once**, when the panel is first opened. Changing theme/mode afterwards does NOT reload the iframe; the embedded page should read the params at load and may re-read on `visibilitychange`/refocus.
- Every later open posts `window.postMessage({ winterraPage: '<slug>' }, <iframe origin>)` to the iframe so the service can re-scope to the page the user is on now. The embedded page must validate `event.origin` against the dashboard origin and the slug against the same regex.
- Embedded pages should render with the host's tokens: set `class="dark"` on `<html>` when `mode=dark`, apply the §15.2 values for `theme`, load Material Symbols Rounded + Yekan (§15.4), set `dir="rtl"` for `lang=fa`. Make the iframe body transparent-safe: background = `var(--md-sys-color-surface)` (the host panel behind it is the same color; its loading placeholder is `surface-variant` with a pulsing 40px `auto_awesome` icon in `outline`).
- Panel geometry the iframe lives in: corner card `w-[400px]` (full width minus 16px each side below `md`), `h-[600px]` capped at `max-h-[70vh]`, `bottom-24 left-6`; maximized = `inset-4` (`md:inset-10`). So the service must be fully usable from 320px to ~400px wide and also fluid in a near-fullscreen frame.
- Host's own `?embed=1` flag (`request()->boolean('embed')`) strips the dashboard chrome (header/loading line); it is the Laravel pages' convention, not a parameter for third-party services.

### 15.4 Typography
Font stack (`--font-sans`): `'Yekan', 'IranYekan', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif` + emoji fallbacks. Yekan (woff2/woff/ttf, `font-display: swap`, a single regular weight — bold is synthesized/uses the same family) is the primary face for Persian; IranYekan is the fallback face. Digits are rendered Persian (`convertToPersian()`); counters use `tabular-nums`; IDs/codes/English use `dir="ltr"` islands and `font-mono` at 11px. Base `html { font-size: 100% }` (16px). The user can scale the root in 10% steps from -2 to +3 (80%, 90%, 100%, 110%, 120%, 130%) via `fontSizeLevel` — so **size everything in rem/Tailwind units, not fixed px, or your page will not follow the user's accessibility setting.**

| Role | Class → size / weight | Where measured |
| :--- | :--- | :--- |
| Modal title (dialog) | `text-xl` (20px) `font-bold` | `ui.modals.dialog` header |
| Confirmation title | `text-lg` (18px) `font-black` `leading-tight` | `ui.modals.confirmation` |
| Panel / launcher title | `text-[15px] sm:text-base` (16px) `font-bold` `leading-tight truncate`; launcher name `sm:text-lg` | `ui.modals.panel`, `modal/menu` |
| Section title | `text-base` `font-bold` (`ui.title`, ai-report list heading) | every module header |
| Card title | `text-sm`–`text-base` `font-bold` `tracking-tight` | project/channel headers |
| Body / table cell / input value | `text-sm` (14px) regular–medium; input value `1rem` (16px) | tables, lists |
| Caption / meta | `text-xs` (12px) | descriptions, helper, table head |
| Micro (chips, timestamps, badges) | `text-[11px]` (most used px size), `text-[10px]`, `text-[9px]` | chips, status pills, meta |
| Button label | `0.925rem` (14.8px) `font-medium` `letter-spacing .01em`; compact `text-sm font-bold` | `.md3-btn`, tonal |
| Tab label | `text-xs sm:text-sm` `font-bold` | `tab-selector` |
| Tooltip | `12px` `font-medium` `tracking-wide` | `core/tooltip.js` |
| Toast | `text-sm` `font-bold` | `ui.modals.toast` |
| Table head | `text-xs` `uppercase` `tracking-wider` `font-medium` | `ui.table` |
| Floating label (input) | `text-sm` `font-medium` → scaled to 75% when floated | `.md3-label` |
| Hero numerals / stat | `text-2xl`–`text-4xl` `font-bold` | home stat tiles |

Weights in use (count): `font-bold` ~1000, `font-medium` ~370, `font-semibold` ~150, `font-black` ~35, `font-normal` ~20. Tracking: `tracking-tight` titles, `tracking-wide/wider/widest` uppercase micro labels. Line-height is the Tailwind default paired with each size (1.5 for text-sm/base, 1.33 for text-xs) except `leading-tight` (1.25) on titles, `leading-none` on icons and `1.6` on prose message blocks.

### 15.5 Radius, spacing, elevation, layers
**Radius** (Tailwind v4 scale: sm 4px, md 6px, lg 8px, xl 12px, 2xl 16px, 3xl 24px). Usage counts: `rounded-xl` 734, `lg` 607, `2xl` 370, `full` 283, `md` 209, `3xl` 20, `sm` 13.

| Component | Radius |
| :--- | :--- |
| Page cards, table card, panels, dialogs, popup widgets, AI panel | `rounded-2xl` (16px) |
| Launcher menu modal, loader card, welcome-empty box | `rounded-3xl` (24px) |
| Confirmation card (`.modal-inner-card`) | `1.5rem` (24px) |
| Auth card | `rounded-[32px]` |
| Buttons (primary / danger / ghost / tonal / icon) | `rounded-xl` (12px); outlined button `rounded-full` |
| Filled input (`.md3-input`) | `rounded-t-lg` (top corners 8px, bottom is a 1px underline); compact search `rounded-xl` |
| Checkbox | `rounded-[6px]` (20x20); switch track `rounded-xl` (`.md3-switch`: `rounded-full`) |
| Chips / status pills / title-count chip | `rounded-lg` (8px); `ui.badge` `rounded-md` (6px) |
| Tab bar / tab button | bar `rounded-2xl`, button `rounded-xl` |
| View toggle | group `rounded-lg`, button `rounded-md` |
| Avatars | `rounded-[28%]` squircle (never `rounded-full`); presence dot `rounded-full` |
| Icon badges (title icon, header icon box) | `rounded-lg` (32px box) / `rounded-xl` (40px) / `rounded-2xl` (44–48px) |
| Tooltip | `rounded-lg`; popover `rounded-xl` (elevated: `rounded-2xl`); toast `rounded-xl` |
| Calendar day cell | `rounded-[14px]`, `aspect-[1/0.85]` |
| Skeleton bar / skeleton card | `rounded-lg` / `rounded-2xl` |
| Scrollbar thumb | `10px` (8px wide track) |
| Focus ring | `outline: 2px solid var(--md-sys-color-primary); outline-offset: 2px; border-radius: 8px` |

**Spacing** (4px grid; counts in parentheses). Padding: `p-4` (132), `p-6` (90), `p-1`, `p-5`, `p-3`; horizontal `px-4` (409), `px-3`, `px-6`, `px-2`; vertical `py-2`, `py-3`, `py-0.5`, `py-4`, `py-1.5`. Gaps: `gap-2` (599), `gap-3` (396), `gap-1.5` (361), `gap-1`, `gap-4`. Anatomy defaults: card body `p-4 md:p-5` (large `p-6`), list row `px-4 py-3`, table cell `px-6 py-4` (`px-4 py-3` in dense profile tables), modal body `p-6`, modal header `px-4 sm:px-6 py-2.5 sm:py-4`, form field stack `space-y-5`/`gap-4`, section gap `mb-8`, page max width `max-w-[88rem]` (1408px) centered, grid gaps `gap-3 md:gap-4`.

**Elevation** — Tailwind shadow counts: `shadow-sm` 385 (resting cards), `shadow-md` 141, `shadow-lg` 54, `shadow-2xl` 27 (modals/slide-over), `shadow-xl` 25 (popovers, toasts), `shadow-inner` 15, `shadow-none` 11. Tinted shadows: glass `0 8px 32px color-mix(primary 25%)`, hover `0 16px 48px color-mix(primary 35%)`, card hover `0 8px 24px color-mix(primary 12–18%)`, tab active `0 4px 12px color-mix(primary 30%)`, menu launcher `0 16px 48px color-mix(primary 25%)`; dark mode swaps to `0 12px 40px rgba(0,0,0,.6)`; AI panel `0 24px 80px rgba(0,0,0,.28)`; button rest `0 1px 2px rgba(0,0,0,.12)`, hover `0 4px 8px rgba(0,0,0,.15)`; popover elevated `0 12px 32px color-mix(scrim 22%)`; tooltip `0 4px 6px -1px rgba(0,0,0,.1)`. Card top accent bar: `h-[3px]` primary with `0 2px 8px color-mix(primary 40%)` glow.

**Z-index layers**: in-component `z-10` (sticky sub-headers; 113 uses), `z-20` (module headers), `z-30`, `z-40` (composer popovers), top header `z-50`, navbar `z-[51]`, page overlays/modals/dialogs/slide-over/launcher/toast `z-[100]`, popovers `z-[110]`, AI panel `z-[999]` (maximized `z-[1000]`), loading line `z-[100]`, full-page loader / minimized auth bubble / tooltip `z-[9999]`, modal close button `z-[10002]`, lightbox `z-[99998]`, floating mini-toast `z-[99999]`. Backdrops (scrim) = `bg-[var(--md-sys-color-primary)]/60` (lightbox `/90`) — the scrim is primary-tinted, not black.

### 15.6 Icons
Library: **Material Symbols Rounded**, self-hosted variable font (`woff2`, weight axis 100–700, `font-display: block`), used by ligature name: `<span class="material-symbols-rounded">search</span>`. Class defaults: `font-size: 24px; line-height: 1; direction: ltr`. Filled glyph = utility `.font-fill` (`'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24`). Sizes by frequency: `text-[16px]` (235), `[14px]` (182), `[18px]` (169), `[20px]` (118), `[15px]`, `text-base` (16), `text-lg` (18), `[13px]`, `[12px]`, `text-xl` (20), `text-2xl` (24, toasts), `[40px]` / `[56px]` (empty-state hero), `text-[220px]` (empty watermark at 4% opacity). Roles: button icon 20px, input icon 20px, close icon 16 / 18–20 / 24 (sm/md/lg), table-tool icon 18px, chip icon 13–14px, title icon 16px in a 32px box, loader `progress_activity` (`animate-spin`). Icons sit in tinted boxes (`primary-container` / `secondary-container` + matching `on-*`) rather than bare; navigation arrows use `arrow_forward` as "back" (RTL), `chevron_right`/`chevron_left` swapped for horizontal pagers.

### 15.7 Component catalogue

**Buttons** (`ui.buttons.form`, `variant` prop; all `inline-flex items-center justify-center gap-2`, `transition-all duration-200`, `active:scale-95`, `disabled:opacity-50 disabled:cursor-not-allowed`, leading icon 20px, optional spinner `h-4 w-4 animate-spin`):

| Variant | Height / padding | Radius | Fill | Text | Hover |
| :--- | :--- | :--- | :--- | :--- | :--- |
| primary (`.md3-btn`) | `h-11` (44px) `px-6` | `rounded-xl` | `primary` + `0 1px 2px rgba(0,0,0,.12)` | `on-primary`, `0.925rem` `font-medium` | `brightness(108%)`, `translateY(-1px)`, bigger shadow; active `brightness(95%)` |
| secondary / outlined (`.md3-btn-outlined`) | `h-11` `px-6` `border` | `rounded-full` | transparent, border `outline` | `primary` | bg `primary-container`, border `primary`, text `on-primary-container` |
| danger | as primary | `rounded-xl` | `error` | `on-error` | `brightness-110` |
| ghost | `h-10` (40px) `px-4` | `rounded-xl` | transparent | `on-surface` | bg `on-surface/10` |
| tonal | `h-10` `px-4` `text-sm font-bold` | `rounded-xl` | `primary-container` | `on-surface-variant` | bg `surface-container-highest` |
| icon (`size="icon"`) | `w-10 h-10 p-0` | `rounded-xl` | per variant | — | — |
| toolbar icon | `w-8 h-8` (most common: `h-8` 325 uses) / `w-9 h-9` / `w-10 h-10` (header) | `rounded-lg`/`rounded-xl` | transparent / `surface-variant` | `on-surface-variant` | bg `surface-variant`, `active:scale-95` |
| close button | `sm` 28px `rounded-lg`, `md` 32px→36px `rounded-xl`, `lg` 40px `rounded-xl`; icon rotates 90deg on hover (300ms) | | neutral: transparent, hover `error/12%` + text `error`; on-primary: `on-primary/15%` + border `on-primary/20%` | | |
| fullscreen-overlay footer (`.modal-btn`) | `padding 12px 30px`, `min-width 120px`, `1.1rem` `font-weight 600`, 2px border | `0.75rem` | confirm = `on-primary` fill with `primary` text; cancel = transparent outlined | | confirm hover goes transparent + glow |

Footer order (RTL): cancel FIRST (rightmost, outlined/ghost, never red), confirm second (leftmost, filled).

**Form fields** (`ui.forms.*`; floating-label "filled" style, label on the right in RTL):

| Control | Spec |
| :--- | :--- |
| Text input / select / textarea (`.md3-input`) | wrapper `.md3-input-group` = `relative mt-2`; field `w-full`, `px-4 pt-6 pb-2.5` (≈58px tall at 16px font; textarea default `rows=3`), `rounded-t-lg`, font `1rem`, bg `surface-variant` @ 8% (dark 10%), only a `1px outline-variant` bottom border; hover bg @ 13%, border `on-surface-variant`; focus border `2px primary` + glow `0 8px 24px -14px color-mix(primary 60%)`; disabled `opacity .55`; transition `.18s cubic-bezier(.2,0,0,1)` |
| Floating label (`.md3-label`) | `absolute right-4 top-4 text-sm font-medium`, color `on-surface-variant`; floated = `translateY(-0.85rem) scale(.75)` (`.24s`), turns `primary` + `letter-spacing .03em` on focus; with a leading icon the resting label shifts to `right-10` |
| Leading icon | 20px `material-symbols-rounded`, `right-0 pr-3`, color `on-surface-variant` → `primary` on `group-focus-within`; field gets `pr-10` |
| Compact search (`ui.forms.search`) | `h-10`, `rounded-xl`, `text-sm`, bg `surface-variant`, `border outline-variant/50`, `pr-10 pl-20`, search icon 20px right, clear `close` 18px left, maximize trigger at `left-10`; debounce 300–400ms |
| Searchable select | same field; panel = popover (below), max 50 results, Persian-normalized (`ي→ی`, `ك→ک`) |
| Validation | `mt-1 text-xs text-[error]` + `animate-pulse` |
| Checkbox (`ui.forms.checkbox`) | 20x20, `rounded-[6px]`, `border-2` (`outline-variant` 80%) on `surface-container-highest` 40%; checked = `primary` fill/border + 15px `check` icon in `on-primary`; hover border `primary` 70%; focus `ring-4` |
| Radio | native with `accent-color: primary`, `text-sm`, `gap-x-5 gap-y-2` |
| Switch (`ui.forms.switch`) | track `w-12 h-7` (48x28) `rounded-xl` `surface-variant` → `primary` when checked; thumb 20x20 white at 4px inset, slides full width (flips under RTL via `rtl:` variant). Legacy `.md3-switch`: 56x32 track, 24px thumb, `outline` → `on-primary` |
| Native `<select>` options | Chrome 135+ follows §4.0's `base-select` block for single selects (hover `primary-container`, checked `secondary-container`; `multiple`/`size` selects excluded); legacy fallback (`@supports not (appearance: base-select)`) = hover/checked `primary-container` / `on-primary-container`, dark options `surface`/`on-surface` |

**Modals / panels** (all teleported to `<body>`, `dir="rtl"`, Esc closes, backdrop click closes):

| Kind | Size | Anatomy |
| :--- | :--- | :--- |
| Dialog (`ui.modals.dialog`) | `sm:max-w-2xl` (672px), full width + `px-4 py-6` on phones, `max-h-[calc(100dvh-6rem)]`, `rounded-2xl`, `glass-panel` | header `px-6 py-4` on `surface-container` + `border-b`, title `text-xl font-bold`, close right; body `p-6`, scrolls |
| Panel (`ui.modals.panel`) | `max-w-2xl` default, `h-[85dvh] sm:h-[680px]`, outer pad `p-3 sm:p-6 md:p-8` | header = gradient `to-l` `primary` → `primary` 85%, `px-4 sm:px-6 py-2.5 sm:py-4`, 40–44px icon badge (`rounded-2xl`, `on-primary` 15% fill / 25% border), title 15–16px bold, subtitle 11–12px at 80%, actions + close(28px); optional search strip `p-4 border-b outline-variant/20`; body flex-1 scroll |
| Launcher menu (`modal/menu`) | `w-[920px] max-w-[95%] h-[680px] max-h-[88vh]` (phones `h-[85dvh]`), `rounded-3xl` | same gradient header (`py-2 sm:py-5`, 44–48px avatar), body = tile grid `grid-cols-3 sm:grid-cols-4 gap-2 sm:gap-2.5 auto-rows-fr` (tile `p-3`, needs `min-h-0`), swipe to change page |
| Slide-over (`ui.modals.slideover`) | `max-w-3xl` (768px) default, full width on phones, `inset-y-0 left-0`, `shadow-2xl`, `border-r outline-variant/20` | enters `0.5s cubic-bezier(.32,.72,0,1)` from the left |
| Confirmation (`ui.modals.confirmation`) | card `max-w-md` (448px), `p-6`, `space-y-5`, `rounded-[1.5rem]` glass | full-screen primary-colored curtain opens (width .5s then height .5s); icon box 44px `rounded-xl` (`primary-container`, or `error-container` for danger), title `text-lg font-black`, rows label/value, footer buttons §15.7 order |
| Lightbox | viewers are Fancybox top-layer `<dialog>`s (library CSS in `app.css`); `x-ui.modals.lightbox` is kept, unused | prev/next/Esc from Fancybox |
| AI panel | see §15.3 | gradient header 135deg `primary` → 78% primary + 22% black, shimmer overlay, 44px icon box |
| Auth card | `max-w-[540px]`, `rounded-[32px]` | flip-card (login / forgot), top hairline gradient, entry `animate-slide-in-top`; minimize bubble `bottom-8 left-8`, `rounded-2xl`, blur 12px |

**Cards**: `bg surface`, `border 1px outline-variant` (usually 40–60% via `/40`–`/60` or `color-mix`), `rounded-2xl`, `shadow-sm`, hover `shadow 0 8px 24px color-mix(primary 12–18%)` + optional `border primary/40`, `transition-all duration-300`. Variants: interactive tile `hover:-translate-y-1 active:scale-[0.98]` (taskboard, `bg primary-container/70`); accent bar `h-[3px]` top; ads card `min-h-[380px] max-w-[400px]` in a `grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4`; hero deck = `primary-container` bg + 24px dot grid (10% opacity); stat tiles by state (§13); glass card §4.1. Section header above a card list = `ui.title`: 32px `rounded-lg` icon box (`primary-container`), `text-base font-bold` title, `flex-1 h-px outline-variant/50` rule, optional count chip (`text-[11px] font-bold px-2.5 py-1 rounded-lg`), `mb-8`.

**Tables** (`ui.table`): card `rounded-2xl border outline-variant bg surface shadow-sm min-h-[300px] overflow-hidden`; wrapper `overflow-x-auto`; `table` `min-w-full text-sm border-separate border-spacing-0` (module tables use `text-right`); `thead` bg `surface-container-high` (or `-low`) + `text-xs uppercase tracking-wider text-on-surface-variant font-medium`, `th px-6 py-4` (dense `px-4 py-3`); `tbody` `divide-y outline-variant/40` on `surface`; `td px-6 py-4`; row hover `bg primary/[0.03]`, active `/[0.06]`, `cursor-pointer`; responsive columns hidden with `hidden sm|md|lg:table-cell`; sort via `sort-label` + `sort-popover`; column toggle + density toggle in the last `th` toolbar (icons 18px, `p-1 rounded-lg`, on = `primary` fill). **Compact density** = class `app-density-compact` on `<html>` (`localStorage['app-density'] === 'compact'`) which sets `td { padding-block: 0 }` for tables carrying a marker class (`.ths-ticket-table`, `.reservation-command-table`, `.ai-report-table`, `.dms-doc-table`, `.launchpad-table`) and tightens `.taskboard-card`.

**Tabs / segmented controls**: tab bar (`ui.buttons.tab-selector`) = `flex p-1 w-fit max-w-full bg surface-variant/40 rounded-2xl border outline-variant/30 shadow-sm`, scrolls horizontally on phones; tab `px-3 py-2 sm:px-6 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold gap-1.5 sm:gap-2` with `text-base sm:text-lg` icon; active = `primary` bg + `on-primary` + `0 4px 12px color-mix(primary 30%)`; inactive = `on-surface-variant`, hover `surface-variant/60`; unread badge `min-w-[18px] h-[18px] px-1 rounded-md text-[10px] font-bold` (`tertiary-container` inactive / `on-primary/15%` active). Two-mode toggle (`view-toggle`): group `bg surface-container-high p-0.5 rounded-lg border outline-variant/40`, buttons `w-8 h-8 rounded-md`, 18px icon, active `primary` + `scale-105`.

**Badges / chips**: `ui.badge` = `inline-flex rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset` with variants `success` (green-50/700), `warning` (yellow-50/800), `danger` (`error-container`), `info` (`primary-container`), neutral (`surface-variant`); status pill = `rounded-lg px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset shadow-sm` with a 6px dot; data chips `px-3 py-1 rounded-xl text-[11px] font-bold` or `px-1.5 py-0.5 text-[10px]` + 13px icon; semantic color rule = container/on-container pairs (§3.3) and Tool sets (§3.2); solid status chip in dark = `dark:bg-{c}-400 dark:text-{c}-950` (§12). Mention chip §11.

**Tooltips / popovers / dropdowns**: tooltip = hover text from `data-tip` (`<x-ui.modals.tooltip text position>`), a single fixed element: `z-[9999] px-3 py-1.5 rounded-lg bg primary text on-primary text-[12px] font-medium tracking-wide`, `max-w-[85vw]`, one line, fades in `300ms ease-out`. Popover/dropdown (`ui.modals.popover`): `w-48` (192px), `rounded-xl bg surface shadow-xl border outline-variant/20`, items in `py-1`, positioned `fixed` 8px from the trigger, `z-[110]`, enter `200ms ease-out` (`opacity-0 scale-95 translate-y-2` → full), leave `150ms ease-in`. Elevated variant = `surface-container-low`, `rounded-2xl`. Composer popovers (emoji 272px min, mention 220px min / 240px max-h) = `rounded-xl p-1.5–3`, `shadow 0 12px 48px color-mix(shadow 18%)`, `z-40`.

**Toasts / notifications**: `ui.modals.toast` = `fixed bottom-6 left-6 z-[100] max-w-sm (384px) w-full flex items-center gap-4 px-6 py-4 rounded-xl shadow-xl border`; 24px icon (`info`/`check_circle`/`error`/`warning`), `text-sm font-bold` message, 18px close; colors info = `secondary-container`, success = `primary-container`, error = `error-container` (border `error`), warning = `tertiary-container`; auto-hide 3000ms; enter `300ms` (`translate-y-4 scale-95` → 0), leave `200ms`; `animate-toast-in` .35s for in-feed toasts. Mini toast `.toast-floating`: bottom 80px centered, `primary` bg, `surface` text, `6px 16px`, `0.5rem` radius, fades in 200ms. Server (Filament) notifications and the bell list reuse the same tokens through `notification.css` (Filament's `color-*` ramp is remapped onto `primary-container`/`primary`).

**Empty / loading / skeleton**: empty (`ui.empty`) = centered column, `h-64` (or `h-full` when `fill`), whole block `opacity-60` in `on-surface-variant`; icon in `p-4 rounded-2xl surface-container-high` box with a 40px glyph (welcome variant `p-6 rounded-3xl`, 56px), title `text-base font-medium` (welcome `text-lg font-bold`), description `text-xs mt-1 opacity-70`, optional slot (`mt-4`) and 220px watermark glyph at 4%. Skeleton = `bg surface-variant rounded-lg animate-pulse` bars (`h-4` default, `h-3`, `h-3.5`, `h-24` image) inside a skeleton card (`rounded-2xl border outline-variant/40 p-4 gap-2`). Page-level load = 4px (`0.25rem`) `--header-border-color` line fixed at the bottom (`.loading-line`, `z-[100]`) plus a centered loader card (`max-w-[280px] p-6 md:p-8 rounded-3xl`, 48px `primary-container` icon box with spinning `progress_activity`, `text-base font-bold` message) over `surface` @ 60%. Buttons show their own spinner (`h-4 w-4`) and disable via `wire:loading`.

**Pagination / paging**: no numbered pagers in the user panel — lists use a "load more" button (`ui.buttons.load-more`: icon + label, spinner `progress_activity` while loading, caps from config) or upward scroll-prepend (chat). Horizontal rails use arrow buttons (`w-5 h-5 md:w-6 md:h-6 rounded-md border`, chevrons) and the launcher pages with horizontal swipe.

**Search box**: see compact search in form fields; header search = `h-[40px] rounded-[12px]` pill on the primary navbar (`on-primary/5%` fill, `on-primary/10%` border, hover `/10%`), max width `max-w-xs md:max-w-md`; client-side filters copy the same visual language (20px icon, `pr-10`, clear button).

**App chrome**: header brand strip sticky `top-0 z-50`; navbar = sticky `top-[60px] lg:top-[80px] z-[51]`, `h-16` (64px), `bg primary`, text `on-primary`, `px-3 lg:px-6`, hairline `on-primary/10`, icon buttons `w-10 h-10 rounded-xl`, dividers `w-px h-6 on-primary/15`; auto-hides on scroll (`translate-y` -60/-80px, 300ms). Content width `max-w-[88rem]`; module screens usually fill `h-screen` minus chrome (`max-h-[calc(100svh-8rem)]`, `lg:`-`6rem`) with an inner scroll. Two-pane chat modules: sidebar `w-full md:w-[320px] lg:w-[360px]` with a right border (`outline-variant` 30%), header `h-16` blur (`backdrop-blur-xl`, surface 94%).

### 15.8 Breakpoints and mobile behaviour
Tailwind v4 defaults: `sm` 640px, `md` 768px, `lg` 1024px, `xl` 1280px, `2xl` 1536px (usage: `md:` ~940, `sm:` ~450, `lg:` ~200, `xl:` ~22). Custom: `max-[359px]` (tiny phones), `min-[480px]`. **`md` (768px) is the phone/desktop switch**: two-pane modules show list OR conversation below it (back button `md:hidden`, `arrow_forward`), full list + detail above it. Grids go `grid-cols-1` → `sm:grid-cols-2`/`md:grid-cols-2` → `lg:grid-cols-3` (launcher 3 → 4 columns). Tables hide secondary columns progressively (`hidden sm|md|lg:table-cell`). Header/navbar offsets switch at `lg` (60px → 80px). Modals become near-fullscreen (`h-[85dvh]`, `p-3`) on phones, centered fixed-size from `sm`. Touch targets are >= 32px (`w-8 h-8` is the floor for toolbar icons, 40–44px for primary actions); tab bars scroll horizontally; hover-only affordances need a tap equivalent (`<x-ui.hover-popover>` opens on hover only for `(hover: hover) and (pointer: fine)`, viewPattern §8.7; the reaction chip's tap only toggles the reaction); use `dvh`/`svh`, not `vh`, for full-height panes; form fields use 16px text (`.md3-input`) so iOS does not zoom (the compact 40px search box is `text-sm`).

### 15.9 RTL specifics
- Locale `fa`; every module root, portal and modal sets `dir="rtl"` itself (modals teleported to `<body>` must carry their own `dir="rtl"`). Embedded services should set it on `<html>`.
- Fonts: Yekan first, IranYekan fallback (§15.4); Persian digits; Jalali dates only (week starts Saturday, Friday = index 6 shown in `error` color).
- Layout is mostly RTL-native flexbox (first DOM child = rightmost). Components use a mix of physical (`right-4` label, `pr-10` icon padding, `left-6` toasts/AI panel/auth bubble) and logical utilities (`start/end`, `ps/pe`, `inset-inline-*`) — when copying, keep the physical sides as measured: field icons and floating labels on the RIGHT, clear/maximize/close affordances on the LEFT, toasts and dock tools bottom-LEFT, modal close top-RIGHT, slide-over enters from the LEFT.
- Things that flip: text alignment (`text-right` in tables/lists), switch thumb direction (`rtl:peer-checked:after:-translate-x-full`), footer button order (cancel first/rightmost), chevron/arrow icon choice (`arrow_forward` = back, `chevron_right`/`chevron_left` swapped in pagers), sidebar border side. Things that do NOT flip: Material icon glyph direction (`direction: ltr` on the icon class), `dir="ltr"` islands for IDs, codes, e-mails, English and numbers-with-units, progress bars, code/mono text.
- Scroll: `scrollbar-gutter: stable` on `html, body`; scrollbars are invisible until hover (thin primary-tinted thumb: `8px` wide, `color-mix(primary 20–40%)`, `10px` radius).

### 15.10 Motion
Durations are short and compositor-only (§5.1). Measured: theme/color change `500ms cubic-bezier(.4,0,.2,1)` (links, buttons, inputs, labels); `--md-sys-motion-fast` `200ms cubic-bezier(.2,0,0,1)` (outlined button, small controls); filled button `250ms`; input `180ms`, floating label `240ms`; tab/card hover `300ms`; close button `200ms` (+ icon rotate `300ms`); popover in `200ms` / out `150ms`; toast in `300ms` / out `200ms`; dialog panel in `300ms` / out `200ms` for non-overlay dialogs; overlay shells enter with `animate-slide-down` and leave with `animate-slide-down-out`, both `600ms` (`cubic-bezier(.16,1,.3,1)` in, reversed `cubic-bezier(.7,0,.84,0)` out); slide-over `500ms cubic-bezier(.32,.72,0,1)`; backdrop in `500ms` / out `400ms`; lightbox `320ms cubic-bezier(.22,1,.36,1)`; list entrance `slideUpFade` `250–450ms` with `50ms` stagger steps; chat bubble in `250ms`; tooltip fade `300ms`; spinner `1s linear`; skeleton/attention pulse `2s cubic-bezier(.4,0,.6,1)`. Press feedback: `active:scale-95` (buttons), `[0.98]` (cards), `[0.92]` (close). Every infinite animation has a `prefers-reduced-motion` off-switch — honour it in embedded pages too.

### 15.11 Pre-merge visual-parity checklist (embedded service vs. dashboard)
1. Switch `theme` x `mode` for at least `default`, `jade`, `magneta` (light AND dark): backgrounds, text, borders and primary all follow the §15.2 values; no hard-coded hex anywhere (grep for `#` and `rgb(`).
2. `on-primary` text sits on every `primary` fill (check `magneta`/`obsidian`, where it is dark).
3. Font renders as Yekan with Persian digits; `dir="rtl"`; labels/icons on the right, toasts/close on the left; the page still works at +30% root font size.
4. Radii match §15.5 (cards/panels 16px, buttons/inputs-fields 12px/8px-top, chips 8px, avatars squircle) and spacing follows the 4px grid (card `p-4`/`p-5`, rows `px-4 py-3`, gaps 8/12px).
5. Buttons: primary 44px tall (`rounded-xl`), icon buttons 32/36/40px, label ~14.8px medium; hover lift + press-scale present; focus ring = 2px primary with 2px offset (never removed).
6. Inputs use the floating-label filled style (underline only, 2px primary on focus), 16px text; search box 40px tall `rounded-xl`.
7. Icons are Material Symbols Rounded at the §15.6 sizes inside tinted rounded boxes; no emoji or other icon sets.
8. Surfaces: cards `surface` + 1px `outline-variant` border + `shadow-sm`, hover primary-tinted shadow; scrim is primary-tinted (`primary/60`), never black; modal/overlay shadows per §15.5.
9. Motion: durations 150–500ms, only transform/opacity, reduced-motion respected, skeletons use `animate-pulse` on `surface-variant`.
10. Mobile: usable at 320px and at the 400px AI-panel width and when maximized; breakpoint behaviour switches at 768px; tap targets >= 32px; scrollbars hidden until hover.
11. Iframe contract: reads `theme/mode/lang/user/role/page` from the query string, listens for `postMessage({ winterraPage })` after validating `event.origin`, shows a `surface-variant` skeleton before first paint.
12. Side-by-side screenshot diff against the dashboard AI panel in light + dark, one empty state, one error toast, one table and one modal.

## 16. Top navbar toolbar colours (2026-11-07, from the contrast audit)
The top navbar (`#navbar > nav`, `components/dashboard/navbars/top.blade.php`) no longer paints with `--md-sys-color-primary`. Each named theme sets `--toolbar-bg` (a dark, theme-tinted fill, light + dark values) and `--md-sys-color-on-primary: #FFFFFF` on `#navbar > nav` at the end of the grey block in `theme.css`; the default theme sets neither and falls back to `primary` via `var(--toolbar-bg, var(--md-sys-color-primary))`. `--toolbar-error: #F87171` (the same red as dark `--presence-busy`; a real red, ≥3.1:1 on all 32 bars, lowest on graphite light) is set on the same element for every theme and mode, because the light-mode `#ba1a1a` measured 1.3–2.5:1 on the new bars and the earlier pale `#FFB4AB` read as pink; the bell dot and swap-button dots read `var(--toolbar-error, var(--md-sys-color-error))`, the bell ring and swap-button border/glyph read `--toolbar-bg`. **Never override `--md-sys-color-primary` on the nav**: the account dropdown is a DOM child and uses `primary` as text on `surface`, so a dark primary there makes its icons vanish in dark mode. Teleported modals (menu, command palette) are unaffected either way. New toolbar-chrome colours go through `--toolbar-*`, not `primary`/`error`.

### 16.1 Page colour tones (2026-11-07)
Settings → background picker has a «رنگ پس‌زمینه» accordion row (turning it on selects the first neutral, خنثی; the four tooltips are named by temperature — خنثی / گرم / سرد / ملایم — not by a colour of snow/pearl/mist, because the same swatch is a near-white in light mode and a near-black in dark mode and a literal colour name read wrong there; the popover itself uses a `compact` variant of `toggle-row`/`background-picker` — 13px labels, 28px icon chips — plus `w-60` and a viewport-capped scroll body, while the Profile settings page keeps the roomier default) (same grammar as «پس‌زمینه طرح دار»: a `toggle-row`, content hidden until it is on, so the popover stays short): four neutral swatches (خنثی `#FFFFFF`, گرم `#FBF9F6`, سرد `#F1F4F8`, ملایم `#E9ECEF`; ids stay `snow|pearl|mist|ash`; a second tap on the active swatch clears it) plus a **hue spectrum slider** (`.hue-slider`, 0–359) for unlimited diversity: the stored value is `bgTone = 'hue:N'`, applied as `html[data-bg-tone="hue"]` + the `--tone-h` custom property, and CSS derives BOTH modes from the one number — light `oklch(0.955 0.03 N)`, dark `oklch(0.21 0.036 N)` (an 8-swatch pastel row was tried first and replaced by the slider: more range, one control, no extra height). The neutral swatches below describe the original mechanism: The choice lives in `localStorage['bgTone']` (listed in `EXPORT_KEYS`, cleared by `resetAppearance`), is applied before first paint by `public/js/prefs-manager.js` as `html[data-bg-tone]`, and `dashboard.css` overrides ONE token: `--md-sys-color-background` becomes the tone in light mode and `color-mix(var(--tone) var(--tone-k), black)` in dark mode — the dark variant is derived from the light hex, with a per-tone `--tone-k` (13/10/7/5%) so the four dark grades stay visibly distinct (a flat 8% gave all four ≈ `#141414`, caught in review); adding a tone is one `{id,name,light,k}` entry in `TONES` (`stores/background.js`), one line in `prefs-manager.js`'s allow-list and one `html[data-bg-tone="…"]` rule carrying the same `--tone`/`--tone-k`. A selected tone deliberately replaces the theme's own dark background (e.g. obsidian's pure black). Cards keep their own `surface` tokens, so only the page behind them changes; image/pattern backgrounds sit above it and are unaffected.

### 16.2 Light-mode text-on-fill fixes (same audit)
Light `--md-sys-color-on-success` is now `#363636` and `on-warning` `#414141` (white on `#10b981`/`#f59e0b` measured 2.54/2.15:1); every named theme inherits them. Seven themes also override a light foreground only (arctic `on-primary`/`on-tertiary`, nebula/ocean/rosewood `on-tertiary`, obsidian/sage `on-secondary`; silver was re-based on a neutral cool-grey palette on 2026-11-07 so it needs none) in the block after the toolbar rules in `theme.css`; dark values are untouched (`:root.dark` and `[data-theme].dark` out-rank them). Primary-as-text on `surface` for arctic, obsidian, sage, magneta (light only) is fixed by five unlayered rules at the end of `dashboard.css` that recolour `.text-[var(--md-sys-color-primary)]` to the audit's darker accent (`#007B96`, `#836E33`, `#716D63`, `#59745F`, `#786E52`) without touching any fill; they must stay unlayered to out-rank the utilities layer, and hover/opacity variants of that class are not covered. Home's connection chip uses `error`/`warning` role tokens; Status About Me cards use the `--tool-gold-*`/`--tool-sage-*` sets (no hex); Status filter chips use `bg-{c}-700 text-white` in light (safelisted in `app.css` via `@source inline`) and keep the `dark:` 400/950 pair; cover-image meta chips use `bg-black/45 text-white`. In chat toasts, hints and state icons never use a fixed hex or opacity under 70% for real text: the undo button uses `on-primary` (was `#facc15`, unreadable on pale dark-mode primaries); edit hints and read ticks sit at `opacity-70`.