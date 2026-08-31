# Model Caching Pattern — the V / T / L tiers

Canonical guide for how every model in `App\Models` caches its derived data. This is the architecture layer above `App\Services\Cache\ModelCacheVersion` (read `cachePattern.md` first — it documents the versioned-key primitive this builds on). The rule a new model follows is picked from one of three tiers below, **before** any `Cache::remember` or Livewire `#[Computed]` is written. Future models must be classified into one of these tiers in this file at the time they gain a cache.

## The one principle

**Model-trait naming & location:** every Eloquent-only model trait lives in `app/Models/Concerns/` with namespace `App\Models\Concerns` and a `Has*` name (`HasModelCache`, `HasReplies`, `HasMenuState`, …). Non-`Has` model traits are legacy; rename on touch. Cross-domain traits shared with Filament/Resource classes (`CleansAttachedFiles`, `StoresAttachedFiles`) and Livewire/Presenter/Filament traits stay in `app/Traits/` (see `traitPattern.md` §3).

Caching in this app lives at the **model layer**, not the Livewire layer. Livewire `#[Computed]` is **per-request memoization only** — never a cross-request cache. Cross-request caching is owned by the model (versioned, auto-invalidating) or, for the named exceptions below, a service (TTL-only). This split exists because Livewire's `#[Computed(cache: true, key: 'literal')]` persists under a fixed string key that **cannot embed a runtime version token** (PHP attribute args are constant expressions) — so it has no invalidation path and is the structural root cause of the "admin pins a post, user panel never shows it" bug. A second failure: Livewire's `generateCachedKey()` is argument-unaware, so `#[Computed(cache: true)]` on a method with a `$dept`/`$userId` parameter collapses every caller onto one shared entry → cross-department/cross-user contamination. A third failure, found in the dashboard widgets: `#[Computed(cache: true)]` only engages through magic-property access (`$this->propName` → `BaseComputed::handleMagicGet` → `Cache::remember`); a method called directly as `$this->getX($arg)` bypasses that path entirely, so the cache **silently never engages** — the attribute is dead decoration and the body runs uncached on every render. `BaseComputed::call()` would throw `CannotCallComputedDirectlyException`, but it only fires on Livewire AJAX wire-calls, not on internal `$this->...()` dispatch. This is exactly why `StructuralChart` / `OperationalChart` were uncached despite wearing `#[Computed(seconds: 300, cache: true)]` — the fix is `rememberGlobal`, not a different `#[Computed]` shape.

## The lever — `HasModelCache` trait + `ModelCacheVersion::remember()`

```php
use App\Models\Concerns\HasModelCache;
use Illuminate\Support\Collection;

class Post extends Model
{
    use HasModelCache;

    public static function cachedPins(): Collection
    {
        return static::cached('pins', fn () => static::with('user')->where('pinned', 1)->latest()->take(1)->get(), now()->addHour());
    }
}
```

`HasModelCache` (in `app/Models/Concerns/HasModelCache.php`) exposes two static helpers on any model that `use`s it:

