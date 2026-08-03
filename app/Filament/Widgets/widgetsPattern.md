# Widgets Pattern — `app/Filament/Widgets`

Catalog and conventions for every widget in this directory. Cross-cuts with `app/Filament/filamentPattern.md` (rules 59 COW-alias-trap and 60 nullsafe-on-grouped-lookup live there).

## Naming convention (ERP-grade)

One rule, applied uniformly: **the class suffix denotes the Filament base kind.**

| Base class | Suffix | Examples |
|---|---|---|
| `ChartWidget` | `…Chart` | `HrDemographicsChart`, `OperationalChart`, `StructuralChart` |
| `TableWidget` | `…Table` | `UnmetSkillDemandTable` |
| `Widget` (custom view widget) | `…Widget` | `AccountWidget`, `ManagePreferencesWidget`, `ModuleAnalyticsWidget`, `ModulesGuideWidget` |
| `Page` (registered as a page, not a widget) | exempt | `Dashboard` |

Class name == PSR-4 filename (auto-discovery relies on this). Names are short, descriptive, and domain-prefixed where a domain grouping exists (`Hr*Chart` = HR analytics block; `Operational`/`Structural` = the two operations/org-analytics chart widgets).

**Data methods are key-driven, not free-named.** Each chart widget's Radio `module` filter has option keys (`hr_a`, `module_f`, …). The matching data method is the key camelCased + `Data`: `hr_a` → `getHrAData()`, `module_f` → `getModuleFData()`. `getData()` is a `match` on the active key dispatching to the one method per module. Stat builders in `ModuleAnalyticsWidget` follow the parallel `{domain}Data()` shape (`usersData()`, `channelsData()`, `departmentsData()`, …). Do not invent ad-hoc method names.

**Helpers.** Shared chart helpers live in the `DepartmentAxis` trait (below). Widget-local helpers stay private and are named descriptively (`positionAxis`, `positionMap`, `bucketSeries`).

## Registration

`AdminPanelProvider` uses `discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')` — every class here is auto-discovered; no manual registration needed. Two explicit entries override discovery: `->widgets([AccountWidget::class])` (force-shown) and `->pages([ManagePreferences::class, Dashboard::class])` — note that `Dashboard` is a Page and `ManagePreferences` in that array is the Livewire `App\Livewire\Admin\ManagePreferences` page, **not** the `ManagePreferencesWidget` here (same short name, different FQN). Class renames are safe as long as the new name keeps class==filename; `AccountWidget` is the only one referenced by FQN outside this folder, so think twice before touching it.

## Catalog

| Class | Sort | Kind | Modules / scope |
|---|---|---|---|
| `AccountWidget` | -3 | custom Widget | account summary, view `filament.widgets.account-widget` |
| `ManagePreferencesWidget` | -2 | custom Widget | admin app-preference toggles (FilamentPreferences trait) |
| `ModulesGuideWidget` | -1 | custom Widget | `config('modules')`-driven module guide, view `filament.widgets.guide` |
| `HrDemographicsChart` | 1 | ChartWidget | hr_a–hr_d (org-wide) |
| `HrQualificationChart` | 2 | ChartWidget | hr_e–hr_h (org-wide) |
| `HrUnitHealthChart` | 3 | ChartWidget | hr_i–hr_m (org-wide) |
| `HrEngagementChart` | 4 | ChartWidget | hr_n–hr_q (org-wide) |
| `UnmetSkillDemandTable` | 5 | TableWidget | ghost-skill demand + promote action, lazy |
| `ModuleAnalyticsWidget` | 6 | custom Widget | 24 `{domain}Data()` stat builders, tabbed, view `livewire.admin.widgets.filament-analytics` |
| `OperationalChart` | 7 | ChartWidget | module_a–module_d (self-scoped) |
| `StructuralChart` | 8 | ChartWidget | module_e–module_i (self-scoped) |

Lower `$sort` = higher on the page. Utility widgets occupy the negatives; the HR block sits at 1–4; the operations/org-analytics block at 5–8.

## Dashboard page — hero + 2-tab layout (`App\Filament\Widgets\Dashboard`)

