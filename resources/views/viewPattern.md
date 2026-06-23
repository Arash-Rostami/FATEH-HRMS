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
