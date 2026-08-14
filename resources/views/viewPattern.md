# View Layer Architecture

Structural organization of the view layer: boundaries between reusable UI primitives, domain components, and page-level compositions.

---

## 1. Components (`resources/views/components/`)

```
components/
├── admin/
├── auth/
├── dashboard/
└── ui/
```

### 1.1 UI Components (`components.ui.*`)
Design system primitives with zero business logic. Dedicated subdirectories for variant families (`ui/buttons/`, `ui/modals/`, `ui/forms/`); root `ui/` for singletons.

| Criterion | UI Location | Domain Location |
|-----------|-------------|-----------------|
| Cross-domain utilization | Required | Prohibited |
| Business logic coupling | None | Present |
| Eloquent model dependency | None | Allowed |
| `config()` or `auth()` references | None | Allowed |

```blade
<x-ui.buttons.form variant="primary">Submit</x-ui.buttons.form>
<x-dashboard.modal.badge-legend :groups="$badgeLegendGroups" />
```

#### 1.1.1 Opt-in Behavior Composition (`maximizable`)
Share a stateful Alpine/Livewire behavior across multiple `ui.forms.*` primitives without duplicating it per-component. Two small partials — `ui.forms.maximize-trigger` and `ui.forms.maximize-overlay` — are included by any host that declares the shared `x-data` scope. The host (`input`, `textarea`, `search`) takes a `maximizable` prop (default `false`); only when true, wraps its root with `x-data="{ value: @entangle($attributes->wire('model')->value() ?? '').live, fullscreen: false }"` plus `wire:ignore.self` — deriving the entangled property from the `wire:model` attribute already on the tag (same technique `select.blade.php` uses), so no separate `model` string prop is needed. Apply only to genuinely long-form free text (bio, descriptions, decision notes) — never short structured fields.

```blade
<x-ui.forms.textarea label="شرح پیشنهاد" name="form.descriptionSelf" wire:model="form.descriptionSelf" :rows="5" :maximizable="true"/>
```

#### 1.1.2 Image gallery maximize (teleported fullscreen viewer)
Click any image in a media grid to open it fullscreen with prev/next navigation. Multi-image grid+prev/next lives in `livewire/dashboard/tab/feeds/media.blade.php`; single-image fullscreen variants (`tab/posts/details.blade.php`, `tab/reports/cards.blade.php`) use a simpler `imageViewer` bool with no prev/next.

- Per-grid Alpine scope `x-data="{ open: false, index: 0 }"`; each cell `@click="index = N; open = true"` with `cursor-zoom-in`. Videos stay inline (`<video controls>`) and are **excluded** from the viewer.
- Viewer is `<template x-teleport="body">` → `fixed inset-0 z-[99998] bg-[var(--md-sys-color-primary)]/90`, so never clipped by ancestor `overflow-hidden`. Close via `@click.self` backdrop, close button, or `@keydown.escape.window`.
- `FeedPresenter::mediaGrid()` splits `$items` (all, for the grid) and `$images` (non-videos, for the viewer). Walk `$items` with an image-only counter so each click target equals its position in `$images`; gate each cell with the global `isVideo($url)` helper on the loop variable — videos get `<video controls>` and are skipped from `$images`.
- Grid sizing: `grid-cols-2` alone leaves rows auto-sized to intrinsic height, so a 4-image set's second row overflows `overflow-hidden`. Fix with explicit `grid-rows-{{ $rows }}` plus definite height (`h-[400px]` multi-row, `h-[320px]` single) so rows share space equally (`minmax(0,1fr)`).
- Multi-image prev/next wrap modularly: `index = (index - 1 + total) % total` / `index = (index + 1) % total`; render arrows only when `count($images) > 1`. Per-include `x-data` means each card has its own independent viewer. Reuse `animate-lightbox-in`.

### 1.2 Domain Components (`components.{admin,auth,dashboard}.*`)
Controller-less Blade compositions for specific business contexts. May interact with services, repositories, or Presenters; not for cross-domain reuse. Flat structure; nest only at 5+ related partials.

---

## 2. Livewire Components (`resources/views/livewire/`)

