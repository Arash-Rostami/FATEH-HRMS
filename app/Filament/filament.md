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

#### 2.2 Table schema

Contains: column definitions, filter definitions, group definitions, row actions and bulk actions when shared at module level, and table-level presentation logic.

All relationships MUST be eager-loaded. Table columns default to hidden unless essential. Only ID, name, status, and a few operational fields should be visible by default. Everything else is toggleable and hidden.

Table configuration options to always consider:

- `->groups([...])` — row grouping via `Group::make()` or dedicated grouping class
- `->filters([...])` — comprehensive filters (not just a few — every filter with real UX value)
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
