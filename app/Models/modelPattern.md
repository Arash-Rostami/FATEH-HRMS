# Model Pattern — traits, hooks, and the V / T / L caching tiers

Governing guide for every model in `App\Models`: where shared logic lives (Concerns traits), where lifecycle hooks live (Hooks traits), and how derived data is cached (three tiers). Read `cachePattern.md` first — it documents the versioned-key primitive (`ModelCacheVersion`) this builds on. A model is classified into a tier **at the moment it gains a cache**, and the classification is recorded here.

## Trait layout

- **`app/Models/Concerns/Has*.php`** — every Eloquent-only model trait: namespace `App\Models\Concerns`, `Has*` name. Non-`Has` model traits are legacy; rename on touch. Cross-domain traits shared with Filament/Livewire/Presenter classes (`CleansAttachedFiles`, `StoresAttachedFiles`) stay in `app/Traits/` (see `traitPattern.md` §3).
- **`app/Models/Concerns/Hooks/Has<Model>Hooks.php`** — lifecycle hooks, one trait per hooked model, declared **last** in the model's trait list (details at the bottom).
- **Trait list shape:** one `use` statement per model, one trait per line, comma-terminated, hooks trait last (the `Task`/`User` shape) — never a one-line list, never several `use` statements.

## Shared concerns registry (2026-10-30)

Cross-model duplicates live as traits in `app/Models/Concerns/`. Composition is pinned by `ModelHooksTest::test_shared_model_concerns_are_composed_by_their_expected_models` — extend that map when adding a trait or a consumer.

| Trait | Consumers | Owns |
|---|---|---|
| `HasUrgencyState` | Task, Ticket | `urgencyState` pipeline (deadline → floor → idle → round) |
| `HasThreadMessages` | Message, ChannelMessage | `attachmentUrls()`, sanitizer `body()` attribute, `sender`/`replyTo`/`replies` thread relations (`static::class` resolves to the using model) |
| `HasBoardRanking` | Task | `nextRank`/`rankForPriority`/`bulkAssignRanks`/`scopeInRankColumn`/`rerankIfMoved` |
| `HasTaskApproval` | Task | approval predicates (`isPendingApproval`, `isFrozenFor`, `isGenuinelyDone`), save-guards (`guardTicketLock`, `guardApprovedFreeze`, `guardPastDeadline`), status/archive/approval side effects, `canChangeStatus`/`isFrozen`/`canReopen`/`canDelete` attributes |
| `HasCycleStep` | Task | `cycleLink` per-instance memo, `setCycleLink`/`cycleStep()`/`isCycleLocked()` |
| `HasProjectFollowUps` | Project | the 12 `saved`-hook follow-ups (creation/membership/settings/owner logging, `releaseUsers`, `stripTaskPeople`, `stripWorkflowAssignees`, `ensureActiveOwner`, channel-membership sync) |
| `HasPresence` | User | `isOnline`, `lastSeenLabel`, `onlineSince`, `scopeOnline`, `touchLastSeen`, `markOffline` (uses `User::ONLINE_MINUTES`) |
| `HasExtraPreferences` | User | `getExtraValue`/`getPreference`/`setExtraValue` + `extra()` attribute + decode/merge privates |
| `HasBookingRows` | User | `bookingRows`/`bookingMax` + `booking()` attribute |
| `HasTodaysDesk` | User | today's-desk static cache + label/summary helpers |
| `HasUser` | 22 models (see the pin test) | plain `user(): BelongsTo` — default `user_id` FK, byte-identical across consumers |
| `HasDepartment` | 7 models (see the pin test) | `department(): BelongsTo` on `department_id` → owner key `code` |

Rules for adding to this registry:

- **Move verbatim or don't move.** Only byte-identical bodies are extracted; differing logic stays per-model (Message vs ChannelMessage unread tallies, Task vs Ticket exempt/priority rules). Extraction is a compile-time composition change — zero dispatch difference — which is what makes it regression-free.
- **Template traits use overridables, not forks.** `HasUrgencyState` declares abstract `urgencyDeadline()` / `urgencyPriorityFloor()` and default no-op `urgencyExemptState()` / `applyExtraUrgency()`; each model overrides the varying step in its own class (class-beats-trait).
- **Framework-trait collisions are fatal.** Laravel's `Prunable` defines both `prunable()` and `prune()`, so those must stay inline in the model class — moving them into a concern composed alongside `Prunable` is a fatal trait-method collision. Same check applies before extracting anything a framework trait already defines.
- **Explicit-FK copies stay per-model.** `Read::user()` passes `'user_id'` explicitly; it was deliberately left out of `HasUser` (verbatim rule) even though the FK is the default.
- **Relation traits are single-purpose.** One trait per relation shape (`HasUser`, `HasDepartment`) — never a bundle trait, or models gain relations pointing at columns they don't have.