`Dashboard::getHeading()` (the typesetter/clock bar, view `filament.widgets.dashboard`) always renders above the tabs regardless of active tab — Filament renders the page heading and `content()` schema as separate regions. `Dashboard::content()` is overridden to wrap the widget grid in a `Tabs::make()` schema with two tabs instead of the base single `Grid`: **"نمای کلی"** (everything that is not a `ChartWidget`) and **"تحلیل‌های نموداری"** (every `ChartWidget` subclass). The split is by base class (`is_subclass_of($widget, ChartWidget::class)` on `normalizeWidgetClass($widget)`), not a hardcoded class list — a new `…Chart` widget lands in the charts tab automatically, no `Dashboard.php` edit needed.

**Inactive-tab widgets are never mounted — server-driven tabs via `->livewireProperty()`, not CSS hiding.** All discovered widgets already default to `$isLazy = true` (`CanBeLazy::$isLazy` in `vendor/filament/support/src/Concerns/CanBeLazy.php`), so relying on Filament's default "client-only" `Tabs` (Alpine `x-bind:class="fi-active"` + CSS `h-0`/`invisible` toggle, both tabs' widgets built and mounted up front, non-active ones just collapsed visually) turned out to gate nothing — every widget on the page is already an unresolved `x-intersect` placeholder regardless of which tab it's in. The actual fix: `Tabs::make()->livewireProperty('activeDashboardTab')` with an **associative** `tabs([...])` array (`'overview' => Tab::make(...)`, `'charts' => Tab::make(...)` — the array key becomes the tab's key, matched against the bound property). Per `vendor/filament/schemas/resources/views/components/tabs/tab.blade.php`, when `livewireProperty` is set, a tab's `$childSchema` is rendered **only** `@elseif (strval($this->{$livewireProperty}) === strval($key))` — the other tab's `Grid::schema(fn() => $this->getWidgetsSchemaComponents(...))` closure is never invoked, so its widgets are never built into `Livewire::make(...)` components at all. Clicking a tab is `wire:click="$set('activeDashboardTab', 'charts')"` — a real Livewire round-trip that (re)mounts only the newly active tab's widgets. Verified two ways: an authenticated HTTP dump of `/admin` (default `overview` state) contains **zero** occurrences of any of the 6 chart widget class names anywhere in the response; `Livewire::test(Dashboard::class)->set('activeDashboardTab', 'charts')` then asserts the chart widgets appear and the overview widgets don't (`tests/Feature/Filament/DashboardTabsSmokeTest.php`). `$isLazy` on individual widgets still matters *within* the active tab (defers their own query behind `x-intersect` once mounted) — it's just no longer what gates tab-to-tab loading.

## Shared trait — `Concerns/DepartmentAxis`

```php
private function topDepartments(int $limit = 10): array
```

Returns `[$codes, $labels, $idx]` — the top-N departments by profile count, with a **deterministic `orderByDesc('cnt')->orderBy('department_id')` tie-breaker** and labels via `COALESCE(NULLIF(description,''), name, code)`. Since the HR-analytics extraction, the actual computation is called from `App\Services\HrAnalyticsService` (`getHrI..MData`, `getHrN..QData`), which `use`s the trait directly — neither `HrUnitHealthChart` nor `HrEngagementChart` `use`s it anymore (both delegate entirely to the Service), and `topDepartments()` coverage lives in `tests/Feature/Services/HrAnalyticsServiceTest.php`, not the widget test files. The tie-breaker matters: without it, equal-count departments return in arbitrary order and the chart bar order flickers between renders.

## Chart-widget shape (shared by all 6 ChartWidgets)

```php
use HasFiltersSchema;

protected static bool $isLazy = true;
protected bool $hasDeferredFilters = true;
protected int|string|array $columnSpan = ['sm' => 'full', 'md' => 1];
```

`filtersSchema()` returns a `Schema` with one Radio `module` filter; `filtersApplyAction`/`filtersResetAction` carry Farsi labels (`اعمال` / `بازنشانی`). `getData()` matches the active key → one `get{Key}Data()` method; `getType()`/`getOptions()` switch on the same key for chart type + axis config. Every data method is `#[Computed(seconds: 300, cache: true)]` and is called directly from `getData()` (the `#[Computed]` cache engages on property access; calling the method directly is consistent across siblings).

