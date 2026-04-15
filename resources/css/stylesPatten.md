```markdown
# Stylesheet Architecture Documentation

## Overview
This document defines the modular CSS architecture, establishing separation between design tokens, motion design, and domain-specific implementations.

---

## 1. Asset Pipeline Structure

```
resources/css/
├── theme.css           # Design tokens & CSS custom properties
├── animations.css      # Global motion design system
├── dashboard.css       # Domain: Dashboard-specific styles
├── admin.css           # Domain: Administrative interface
├── auth.css            # Domain: Authentication flows
└── app.css             # Vite entry point & orchestration layer
```

---

## 2. Core Stylesheets

### 2.1 Theme Tokens (`theme.css`)

**Purpose:** Centralized design token registry exposing variables to both CSS and JavaScript runtimes.

**Contents:**
- Color system (Material Design 3 dynamic color roles)
- Typography scale
- Elevation/shadow values
- Spacing increments
- Breakpoint definitions

**Contract:**
```css
:root {
  /* Surface roles — accessible via CSS var() and JS getComputedStyle() */
  --md-sys-color-primary: #6750A4;
  --md-sys-color-on-primary: #FFFFFF;
  --md-sys-color-primary-container: #EADDFF;
  /* ... additional MD3 color roles */
  
  /* Semantic spacing */
  --space-xs: 0.25rem;
  --space-sm: 0.5rem;
  /* ... */
}
```

**Consumption:**
```css
/* CSS */
.my-component {
  background: var(--md-sys-color-surface);
}

// JavaScript
const primary = getComputedStyle(document.documentElement)
  .getPropertyValue('--md-sys-color-primary');
```

---

### 2.2 Animation System (`animations.css`)

**Purpose:** Reusable motion primitives independent of domain context.

**Scope:**
- Keyframe definitions
- Transition timing functions
- Duration tokens
- Easing curves (MD3 motion standards)

**Structure:**
```css
/* Entrance */
@keyframes slide-left { ... }
@keyframes fade-up { ... }

/* Exit */
@keyframes fade-out { ... }

/* State */
@keyframes pulse { ... }
@keyframes spin { ... }

/* Utility classes */
.animate-slide-left { animation: slide-left 400ms ease-out; }
.animate-fade-up { animation: fade-up 300ms cubic-bezier(0.4, 0, 0.2, 1); }
```

**Rule:** No domain-specific selectors. All animations are opt-in via utility classes or explicit `animation` declarations.

---

### 2.3 Domain Stylesheets (`{domain}.css`)

**Purpose:** Scoped implementation details for specific business modules.

**Pattern:**
| File | Domain | Scope |
|------|--------|-------|
| `dashboard.css` | Employee/Analytics dashboard | Widgets, charts, data tables |
| `admin.css` | System administration | Permission matrices, bulk operations |
| `auth.css` | Authentication flows | Glass panels, branded layouts |

**Contract:**
- May reference `theme.css` variables
- May extend `animations.css` utilities
- No cross-domain leakage (e.g., `dashboard.css` must not style `.admin-sidebar`)
- Component-scoped selectors preferred (BEM or utility-first)

---

## 3. Orchestration Layer (`app.css`)

**Purpose:** Vite entry point assembling all dependencies in dependency-correct order.

**Loading Sequence:**
```css
/* 1. External dependencies */
/*@import 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200';*/
@import '@fancyapps/ui/dist/fancybox/fancybox.css';
@import 'material-symbols/rounded.css';
@import 'tailwindcss';
@import './core/theme.css';
@import './core/animation.css';
@import './core/dashboard.css';


@variant dark (&:where(.dark, .dark *));
@variant rtl (&:where([dir="rtl"], [dir="rtl"] *));

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

```

**Rationale:** Cascading specificity requires tokens before consumers, and domain styles must override base layers when necessary.

---

## 4. Inclusion Strategy

### 4.1 Vite Configuration
```javascript
// vite.config.js
export default defineConfig({
  css: {
    postcss: './postcss.config.js',
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          // Domain CSS may be split if > 50KB
          'styles-dashboard': ['./resources/css/dashboard.css'],
        }
      }
    }
  }
});
```

### 4.2 Blade Integration
```blade
{{-- layouts/app.blade.php --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

**No conditional loading:** All CSS is bundled at build time; Tailwind purges unused selectors.

---

## 5. Decision Matrix

| Scenario | Location | Rationale |
|----------|----------|-----------|
| MD3 color role definition | `theme.css` | Cross-cutting token |
| Button hover micro-interaction | `animations.css` | Reusable motion |
| Dashboard widget grid layout | `dashboard.css` | Domain-specific structure |
| Auth card glass morphism | `auth.css` | Single-use visual treatment |
| Typography scale token | `theme.css` | Design system foundation |

---

## 6. Anti-Patterns

| Violation | Correction |
|-----------|------------|
| `color: #6750A4` hardcoded | `color: var(--md-sys-color-primary)` |
| `@keyframes` in `dashboard.css` | Migrate to `animations.css` |
| `.btn { }` global reset in domain file | Move to `theme.css` or use Tailwind |
| `dashboard.css` styling `.admin-header` | Scope to `.dashboard-header` only |
| Dynamic `@import` in Blade | Static Vite bundling only |

---



---

*Document Version: 1.0*  
*Last Updated: 2026-04-15*
```
