# Filament 5 Module Structure Guide

This document describes the preferred module structure for a Filament PHP 5 resource. The goal is to keep each concern isolated, reusable, easy to scan, and consistent with the Livewire module pattern used elsewhere in the codebase.

> Before implementing, study the latest Filament PHP 5 docs: https://filamentphp.com/docs/5.x/resources/overview

Filament's resource model centers on a main resource class plus dedicated `Pages`. Tables hold columns, filters, groups, and actions. Forms and infolists define schema-driven UI. Relation managers are the standard way to manage related records inside a resource.

---

## Core idea

Each module is organized around a main `Resource` class that acts as the entry point. The resource only composes the module and defines page registration, query behavior, and high-level layout. All detailed UI definitions live in dedicated presenter classes.

The resource does **not** contain inline form, table, or infolist definitions. It calls static methods from dedicated schema classes.

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
    ├── FilamentActions.php
    ├── FilamentHeaderActions.php
    └── FilamentPageBehavior.php
```

### Key structural rules

- **Filters are merged into `UserTablePresenter.php`** — no separate Filters folder.
- **Grouping logic with non-trivial queries lives in `Schemas/Grouping/`** as a dedicated class.
- **Forms, Tables, and Infolists live under `Schemas/`**.
- **Names are intentionally module-qualified**: `UserFormPresenter`, `UserTablePresenter`, `UserInfolistPresenter`.
- **Pages are flexible**: a resource may have `Manage`, `Create`, `Edit`, `List`, and `View` pages.
- **Actions are optional** and used only when a module needs reusable write operations.
- **No Validators folder**: validation rules and messages stay inside each field method.
- **Shared cross-cutting behavior lives in Traits**: `FilamentActions`, `FilamentHeaderActions`, `FilamentPageBehavior`.

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

#### 2.3 Infolist schema

One method per display entry. Each method returns a single read-only entry.

```php
class UserInfolistPresenter
{
    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/user/strings.infolist.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
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
            ->iconButton()
            ->slideOver();
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
        return [
            CreateAction::make()
                ->icon('heroicon-o-sparkles')
                ->label(__('resources/general/strings.table.action_create')),
        ];
    }
}
```

All `ListRecords` and `EditRecord` pages must use this trait rather than defining `getHeaderActions()` inline.

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
8. **Pages use both traits** — `FilamentHeaderActions` and `FilamentPageBehavior` on all Create/Edit/List pages.
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
            ->when($data['from'],  fn($q) => $q->whereDate('created_at', '>=', $data['from']))
            ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until'])))
        ->indicateUsing(function (array $data): array {
            $indicators = [];
            if ($data['from'])  $indicators[] = __('...date_from')  . ': ' . $data['from'];
            if ($data['until']) $indicators[] = __('...date_until') . ': ' . $data['until'];
            return $indicators;
        });
}
```

**Rules:**
- Always the last filter in the list.
- `->native(false)` is mandatory — forces the custom Jalali picker UI.
- `->locale('fa')` activates Persian calendar.
- `indicateUsing` must return human-readable active-filter chips so users can see the active range at a glance.

---

### 5) `AuthorizesByPermission` trait

Located at `app/Traits/AuthorizesByPermission.php`. Applied on every resource. This is the **entire access control system** for the admin panel.

```php
trait AuthorizesByPermission
{
    public static function canCreate(): bool          { return static::permits('create'); }
    public static function canEdit(Model $r): bool    { return static::permits('edit'); }
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
        return (bool) Permission::forUser($user->id)?->can(static::moduleKey(), $action);
    }
}
```

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
TaskboardResource/Enums/TaskStatus.php
DmsResource/Enums/DocumentStatus.php
LinkResource/Enums/LinkType.php
EnergyResource/Enums/ImpactScore.php
EnergyResource/Enums/ExecutionProcedure.php
...
```

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

### ModuleAnalytics

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

### ModuleAnalyticsChartsLeft / ChartsRight

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

### ManagePreferences

Implements `HasActions` and `HasSchemas`, uses `InteractsWithActions`, `InteractsWithSchemas`, and the `FilamentPreferences` trait. Renders as a custom Livewire view.

```php
public function getPreferencesColumns(): int { return 4; }
```

### Widget sorting convention

`$sort` determines widget order on the dashboard. Lower = higher on the page. `AccountWidget` at `-3` ensures it always appears first regardless of any other widget registered later.

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

### Global search

```php
->globalSearch(true, position: GlobalSearchPosition::Topbar)
->globalSearchFieldSuffix(fn(): ?string => match (Platform::detect()) {
    Platform::Windows, Platform::Linux => 'Ctrl+K',
    Platform::Mac => '⌘K',
    default => null
})
->globalSearchKeyBindings(['command+k', 'ctrl+k'])
->globalSearchDebounce('1000ms')
```

Platform-aware keyboard shortcut suffix is shown next to the search field in the topbar.

### User preferences

Nine panel behaviors are controlled at runtime by the authenticated user's stored preferences:

```php
->sidebarCollapsibleOnDesktop(fn() => $this->getPreference('sidebar_collapsible', false))
->sidebarFullyCollapsibleOnDesktop(fn() => $this->getPreference('sidebar_fully_collapsible', false))
->breadcrumbs(fn() => $this->getPreference('breadcrumbs', true))
->collapsibleNavigationGroups(fn() => $this->getPreference('collapsible_groups', true))
->topNavigation(fn() => $this->getPreference('top_nav', false))
->unsavedChangesAlerts(fn() => $this->getPreference('unsaved_changes_alerts', true))
->topbar(fn() => $this->getPreference('topbar', true))
->spa(fn() => $this->getPreference('spa_enabled', true))
->userMenu(position: fn() => $this->getPreference('user_menu_topbar', false)
    ? UserMenuPosition::Topbar
    : UserMenuPosition::Sidebar)