## The lever — `HasModelCache` + `ModelCacheVersion::remember()`

```php
use App\Models\Concerns\HasModelCache;
use Illuminate\Support\Collection;

class Post extends Model
{
    use HasModelCache;

    public static function cachedPins(): Collection
    {
        return static::cached('pins', fn () => static::with('user')->where('pinned', 1)->latest()->take(1)->get());
    }
}
```

- `cached(string $suffix, Closure $callback, $ttl = null)` → `ModelCacheVersion::remember(static::class, $suffix, $ttl ?? default, $callback)`. Key is `{$class}:v{version}:{$suffix}` — auto-orphaned when that model's version bumps (the wildcard `saved/deleted/restored` listeners in `ModelCacheServiceProvider`). Default TTL: `config('app.cache_ttl')` (300s, env `APP_CACHE_TTL`).
- `cachedForUser(int $userId, string $suffix, Closure $callback, $ttl = null)` — bakes `u{$userId}:` into the suffix and defaults TTL to `ModelCacheVersion::viewerSeconds()`.
- A model carries `use HasModelCache;` **when it grows a `cached*()` method** — the trait is inert otherwise; never sprinkle it for uniformity.
- **Viewer-scoped keys:** a cached list/count reading through `HasAudience`'s `forViewer()` bakes the department code into the suffix (`pins:{dept}`, `total_count:{dept}`, …) — a dept-free key would leak one department's hidden rows to another.
- The five direct-migration models (`Department`, `User`, `Skill`, `Permission`, `EnergyTest` + `Profile::cachedWithDates()`) call `ModelCacheVersion::key()` directly; respelling through `cached()` is optional purity, not required.

Caching lives at the **model layer**. Livewire `#[Computed]` is per-request memoization only — never a cross-request cache. Livewire's cached-computed path has three structural failures (fixed-string key with no invalidation path; argument-unaware key generation → per-user/dept contamination; direct method calls bypassing the magic-get path so `cache: true` silently never engages) — which is why cross-request caching is model-owned.

## Tier V — versioned, auto-invalidate on write

**Use when:** read-heavy, rare-change, owned by one model — the default for content/catalog/directory/option-list data.

