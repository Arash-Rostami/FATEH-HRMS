# Tenant/company branding convention

Multi-company white-labeling: switching one env var (`APP_TENANT`) repoints brand text, logos (app + company variants), favicon, per-panel background images, logo reversal, and the auth-page video playlist — no code changes to onboard a new tenant. See also `resources/assets/assetPattern.md` for the underlying folder/file convention.

## Active tenant
`APP_TENANT` env var (default `fateh`), exposed as `config('app.tenant')`.

## `config/tenants.php` is the single source of truth
Every fact about a tenant — text, computed asset paths, behavioral flags — lives in one array keyed by tenant slug. Nothing about a tenant is split across other files. Its own PHPDoc block documents every env var that can override a tenant value. `config/app.php` is a thin two-layer resolver only: `env('APP_X', $tenant['x'])` — env wins if set, otherwise the active tenant's own value, nothing else. There is deliberately no third hardcoded literal fallback in `app.php`; if a key is missing from a tenant's array with no matching env var, it resolves to `null`/an undefined-key warning — acceptable since only `fateh`/`persol` are ever expected as valid `APP_TENANT` values.

`$tenant = (require __DIR__.'/tenants.php')[env('APP_TENANT', 'fateh')] ?? []` in `app.php` is a plain `require`, not `config('tenants...')`, because `app.php` loads alphabetically before `tenants.php` and would read an empty repository otherwise.

Asset-path values inside `config/tenants.php` are computed once per tenant via `tenantAsset(string $tenant, string $type, string $role, ?string $fallback)` / `tenantVideos(string $tenant)` (`app/Helpers/index.php`) — both take the tenant slug explicitly (not read from `env()` internally), since `tenants.php` builds every tenant's full profile up front, not just the active one.

`.env`/`.env.example` brand-text keys (`APP_NAME`, `APP_NAME_EN`, `APP_NAME_ALT`, `APP_ORGANIZATION(_EN)`, `APP_SLOGAN(_EN)`) must stay **commented out**, not blank — Laravel's `env()` treats a present-but-empty key as "set" (returns `''`, not the tenant fallback), so a blank line silently defeats tenant switching for that field. Same rule for `COMPANY_LOGO`/`APP_LOGO_*`/`APP_FAVICON`/`APP_BACKGROUND_IMAGE`.

## Behavioral flags (bool, no env override) — every flag comes in an `admin_*`/`user_*` pair
- `admin_reverse_logo` / `user_reverse_logo` and `admin_use_company_logo` / `user_use_company_logo` are both resolved by one shared helper, `tenantLogo(bool $dark, string $scope = 'user'): string` (`app/Helpers/index.php`):
```php
function tenantLogo(bool $dark, string $scope = 'user'): string
{
    if ($scope === 'admin' && config('app.admin_use_company_logo')) {
        return config('app.company_logo');
    }

    $reversed = config("app.{$scope}_reverse_logo");
    $showDark = $scope === 'admin' && $reversed
        ? $dark === request()->routeIs('filament.admin.auth.login')
        : ($reversed ? !$dark : $dark);

    return config($showDark ? 'app.app_logo_dark' : 'app.app_logo_light');
}
```
  - `admin_reverse_logo`, via `tenantLogo($dark, 'admin')` (called from `FilamentPanelCustomizer::logoHtml()`): when `true`, the logo variant swaps light-for-dark/dark-for-light everywhere in the authenticated admin panel EXCEPT the login page, which renders normally (legacy Persol quirk, preserved as-is from the pre-existing hardcoded formula `$dark === $onLogin`); `false` (fateh) renders light-for-light/dark-for-dark everywhere, login included — no swap at all. The login-route branch only applies for the `admin` scope; the `user` scope has no route awareness at all.
  - `user_reverse_logo`, via the default `tenantLogo($dark)` (scope `'user'`): consumed by two light/dark logo pairs, `card-no-signup.blade.php` and the dashboard header `brand.blade.php` — `asset(tenantLogo(true))` for the `dark:block` slot, `asset(tenantLogo(false))` for the `dark:hidden` slot — the helper decides which actual file (`app_logo_dark`/`app_logo_light`) fills which slot, so the Blade markup never hardcodes the mapping.
  - `admin_use_company_logo` / `user_use_company_logo` replace the *app* logo (the light/dark-varying product brand mark) with the single, fixed `company_logo` in one specific slot each — **`company_logo` has no light/dark variant and never should**, it's one file. The two flags are **not** symmetric inside the helper:
    - `admin_use_company_logo` short-circuits `tenantLogo($dark, 'admin')` itself — returns `company_logo` fixed, skipping the `admin_reverse_logo`/login-route branch altogether (there's nothing to reverse when it's one file).
    - `user_use_company_logo` is deliberately **not** checked by `tenantLogo()` in the `user` scope — it's read directly by `brand.blade.php`, which renders a click-to-flip card (vertical `rotate-x-180` axis) whose front is the fixed `company_logo` and whose back is the app-logo light/dark pair (`tenantLogo(true)` `dark:block` / `tenantLogo(false)` `dark:hidden`); when `false` it renders just that app-logo light/dark pair statically (no flip, no `company_logo`). `tenantLogo()` always returns the plain app-logo variant for the `user` scope regardless of this flag — the flip is the Blade template's job, not the helper's.
  - These are independent of `user_reverse_logo`/`admin_reverse_logo` by construction — a *different* slot (header vs. the light/dark toggle-badge pair), so there's no interaction to keep in sync when one changes.

