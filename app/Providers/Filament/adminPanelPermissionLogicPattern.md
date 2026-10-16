# Admin Panel Permission Logic

Reference for the role-first permission model used by the `admin` Filament panel.
Read this before touching anything in the permission chain.

## Mental model: role is the first gate, the Permission row is an admin-only refinement

| Role | Reaches panel? | How module access is decided |
|------|----------------|------------------------------|
| `user` | **No** — `User::canAccessPanel()` `default => false` | n/a |
| `admin` | Yes, **only if a Permission row exists** with `is_super_admin` or non-empty `abilities` | By the Permission row (see tiers below) |
| `developer` | Yes, unconditionally (`canAccessPanel` `developer => true`) | **Super admin by role — sees every module/action, no row, no exclusions.** Bypasses the whole Permission system. |

The Permission row (`permissions` table, one per user via `user_id` unique) is
**admin-only**: the form's user picker is scoped to `role = 'admin'`
(`User::getCachedAdminOptions()`), and `PermissionsRelationManager` only renders
when the owner being edited is an `admin`.

## Panel entry

`User::canAccessPanel(Panel $panel)` (`app/Models/User.php`):
- `developer => true`
- `admin => ($p = Permission::forUser($this->id)) && ($p->is_super_admin || !empty($p->abilities))`
- `default => false` (covers `user`)

`app/Http/Middleware/EnsureHasPermission.php` (in `AdminPanelProvider->authMiddleware`)
runs on every admin request **after** `canAccessPanel` and is the hard door:
- no `$user` → 403
- `developer` → pass through (bypass)
- no Permission row → 403
- row exists but `!is_super_admin && empty(abilities)` → 403 (locked-out guard rail)

## Developer bypass — every gate site

A developer is super admin by role, so each gate short-circuits `true`/skip before
touching `Permission::forUser()`. The bypass lives in **all seven** call sites
(adding an eighth requires adding the same guard there too):

1. `app/Http/Middleware/EnsureHasPermission.php` — `if ($user->isDeveloper()) return $next($request);`
2. `app/Traits/AuthorizesByPermission.php` `permits()` — `if ($user->isDeveloper()) return true;`
3. `app/Models/User.php` `permits()` — `if ($this->isDeveloper()) return true;`
4. `app/Filament/Resources/PermissionResource.php` `canViewAny()` — developer sees the Permission admin
5. `app/Filament/Resources/UserResource/RelationManagers/PermissionsRelationManager.php` `canViewAny()` — developer may administer permissions (also enforces owner role === `admin`)
6. `app/Helpers/index.php` `canAdmin()` — used by Livewire/dashboard nav
7. `app/Http/Middleware/EnsureUserModulePermission.php` — per-module route middleware

## The two admin tiers (both stored on the same Permission row)

`Permission::can(string $module, string $action): bool` (`app/Models/Permission.php`)
is the single evaluator and source of truth:

```php
if ($this->is_super_admin) {
    return !$this->isModuleExcluded($module);
}
foreach ($this->abilities ?? [] as $row) {
    if (($row['module'] ?? null) === $module && in_array($action, $row['actions'] ?? [], true)) {
        return true;
    }
}
return false;
```

**Tier 1 — Super-ish admin:** `is_super_admin = true`, scoped by `excluded_modules`
(denylist). Sees every module except those excluded. **0..~20% exclusions allowed**
— zero is a valid "sees everything" super admin; beyond ~20% the row is really a
restricted admin and must use Tier 2 (enforced by `App\Rules\SuperAdminExclusion`).

**Tier 2 — Regular admin:** `is_super_admin = false`, scoped by `abilities`
(allowlist of `{module, actions[]}`). Sees **only** the listed modules, and only
the listed actions per module.

### Module vs action granularity
- **Module-level**: a module in `abilities` grants access; ≥1 action must be
  selected per module (`actions` CheckboxList is `->required()`).
- **Action-level**: within a granted module, unchecking specific actions denies
  them — the granular second step on top of module-level.

## Form (`PermissionFormPresenter`) — shared by `PermissionResource` and the relation manager

- `isSuperAdmin()` Toggle — `->live()`, `->default(false)`, `->afterStateUpdated`
  nulls the **inactive** side (`state ? 'abilities' : 'excluded_modules'` → null).