Mirrors `App\Livewire\` namespace: `livewire/{admin,auth,dashboard}/`.

| Aspect | Convention |
|--------|------------|
| **Primary view** | `livewire/{domain}/{Module}.blade.php` |
| **Controller** | `App\Livewire\{Domain}\{Module}.php` |
| **Partials** | Co-located in `livewire/{domain}/` |

Extract a partial only when the component exceeds 150 lines, a section needs conditional inclusion, or logic is reused within the same domain. Anti-pattern: strict separation under 100 lines creates navigation friction.

---

## 3. Error Views (`resources/views/errors/`)

HTTP error pages (401/403/404/405/419/429/500/503) extend `errors.layout` — a dedicated standalone HTML shell with its own theme bootstrap + Vite CSS, **not** `layouts.app`. Using a separate shell avoids recursive failures in shared chrome (e.g., database-dependent navigation) when the error itself stems from that chrome. Domain error views outside the HTTP set (e.g. `document-not-found.blade.php`) extend `layouts.app` with `@section('minimal_layout', true)` to suppress header/sidebar/footer.

---

## 4. Layouts (`resources/views/layouts/`)

Centralized orchestration for HTML document structure, asset pipeline inclusion (Vite, Livewire), global component injection, and render slot flexibility.

```blade
@isset($slot)
    {{ $slot }}
@else
    @yield('content')