There is no `app_logo_initial` role — it was removed entirely (deleted from `config/tenants.php`, `config/app.php`, the env-var PHPDoc block) since it was never consumed anywhere in the codebase; dead config, not a mechanism. Don't re-add a key without a real Blade/PHP consumer already in mind.

## Files (auto-discovered, never listed in code) — `resources/assets/{img,video}/<tenant>/`
- **Images**: one file per role, matched by basename regardless of extension — `logo.*`, `light.*`, `dark.*`, `favicon.*`, `user-background.*`, `admin-background.*`. Resolved via `tenantAsset()`, which globs `resources/assets/{type}/{tenant}/{role}.*` and returns the `build/assets/...` path for `asset()`. Missing file for a tenant → falls back to the literal default passed at the call site in `tenants.php`. Note: the glob only checks the tenant's immediate folder, not subfolders — any `extra/` subfolder a tenant keeps for unused/reference assets is correctly ignored.
- **Videos**: any number of files, named `1.<ext>`, `2.<ext>`, ... — no fixed count, a tenant can have 2 or 20. Resolved via `tenantVideos()`, `natsort()`'d into `config('app.videos')`. `auth.blade.php` loops this into its Alpine video-rotation array.

## Two distinct backgrounds, not one shared role
`user-background.*` and `admin-background.*` are separate per-tenant roles (not a single shared `background.*`) since the user-panel auth page and the admin login page are different surfaces that may want different imagery:
- `config('app.background_image')` — consumed directly by `auth.blade.php`'s non-video background fallback `<img>`.
- `config('app.admin_background_image')` — consumed only via the CSS bridge below, since the admin background lives in a compiled CSS file, not Blade.

**Known gap:** neither generic fallback (`resources/assets/img/user-background.*` / `admin-background.*`, no subfolder) exists on disk yet — only hit if a tenant is unset/unknown or missing its own role file, so nothing is broken for `fateh`/`persol` today.

## Admin login background (CSS, not Blade)
`resources/css/core/filament.css`'s `.fi-simple-layout::before` (Filament's built-in login-page background) is a Tailwind-compiled static asset — it can't call `config()` at request time. Bridged via a CSS custom property: the rule reads `background-image: var(--tenant-bg-image, url('/build/assets/img/admin-background.jpg'))`, and `AdminPanelProvider` registers `->renderHook(PanelsRenderHook::HEAD_END, fn() => $this->tenantBackgroundStyle())`, which emits `<style>:root{--tenant-bg-image:url("...")}</style>` from `config('app.admin_background_image')`, scoped to only the login route (guarded by `request()->routeIs('filament.admin.auth.login')`, matching `logoHtml()`'s own route guard). The value is escaped for CSS-string context (`addcslashes(..., '"\\')`), not HTML-entity escaped, since `<style>` is a raw-text element and HTML entities aren't decoded inside it.

## Favicon
`AdminPanelProvider` uses Filament's own `->favicon(fn() => asset(config('app.favicon')))` panel method — not a manually-placed `<link>` tag — since the admin panel renders its own layout and never includes the user-panel's shared `meta-tags.blade.php` partial (which has its own separate `<link rel="icon">` for the user panel).

## Web manifest
`meta-tags.blade.php`'s `<link rel="manifest" href="{{ asset('site.webmanifest') }}">` is served dynamically by `GET /site.webmanifest` (`routes/web.php`, public/no-auth) → `resources/views/components/manifest.blade.php` via `view('components.manifest')` with `Content-Type: application/manifest+json; charset=utf-8`. It reads `config('app.name'/'name_en'/'slogan_en')` and `asset(config('app.favicon'))`, so it is tenant-aware (no static `public/site.webmanifest` — a real file there would shadow the route). The file lives in the components folder but is rendered as a plain Blade view from the route, not as a `<x-manifest>` component. `theme_color`/`background_color` are fixed default-theme literals (`#4e5f66`/`#08191e`) since the manifest is installed once and cannot follow the live theme/mode toggle. Only the `.ico` favicon is listed as a stopgap icon — add 192/512 PNGs to `resources/assets/img/<tenant>/` and reference them here when proper maskable PWA icons are needed.

## Testing
`tests/Feature/Support/TenantHelpersTest.php` covers `tenantAsset()`, `tenantVideos()`, and `tenantLogo()` (both scopes, including the admin login-route exception via a real `$this->get(route('filament.admin.auth.login'))` call) directly against the real `fateh`/`persol` fixture folders — pure-logic, no DB, per `tests/Feature/coreTestPattern.md`.

## Onboarding a new tenant
1. Add `resources/assets/img/<slug>/` with whichever role files apply (any missing role silently falls back to the generic default — no crash).
2. Add `resources/assets/video/<slug>/` with `1.mp4`, `2.mp4`, ... as many as needed.
3. Add one entry to `config/tenants.php` with all the same keys as `fateh`/`persol` — text, computed asset calls, and flags.
4. Set `APP_TENANT=<slug>` in `.env`.

Related: [[brand-identity-config]] (superseded — the old flat env-only model), [[no-comments-preference]].