- `excludedModules()` — `->visible(fn(Get $get) => (bool)$get('is_super_admin'))`,
  `->rules(fn(Get $get) => $get('is_super_admin') ? [new SuperAdminExclusion()] : [])`.
- `abilities()` Repeater (module Select `->required()->distinct()`, actions
  CheckboxList `->required()`) — `->visible(fn(Get $get) => !$get('is_super_admin'))`,
  plus lockout guard `->required(fn(Get $get) => !$get('is_super_admin'))` so a
  non-super admin can't be saved with zero modules (would 403-lock them out).
- `user()` Select — scoped to `User::getCachedAdminOptions()` (admin role only).

The same `PermissionFormPresenter` backs both `PermissionResource::form()` and
`PermissionsRelationManager::form()` — one edit covers both.

## Data-layer invariant (`Permission::booted()`)

A `static::saving` hook nulls the inactive side regardless of which form path
wrote the row: `is_super_admin` → `abilities = null`; else → `excluded_modules = null`,
so a row never carries a misleading dead-side value (e.g. a super-admin row still
holding a populated `abilities` array `can()` would never consult). This is the
authoritative layer; the form-side `afterStateUpdated` is UX. The same `booted()`
flushes the per-user permission cache on `saved`/`deleted`.

## Resource-level gating (`AuthorizesByPermission` trait)

All 28 resources in `app/Filament/Resources/*Resource.php` `use AuthorizesByPermission`.
The trait maps every Filament authorization method to `permits($action)` in **two layers**:

- `canViewAny / canView / canCreate / canEdit / canDelete / canRestore / canForceDelete …`
  → `static::permits($action)` — page entry and navigation only.
- `getAuthorizationResponse($action, $record)` → allow/deny by ability map: `viewAny,view→view`;
  `create,replicate→create`; `update,reorder→update`; `delete,deleteAny,forceDelete,forceDeleteAny→delete`;
  `restore,restoreAny→restore`; unknown → deny. **This is what in-table row/bulk/header actions hit**
  (Filament v5 `Resource::get*AuthorizationResponse`); the app has no policies, and Filament allows when
  none exists, so a canX-only trait never hid or blocked in-table Delete/Edit. Unauthorized actions now
  auto-hide. Resource-level `canX` overrides (`canCreate=false` on Contact/EnergyTest/ReservationPolicy,
  `canForceDelete=false` on Project) are **not** consulted by it.
- `permits($action)` → `Permission::forUser(Auth::user()->id)?->can(static::moduleKey(), $action)`
  (developer bypass first).
- `moduleKey()` = `Str::snake` of the class basename with the **trailing** `Resource`
  suffix stripped (`substr($base, 0, -strlen('Resource'))`) → `AuthorityResource` →
  `authority`, `EventResource` → `event`. **Must strip the suffix, not the first
  occurrence** — `Str::before($base, 'Resource')` would turn `ResourceResource` into
  `''`, never matching the `resource` key `availableModules()` produces, making that
  module ungovernable for non-super admins. Invariant: every resource's `moduleKey()`
  must be a key in `Permission::availableModules()`. If you add a resource, verify
  both produce the same key.

`PermissionResource` and `PermissionsRelationManager` override `canViewAny()` directly
(super-admin only, plus developer bypass). `PermissionResource` also overrides `permits()`
(`canViewAny() && trait permits`), so **every** page and action — not just entry — is
super-admin/developer only. Nobody can delete their own permission row (`getAuthorizationResponse`
denies `delete`/`forceDelete` when `record->user_id === Auth::id()`: row, edit-header,
relation-manager and bulk delete; bulk uses `->authorizeIndividualRecords()`).
`PermissionFormPresenter` module pickers (`abilities`, `excluded_modules`) drop the `permission`
module (`Arr::except(availableModules(), 'permission')`) so it can't be granted.

**Relation managers** (`app/Filament/RelationManagers/RelationManager.php`, shared base): tab
visibility and row/header/bulk actions follow the **related** model's resource permission
(when it has one); Attach/Detach/Associate/Dissociate (+bulk) follow the **owner** resource's
`update`; related models without a resource keep vendor behaviour (allow) — **owner decision 2026-10-16: intentional, no strict policy**: children that exist only inside a master resource's relation manager (DMS `Read`, Feed `Comment`/`Poll`/`Reaction`, Suggestion `Review`, Channel `ChannelMessage`, Project/User `Workflow`, Event `EventShare`) are viewable and manageable by anyone who can open the master record; do not add per-child permission modules unless the owner asks. An admin lacking the
related module loses that child tab (User page tabs each need that module's `view`; Channel
Members needs `user` view). Overrides of `canViewForRecord()` must call `parent::`. Edit/View pages automatically show a callout naming tabs hidden by these permission checks (`RelationManager::deniedByPermission()` + `ExplainsHiddenRelationTabs`; see filamentPattern.md).

