# Bootstrap Configuration (`bootstrap/app.php`)

## `config()` is unsafe inside `withMiddleware()` / `withExceptions()` closures — `env()` only

`Application::configure()->withMiddleware(function (Middleware $middleware) {...})` and `->withExceptions(function (Exceptions $exceptions) {...})` fire via `Container::afterResolving(Kernel::class | ConsoleKernel::class, ...)` at kernel-**construction** time — before Laravel's bootstrappers (including `LoadConfiguration`, which registers the `'config'` container alias) have run. Calling `config(...)` (or `app('config')`) anywhere inside these closures throws `ReflectionException: Class "config" does not exist` — **unconditionally, on every request and every `artisan` command**, not just in a cached-config scenario.

**Only `env()`** (Laravel's helper — reads `$_ENV`/`getenv()` directly, zero container dependency) **is safe to call here.** `trustProxies(at: env('TRUSTED_PROXIES'))` on the `$middleware->trustProxies(...)` line is correct as-is; do not route it through `config('app.trusted_proxies')` even though that key exists in `config/app.php` for use elsewhere in the app.

`env()`'s own real gap: `LoadEnvironmentVariables` skips parsing `.env` entirely once `config:cache` has run (`$app->configurationIsCached()` early-return), so `env('TRUSTED_PROXIES')` returns `null` in a cached-config production deploy **unless the value is also set as a genuine OS/container-level environment variable** (most PaaS dashboards support this, separate from the repo's `.env` file). That's the correct fix for the config-cache gap — not swapping to `config()`, which crashes the app outright rather than just silently dropping the value.

**Incident (2026-09-08):** routing `trustProxies` through `config('app.trusted_proxies')` took the whole app down — 500 on every page, every artisan command failing at boot, verified via a direct `php artisan --version` and a real HTTP request before/after the revert. Don't trust framework-source reasoning alone for a boot-order question in this file — it's cheap to verify empirically (`php artisan --version` after any `bootstrap/app.php` edit) and the failure mode here is total outage, not a soft bug.

## `withExceptions()` — Utime/`CAP_FOWNER` suppression (managed hosting)

The `\ErrorException` report-suppression for `str_contains($e->getMessage(), 'Utime failed')` is a cosmetic-log fix for containers lacking `CAP_FOWNER` (common on managed/PaaS hosting) — Livewire's `@island` directive's `touch()` call fails harmlessly there. Full context, diagnosis steps, and the "why `report()` not `dontReport()`" note (`dontReport()` only accepts exception class names, not closures — throws a `TypeError`) live in `temp/chbkn/setup.md` §13. Don't duplicate that here; this file just anchors the two `bootstrap/app.php` gotchas together.

## `withExceptions()` — Livewire renderable: precise error messages for the user panel (2026-09-21)

A `$exceptions->render()` closure presents exceptions through `App\Services\ExceptionPresenter` on two request lanes — **Livewire requests** (`x-livewire` header) get JSON `{message: title+"\n"+body}` (toasted client-side by `resources/js/core/livewire-errors.js`, see `scriptPattern.md` §18), and **full-page loads** with `app.debug` off get the real 500 error view rendered with the presenter's data: `errors.500` receives `trace_id` (the `ERR-…` reference — before this, the layout's "شناسه ردیابی" accordion always showed the literal `TRC-XXXXXXXX` placeholder), plus `heading`/`message` overrides for the presented title/body.

Non-Livewire passthroughs (each deliberate): `HttpExceptionInterface` and `ModelNotFoundException` keep their dedicated error pages (404/403/419/429/503 — ModelNotFound converts to 404 only after renderables run, so it must be excluded explicitly or a missing record renders as a 500), and **`app.debug` on short-circuits BOTH lanes** (the whole renderable returns null) — development keeps Whoops on page loads and Livewire's error overlay with stack traces, so the presented path is exercised only in production or when a test forces `config(['app.debug' => false])`.

**Admin Livewire requests are excluded too** (`$request->is('admin*')` → null, decision 2026-09-21): the toast bridge (`livewire-errors.js`) is deliberately NOT registered on the Filament panel — admin's precise-message path is the trait notifications, and its out-of-trait Livewire failures (filter apply, search, re-render) fall back to Livewire's default modal. The exclusion and the non-registration are a coupled pair — removing either one alone degrades the admin panel (see `scriptPattern.md` §18).

Two rules that are easy to break:

- **The exclusion list is contract, not decoration.** `ValidationException` (Livewire owns inline field errors), `AuthenticationException` (redirect flow), and Filament's `Halt`/`Cancel` (control flow, not errors) must keep bubbling — rendering any of them as a 500 error breaks validation display, login redirects, or Filament action halting. New "not really an error" exception types go into this list.
- **This closure runs at request time, not boot.** The `config()`-at-boot trap (top section) applies only to the closure *registration*; calls inside the renderable callback body (like `ExceptionPresenter::present()` and the `config('app.debug')` gate) execute per-request, after the container is fully booted — safe. Don't move any of it into the registration closure body.