- `cached(string $suffix, Closure $callback, DateTimeInterface|DateInterval|null $ttl = null)` — delegates to `ModelCacheVersion::remember(static::class, $suffix, $ttl ?? now()->addSeconds(ModelCacheVersion::defaultSeconds()), $callback)`. The key is `{$class}:v{version}:{$suffix}`, so it auto-orphans the moment that model's version bumps (the global `saved/deleted/restored` wildcard listeners in `ModelCacheServiceProvider`). The default TTL is `config('app.cache_ttl')` (300s, env `APP_CACHE_TTL`) — see Tier T.
- `cachedForUser(int $userId, string $suffix, Closure $callback, $ttl = null)` — bakes `u{$userId}:` into the suffix so users never share a cached row, and defaults the TTL to `ModelCacheVersion::viewerSeconds()` (the viewer's preference raised above the config default — see Tier T). Invalidation stays class-wide (safe: the refill is one cheap query).

A model carries `use HasModelCache;` **when it grows a `cached*()` method** — added at the moment the method is added, not preemptively. The trait is inert until `cached()`/`cachedForUser()` is called, so a `use` line on a model that never calls them does nothing; do not sprinkle it for uniformity. The uniform linkage this pattern enforces is the **tier classification** in the table below, not a `use` line on every file.

## Tier V — Versioned (auto-invalidate on write)

**Use when:** read-heavy, changes rarely, owned by one model, and must go fresh the instant it is written. The default for content / catalog / directory / option-list / single-record-detail data.

The cache is built through `cached()`/`cachedForUser()` (or the equivalent direct `ModelCacheVersion::key()` call the five already-migrated models use). No `Cache::forget` to wire, no key string to keep in sync — a `save()`/`delete()` bumps the version and orphans the old entry. For bulk/raw write paths (`insert`/`upsert`/`query()->update`/`DB::table`), add one explicit `ModelCacheVersion::bump(ThatModel::class)` after the write (see `cachePattern.md` "What this does NOT cover").

**Tier V models (current):** `Post`, `Link`, `FAQ` (categories + category-filter), `Feed` (categories), `Authority` (count); already-migrated direct: `Department`, `User` (option lists / names), `Skill`, `Permission` (user-permission key), `EnergyTest`; planned as read-heavy/rare-change: `Profile`, `ProfileDetail`, `Credential`, `Project`, `Report`, `ReservationPolicy`, `Resource`, `Event`, `Ad`, `ReleaseRequest`. `HasCountdown` is Tier V-equivalent via a manual `Cache::forget('countdown:active')` in its `bootHasCountdown` save-hook (60s TTL, already correct).

The five already-migrated models call `ModelCacheVersion::key(self::class, …)` directly and keep working — respelling them through `$this->cached()` is optional API purity with zero behavior change and is **not** required.

## Tier T — TTL-only (default 300s)

**Use when:** the data has no single model to version-key on (cross-model joins / aggregates), **or** it depends on a `saveQuietly` field whose write bypasses the bump (so versioning would be silently incomplete), **or** it is an ERP analytics dashboard where a short staleness window is the accepted SLA. The cache key is a plain literal (optionally with the parameter baked in — see "parameterized Tier T" below); there is no version token, no auto-invalidation, just expiry.

**Default TTL = 300 seconds** (5 minutes) — the project-wide ERP-analytics convention, sourced from `config('app.cache_ttl')` (env `APP_CACHE_TTL`, default 300) so it is tunable per-deploy without code changes. The two justified exceptions that stay shorter because they are time-sensitive: `User` presence / `last_seen` (60s, `saveQuietly`) and `HasCountdown` (60s). The one justified exception that stays longer because it is very stable and rebuilds from a filesystem glob: `Permission::availableModules()` / `permission_modules` (1 day). `DMS::dms_document_counts` is 900s with a manual `Cache::forget` on save (Tier T with a V-style manual flush).

**Per-user TTL override (wired):** the manage-preferences panel (`FilamentPreferences` trait) exposes a `cache_ttl` slider displayed in minutes (5/10/15/30/60) but **stored as seconds** (300/600/900/1800/3600 via `dehydrateStateUsing`) in `users.extra.preferences.cache_ttl`. `ModelCacheVersion::viewerSeconds()` returns `max(config default, min(3600, pref))` — `pref` is the stored seconds value, so the viewer can only **raise** the TTL up to the 60-minute (3600s) ceiling, never below the app default. This applies cleanly to **user-scoped** caches via `cachedForUser()` (each user owns their own key + expiry). **Global / shared** analytics (HR, the module-analytics widgets, structural/operational charts) keep the `config` default: a per-user TTL there would race on the shared key (whoever writes first sets the expiry for everyone), so they are built with `cached()` / plain `Cache::remember` using `defaultSeconds()`, not `viewerSeconds()`.

**Parameterized Tier T — bake the argument into the key.** A per-department / per-user aggregate must include that argument in the cache key, never rely on Livewire's arg-unaware computed key. The lever is `ModelCacheVersion::rememberGlobal(string $key, Closure $fn, $ttl = null)` — a plain `Cache::remember` under a caller-chosen key, defaulting to `config('app.cache_ttl')` (300s), with no version token (correct for cross-model aggregates that no single model save can reliably invalidate). Scope-varying modules bake the arg: `rememberGlobal("{$widget}:module_{$x}:{$dept}", fn () use ($dept) => […])`; modules that are genuinely org-global (`module_g` posts, `module_c` suggestions, `module_d` reservations — no department column in the query) use a dept-free key and a closure that does not `use $departmentCode`. No service class needed while only two widgets use it (`StructuralChart`, `OperationalChart`).

**Tier T data (current):** `User` presence/last_seen (60s); `Suggestion` stage aggregates (300s — `HasStageHelpers` writes `stage` via `saveQuietly`); `DMS` document counts (900s + manual flush); `Permission::availableModules` (1 day); HR analytics 17 methods (`HrAnalyticsService::getHrXData()` via `rememberGlobal('hr:hr_x', …)`, 300s; the 4 `Hr*Chart` widgets are now thin uncached `return app(HrAnalyticsService::class)->getHrXData();` delegators — the prior per-widget `#[Computed(seconds: 300, cache: true)]` was dead because `getData()` called them directly, bypassing the computed cache); `StructuralChart` / `OperationalChart` module aggregates (300s via `rememberGlobal`; `$dept` baked into the key for scoped modules e/f/h/i/a/b, dept-free key for the org-global modules g/c/d — the prior `#[Computed(seconds: 300, cache: true)]` was dead and ran uncached on every render); `ModuleAnalyticsWidget` 24 single-table aggregates (300s, no-key no-param — the tolerated tier-3 shape); `Poll` results would-be-T but see Tier L; `Review` (300s); `Onboarding` (300s).

## Tier L — Live / per-request memo only (no cross-request cache)

**Use when:** the model is a high-churn append stream where the version would flip on nearly every request (so a versioned cache never hits), **or** the derived query is cheap and indexed (counts, unread tallies) and caching costs more than it saves. Use a plain `#[Computed]` (per-request memo, Livewire's `$requestCachedValue ??= …`) — never `cache: true`, never a `Cache::remember`. For tallies that are expensive enough to want a window, a short TTL is acceptable, but versioning is the wrong tool.

**Tier L models (current):** `Read` (written on every feed view by every user), `Reaction`, `Comment`, `Reply`, `Message`, `ChannelMessage`, `ChannelMember` (membership/unread pivot), `SkillUser`, `EventShare`, `TaskDetail`, `DMS` thread mutation, `Task` board (`TaskBoard\Main` uses plain `#[Computed]`, no cross-request cache — a status flip re-queries fresh next render), `Reservation` calendar (`Reservation\Main` uses plain `#[Computed]` — a booking re-queries fresh next render), `Ticket` (list is live; reply counts are live via the `Reply` high-churn stream), `Poll` (results are a live `loadMissing('polls')` relation query — 300s stale vote counts is bad UX), `Channel` list (membership-scoped, live), and the `FAQ` / `Feed` **lists** themselves (filtered/paginated/user-scoped → live; only their **categories** are Tier V).

## The rules

1. **Classify before you cache.** Every new model that gains a cache is placed in Tier V / T / L in the table above, in this file, at the time the cache is written. Future models are not "uncached by default" — they are "not yet classified"; the first cache they get picks the tier and is recorded here.
2. **Livewire `#[Computed]` = per-request memo only.** Plain `#[Computed]`, delegating to a model `cached*()` method (Tier V) or a service (Tier T). Never `cache: true` with a literal `key:`. Never `cache: true` on a method with a parameter. The one tolerated shape is `#[Computed(seconds: N, cache: true)]` with **no `key:` and no method parameters** — a short-TTL aggregate that is contamination-free and has no fixed-literal-key staleness (the `ModuleAnalyticsWidget` pattern). The local guard test enforces both bans.
3. **Tier V is the default for read-heavy / rare-change model data.** Tier T is the named exception for cross-model / `saveQuietly` / analytics. Tier L is for high-churn appends and cheap indexed queries.
4. **`saveQuietly` ⇒ Tier T (or fix the write).** If a field is written with `saveQuietly()` / `updateQuietly()` / `increment(..., quiet)` / `touch()` / `withoutEvents()`, the version never bumps on that write, so a Tier V cache over it is silently incomplete. Either accept TTL (Tier T) or add an explicit `ModelCacheVersion::bump()` at the write site. The two `saveQuietly` fields today: `User::last_seen` (`User.php:410`), `Suggestion::stage` (`HasStageHelpers.php:73`).
5. **Parameterized cache ⇒ argument in the key.** Any cache varying by `$dept` / `$userId` / `$id` bakes that argument into the key suffix. `cachedForUser($userId, …)` does this for users; for other args, interpolate them into the suffix string.
6. **Bulk/raw writes ⇒ one manual `bump()`.** `insert` / `upsert` / `query()->update` / `DB::table` bypass events (see `cachePattern.md`).
7. **Never wrap a versioned cache in `once()`.** See `cachePattern.md`'s `once()` gotcha — the in-process memo survives the version bump until the worker recycles.

## The banned-pattern guard

A local test (gitignored `tests/` tree) scans `app/Livewire/**/*.php` and `app/Filament/Widgets/**/*.php` and fails if it finds:

- any `#[Computed(` attribute containing `key:` with a literal string (the pinned-post bug signature);
- any `#[Computed(` attribute containing `cache: true` on a method whose signature has a `$` parameter (the contamination signature).

It explicitly does **not** flag `#[Computed(seconds: N, cache: true)]` with no `key:` and no parameters — the tolerated tier-3 aggregate. The guard is local-only until promoted to CI.

## Verification shape

- A pin/edit on a Tier V model changes `ModelCacheVersion::key(ThatModel::class, $suffix)` and a fresh `cached*()` recomputes through the new key (assert the key differs, not that the old key is deleted — see `cachePattern.md`).
- A parameterized Tier T cache with two different argument values produces two different keys and two different results (locks the contamination fix).
- `php artisan optimize:clear` once post-deploy to drop orphaned literal keys (`dashboard.posts.pins`, `dashboard.posts.item.*`, `faq-categories`, `feed-categories`, `authority-global-count`, `lw_computed:*`, etc.).