**The 4 `Hr*Chart` widgets' `get{Key}Data()` bodies are one-line delegations to `App\Services\HrAnalyticsService`** (`return app(HrAnalyticsService::class)->getHrXData();`) — the actual query/aggregation logic lives in that Service, not in the widget. This is deliberate: the same 17 analyses are also rendered in the user panel (`App\Livewire\Dashboard\Analytics`), so the computation had to move somewhere framework-agnostic both consumers can call. Each widget still owns everything Filament-specific (`filtersSchema()`, `getDescription()`, `getType()`, `getOptions()`, `getHeading()`, the `#[Computed]` cache boundary) — only the query logic moved. `OperationalChart`/`StructuralChart` are NOT part of this — they remain self-contained (self-scoped, not reused elsewhere). Full cross-panel pattern (including the private helpers relocated alongside the data methods, and why the Service isn't Filament-specific) is documented in `app/Livewire/livewirePattern.md` under "Shared Service across panels — HR Analytics".

## Scope: org-wide vs self-scoped

The four `Hr*Chart` widgets are **org-wide** (managerial/HR views, no per-user department filter). `OperationalChart` and `StructuralChart` are **self-scoped**: their `getData()` calls `getScopeCondition()` = `auth()->user()->profile?->department_id ?? ''` and pass it as the first arg to every `getModule{X}Data(string $departmentCode)` method, which applies `->where('departments.code', $departmentCode)` when non-empty (a manager viewing only their own unit). This is the intentional divergence — do not flatten it.

## Aggregation patterns

- **Single-query bucketing over derived columns.** Age/tenure are computed once in a derived subquery (`(SELECT … TIMESTAMPDIFF(YEAR, birthdate, NOW()) AS age …)`) then bucketed with `SUM(CASE WHEN age < 25 THEN 1 ELSE 0 END)` in one `groupBy` pass — not per-row `TIMESTAMPDIFF` repeated 5×. See `getHrCData`, `getHrFData`, `getHrGData`, `getHrKData`, `getModuleIData`.
- **Derived aggregates instead of correlated subqueries / cartesian fan-up.** `OperationalChart::getModuleAData` and `StructuralChart::getModuleEData`/`getModuleHData` use `leftJoinSub` for `energy_agg`/`tasks_agg`/`ticket_agg`/`prof_agg`/`rep_agg` — computed once, joined once. The base `departments` table drives the row set; `LIMIT 10` gets a deterministic `orderBy('departments.id')`/`orderBy('departments.code')` tie-breaker.
- **Single-pass 2D accumulation.** `HrEngagementChart::getHrNData` builds the dataset array up front (one slot per `PresenceStatus::cases()`), then accumulates per row in one pass; `HrUnitHealthChart::bucketSeries()` does the same for known-enum datasets plus one trailing rest dataset. See rules 59 (COW alias trap) and 60 (nullsafe on grouped-row lookup) in `filamentPattern.md`.
- **Null/invalid bucketing.** Enum/bucket columns never silently drop rows — null/invalid values land in a trailing `__other__` / `سایر` / `نامشخص` dataset appended only when such rows actually exist. `positionAxis($rows)` (in `HrQualificationChart`) appends `__other__` only when a null/invalid position is present; `positionMap()` is the matching `array_flip` validity map.

MySQL `TIMESTAMPDIFF`/`NOW()`/`DATE_SUB` are intentional (MySQL-only project). No user input reaches SQL — the Radio option list is fixed → `match` → a fixed method; raw `field` values surface as Chart.js text labels (no HTML, no XSS).

## `UnmetSkillDemandTable` — ghost-promote

