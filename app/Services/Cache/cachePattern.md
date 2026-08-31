# Automatic Cache Invalidation Pattern

Canonical guide for `App\Services\Cache\ModelCacheVersion`, `App\Services\Cache\SkipsAutomaticCacheVersioning`, and `App\Providers\ModelCacheServiceProvider` — the project-wide, zero-wiring cache-invalidation safety net. Read this before adding any new `Cache::remember`/`Cache::rememberForever` call that reads data derived from an Eloquent model.

## The problem this solves

Before this system, every cache that read model-derived data needed its own hand-wired invalidation: a `booted()` hook calling `Cache::forget('exact_key_string')`, with the key string duplicated between the read site and the hook. Two failure modes recurred:

1. A new `Cache::remember(...)` gets added and the matching `Cache::forget(...)` hook is forgotten, or a model had no cache-invalidation code at all — the classic "admin creates a record, users still see stale data" bug.
2. A write path bypasses Eloquent's per-model events entirely (`Model::insert()`, `Model::upsert()`, `Model::query()->update()`, raw `DB::table(...)`) and a hand-wired hook never fires because it was never reached.

Failure mode 1 is now structurally impossible for any new cache that follows the convention below — every model gets automatic coverage with zero code, including the ones that had none before. Failure mode 2 is a fundamental Eloquent/PHP limitation (see "What this does NOT cover") and still needs one explicit line — but that line is now trivial and hard to get wrong.

A real, live instance of failure mode 1 was found and fixed while building this: `Department` and `Skill` both wrapped their cached option lists in `once()` while *also* having a `booted()` hook that reactively `Cache::forget()`d them — `once()`'s in-process memo can't see a `Cache::forget()`, so a php-fpm worker kept serving stale Department/Skill dropdown options for up to `pm.max_requests` requests after an edit. Both are fixed as part of this migration (see "The `once()` gotcha" below).

## How it works

`ModelCacheVersion` stores one lightweight "version token" per model class (`Cache::forever("model_cache_version:{$modelClass}", $token)`, where `$token` is a microsecond-precision timestamp string plus a random tie-breaker — deliberately a plain string, not a float, since a 16-digit microsecond timestamp exceeds PHP float precision and would risk two rapid bumps rounding to the same value). `ModelCacheServiceProvider::boot()` registers three Eloquent **wildcard** event listeners — `eloquent.saved: *`, `eloquent.deleted: *`, `eloquent.restored: *` — that fire for **every model in the app, automatically, with zero per-model code**, and call `ModelCacheVersion::bump(get_class($model))` immediately (synchronously, not deferred to `DB::afterCommit()` — see "Why not `DB::afterCommit()`" below).