**Cost:** each check = 2 Redis GETs, no DB queries — no `once()` memoization of
`Permission::forUser` (see Caching).

## Validation rules

- `App\Rules\SuperAdminExclusion` (`app/Rules/SuperAdminExclusion.php`) — fails when
  `count(excluded_modules) > max(1, floor(20% × availableModules))` (the `max(1, …)`
  guard keeps a tiny module count from yielding 0). Zero is allowed. Message key:
  `resources/permission/strings.validation.exclusion_too_many`.
- Lockout guard — `abilities` Repeater `required` when `!is_super_admin`. Message key:
  `resources/permission/strings.validation.abilities_required`.
- Per-module `actions` `required` (≥1 action per module) — Filament default message.

## Caching

- `Permission::forUser($userId)` cached 1 day under `user_permission:{$userId}`,
  flushed on the model's `saved`/`deleted`.
- `Permission::availableModules()` cached 1 day under `permission_modules` (derived
  by scanning `app/Filament/Resources/*Resource.php`).
- `User::getCachedAdminOptions()` cached 6h under `user_admin_options`, flushed on
  `User` saved/deleted alongside the other user caches.
- Developers never hit `forUser` (bypass short-circuits first), so no cache entry
  is needed for them.
- **Never wrap `Cache::remember(...)` in `once(...)`** for any cache a `booted()`
  `saved`/`deleted` hook reactively forgets. `once()` memoizes in a PHP `static`
  that lives for the whole php-fpm worker (up to `pm.max_requests=500`) with no API
  to clear it — `Cache::forget` flushes Redis but the worker's `once()` memo keeps
  serving the pre-forget value (repro: after promoting a user to admin,
  `Cache::get('user_admin_options')` returns `null` post-forget, yet the next
  `once()`-wrapped read in the same process still returns the stale pre-promotion
  list; the invalidation logic is correct, `once()` hides it). All four
  `User::getCached*` option-list methods use bare `Cache::remember` for this.
- `Permission::availableModules()` is the deliberate `once()`-wrapped exception:
  nothing calls `Cache::forget('permission_modules')` — the module list is a
  filesystem glob of `Resource.php` files, only changing at deploy time (opcache/
  autoloader reload = worker restart anyway). `once()` collapses the 4-5 calls
  within a single Permission form/table/infolist render (`PermissionFormPresenter`,
  `PermissionTablePresenter`, `PermissionInfolistPresenter`, `ModuleGroup`) into
  one Redis round trip.

## Developer role cannot be granted from the UI

`UserFormPresenter::role()` uses `->disableOptionWhen(fn(string $value) => $value === UserRole::Developer->value)`
— the developer option is **shown** (existing developers display correctly) but
**not selectable**; the role is granted outside the UI (DB / console).

## Verifying (tinker)

Developer — no row needed, sees everything:

```php
\App\Models\User::find(1)->permits('feed', 'view');
\App\Models\User::find(1)->permits('feed', 'delete');
```

Regular admin with `abilities=[authority]`:

```php
\App\Models\User::find(5)->permits('feed', 'view');
\App\Models\User::find(5)->permits('authority', 'view');
```

Exclusion rule bounds (28 modules → max 5 = `floor(0.20 × 28)`):

```php
$fail = fn(string $m) => throw new \RuntimeException($m);
(new \App\Rules\SuperAdminExclusion())->validate('x', [], $fail);
(new \App\Rules\SuperAdminExclusion())->validate('x', range(1, 5), $fail);
(new \App\Rules\SuperAdminExclusion())->validate('x', range(1, 6), $fail);
```

## Things that are intentionally NOT touched

- `Permission::can()` evaluator — correct, the single source of truth.
- DMS module — left untouched per the project security convention.
- `User::canAccessPanel()` role switch — already matches the model; only the
  middleware/`permits` bypass was added so the developer branch works without a row.