@endisset
```

The `@else` branch is a legacy yield for backward compatibility — kept so non-component views still resolve `@section('content')`.

Layout auto-includes cross-cutting concerns: confirmation modals, toast notifications, background elements, and header/footer (unless `minimal_layout` is set).

---

## 5. Decision Matrix

| Scenario | Location | Example |
|----------|----------|---------|
| MD3 button with variants | `components.ui.buttons.form` (`variant` prop) | `<x-ui.buttons.form variant="primary">` |
| Badge/nudge legend with catalog backing | `components.dashboard.modal.badge-legend` | `<x-dashboard.modal.badge-legend>` |
| Stateful form with validation | `livewire.auth.login` + `App\Livewire\Auth\Login` | `<livewire:auth.login />` |
| HTTP 500 error page | `errors.500` | Extends `errors.layout` (standalone shell) |
| Calculator floating widget | `components.dashboard.tools.calculator` | Included via `components.dashboard.global` in `layouts.app` |

---

## 6. Naming Conventions

| Element | Pattern | Rationale |
|---------|---------|-----------|
| UI components | `kebab-case` | HTML attribute compatibility |
| Domain folders | `kebab-case` | PSR-4 alignment |
| Livewire modules | `PascalCase` (class) / `kebab-case` (view) | Laravel convention |
| Partials | descriptive, no `partial-` prefix | Context implied by location |

---

## 7. Anti-Patterns to Avoid

1. Deep nesting: max 3 levels (`domain/sub-component/file`)
2. `.index` suffix: use `footer.blade.php`, not `footer/index.blade.php`
3. `partials/` subfolders: co-locate; use folders only for 5+ related files
4. Business logic in UI: move to domain or Livewire controller
5. Generic naming: `components.partials.card` → `components.ui.card`

---

## 8. Shared component patterns

### 8.1 Shared empty-state (`<x-ui.empty>`)
One design-system component for **all** user-panel empty-states. Props: `icon` (required), `title` (required), `description?`, `variant?` (only `'welcome'` is special-cased; `'list'`/`'filtered'`/`'search'`/`'default'` all fall through to the default list style), `fill?` (bool — `h-full w-full` instead of default `h-64`), `watermark?`, `animate?`. With no extra props it renders byte-equivalent to the original inline empty-states, so adopting is a pure substitution. Keep genuinely special cases inline (gallery filtered-overlay, contact welcome panel). Profile onboarding/credentials wrap it in a dashed-border card. Reach for `<x-ui.empty>` first; fall back to inline only when the empty-state has structure the component cannot express.

### 8.2 Solid-button dropdown for filters
The gallery month filter is a **solid button opening a dropdown on click** — not chips, not native `<select>`. Wrapper: `x-data="{ open: false }" @click.outside="open = false"`; button `@click="open = !open"` with `:class="open || month ? 'primary-bg' : 'surface-container-highest'"`; popup `x-show="open" x-transition x-cloak` listing options that set the Alpine `month` var and `open = false`. Matches the reports card/list solid segmented-toggle visual language. Use this shape for any single-dimension filter picking one value from a short list.

### 8.3 Pre-paint theme bootstrap (`public/js/mode-manager.js`)
Dark mode is class-based (`@variant dark (&:where(.dark, .dark *))` in `app.css`, palette vars in `core/theme.css` keyed on `:root.dark` / `[data-theme='x'].dark`), so the page is dark **only when `.dark` is on `<html>`**. The toggle in `resources/js/core/theme-manager.js` loads as a deferred `type="module"` via `@vite` — executing *after* first paint, causing a light→dark flash (FOUC) on cold load, hard refresh, and `wire:navigate` transitions. Fix: `public/js/mode-manager.js` — a standalone IIFE that reads `localStorage('user-mode')` (fallback `matchMedia('(prefers-color-scheme: dark)')`) and `localStorage('user-theme')` (fallback `'default'`) and sets `.dark` + `data-theme` on `documentElement` synchronously. Included in `layouts/app.blade.php` `<head>` as a **plain classic `<script src>` before `@vite`** (`<script src="{{ asset('js/mode-manager.js') }}"></script>`) — never via `@vite` (would emit `type="module"` deferred → re-introduce flash). Filament admin gets the same tag at `PanelsRenderHook::HEAD_START` in `FilamentServiceProvider::registerThemeBootstrap()`. Auth layout is intentionally not covered. The file lives directly under `public/js/` (NOT `public/build/`, NOT a `viteStaticCopy` target, NOT a Vite entry) so Laravel's web server serves it at `/js/mode-manager.js` in dev and prod with no build step, manifest, or hashing. Classic head scripts re-execute on `wire:navigate`, so SPA nav is covered. `ThemeManager` stays unchanged — re-applies idempotently, owns Alpine-store sync and cross-tab `storage` listeners. Rule: any client-resolved, paint-affecting state must be asserted by a render-blocking classic script in `<head>` before the deferred `@vite` bundle; ship as a static `public/` asset (not through Vite); reuse existing storage keys, never add a parallel mechanism.

### 8.4 In-page tools survive navigation (`@persist` + new-tab module links)
Dashboard tools (radio/calculator/stopwatch in `components/dashboard/tools/*`, Alpine `x-data` whose minimized pills `x-teleport="#tool-dock"`) live in the **layout** via `components/dashboard/global.blade.php` (included by `layouts/app.blade.php`), outside the page slot — so in-place tab swaps (`switch-tab`) never touch them. Rule: **a module either opens in-page (SPA, tools preserved) or in a new tab (full reload, originating tab's tools untouched) — never a same-tab full reload that kills the tools.** (1) The **menu modal** (`components/dashboard/modal/menu.blade.php`) uses plain `<a href>`, so route items get `:target="item.disabled || item.href === '-' ? '_self' : '_blank'"` + `rel="noopener"` — in-page tools (`href:'-'`, which `handleItemClick` `preventDefault`s + `$dispatch`es) stay `_self`; every route item opens a new tab, leaving the dashboard tab's radio playing. Matches the left-sidebar (`navbars/left.blade.php`) and account-dropdown `target="_blank"` convention. (2) **SPA navigation** (command palette `navigate:true`, `wire:navigate` chips, profile back-to-dashboard link) swaps the body and would re-init tools — so the 3 tool includes + `#tool-dock` are wrapped in `@persist('dashboard-tools') ... @endpersist` inside `global.blade.php`; Livewire v4 caches that subtree across `wire:navigate`, preserving Alpine state, window listeners, and the detached `new Audio()` (radio audio keeps playing). `#tool-dock` MUST stay inside the same `@persist` region as the tools. Toast/occasion stay outside `@persist`. Do NOT add an empty `x-persist="dashboard-tools"` stub on `minimal_layout` pages — it would cache an empty node on navigate-away and overwrite the real tools on the next normal page; the current "no stub on minimal pages" state is correct. `menu.js` needs no change.

### 8.5 Badge/nudge notification legend (`<x-dashboard.modal.badge-legend>` + `BadgeLegendCatalog`)
A reusable cross-module modal explaining `App\Services\Menu`'s two notification signals (see `app/Services/Menu/statePattern.md`): the "dot" (live status, Signal 1) and the "bell" (one-time dismissible alert, Signal 2). Wraps `<x-ui.modals.dialog>` — does not fork it. The bell's always-shown intro chip also explains the optional click-to-redirect `url()` capability — a bell with a "مشاهده" action jumps straight to the relevant record; aggregated bell cards (5+ folded into one) never have this button. One shared sentence in `badge-legend.blade.php` covers every consumer.

**Single source of truth — `App\Services\Menu\BadgeLegendCatalog`.** Every badge's row data (`tone`, `icon`, `label`, `lights`/`clears` copy, `surface`) is defined **once**, keyed by the indicator's `getKey()`. `BadgeLegendCatalog::get($key)` returns one row; `BadgeLegendCatalog::grouped()` returns all 14 rows bucketed into 5 thematic tabs (`groups()`). Editing a row updates it everywhere at once — replacing an earlier draft that duplicated each row literally in every Blade file (a real content-drift bug: Ths's two copies disagreed on what clears the badge).

**Props on `<x-dashboard.modal.badge-legend>`:** `name` (required, unique dispatch name), `title` (optional, defaults to "راهنمای نشانگرهای اعلان"), `items` (flat array — normally `[BadgeLegendCatalog::get('some-key')]` — single "در این صفحه" heading, for a page with one relevant badge), `groups` (array of `{id, label, icon, items}` — normally `BadgeLegendCatalog::grouped()` verbatim — segmented pill tab-switcher, one tab per theme). Row rendering is factored into `<x-dashboard.modal.badge-legend-row :item="$item">`, reused by both branches.

**Row shape:** `tone` picks a semantic Tool token (`userStylesPattern.md` §3.2) — `sapphire` (view-based, dot clears on look), `gold` (action-based, clears on completing the task), `amethyst` (global/org-wide, never view-gated), `sage` (nudge-only with no matching dot/badge — `channels-controller`, `gallery-controller`, `reports-controller`; bell only, cleared by dismissing; `lights`/`clears` copy says so instead of describing dot behavior that doesn't exist). `badge-legend-row.blade.php` resolves `tone` through a static `match()` returning a complete literal Tailwind class string per case — **never** interpolate into `bg-[var(--tool-{{ $tone }}-bg)]`; Tailwind's JIT scanner needs the full class text literally in source, and a runtime-assembled value silently fails unless that exact string appears elsewhere by coincidence (a real bug caught in review — the `amethyst` variant rendered with no background/icon color). `lights`/`clears` are plain-language triggers sourced from the corresponding `Indicators\*` class's `getTitle()`/`getBody()` copy or its underlying model condition. Optional `surface` names WHERE the dot actually renders (a hamburger-menu item id from `resources/js/components/alpine/data/menu.js`, or a `Tabs.php` `'badge'` field) — existing wiring, never a new indicator location.

**Height boundary — capped on the WHOLE content block, not just the item list, and net of the dialog's own chrome.** The entire slot content (dot card + bell card + tab strip + tab-panel or `items` list) is wrapped in one outer `max-h-[calc(50vh-112px)] overflow-y-auto` (`badge-legend.blade.php`'s root div). **Real bug, caught 2026-08-13**: the cap used to sit only on the per-tab item list (`max-h-[45vh] overflow-y-auto` on the inner tab-panel/`items` div) — but the always-visible dot card, bell card, and tab-strip pills sit ABOVE that inner div, uncapped, and `<x-ui.modals.dialog>` itself has no height limit on the modal box. Total rendered height was chrome (~400px: dialog title bar + dot card + bell card + tab strip) **plus** up to 45vh of items — comfortably exceeding half the viewport, worst noticeable on `Profile`'s full-catalog `groups` view (the "اطلاعیه و پیام" tab alone holds 2 items). First fix moved the cap to the outer wrapper at a flat `50vh` — `claude-reviewer` then did the arithmetic: `<x-ui.modals.dialog>`'s own title bar (`px-6 py-4` + border, ≈64px) plus its `p-6` content padding (48px vertical) sit OUTSIDE this capped div, so a flat `50vh` on the content alone still pushed the total modal box to ≈50vh+112px — on a typical 800-900px viewport, ~62-65% of viewport height, not literally half. Corrected to `calc(50vh-112px)` so the capped content plus that ~112px of dialog chrome nets out to a true ≈50vh total. Tab strip uses `flex flex-wrap` (not `flex-1` per button) so 5 tabs wrap on narrow screens.

**Trigger convention:** same `<x-ui.title>` `actions`-slot help-icon pattern as `taskboard.blade.php`/`ths.blade.php`'s workflow-legend button — `notifications` icon (distinct from the `help` icon used for a module's own workflow/role legend when both exist), `@click="$dispatch('open-modal', { name: '...' })"`.

**DOM order when both buttons exist — notification button FIRST, legend/help button SECOND.** `<x-ui.title>`'s `actions` slot (`title.blade.php:11`) is a plain `flex items-center gap-2` with no `row-reverse`, inheriting `dir="rtl"` from the module root. Under standard CSS Flexbox RTL behavior (no override needed — spec behavior), the first DOM child renders at the right edge and the last DOM child renders at the left edge. So source order notification-first/legend-second renders **left-to-right as legend, then notification**, which is the required visual order. Do NOT "fix" this by swapping the DOM order to put the notification button first visually; that would invert it. Consistent in `dms.blade.php`, `energy.blade.php`, `ths.blade.php`, `taskboard.blade.php`, `channel.blade.php`, `contact.blade.php`. **`profile.blade.php` was found backwards (real bug, fixed 2026-08-13)** — it had `profile-legend` (help) first and `profile-badge-legend` (notifications) second, the exact reverse of every other module, rendering notification-left/legend-right on screen. Swapped to match.

**Current usages:** `taskboard.blade.php`, `ths.blade.php`, `dms.blade.php`, `energy.blade.php`, `ads.blade.php`, `suggestion.blade.php`, `tab/posts.blade.php`, `tab/feeds.blade.php`, `tab/gallery.blade.php`, `tab/reports.blade.php` each pass a single `items` entry (e.g. `[BadgeLegendCatalog::get('tasks-controller')]`); `tab/calendar.blade.php` passes two (`shared-events` + `special-days` — its badge covers both indicators). `channel.blade.php` and `contact.blade.php` share `messaging/legends.blade.php`, which passes a `groups` array with two entries (`contacts-controller` + `channels-controller`) under one combined `messaging-badge-legend` modal. The **master catalog** (all 14 entries) lives in `resources/views/livewire/dashboard/profile.blade.php` (`App\Livewire\Dashboard\Profile\Main`), shown on every profile sub-tab except onboarding (`$isProfileTab = $activeTab !== 'onboarding'`), passing `groups="$badgeLegendGroups"` where `Main::render()` computes `'badgeLegendGroups' => BadgeLegendCatalog::grouped()`. Not on the Status sidebar tab (no badge of its own per `Tabs.php`). Add a new usage: add the row to `BadgeLegendCatalog::all()` with the right `group`, then one `<x-dashboard.modal.badge-legend>` include + one trigger button in the module's header, pulling that row via `get()`.

### 8.6 Per-module "workflow/role legend" (`help` icon → `<x-ui.modals.dialog>` → icon-chip rows)
Explains a module's own role-dependent behavior or status meanings — distinct from §8.5 by icon (`help`, not `notifications`) and by having no `BadgeLegendCatalog` backing. Each is a one-off `legend.blade.php` partial local to its module, `@include`d inside a plain `<x-ui.modals.dialog name="...">` (no wrapper component).

**Two shapes:**
- **Role-tabs** (`x-data="{ role: 'first-role' }"`, segmented pill switcher, one `x-show` panel per role, rows keyed `visibility`/`bolt`/`notifications` for "می‌بینید"/"اقدام شما"/"اعلان") — when different users see/do different things: `taskboard/legend.blade.php` (creator/assignee), `ths/legend.blade.php` (requester/dept-head/assignee), `channel/legend.blade.php` (owner/member).
- **Flat list** (no tabs, icon-chip rows) — one perspective, several status meanings: `reservation/legend.blade.php` (the 4 `ReservationStatus` values), `tab/calendar/legend.blade.php` (private/public visibility + owner-only edit/share/delete — deliberately does NOT repeat the calendar grid's already-visible inline icon key for holiday/birthday/anniversary/shared-event dots), `dms/status-legend.blade.php` (the 3 confirmation states framed as a two-step digital signature; `dms/legend.blade.php` now holds only the still-relevant "action required" banner), `energy/legend.blade.php` (25-day survey cooldown + last-option-is-exclusive rule, re-derived from `Energy\Main::mount()`/`updatedAnswers()`).

**Tailwind pitfall — same as §8.5's `amethyst` bug.** Every icon-chip row resolves color through a `@php $chipClasses = match ($row['color']) { 'x' => 'bg-[var(--...)] text-[var(--...)]', ... };` block producing a complete literal class string per case — never interpolate into `bg-[var(--tool-{{ $color }}-bg)]`. JIT needs the exact class text literally in source; an interpolated value only "works" if that string appears elsewhere by coincidence.

**Content accuracy rule:** copy must be re-derived from the actual gating code (`@if($isOwner)` guards, model scopes, enum `getLabel()`/policy classes) each time — e.g. `reservation/legend.blade.php`'s `CancelledAdmin` row text is sourced from `CancelAction::execute()`'s `$user->hasElevatedRole()` branch, not a guess.

---
```