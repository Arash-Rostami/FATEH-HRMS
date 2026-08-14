# Filament 5 Module Structure Guide

Preferred module structure for a Filament PHP 5 resource — each concern isolated, reusable, and consistent with the Livewire module pattern used elsewhere.

> Before implementing, study the latest Filament PHP 5 docs: https://filamentphp.com/docs/5.x/resources/overview

Filament's resource model centers on a main resource class plus dedicated `Pages`; tables hold columns/filters/groups/actions; forms and infolists are schema-driven UI; relation managers manage related records inside a resource.

---

## Core idea

Each module is organized around a main `Resource` class that acts as the entry point — it only composes the module and defines page registration, query behavior, and high-level layout. The resource does **not** contain inline form/table/infolist definitions; it calls static methods from dedicated schema classes (presenters).

---

## Recommended structure

```text
App/
└── Filament/
    └── Resources/
        └── UserResource/
            ├── UserResource.php
            ├── Pages/
            │   ├── ListUsers.php
            │   ├── CreateUser.php
            │   ├── EditUser.php
            │   └── ViewUser.php
            ├── Schemas/
            │   ├── UserFormPresenter.php
            │   ├── UserTablePresenter.php
            │   ├── UserInfolistPresenter.php
            │   └── Grouping/
            │       └── SomeComplexGroup.php
            ├── Actions/
            │   └── StoreUserAction.php
            ├── Exports/
            │   └── UserExporter.php
            ├── Enums/
            │   └── Status.php
            └── RelationManagers/
                └── RolesRelationManager.php

App/
└── Traits/
    ├── AuthValidationRules.php
    ├── AuthorizesByPermission.php
    ├── FilamentActions.php
    ├── FilamentAdminGuide.php
    ├── FilamentDateHandler.php
    ├── FilamentEditHeading.php
    ├── FilamentFilters.php
    ├── FilamentFormDivider.php
    ├── FilamentHeaderActions.php
    ├── FilamentPageBehavior.php
    ├── FilamentPreferences.php
    ├── FocusOnRecord.php
    └── InteractsWithNotifications.php
```

### Key structural rules

- **Filters are merged into `UserTablePresenter.php`** — no separate Filters folder.
- **Grouping logic with non-trivial queries lives in `Schemas/Grouping/`** as a dedicated class.
- **Forms, Tables, and Infolists live under `Schemas/`**.
- **Names are intentionally module-qualified**: `UserFormPresenter`, `UserTablePresenter`, `UserInfolistPresenter`.
- **Pages are flexible**: a resource may have `Manage`, `Create`, `Edit`, `List`, and `View` pages.
- **Actions are optional** and used only when a module needs reusable write operations.
- **No Validators folder**: *simple* validation rules and messages stay inside each field method as fluent chain calls (`->required()`, `->maxLength()`, `->email()`, `->unique()` on a plain column, etc.). *Complex* validation — cross-field checks, DB-backed uniqueness beyond a plain column, business-rule thresholds — is extracted to `App\Rules\*` (a top-level, project-wide folder, not Filament-specific). See "Extracting validation logic to `App\Rules`" below for the criteria and the two established shapes.
- **Shared cross-cutting behavior lives in Traits** (the core Filament-related `App/Traits/` are listed in the tree above; the folder also holds non-Filament helpers like `CleansAttachedFiles`/`StoresAttachedFiles` (Rules 67/68 below) and `ChatComposer` (documented in contactPattern.md/channelPattern.md)): `FilamentActions`, `FilamentFilters`, `AuthorizesByPermission`, `FilamentHeaderActions`, `FilamentPageBehavior`, `FilamentEditHeading` (Edit pages only), and `FilamentAdminGuide` (applied to all 28 resources — the `moduleGuide` modal; full spec in §3.4). The remaining traits (`AuthValidationRules`, `FilamentDateHandler`, `FilamentFormDivider`, `FilamentPreferences`, `FocusOnRecord`, `InteractsWithNotifications`) are narrower helpers documented in their own sections below.

---

## Responsibility of each part

### 1) Main Resource

The main resource is the module orchestrator.

It should:

- define the model, navigation icon, navigation sort, navigation group
- define localized model labels (`getModelLabel`, `getPluralModelLabel`)
- define high-level schema composition: tabs, sections, accordions — structural layout only, not field logic
- define query behavior (`getEloquentQuery`) with all eager-loaded relationships
- define global search attributes, result title, details, URL, and actions
- define page routes
- delegate form/table/infolist logic entirely to schema classes
- use `FilamentActions` trait for centralized action methods

> All relationships MUST be eager-loaded inside `getEloquentQuery` for performance. When using `withCount`, add it here too.

Example:

```php
class DepartmentResource extends Resource
{
    use FilamentActions;

    protected static ?string $model = Department::class;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('resources/department/strings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/department/strings.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources/department/strings.nav_group');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'name', 'description'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('resources/department/strings.fields.code') => $record->code,
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->icon('heroicon-m-pencil')
                ->url(static::getUrl('edit', ['record' => $record])),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('users');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/department/strings.form.section_main'))
                ->icon('heroicon-o-building-office-2')
                ->description(__('resources/department/strings.form.section_description'))
                ->schema([
                    DepartmentFormPresenter::code(),
                    DepartmentFormPresenter::name(),
                    DepartmentFormPresenter::description(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                DepartmentTablePresenter::id(),
                DepartmentTablePresenter::code(),
                DepartmentTablePresenter::name(),
                DepartmentTablePresenter::description(),
                DepartmentTablePresenter::usersCount(),
                DepartmentTablePresenter::createdAt(),
            ])
            ->groups([DepartmentTablePresenter::usersCountGroup()])
            ->filters([
                DepartmentTablePresenter::hasUsersFilter(),
                DepartmentTablePresenter::createdAtFilter(),
            ])
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells)
            ->groupedBulkActions(self::bulkActions(DepartmentExporter::class))
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('name');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/department/strings.infolist.section_main'))
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    DepartmentInfolistPresenter::code(),
                    DepartmentInfolistPresenter::name(),
                    DepartmentInfolistPresenter::usersCount(),
                    DepartmentInfolistPresenter::description(),
                    DepartmentInfolistPresenter::createdAt(),
                    DepartmentInfolistPresenter::updatedAt(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public static function getRelations(): array
    {
        return [UsersRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }
}
```

When the resource has many fields, use tabs in form and infolist to group logically connected data:

```php
public static function form(Schema $schema): Schema
{
    return $schema->components([
        Tabs::make()
            ->tabs([
                Tab::make(__('resources/profile/strings.form.section_identity_header'))
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Section::make(__('resources/profile/strings.form.section_identity'))
                            ->schema([
                                ProfileFormPresenter::personnelId(),
                                ProfileFormPresenter::gender(),
                                // ...
                            ])
                            ->columns(2)
                            ->columnSpan(1),

                        Section::make(__('resources/profile/strings.form.section_contact'))
                            ->icon('heroicon-o-phone')
                            ->schema([
                                ProfileFormPresenter::cellphone(),
                                // ...
                            ])
                            ->columns(2)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Tab::make(__('resources/profile/strings.form.section_media'))
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        ProfileFormPresenter::image(),
                        ProfileFormPresenter::attachments(),
                    ])
                    ->columns(2),
            ])
            ->columnSpanFull()
            ->persistTabInQueryString(),
    ]);
}
```

---

### 2) Schema classes (Dedicated Classes, NOT traits)

The `Schemas/` folder contains reusable UI definitions for the resource.

These classes are the Filament equivalent of the Livewire presentation layer: pure UI composition, no business writes.

#### 2.1 Form schema

One method per input component. Validation and messages live inside the method.

```php
class UserFormPresenter
{
    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/user/strings.form.name'))
            ->required()
            ->maxLength(255);
    }
}
```

##### Reactive / mutually-exclusive fields

When two fields are mutually exclusive (only one should hold a value at a time — e.g. `GalleryFormPresenter` `department_id` single vs `departments` multi), wire both as `->live()` and cross-clear with `->afterStateUpdated(fn (Set $set) => $set('otherField', null))`. Disable the subordinate field with `->disabled(fn (Get $get) => filled($get('otherField')))`.

**Import gotcha:** in Filament v5 the closure `Get`/`Set` come from `Filament\Schemas\Components\Utilities\Get` / `Filament\Schemas\Components\Utilities\Set` — **not** `Filament\Forms\Get` (that class no longer matches what Filament injects, and the wrong import throws `Argument #1 ($get) must be of type Filament\Forms\Get, Filament\Schemas\Components\Utilities\Get given`). This is the import every `*FormPresenter` in this repo uses.

In Filament v5 a **disabled field is not dehydrated by default** (see `ContactFormPresenter` `->disabled()->dehydrated(false)`). To persist the cleared (`null`) value of a disabled field instead of silently keeping the old DB value, add `->dehydrated(true)` (see `FAQFormPresenter`). This is mandatory whenever the disabled state must produce a saved `null`.

```php
Select::make('department_id')
    ->live()
    ->disabled(fn (Get $get) => filled($get('departments')))
    ->dehydrated(true)
    ->afterStateUpdated(fn (Set $set) => $set('departments', null));
```

##### Toggle-driven mutual exclusion (Permission `is_super_admin`)

When one **boolean toggle** decides which of two *sections* is authoritative (not a field disabled by another field), the form-side cross-clear goes on the toggle itself, branching on the new state: `PermissionFormPresenter::isSuperAdmin()` uses `->afterStateUpdated(function (Set $set, ?bool $state) { $set($state ? 'abilities' : 'excluded_modules', null); })`, and each section is `->visible(fn (Get $get) => (bool)$get('is_super_admin'))` / `!$get(...)`. The same presenter is reused by both `PermissionResource::form()` and the `UserResource` `PermissionsRelationManager::form()`, so the one edit covers both.

Because `Permission::can()` ignores `abilities` while `is_super_admin` is true and ignores `excluded_modules` while it is false, a row carrying a value on the dead side is **misleading** (reads as "granted" but is never consulted). The form clear only fires on toggle interaction, so the invariant is **also enforced at the data layer** in `Permission::booted()` via a `static::saving` hook that nulls the inactive side — this guarantees a clean row regardless of which form path wrote it and cleans up any stale row on its next save. Prefer this two-layer (UX + model) approach whenever the dead-side value would otherwise be misread as a grant.

> **Full permission workflow** (role-first model, developer bypass sites, the two admin tiers, module vs action granularity, validation, caching) is documented in `app/Providers/Filament/adminPanelPermissionLogicPattern.md` — read it before changing anything in the permission chain.

##### Cross-field validation rule that re-runs when any sibling changes

When a uniqueness/consistency rule depends on **several fields together** (not just the field it's on), make the `Illuminate\Contracts\Validation\ValidationRule` **field-agnostic**: pass the whole triple (all values read from siblings via `Get` in the form) through the constructor, and have `validate()` ignore `$value` (the field's own submitted value) — the constructor is the single source of truth. Then attach the **same** `->rule(fn (Get $get, $record) => new TheRule(...$get siblings..., exceptId: $record?->id))` closure to **every** field in the triple.

Why: Filament validates the **whole form on submit**, so any one attachment already re-runs the check with the current sibling values regardless of which field the user edited. Attaching to all three is not for coverage — it is so the **failure message surfaces on the field the user actually changed**. Extract the closure into a private `static` helper (`uniqueLiveRule()` below) so each attachment is one line and the triple wiring lives in one place.

Note `->live()` only re-renders + fires `afterStateUpdated` hooks (`InteractsWithSchemas::updatedInteractsWithSchemas` → `callAfterStateUpdated`); it does **not** call `validate()`. So `->live()` alone gives no live validation — rules still run at submit. Don't add `->live()` just for validation; it only buys server round-trips.

Reference: `App\Rules\UniqueLiveDocument` — `(code, version)` must be unique among LIVE DMS docs only (non-live duplicates are allowed, so a plain DB unique index would wrongly block them; this stays form-layer). It is attached to `code`, `version`, **and** `status` in `DmsFormPresenter` via `uniqueLiveRule()`, so flipping `status` to `live`, or editing `version`, re-validates the same triple and the warning appears under the changed field.

##### Extracting validation logic to `App\Rules`

**Why a top-level folder, not `Schemas/Validators/`:** the rule class is a plain `Illuminate\Contracts\Validation\ValidationRule` with no Filament dependency — it's reusable outside the admin panel (a user-panel Livewire form, a job, an API request) if the same business rule is ever needed there. Keeping it under `App\Rules` instead of nested inside the Filament resource module keeps that reuse cheap. The Filament layer only supplies the *wiring* (a private static closure on the `*FormPresenter` that reads `Get`/`$record`/`$operation` and constructs the rule).

**Decision test — extract only when the logic is genuinely rule-shaped, not just present:**
- ✅ Extract: a private method/closure that reads **multiple sibling fields** via `Get`, or runs a **DB query** inside the validation path, or encodes a **business threshold/cap**, or is **duplicated** across two or more field definitions.
- ❌ Leave inline: a single-sibling `->required(fn (Get $get) => (bool) $get('other'))` one-liner, any closure feeding `->visible()`/`->disabled()`/`->options()`/`->formatStateUsing()` (pure UI-state/display, never fails/rejects), or a plain `Rule::unique('table','col')->ignore($record?->id)` builder call (still "simple" even with an extra `->whereNull('deleted_at')` clause, unless it starts duplicating custom PHP across resources).
- Pure data-shaping/persistence-sync logic (pack/unpack helpers, relationship-sync `saveRelationshipsUsing`, toggle-driven sibling auto-correction) is **not** extraction-worthy here even when non-trivial — it never calls `$fail()`, so it doesn't fit `ValidationRule`'s contract. That's a separate concern (Service/Action territory), not this pattern.

**Two established shapes**, both `readonly`-constructor, single terse doc-line above the class, no inline comments:

1. **Field-agnostic / cross-field** (constructor holds the whole state, `validate()` ignores `$value`) — used when the check depends on *several sibling fields together* and must re-validate identically no matter which one the same rule instance is attached to. `App\Rules\UniqueLiveDocument` (above) and `App\Rules\ExtraRequiresInternalUrl` (`LinkFormPresenter` — `extra`/`internal_url` must be filled together or both empty; attached to both fields via a `fn (Get $get) => new ExtraRequiresInternalUrl($get('extra'), $get('internal_url'))` wiring closure).
2. **Per-field / contextual** (constructor holds surrounding context — a `$record`, an `$operation`, a gating flag — and `validate()` uses `$value` normally) — used when the check is independent per field but needs create/edit-aware or record-aware context. `App\Rules\SuperAdminExclusion` (`PermissionFormPresenter` — caps excluded modules at 20%, gated by `is_super_admin`), `App\Rules\UniqueActiveSkillName` (`SkillFormPresenter` — DB-backed name-conflict check, active-only on create, any-collision on edit, shared by `name()` and `name_en()`), `App\Rules\SuggestionSubmitterNotFromMa` (`SuggestionFormPresenter::userId()` — blocks MA-department submitters; moved off `CreateSuggestion`'s page lifecycle, where it used to throw a late `ValidationException` + fire a redundant `Notification`, so the error now surfaces as a normal field error at submit time, before record creation ever starts).

**Wiring convention:** keep the original field-method's private static helper name unchanged (e.g. `noNameConflictRule()`, `extraRequiresInternalUrl()`) and reduce its body to a one-line closure that constructs the new rule class — this keeps both call sites (`->rule(self::helperName())` on each field) untouched, so the extraction is a pure move, not a rename.

**Test convention:** one `tests/Feature/Rules/<RuleClass>Test.php` per rule, mirroring `SuperAdminExclusionTest`/`UniqueLiveDocumentTest` — a private `fails(Rule $rule, ...): bool` helper that calls `validate()` directly with a closure flipping a bool, no Filament/Livewire involved. Use the standard `useMysql()` + `DB::beginTransaction()`/`rollBack()` setUp/tearDown convention (see "Admin panel feature-test convention" below) for any rule touching real models; only reach for `UniqueLiveDocumentTest`'s from-scratch `Schema::create` approach when a table's migration genuinely isn't picked up by `RefreshDatabase` (DMS-specific, not the default). Add a matching resource-level feature test in the `*ResourceTest.php` only if the rule wasn't already covered end-to-end there.

**Known gotcha — a rule attached to a `->visible()`-gated field can silently never fire in one direction.** If field A is only visible when field B is filled (`->visible(fn (Get $get) => filled($get('b')))`), and the same rule is attached to both A and B: (1) Filament excludes a hidden, non-`dehydratedWhenHidden()` field from `getValidationRules()` entirely, so A's copy of the rule never runs while A is hidden; (2) independently, Laravel's own validator skips any non-`ImplicitRule` custom rule when the *attached* field's own submitted value is blank — so B's copy also skips whenever B itself is blank. Net effect: the "A filled, B blank" direction can be structurally unreachable even though the rule code is correct. This isn't a bug in the rule — it's a property of `->visible()` + non-implicit rules — but check it explicitly when a cross-field rule involves a field that's conditionally hidden by its counterpart (confirmed on `App\Rules\ExtraRequiresInternalUrl` / `LinkFormPresenter::companyIps()`).

**Known gotcha — constraining a field to a fixed enum/catalog can retroactively lock out unrelated saves on rows that already hold a stale, no-longer-valid value.** Swapping a free-form input (`ColorPicker`) for `Select::make(...)->options(EnumClass::class)` is the right minimal fix when a field should only ever hold one of N known values (`ProfileFormPresenter::favoriteColors()` → `App\Enums\FavoriteColor`, closing a bug where an off-catalog admin-set color was invisible to the user's own catalog-only checkbox UI). But Filament's `Select::getInValidationRuleValues()` builds `Rule::in([...])` from the option list, and for the field's *current* value, if it doesn't match any option, the resolved rule is `Rule::in([])` — an empty allow-list that rejects **any** value, not just the stale one. Net effect: a row that already carries a value outside the new catalog (from before the constraint existed) will fail validation the next time an admin opens Edit and saves, **even if they never touch that field** — blocking the whole save, not silently dropping the stale entry. Not a crash, no data loss, but worth an explicit prod audit for existing stale values before shipping this kind of fix (e.g. `Profile::whereNotNull('favorite_colors')->get()->filter(fn($p) => array_diff($p->favorite_colors, EnumClass::values())`) — confirmed zero such rows for `favorite_colors` at the time this was fixed). If any are found, cleaning them up needs explicit approval per the no-data-erase-without-approval policy; otherwise the forced-fix-on-next-edit behavior is an acceptable, self-healing side effect.

##### Packing virtual form fields into one JSON column

When several form controls do not map 1:1 to model columns but must be persisted together inside one JSON/array column, expose them as **virtual** Filament fields (no matching model attribute) and pack/unpack them through the resource page mutators — the same mechanism already used for `FeedResource` media (`splitMediaPaths`/`mergeMediaPaths`) and poll settings (`unpackPollSettings`/`packPollSettings`):

- `mutateFormDataBeforeFill` → `unpackX($data)` (split the stored column into the virtual fields for editing).
- `mutateFormDataBeforeCreate` and `mutateFormDataBeforeSave` → `packX($data)` (recombine the virtual fields back into the column and `unset()` the virtual keys so they never reach mass assignment).

Example: `FeedFormPresenter` stores poll mode + 2 toggles in the first 3 slots of `poll_options`, with real choices after; `Feed::extractPollSettings()` is the single source of truth for that split so the form, action, view and relation manager all agree. Always keep the split logic in one model method and reuse it — do not re-derive the format at each call site.

**Virtual sub-field names are bare — they do NOT restate the column name.** When a JSON column is named X, the virtual fields packed into it are `enabled` / `messages` (or whatever the sub-keys are), NOT `X_enabled` / `X_messages`. The column already names the feature; the prefix is redundant. `EventFormPresenter` exposes `Toggle::make('enabled')` + `Repeater::make('messages')` packed into the `events.countdown` JSON column by `Event::packCountdown`/`unpackCountdown` (read `$data['enabled']`/`$data['messages']`, write `$data['countdown'] = ['enabled'=>…,'messages'=>…]`, `unset()` the bare keys). Same rule for any future JSON-column pack/unpack.

#### 2.2 Table schema

Contains: column definitions, filter definitions, group definitions, row actions and bulk actions when shared at module level, and table-level presentation logic.

All relationships MUST be eager-loaded. Table columns default to hidden unless essential. Only ID, name, status, and a few operational fields should be visible by default. Everything else is toggleable and hidden.

> **`modifyQueryUsing` closure parameter MUST be named `$query`.** Filament invokes table scopes via `HasQuery::applyQueryScopes` → `evaluate($scope, ['query' => $query, 'isResolvingRecord' => bool])`, and `EvaluatesClosures` resolves the closure's parameters **by name** against that array. A parameter named anything else (`$q`, `$builder`) misses the `'query'` key and falls back to container resolution of `Illuminate\Database\Eloquent\Builder`, which yields a model-less builder — `$query->with([...])` then fatals with `Call to a member function newQueryWithoutRelationships() on null` (HTTP 500 on the table page). Always write `fn (Builder $query) => $query->with([...])`. Filter `queries()`/`query()` callbacks are positional (`$callback($query)`) and are exempt — `$q` is fine inside a `TernaryFilter::queries()` or `->when()`.

Table configuration options to always consider:

- `->groups([...])` — row grouping via `Group::make()` or dedicated grouping class
- `->filters([...])` — comprehensive filters (not just a few — every filter with real UX value)

> **Group objects go in `->groups([...])`, never `->filters([...])`.** A `Group::make()` is not a
> filter — Filament treats every item in `->filters([...])` as a `Filter` and calls `getName()`
> on it; `Group` has no `getName()`, so dropping a group there fatals the table page with
> `BadMethodCallException: Method Filament\Tables\Grouping\Group::getName does not exist` (HTTP 500).

**Dynamic, per-key grouping for JSON-object columns** — when a column holds a variable set of
keys (`extra`, `tags` — both stored as JSON *objects*, e.g. `tags = {"type":"test1","روش":"test1"}`),
don't write one static `Group` per column. Instead a Service extracts the union of distinct keys
across all rows and emits **one `Group` per merged key** — the group list auto-grows with the data,
0 or N keys, no code change. Reference implementation: `app/Services/Dms/DmsKeyGrouper.php` (DMS).
**Same-key merging**: keys equal after `trim()` + `mb_strtolower()` (e.g. `Category`, `Category `,
`category`) collapse into ONE group; the label is the most frequent original variant. Genuine
typos (`Catergory`) stay separate — only whitespace/case variants merge, never edit-distance. Pattern:

- `map()` (cached, the core) — **raw SQL**, single query, MySQL 5.7+/8.x compatible. Unnests each
  row's keys positionally via a numbers derived table (`0..N`, the 5.7 substitute for 8.x
  `JSON_TABLE`): `JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(col), CONCAT('$[', n, ']')))`, `UNION ALL`
  across `extra` and `tags`, grouped with `GROUP BY CAST(k AS BINARY)` and `COUNT(*)` per variant.
  **The BINARY cast is essential** — MySQL's default collation uses PAD SPACE, so a plain group key
  collapses `Category` and `Category ` (trailing space) into one row before PHP ever sees them, and
  then the trailing-space record's value could be lost. BINARY keeps every byte-distinct variant
  separate at extraction time; the trim+case-fold merge happens in PHP afterward. Returns
  `list<{norm, label, variants}>` sorted by label. TTL-cached (`Cache::remember`, no model flush
  hook — same convention as `DMS::getDocumentCounts()`; `php artisan cache:clear` for instant
  refresh). `MAX_KEYS_PER_OBJECT` bounds the numbers table; bump it if an object can hold more keys.