- **Via `cached()`/`cachedForUser()`:** `Post` (pins, items, counts), `Link`, `FAQ` (categories + filter), `Feed` (categories), `Authority` (count), `User` (option lists, names, `headsOfDepartment` via `HasProfileHierarchy` — a `Profile` `department_id`/`position` change bumps `User` from `Profile::booted()`), `Profile::cachedWithDates()` (birthday/anniversary roster; `User` version baked in because the query eager-loads `user:id,name`), `ReportingService::task-report`/`label_options` (`Project` version baked into the suffix).
- **Per-user derived counts (900s, `ModelCacheVersion::remember`):** `DMS::pendingCounts`, DMS filter-chip groups, `EnergyTest::company_averages`, `Feed`/`Post::total_count`, `Post::non_pinned_count`, `DatabaseNotification unread_menu_groups`.
- `HasCountdown` is Tier-V-equivalent via a manual `Cache::forget('countdown:active')` in its save-hook (60s).
- **Cross-model drift rule:** when the state change writes a DIFFERENT model than the versioned one, the acting code adds one targeted `Cache::forget(key)` (see `ConfirmReadAction` — forgets only the acting user's key, never a flush-style bump), or — when the write goes through the writing model's own lifecycle — a `booted()` hook on the writer bumps the versioned class (see `Profile::booted()` → `bump(User::class)`). Full bump is correct when writes are rare and rebuilds are cheap pluck queries.
- Raw/bulk writes (`insert`/`upsert`/`query()->update`/`DB::table`) bypass events → add one explicit `ModelCacheVersion::bump()` after the write (`HasProjectFollowUps::syncTaskDetailProjectName()` bumps `TaskDetail`; `presence:reset-stale` bumps `User`).

## Tier T — TTL-only, default 300s

**Use when:** no single model to version-key on (cross-model joins/aggregates), **or** the underlying field is written via `saveQuietly` (version never bumps → versioning silently incomplete), **or** an ERP analytics SLA where short staleness is accepted.

- `NotifyRule` (`notify:rules`, 1 day, manual flush on save/delete), `User` presence/`last_seen` (60s), `Suggestion` stage aggregates (300s — `stage` written via `saveQuietly`), `DMS` document counts (900s + manual flush), `Permission::availableModules` (1 day, filesystem rebuild), `Review`, `Onboarding`, HR analytics (`HrAnalyticsService` via `rememberGlobal('hr:…')`), `StructuralChart`/`OperationalChart` module aggregates, `ModuleAnalyticsWidget` (the one tolerated `#[Computed(seconds: N, cache: true)]` no-key no-param shape), `TabPresenter::teamPulse()` (`home_team_pulse`, 60s, dept-free — a User × Profile aggregate no single write invalidates), admin Contact tab badges (`contact_stats_badges`, 300s, `once()`-wrapped, deliberate TTL lag).
- **Per-user TTL override:** the preferences slider stores seconds in `users.extra.preferences.cache_ttl`; `ModelCacheVersion::viewerSeconds()` = `max(config default, min(3600, pref))` — viewer can only raise, up to 60 min. Applies to `cachedForUser()` only; global/shared aggregates keep the config default (a per-user TTL would race on the shared key).
- **Parameterized Tier T:** bake the argument into the key — `ModelCacheVersion::rememberGlobal("{$widget}:module_{$x}:{$dept}", …)`. Genuinely org-global aggregates use a dept-free key AND a closure that does not `use $departmentCode`.

## Tier L — live / per-request memo only

**Use when:** high-churn append stream (version flips on nearly every request) or cheap indexed queries where caching costs more than it saves. Plain `#[Computed]` (per-request memo), never `cache: true`, never `Cache::remember`.

`NotifyLog`, `Read`, `Reaction`, `Comment`, `Reply`, `Message`, `ChannelMessage`, `ChannelMember`, `SkillUser`, `EventShare`, `TaskDetail`, DMS thread mutation, `Task` board, `Reservation` calendar, `Ticket` (list + reply counts live), `Poll` (live relation query), `Channel` list, the `FAQ`/`Feed` **lists** (only their categories are Tier V), `Reminder` (zero cache code by design; one per-request static memo `openForUser()` cleared on write — see `reminderPattern.md`).

## The rules

1. **Classify before you cache.** Tier V/T/L is recorded here at the time the cache is written; "not yet cached" models are "not yet classified".
2. **Livewire `#[Computed]` = per-request memo.** Plain, delegating to a model `cached*()` (V) or service (T). Never `cache: true` with a literal `key:`; never `cache: true` on a parameterized method. Tolerated shape: `#[Computed(seconds: N, cache: true)]`, no key, no params. The local guard test enforces both bans.
3. **`saveQuietly` ⇒ Tier T (or fix the write).** Today's quiet fields: `User::last_seen` (`HasPresence::touchLastSeen()`/`markOffline()` — every logout path backdates `last_seen` via `markOffline()` on the `Logout` listener so the 10-min online window drops the user instantly) and `Suggestion::stage` (`HasStageHelpers`). `touch()`/`withoutEvents()` count as quiet.
4. **Parameterized cache ⇒ argument in the key** (`cachedForUser`, or interpolate into the suffix).
5. **Bulk/raw writes ⇒ one manual `bump()`.**
6. **Never wrap a versioned cache in `once()`** — the in-process memo survives the version bump until the worker recycles.

## Gotchas

- **Relation ordering.** A relation that bakes `orderBy(...)` in (`HasReplies::replies()`) makes any later `->latest('id')` a third sort key — `first()` returns the earliest row. Override a relation's default orders with `->reorder()` first (fixed 2026-09-10 in `ProjectNudge`/`ProjectToast`/`TaskInfolistPresenter`).
- **Prunable collision.** `prunable()`/`prune()` must stay in the model class — see the registry rules above.
- **Jalali month buckets.** Any month-scoped model query uses `jalaliMonthBounds()`, never `whereMonth` (Gregorian months are wrong for Jalali-only business rules).

## Verification shape

- A pin/edit on a Tier V model changes `ModelCacheVersion::key(...)` — assert the key differs, not that the old key is deleted.
- A parameterized cache with two argument values produces two different keys/results.
- `php artisan optimize:clear` once post-deploy to drop orphaned literal keys.
- Trait extraction: composition pinned by `ModelHooksTest`; per-trait behavior stays in each `<Model>ModelTest`.

## Lifecycle hooks live in `Concerns/Hooks/Has<Model>Hooks` (2026-10-29)

A model never registers `static::creating/…/forceDeleted` inline — hook bodies live in `App\Models\Concerns\Hooks\Has<Model>Hooks`.

- **Shape.** The trait's only public surface is `protected static function bootHas<Model>Hooks(): void`, registering listeners in the original order. Each callback is a `private static` method with a unique descriptive name (two traits defining the same private name is a fatal — grep first). Non-hook statements (global scopes) stay in the model's `booted()`.
- **Order.** Hooks trait declared LAST — trait boots run in declaration order, so last preserves the original listener order.
- **Return values are behaviour.** A moved callback returns exactly what the original did (a `false` cancels halting events; non-null stops later listeners) and carries no return type unless the original declared one.
- **Verification.** Snapshot listener counts per model per event before/after any hook refactor and require them identical; `ModelHooksTest` pins the structure (no inline hooks, one model per hooks trait, hooks trait last).