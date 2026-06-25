# View Layer Architecture Documentation

## Overview
This document defines the structural organization of the view layer, establishing clear boundaries between reusable UI primitives, domain-specific components, and page-level compositions.

---

## 1. Components (`resources/views/components/`)

### 1.1 Directory Structure
```
components/
├── admin/          # Administrative interface components
├── auth/           # Authentication flow components
├── dashboard/      # Dashboard-specific widgets
└── ui/             # Domain-agnostic design system
```

### 1.2 Classification Criteria

#### 1.2.1 UI Components (`components.ui.*`)
**Purpose:** Design system primitives with zero business logic dependency.

**Location Logic:**
- **Dedicated subdirectories** (e.g., `ui/buttons/`, `ui/modals/`, `ui/forms/`) — for variants requiring type differentiation
- **Root level** (`ui/`) — for singleton components without significant variation

**Determining Factors:**
| Criterion | UI Location | Domain Location |
|-----------|-------------|-----------------|
| Cross-domain utilization | Required | Prohibited |
| Business logic coupling | None | Present |
| Eloquent model dependency | None | Allowed |
| `config()` or `auth()` references | None | Allowed |

**Example:**
```php
{{-- UI: Pure presentation --}}
<x-ui.button variant="primary" size="lg">Submit</x-ui.button>

{{-- Domain: Business logic encapsulated --}}
<x-dashboard.employee.status-badge :employee="$user" />
```

#### 1.2.1.1 Opt-in Behavior Composition (`maximizable`)
**Purpose:** Share a stateful Alpine/Livewire behavior (not just markup) across multiple `ui.forms.*` primitives without duplicating it per-component.

**Pattern:**
- The behavior's markup lives in two small partials — `ui.forms.maximize-trigger` (the expand button) and `ui.forms.maximize-overlay` (the teleported fullscreen editor) — included by any host component that declares the shared `x-data` scope.
- The host component (`input`, `textarea`, `search`) takes a `maximizable` prop (default `false`, opt-in) and, only when true, wraps its root element with `x-data="{ value: @entangle($attributes->wire('model')->value() ?? '').live, fullscreen: false }"` plus `wire:ignore.self` — deriving the entangled property straight from the `wire:model` attribute already on the tag (same technique `select.blade.php` uses for its searchable dropdown), so no separate `model` string prop is needed.
- The host's own field keeps its normal `wire:model` binding; the entangled `value` is purely the bridge that lets the fullscreen overlay's `x-model="value"` stay in sync with the same Livewire property.
- Apply `maximizable` only where the field is genuinely long-form free text (bio, descriptions, decision notes) — not on short structured fields (codes, phone numbers, single selects).

**Example:**
```blade
<x-ui.forms.textarea label="شرح پیشنهاد" name="form.descriptionSelf" wire:model="form.descriptionSelf" :rows="5" :maximizable="true"/>
```

#### 1.2.1.2 Image gallery maximize (teleported fullscreen viewer)
**Purpose:** Click any image in a media grid (1, 2, 3, … images — count does not matter) to open it fullscreen, with prev/next navigation across the set. Reuse the same shape wherever a media grid appears (`posts/details.blade.php` single image; `feeds/media.blade.php` multi-image).

**Pattern:**
- Self-contained Alpine scope on the grid root: `x-data="{ open: false, index: 0 }"`. Each image cell's `@click="index = N; open = true"` opens the viewer at that image's position; `cursor-zoom-in` + an `open_in_full` badge signal it is clickable. Videos stay inline (`<video controls>`) and are **excluded** from the viewer.
- The viewer is `<template x-teleport="body">` → a `fixed inset-0 z-[99999] bg-black/90` overlay, so it is never clipped by ancestor `overflow-hidden` (unlike an in-card absolute tooltip). Close via `@click.self` on the backdrop, a close button, or `@keydown.escape.window`.
- Build two lists from the raw paths: `$items` (all, for the grid) and `$images` (non-videos only, for the viewer). Walk `$items` with an image-only counter so each image's click target equals its position in `$images`; render one `<img x-show="index === N">` per image in the viewer and `x-show` toggles the active one. Use `!isVideo($p)` (the closure param), never `$path` (undefined in that scope → keeps videos too → broken `<img>` in the viewer).
- Grid sizing: `grid-cols-2` alone leaves rows auto-sized to each image's intrinsic height, so a 4-image set's second row overflows the card's `overflow-hidden` and is clipped. Fix with explicit `grid-rows-{{ ceil($count/$cols) }}` plus a definite height (`h-[400px]` for multi-row, `h-[320px]` single) so rows share the space equally (`minmax(0,1fr)`) and every cell stays visible without scrolling.
- Multi-image prev/next use modular wraparound: `index = (index - 1 + total) % total` / `index = (index + 1) % total`; only render the arrows when `count($images) > 1`.
- Because the `x-data` is per-include, each card/post has its own independent viewer; only the clicked one opens. Reuse `animate-backdrop-in` for the entrance.