- `valueFor($record, $variants)` — tries every original variant byte-exactly (`array_key_exists`)
  across `extra` then `tags` (both objects); first hit wins; `null`/missing → `—`. Byte-exact so a
  variant like `Category ` resolves only on the record that actually carries it.
- `groups()` — `array_map` over `map()` → `Group::make(self::idFor($norm))->label($label)` with
  `getKeyFromRecordUsing` + `getTitleFromRecordUsing` both returning `valueFor($record, $variants)`,
  plus `->orderQueryUsing(fn($q) => $q)` **and** `->scopeQueryByKeyUsing(fn($q,$k) => …)`. **Both
  are essential** — the group id is an opaque hash, not a real column. `orderQueryUsing` stops
  Filament appending `ORDER BY <hash-id>` (throws `Unknown column 'dyn_…'` when the group is
  selected). `scopeQueryByKeyUsing` is the second crash site: "select all in group" (grouped bulk
  actions, `HasBulkActions`) calls `scopeQueryByKey($query, $value)` → `WHERE <hash-id> = <value>`
  → same `Unknown column`. Rebuild the scope as a raw `COALESCE(JSON_UNQUOTE(JSON_EXTRACT(col,
  '$."k"')) …) = ?` over the variants (mirrors `valueFor`); for the `—` bucket use `IS NULL`
  (matches valueFor's "no variant / null" case). With both, the query keeps its default sort and
  buckets are computed PHP-side via `getKeyFromRecordUsing`; "select all in group" scopes in SQL.
- `idFor($norm)` = `'dyn_' . substr(sha1($norm), 0, 16)` — a **stable, unique, ASCII-safe** id per
  *merged* key (hash the normalized form, not the label, so case variants share one id). The id is
  an internal identifier; the **label** shown to the user is the original key.
  - `valueExpression($variants)` / `jsonPath($key)` build the COALESCE + escaped JSON path used by
  `scopeQueryByKeyUsing`. Escape `"` and `\` (JSON path) **and** `'` (the wrapping single-quoted SQL
  string — the path is interpolated into `whereRaw`, and the key is user-controlled via the KeyValue
  field, so an unescaped `'` would close the literal and allow SQL injection/crash):
  `addcslashes($key, '"\'\\')`.
- Register with a spread: `->groups([..., ...DmsKeyGrouper::groups()])`.
- **Known Filament limitation**: with `getKeyFromRecordUsing`, Filament groups the **paginated
  page** (it loads the page, then buckets those rows) — it does not SQL `GROUP BY` the full result
  set (`groupQuery`/`groupBy` is only used for table summaries). So a selected group reflects only
  the current page's records. Accept this, or load all rows when grouped, if full grouping is needed.

**Grouping by a JSON array column (multi-value, not per-key like `extra`/`tags`)** — e.g. `owners`
on DMS, a JSON array of department codes (`["MA","IT"]` or the `"ALL"` sentinel). Same
`scopeQueryByKeyUsing` requirement as above applies whenever `getKeyFromRecordUsing` returns
anything other than a raw column value — here it's `implode(',', $record->owners ?? [])`, so the
scope must reconstruct that exact string server-side via `GROUP_CONCAT(JSON_UNQUOTE(JSON_EXTRACT(
owners, CONCAT('$[', n, ']'))) ORDER BY n SEPARATOR ',')` over a numbers-table unnest (same 5.7/8.x
technique as `DmsKeyGrouper`), bound via `?` — never trust the `implode` to be reproducible any
other way. Missing this scope doesn't crash (the column is real, unlike a hash id) — it silently
selects **zero rows** on "select all in group", which is easy to miss in manual QA. Reference:
`DmsTablePresenter::ownersGroup()` / `ownersKeyExpression()`.

**MariaDB fails to match a JSON object key when the stored document has it `\uXXXX`-escaped but the
query's JSON path references it via literal UTF-8 bytes** — confirmed live on production (MariaDB
10.6.20, Laravel's generic `mysql` driver). PHP's default `json_encode()` (what Eloquent's `array`
cast uses to save `dms.tags`/`dms.extra`, and what a Filament `KeyValue` field submission goes
through) escapes non-ASCII characters to `\uXXXX` — so a Persian key like `نوع سند` is stored as
literal ASCII bytes (`نوع سند`), not literal UTF-8. Any raw PHP string
built from that same logical key (e.g. from a rendered button's click value, or a variant computed
in PHP) contains the literal UTF-8 bytes instead. Both represent the identical JSON value and
`json_decode` treats them as equal — but MariaDB's `JSON_CONTAINS`/`JSON_EXTRACT`+`JSON_UNQUOTE`
path-matching does **not**: querying `JSON_EXTRACT(tags, '$."نوع سند"')` (literal-bytes path)
against a document whose key was stored escaped returns nothing, even when the key/value pulled
directly from that exact row is used verbatim in the query — a real MariaDB JSON-path defect, not a
collation/locale/`strtolower()` issue (all ruled out via direct empirical testing; `mb_strtolower`
and `strtolower` produced byte-identical output for Persian text, and MySQL's *native* `json()`
column type on local dev auto-canonicalizes storage to literal UTF-8, silently sidestepping the bug
entirely — so this class of bug is invisible in local testing and only surfaces against MariaDB).
**This affects any JSON-path SQL expression built from a dynamic, non-ASCII, admin-entered key** —
confirmed broken in `App\Livewire\Dashboard\Dms\Main::getBaseQuery()`'s `activeFilter` tag matching
(fixed — see `app/Livewire/livewirePattern.md` for the applied fix), but **`DmsKeyGrouper` and
`UserKeyGrouper`'s `scopeQueryByKeyUsing`/`valueExpression`/`jsonPath` mechanism above builds the
identical `JSON_UNQUOTE(JSON_EXTRACT(col, '$."k"'))` shape and has not yet been audited or fixed for
this — treat "select all in group" on a Persian-keyed dynamic group as suspect on MariaDB until
verified.** The fix applied to the Livewire board: match dynamic keys in PHP after fetching (decode
via the model's own array cast, compare with plain PHP equality/`in_array`) instead of pushing the
key into a SQL JSON path — sidesteps the MariaDB defect entirely regardless of storage encoding,
since PHP's `json_decode` already normalizes both forms. The same fix direction would apply to
`DmsKeyGrouper`/`UserKeyGrouper` if their grouped-bulk-action `scopeQueryByKeyUsing` is ever
confirmed broken the same way (currently unverified, not yet reproduced against MariaDB for these
two).

**Sibling defect, same root cause, different mechanism:** `getBaseQuery()`'s free-text search (not the `activeFilter` tag matching above) independently hit a second bug from the identical `json_encode()`-ASCII-escapes-non-ASCII root cause — but via MySQL `LIKE`'s own backslash-escape handling stripping the `\uXXXX` sequences from a search pattern, not via `JSON_EXTRACT` path encoding. Fixed by switching every free-text clause to `INSTR()` (no escape-character semantics at all) instead of `LIKE`. Full writeup: `app/Livewire/livewirePattern.md` § "`getBaseQuery()` free-text search — `INSTR()`, not `LIKE`, for every free-text OR-clause".

**Filtering a JSON array column that carries an "all/wildcard" sentinel** — if the column's
semantics include a sentinel value meaning "matches everyone" (e.g. `owners` containing `"ALL"`),
every `SelectFilter`/`Filter` that does `orWhereJsonContains(column, $specificValue)` must also
`orWhereJsonContains(column, 'ALL')` inside the same `where()` group, or wildcard-owned rows
silently disappear the moment any specific value is selected — a "the filter doesn't work" bug
report with no error, just wrong (under-inclusive) results. Reference: `DmsTablePresenter::ownersFilter()`.

**Dynamic per-key classifier on a single JSON-object column (groups **and** filters)** — when a model
has one JSON-object column holding admin-defined key/value tags with no fixed schema (e.g.
`users.extra` routed by its accessor into the `$.admin` sub-object), reuse the `DmsKeyGrouper` shape
but scope to that one column at its JSON path. Reference: `app/Services/User/UserKeyGrouper.php`.
- The `User::extra` accessor has a two-bucket setter: `preferences` (merged) and `admin` (the
  catch-all — every top-level key that isn't `preferences`/`admin` is routed into `$.admin`). So
  classifier keys live at `$.admin.<key>`, and the grouper's `map()` uses the 2-arg
  `JSON_KEYS(extra, '$.admin')` (MySQL 5.7.9+/MariaDB 10.6+, returns NULL on a missing/non-object
  path — no error). This is the only divergence from `DmsKeyGrouper`, which reads top-level keys of
  two columns via the 1-arg `JSON_KEYS(col)`.
- Emits **both** `Group[]` (via `groups()`, same `scopeQueryByKeyUsing`/`orderQueryUsing(fn($q)=>$q)`
  pattern as DMS) **and** `SelectFilter[]` (via `filters()`). Each filter's `->options(fn() => ...)`
  is a lazy `SELECT DISTINCT (COALESCE(...))` over the variants — one small query per dropdown open,
  not on page render. Register with spreads: `->groups([..., ...UserKeyGrouper::groups()])` and
  `->filters([..., ...UserKeyGrouper::filters()])`. Grouper ids are `dyn_<sha1:16>` — no collision
  with hand-written filter/group names.
- Cache invalidation: the grouper cache (`user_dynamic_group_keys`, 900s) is busted on every
  `User::saved` (unconditional, because classifier edits change `extra` which `wasChanged(['name',
  'role','status'])` would miss) **and** on `User::deleted` (so a deleted user's values stop
  appearing as filter options). `touchLastSeen()` uses `saveQuietly()`, which skips `saved`, so the
  frequent heartbeat does NOT bust the cache. See `User::booted()`.
- `jsonPath` escapes the same `"`/`'`/`\` set as DMS (`addcslashes($key, '"\'\\')`) — the key is
  admin-controlled via the KeyValue field, so this is the SQL-injection surface and must stay escaped.
- Reusing the same classifier outside Filament (a plain Livewire board, not a table filter): don't
  duplicate the `whereRaw` condition — `UserKeyGrouper::applyFilter(Builder $query, array $variants,
  string $value): Builder` is the public, parameterized query builder `filters()` itself calls
  internally; call it directly. Reference: `Status::applyClassifier()` in
  `app/Livewire/Dashboard/Tab/Status.php`, which encodes the active selection as a single
  `"{norm}|{value}"` string property (same shape as the pre-existing `Dms` Livewire board's
  `activeFilter`), resolves `$norm` back to its `variants` via `UserKeyGrouper::map()`, and applies it
  identically in both `stats()` and `users()` (AND-combined with the existing presence/search filters,
  never replacing them).
- **Admin-only tags**: since any admin-typed key auto-surfaces as a filter, some tags may not be meant
  for every authenticated user to see (the Status board sits behind plain `auth` middleware, wider than
  the admin panel). Convention: a key whose normalized form starts with `UserKeyGrouper::HIDDEN_PREFIX`
  (`_`, e.g. `_Salary`) is excluded from any user-facing surface via `UserKeyGrouper::isHidden($norm)`
  — checked both when building the visible group list (so it never renders as a button) **and** inside
  the query-applying method (so a hand-crafted/tampered filter value targeting a hidden key is a no-op,
  not a narrowing leak). The admin's own Filament `SelectFilter`/`Group` (`filters()`/`groups()`) are
  unaffected by this — hidden tags stay fully usable there. Deliberately opt-out (visible by default,
  hide via prefix), not opt-in, to match the feature's no-predefined-enum design; a hardcoded
  sensitive-keyword blocklist was rejected as fragile (typos, language variants) and against that same
  design intent.
- Free-text values interpolated into inline Alpine expressions (`@click`, `:class`, etc.) must go
  through `{{ \Illuminate\Support\Js::from($value) }}`, never a bare `{{ $value }}` inside a JS string
  literal — Blade's `{{ }}` only HTML-attribute-escapes, so an admin-typed value containing a quote
  breaks out of the JS string once the browser HTML-decodes the attribute. Reference:
  `resources/views/livewire/dashboard/tab/status/filters.blade.php` and the pre-existing precedent in
  the sibling `grid.blade.php`.

**Visibility scope for the colleague-status board (`scopeVisibleOnBoard`)** — to hide a class of
users from the "وضعیت همکاران" Livewire board while keeping them fully functional everywhere else
(login, channels, tasks, reservations), add a named combo scope on the model rather than reusing
`scopeActive` (which other call sites depend on). Reference: `User::scopeVisibleOnBoard()` =
`active()->whereNot('type', UserType::Guest->value)`, used in `Status` Livewire's `stats()` and
`users()`. `UserType::Guest` was a defined-but-unused enum case — repurposing it as the hide signal
needs no migration, no new column, no new enum case. Admin side surfaces these users via a list tab
(`ListUsers::getTabs()` → `'hidden'` tab filtering `type = guest`) plus the pre-existing
`typeFilter`. Note: `whereNot` excludes NULL-typed rows in SQL; safe here only because the schema
defaults `type` to `'employee'`.

**Same exclusion applies to org-wide headcount stats, not just the status board.** `ModuleAnalyticsWidget::usersData()`/`departmentsData()` originally counted every row in `users` with no `type` filter, so `UserType::Guest`/`UserType::VIP` inflated the admin dashboard's "users" and per-department headcount stats. Fixed with `->whereNotIn('type', [UserType::Guest->value, UserType::VIP->value])` — on the raw `DB::table('users')` query for `usersData()`, and inside a `withCount(['users' => fn($query) => ...])` closure for `departmentsData()` (`Department::users()` is `HasManyThrough` via `profiles`; neither `departments` nor `profiles` has a `type` column, so the closure's `type` reference is structurally unambiguous — always resolves to `users.type`). `VIP` is included here (unlike `scopeVisibleOnBoard`, which only excludes `Guest`) because these are org-wide *statistics*, not the status board's narrower "hide non-employees from the colleague list" concern — match the exclusion set to what the specific surface is actually counting, don't reflexively copy `scopeVisibleOnBoard`'s exact set.
- `->filtersFormColumns(2)` — use 2-column filter layout when there are multiple filters
- `->recordActions([...], RecordActionsPosition::AfterCells)` — always position after cells
- `->groupedBulkActions([...])` — use `self::bulkActions(ExporterClass::class)` from `FilamentActions` trait
- `->emptyStateIcon('heroicon-o-bookmark')` — always set
- `->defaultSort(...)` — always set a sensible default
- `->striped()` — use on dense tables for readability
- all validationMessages are from GLOBAL STRINGS within lan.fa folders

```php
class UserTablePresenter
{
    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function name(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('resources/user/strings.table.name'))
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function email(): TextColumn
    {
        return TextColumn::make('email')
            ->label(__('resources/user/strings.table.email'))
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('resources/user/strings.table.status'));
    }

    public static function statusGroup(): Group
    {
        return Group::make('status')
            ->label(__('resources/user/strings.table.status'))
            ->collapsible();
    }
}
```

##### `ListRecords` tabs (`getTabs()`) — default-view gotcha

When a `List*` page defines `getTabs()`, Filament's un-overridden `getDefaultActiveTab()` returns `array_key_first()` of the tabs array — whichever tab is listed first silently becomes the default, hard-scoping the page's default view to that tab's `modifyQueryUsing`. If the desired default is "show everything" (no hard scope), **don't** override `getDefaultActiveTab()` to return `null` — a blank `activeTab` never string-matches a tab key, so no tab pill highlights on load even though a separate `->default(...)`'d dropdown filter may still be silently narrowing the visible rows underneath. Instead follow `ListUsers.php`'s established idiom: add an `'all'` tab as the **first** key, with no `modifyQueryUsing` — `array_key_first()` naturally selects it, it imposes zero query constraint, and its pill correctly highlights (`'all' === 'all'`). Reference: `ListSkillRequests::getTabs()` — one tab per `SkillRequestStatus` case, icon/color/badge sourced straight from the enum's own `->heroicon()`/`->color()` methods via `collect(Enum::cases())->mapWithKeys(...)`, with `'all'` prepended via array union (`['all' => Tab::make(...)] + collect(...)->mapWithKeys(...)->all()`) so the status tabs stay enum-generated rather than hand-written per case. Same partition idea reused non-enum-driven in `ListSkills::getTabs()` (`catalog`/`inactive`/`ghosts`, mutually exclusive on `is_active`/`is_ghost` — no `'all'` tab needed there since the three together are already exhaustive).

#### 2.3 Infolist schema

One method per display entry. Each method returns a single read-only entry.

```php
class UserInfolistPresenter
{
    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/user/strings.infolist.created_at'))
            ->formatStateUsing(fn($state, $record) => $record->createdLabel())
            ->color('gray')
            ->placeholder('-');
    }
}
```
Note my persian date helper ideal for table and infolist.
Make maximum practical use of all infolist options. Apply color, icons, and badges purposefully for clarity and hierarchy.

#### 2.4 Grouping classes (dedicated, in `Schemas/Grouping/`)

When a group requires a non-trivial query (computed counts, raw SQL, bucket logic), extract it to a dedicated class under `Schemas/Grouping/`.

The table presenter method simply calls `GroupingClass::make()`.

```php
// Schemas/Grouping/UserCountGroup.php
class UserCountGroup
{
    private const SUBQUERY = <<<'SQL'
(
    SELECT COUNT(*)
    FROM `users`
    INNER JOIN `profiles`
        ON `profiles`.`user_id` = `users`.`id`
    WHERE `profiles`.`department_id` = `departments`.`code`
)
SQL;

