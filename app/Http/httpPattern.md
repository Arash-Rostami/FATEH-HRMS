# HTTP Middleware Layer — app/Http/Middleware

Five middlewares (`UpdateLastSeen`, `EnsureHasPermission`, `EnsureUserModulePermission`, `EnsureTenantModuleOn`, `SecureLetterLink`), each one behavioral rule (§2 documents the inactive-user gate that spans them, §6 is the public-letter-link stack). (Controllers live in `app/Http/Controllers/`: single-action JSON endpoints — `ToggleCalendarController`, `IconMissController` (`POST /icon-miss`, public icon-font miss report: string-only name, once-a-day log line, `icon-miss` limiter = 20/min per IP + 60/min global; see `resources/icons/iconsPattern.md`; test `IconMissTest.php`) and `ProjectPulseController`, the latter documented in `app/Livewire/Dashboard/Project/projectPattern.md` "Performance pass 2026-08-28"; tests: `tests/Feature/Http/ProjectPulseControllerTest.php` and `tests/Feature/Http/ToggleCalendarControllerTest.php`, one dedicated file per controller.)

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

## 5. `EnsureTenantModuleOn` — alias `tenant.on:{key}` (2026-11-02)

Per-company switch gate: `abort_if(tenantOff($key), 404)`, so a route can be turned off per tenant in `config/tenants.php` `modules` without an `if` in the routes file. First user: `Route::middleware(['guest', 'tenant.on:password-reset'])` around the forgot/reset pages (Fateh off, Persol on; full list of what the switch touches in `config/tenantPattern.md`). The key is read at request time, so it works with a cached route table. Test: `tests/Feature/Http/HttpRoutesTest.php`.

## 6. `SecureLetterLink` — alias `letter.link`, the public letter-link stack (2026-11-09)

The only unauthenticated, non-JSON controllers: `CorrespondenceLinkController` (`GET /c/{id}-{token}` → `correspondence.link`, `/pdf` → `correspondence.link.pdf`, an outside recipient's private view/PDF of an approved letter) and, for contrast, the authenticated `CorrespondencePdfController` (`GET /correspondence/{id}/pdf` → `correspondence.pdf`, in the `auth` group, `canView`, `private, no-store`) and `CorrespondenceAttachmentController` (`GET /correspondence/{id}/attachment/{index}` → `correspondence.attachment`, same `auth` group, delegates to `DownloadAttachmentAction`: `canView`, path under `correspondence/{id}/`, file must exist, 404 on any miss; PDF inline, other types download). The link routes sit in ONE group in `routes/web.php`: middleware `letter.link` + `throttle:letter-link` (`RateLimiter::for('letter-link')` in `FortifyServiceProvider`: 20/min per IP + 120/min global), `withoutMiddleware` of `EncryptCookies`, `AddQueuedCookiesToResponse`, `StartSession`, `ShareErrorsFromSession`, `ValidateCsrfToken` and `UpdateLastSeen` (no session, no cookies, no CSRF, no presence touch), and route constraints `id = [1-9][0-9]{0,9}`, `token = [A-Za-z0-9_-]{43}`. `SecureLetterLink` stamps `SecureLetterLink::HEADERS` on every response (`X-Robots-Tag: noindex, nofollow`, `Cache-Control: no-store`, `Referrer-Policy: no-referrer`, `nosniff`, `X-Frame-Options: DENY`, `frame-ancestors 'none'`); the controller reuses the same constant for downloads, and the `bootstrap/app.php` renderer turns any 404/429 under `c/*` into an empty body with those headers. Rules: identical empty 404 for every miss (missing, wrong token, revoked, expired, unapproved, confidential); only the sha256 of the token is stored (`parties[].token_hash` / `token_hash_next`, `hash_equals`); a GET records the open under a row lock through `SendCorrespondenceJob::writeQuiet`, HEAD never does; the tenant switch is NOT checked here (links already sent keep working). `TRUSTED_PROXIES` must be set or the per-IP limiter sees the proxy. Full contract: `app/Livewire/Dashboard/Correspondence/correspondencePattern.md` §12.

## 7. File protection grades — pick one per module, no fourth pattern (2026-11-10)

Every stored file belongs to exactly one of three grades. A new module chooses one; do not invent another mechanism (no per-module signed routes, caches or ad-hoc rewrites).

1. **OPEN** — public disk via `StoresAttachedFiles`/`storeAttachment` + `FileNamer::stored`; reachable by the direct `/storage/<folder>/…` URL. For content meant for everyone: avatars, feed, gallery, link icons, onboarding, letterheads, post images.
2. **PRIVATE BY APACHE** — same public-disk storing code, PLUS an authorized route (permission lookup, then serve; the `Dms\Main::getAuthorizedFile`/`serveFile` shape), PLUS one Apache rewrite line in `public/.htaccess` redirecting `/storage/<folder>/` to that route, inserted idempotently by `scripts/setup-container.sh` and checked by `scripts/verify-health.sh`. Admin/developer are served via `hasElevatedRole()`. Example: DMS. When adopting, add the folder name to the scripts' guarded list.
3. **PRIVATE EXCLUSIVE BY APP** — private `local` disk, no public URL at all. Pictures embedded in a page get a short-lived Laravel signed `temporaryUrl` from ONE helper (Correspondence `privateSrc`); files the user clicks go through a permission-checked route/controller (Correspondence attachment + PDF). Example: Correspondence. Grade 3 does not use `StoresAttachedFiles`/`CleansAttachedFiles` (both are hard-wired to the public disk): the module owns its writers and deleters on `Storage::disk('local')` (Correspondence `LetterUploads`, `LetterSeal`, `LetterBody`), names files with `FileNamer::stored`, and serves them only via `DownloadAttachmentAction` / `privateSrc`.

| | 1 Open | 2 Private by Apache | 3 Private exclusive by app |
|---|---|---|---|
| Who may see it | anyone with the URL | users the route permits (+ admin/developer) | users the app permits (staff by login, outsider by secret link) |
| Where stored | public disk | public disk | private `local` disk |
| How served | direct `/storage/…` | authorized route; direct URL redirected by Apache | embedded: signed `temporaryUrl`; clicked: permission-checked controller |
| What protects it | nothing (by design) | route check + `.htaccess` rewrite | no public path; signature stamp + route check |
| On rebuild/deploy | nothing | rewrite line must survive: `setup-container.sh` re-inserts it, `verify-health.sh` checks it; never upload `public/.htaccess` blindly | keep `local` disk `serve => true`; nothing else |
| Choose when | content is public by nature | files must be private but existing public-disk code/links stay | confidentiality matters more than reuse (letters) |

Where things sit today (as far as the code shows): DMS = 2; Correspondence = 3; avatars, feed, gallery, link icons, onboarding, letterheads, post images = 1. Business folders `task/attachments`, `ticket/*`, `release_request/attachments`, `reports/files`, suggestions, `profiles/docs`, resources are currently grade 1 (unguarded) and are candidates for grade 2 later. Related: `app/Livewire/livewirePattern.md` (upload/disk rules, DMS section), `app/Support/supportPattern.md` §5, `temp/winterra/paas-setup.md` §5.