```

Preferences are read from `Auth::user()->extra['preferences']` (a JSON column):

```php
private function getPreference(string $key, mixed $default = false): mixed
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
->darkMode(false)                               // dark mode is disabled app-wide
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

Global helper trait are autoloaded from `app/trait - FilamentFormDivider.php`. The most relevant ones in Filament context:

### `ExampleFormPresenter::divider()`

This is used in form presenter class and returns a `TextEntry` that renders as a full-width gradient divider line inside infolists:

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

**34. Kill per-row correlated subqueries under `wire:poll` with non-correlated `UNION ALL` derived tables + covering indexes — never `addSelect([subquery])` against the outer row.** The contact-list query (`FetchContactsAction`) was `User::active()->addSelect(['last_message_id' => MAX(id) subquery, 'unread_count' => COUNT(*) subquery])`, where each subquery `whereColumn`'d `users.id` — MySQL plans it as `DEPENDENT SUBQUERY`, re-executing once **per outer users row**. Under `wire:poll.10s` that's `O(N·k)` re-runs per viewer per poll; at 5K users / 500K messages it measured **1103 ms** (vs **8.8 ms** after the fix, **≈125×**). The fix is structural: pre-aggregate into **non-correlated derived tables** and `leftJoinSub` them once — `$sent = MAX(id) GROUP BY recipient_id WHERE sender_id=viewer`, `$received = MAX(id) GROUP BY sender_id WHERE recipient_id=viewer`, `$lastMsgSub = fromSub($sent->unionAll($received))->selectRaw('contact_id, MAX(max_id)')->groupBy('contact_id')`, plus `$unreadSub = COUNT(id) GROUP BY sender_id WHERE recipient_id=viewer AND read_at IS NULL`; `User::active()->select('users.*','lm.last_message_id', DB::raw('COALESCE(uc.unread_count,0) as unread_count'))->leftJoinSub($lastMsgSub,'lm',...)->leftJoinSub($unreadSub,'uc',...)`. `HAVING unread_count > 0` becomes `WHERE uc.unread_count > 0` (it's a real joined column now); users with no messages keep `null`/`0` via `leftJoinSub` + `COALESCE`. **The `OR`-defeats-index pitfall:** the old `WHERE (sender_id=V AND recipient_id=u.id) OR (sender_id=u.id AND recipient_id=V)` made the optimizer **ignore** both directional composites and fall back to `idx_deleted_at` + `filesort`; the same OR in the 1-on-1 thread view (`->latest()->take()`, `ORDER BY created_at`) defeats the directional composite there too — so `idx_sender_recipient_created`/`idx_recipient_sender_created` were load-bearing for **no** query (verified by `EXPLAIN`, not assumed) and were dropped. **Covering indexes that replace them:** `idx_sent_covering(sender_id, deleted_at, recipient_id, id)` + `idx_received_covering(recipient_id, deleted_at, read_at, sender_id, id)` — `deleted_at` in the middle (after the equality prefix) lets `whereNull('deleted_at')` prune in-index, `id` is the implicit InnoDB PK made explicit so `MAX(id)`/`COUNT(id)` resolve `Using index` with **no row lookup**; the `(recipient_id, deleted_at, read_at, sender_id, id)` index covers **both** the received-MAX leg (prefix `recipient_id, deleted_at`, group `sender_id`) and the unread-COUNT leg (prefix `recipient_id, deleted_at, read_at IS NULL`). Drop `idx_recipient_read_at(recipient_id, read_at)` — strict subset of `idx_received_covering`. **Migration ordering gotcha:** if an index backs a foreign key (`messages_sender_id_foreign`/`_recipient_id_foreign`), InnoDB refuses `DROP INDEX` ("needed in a foreign key constraint") — **add the new covering indexes first** (they lead with `sender_id`/`recipient_id`, satisfying the FK), then drop the old composites in a separate `Schema::table` call. `DB::table()` bypasses Eloquent `SoftDeletes`, so `whereNull('deleted_at')` is **mandatory** in every derived leg. Validated on an isolated `perf_benchmark` database (seeded 5K users / 500K messages) via `EXPLAIN` + wall-clock — never trust theory for index choices; seed at scale and measure (`EXPLAIN ANALYZE` on MySQL 8/MariaDB, `EXPLAIN` + timed runs on 5.7). Canonical migration `2026_06_30_000001_optimize_messages_indexes_for_contacts`.

**35. Presenter `->tooltip()` / `formatStateUsing()` closures read a model accessor or eager relation — never issue a per-row query inside a Schema closure.** A `->tooltip(fn($record) => SomeModel::where(...)->...)` (or any query inside `formatStateUsing`) is an N+1: one extra query per rendered row. Move the data-shaping logic into a **model accessor** that consumes a relation already eager-loaded in `getEloquentQuery()` (Rule 18), and let the closure only read `$record->accessor`. Reference: `DMS::readerNamesTooltip` reads the eager `reads` collection (loaded via `DmsResource::getEloquentQuery()->with(['reads.user'])`) and dedupes `user_id` → `User::getCachedNames()` joined with ` ┆`, replacing the per-row `Read::getReaderNamesTooltipForDocument($record->id)` static query that used to sit in `DmsTablePresenter::readCount()`. Same principle as Rule 32 (display reads the model accessor, not an inline re-derivation) and Rule 33 (labels via trait) — the Schema stays pure UI composition, the model stays the single source of truth. Before applying, confirm the relation is eager-loaded with the columns the accessor reads and is **not** scope-constrained to a different row (a `hasMany` by the right FK, unconstrained, is the safe shape).

**36. `use Closure;` is mandatory when a Presenter method declares `: Closure` (or a `Closure` parameter).** Inside a namespaced Filament Presenter class, an unimported `Closure` resolves to `<namespace>\Closure` (nonexistent) — PHP throws a TypeError at call time and the entire admin page 500s. Reference: `LinkFormPresenter` declared a `: Closure` return type without the import and took down the whole Link admin page; siblings `DmsFormPresenter`/`DetailsFormPresenter` already had it. Always add `use Closure;` at the top of any Presenter that references `Closure` in a signature.

**37. Cross-field closure validation rule — attach the SAME closure to both fields and branch on `$attribute`.** To enforce a dependency between two fields (e.g. extra IPs require `internal_url`), return `fn(Get $get) => function(string $attribute, mixed $value, Closure $fail) use($get){…}` from a private static helper and attach it to **both** fields via `->rule(self::extraRequiresInternalUrl())`. Filament's `getValidationRules()` calls `$this->evaluate($rule)` (injects `Get`), and the returned inner closure becomes the Laravel closure-rule called with `($attribute, $value, $fail)`. Branch on `$attribute` inside the inner closure so each field is handled symmetrically and the failure surfaces under the field the user edited. Filter empty tags before the cross-field check so empty/junk tags don't falsely trigger. Reference: `LinkFormPresenter::extraRequiresInternalUrl()`. Distinct from the `ValidationRule` object pattern in §2.1 ("Cross-field validation rule that re-runs when any sibling changes", the `UniqueLiveDocument`/`uniqueLiveRule()` shape) — this is the Laravel closure-rule form for dependencies that don't warrant a dedicated Rule class.

**38. TagsInput `->dehydrateStateUsing` must strip empty/whitespace tags before save.** TagsInput's default dehydrate only TRIMS tags; it does NOT filter empty ones, so `['']` persists to the DB. When storing arrays (IPs, tags), add `->dehydrateStateUsing(fn($state) => is_array($state) ? array_values(array_filter(array_map('trim', $state), fn($v) => $v !== '')) : $state)`. The Link model's read-time `array_filter` in `resolvedIsInternal()` is defense-in-depth, but the form should not persist junk in the first place.

**39. A RelationManager (or Resource) calling `self::createdAtFilter()` (or any `FilamentFilters` helper) must `use App\Traits\FilamentFilters;` AND `use FilamentFilters;` in the class body — `FilamentActions` alone does NOT provide filters.** `createdAtFilter()` / `typeFilter()` / etc. live in `App\Traits\FilamentFilters`, while `viewAction`/`editAction`/`deleteAction`/`bulkActions` live in `App\Traits\FilamentActions`. A class that only imports `FilamentActions` and calls `self::createdAtFilter()` in `->filters([...])` throws a fatal `Call to undefined method ...::createdAtFilter()` the moment the RM table renders its filter row. Mirror the Resource itself: `ChannelResource` imports both (`use App\Traits\FilamentActions; use App\Traits\FilamentFilters;` + `use FilamentActions, FilamentFilters, AuthorizesByPermission;`). Always pair them — if a RM uses any `self::...Filter()`, add the `FilamentFilters` import + trait. Reference: `ChannelMessagesRelationManager` shipped with only `FilamentActions` and fatalled on filter render until `FilamentFilters` was added.
