# Bootstrap Configuration (`bootstrap/app.php`)

## `config()` is unsafe inside `withMiddleware()` / `withExceptions()` closures — `env()` only

`Application::configure()->withMiddleware(function (Middleware $middleware) {...})` and `->withExceptions(function (Exceptions $exceptions) {...})` fire via `Container::afterResolving(Kernel::class | ConsoleKernel::class, ...)` at kernel-**construction** time — before Laravel's bootstrappers (including `LoadConfiguration`, which registers the `'config'` container alias) have run. Calling `config(...)` (or `app('config')`) anywhere inside these closures throws `ReflectionException: Class "config" does not exist` — **unconditionally, on every request and every `artisan` command**, not just in a cached-config scenario.

**Only `env()`** (Laravel's helper — reads `$_ENV`/`getenv()` directly, zero container dependency) **is safe to call here.** `trustProxies(at: env('TRUSTED_PROXIES'))` on the `$middleware->trustProxies(...)` line is correct as-is; do not route it through `config('app.trusted_proxies')` even though that key exists in `config/app.php` for use elsewhere in the app.

`env()`'s own real gap: `LoadEnvironmentVariables` skips parsing `.env` entirely once `config:cache` has run (`$app->configurationIsCached()` early-return), so `env('TRUSTED_PROXIES')` returns `null` in a cached-config production deploy **unless the value is also set as a genuine OS/container-level environment variable** (most PaaS dashboards support this, separate from the repo's `.env` file). That's the correct fix for the config-cache gap — not swapping to `config()`, which crashes the app outright rather than just silently dropping the value.

**Incident (2026-09-08):** routing `trustProxies` through `config('app.trusted_proxies')` took the whole app down — 500 on every page, every artisan command failing at boot, verified via a direct `php artisan --version` and a real HTTP request before/after the revert. Don't trust framework-source reasoning alone for a boot-order question in this file — it's cheap to verify empirically (`php artisan --version` after any `bootstrap/app.php` edit) and the failure mode here is total outage, not a soft bug.

## `withExceptions()` — Utime/`CAP_FOWNER` suppression (managed hosting)

The `\ErrorException` report-suppression for `str_contains($e->getMessage(), 'Utime failed')` is a cosmetic-log fix for containers lacking `CAP_FOWNER` (common on managed/PaaS hosting) — Livewire's `@island` directive's `touch()` call fails harmlessly there. Full context, diagnosis steps, and the "why `report()` not `dontReport()`" note (`dontReport()` only accepts exception class names, not closures — throws a `TypeError`) live in `temp/chbkn/setup.md` §13. Don't duplicate that here; this file just anchors the two `bootstrap/app.php` gotchas together.