#### 1.2.2 Domain Components (`components.{admin,auth,dashboard}.*`)
**Purpose:** Controller-less Blade compositions representing specific business contexts.

**Characteristics:**
- Encapsulate domain-specific presentation logic
- May interact with services, repositories, or Presenter patterns
- Not intended for cross-domain reuse
- Flat structure preferred; nested only when component count exceeds 5+ related partials

---

## 2. Livewire Components (`resources/views/livewire/`)

### 2.1 Directory Mapping
Mirrors `App\Livewire\` namespace structure:

```
livewire/
├── admin/
├── auth/
└── dashboard/
```

### 2.2 Organizational Rules

| Aspect | Convention |
|--------|------------|
| **Primary view** | `livewire/{domain}/{Module}.blade.php` |
| **Controller** | `App\Livewire\{Domain}\{Module}.php` |
| **Partials** | Co-located in `livewire/{domain}/` directory |

### 2.3 Partial Extraction Criteria
Extract to dedicated partial only when:
- Component exceeds 150 lines
- Section requires conditional inclusion
- Logic is reused within the same domain

**Anti-pattern:** Strict separation for components under 100 lines creates navigation friction.

---

## 3. Error Views (`resources/views/errors/`)

### 3.1 Requirements
- **Template Inheritance:** Must extend `layouts.app`
- **Layout Suppression:** Implement `@section('minimal_layout', true)` to exclude:
    - Navigation headers
    - Sidebars
    - Footers

### 3.2 Rationale
Error pages require reduced chrome to prevent recursive failures in shared components (e.g., database-dependent navigation).

---

## 4. Layouts (`resources/views/layouts/`)

### 4.1 Responsibility
Centralized orchestration layer for:
- HTML document structure
- Asset pipeline inclusion (Vite, Livewire)
- Global component injection
- Render slot flexibility

### 4.2 Contract
```blade
{{-- Supports both modern component syntax --}}
@isset($slot)
    {{ $slot }}
@else
    {{-- Legacy yield for backward compatibility --}}
    @yield('content')
@endisset
```

### 4.3 Global UI Injection
Layout automatically includes cross-cutting concerns:
- Confirmation modals
- Toast notifications
- Background elements
- Header/Footer (unless `minimal_layout` section defined)

---

## 5. Decision Matrix

| Scenario | Location | Example |
|----------|----------|---------|
| MD3 button with variants | `components.ui.buttons.{variant}` | `<x-ui.buttons.primary>` |
| Employee status with business rules | `components.dashboard.employee.status` | `<x-dashboard.employee.status>` |
| Stateful form with validation | `livewire.auth.login` + `App\Livewire\Auth\Login` | `<livewire:auth.login />` |
| HTTP 500 error page | `errors.500` | Extends `layouts.app` with minimal layout |
| Calculator floating widget | `components.tools.calculator` | Included in `layouts.app` globally |

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

1. **Deep nesting:** Maximum 3 levels (`domain/sub-component/file`)
2. **`.index` suffix:** Use `footer.blade.php`, not `footer/index.blade.php`
3. **`partials/` subfolders:** Co-locate; use folders only for 5+ related files
4. **Business logic in UI:** Move to domain or Livewire controller
5. **Generic naming:** `components.partials.card` → `components.ui.card`

---

```
