# Filament 5 Module Structure Guide

This document describes the preferred module structure for a Filament PHP 5 resource. The goal is to keep each concern isolated, reusable, easy to scan, and consistent with the Livewire module pattern used elsewhere in the codebase.

Filament’s resource model is centered on a main resource class plus dedicated `Pages`, and the framework expects tables to hold columns, filters, and actions, while forms and infolists define schema-driven UI. Relation managers are the standard way to manage related records inside a resource. citeturn0search0turn0search2turn0search11

## Core idea

Each module is organized around a main `Resource` class that acts as the entry point for the module. The resource should only compose the module and define page registration, query behavior, and high-level layout. All detailed UI definitions should live in dedicated module classes.

The resource does **not** contain all form, table, or infolist definitions inline. Instead, it calls static methods from dedicated schema classes.

## Recommended structure

This structure follows the same naming style as the Livewire module pattern: `Schemas`, `Actions`, and optional module-specific helpers. It avoids generic names like `Form.php` or `Table.php`, which become ambiguous when many modules exist.

```text
App/
└── Filament/
    └── Resources/
        └── UserResource/
            ├── UserResource.php
            ├── Pages/
            │   ├── ManageUsers.php
            │   ├── CreateUser.php
            │   ├── EditUser.php
            │   └── ViewUser.php
            ├── Schemas/
            │   ├── UserFormPresenter.php
            │   ├── UserTablePresenter.php
            │   └── UserInfolistPresenter.php
            ├── Actions/
            │   └── ...
            ├── Exports/
            │   └── UserExporter.php
            ├── Enums/
            │   └── Status.php
            └── RelationManagers/
                └── RolesRelationManager.php
```

### Key structural rules

* **Filters are merged into `UserTablePresenter.php`** — no separate Filters folder.
* **Forms, Tables, and Infolists live under `Schemas/`**.
* **The name is intentionally module-qualified**: `UserFormPresentater`, `UserTablePresentater`, `UserInfolistPresentater`.
* **Pages are flexible**: a resource may have `Manage`, `Create`, `Edit`, `List`, and `View` pages depending on the workflow.
* **Actions are optional** and used only when a module needs reusable write operations.
* **No Validators folder**: validation rules and validation messages stay inside each form method component.

### Why this structure works

* fewer ambiguous class names
* consistent with the Livewire pattern already used in the codebase
* easy to scan in an IDE when there are many modules
* keeps Filament-specific structure predictable
* reduces boilerplate duplication while staying readable

## Responsibility of each part

### 1) Main Resource

The main resource is the module orchestrator.

It should:

* define the model
* define the navigation icon and sort order
* define the high-level schema composition: note schema is sections, accordion, ... sits here not inside table or form.
* define query behavior
* define page routes
* delegate form/table/infolist logic to schema classes
* connect only true cross-cutting traits when necessary
* note that all relationships available MUST be eager-loaded inside this resource class as well as table and form for performant and less resource intensive experience.


Example responsibilities:

```php
class UserResource extends Resource
{
    use HandleActivation; // only for true cross-cutting behavior

    protected static ?string $model = User::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    UserFormPresentater::name(),
                    UserFormPresentater::email(),
                    UserFormPresentater::isActive(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
}
```

### 2) Schema classes (Dedicated Classes, NOT traits)

The `Schemas/` folder contains the reusable UI definitions for the resource.

These classes are the Filament equivalent of your Livewire presentation layer: they contain pure UI composition, not business writes.

#### 2.1 Form schema

A form schema class should contain **one method per input component**.

It is a standalone class such as `UserFormPresenter` and is called statically from the resource.

Validation rules and validation messages should live inside each field method itself.

Example:

```php
class UserFormPresenter
{
    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/user/strings.form.name'))
            ->required()
            ->maxLength(255)
            ->validationMessages([
                'required' => __('resources/user/strings.form.name_required'),
            ]);
    }
}
```