Any cache key built via `ModelCacheVersion::key($modelClass, $suffix)` embeds the current version token. The moment that model's version bumps, every `key()` call for it returns a **different string** — the old cache entry is never explicitly deleted, it's simply orphaned (unreachable through `key()` again, expires naturally via its own TTL or the store's normal eviction). **Do not assert "the old key is gone" in a test** — assert that `key()` now returns something different, or that a fresh `Cache::remember()` through the new key recomputes. This project's own test suite got this wrong on the first pass (asserting `Cache::has($oldKey)` is false) and had to be corrected — the mechanism never deletes anything, it just stops pointing at it.

This is the same versioned-cache trick `App\Services\Menu\StateService` already uses for the menu-badge cache (see `statePattern.md`), generalized to work per-model instead of one global version.

**Why a version token instead of Laravel's native cache tags:** this project's cache store is `database` (`CACHE_STORE=database`, `config/cache.php:18`), which does not support `Cache::tags(...)` — only Redis/Memcached do. The version-token approach needs no tag support and works identically on every driver.

## Why not `DB::afterCommit()`

The first version of this system deferred the bump to `DB::afterCommit()` (theoretically more correct — don't invalidate for a change that might still roll back). This was wrong for this specific codebase: **every** existing cache-invalidation hook here (`Department`, `Skill`, `Permission`, `EnergyTest`, the old `ReservationPolicy`) fires immediately on the event, never deferred — and this project's mandatory test convention wraps every DB test in `DB::beginTransaction()`/`DB::rollBack()` (`RefreshDatabase` is broken here, see `coreTestPattern.md` §2). `DB::afterCommit()` callbacks never fire inside a transaction that gets rolled back, so the deferred version was silently untestable under this project's own established pattern — 8 tests failed silently-looking-like-a-real-bug before this was caught. Fire immediately, matching every sibling hook in this codebase.

## Usage — the entire API surface

```php
use App\Services\Cache\ModelCacheVersion;

// Reading through a versioned cache:
$rows = Cache::remember(
    ModelCacheVersion::key(ReservationPolicy::class, 'resource_types_registry'),
    now()->addDay(),
    fn () => ReservationPolicy::query()->whereIn('key', self::DISPLAY_KEYS)->get(),
);

// Manually bumping (ONLY needed for bulk/raw writes — see below):
ModelCacheVersion::bump(ReservationPolicy::class);

// Tier V via the HasModelCache trait (model owns a cached*() method):
//   class Post { use HasModelCache;
//       public static function cachedPins(): Collection {
//           return static::cached('pins', fn () => static::where('pinned', 1)->latest()->take(1)->get());
//       }
//   }   // key = {Post}:v{version}:pins — auto-orphans on Post save/delete; TTL defaults to defaultSeconds()
//   cachedForUser($userId, $suffix, $cb) bakes u{userId}: into the suffix and uses viewerSeconds().

// Tier T global — cross-model aggregate, no version token, 300s config default:
ModelCacheVersion::rememberGlobal('op:module_a:{dept}', fn () => /* … */);
//   = Cache::remember($key, now()->addSeconds(ModelCacheVersion::defaultSeconds()), $cb)
//   For per-dept/per-user Tier T, bake the arg into the key yourself ( Livewire #[Computed(cache:true)]
//   is dead on a directly-called method — see app/Models/modelPattern.md "the one principle" ).

// TTL resolvers (config-driven, env APP_CACHE_TTL, default 300):
ModelCacheVersion::defaultSeconds();  // global/shared analytics
ModelCacheVersion::viewerSeconds();   // max(default, min(3600, user pref seconds)) — user-scoped caches only
```

`bump()`/`version()` are both wrapped in try/catch + `Log::warning` internally — a cache-store hiccup during a bump **never** breaks the save/delete that triggered it, and a failed version read falls back to `'0'` rather than throwing (matches this project's established `CleansAttachedFiles`-style "never let cache/storage failure break the primary operation" convention).

## What this DOES cover, automatically, forever

Every `$model->save()`, `$model->delete()`, `$model->update([...])`, `$model->forceDelete()` (still dispatches `deleted`), and `$model->restore()` call, for **every** Eloquent model in the app except `User` (see "Opting out" below) — including models that had zero cache-invalidation code before this system, and any model created in the future. Nothing needs to `use` a trait or declare anything to get this coverage; it is wired once, globally, in `ModelCacheServiceProvider`.

## Opting out — `SkipsAutomaticCacheVersioning`

`User` is the one model that implements `App\Services\Cache\SkipsAutomaticCacheVersioning`. Its own `booted()` hook already had a deliberate optimization: only bump the option-list caches when `wasChanged(['name', 'role', 'status'])`, specifically so the very-high-frequency `touchLastSeen()` write (gated to at most once per 5 minutes per user by `UpdateLastSeen` middleware, and using `saveQuietly()` anyway so it doesn't even dispatch events) and any other frequent low-significance `User` write don't force a full recompute of the option-list caches on every request. If `User` didn't opt out, the global listener's unconditional bump would defeat that optimization and make those caches effectively never stay warm. A model implementing this marker interface must handle its own bumping entirely — the global listener skips it completely, not partially.

Only add this marker for a model with a *proven*, *existing* reason to be more selective than "bump on every write" — it is not a general-purpose performance escape hatch, and the default (no marker, full automatic coverage) is correct for every other model in the app today.

## What this does NOT cover (and cannot — this is a PHP/Eloquent limitation, not a gap in this system)

Eloquent **only** dispatches model events for per-instance `save()`/`delete()`. These bypass events entirely, so the global listener never fires for them:
- `Model::insert([...])` (bulk insert — used by `TypeProvisioner::provision()`)
- `Model::upsert([...])` (used by `ReservationPolicyResource\Pages\EditPolicy::handleRecordUpdate()`)
- `Model::query()->update([...])` / `Model::query()->delete()` (mass update/delete — used by `Resource`'s cascade cleanup of a deleted type's policy rows)
- Raw `DB::table(...)->...` queries

For these, call `ModelCacheVersion::bump($modelClass)` manually, once, right after the write — every production write path in this codebase that does this already has the call wired (see the three examples above). This is the **only** remaining manual step in the whole system, and it replaces what used to require remembering an exact `Cache::forget('the_same_key_string_as_the_read_site')` call — now it's just "bump the model class," no key string to keep in sync.

This project already has an established, separate compensating convention for the same underlying limitation — `App\Services\Menu\StateService::flush()` is called manually from ~10 Livewire Actions that do bulk mutations (see `statePattern.md`'s "Limitations / inherent drift" section). That convention is unrelated to this system (different cache, different concern) but the *shape* of the problem — bulk ops bypass events — is identical, and the fix is the same shape: one explicit call at the bulk-write site.

## The `once()` gotcha still applies — read this before wrapping a versioned cache in `once()`

`adminPanelPermissionLogicPattern.md`'s Caching section already documents: **never wrap `Cache::remember(...)` in `once(...)` for any cache that gets reactively invalidated** — `once()` memoizes in a PHP `static` that survives for the whole php-fpm worker process (up to `pm.max_requests`), with no way for a version bump to invalidate that in-process memo. This system does not change that rule — a versioned cache wrapped in `once()` has the identical bug: the worker keeps serving its `once()`-memoized value even after the version changes, until the worker recycles. `Department::getCachedOptions()`/`getCachedModels()`/`getCachedOptionsExcludingEmptyTickets()`/`anyHasCustomTicketOptions()` and `Skill::cachedActiveCatalog()` had this exact bug and had their `once()` wrapper removed as part of this migration. Only wrap in `once()` if the underlying data is genuinely deploy-time-static and never needs to react to a model change within a running worker's lifetime — `Permission::availableModules()` is the one remaining example (a filesystem glob of `Resource.php` files, nothing ever calls `Cache::forget`/bump on it, left untouched).

## Performance characteristics

**Write side:** every model save/delete now does one extra `Cache::forever()` write (a single indexed upsert on the `database` driver's `cache` table), unconditionally, even for models nobody has built a versioned cache against yet. Deliberate simplicity-over-micro-optimization tradeoff — the alternative (a manual allowlist of "which models are worth watching") reintroduces exactly the kind of registry-you-can-forget-to-update this system exists to eliminate.

**Read side:** a versioned cache read costs **two** cache-store round trips, not one — one to resolve the current version (`ModelCacheVersion::version()`), one for the actual `Cache::remember()`/`Cache::get()` call through the resolved key. This is a real, measured 2x vs. the old bare-literal-key approach (verified via `DB::getQueryLog()` in `ReservationTest::test_disabled_types_helper_issues_bounded_cache_lookups_when_warm`). No in-process memoization was added to collapse this to one round trip, on purpose — any memo that survives across requests (a bare `static` property, `once()`) risks the exact same-across-workers staleness bug this whole system exists to eliminate, and this project has already been burned by that once (see the `once()` section above). Two cheap indexed lookups on an internal ERP tool's realistic traffic is an accepted cost for correctness; do not "optimize" this without re-reading why it's shaped this way.

## Adopting this for a new cache

1. Identify which model's data the cache is derived from.
2. Build the key with `ModelCacheVersion::key(ThatModel::class, 'a_descriptive_suffix')` instead of a bare string literal.
3. If the write path is a normal `$model->save()`/`->delete()`, you're done — nothing else to wire.
4. If the write path is bulk/raw (see above), add one `ModelCacheVersion::bump(ThatModel::class)` call right after it.
5. Never wrap the `Cache::remember(...)` call in `once()` unless the data is genuinely deploy-time-static.

## What's been migrated onto this system

`ResourceType` (registry cache + `forgetCache()`), `ReservationPolicy`/`ValidationService` (`getPolicies()`, `disabledTypes()`, `flushPolicyCache()` — the `booted()` hook was removed entirely, replaced by the global listener plus the three explicit bulk-write bump calls above), `Department` (all 4 option/model caches, `once()` removed), `Skill` (`cachedActiveCatalog()`, `once()` removed), `Permission` (`forUser()`/`cacheKey()` — `availableModules()`/`permission_modules` deliberately left untouched), `User` (all 4 option-list caches — opts out of the global listener via `SkipsAutomaticCacheVersioning`, keeps its own selective `booted()` hook, simplified to one `ModelCacheVersion::bump()` call), `EnergyTest` (`getAverageScoresForUser()`). Left untouched, by design: `DmsKeyGrouper`/`UserKeyGrouper`/`HasDmsCountHelpers`' `dms_document_counts` — these are documented TTL-only-by-design caches with no reactive invalidation intended (see `filamentPattern.md`'s DMS section), not an oversight.