A `TableWidget` listing `Skill::ghost()` rows ordered by `search_count` (pruned `select([...])` — only the columns the table + promote form need, not `SELECT *`). One `promote` row action opens a completion form reusing `SkillFormPresenter::category()`/`::icon()` (the same `Select`s the real `SkillResource` form uses — not duplicated `TextInput`s) then calls the model's own `activate()` inside `DB::transaction` with `Skill::lockForUpdate()->find($record->id)` (race-guard). `canView()` delegates to `SkillResource::canViewAny()`; `promoteAction()` is `visible`/`abort_unless`-gated on `SkillResource::canCreate()` (defense in depth). See `filamentPattern.md` "Approval-queue resource pattern" for why this must be a `TableWidget` (not a hand-rolled `@foreach` action loop).

## `ModuleAnalyticsWidget` — tabbed stats

A custom `Widget` (not `StatsOverviewWidget`) rendering `livewire.admin.widgets.filament-analytics`. Holds `public string $activeTab` (Livewire tab state) and ~24 `#[Computed(seconds: 300, cache: true)]` `{domain}Data()` builders returning arrays of `Filament\Widgets\StatsOverviewWidget\Stat`. `departmentsData()` is one `Department::withCount('users')->get()` + collection filter, not two queries. Lazy-loaded.

## Test convention — `tests/Feature/Filament/Widgets/`

One file per widget (`<Widget>Test.php`), mirroring the one-file-per-target rule in `tests/Feature/coreTestPattern.md`. The widget logic is exercised by **direct reflection, not Livewire mounting**: `(new ReflectionClass($cls))->newInstanceWithoutConstructor()` + `ReflectionMethod::setAccessible(true)` to call each `get{Key}Data()` / `{domain}Data()` / private helper (`topDepartments`, `bucketSeries`, `positionAxis`, `positionMap`, `getModuleIData`) directly. The `#[Computed]` cache does not intercept a raw `invoke()`, so the method body runs fresh each call. `getData()` dispatch is tested by setting `$widget->filters` (public `HasFiltersSchema::$filters`) and calling the protected `getData()` via reflection, then asserting it equals the direct data-method call on the same instance. Skeleton is the verbatim `useMysql()` + `beginTransaction`/`rollBack` from `tests/Feature/Filament/WidgetRenderCheckTest.php`, plus `Cache::flush()` in setUp AND tearDown; `ModuleAnalyticsWidgetTest` additionally calls `Once::flush()` because `departmentsData()` memoizes via `once(Cache::remember(...))`.

**`Hr*Chart`'s query logic is now covered twice, deliberately.** `tests/Feature/Services/HrAnalyticsServiceTest.php` calls `HrAnalyticsService` directly (no reflection needed — it's a plain class) and is the primary, framework-independent coverage for all 17 data methods + the 3 relocated private helpers. The 4 `Hr*ChartTest.php` files keep only the Filament-specific assertions (`getData()` dispatch, `getOptions()`/`getType()` per-module branching) — the data-method-body tests that used to live there moved to the Service test alongside the code they exercise, not duplicated in both places.

Conventions specific to this folder:
- **Reflection helper must not be named `call`** — it fatally collides with `Illuminate\Foundation\Testing\TestCase::call()` (public HTTP helper). Use `invoke` / `invokeMethod`.
- **Self-scoped `OperationalChart`/`StructuralChart`** `getModule{X}Data(string $departmentCode)` methods are called with `''` (org-wide) and with a real dept code; `getData()` dispatch tests `actingAs` a user whose profile points at the dept so `getScopeCondition()` resolves.
- **Top-10 axis robustness:** `HrUnitHealthChart`/`HrEngagementChart` tests scale the seeded dept's profile count past the dev-DB top-10 threshold (`maxDeptProfileCount()+3` or `topTenThreshold()+3`) and assert at the seeded dept's own index, so pre-existing dev-DB rows don't make counts non-deterministic.
- **Enum-constrained columns** (`profiles.gender`/`employment_status`/`degree` are MySQL `enum NOT NULL`) can't be seeded with invalid values to exercise the rest/`سایر`/`نامشخص` branch — cover those branches by reflecting on the private bucket helper with synthetic rows (coreTestPattern §8 cat. 3 skip otherwise), and `markTestSkipped` with a one-line note when the branch is genuinely unreachable at the DB level.