    private const BUCKETS = [
        '0'     => ['min' => 0,  'max' => 0,    'label' => 'بدون کاربر'],
        '1-3'   => ['min' => 1,  'max' => 3,    'label' => '۱ تا ۳ کاربر'],
        '4-6'   => ['min' => 4,  'max' => 6,    'label' => '۴ تا ۶ کاربر'],
        '7-10'  => ['min' => 7,  'max' => 10,   'label' => '۷ تا ۱۰ کاربر'],
        '11-20' => ['min' => 11, 'max' => 20,   'label' => '۱۱ تا ۲۰ کاربر'],
        '20+'   => ['min' => 21, 'max' => null, 'label' => 'بیشتر از ۲۰ کاربر'],
    ];

    public static function make(): Group
    {
        $expr = self::expression();

        return Group::make('users_count')
            ->label(__('resources/department/strings.fields.users_count'))
            ->groupQueryUsing(fn(Builder $query) => $query
                ->addSelect('departments.*')
                ->selectRaw("{$expr} as users_count"))
            ->scopeQueryByKeyUsing(function (Builder $query, string $key) use ($expr) {
                $bucket = self::BUCKETS[$key] ?? null;
                if ($bucket === null) return $query;
                return match (true) {
                    $bucket['max'] === null               => $query->whereRaw("{$expr} >= ?", [$bucket['min']]),
                    $bucket['min'] === $bucket['max']     => $query->whereRaw("{$expr} = ?",  [$bucket['min']]),
                    default                               => $query->whereRaw("{$expr} BETWEEN ? AND ?", [$bucket['min'], $bucket['max']]),
                };
            })
            ->orderQueryUsing(function (Builder $query, string $direction) use ($expr) {
                return $query->orderByRaw("{$expr} " . (strtolower($direction) === 'desc' ? 'desc' : 'asc'));
            })
            ->getKeyFromRecordUsing(fn($record) => self::bucket((int)($record->users_count ?? 0))['key'])
            ->getTitleFromRecordUsing(fn($record) => self::bucket((int)($record->users_count ?? 0))['label'])
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    private static function bucket(int $count): array
    {
        foreach (self::BUCKETS as $key => $bucket) {
            if ($count < $bucket['min']) continue;
            if ($bucket['max'] !== null && $count > $bucket['max']) continue;
            return ['key' => $key, 'label' => $bucket['label']];
        }
        return ['key' => '0', 'label' => self::BUCKETS['0']['label']];
    }

    private static function expression(): string
    {
        return 'COALESCE(' . self::SUBQUERY . ', 0)';
    }
}
```

The presenter then delegates:

```php
// In DepartmentTablePresenter
public static function usersCountGroup(): Group
{
    return UserCountGroup::make();
}
```

---

### 3) Shared Traits

Three cross-cutting traits are used across all resources and pages. They must not contain resource-specific logic.

#### 3.1 `FilamentActions`

Centralizes all reusable action definitions. Every resource uses this trait instead of defining actions inline.

```php
trait FilamentActions
{
    public static function viewAction(): ViewAction
    {
        return ViewAction::make()
            ->tooltip(__('resources/general/strings.table.action_view'))
            ->iconButton();
    }

    public static function editAction(): EditAction
    {
        return EditAction::make()
            ->tooltip(__('resources/general/strings.table.action_edit'))
            ->iconButton();
    }

    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->tooltip(__('resources/general/strings.table.action_delete'))
            ->iconButton()
            ->requiresConfirmation()
            ->modalHeading(__('resources/general/strings.table.action_delete_confirm'))
            ->modalDescription(__('resources/general/strings.table.action_delete_body'));
    }

    public static function bulkActions(string $exporterClass): array
    {
        return [
            static::bulkDeleteAction(),
            static::bulkExportAction($exporterClass),
        ];
    }

    public static function bulkDeleteAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->label(__('resources/general/strings.table.bulk_delete'));
    }

    public static function bulkExportAction(string $exporterClass): ExportBulkAction
    {
        return ExportBulkAction::make()
            ->label(__('resources/general/strings.table.bulk_export'))
            ->exporter($exporterClass);
    }

    public static function assignAction(callable $handler): Action
    {
        return Action::make('assign')
            ->label(__('resources/general/strings.table.action_assign'))
            ->icon('heroicon-m-user-plus')
            ->color('success')
            ->iconButton()
            ->requiresConfirmation()
            ->modalHeading(__('resources/general/strings.table.action_assign_heading'))
            ->modalDescription(__('resources/general/strings.table.action_assign_description'))
            ->action(function (Model $record) use ($handler): void {
                $handler($record);
                Notification::make()
                    ->title(__('resources/general/strings.notifications.assigned'))
                    ->success()
                    ->send();
            });
    }

    public static function unassignAction(callable $handler): Action
    {
        return Action::make('unassign')
            ->label(__('resources/general/strings.table.action_unassign'))
            ->icon('heroicon-m-user-minus')
            ->color('danger')
            ->iconButton()
            ->requiresConfirmation()
            ->modalHeading(__('resources/general/strings.table.action_unassign_heading'))
            ->modalDescription(__('resources/general/strings.table.action_unassign_description'))
            ->action(function (Model $record) use ($handler): void {
                $handler($record);
                Notification::make()
                    ->title(__('resources/general/strings.notifications.unassigned'))
                    ->success()
                    ->send();
            });
    }

    public static function bulkUnassignAction(callable $handler): BulkAction
    {
        return BulkAction::make('bulk_unassign')
            ->label(__('resources/general/strings.table.action_bulk_unassign'))
            ->icon('heroicon-m-user-minus')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (Collection $records) use ($handler): void {
                $records->each($handler);
                Notification::make()
                    ->title(__('resources/general/strings.notifications.bulk_unassigned'))
                    ->success()
                    ->send();
            });
    }
}
```

Usage in table:

```php
->recordActions([
    self::viewAction(),
    self::editAction(),
    self::deleteAction(),
], RecordActionsPosition::AfterCells)
->groupedBulkActions(self::bulkActions(UserExporter::class))
```

#### 3.2 `FilamentHeaderActions`

Centralizes page-level header actions with a `match` dispatch on page type. Used on all Page classes.

```php
trait FilamentHeaderActions
{
    protected function getHeaderActions(): array
    {
        return match (true) {
            $this instanceof ListRecords => $this->listHeaderActions(),
            $this instanceof EditRecord  => $this->editHeaderActions(),
            default => [],
        };
    }