#### 2.2 Table schema

A table schema class should contain:

* column definitions
* filter definitions
* row actions when they are shared at the module level
* bulk actions when they are shared at the module level
* table-level presentation logic
* note that all relationships available MUST be eager-loaded inside table and form for performant and less resource intensive experience.


Important: the table is for browsing and managing data. Since the infolist handles read-only viewing, **table columns should default to hidden unless they are essential**. Only absolute essentials such as ID, name, status, and a few operational fields should be visible by default. Everything else should be toggleable and hidden by default for a simpler UI.

Example:

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
}
```

#### 2.3 Infolist schema

An infolist schema class should contain **one method per display entry**.

Each method returns a single read-only entry with formatting and display behavior.

Example:

```php
class UserInfolistPresenter
{
    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/user/strings.infolist.created_at'))
            ->dateTime('M Y | D: H:i:s')
            ->color('gray')
            ->placeholder('-');
    }
}
```

### 3) Actions

Optionally use module actions only when a write operation is reusable or complex enough to deserve extraction.

This keeps the Filament resource clean and consistent with the Livewire module pattern.

An action should:

* contain a single public execution method
* receive the needed form data or model instances
* avoid component-specific UI state
* avoid dispatching toasts, redirects, or tab changes directly unless the module explicitly treats it as presentation logic

Example:

```php
class StoreUserAction
{
    public function execute(array $data): User
    {
        return User::create($data);
    }
}
```

### 4) Export class

Each resource that supports export should have a dedicated exporter class.

It should:

* define export columns
* define custom state formatting
* define the exported file name
* reuse shared export defaults when needed

Example:

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

### 5) Enums

Shared logic that is reused across form, table, and infolist should be placed in enums when it represents a fixed domain state.

This is useful for:

* statuses
* sources
* types
* color mapping
* icon mapping
* tooltip mapping
* computed labels

Example:

```php
enum Status: string implements HasColor, HasLabel, HasIcon
{
    case Active = 'active';
    case Inactive = 'inactive';
}
```

Common enum responsibilities:

* return color based on state
* return icon based on state
* return label based on state
* compute state from record relationships
* return localized tooltip text

### 6) Relation managers

If the resource has related records, each relationship gets a dedicated relation manager.

A relation manager should:

* represent one relationship only
* define its own table behavior
* reuse module-level schema helpers where useful
* define relationship-specific header actions
* define relation-specific row actions and bulk actions
* redirect create/edit actions to the correct resource page when needed
* note that all relationships available MUST be eager-loaded inside the table and form for performant and less resource intensive experience.

* Example:

```php
class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';
}
```

## Page structure

Use the default Filament pages when they fit the workflow:

* `Manage`
* `Create`
* `Edit`
* `List`
* `View`

Filament’s `Pages` directory is the intended place for these page components, and they are full-page Livewire components. citeturn0search0turn0search12

Example page registration:

```php
public static function getPages(): array
{
    return [
        'index' => ManageUsers::route('/'),
        'create' => CreateUser::route('/create'),
        'edit' => EditUser::route('/{record}/edit'),
        'view' => ViewUser::route('/{record}'),
    ];
}
```

## Layout pattern

The resource should define the layout only at a high level.

Typical pattern:

* resource defines sections and order
* schema classes define each field, column, and entry
* table schema defines columns and filters
* infolist schema defines read-only items

Example:

```php
public static function form(Schema $schema): Schema
{
    return $schema->components([
        Section::make()
            ->schema([
                UserFormPresenter::name(),
                UserFormPresenter::email(),
                UserFormPresenter::status(),
            ])
            ->columnSpanFull()
            ->columns(2),
    ]);
}
```

## Naming conventions

Use consistent, module-qualified names instead of generic names.

### Schema methods

* `name()`
* `email()`
* `status()`
* `createdAt()`
* `updatedAt()`

### Schema classes

* `UserFormPresentater`
* `UserTablePresentater`
* `UserInfolistPresentater`

### Enum methods

* `getFromRecord()`
* `getAllFromRecord()`
* `getColor()`
* `getIcon()`
* `getLabel()`
* `getTooltip()`

## Design rules

1. **Unified Schemas folder**

    * All form, table, and infolist schema logic must live inside `Schemas/`.
    * `Presentation/` is acceptable as an alternate team convention, but `Schemas/` is preferred here.

2. **No Validators folder**

    * Validation lives inside each form method component.
    * Validation messages and rules stay close to the field they belong to.
    * Do not create a separate `Validators/` folder.

3. **Filters belong to Table schema**

    * No separate Filters folder.

4. **Module-qualified naming**

    * Use names like `UserFormPresentater.php`, not generic `Form.php`.

5. **Resource is the orchestrator**

    * The resource only composes schema and page structure.
    * It does not define field or column logic directly.

6. **One method = one component**

    * Each field, column, and display entry is isolated in a schema class.

7. **Pages are flexible**

    * A module can include Manage, Create, Edit, View, or List pages depending on workflow.

8. **Actions are optional**

    * Use `Actions/` only for reusable or complex write operations.

9. **Shared logic belongs in Enums**

    * Business states, labels, icons, and colors must be in enums.

10. **RelationManagers remain separate**

    * Each relationship gets its own manager.

11. **Exports are isolated**

    * Export logic must remain independent in `Exports/`.

12. **Localization is mandatory**

    * All labels, messages, tooltips, placeholders, and validation text must use translation keys.

13. **Table defaults should be sparse**

    * Only absolute essentials should be visible by default.
    * All non-essential columns should be toggleable and hidden by default.
    * The infolist should be the primary place for complete read-only viewing.

## Example workflow

### Example module: UserResource

#### Form schema example

```php
class UserFormPresentater
{
    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->required()
            ->maxLength(255)
            ->validationMessages([
                'required' => __('resources/user/strings.form.name_required'),
            ]);
    }
}
```

#### Table schema example (includes filters)

```php
class UserTablePresentater
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
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function email(): TextColumn
    {
        return TextColumn::make('email')
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status');
    }
}
```

#### Infolist schema example

```php
class UserInfolistPresentater
{
    public static function name(): TextEntry
    {
        return TextEntry::make('name');
    }
}
```

#### Resource composition example

```php
class UserResource extends Resource
{
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            UserFormPresentater::name(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                UserTablePresentater::id(),
                UserTablePresentater::name(),
                UserTablePresentater::email(),
            ])
            ->filters([
                UserTablePresentater::statusFilter(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            UserInfolistPresentater::name(),
        ]);
    }
}
```

### When adding a new field

1. Add a method in the form schema class.
2. Add the same field in the infolist schema class if read-only display is needed.
3. Add the same field in the table schema class if it should be visible in listing.
4. Reference the method from the resource layout.

### Example

```php
// Form schema helper
public static function code(): TextInput
{
    return TextInput::make('code')
        ->required()
        ->maxLength(50);
}

// Resource
Section::make()->schema([
    UserFormPresentater::code(),
]);
```

## Why this structure is useful

* easier to read
* easier to test
* easier to reuse across resources
* easier for AI to understand and extend
* keeps the main resource clean
* reduces duplication
* keeps naming consistent with the Livewire module pattern

## Final rule

The resource file is the main presenter for the module. The actual form, table, infolist, action, export, enum, and relationship logic should be kept in dedicated classes and only composed in the resource. Above all, all eager loaded relationships must be present where relevant to reduce system load. The design MUST be 100% technically optimized, rather than conventionally uniform.

Table views should stay intentionally sparse by default, with only the absolute essentials visible. The infolist is the primary place for complete record inspection.

Last point that is super important, the primary language of the entire app, particularly filament php admin panel, inputs, labels, messages, notifications, validation messages and ... is in FARSI (Persian) and direction is RTL.
