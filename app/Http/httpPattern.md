# HTTP Middleware Layer — app/Http/Middleware

Three middlewares, each one behavioral rule. (Controllers live in `app/Http/Controllers/`: single-action JSON endpoints only — `ToggleCalendarController` and `ProjectPulseController`, the latter documented in `app/Livewire/Dashboard/Project/projectPattern.md` "Performance pass 2026-08-28"; tests: `tests/Feature/Http/ProjectPulseControllerTest.php` and `tests/Feature/Http/ToggleCalendarControllerTest.php`, one dedicated file per controller.)

## 1. `UpdateLastSeen` — presence throttle + inactive-session boot (2026-10-02)

- Registered twice: `bootstrap/app.php` `appendToGroup('web', ...)` (user panel) and the admin panel's `->middleware([...])` in `AdminPanelProvider` — so it runs for **both panels**.
- Two jobs, in order:
  1. **Inactive boot.** If the authenticated user's `status` is not `active` (`User::isActive()`, single source), log out the `web` guard and redirect to `/login`. This seals sessions that were created *before* an admin deactivated the user — a logged-in user is booted on their very next request, in either panel.
  2. **last_seen throttle.** Otherwise `touchLastSeen()` at most once per `User::TOUCH_MINUTES` (3), keyed `last_seen:{id}` in cache. All "online" derivation reads `last_seen` through ONE window, `User::ONLINE_MINUTES` (10) — `isOnline()`, `scopeOnline()`, the Contacts "online" filter, Home's team pulse, the admin Users stats and HR analytics all use `User::onlineSince()` (2026-10-14; before this it was 5 min in the user panel and 15 min in admin/HR) — never a boolean column. The touch interval stays well under the window (3 vs 10) so an active user can never flicker offline. `User::markOffline()` (Logout listener) backdates `last_seen` to `ONLINE_MINUTES + 1` minutes, so sign-out is instant under any window value.
- Guard nuance: it logs out `Auth::guard('web')` because both panels' guards resolve to `web` (Filament default guard); do not switch to `$request->user()->logout()`-style calls here.

## 2. The inactive-user gate — three seals, one source (2026-10-02)

A user with `status` other than `active` cannot hold a session anywhere. Enforced at three independent points so no path slips through:

1. **User-panel login** — `App\Livewire\Auth\Login::login()` checks `isActive()` right after `Auth::attempt()` and logs out + errors on `email` otherwise.
2. **Admin-panel login** — `App\Filament\Pages\Auth\Login` (subclass of the vendor page, registered via `->login()` in `AdminPanelProvider`) overrides `authenticate()`, calls the parent, then re-checks `Filament::auth()->user()->isActive()` and logs out + throws `ValidationException` on key `data.email` (the vendor page's own error key).
3. **Every live request** — `UpdateLastSeen`'s boot above.

Tests: `tests/Feature/Auth/LoginTest.php` (user panel), `tests/Feature/Filament/AdminLoginTest.php` (admin panel). `UserFactory` defaults `status => 'active'` deterministically — a random default silently made any login-asserting test flaky once this gate existed; tests that need an inactive user pin it.

## 3. `EnsureHasPermission` — admin-panel route gate

Role-first permission model with developer bypass. Full model: `app/Providers/Filament/adminPanelPermissionLogicPattern.md`.

## 4. `EnsureUserModulePermission` — alias `module:{name}` (user panel)

Per-module view gate on user-panel routes (`bootstrap/app.php` alias): guests → login, developers → always pass, everyone else needs a `Permission::forUser()` row with `can($module, 'view')`, else 403. This is the user-panel counterpart of the admin panel's `EnsureHasPermission`.