    private function editHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-sparkles')
                ->label(__('resources/general/strings.table.action_create')),
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->label(__('resources/general/strings.table.action_delete')),
        ];
    }

    private function listHeaderActions(): array
    {
        $actions = [
            CreateAction::make()
                ->icon('heroicon-o-sparkles')
                ->label(__('resources/general/strings.table.action_create')),
        ];

        $resource = static::getResource();
        if (method_exists($resource, 'guideTabs') && !empty($resource::guideTabs())) {
            array_unshift($actions, $resource::setupGuideAction());
        }

        return $actions;
    }
}
```

`listHeaderActions()` auto-prepends the module-guide button (see §3.4) when the resource exposes a non-empty `guideTabs()`. All `ListRecords` and `EditRecord` pages must use this trait rather than defining `getHeaderActions()` inline. Pages that suppress the Create button (e.g. `canCreate() = false`) override `listHeaderActions()` and return `[ResourceClass::setupGuideAction()]` explicitly.

#### 3.3 `FilamentPageBehavior`

Centralizes notification titles and redirect behavior after create/save. Used on all `CreateRecord` and `EditRecord` pages.

```php
trait FilamentPageBehavior
{
    protected function getCreatedNotificationTitle(): ?string
    {
        return __('resources/general/strings.notifications.created');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('resources/general/strings.notifications.saved');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
```

All `CreateRecord` and `EditRecord` pages must use this trait.

#### 3.4 `FilamentAdminGuide`

Gives a resource a tabbed in-panel admin guide (foolproof usage/setup doc) with no inline Action code. The resource declares `protected static array $guide = [['label','icon','view'], …]` (one entry per tab); the trait exposes `guideTabs()`, `setupGuideAction()` (modalContent → shared `filament/components/admin-guide.blade.php`), and `guideEmptyStateActions()`. The wrapper renders an Alpine `x-data="{ tab: 0 }"` tab bar + a `max-h-[60vh]` scroll region that `@include`s each tab view — so each tab is a self-contained Blade fragment under `resources/views/filament/resources/<module>/guide/<tab>.blade.php` (NO modal chrome, NO tab bar inside partials). Styling matches `policy/legend.blade.php`: MD3 `--md-sys-color-*` tokens, `material-symbols-rounded`, `rounded-2xl` cards, `divide-y divide-[var(--md-sys-color-outline-variant)]`, RTL, `convertToPersian()` for digits. Lang keys live once in `resources/general/strings.php` under `guide.*` (label/heading/cancel).

```php
trait FilamentAdminGuide
{
    public static function guideTabs(): array
    {
        return property_exists(static::class, 'guide') ? static::$guide : [];
    }

    public static function setupGuideAction(): Action
    {
        return Action::make('moduleGuide')
            ->label(__('resources/general/strings.guide.label'))
            ->icon('heroicon-o-book-open')
            ->modalHeading(static::getPluralModelLabel() . ' — ' . __('resources/general/strings.guide.heading'))
            ->modalContent(fn() => view('filament.components.admin-guide', ['tabs' => static::guideTabs()]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('resources/general/strings.guide.cancel'));
    }

    public static function guideEmptyStateActions(): array
    {
        return [static::setupGuideAction()];
    }
}
```

The trait does **not** declare `$guide` itself — a class cannot override a trait static property with a different default (PHP fatal: "the definition differs and is considered incompatible"). The `property_exists` guard lets a trait-using resource that forgot `$guide` degrade to "no guide" instead of fatalling. To wire: `use FilamentAdminGuide` on the resource + declare `$guide`; the header button auto-appears via §3.2. Empty-state opt-in: `->emptyStateActions(self::guideEmptyStateActions())` in `table()`.

**Icon convention — admin panel vs user panel (stress this).** The two panels use different icon systems and must not be mixed:

- **Admin (Filament) → Heroicons** (`heroicon-o-*` outline / `heroicon-m-*` mini / `heroicon-s-*` solid) for every Filament-native surface: actions, infolist entries, form fields, table columns, navigation, `Section` icons, widgets, resource `navigationIcon`, enum `getIcon()`. Filament's icon prop expects a Heroicons name.
- **User panel (Livewire/Dashboard) → Google Material Symbols** (`material-symbols-rounded`) — the MD3 design language used across dashboard blade + `dashboard.css`.

Never cross the streams: no Heroicons in user-panel blade; no Material Symbols names passed to Filament-native icon props.

**One documented exception — custom rich-content admin blade inside a Filament modal.** The `FilamentAdminGuide` tab partials use `material-symbols-rounded`. This renders because the admin Vite theme `resources/css/core/filament.css` does `@import "material-symbols/rounded.css";` (line 6) and its base layer excludes the class from the sans override — `*:not(.material-symbols-rounded) { font-family: var(--font-sans) !important }` (line 73) — so the Google font loads inside Filament. Use Material Symbols here only where Heroicons lacks a suitable glyph (`event_repeat`, `hourglass_top`, `verified_user`, `center_focus_strong`, `tips_and_updates`, …); otherwise prefer Heroicons.

**Material Symbols names must be real ligatures** — an invalid name renders as broken literal text, not a missing glyph. **Source of truth = the woff2 ligature table**, NOT `node_modules/material-symbols/index.d.ts` (the d.ts lists only current canonical names and omits legacy aliases like `done`, `email`, `phone`, `tips_and_updates`, `expand_more`, `remove_circle` that the font still renders fine — trusting the d.ts produces false positives and "fixes" working icons). A sorted 4167-entry list extracted from the installed `material-symbols-rounded.woff2` (v0.40.2) is committed at `.claude/material-symbols-ligatures.txt`. **Every** ligature used in a guide partial, legend, or `$guide` tab icon must be verified with `grep -xF "name" .claude/material-symbols-ligatures.txt` before saving — this is mandatory for the guide/legend pipeline (see §3.4 pipeline policy). Regenerate the file with fontTools if the package is upgraded (iterate GSUB → unwrap type-7 `ExtSubTable` → type-4 `ligatures` → rebuild strings from cmap-reversed glyph ids). Confirmed-NOT-in-font (never use): `shield_check`, `checklist_partial`, `event_share`, `event_add`, `widget` (singular — only `widgets` exists). Substitutions: `shield_check`→`verified_user`, `checklist_partial`→`rule`, `event_share`→`share`, `event_add`→`event`/`group_add`, `widget`→`widgets`. (`share_reviews` IS valid — do not "fix" it.)

**Guide + legend are a MANDATORY pipeline step** (treated like doc-sync + test-coverage, never skipped): every admin Filament resource ships a `FilamentAdminGuide` (`$guide` array + partials under `resources/views/filament/resources/<x>/guide/*.blade.php`); every user-panel Livewire/Dashboard module ships a `legend.blade.php` (TaskBoard tabbed pattern — see livewirePattern.md "User-panel legend pattern"). Module on both sides → both surfaces (guide includes a «تجربهٔ کاربر» tab); one side only → that side only. Review must verify: real Material Symbols ligatures (per the file above), JIT-safe `match` for chip classes (never Blade interpolation inside `bg-[var(--md-sys-color-{{ }}-container)]` arbitrary-value brackets), no comments, `convertToPersian()` for digits, non-obvious nuggets only (no visual noise), and every claim traceable to actual code (no hallucination).

---

### 4) Actions

Optionally use module actions only when a write operation is reusable or complex enough to deserve extraction.

An action should:

- contain a single public `execute` method
- receive needed form data or model instances
- avoid dispatching toasts, redirects, or tab changes unless treated explicitly as presentation logic

```php
class StoreUserAction
{
    public function execute(array $data): User
    {
        return User::create($data);
    }
}
```

---

### 5) Export class

Each resource that supports export has a dedicated exporter class.

```php
class UserExporter extends Exporter
{
    use ExportDefaults;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('name')->label('Name'),
        ];
    }
}
```

> Eager-load relationships accessed in per-row `ExportColumn` state closures via `modifyQuery(Builder $query): Builder` (e.g. `return $query->with(['user', 'department'])`). Do NOT override `getEloquentQuery` on an exporter — the `Exporter` base class has no such method (that hook belongs to the `Resource` class); calling `parent::getEloquentQuery()` from an exporter is a fatal `BadMethodCallException`.

---

### 6) Enums

Shared logic reused across form, table, and infolist that represents fixed domain states belongs in enums.

Use cases: statuses, sources, types, color mapping, icon mapping, tooltip mapping, computed labels.

```php
enum Status: string implements HasColor, HasLabel, HasIcon
{
    case Active   = 'active';
    case Inactive = 'inactive';

    public function getColor(): string  { ... }
    public function getIcon(): string   { ... }
    public function getLabel(): string  { ... }
}
```

Common enum responsibilities: color, icon, label, tooltip, computed state from record relationships.

---

### 7) Relation managers

Rules:

- One manager per relationship.
- **Reuse presenter classes** (`ProfileFormPresenter`, `ProfileInfolistPresenter`, `ProfileTablePresenter`) — never define new components inside a relation manager.
- **No filters** unless the relationship has many records where filtering provides real UX value.
- **No group actions** unless explicitly needed.
- **No edit/delete row actions** unless the workflow requires it.
- Eager-load all relationships in the table.
- If the relationship has only one record: disable search (`->searchable(false)`).
- Always set `->emptyStateIcon('heroicon-o-bookmark')`.
- `->headerActions([CreateAction::make()->icon('heroicon-o-sparkles')->label(...)])` — always use the sparkles icon for create.

```php
class ProfileRelationManager extends RelationManager
{
    use FilamentActions;

    protected static string $relationship = 'profile';
    protected static ?string $title = 'پروفایل';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/profile/strings.form.section_identity'))
                ->schema([
                    ProfileFormPresenter::personnelId(),
                    ProfileFormPresenter::gender(),
                    // ...
                ])
                ->columns(2),
            // more sections reusing ProfileFormPresenter...
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/profile/strings.infolist.section_identity'))
                ->schema([
                    ProfileInfolistPresenter::personnelId(),
                    ProfileInfolistPresenter::gender(),
                    // ...
                ])
                ->columns(2),
            // more sections reusing ProfileInfolistPresenter...
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ProfileTablePresenter::id(),
                ProfileTablePresenter::position(),
                ProfileTablePresenter::employmentStatus(),
                ProfileTablePresenter::department(),
                ProfileTablePresenter::gender(),
                ProfileTablePresenter::cellphone(),
                ProfileTablePresenter::startDate(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-sparkles')
                    ->label(__('resources/profile/strings.navigation.singular')),
            ])
            ->searchable(false)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->recordActions([
                self::viewAction(),
                self::editAction(),
                self::deleteAction(),
            ], RecordActionsPosition::AfterCells);
    }
}
```

#### Aggregated relation managers (one row per parent-side entity)

When a relation manager must show **one row per a grouping of the child records** (e.g. `PollsRelationManager` shows one row per *voter* with their concatenated choices, not one row per vote), the table query needs `GROUP BY` + aggregates (`GROUP_CONCAT`, `COUNT(*)`, `MIN(created_at)`). Two gotchas that break this under MySQL `ONLY_FULL_GROUP_BY`:

- Filament appends a stable-pagination tiebreaker `order by <table>.<pk> asc`. With `GROUP BY user_id` the raw `polls.id` is neither grouped nor aggregated → `SQLSTATE 1055`.
- A `HAVING`-based filter (`HAVING COUNT(*) = 1`) only works on the *aggregating* query; once you move aggregation into a subquery the outer query has no `GROUP BY`, so the filter must become `WHERE` on the derived column.

The robust shape is to aggregate inside a **subquery** via `fromSub`, so the outer query Filament sorts/filters/paginates only ever sees derived columns (no `ONLY_FULL_GROUP_BY` violation), and to filter with `whereRaw` on the derived aggregate alias:

```php
->query(
    Poll::query()
        ->fromSub(function ($query) use ($ownerId) {
            $query->from('polls')
                ->where('feed_id', $ownerId)
                ->selectRaw('user_id, MIN(id) as id, GROUP_CONCAT(option_index ORDER BY option_index SEPARATOR ",") as option_indexes, COUNT(*) as votes_count, MIN(created_at) as created_at')
                ->groupBy('user_id');
        }, 'polls')
        ->with('user')
)
->defaultSort('created_at', 'desc')   // orders by the derived created_at, valid
```

Conventions for this kind of manager:

- `MIN(id) as id` keeps Filament record keys unique per grouped row, so selection/view still work.
- Per-row value columns (e.g. concatenated choices) use `getStateUsing(fn (Poll $record) => $this->resolveChoices($record))` backed by a single helper that parses the `GROUP_CONCAT` CSV through the owner record's choice list — keep the parse in one protected method reused by both the table column and the infolist.
- `->sortable()` is dropped on every column (per-column sorting is meaningless over an aggregate set); order only via `defaultSort` on a derived column.
- Delete semantics change: the row no longer maps to one child record, so the default delete action (which deletes by model key) is wrong. Provide a custom `DeleteAction`/`BulkAction` that removes *all* of the grouped entity's rows (`where('feed_id', $ownerId)->where('user_id', $record->user_id)`) with confirmation.

---

## Page structure

Pages must always use both `FilamentHeaderActions` and `FilamentPageBehavior` traits:

```php
class CreateUser extends CreateRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = UserResource::class;
}

class EditUser extends EditRecord
{
    use FilamentEditHeading;
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = UserResource::class;
}

class ListUsers extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = UserResource::class;
}
```

Available page types: `Manage`, `Create`, `Edit`, `List`, `View`.

Example page registration:

```php
public static function getPages(): array
{
    return [
        'index'  => ListUsers::route('/'),
        'create' => CreateUser::route('/create'),
        'edit'   => EditUser::route('/{record}/edit'),
        'view'   => ViewUser::route('/{record}'),
    ];
}
```

---

## Layout pattern

- Resource defines sections and order (tabs, sections, columns).
- Schema classes define each field, column, and entry.
- Table schema defines columns, filters, and groups.
- Infolist schema defines read-only items.

The resource never defines field-level logic directly.

---

## Colors

Apply colors purposefully across tables, infolists, and forms. Avoid decorative use. Available palette:

```php
'danger'  => Color::Red,
'gray'    => Color::Zinc,
'info'    => Color::Blue,
'primary' => Color::Amber,
'success' => Color::Green,
'warning' => Color::Amber,

'slate'   => Color::Slate,
'zinc'    => Color::Zinc,
'neutral' => Color::Neutral,
'stone'   => Color::Stone,
'mauve'   => Color::Mauve,
'olive'   => Color::Olive,
'mist'    => Color::Mist,
'taupe'   => Color::Taupe,

'red'     => Color::Red,     'orange'  => Color::Orange,
'amber'   => Color::Amber,   'yellow'  => Color::Yellow,
'lime'    => Color::Lime,    'green'   => Color::Green,
'emerald' => Color::Emerald, 'teal'    => Color::Teal,
'cyan'    => Color::Cyan,    'sky'     => Color::Sky,
'blue'    => Color::Blue,    'indigo'  => Color::Indigo,
'violet'  => Color::Violet,  'purple'  => Color::Purple,
'fuchsia' => Color::Fuchsia, 'pink'    => Color::Pink,
'rose'    => Color::Rose,
```

---

## Naming conventions

### Schema methods
`name()`, `email()`, `status()`, `createdAt()`, `updatedAt()`

### Schema classes
`UserFormPresenter`, `UserTablePresenter`, `UserInfolistPresenter`

### Grouping classes
`UserCountGroup`, `StatusGroup` — stored in `Schemas/Grouping/`

### Enum methods
`getColor()`, `getIcon()`, `getLabel()`, `getTooltip()`, `getFromRecord()`, `getAllFromRecord()`

---

## Design rules

1. **Unified Schemas folder** — all form, table, infolist schema logic lives in `Schemas/`.
2. **Grouping classes in `Schemas/Grouping/`** — any non-trivial grouping with raw SQL, buckets, or computed expressions gets its own class.
3. **No Validators folder** — validation lives inside each form method.
4. **Filters belong to Table schema** — no separate Filters folder.
5. **Module-qualified naming** — `UserFormPresenter.php`, not `Form.php`.
6. **Resource is the orchestrator** — composes schema and page structure only; no field/column logic.
7. **One method = one component** — each field, column, and display entry is isolated.
8. **Pages use both traits** — `FilamentHeaderActions` and `FilamentPageBehavior` on all Create/Edit/List pages. Edit pages additionally `use FilamentEditHeading;` for the record-aware heading (see §6 below).
9. **FilamentActions trait is mandatory** — no inline action definitions in resources or relation managers.
10. **Pages are flexible** — Manage, Create, Edit, View, List depending on workflow.
11. **Actions are optional** — `Actions/` only for reusable/complex write operations.
12. **Shared logic belongs in Enums** — states, labels, icons, colors.
13. **RelationManagers reuse presenter classes** — never define new components inside a manager.
14. **RelationManagers are minimal** — no filters unless genuinely needed; no edit/delete unless workflow requires it.
15. **Exports are isolated** — `Exports/` folder, dedicated class.
16. **Localization is mandatory** — all labels, messages, tooltips, placeholders, and validation text use translation keys.
17. **Table defaults are sparse** — only ID, name, status, and essential operational fields visible by default; everything else toggleable and hidden.
18. **Eager-load everywhere** — `getEloquentQuery`, table, form, and relation manager tables.
19. **`emptyStateIcon`** — always set to `heroicon-o-bookmark` on every table and relation manager.
20. **`defaultSort`** — always set a sensible default on every table.

---

## Example workflow — Adding a new field

1. Add method to form presenter class.
2. Add method to infolist presenter class if read-only display is needed.
3. Add method to table presenter class if it should appear in listing (hidden by default unless essential).
4. Reference from the resource layout.

```php
// UserFormPresenter
public static function code(): TextInput
{
    return TextInput::make('code')
        ->label(__('resources/user/strings.form.code'))
        ->required()
        ->maxLength(50);
}

// UserResource
Section::make()->schema([
    UserFormPresenter::code(),
]);
```

---

## Final rules

- The resource file is the main presenter for the module. Actual form, table, infolist, action, export, enum, and relationship logic lives in dedicated classes and is only composed in the resource.
- All eager-loaded relationships must be present wherever relevant to reduce system load.
- The design must be **100% technically optimized**, not just conventionally uniform.
- Table views stay intentionally sparse. The infolist is the primary place for complete record inspection.
- **The primary language of the entire app — including all Filament admin panel inputs, labels, messages, notifications, and validation messages — is Farsi (Persian). Direction is RTL.**

---

## Shared Traits — Additional (Undocumented above)

### 4) `FilamentFilters` trait

Located at `app/Traits/FilamentFilters.php`. Applied on every resource alongside `FilamentActions`.

```php
use FilamentActions, FilamentFilters, AuthorizesByPermission;
```

Provides the shared `createdAtFilter()` method — a date-range filter with Persian Jalali date pickers that all resources use in their `->filters([])` table call.

```php
public static function createdAtFilter(): Filter
{
    return Filter::make('created_at')
        ->label(__('resources/general/strings.filters.date_range'))
        ->schema([
            Grid::make(2)->schema([
                DatePicker::make('from')
                    ->native(false)
                    ->locale('fa')
                    ->label(__('resources/general/strings.filters.date_from')),
                DatePicker::make('until')
                    ->native(false)
                    ->locale('fa')
                    ->label(__('resources/general/strings.filters.date_until')),
            ])
        ])
        ->query(fn(Builder $query, array $data) => $query
            ->when($data['from'] ?? null,  fn($q) => $q->whereDate('created_at', '>=', $data['from']))
            ->when($data['until'] ?? null, fn($q) => $q->whereDate('created_at', '<=', $data['until'])))
        ->indicateUsing(function (array $data): array {
            $indicators = [];
            if ($from = $data['from'] ?? null)  $indicators[] = __('...date_from')  . ': ' . $from;
            if ($until = $data['until'] ?? null) $indicators[] = __('...date_until') . ': ' . $until;
            return $indicators;
        });
}
```

**Rules:**
- Always the last filter in the list.
- `->native(false)` is mandatory — forces the custom Jalali picker UI.
- `->locale('fa')` activates Persian calendar.
- Guard every `$data['from']`/`$data['until']` read with `?? null` in both `query` and `indicateUsing` — a single-sided range (only `from` or only `until`) throws `Undefined array key` without it.
- `indicateUsing` must return human-readable active-filter chips so users can see the active range at a glance.

---

### 5) `AuthorizesByPermission` trait

Located at `app/Traits/AuthorizesByPermission.php`. Applied on every resource. This is the **entire access control system** for the admin panel.

```php
trait AuthorizesByPermission
{
    public static function canCreate(): bool          { return static::permits('create'); }
    public static function canEdit(Model $r): bool    { return static::permits('update'); }
    public static function canView(Model $r): bool    { return static::permits('view'); }
    public static function canDelete(Model $r): bool  { return static::permits('delete'); }
    public static function canViewAny(): bool          { return static::permits('view'); }
    // canForceDelete, canRestore follow the same pattern

    public static function moduleKey(): string
    {
        $base    = class_basename(static::class);
        $english = Str::endsWith($base, 'Resource') ? Str::before($base, 'Resource') : $base;
        return Str::snake($english);
    }

    protected static function permits(string $action): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->isDeveloper()) return true;
        return (bool) Permission::forUser($user->id)?->can(static::moduleKey(), $action);
    }
}
```

`permits()` short-circuits to `true` for `isDeveloper()` users — the developer bypass that makes every `can*` pass with no `Permission` row (also relied on by `EnsureHasPermission` middleware and the test scaffold's `developer()` helper).

**Module key derivation (automatic):**
- `UserResource` → `'user'`
- `ThsResource` → `'ths'`
- `OnboardingResource` → `'onboarding'`

**Hard override pattern:** When a resource needs special-case authorization (e.g., super-admin-only), override `canViewAny()` directly in the resource:

```php
// PermissionResource.php
public static function canViewAny(): bool
{
    return (bool) Auth::user()?->is_super_admin;
}
```

This bypasses `permits()` entirely for that one check while other `can*` methods still flow through the trait.

---

### 6) `FilamentEditHeading` trait

Located at `app/Traits/FilamentEditHeading.php`. Applied on **every `EditRecord` page** (alongside `FilamentHeaderActions` + `FilamentPageBehavior`). Gives each Edit page a record-aware heading so the admin never edits the wrong record:

> **ویرایش {modelLabel}: {recordTitle}** — e.g. `ویرایش قوانین رزرو: میز کار`

Filament's default `EditRecord::getHeading()` resolves to `getTitle()` → `ویرایش :label` where `:label = getRecordTitle()` — and `getRecordTitle()` falls back to the generic model label when the resource sets no record title, so every Edit page reads identically. This trait overrides `getHeading()` to surface the record's own identity, resolved through `Resource::getRecordTitle($record)`, with a safe fallback to `$this->getTitle()` when the resource has no real title (no regression for unconfigured resources or null records).

```php
trait FilamentEditHeading
{
    public function getHeading(): string|Htmlable
    {
        $resource = static::getResource();
        $label = $resource::getModelLabel();
        $title = (string) $resource::getRecordTitle($this->getRecord());

        if (! filled($title) || $title === $label) {
            return $this->getTitle();
        }

        return __('resources/general/strings.edit_heading', ['label' => $label, 'title' => $title]);
    }
}
```

The heading format string lives once in `lang/fa/resources/general/strings.php` → `edit_heading` (`'ویرایش :label: :title'`).

**Each resource supplies its record title via exactly one of two levers** (this is the only resource-side config the trait needs):

1. **Column-based** — set `protected static ?string $recordTitleAttribute = '<column>';` when one column identifies the record (`UserResource` → `'name'`, `PostResource` → `'title'`, `ThsResource` → `'request_subject'`, …). Filament reads it natively; no method needed.
2. **Composite / enum / accessor** — override `getRecordTitle(?Model $record): ?string` when the identity is a combination or a non-column value (`ReservationPolicyResource` → `ResourceType::tryFrom($record->resource_type)?->getLabel()`, `ProfileResource` → `$record->user?->name ?? '#'.$record->personnel_id`, `ResourceResource` → `$record->labeled_name`, `FeedResource`/`ContactResource` → `Str::limit($record->content/body, 60)`). Always guard `if (! $record) return null;` first — the `?Model` signature permits null (Filament may call before a record is loaded).

**Rules:**
- Every `EditRecord` page must `use FilamentEditHeading;`. Create/List/View pages do **not** use it — `$this->record` is null there and the trait is Edit-only.
- Relations accessed inside a composite `getRecordTitle` must already be eager-loaded by the resource's `getEloquentQuery()` (e.g. `ReservationResource` withs `resource`, `ProfileResource` withs `user`) — the trait runs on the Edit page where the record is already loaded, but keep it lazy-load-safe by relying on those withs.
- Do not duplicate this heading logic per-Edit-page — the trait is the single source. Add only the per-resource title lever (one line) above.

---

## Resource-local Enums

Beyond global enums in `app/Enums/`, each resource that models domain-specific states has an `Enums/` subfolder:

```
UserResource/Enums/UserRole.php
UserResource/Enums/UserStatus.php
UserResource/Enums/UserType.php
FeedResource/Enums/FeedCategory.php
ThsResource/Enums/TicketStatus.php
ThsResource/Enums/TicketPriority.php
ThsResource/Enums/RequestType.php
TaskResource/Enums/TaskStatus.php
TaskResource/Enums/TaskState.php
DmsResource/Enums/DocumentStatus.php
LinkResource/Enums/LinkType.php
...
```

**A resource-local enum's backing string values are the single source of truth wherever that domain concept has an ordinal rank.** `ProfileResource/Enums/Position.php` backs `HasProfileHierarchy::RANKS` (`app/Models/Traits/HasProfileHierarchy.php`) — every enum case's value is a `RANKS` key (lower number = higher rank). Adding a case means updating both the enum's match arms (label/color/icon) and `RANKS` in lockstep; every consumer (admin form/table/infolist/export, `rank()`, `isDeptHead()`, `highestRankingInDepartments()`, and the user-panel Status board's `ORDER BY FIELD(...)` sort) reads from one of these two places, so nothing else needs touching. Never hard-code the position list a third time elsewhere.

**Every resource-local enum implements `HasColor`, `HasIcon`, `HasLabel`:**

```php
enum UserRole: string implements HasColor, HasIcon, HasLabel
{
    case User      = 'user';
    case Admin     = 'admin';
    case Developer = 'developer';

    public function getLabel(): string
    {
        return match ($this) {
            self::User      => 'کاربر',
            self::Admin     => 'مدیر',
            self::Developer => 'توسعه‌دهنده',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::User      => 'gray',
            self::Admin     => 'warning',
            self::Developer => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::User      => 'heroicon-o-user',
            self::Admin     => 'heroicon-o-shield-check',
            self::Developer => 'heroicon-o-code-bracket',
        };
    }
}
```

**Usage pattern in table column:**

```php
TextColumn::make('role')
    ->badge()
    ->color(fn($state) => UserRole::tryFrom($state)?->getColor() ?? 'gray')
    ->icon(fn($state)  => UserRole::tryFrom($state)?->getIcon()  ?? '')
    ->formatStateUsing(fn($state) => UserRole::tryFrom($state)?->getLabel() ?? $state)
```

---

## Navigation Badges

Resources with actionable unread/pending counts expose a live badge on the sidebar navigation item:

```php
// ContactResource.php
public static function getNavigationBadge(): ?string
{
    return (string) static::getModel()::query()->where('is_read', false)->count() ?: null;
}

public static function getNavigationBadgeTooltip(): ?string
{
    return __('resources/contact/strings.nav_badge_tooltip');
}
```

**Rules:**
- Return `null` (not `'0'`) when the count is zero — an empty badge should not appear.
- Tooltip is always localized.
- Only add badges to resources where an unread/pending state has operational significance (contacts, tickets, etc.).

---

## User-panel badge/nudge legend (cross-reference, not this doc's mechanism)

The Filament nav badges above are admin-panel-only. The **user panel** has a separate, unrelated
notification system — `App\Services\Menu` (dot/badge = live status, bell/nudge = one-time dismissible
alert), documented in full at `app/Services/Menu/statePattern.md`. Do not conflate the two.

That system's user-facing explainer — `<x-dashboard.modal.badge-legend>` (documented at
`resources/views/viewPattern.md` §8.5) — is backed by a **single source of truth**,
`App\Services\Menu\BadgeLegendCatalog`, keyed by each indicator's `getKey()`. There is exactly one
master catalog (all 14 badges, grouped into themed tabs) in the actual Profile module
(`resources/views/livewire/dashboard/profile.blade.php`), and per-module micro-legends in modules that
have their own dedicated badge (currently the badge-bearing user-panel modules). **Rule: never hand-write a badge row's copy
in more than one place.** A module's micro-legend must call `BadgeLegendCatalog::get('its-own-key')`;
the master catalog must call `BadgeLegendCatalog::grouped()`. Both read the same row, so they cannot
drift out of sync. If you add a badge to a new module, add its row to `BadgeLegendCatalog::all()` first,
then wire both the module's own legend (if it gets one) and confirm it appears in the master catalog —
never write the `tone`/`icon`/`label`/`lights`/`clears` text a second time anywhere else.

---

## Widgets

All widgets live in `app/Filament/Widgets/` and are auto-discovered by the panel provider.

### AccountWidget

Sorts at `-3` (renders topmost, above all others). Spans full width. Passes structured data to its blade view via `getViewData()`.

```php
protected int|string|array $columnSpan = 'full';
protected static int $sort = -3;

protected function getViewData(): array
{
    $user = Auth::user();
    return [
        'user'        => $user,
        'greeting'    => shortGreeting($user->name),
        'roleLabel'   => ...,
        'department'  => $user->profile?->department?->name,
        'jalaliDate'  => convertToPersian(Jalalian::now()->format('l | d F')),
    ];
}
```

### Dashboard

Overrides `getHeading()` to return a blade view instead of a string — the heading area becomes a full rendered component:

```php
class Dashboard extends BaseDashboard
{
    public function getHeading(): string|Htmlable
    {
        return view('filament.widgets.dashboard');
    }
}
```

### ModulesGuideWidget

Driven entirely by `config('modules', [])`. Merges module config with two internal maps (`moduleMeta()`, `moduleFilamentIcons()`) and groups by `major_category`. Categories are sorted by a predefined Persian order array.

```php
protected int|string|array $columnSpan = 'full';

public function getGroupedModules(): Collection
{
    return collect(config('modules', []))
        ->map(fn($module) => array_merge($module, $meta[$id] ?? [], $icons[$id] ?? []))
        ->groupBy(fn($module) => $module['major_category'] ?? 'محتوا و ارتباطات')
        ->sortBy(fn($_, $key) => array_search($key, $categoriesOrder, true))
        ->mapWithKeys(fn($modules, $category) => [md5($category) => [
            'name'    => $category,
            'modules' => $modules,
        ]]);
}
```

Four major categories (in order): `محتوا و ارتباطات` / `عملیات و منابع` / `سیستم‌ها و ابزارها` / `کاربران و سازمان`.

### ModuleAnalyticsWidget

A heavy analytics widget. Key patterns:

```php
protected static bool $isLazy = true;          // deferred loading
protected string $view = 'livewire.admin.widgets.filament-analytics';

public string $activeTab = 'users';             // tab state tracked in Livewire
```

Every data-fetching method is cached:

```php
#[Computed(seconds: 300, cache: true)]
public function usersData(): array { ... }
```

`#[Computed(seconds: 300, cache: true)]` caches each analytics method for 5 minutes so the dashboard doesn't hammer the database on every render or poll cycle.

### OperationalChart / StructuralChart

Extends `ChartWidget` with `HasFiltersSchema` and deferred filter loading:

```php
use HasFiltersSchema;

protected bool $hasDeferredFilters = true;
protected static bool $isLazy = true;
protected int|string|array $columnSpan = ['sm' => 'full', 'md' => 1]; // responsive
```

Farsi filter action labels:

```php
protected function filtersApplyAction(Action $action): Action
{
    return $action->label('اعمال');
}

protected function filtersResetAction(Action $action): Action
{
    return $action->label('بازنشانی');
}
```

`filtersSchema()` returns a `Schema` with a Radio component for module selection.

### HR analytics chart widgets (HrDemographicsChart / HrQualificationChart / HrUnitHealthChart / HrEngagementChart)

Four `ChartWidget`s extending the same `HasFiltersSchema` + deferred-filter shape as `OperationalChart`/`StructuralChart` (added 2026-08-03). Each carries a Radio `module` filter whose options dispatch `getData()` via `match` to one `getHr{X}Data()` method per module (17 modules total, hr_a..hr_q). Every data method is annotated `#[Computed(seconds: 300, cache: true)]` and is called directly from `getData()` (the `#[Computed]` cache engages on property access; calling the method directly is consistent with the sibling widgets).

**Scope is org-wide** (no `getScopeCondition`/self-department filter), intentionally diverging from the self-scoped `OperationalChart`/`StructuralChart` — these are managerial/HR views, not personal ones.

**Null/invalid bucketing convention.** Enum-or-bucket columns never silently drop rows: null/invalid values land in a trailing `__other__` / `سایر` / `نامشخص` dataset appended only when such rows actually exist (`$rows->contains(fn($r) => !in_array($r->{$column}, $known, true))` guard, or `array_sum($unknown) > 0` for the inline-array variants). The position axis is built by a private `positionAxis($rows)` helper (in `HrQualificationChart`) returning `[$keys, $labels, $index]` that appends `__other__` only when a null/invalid position is present; `positionMap()` is the matching `array_flip` validity map. Department-axis widgets share `topDepartments()` (top-10 by profile count, deterministic `orderByDesc('cnt')->orderBy('department_id')` tie-breaker, label = `COALESCE(NULLIF(description,''), name, code)`) via the `App\Filament\Widgets\Concerns\DepartmentAxis` trait (used by `HrUnitHealthChart` + `HrEngagementChart`). `HrUnitHealthChart` also has a private `bucketSeries($rows, $idx, $column, $known, $rest, $size)` that folds rows into known datasets plus one trailing rest dataset (catches everything NOT in `$known` — null + invalid).

**59. PHP array COW trap — do not alias-then-write.** `$target = $male; $target[$i] += $v;` does NOT update `$male`: PHP arrays are value types with copy-on-write, so the write separates a detached copy and the named array stays unchanged (chart renders blank). Mutate the named arrays directly — branch inside the inner loop: `if ($r->gender === 'male') $male[$i] += $v; elseif ($r->gender === 'female') $female[$i] += $v; else $unknown[$i] += $v;`. A `$data = array_fill(...)` that is the sole owner and is never aliased to another named array IS safe to mutate (e.g. the per-band loop in `getHrKData`).

**60. Nullsafe on grouped-row lookup, not `??`.** `$rows->firstWhere('department_id', $code)` can return null under a cache race (`topDepartments()` and the data method are cached independently); `$row->total ?? 0` throws on null `$row` in PHP 8 — property access on null errors before `??` evaluates. Use `$row?->total ?? 0` (nullsafe operator). Array-key access (`$totals[$code] ?? 0`) is also null-safe and was the older idiom.

Single-query aggregation preferred: `SUM(CASE WHEN ... THEN 1 ELSE 0 END)` in one `groupBy` query beats two separate total/probational queries (`getHrMData`, `getHrKData`, `getHrOData`). MySQL `TIMESTAMPDIFF`/`NOW()`/`DATE_SUB` are intentional (MySQL-only project, matches siblings). No user input reaches SQL (Radio option list is fixed → `match` → fixed method); raw `field` values surface as Chart.js text labels (no HTML → no XSS).

### ManagePreferencesWidget

Implements `HasActions` and `HasSchemas`, uses `InteractsWithActions`, `InteractsWithSchemas`, and the `FilamentPreferences` trait. Renders as a custom Livewire view.

```php
public function getPreferencesColumns(): int { return 4; }
```

### Approval-queue resource pattern + ghost-promote widget (Skills / Talent Pool)

`SkillRequestResource` is a read/transition-only resource (no create/edit page — a `ListSkillRequests` page only) with inline one-click `approve`/`reject` `Action`s AND `groupedBulkActions` mirroring them, both gated by `self::canEdit($record)`/`self::canEdit(new Model())` in `->visible()` PLUS `abort_unless(...)` inside the `->action()` closure (defense in depth — `visible()` alone only hides the button, it doesn't stop a crafted request). Every transition (row or bulk) is guarded server-side to only act on the current `status`, so a stale/duplicate click no-ops instead of double-processing. `UnmetSkillDemandTable` is a "ghost-promote" widget shape: lists `Skill::ghost()`-scoped rows ordered by demand signal, with a single `promote` row action that opens a completion form (reusing `SkillFormPresenter::category()`/`::icon()` — the same `Select` fields the real `SkillResource` form uses, not duplicated `TextInput`s) then calls the model's own `activate()`. Ghosts are also directly browsable in `ListSkills` via a dedicated "ghosts" tab (see rule 58 below for the `getTableQuery()` override this required). See `app/Livewire/Dashboard/Profile/skillsPattern.md` for the full data-model/lifecycle this resource pair drives.

**Trap avoided — a raw ad-hoc `Action::make()` echoed directly in a custom Blade loop (`{{ $this->someAction($row) }}` inside `@foreach`) never actually works**, even with `HasActions`/`InteractsWithActions` on the component. Filament only auto-registers (`cacheAction()`) actions through specific "action-holder" contexts — Table row/header actions, Form/page header actions — never a bare per-loop-item action rendered by hand. `UnmetSkillDemandTable` originally did exactly this (dynamically-named `promote_{$skill->id}` actions in a hand-rolled `getGhosts()`/`@forelse` view); the button rendered but clicking it threw `ActionNotResolvableException` in both tests AND real usage. Fix: extend `Filament\Widgets\TableWidget` (not plain `Widget`) and define the promote button as a genuine `->recordActions([...])` entry on a `table()` method — Table's own action resolution (`resolveTableAction()`) handles registration correctly, and you get real pagination (`->defaultPaginationPageOption()`/`->paginated([...])`) for free instead of a hardcoded `->limit(10)`.

**58. A List page `Tab` cannot un-apply a `where()` the Resource's `getEloquentQuery()` already baked in — override `getTableQuery()` on the page to remove that base restriction, and push it back into each tab's `modifyQueryUsing()` instead.** `Tab::modifyQueryUsing()` chains an ADDITIONAL `where()` onto whatever `ListRecords::getTableQuery()` returns (which defaults to `static::getResource()::getEloquentQuery()`); if the resource's base query already excludes a subset of rows (e.g. `SkillResource::getEloquentQuery()` hard-filters `->where('is_ghost', false)` so ghosts never leak into ordinary browsing/edit/global-search), no tab can ever show that subset — the exclusion ANDs with every tab's own filter, always yielding zero rows for the "show the excluded rows" tab. Fix: override `protected function getTableQuery(): Builder` on the List page to build the query WITHOUT the resource's restrictive clause (replicate only what should apply unconditionally, e.g. `withCount(...)`), then give EVERY tab (including the "normal" one) an explicit `modifyQueryUsing()` so the previously-implicit exclusion is now spelled out per-tab. Reference: `ListSkills::getTableQuery()` returns `Skill::query()->withCount('skillUsers')` (no `is_ghost` clause), and `getTabs()` has `catalog` (`->where('is_ghost', false)`, default/first tab — preserves the original hide-ghosts-by-default behavior) + `ghosts` (`->where('is_ghost', true)`, badged with `Skill::ghost()->count() ?: null`, mirroring `ListUsers`' `?: null` badge idiom).

**Trap this creates — a page-navigation row action can still 404 on a row the table now shows but the Resource's `getEloquentQuery()` still excludes.** Row actions resolved from the table's own loaded record (a modal `ViewAction` with no dedicated view page, `DeleteAction`) are safe — they never re-query. But `EditAction` (or any action navigating to a registered Resource page) triggers `EditRecord::resolveRecord()` → `static::getResource()::resolveRecordRouteBinding()`, which DOES use the resource's still-restrictive `getEloquentQuery()` — so editing a ghost row surfaced by the new tab 404s. Guard the action itself, not the tab: `self::editAction()->hidden(fn(Skill $record): bool => $record->is_ghost)` in `SkillResource::table()`. Do this for every row action that navigates to a page-based (non-modal) Resource route whenever a List page tab widens visibility past the Resource's own `getEloquentQuery()`.

### Widget sorting convention

`$sort` determines widget order on the dashboard. Lower = higher on the page. `AccountWidget` at `-3` ensures it always appears first regardless of any other widget registered later.

Dashboard sort block (2026-08-03): utility widgets `AccountWidget` `-3`, `ManagePreferencesWidget` `-2`, `ModulesGuideWidget` `-1`; HR analytics block `HrDemographicsChart` `1`, `HrQualificationChart` `2`, `HrUnitHealthChart` `3`, `HrEngagementChart` `4`; existing analytics `UnmetSkillDemandTable` `5`, `ModuleAnalyticsWidget` `6`, `OperationalChart` `7`, `StructuralChart` `8`. The HR block was inserted at the top (sort 1-4) and the four existing analytics widgets renumbered down by sort-integer only — no logic/query/schema change to the existing widgets.

### Responsive columnSpan

Chart widgets use responsive arrays:

```php
protected int|string|array $columnSpan = ['sm' => 'full', 'md' => 1];
```

Full-width widgets use:

```php
protected int|string|array $columnSpan = 'full';
```

---

## AdminPanelProvider configuration

All panel-level behavior is centralized in `app/Providers/Filament/AdminPanelProvider.php`. Key patterns:

### Colors

Colors are never hardcoded in the provider — they come from a dedicated config:

```php
->colors(config('colors'))
```

### Font

Locale-conditional Yekan font via local provider:

```php
->font(
    (app()->getLocale() == 'fa') ? 'Yekan' : 'IranYekan',
    url: asset('build/assets/fonts/Yekan.woff'),
    provider: LocalFontProvider::class
)
```

### Brand logo (light/dark, per-tenant reversible)

Brand logo, favicon, preferences, and the login-page tenant background are all applied by `App\Support\FilamentPanelCustomizer::apply(Panel)` (called from `AdminPanelProvider`), not inline in the provider. The logo pair routes through one `logoHtml(bool $dark)` builder that resolves the actual file via the shared `tenantLogo($dark, 'admin')` helper (`app/Helpers/index.php`):

```php
->brandLogo(fn() => self::logoHtml(false))
->darkModeBrandLogo(fn() => self::logoHtml(true))

private static function logoHtml(bool $dark): HtmlString
{
    return new HtmlString(sprintf(
        '<img src="%s" alt="%s" title="%s" style="height:2rem;width:auto;margin:auto" />',
        e(asset(tenantLogo($dark, 'admin'))), e(config('app.name_en')), e(config('app.name_en'))
    ));
}
```

`darkModeBrandLogo()` renders both images; Filament's CSS (`fi-logo-light`/`fi-logo-dark`, plain `dark:` Tailwind variants) toggles visibility off the `.dark` class on `<html>` — no JS needed beyond what's already wired by the app's own theme sync. Works even with `->darkMode(false)` since that flag doesn't gate the CSS. The per-tenant reverse-logo behavior (`admin_reverse_logo` in `config/tenants.php`) and the `admin_use_company_logo` short-circuit are both resolved inside `tenantLogo()` itself — when `admin_reverse_logo` is `true` (Persol), the login page renders normally and the rest of the authenticated panel swaps light/dark; when `false` (fateh), there's no swap anywhere. Logo paths never hardcoded — see `config/tenantPattern.md` for the full `tenantLogo()` contract.

### Global search

```php
->globalSearch(FaultTolerantGlobalSearchProvider::class, position: GlobalSearchPosition::Topbar)
->globalSearchFieldSuffix(fn(): ?string => match (Platform::detect()) {
    Platform::Windows, Platform::Linux => 'Ctrl+K',
    Platform::Mac => '⌘K',
    default => null
})
->globalSearchKeyBindings(['command+k', 'ctrl+k'])
->globalSearchDebounce('1000ms')
```

The custom `App\Support\FaultTolerantGlobalSearchProvider` (not Filament's stock `true`) wraps each per-resource search step in a try/catch so one throwing resource can't take down the whole bar — see Rule 65 for the full rationale. Platform-aware keyboard shortcut suffix is shown next to the search field in the topbar.

### User preferences

Nine panel behaviors are controlled at runtime by the authenticated user's stored preferences, applied by `FilamentPanelCustomizer::apply()` (the same customizer that handles the brand logo above):

```php
->sidebarCollapsibleOnDesktop(fn() => self::pref('sidebar_collapsible', false))
->sidebarFullyCollapsibleOnDesktop(fn() => self::pref('sidebar_fully_collapsible', false))
->breadcrumbs(fn() => self::pref('breadcrumbs', true))
->collapsibleNavigationGroups(fn() => self::pref('collapsible_groups', true))
->topNavigation(fn() => self::pref('top_nav', false))
->unsavedChangesAlerts(fn() => self::pref('unsaved_changes_alerts', true))
->topbar(fn() => self::pref('topbar', true))
->spa(fn() => self::pref('spa_enabled', true))
->userMenu(position: fn() => self::pref('user_menu_topbar', false)
    ? UserMenuPosition::Topbar
    : UserMenuPosition::Sidebar)
```

Preferences are read from `Auth::user()->extra['preferences']` (a JSON column):

```php
private static function pref(string $key, mixed $default = false): mixed
{
    $preferences = Auth::check() ? (Auth::user()->extra['preferences'] ?? []) : [];
    return $preferences[$key] ?? $default;
}
```

### `extra` two-bucket protection (User model)

`users.extra` is a JSON bag holding **two distinct top-level buckets**, enforced at the model level by a custom `extra()` Attribute (no `array` cast — mirrors the `booking()` / Feed `content()` set-mutator pattern):

- `extra['preferences']` — purely admin-panel **app** settings (the toggles above, written by `ManagePreferences` and `setExtraValue('preferences.x', …)`). This bucket is never wiped or polluted by any write.
- `extra['admin']` — a distinct bucket for **flat key/value pairs an admin adds via the User edit form's `KeyValue` field**. Its single-word key records the origin (admin panel). Loose flat keys never sit at the top of `extra` and never enter `preferences`.

The setter discriminates by intent: a write that carries an **array** `preferences` key is "preferences-aware" (deep-merge `preferences`, preserve/replace `admin`, route any stray top-level keys into `admin`); a write **without** an array `preferences` is a KeyValue save (replace `admin` wholesale so add/delete/clear work, preserve `preferences`). The reserved names `preferences` and `admin` are dropped from KeyValue input, so an admin cannot pollute `preferences` by typing one of those names as a key.

Because the form's `KeyValue` only edits the `admin` bucket, `EditUser::mutateFormDataBeforeFill` extracts `$data['extra']['admin']` (and self-heals legacy rows that pre-date the split by gathering their loose top-level scalar keys into the KeyValue state, so they survive the next save rather than being dropped). `UserInfolistPresenter::extra()` displays `$record->extra['admin']` only — never the nested `preferences`.

### Other panel config

```php
->databaseTransactions()                        // all writes wrapped in transactions
->databaseNotifications()
->databaseNotificationsPolling('60s')
->darkMode(false)                               // suppresses Filament's own switcher; the app's custom ThemeManager/mode-manager.js already toggles `.dark` on <html> independently, so dark-mode-reactive CSS (e.g. darkModeBrandLogo) still works
->maxContentWidth(Width::Full)
->subNavigationPosition(SubNavigationPosition::End)
->viteTheme('resources/css/core/filament.css')
->navigationGroups([...])                       // 4 groups registered, all via i18n keys
->authMiddleware([Authenticate::class, EnsureHasPermission::class])
->userMenuItems(FilamentMenuService::getActions())
```

`EnsureHasPermission` is a **custom middleware** that runs after `Authenticate` and enforces module-level access before any resource is served.

`FilamentMenuService::getActions()` builds the user menu action list from a centralized service class — no menu items are defined inline in the provider.

---

## Helper functions used in Filament

Global helpers are autoloaded from `app/Helpers/index.php`; the `divider()` UI helper lives on the `App\Traits\FilamentFormDivider` trait (`app/Traits/FilamentFormDivider.php`), consumed by presenter classes. The most relevant ones in Filament context:

### `FilamentFormDivider::divider()`

This is used in a presenter class and returns a `TextEntry` that renders as a full-width gradient divider line inside infolists:

```php
function divider(): TextEntry
{
    return TextEntry::make('divider')
        ->hiddenLabel()
        ->columnSpanFull()
        ->state(new HtmlString(
            '<div class="w-2/3 h-px bg-gradient-to-r from-transparent via-gray-300 dark:via-gray-700 to-transparent opacity-80 mx-auto"></div>'
        ));
}
```

Use it between logical sections in an infolist to add visual separation without wrapping in another `Section`.

### `toJalali($date, $format = 'Y/m/d H:i')`

Converts a Gregorian date to Jalali. Detects already-Jalali years (1300–1500 range) and returns as-is to avoid double conversion.

### `toJalaliSmart($date)`

Same as `toJalali` but omits the time portion when it is `00:00` — avoids showing meaningless midnight times on date-only fields.

### Admin date-display standard — `HasJalaliAdminLabels` trait

The admin-panel uniform date format is `toJalaliSmart` (`Y/m/d H:i`, auto-drops time when `00:00`). Apply the `App\Models\Traits\HasJalaliAdminLabels` trait to the model and call its labels from Filament presenters instead of raw `toJalali($state, '<format>')`:

- `createdLabel()` / `updatedLabel()` / `deletedLabel()` — wrap `created_at` / `updated_at` / `deleted_at`.
- `adminDateLabel(string $column, ?string $fallback = '—')` — any other date column (e.g. `deadline`).
- In a presenter: `->formatStateUsing(fn($state, $record) => $record->createdLabel())`.

Do NOT mutate existing date accessors/getters (e.g. `created_formatted`) — they may be used by the user panel or other code; add the trait label alongside and call it from admin presenters (backward compatible). Apply the trait + presenter swap only to models whose display accessor uses a non-smart format (e.g. Task `j F Y`); models already on `toJalaliSmart` or settled `toJalaliRelative` (presence/last-seen) need no change. Keep `toJalaliRelative` for presence/last-seen columns — intentional, not part of this absolute-date rule.

### `convertToPersian(?string $string)`

Replaces ASCII digits 0–9 with their Persian equivalents ۰–۹. Used on any displayed number that should appear fully Persian (dates, counts, etc.).

### `superClean(?string $text, int $limit = 100, bool $nl2br = false)`

Strips HTML tags, html-decodes entities, trims, and truncates to `$limit` characters. Used for safe short previews in table columns.

### `shortGreeting(?string $name)`

Returns a time-aware Persian greeting suitable for the dashboard header (delegates to `GreetingService`).

### Other helpers available

| Helper | Purpose |
|---|---|
| `jdate($date)` | `Jalalian::fromCarbon(Carbon::parse($date))` |
| `jdateOnly($date)` | Jalali date as `d/m/Y` string |
| `isPast(string $time)` | `Carbon::parse($time)->isPast()` |
| `presence(mixed $p)` | Cast to `PresenceStatus` enum |
| `presenceCases()` | All `PresenceStatus` cases |
| `greeting($name)` | Full greeting string |
| `formatJalaliDate($date, $format, $fromFormat)` | Formatted Jalali with try/catch |
| `isVideo($path)` | Checks for mp4/webm/ogg extension |
| `getFileExtension($path)` | `strtolower(pathinfo(..., PATHINFO_EXTENSION))` |

---

## Additional design rules

_(Extending the 20 rules listed above)_

**21. Every `Section` has an icon** — `->icon('heroicon-o-...')` is always set. Description is optional but used when the section's purpose needs context.

**22. Asymmetric grid layouts are intentional** — `Grid::make(['default' => 1, 'lg' => 4])` is used for sidebar-like form layouts where one column is a narrow control panel and the others are content. Not every form uses uniform `->columns(2)`.

**23. Export columns use `formatStateUsing` for enum humanization** — raw enum string values are never written to the export file. Each enum column converts via `tryFrom($state)?->getLabel() ?? $state`.

**24. `getCompletedNotificationBody` is always Farsi** — the export completion notification body is localized. Pattern: `"خروجی {$count} {$itemLabel} با موفقیت آماده شد."`.

**25. `#[Computed(seconds: 300, cache: true)]` on heavy widget methods** — any widget method that queries the database for aggregate stats must carry this attribute. Never call raw queries in widget `getViewData()` without caching.

**26. `$isLazy = true` on analytics widgets** — all analytics and chart widgets defer their load so the initial dashboard render is fast and widgets load independently after page paint.

**27. Responsive `columnSpan` on chart widgets** — chart widgets always declare `['sm' => 'full', 'md' => 1]` so they stack on mobile and sit side-by-side on desktop.

**28. `canViewAny()` hard override for super-admin-only resources** — when a resource must be strictly invisible to non-super-admins (e.g., PermissionResource), override `canViewAny()` directly in the resource to check `is_super_admin` directly, bypassing the permission system entirely.

**29. Navigation badges return `null` not `'0'`** — `getNavigationBadge()` must cast the count to null when zero: `return (string) $count ?: null;`. An empty badge clutters the sidebar.

**30. `FilamentMenuService` owns the user menu** — no menu items are ever defined inline in `AdminPanelProvider`. The service class is the single source of truth for all user menu actions.

**31. Embed a `HasOne` companion table via `->relationship()`, never a new resource/relation manager** — when a model has a 1:1 companion table that's purely supplementary (e.g. `Task` → `TaskDetail`, mirroring the existing `User` → `Profile`/`ProfileDetail` pattern), do **not** create a separate Resource or RelationManager for it. Instead, add a new `Tab` to the parent resource's existing `Tabs::make()` and bind a `Section` directly to the relation:

```php
Tab::make('اطلاعات تکمیلی (BI)')
    ->icon('heroicon-o-chart-bar-square')
    ->schema([
        Section::make('طبقه‌بندی و گزارش‌گیری')
            ->icon('heroicon-o-chart-bar-square')
            ->relationship('detail')   // HasOne on the parent model
            ->schema([
                TaskFormPresenter::departmentId(),
                TaskFormPresenter::unit(),
                // ...
            ])
            ->columns(2),
    ]),
```

`->relationship($name)` on `Section`/`Group`/`Fieldset` (confirmed in `vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php`) works for `HasOne`, `BelongsTo`, and `MorphOne` and auto-creates/fills/saves the related row with zero custom page hooks — every parent record gets its companion row the moment it's created, even if every field in the tab is left blank. New fields on the companion model are added as more methods on the **same** `*FormPresenter`/`*InfolistPresenter` classes (no new Schemas classes), and `getEloquentQuery()` just needs the relation added to `->with([...])`. The same applies to the matching `infolist()` — use a second `Tab` there too, with a plain (non-bound) `Section` of `TextEntry`s pointed at the dot-path (`detail.department.name`, `detail.unit`, etc.).

Field-level convention for the companion table itself: when an FK-like column stores another model's natural key (not its surrogate `id`) — e.g. `task_details.department_id` storing `departments.code` — name the column `*_id` anyway for uniformity with the rest of the codebase, and declare the relation with an explicit owner key: `belongsTo(Department::class, 'department_id', 'code')`.

> **Eloquent gotcha:** never declare a column in both `casts()` (e.g. `'units' => 'array'`) **and** an `Attribute::make()` accessor/mutator for that same key. `HasAttributes::setAttribute()` checks for an attribute-mutator first and short-circuits — it never reaches the cast's JSON-encode step, so a `set` callback returning a plain array gets merged into `$this->attributes` using the array's own keys as column names (`Unknown column '0'`). If you need custom set/get logic (e.g. dedupe-on-write) for a JSON column, drop it from `casts()` and do the `json_encode`/`json_decode` by hand inside the `Attribute::make(get: ..., set: ...)` pair instead — don't combine the two mechanisms for the same key.

**32. Date/time display must read from the model's own accessor, never re-derive the format inline in a Schema** — when a model already exposes a formatted-date accessor consumed by the Livewire side (e.g. `Task::createdFormatted`/`Task::deadlineFormatted`, appended via `$appends`), the matching Filament `TextColumn`/`TextEntry` must call that **same** accessor (`fn($state, $record) => $record->created_formatted`) instead of independently calling `toJalali($state, '...')` with its own format string. Two independently-typed format strings for the same conceptual field is exactly how the admin table (`Y/m/d`) and the Livewire Kanban card (`j F Y`) silently drifted apart for `created_at`/`deadline` on `TaskResource` — found during a full cross-panel consistency audit, fixed by pointing both `TaskTablePresenter`/`TaskInfolistPresenter` at the model accessor. The underlying column still binds to the real DB field (`TextColumn::make('created_at')`) so `->sortable()` keeps working — only the **display closure** changes. For date fields that have no Livewire-facing equivalent and thus no model accessor (e.g. `deleted_at`, `updated_at`), there's nothing to bind to — at minimum reuse the exact same `toJalali(...)` format string already used elsewhere in the same resource's other date columns, so the resource is internally consistent even without a cross-panel counterpart to check against.

**33. Department labels go through the `HasDepartmentLabel` trait — never read `name`/`description`/`code` directly as a display label.** The `Department` model carries the `App\Models\Traits\HasDepartmentLabel` trait as the single source of truth for department labeling: `displayLabel()` = `description ?: name ?: code` (Farsi-first primary label, empty-string-safe `?:` not `??`), `tooltipLabel()` = `name ?: code ?: description` (English-first vice-versa tooltip). Everywhere a department is *referenced* — FK `TextColumn`/`TextEntry`, exporters, global-search titles, SQL chart axes, `Group` headings (`->getTitleFromRecordUsing(fn(?Model $record) => $record?->department?->displayLabel())`), filter buttons, Blade chips — the visible label is `displayLabel()` and the `->tooltip()` / `title=""` is `tooltipLabel()`. SQL projections use `COALESCE(NULLIF(description,''), name, code)` (add `description` to the matching `groupBy`). `Department::getCachedOptions()` / `getCachedOptionsExcludingEmptyTickets()` stay **keyed by `code`** with label = `displayLabel()` — the keys (codes) are what gets saved, so this is a purely cosmetic label change with **no save-logic/schema change**. The **exception** is `DepartmentResource` itself (table/infolist/form/exporter): as the master edit surface its own `name`/`description`/`code` columns show the **raw** field values so an admin toggling a column sees the actual data to fix — no `displayLabel`/`tooltipLabel` mixing there (global-search title stays `$record->description ?? $record->name`). The multi-department DMS layer uses the `HasDepartmentHelpers` trait (`getDepartmentDisplayLabels()` / `getDepartmentTooltipLabels()`, joined with ` ┆ `, `'ALL'` placeholder short-circuit). Code-columns storing department codes (e.g. `Review::referral`, `Suggestion::departments`) must `pluck('code')` for any `in_array` code-matching and map codes→`displayLabel()` for display — `pluck('description')` is a bug (codes never match descriptions). A standalone `code` badge next to a label is allowed for brevity; raw `description ?? name` used *as* the label is not.

**34. Kill per-row correlated subqueries under `wire:poll` with non-correlated `UNION ALL` derived tables + covering indexes — never `addSelect([subquery])` against the outer row.** `FetchContactsAction` was `User::active()->addSelect(['last_message_id' => MAX(id) subquery, 'unread_count' => COUNT(*) subquery])` with each subquery `whereColumn`'ing `users.id` → MySQL `DEPENDENT SUBQUERY`, re-running per outer row; under `wire:poll.10s` that's `O(N·k)` per viewer per poll (5K users / 500K msgs: 1103 ms → 8.8 ms, ≈125×). Fix: pre-aggregate into non-correlated derived tables and `leftJoinSub` once — `$sent = MAX(id) GROUP BY recipient_id WHERE sender_id=viewer`, `$received = MAX(id) GROUP BY sender_id WHERE recipient_id=viewer`, `$lastMsgSub = fromSub($sent->unionAll($received))->selectRaw('contact_id, MAX(max_id)')->groupBy('contact_id')`, `$unreadSub = COUNT(id) GROUP BY sender_id WHERE recipient_id=viewer AND read_at IS NULL`; `User::active()->select('users.*','lm.last_message_id', DB::raw('COALESCE(uc.unread_count,0) as unread_count'))->leftJoinSub($lastMsgSub,'lm',...)->leftJoinSub($unreadSub,'uc',...)`. `HAVING unread_count > 0` → `WHERE uc.unread_count > 0`; no-message users keep `null`/`0` via `leftJoinSub` + `COALESCE`. **`OR`-defeats-index:** old `WHERE (sender_id=V AND recipient_id=u.id) OR (sender_id=u.id AND recipient_id=V)` made the optimizer ignore both directional composites → `idx_deleted_at` + `filesort`; same OR in the 1-on-1 thread view (`->latest()->take()`, `ORDER BY created_at`) defeats the composite there — so `idx_sender_recipient_created`/`idx_recipient_sender_created` were load-bearing for no query (EXPLAIN-verified) and dropped. **Covering replacements:** `idx_sent_covering(sender_id, deleted_at, recipient_id, id)` + `idx_received_covering(recipient_id, deleted_at, read_at, sender_id, id)` — `deleted_at` mid-prefix (after equality prefix) for in-index `whereNull('deleted_at')` prune, explicit `id` (InnoDB PK) so `MAX(id)`/`COUNT(id)` resolve `Using index` with no row lookup; `(recipient_id, deleted_at, read_at, sender_id, id)` covers both received-MAX and unread-COUNT legs. Drop `idx_recipient_read_at` (strict subset). **FK migration ordering:** if an index backs `messages_sender_id_foreign`/`_recipient_id_foreign`, InnoDB refuses `DROP INDEX` — add covering indexes first (they lead with the FK col), drop old composites in a separate `Schema::table`. `DB::table()` bypasses `SoftDeletes`, so `whereNull('deleted_at')` is mandatory in every derived leg. Validate on isolated `perf_benchmark` DB (5K/500K): seed at scale and measure — `EXPLAIN ANALYZE` on MySQL 8/MariaDB, `EXPLAIN` + timed runs on 5.7 — never trust theory for index choices. Migration: `database/migrations/migrated/2026_06_30_000019_create_messages_table.php` (covering indexes live in this create-table migration; no separate optimize migration).

**35. Presenter `->tooltip()` / `formatStateUsing()` closures read a model accessor or eager relation — never issue a per-row query inside a Schema closure.** A `->tooltip(fn($record) => SomeModel::where(...)->...)` (or any query inside `formatStateUsing`) is an N+1. Move data-shaping into a **model accessor** consuming a relation already eager-loaded in `getEloquentQuery()` (Rule 18); the closure only reads `$record->accessor`. Confirm the relation is eager-loaded with the columns the accessor reads and is **not** scope-constrained to a different row (a `hasMany` by the right FK, unconstrained, is the safe shape). Reference: `DMS::readerNamesTooltip` reads the eager `reads` collection (`DmsResource::getEloquentQuery()->with(['reads.user'])`), dedupes `user_id` → `User::getCachedNames()` joined with ` ┆`, replacing the per-row `Read::getReaderNamesTooltipForDocument($record->id)` static query in `DmsTablePresenter::readCount()`. Same principle as Rule 32 (display reads the model accessor, not an inline re-derivation) and Rule 33 (labels via trait) — the Schema stays pure UI composition, the model stays the single source of truth.

**36. `use Closure;` is mandatory when a Presenter method declares `: Closure` (or a `Closure` parameter).** Inside a namespaced Filament Presenter class, an unimported `Closure` resolves to `<namespace>\Closure` (nonexistent) — PHP throws a TypeError at call time and the entire admin page 500s. Reference: `LinkFormPresenter` declared a `: Closure` return type without the import and took down the whole Link admin page; siblings `DmsFormPresenter`/`DetailsFormPresenter` already had it. Always add `use Closure;` at the top of any Presenter that references `Closure` in a signature.

**37. Cross-field closure validation rule — attach the SAME closure to both fields and branch on `$attribute`.** To enforce a dependency (e.g. extra IPs require `internal_url`), return `fn(Get $get) => function(string $attribute, mixed $value, Closure $fail) use($get){…}` from a private static helper and attach to **both** fields via `->rule(self::extraRequiresInternalUrl())`. Filament's `getValidationRules()` calls `$this->evaluate($rule)` (injects `Get`); the returned inner closure is the Laravel closure-rule called with `($attribute, $value, $fail)`. Branch on `$attribute` so the failure surfaces under the edited field; filter empty tags before the cross-field check. Reference: `LinkFormPresenter::extraRequiresInternalUrl()`. Distinct from the `ValidationRule` object pattern in §2.1 (`UniqueLiveDocument`/`uniqueLiveRule()`) — this is the closure-rule form for dependencies that don't warrant a dedicated Rule class.

**38. TagsInput `->dehydrateStateUsing` must strip empty/whitespace tags before save.** TagsInput's default dehydrate only TRIMS tags; it does NOT filter empty ones, so `['']` persists to the DB. When storing arrays (IPs, tags), add `->dehydrateStateUsing(fn($state) => is_array($state) ? array_values(array_filter(array_map('trim', $state), fn($v) => $v !== '')) : $state)`. The Link model's read-time `array_filter` in `resolvedIsInternal()` is defense-in-depth, but the form should not persist junk in the first place.

**39. A RelationManager (or Resource) calling `self::createdAtFilter()` (or any `FilamentFilters` helper) must `use App\Traits\FilamentFilters;` AND `use FilamentFilters;` — `FilamentActions` alone does NOT provide filters.** `createdAtFilter()` lives in `App\Traits\FilamentFilters` (it is the only method on that trait); module-specific filters like `typeFilter()` live on each resource's own `*TablePresenter`, not the trait. `viewAction`/`editAction`/`deleteAction`/`bulkActions` live in `App\Traits\FilamentActions`. A class importing only `FilamentActions` and calling `self::createdAtFilter()` fatalles `Call to undefined method ...::createdAtFilter()` on filter-row render. Mirror `ChannelResource`: `use App\Traits\FilamentActions; use App\Traits\FilamentFilters;` + `use FilamentActions, FilamentFilters, AuthorizesByPermission;`. Reference: `ChannelMessagesRelationManager` shipped with only `FilamentActions` and fatalled until `FilamentFilters` was added.

**40. Attachment JSON has ONE canonical shape project-wide — `{path, name, mime, size}` — built at write time, never `{file: path}`.** Every model with a JSON/array attachment column (`Message.attachments`, `ChannelMessage.attachments`, `TaskDetail.attachments`) stores each item as: `path` = stored relative path on the public disk (`$file->store(...)`/`storeAs(...)`), `name` = `$file->getClientOriginalName()` (display only), `mime` = `$file->getMimeType()` (server-side `finfo`), `size` = `$file->getSize()`. Build it in the write Action's `storeAttachments()` — mirror `SendMessageAction`/`SendChannelMessageAction` verbatim; `Task`'s `CreateTaskAction`/`UpdateTaskAction` do too. Never store a bare path under a `file` key (the `Task` inconsistency that broke cross-module parity). Display reads `name` (always via Blade `{{ }}` — `name` is client-controlled; never `{!! !!}`) and links to `path`; the model's `forceDeleting`/`forceDeleted` hook deletes by `path`. `name` is display metadata — never feed it into a storage path or `Content-Disposition`.

**The relationship-Repeater metadata trap:** when an attachments `Repeater` lives inside a `Section::relationship('detail')` (Rule 31), Filament saves the Repeater items straight into the related model's column, bypassing the page's `mutateFormDataBeforeCreate/Save` (those only see the parent record's fields). A bare `FileUpload::make('path')` stores `{path}` only and **truncates** `name`/`mime`/`size` on every admin save (the Repeater hydrates only declared schema fields from the model, so undeclared keys are dropped). The fix is to declare the metadata as `Hidden` siblings inside the Repeater and populate them from the real uploaded file inside `saveUploadedFileUsing` — NOT `afterStateUpdated`:

```php
Repeater::make('attachments')->schema([
    FileUpload::make('path')
        ->disk('public')->directory('task/attachments')
        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $set) {
            $path = $file->storeAs('task/attachments', self::fileName($file), 'public');
            $set('name', $file->getClientOriginalName());
            $set('mime', $file->getMimeType());
            $set('size', $file->getSize());
            return $path;
        }),
    Hidden::make('name'),
    Hidden::make('mime'),
    Hidden::make('size'),
])->maxItems(5),
```

Why `saveUploadedFileUsing` and NOT `afterStateUpdated`: `BaseFileUpload::saveUploadedFiles()` (vendor) passes **existing stored-path strings through unchanged** and calls `callAfterStateUpdated()` **unconditionally on every save** — so an `afterStateUpdated` closure re-runs for untouched existing items too. Deriving `name = basename($path)` there would **overwrite** a user-panel `getClientOriginalName()` whenever an admin later edits the task without touching attachments (an admin↔user sync violation), and a `!filled($get('name'))` guard to prevent that then breaks the **replace-file-in-place** case (stale hydrated metadata for the new file). `saveUploadedFileUsing` fires **only** for genuine new `TemporaryUploadedFile` uploads (passthrough strings skip it), hands you the real file object — so `name` is the true original client name (matching the Livewire Actions exactly, no `basename` fallback), and `mime`/`size` come from the file with **no disk read and no `Storage::mimeType()` throw risk** — so admin and user panels now produce the identical shape AND identical `name` value. `$set('name', ...)` resolves the sibling within the same Repeater item (Filament injects the `Set` utility by parameter name via `Component::resolveDefaultClosureDependencyForEvaluationByName`). `Hidden extends Field` and does not call `dehydrated(false)`, so the siblings hydrate from the model on edit and dehydrate back on save — that's what stops the truncation. Reference: `TaskFormPresenter::attachments()` + `TaskInfolistPresenter::attachments()` (`TextEntry::make('path')`) + `detail-fields.blade.php` + `Task::forceDeleting`.

**41. Prune-state admin UI is ONE surface, rendered identically on every prune-capable resource — `prunableWarning()` column + `pruningSoonFilter()` filter + `prunableWarning()` infolist entry, all driven by the `HasPrunableStatus` trait.** Models using `Prunable` + `App\Models\Traits\HasPrunableStatus` (currently `Message`/`Channel`/`ChannelMessage`/`Task`, `getPruneDays()=30`) get the same three-part UI. The trait is the single source: `pruneStatusText()` ("در آستانه حذف" / "تا حذف خودکار: {interval}" / "وضعیت ایمن تا: {interval}", or `null` when not soft-deleted) + `pruneStatusColor()` (`danger`/`warning`/`info`). Canonical column (`*TablePresenter::prunableWarning()`): `TextColumn::make('prune_status')->label(fields.prune_status)->getStateUsing(fn($r) => $r->pruneStatusText())->color(fn($r) => $r->pruneStatusColor())->badge()->placeholder('—')->toggleable(isToggledHiddenByDefault: true)`. Canonical filter (`*TablePresenter::pruningSoonFilter()`): `Filter::make('pruning_soon')->label(filters.pruning_soon)->query(fn(Builder $q) => $q->whereNotNull('deleted_at')->where('deleted_at', '<=', now()->subDays(30)))->toggle()` (off by default). Canonical infolist entry (`*InfolistPresenter::prunableWarning()`): `TextEntry::make('prune_info')->label(fields.prune_status)->getStateUsing(pruneStatusText)->color(pruneStatusColor)->badge()->hidden(fn($r) => !$r->deleted_at)`. Each resource owns its own in its **TablePresenter/InfolistPresenter** with its own `resources/<r>/strings` keys (`fields.prune_status`="وضعیت حذف خودکار", `filters.pruning_soon`="در آستانه حذف خودکار"); do **not** put a model-specific prune filter in `FilamentFilters` (stays generic: only `createdAtFilter()`); a Presenter-less RM inlines the same definitions in `table()`. Reference: `ContactResource` (Message) original; `ChannelResource`, `TaskResource`, `ChannelMessagesRelationManager` mirror it.

**The RM soft-delete-scope gotcha:** a prune column/filter is meaningless on a query that hides soft-deleted rows — `deleted_at` is always `null` so the badge is always `—` and the filter returns zero rows. A Resource solves this at `getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class])` (Contact/Channel/Task already do); a RelationManager that adds the prune surface must do the **same** in its own `getEloquentQuery()` — and once trashed rows are visible, pair it with `self::restoreAction()` plus `self::deleteAction()->visible(fn($r) => !$r->trashed())` for parity with the Resource (trashed rows restorable, delete only on live rows). When the RM's base query may carry a join/alias, prefix the filter column with the real table name (`channel_messages.deleted_at`) to match the RM's own `defaultSort('channel_messages.created_at')` convention and avoid ambiguity. Reference: `ChannelMessagesRelationManager` (query drops `SoftDeletingScope`, `deleted_at` + `prune_status` columns, `pruning_soon` filter, restore/delete gated). Verdict on the inline-vs-trait question for the RM filter: inline in `table()` is correct here — the RM already inlines all its columns and has no Presenter; extracting to `FilamentFilters` would diverge from the Presenter-based reference and pollute a model-agnostic trait.

**42. `$get('field')` on a Select bound to a backed enum returns the ENUM CASE, not the scalar value — never `(string)`-cast it.** A `Select` using `->options(SomeBackedEnum::class)` (a Filament `HasLabel` backed enum) hydrates field state to the **enum case instance** during the form lifecycle; it only dehydrates to the scalar on save. `$get('status')` yields a `DocumentStatus` object, not `'live'`. PHP does not auto-cast a backed enum via `(string)` — `(string)($get('status') ?? '')` throws `Error: Object of class … could not be converted to string` mid-validation and 500s the form (surfaces as "cannot upload file" because file-drop validation round-trips the whole form). Normalize: `$s = $get('status'); $s instanceof DocumentStatus ? $s->value : (string)($s ?? '')`. Applies to any rule/closure/`ValidationRule` constructor reading a sibling enum-backed Select via `Get` — feed it `->value`, never a cast. Reference: `DmsFormPresenter::uniqueLiveRule()` → `UniqueLiveDocument(string $status)`; the cast threw at `DmsFormPresenter::uniqueLiveRule()` and broke DMS upload + create. Same family as Rule 23 (export columns humanize enums via `tryFrom($state)?->getLabel()`) and Rule 36 (a missing import silently fatalles a Presenter) — Filament hands you enum objects in more places than it advertises.

**43. `FileUpload->downloadable()` on the `public` disk renders as a plain `<a href>` to `Storage::url()` (→ `/storage/...`, served statically off the symlink) — it does NOT route through the admin panel and does NOT run `EnsureHasPermission`.** A click is a client-side anchor (`vendor/filament/forms/.../file-upload.js::getDownloadLink`), not a Livewire roundtrip. Don't assume panel auth gates a public-disk download — it bypasses `EnsureHasPermission` entirely; use a private disk + a streamed/signed route if access must be enforced. The infolist `asset('storage/'.$path)` link is the same static URL.

**44. `FileUpload->maxSize()` is in KB and must stay ≤ PHP `upload_max_filesize`/`post_max_size` AND ≤ Livewire's `temporary_file_upload.rules` `max:` (KB).** A larger value fails silently (PHP rejects before Filament's rule runs). Binding ceilings: PHP `upload_max_filesize=100M`/`post_max_size=105M`, `config/livewire.php` `temporary_file_upload.rules = ['max:102400']` (100MB — Livewire's multipart temp-upload endpoint, distinct from `payload.max_size` which only guards JSON snapshots). Cap in code or raise php.ini/livewire config. Reference: `OnboardingFormPresenter::videos()` was `maxSize(204800)` (200MB) vs 100M PHP → rejected real videos; raised to `97280` KB (95MB), under `FeedFormPresenter` video (`102400` KB/100MB) and both ceilings. For "no practical limit" infrequent large uploads, still cap just under the lowest binding ceiling.

**45. A column the app treats as nullable must be `->nullable()` in the migration — the form's `->nullable()` is not enough.** Saving the null case otherwise throws `1048 Column 'x' cannot be null`. Reference: `onboardings.user_id` is the "global audience" key (null = global), but the migration omitted `->nullable()` so creating a global onboarding 500'd until the column was made nullable.

**46. Single-active-per-scope invariants belong on the model (`saved`/`updated` boot hook), NOT page `afterCreate`/`afterSave`.** A list-table `ToggleColumn` (or bulk action) UPDATEs the column directly and bypasses page hooks, so it can leave 0 or 2+ active rows per scope. Enforce at the model level so every write path is covered. Reference: `Onboarding` single-active-per-audience is only in `EditOnboarding::afterSave`/`CreateOnboarding::afterCreate`, so the list `ToggleColumn` (`OnboardingTablePresenter::isActive()`) breaks it.

**47. A required field on a non-active Filament `Tab` blocks save with its error hidden on that tab.** The admin sees "fix the errors" with nothing visible on the current tab. Make non-first-tab critical fields optional, or surface the error. Reference: `OnboardingFormPresenter::welcome()` is the only `required` RichEditor; saving from another tab fails silently.

**48. Form `->default()` overrides the DB column default on Create.** Filament dehydrates the field default into the insert payload, so `default(false)` beats a migration `default(1)` — the saved value surprises. Align the two. Reference: `OnboardingFormPresenter::isActive()` was `default(false)` vs migration `default(1)`, so new onboardings saved inactive.

**49. `TextInput->url()` uses Laravel's `url` rule = `filter_var(FILTER_VALIDATE_URL)`, which REQUIRES `scheme://host` — it rejects schemeless IPs/hosts (`127.0.0.1`, `192.168.1.1`, `example.com`).** To allow internal IPs / schemeless URLs, drop `->url()` and use `->rules(['string', 'regex:'.self::URL_HOST_REGEX])` with a three-branch regex: `(?:mailto|tel):…` | `scheme://…` | `[host][(:port)]?[(/?#)…]`. Strict `mailto|tel` keeps `javascript:` rejected. Caveat: `javascript://x` passes BOTH old `url` and this regex (pre-existing surface, not a regression) — block `javascript`/`data` explicitly if hardening is required. Reference: `LinkFormPresenter::URL_HOST_REGEX` on `internal_url` + `url`.

**50. A locked/audit submitter field is a non-submittable `Placeholder`, NEVER a `disabled()->dehydrated(true)` relationship `Select` — and an admin "quick submit" modal is a header `Action::schema([...])` that persists via inline `Model::create`, gated by `canCreate()`.** `Select::make('user_id')->relationship('user','name')->disabled()->dehydrated(true)` is wrong on two axes: it runs an **unbounded** query loading the entire `users` table for a dropdown the user can't change (data leakage + scaling), and `dehydrated(true)` on a disabled field still ships `user_id` in the Livewire payload with no server re-assertion — a crafted request can reassign the record. Correct display: `Placeholder::make('user_id')->content(fn(?Model $record) => $record?->user?->name ?? ($record === null ? auth()->user()?->name : null) ?? __('...deleted_user'))` — the `??` chain MUST gate the auth fallback on `$record === null`, else a record whose author was deleted (`$record->user` null, `$record` non-null) falls through to `auth()->user()->name` and mis-attributes the row to the current viewer. `Placeholder` submits nothing, so `user_id` is enforced server-side: on Create via `mutateFormDataBeforeCreate(array $data): array { $data['user_id'] = Auth::id(); return $data; }` (see `CreateReleaseRequest::mutateFormDataBeforeCreate`), and on Edit it's simply not in the form data so it's preserved untouched. **Rule: a display-only field is a `Placeholder` with a `$record === null`-gated auth fallback; the value is injected server-side or kept out of the form — never a `disabled()->dehydrated(true)` Select that leaks the table and trusts the payload.**

The admin "quick submit" modal (the user panel has its own Livewire modal; admin needs one too — a Resource + Create page is a full page, not a modal) is a header `Action::make('submitRequest')->schema([Presenter::type(), Presenter::title(), Presenter::body()])->action(fn(array $data) => ReleaseRequest::create(['user_id'=>(int)Auth::id(), 'type'=>$data['type'], 'title'=>$data['title'], 'body'=>$data['body'], 'status'=>ReleaseRequestStatus::Open->value]))` on `ListReleaseRequests::getHeaderActions()`, alongside the standard `CreateAction`. Two gates the custom Action does NOT get for free (unlike `CreateAction`, which auto-enforces the create policy): `->visible(fn() => $this->getResource()::canCreate())` hides it from users without create permission, and `->authorize('create', Model::class)` re-checks at submit time. Persistence goes straight to `Model::create([...])` with `user_id` and `status` forced **server-side** (never read from `$data`) — the schema validates user-editable fields (type/title/body via Presenter rules), `$fillable` is scoped to safe columns, so no mass-assignment risk and **no separate Service class is needed** (same convention as `CreateTaskAction` etc. — Action owns persistence; do not introduce a `*Service`). **Rule: a custom header Action that creates rows needs explicit `canCreate()` visibility + `authorize('create')` (CreateAction gets these implicitly) and must force immutable columns server-side — but it does NOT need a Service; inline `Model::create` with a `$fillable`-scoped model + server-forced fields is the pattern.** Reference: `ReleaseRequestResource` + `ListReleaseRequests` (header `submitRequest` → `ReleaseRequest::create` with server-forced `user_id`/`status`) + `CreateReleaseRequest::mutateFormDataBeforeCreate`.

**51. A new admin module gets a top-navbar shortcut button alongside the cache/utilities strip — a `canCreate()`-gated `<a>` to `Resource::getUrl('create')`, rendered via the `GLOBAL_SEARCH_START` → `filament.resources.dashboard.utilities` render hook.** The utilities strip (`utilities.blade.php`, registered in `FilamentServiceProvider::registerComponentHooks()` at `PanelsRenderHook::GLOBAL_SEARCH_START`) is a horizontal scroll of `w-10 h-10` icon buttons. A deep-link to a Resource create page is a plain `<a href="{{ Resource::getUrl('create') }}">` styled identically (same `group relative shrink-0 flex w-10 h-10 … rounded-xl … text-[var(--md-sys-color-primary-container)]` + `<x-ui.modals.tooltip position="bottom"/>`), NOT a `<button @click>` — it's navigation, not an Alpine action. Gate it `@if(Resource::canCreate())` so only permitted admins see it (the create page enforces the policy, but hiding the entry avoids a 403 click). Icon is a Material Symbols name matching the resource (`auto_awesome` for `ReleaseRequestResource`). **Rule: a top-navbar deep-link to a Resource page is a `canCreate()`-gated `<a>` using `Resource::getUrl('create')`, styled like the existing icon buttons — never a `<button @click>` and never ungated.** Reference: `utilities.blade.php` (release-request create shortcut) + `ReleaseRequestResource::canCreate()`/`getUrl('create')`.

**52. Release-note sync — every new admin module/feature gets a `config/releases.php` checkpoint AFTER its pattern doc (this file) is updated.** `config/releases.php` is an array of checkpoints rendered by `components/dashboard/modal/release.blade.php` as `collect(config('releases', []))->reverse()` — the **last** entry is newest. Doc-sync order when a module ships: (1) add the reusable pattern here in `filamentPattern.md` (and the user-panel side in `livewirePattern.md`) FIRST; (2) THEN append a checkpoint at the **end of the array** so it becomes newest. Checkpoint shape: `id` (kebab), `version` (next β.N), `badge` => `'جدیدترین'`, `period` (Jalali month), `icon`+`theme_icon` (Material Symbols), `color` (token key; newest forced `primary`), `theme_title` (Farsi), `items` = `['title'=>…,'desc'=>…]` Farsi pairs. **Demotion: re-badge the previously-newest entry from `'جدیدترین'` to a real category badge before appending, so only ONE `'جدیدترین'` ever exists.** Never edit an old checkpoint in place. Reference: the `release-request` β.9 checkpoint + the re-badged `channels-launch` (was `'جدیدترین'` → `'ارتباطات'`).

**53. A mixed-media `FileUpload` (image + video) MUST drop `->image()` — `->image()` forces image-only server validation and rejects every non-image mime regardless of `acceptedFileTypes`.** `acceptedFileTypes` alone is NOT enough: `->image()` adds Laravel's `image` rule (`jpg,jpeg,png,bmp,gif,webp,avif`) on top, so `video/mp4` in `acceptedFileTypes` while keeping `->image()` still 422's a `.mp4`. Gate: drop `->image()` entirely, enumerate both image AND video mimes in `acceptedFileTypes`, bump `maxSize()` to the video ceiling (Rule 44). Filament's FileUpload grid does NOT render video thumbnails natively (only image previews) — accepted trade-off; the user-panel render supplies the cover frame (see `livewirePattern.md` "Gallery mixed-media render"). Reference: `GalleryFormPresenter::path()` — was `->image()` + 4 image mimes + `maxSize(5120)`; now no `->image()`, mimes include `video/mp4|video/webm|video/quicktime`, `maxSize(51200)`. The stored `path` JSON array shape is unchanged — video support needs NO migration and NO model edit, only the form gate + a render-time extension branch.

**54. A relationship `Repeater` (`->relationship()`) that allows optional/empty rows must use `->defaultItems(0)` + conditional `->required(fn(Get $get) => filled($get('key')))` on the key/value fields + return `null` from the `mutateRelationshipDataBefore*Using` helper to skip empty items — NOT a repeater-level `dehydrateStateUsing` filter.** Filament's relationship-save loop (`vendor/filament/forms/src/Components/Repeater.php` `saveRelationshipsUsing`) iterates `getItems()` (child components), **not** the dehydrated state array, and does `if ($itemData === null) continue;` — so returning `null` from `mutateRelationshipDataBeforeCreateUsing`/`mutateRelationshipDataBeforeSaveUsing` is the only way to drop an empty row. The deletion check keys state by `"record-{modelID}"` via `array_key_exists("record-{$id}", $state)` (key lookup, not positional). **Never wrap a relationship repeater's `dehydrateStateUsing` in `array_values()`** — it strips those `"record-{id}"` keys and makes every Edit save delete-all + re-create every child (PK churn, lost timestamps, cascade risk); `array_values(array_filter(...))` is only safe on a JSON-column repeater with no reconciliation (Rule 40 `attachments()`). Symptom prevented: an empty default repeater row with a `required` key blocks Create with `"فیلد … الزامی است"` even when other rows are filled — the error is on the unnoticed empty row. Reference: `ProfileFormPresenter::details()` — `->defaultItems(0)`, `key`/`value_*` conditional `->required`, `dehydrateDetailValue()` returns `null` for a blank key; `key` `->required($rowHasValue)` fires when any value field is filled (catches "value entered, key forgotten"), each `value_*` `->required(fn(Get $get) => filled($get('key')))` fires when a key is chosen (catches "key chosen, value forgotten") — an entirely empty row passes both and is skipped at save.

**55. `disabledOn('edit')` stops a field from being *saved* — it does NOT stop Filament from *validating* it against a live-recomputed `options()` list; add `->validatedWhenNotDehydrated(false)` whenever the field's options depend on mutable data.** `disabled()` makes `isSaved()`/`isDehydrated()` false on that operation, but `isValidatedWhenNotDehydrated()` defaults to `true` and is never implied false by `disabled()` — so a `Select` whose `options()` closure is scoped to admin-editable data (e.g. a department's `ticket_options`) still runs Filament's auto-derived `in:` rule against the *current* options set on every save, even though the field can't be changed. If that data narrows after the record was created (a department gets/loses a custom type), the stored value falls outside the new options list, and editing ANY other field on that record throws a spurious "selected X is invalid" error from the untouched disabled field. Verified against installed Filament source (`CanBeValidated`/`Select::getInValidationRuleValues()`). **Rule: any `disabledOn('edit')` field whose `options()` is computed from data that can change after the record exists needs `->validatedWhenNotDehydrated(false)` — a relationship-bound Select (label resolved from the relationship, not a static options array) and a static-enum-backed Select are NOT at risk.** Reference: `TicketFormPresenter::requestType()`/`requestArea()`/`targetDepartment()`.

**56. An interactive widget shared between the admin panel and a user-panel Livewire module is ONE embeddable Livewire component, mounted via `Filament\Schemas\Components\Livewire::make($componentClass, fn(?Model $record) => [...])` on the admin side — not a second, parallel implementation of the same UI.** Gate with `->visible(fn(?Model $record) => $record !== null)` (no record yet on Create). The embedded component's blade must render **one stable root element unconditionally** — never wrap the whole template in a top-level `@if`; Livewire's nested-component tracker extracts the child's tag name from rendered HTML on mount and reuses it on the next partial re-render (e.g. from a `->live()` sibling), and zero/malformed output on any render pass poisons that stored tag, throwing `Invalid Livewire child tag name` on the *next* interaction — move the `@if` inside the root div, not around it. Full pattern (shared policy class + one embedded widget + reply-thread trait + effectiveness/notification wiring) in `livewirePattern.md` "Shared access policy + one embeddable widget". Reference: `ThsResource::form()` embedding `Ths\Workspace`.

**57. A `Repeater` with a custom `saveRelationshipsUsing()`/`afterStateHydrated()`/`dehydrateStateUsing()` that flattens a list-of-pairs into a scalar map (or vice versa) is a reshape BOUNDARY — normalize defensively on both sides, and if a `RichEditor` shares the same column with a user-facing plain-text form, sanitize before rendering unescaped, never just un-escape.** Two hazards: (1) shape mismatch (nested array where a scalar is expected) crashes a typed Livewire `Form` property or hard-typed helper — coerce non-scalars to a joined string at the read boundary rather than trusting every writer (`Profile.about_me`, `Feed.poll_options`); (2) if the same column is also writable as plain text via a user-facing Livewire form (dual-authored, not admin-only), rendering it as unescaped HTML once a `RichEditor` is added is a stored-XSS hole — a user typing `<script>` gets it rendered unescaped to every viewer. Full decision table (shape-normalize guard vs. `Str::sanitizeHtml()` vs. `superClean()` for excerpts) in `livewirePattern.md` "Rendering text/HTML in Blade" — read it before adding a `RichEditor` to any column with (or might get) a plain-text writer, or before adding a custom reshape closure to any `Repeater`. Reference: `ProfileFormPresenter::aboutMe()`, `FeedFormPresenter::pollOptions()`, `SuggestionFormPresenter::description()` (dual-authored with `SuggestionForm::$descriptionSelf`), `CommentsRelationManager`'s `RichEditor` (dual-authored with the front-end comment composer).

**61. Gating an enum case behind a dedicated Action (not a free `Select` option) needs a `selectableOptions()` enum helper that drops the case + `disabled()`/`->validatedWhenNotDehydrated(false)` on the Select for records already in that state (Rule 55) + the Action's own explicit permission gate — a bare `Action::make()` is NOT a `RecordAction` subclass and does not auto-enforce `canEdit()`/`canDelete()` the way `EditAction`/`DeleteAction` do.** A custom table Action gated only on record state (`->visible(fn($record) => $record->status !== Rejected)`) is reachable by any admin who can view the table, regardless of their `update` permission on that module — `->visible()` must ALSO check `Resource::canEdit($record)`, and the `->action()` closure should re-check with `abort_unless(Resource::canEdit($record), 403)` as defense-in-depth against a replayed/crafted action call bypassing a stale `visible` state. This was a real gap caught by `claude-reviewer` on `ReleaseRequestTablePresenter::reject()` (visibility checked only `status`, not permission) — fixed by adding `&& ReleaseRequestResource::canEdit($record)` to `visible()` and `abort_unless(ReleaseRequestResource::canEdit($record), 403)` inside `action()`, mirroring the pre-existing `SkillRequestResource`'s `RequestActions::rejectAction()` (which already does both checks via `self::canEdit()` since it's a trait mixed into the Resource itself, not a standalone class needing an explicit Resource reference). `ReleaseRequestStatus::selectableOptions()` mirrors `options()` but `->reject()`s `Rejected`, so `ReleaseRequestFormPresenter::status()` can never free-select it — the ONLY path to `Rejected` is `ReleaseRequestTablePresenter::reject()`'s dedicated Action. Without the `disabled()`+`validatedWhenNotDehydrated(false)` pair on the Select, re-saving ANY field on an already-rejected record would 422 with a spurious "selected value invalid" error (exactly Rule 55's mechanism — `selectableOptions()` narrows the `in:` validation list, and a stored `'rejected'` falls outside it). **Rule: "reject via a dedicated action, not a free Select option" is `selectableOptions()` (excludes the case) + `disabled()` on the record's own already-rejected state + `validatedWhenNotDehydrated(false)` + the Action's own `canEdit()` gate (`visible()` AND inside `action()`) — the mandatory-vs-optional question for any accompanying reason/response field is a separate, independent design decision (see the field-generalization note below), not part of the gating mechanism itself.**

`ReleaseRequest` also carries a general-purpose `response` text column (renamed from an earlier `rejection_reason` — the original design coupled the field to the `Rejected` status only; it was generalized because an admin resolving/approving a request should be able to leave the same kind of note, not just when rejecting) — `ReleaseRequestFormPresenter::response()` is a plain, always-optional, always-editable `Textarea` wired into the regular Edit form for ANY status, independent of `status()`'s own disabled-when-rejected behavior; `ReleaseRequestTablePresenter::reject()`'s own `response` field is likewise `->nullable()`, not `->required()` — a rejection can exist with no note attached, by design. `ReleaseRequestInfolistPresenter::response()`/the user-panel history-tab callout are BOTH gated purely on `filled($record->response)`, never on `status === Rejected` — the same field is shown to the requesting user whenever an admin has written into it, regardless of which status it was written under, with color/icon (`danger`/`cancel` vs `primary`/`forum`) chosen by status only for presentation, not for whether the entry renders at all. **Rule: an admin-response field attached to a status-driven workflow should default to "always optional, always visible-to-the-affected-user when filled" unless there's a specific reason to couple it to one status — coupling it to a single status (as the original `rejection_reason` did) is a premature narrowing that has to be undone the moment the field turns out to be useful for other outcomes too.** The attachments Repeater on `ReleaseRequest` reuses the Task pattern verbatim (Rule 40: `FileUpload::make('path')->saveUploadedFileUsing(...)->set('name'/'mime'/'size')` + `Hidden` siblings, directory `release_request/attachments`) even though it's a plain JSON column with no relationship — the technique doesn't require a relationship context, only the shape does. User-panel mirror in `livewirePattern.md` "Response + attachments on user-submitted requests". Reference: `ReleaseRequestFormPresenter::status()`/`response()`/`attachments()`, `ReleaseRequestTablePresenter::reject()`, `ReleaseRequestInfolistPresenter::response()`.

**62. Filament table row Actions never carry a visible text `->label()` — they are `->tooltip($label)->iconButton()`, the label surfacing only on hover.** This is the established convention for every row-level action in this codebase (`FilamentActions::viewAction()`/`editAction()`/`deleteAction()`/`restoreAction()` all use `tooltip()`+`iconButton()`, never `label()`); a custom table Action that skips `iconButton()` renders as a full labeled button inconsistent with its siblings in the same row. Header-level Actions (`CreateAction`, a custom "quick submit" modal trigger) are the opposite — they keep `->label()` since they're primary page-level CTAs, not row-icon-buttons; only ROW actions get the icon-only treatment. Reference: `ReleaseRequestTablePresenter::reject()` was originally built with `->label()` and no `iconButton()` — inconsistent with its `view`/`edit`/`delete` siblings on the same row — fixed to `->tooltip(...)->iconButton()`.

**63. A record editable from MORE THAN ONE admin surface (a Resource's own Create/Edit pages AND a RelationManager elsewhere) needs its write side-effect wired on EVERY surface individually — a page-level `afterCreate`/`afterSave` hook does NOT cover a RelationManager's table `EditAction`, which has its own independent lifecycle.** `TicketsRelationManager` (mounted on `UserResource` — a user's "Tickets" tab) reuses `TicketFormPresenter::assignedTo()` in its `EditAction`'s form, but that action is a plain table action with no `after()` hook — assigning a ticket from there updated `assigned_to` correctly (Filament's default `EditAction` handler) but never called `AssignTicketAction::syncForAdmin()`, so the mirrored TaskBoard `Task` silently never got created/updated, even though the identical field on `ThsResource`'s own `CreateTicket::afterCreate()`/`EditTicket::afterSave()` had already been wired (and tested) correctly. Fix is to attach the same side-effect directly on that surface's action: `self::editAction()->after(function (Ticket $record) { if ($record->wasChanged('assigned_to')) { app(AssignTicketAction::class)->syncForAdmin($record, $record->assigned_to); } })` — a table action's `after()` closure receives the same already-`update()`d record instance as a page's `afterSave()`, so `wasChanged()` behaves identically. Distinct from Rule 46 (which moves a cross-write-path invariant into a model boot hook): that works for a pure validity constraint, but spawning a `Task` needs request-scoped construction (title/description built from the ticket, `TaskDetail::create`) that doesn't belong in a model boot hook and would also misfire from factories/seeders — so the fix here is "audit every admin surface that can write this field and wire the same call," not "move it to the model." **Rule: whenever a field gets a dedicated sync/side-effect hook on one Resource page, grep for every OTHER place that field is form-editable (RelationManagers especially) and wire the identical hook there too — don't assume the page lifecycle is the only entry point.** Reference: `TicketsRelationManager` (`app/Filament/Resources/UserResource/RelationManagers/TicketsRelationManager.php`), covered by `UserResourceTest::test_tickets_relation_manager_assigning_a_ticket_syncs_linked_taskboard_task` / `test_tickets_relation_manager_reassigning_updates_existing_linked_task_not_duplicate`.

**64. A cascading `Select`'s `afterStateUpdated` must re-validate every DEPENDENT field's CURRENT value against its own newly-recomputed option set, not just clear the field one level below it in the chain.** `TicketFormPresenter::targetDepartment()` (department picker) feeds `requestType()`'s options (`Ticket::getCustomRequestTypeOptions($get('extra.target_department'))`, department-scoped via `HasTicketOptions`/`Department::ticket_options`), which in turn feeds `requestArea()`'s options. The department's `afterStateUpdated` only reset `request_area`/`assigned_to` (the two fields with no further options-dependency), leaving `request_type` holding whatever value it had before — if the new department's custom `ticket_options` no longer include that type, the Select renders blank/unselected while the underlying form state still carries the stale, now-invalid value. Fix mirrors the already-correct sibling implementation on the user-panel side (`Livewire\Dashboard\Ths\Main::updatedTicketTargetDepartment()`): explicitly recompute the dependent field's option set and reset it if the current value no longer exists in it, `$set` cascading down from there:
```php
->afterStateUpdated(function (Get $get, callable $set) {
    $typeOptions = Ticket::getCustomRequestTypeOptions($get('extra.target_department'));
    if (!array_key_exists($get('request_type'), $typeOptions)) {
        $set('request_type', array_key_first($typeOptions));
    }
    $set('request_area', null);
    $set('assigned_to', null);
});
```
`$set()` does NOT re-trigger the target field's own `afterStateUpdated` (Filament's `Set` utility defaults `$shouldCallUpdatedHooks` to `false`), so this doesn't double-fire `requestType()`'s own reset-`request_area` callback — the explicit `$set('request_area', null)` right after is what actually clears it. **Rule: when field C's options depend on field B which depends on field A, changing A must re-validate B against A's new scope (reset if invalid) AND unconditionally reset C — don't just reset the leaf.** Reference: `TicketFormPresenter::targetDepartment()`, covered by `ThsResourceTest::test_create_ticket_target_department_change_resets_stale_request_type` / `test_create_ticket_target_department_change_keeps_request_type_when_still_valid`.

**65. The admin panel's global search is wired through a custom `App\Support\FaultTolerantGlobalSearchProvider` (via `AdminPanelProvider`'s `->globalSearch(FaultTolerantGlobalSearchProvider::class, ...)`), NOT Filament's default `true` provider — because Filament's stock `DefaultGlobalSearchProvider::getResults()` loops every globally-searchable resource with no per-resource try/catch, so a single resource throwing (e.g. a bad `getGloballySearchableAttributes()` dot-notation attribute, or any framework/driver-version SQL quirk) takes down the ENTIRE global search bar for every admin, on every keystroke, sitewide.** Prompted by a production incident (`PermissionResource`'s `['user.name', 'user.email']` global search threw `SQLSTATE[42S22]: Unknown column 'user'` a handful of times — root cause traced to a probable stale `vendor/` deploy, not a bug in current code, but the underlying availability gap — one resource can crash search for all — was real and worth closing regardless). The custom provider mirrors `DefaultGlobalSearchProvider`'s logic except the ENTIRE per-resource pipeline — `canGloballySearch()`, `getGlobalSearchResults($query)`, and `count()`/`category()` — is wrapped in one `try/catch (Throwable)` per resource (not just the results call, which alone would still leave `canGloballySearch()` free to fatal the whole bar). `getGlobalSearchSort()` is called before the loop (inside `usort()`'s comparator) so it's isolated behind its own `safeSort()` helper, which catches and defaults to sort-weight `0` — a throwing sort comparator can't fatal `usort()` before the loop even starts, and the resource's own results still render normally, only its ordering degrades. A caught exception anywhere is `report()`-ed (so it still surfaces in logs/monitoring) and that resource is skipped for that search, while every other resource's results still render normally. The resource-iteration source is factored into an overridable `protected function getResources(): array { return Filament::getResources(); }` specifically so tests can inject fake resources without touching the live panel's registered resource list. **Rule: any panel-level extension point that fans out across all resources/models in a loop (global search, a future cross-resource bulk-export, etc.) should assume any single item can throw at ANY call site in its per-item pipeline — not just the "obvious" one — and must not let one bad actor take down the whole feature for everyone else; wrap the whole per-item block, report the exception, continue the loop.** Reference: `app/Support/FaultTolerantGlobalSearchProvider.php`, wired in `AdminPanelProvider::panel()`, covered by `tests/Feature/Support/FaultTolerantGlobalSearchProviderTest.php` (`test_admin_panel_uses_the_fault_tolerant_provider` — wiring; `test_it_skips_a_throwing_resource_and_still_returns_other_resources_results` — `getGlobalSearchResults()` throw; `test_it_skips_a_resource_that_throws_from_can_globally_search` — `canGloballySearch()` throw; `test_a_resource_that_throws_from_get_global_search_sort_still_returns_its_results` — `getGlobalSearchSort()` throw degrades ordering only, not availability — via fixture classes and the `getResources()` override seam).

**66. Cross-panel convention (not yet applicable in admin, noted for when it becomes so): when a page header carries BOTH a notification/badge-legend button and a workflow/guide button, the notification button goes FIRST in DOM source order, the guide button SECOND — under RTL flex with no `row-reverse`, this renders left-to-right as guide-then-notification, the required visual order.** The admin panel currently has only a single "guide" concept per page (no separate badge/nudge-legend button — the badge/nudge system is user-panel-only, see `app/Services/Menu/statePattern.md`), so this rule has no live admin call site today. Documented here so that if the admin panel ever grows a second, distinct header button alongside an existing guide button, the ordering matches the user panel's already-established, verified-consistent convention rather than being decided ad hoc. Full detail + the RTL-flex reasoning: `resources/views/viewPattern.md` §8.5 ("DOM order when both buttons exist").

**67. Physical-file cleanup on record deletion is centralized in `App\Traits\CleansAttachedFiles` — every model with a disk-stored attachment column uses it, never a bespoke `Storage::disk()->delete()` loop.** The trait exposes two protected statics: `deleteStoredFiles($value, array $pathKeys = ['path'])` (accepts a single path string, a flat array of path strings, or an array/`AsCollection`/`AsArrayObject` of associative items — pulls `$pathKeys` out of each associative item, e.g. `['file']` for `Ticket::requester_files`/`assignee_files`, `['url','thumbnail']` for `Onboarding::videos`) and `deleteStoredDirectory($directory)` (for whole-subtree deletes — `Channel`/`ChannelMessage`). Both are hard-wired to the `'public'` disk — every attachment column project-wide already uses it, so a disk parameter would be dead configurability, not real flexibility. Both filter every candidate path through an internal `isSafeStoredPath()` guard (rejects empty/absolute/`..`/scheme-prefixed strings, so a stray external URL or path-traversal value in a JSON column is never handed to the filesystem) and wrap the actual `Storage` call in try/catch → `Log::warning` rather than letting a disk failure abort the model's `deleting`/`forceDeleted` transaction. Wire it the same way every time: `use CleansAttachedFiles;` on the model, one line inside `booted()` on the correct event — SoftDeletes models hook `forceDeleted` (or `forceDeleting` if a *related* model's attachments must be read before cascade removes the row, e.g. `Task` reading `$task->detail->attachments`), non-SoftDeletes models hook `deleting`. Reference models: `Message`/`ChannelMessage`/`Channel`/`Task`/`ReleaseRequest` (already had bespoke cleanup, backfilled onto the trait for one source of truth), `Profile`/`DMS`/`Report`/`Ticket`/`Suggestion`/`Link`/`Post`/`Resource`/`Photo`/`Onboarding`/`Reply`/`Feed` (previously leaked orphaned files on delete — the record vanished but the file stayed on disk forever; fixed by adding the same one-line hook). **Rule: a new attachment/image/document column on any model gets its `booted()` cleanup hook in the same change that adds the column — never ship a write path without the matching delete path.**

**68. The write-side counterpart is `App\Traits\StoresAttachedFiles` — one `storeAttachment($file, string $directory, ?callable $fileName = null): array` that returns the Rule-40 canonical `{path,name,mime,size}` shape, used ONLY at the sites that already produce exactly that shape.** `$fileName` is an optional per-call-site closure (`fn($file) => …`) — the trait does not impose one naming scheme; every consumer keeps its own existing filename generator (`SendMessageAction`/`SendChannelMessageAction`/`HasReplies::storeReplyFiles` use `time().'_'.Str::random(10).'.'.ext`, `TaskFormPresenter`/`ReleaseRequestFormPresenter` use their own `self::fileName()` with a `TASK-`/`RR-` prefix, `CreateTaskAction`/`UpdateTaskAction`/`SubmitReleaseRequestAction` pass no closure at all and get Laravel's default random name) — passing `null` uses plain `store()` instead of `storeAs()`. **Critical ordering, matched against a real production failure mode:** `$file->getClientOriginalName()`/`getMimeType()`/`getSize()` are read BEFORE `store()`/`storeAs()` runs, never after — reading file metadata off a `TemporaryUploadedFile` once it has already been moved to its final disk location is what caused production errors previously, which is why every hand-written call site already did it in that order; the trait preserves that ordering internally so no caller has to remember it. Sites that do NOT use this trait because their shape genuinely differs: `Ticket::requester_files`/`assignee_files` (`{file: path}` only, via Filament's `getUploadedFileNameForStorageUsing` — Filament handles the store itself, no metadata array needed), `Onboarding::videos`/`guides` (`{title,url,thumbnail/ext,size}` — extra sibling fields, not file metadata), `Profile::attachments` (`{key,category,path}` — no name/mime/size at all). Forcing those into the same 4-key shape would be a real schema/behavior change, not a refactor — don't. Reference: `app/Traits/StoresAttachedFiles.php`, wired into `SendMessageAction`/`SendChannelMessageAction`/`CreateTaskAction`/`UpdateTaskAction`/`SubmitReleaseRequestAction`/`HasReplies`/`TaskFormPresenter`/`ReleaseRequestFormPresenter`. The two call sites that already rolled back partially-stored files on failure (`SendMessageAction`, `HasReplies::storeReplyFiles`) also now call `deleteStoredFiles()` from `CleansAttachedFiles` (Rule 67) for that rollback instead of a raw `foreach { Storage::delete() }` — same outcome on success, and no longer risks a `Storage` exception during rollback masking the original error being rethrown.

## Admin panel feature-test convention — one `ResourceTest` per resource

Every admin resource has ONE feature-test file `tests/Feature/Filament/<Module>ResourceTest.php` (28 files, one per resource — no standalone filter/helper-text files; those were folded in). The load-bearing rules:

- **Trait-level cover-gap tests live in `tests/Feature/Traits/` (mirrors `app/Traits/`, NOT the `Filament/` ResourceTest folder):** the per-resource `*ResourceTest` files only exercise the shared traits through the developer super-admin bypass, so each shared trait gets its OWN dedicated `tests/Feature/Traits/<Trait>Test.php` (namespace `Tests\Feature\Traits`) for its real contract — 10 files covering all 9 `App\Traits\Filament*` traits + `AuthorizesByPermission`. The DB-backed ones reuse the verbatim `useMysql()` + transaction + `developer()`/`admin()` scaffolding (identical to the ResourceTest skeleton below) and add `setLocale('fa')` wherever a `__()` string is asserted; the two pure-static factories (`FilamentFormDividerTest`, `FilamentActionsTest`) drop `useMysql()`/transactions/auth entirely (no DB dependency — keeping them would be dead weight the reviewer blocks). Coverage map: `FilamentEditHeadingTest` (format branch "ویرایش {label}: {title}" via a column-based resource + a composite `getRecordTitle` override, plus the `title === label` fallback to `getTitle()`; mounts a real Edit page, reads `$component->instance()->getHeading()`), `AuthorizesByPermissionTest` (`moduleKey()` incl. the `ResourceResource → "resource"` edge case, developer bypass, non-developer `role='admin'` denial WITHOUT a Permission row, granted-actions via `abilities=[['module'=>'post','actions'=>['view','create']]]`, unauthenticated denial), `FilamentPageBehaviorTest` (`getRedirectUrl`→index + `getCreatedNotificationTitle`/`getSavedNotificationTitle` fa strings — protected methods invoked via `ReflectionMethod::setAccessible(true)`), `FilamentFiltersTest` (`createdAtFilter` date-range `from`/`until` scoping via `filterTable('created_at', ['from'=>…,'until'=>…])`), `FilamentHeaderActionsTest` (`getHeaderActions` returns `['create']` on a List page, `['create','delete']` on an Edit page — reflection), `FilamentDateHandlerTest` (`mergeDeadline` joins `deadline_date`+`deadline_time`→`deadline` on Create, `mutateFormDataBeforeFill` splits on Edit, via the real `CreateTask`/`EditTask` consumers; assert on the RAW DB value, not the `datetime`-cast/Jalali accessor), `FilamentActionsTest` (every `public static` action factory — instance type + `getName()` + `isConfirmationRequired()`/`getExporter()`/`getLabel()`; do NOT invoke the `assign`/`unassign` action closures — they send Filament Notifications needing a Livewire session), `FilamentFormDividerTest` (`divider()` returns a `TextEntry` named `divider` with `getColumnSpan()['default'] === 'full'` — call via a consuming Presenter like `AdFormPresenter::divider()`, not the trait name), `FilamentPreferencesTest` (the `App\Livewire\Admin\ManagePreferences` page: `mount()` hydrates from `Auth::user()->extra['preferences']`, `save()` persists back, `getTogglesCount()` equals `count(getPreferenceFields())` — use `$component->set('data.field', …)` to mutate form state through Livewire's update lifecycle, not direct `instance()->data` writes), `FilamentAdminGuideTest` (pure-static, mirrors `FilamentActionsTest` — no DB/transaction, `setLocale('fa')`; `guideTabs()` empty-fallback via an anonymous `use FilamentAdminGuide` host with no `$guide` → `[]`, `PostResource::guideTabs()` shape with `label`/`icon`/`view` keys + `view()->exists()` on each tab view, `setupGuideAction()` builds an `Action` named `moduleGuide` with `getModalSubmitAction() === null`, the `modalContent` closure is pulled via `ReflectionObject` walk to the `modalContent` property and invoked → rendered HTML contains every tab label + `material-symbols-rounded` + `dir="rtl"`, and `guideEmptyStateActions()` returns only that one action). Heading assertions are ALSO embedded into the relevant resource's own ResourceTest (column-based: `PostResourceTest`; composite: `ReservationPolicyResourceTest`) via `assertStringContainsString($record->title|$type, (string) $component->instance()->getHeading())` so a regression in either the trait OR the per-resource `recordTitleAttribute`/`getRecordTitle` config is caught at the resource level too. **Bug surfaced + fixed by `FilamentFiltersTest`:** `FilamentFilters::createdAtFilter()` read `$data['from']`/`$data['until']` unguarded in both the `query` and `indicateUsing` closures, so a single-sided date range (only `from` or only `until`) threw `Undefined array key` under Laravel's warning→exception handler — fixed with `$data['from'] ?? null` / `$data['until'] ?? null` guards; `test_created_at_from_only_filter_works_without_until_key` locks the fix by passing only the `from` key.

- **Skeleton (copied verbatim across all 28 files):** `setUp` calls `useMysql()` (copies `.env` MySQL creds at runtime, `DB::purge('mysql')`, sets `mysql` default — no `RefreshDatabase`, migrations are MySQL-only under `database/migrations/migrated/`), then `DB::beginTransaction()`; `tearDown` does `DB::rollBack()`. A private `developer()` helper creates a `role='developer'` User + an `is_super_admin` Permission row — the developer bypass in `AuthorizesByPermission::permits()` (and `EnsureHasPermission` middleware) makes every `canViewAny/canCreate/canEdit/canDelete` pass without per-module ability rows. Auto-increment IDs are NOT reset between tests; create fresh factory rows inside the transaction.
- **DeleteAction namespace:** this Filament v5 install has UNIFIED actions — `callTableAction(\Filament\Actions\DeleteAction::class, $record)`. `Filament\Tables\Actions\DeleteAction` does NOT exist here (throws `ActionNotResolvableException`). The string form `callTableAction('delete', $record)` also works (resources register delete via the `FilamentActions` trait under name `delete`).
- **`filterTable` second-arg shape** (`TestsFilters::filterTable` signature `$data = null` with auto-wrapping): bare scalar for `SelectFilter` (non-multiple) and `TernaryFilter` FALSE-case — `filterTable('gender','Male')`, `filterTable('active','0')` (string `'0'`, not `false` — `blank(false)` no-ops a TernaryFilter). BOOLEAN `true` for a `TernaryFilter` TRUE-case — `filterTable('has_users', true)`; string `'1'` lands in the else branch and silently fails to select the true query. ARRAY for multi-select — `filterTable('owners', ['departments'=>['MA']])`. NO second arg for a plain toggle `BaseFilter` — `filterTable('low_score')` sets `['isActive'=>true]`. Symptom of a wrong shape: `array_key_exists(): Argument #1 must be a valid array offset type` or a silent no-op — swap scalar↔array↔boolean↔none; the test run is the source of truth.
- **RichEditor (Tiptap) quirk:** `->required()` does NOT register the `required` rule key in Livewire's validator (closure-based `getRequiredValidationRule` calls `$fail('validation.required')->translate()`), so `assertHasFormErrors(['field'=>'required'])` cannot match — use key-only `assertHasFormErrors(['field'])` or the message-string form `['field'=>'validation.required']`. The empty-Tiptap-doc value `{type:doc,content:[{type:paragraph,content:[]}]}` is what trips required (plain `''`/`null` does NOT). RichEditor stores HTML — DB assertions use `assertStringContainsString`, not exact match.
- **Locale:** `.env.testing` forces `APP_LOCALE=en` but most `lang/en/...` option-translation files don't exist (only `lang/fa`). Any field whose options come from `__('resources/.../strings.*')` throws a `ViewException` ("Return value must be of type array, string returned") on mount under `en`. Add `$this->app->setLocale('fa')` in `setUp` (or per-test) when the FormPresenter/TablePresenter uses `__()` for option arrays — reach for it the moment you see that ViewException on mount.
- **Pagination flake on `assertCanSeeTableRecords`:** the dev DB has many seeded rows and tables with `defaultSort` paginate at 10/page — a factory row with a mid-alphabet name can sort beyond page 1. Give factory rows `0`-prefix names/titles so they land on page 1 (test-data choice, not a production change).
- **Read-only / List+Edit-only resources:** some resources hard-code `canCreate()=false` and register only an `EditAction` (no delete, no filters) — e.g. `ReservationPolicyResource`. Do NOT invent create/delete/filter tests; cover List + Edit mount + Edit save + 1-2 edit-form validation tests. A resource with only `index`+`view` pages (e.g. `EnergyTestResource`) gets List + View + filter tests only.
- **Embedded user-panel widgets on an admin Edit page can 403 the admin** (open issue, Rule 56): `ThsResource::form()` embeds `Ths\Workspace`, whose `mount()` enforces `TicketAccessPolicy::canView()` — which has NO developer/admin bypass (only requester/assignee/head-of-target-dept). So an admin/developer who is none of those gets a 403 mounting admin `EditTicket`. Tests route around it by factory-creating the ticket with `requester_id => $admin->id` (NOT `assigned_to` — `TicketFormPresenter::assignedTo()` options are scoped to the target department and reject an out-of-department assignee via `in:`). The production fix (admin bypass in `TicketAccessPolicy` vs. admin-context skip in `Workspace`) is unresolved — flagged, not fixed.
