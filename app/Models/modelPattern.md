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
| `HasThreadMessages` | Message, ChannelMessage | `attachmentUrls()`, `toggleReaction()` (one reaction per user, raw update — shared by the Channel and DM reaction actions since 2026-11-17), sanitizer `body()` attribute, `sender`/`replyTo`/`replies` thread relations (`static::class` resolves to the using model) |
| `HasBoardRanking` | Task | `nextRank`/`rankForPriority`/`bulkAssignRanks`/`scopeInRankColumn`/`rerankIfMoved` |
| `HasTaskApproval` | Task | approval predicates (`isPendingApproval`, `isFrozenFor`, `isGenuinelyDone`), save-guards (`guardTicketLock`, `guardApprovedFreeze`, `guardPastDeadline`), status/archive/approval side effects, `canChangeStatus`/`isFrozen`/`canReopen`/`canDelete` attributes |
| `HasCycleStep` | Task | `cycleLink` per-instance memo, `setCycleLink`/`cycleStep()`/`isCycleLocked()` |
| `HasProjectFollowUps` | Project | the 12 `saved`-hook follow-ups (creation/membership/settings/owner logging, `releaseUsers`, `stripTaskPeople`, `stripWorkflowAssignees`, `ensureActiveOwner`, channel-membership sync) |
| `HasPresence` | User | `isOnline`, `lastSeenLabel`, `onlineSince`, `scopeOnline`, `touchLastSeen`, `markOffline` (uses `User::ONLINE_MINUTES`) |
| `HasExtraPreferences` | User | `getExtraValue`/`getPreference`/`setExtraValue` + `extra()` attribute + decode/merge privates; the single rule for the admin `extra` bucket: `adminBucketOf`/`editableExtraOf` (static, minus `PROTECTED_ADMIN_KEYS`) and `adminExtra()`/`editableExtra()` (instance) used by the user form, the Departments relation manager, the infolist and `EditUser` |
| `HasBookingRows` | User | `bookingRows`/`bookingMax` + `booking()` attribute |
| `HasTodaysDesk` | User | today's-desk static cache + label/summary helpers |
| `HasActive` | AiReport, Letterhead (plus the other consumers, see the pin test) | `active` fillable/cast init plus `scopeActive`/`scopeInactive` |
| `HasSharedRecipients` | AiReport, Correspondence (limits read `config('ai.{quotaFeature()}.*')`; the consumer must also use `HasDailyQuota`; Correspondence overrides `scopeSharedWith` (non-draft/saved/withdrawn rows whose `recipients` or `parties` hold the user) and overrides `scopeUnseenBy`/`markSeenBy` to throw `LogicException` — its seen-by lives on `parties[].opened_at`) | recipients list, replace-share, `isRecipient`, shared-visibility scope, `seen_by` tracking (`scopeUnseenBy`, `markSeenBy`) behind the menu badge |
| `HasVersionHistory` | AiReport, Correspondence (`max_versions` via `config('ai.{quotaFeature()}.max_versions')`) | `versions` JSON: `pushVersion` (trim keeps last N and flags `versions[0]['trimmed']`), `lastVersion`, `lastSpecVersion` |
| `HasDailyQuota` | AiReport, Ticket, Correspondence (`correspondence`) | per-user per-day cache counter: `consumeQuota`, `refundQuota` (floored at 0), `quotaUsed`, `quotaCap`; feature-aware via `protected static quotaFeature()` (default `report`, Ticket returns `ticket`): key `ai-{feature}:quota:{uid}:{Y-m-d}`, cap `config('ai.{feature}.daily_cap')` |
| `HasGenerationLifecycle` | AiReport, Correspondence (`stale_minutes`/`no_refund` via `config('ai.{quotaFeature()}.*')`; `tempDirectory()` = `ai-{feature}/tmp/{id}`, AiReport unchanged at `ai-report/tmp/{id}`) | `isStale`, `scopeFreshGenerating($userId)` (owner + `generating` + `updated_at` within `stale_minutes`; the one "is a run in progress" query shared by `GenerateReportAction::assertIdle` and `Main`), `markFailed` (conditional update, the only refund path; refunds the day in the last version's `quota_day`, re-reading `versions` from the DB when the instance came from a column-limited list select), `tempDirectory` |
| `HasLetterheadNumbering` | Letterhead | letter-number allocation: `nextNumber($context)` (must run inside the caller's transaction; locks the ROOT letterhead row — a child with `parent_id` shares its parent's pattern, counters and prefix), read-only `preview()`/`lastNumber()`, token helpers (`numberValues`, `formatNumber`, `counterTokens`, `counterKey`, `nextSequence`, `hasSequence`). Counter scope = the values of the tokens present in the pattern (Jalali `{YYYY}/{YY}/{MM}` from Asia/Tehran, so Nowruz resets), `start_at` only moves forward, a taken number (soft-deleted letters included) advances the sequence up to 25 tries. See `app/Livewire/Dashboard/Correspondence/correspondencePattern.md` §7 |
| `HasUser` | 23 models (see the pin test) | plain `user(): BelongsTo` — default `user_id` FK, byte-identical across consumers |
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
- The five direct-migration models (`Department`, `User`, `Skill`, `Permission`, `EnergyTest` + `Profile::cachedWithDates()`) call `ModelCacheVersion::key()` directly; respelling through `cached()` is optional purity, not required. `Profile::cachedWithDates()` reads through `Profile::scopeCurrent()` (not terminated AND `User::visibleOnBoard()`), the shared "current staff" rule also used by `SpecialDays`; its key already carries the `User` version, so a status/type change refreshes it.

Caching lives at the **model layer**. Livewire `#[Computed]` is per-request memoization only — never a cross-request cache. Livewire's cached-computed path has three structural failures (fixed-string key with no invalidation path; argument-unaware key generation → per-user/dept contamination; direct method calls bypassing the magic-get path so `cache: true` silently never engages) — which is why cross-request caching is model-owned.

## Tier V — versioned, auto-invalidate on write

**Use when:** read-heavy, rare-change, owned by one model — the default for content/catalog/directory/option-list data.

- **Via `cached()`/`cachedForUser()`:** `Post` (pins, items, counts), `Link`, `FAQ` (categories + filter), `Feed` (categories), `Authority` (count), `User` (option lists, names, `headsOfDepartment` via `HasProfileHierarchy` — a `Profile` `department_id`/`position` change bumps `User` from `Profile::booted()`), `Profile::cachedWithDates()` (birthday/anniversary roster; `User` version baked in because the query eager-loads `user:id,name`), `ReportingService::task-report`/`label_options` (`Project` version baked into the suffix).
- **Locks:** `Lock::moduleMap()` / `Lock::recordMap()` (fingerprints only, never hashes; bumped by the global listener and again after commit by `HasLockHooks`; a missing table reads as empty and is never cached) — see `app/Services/Lock/lockPattern.md`.
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

`NotifyLog`, `Read`, `Reaction`, `Comment`, `Reply`, `Message`, `ChannelMessage`, `ChannelMember`, `SkillUser`, `EventShare`, `TaskDetail`, DMS thread mutation, `Task` board, `Reservation` calendar, `AiReport` (generation status is live and polled), `Correspondence` and `Letterhead` (letters are polled/locked rows; the only caches are the quota counter and the generate lock — the letterhead pickers read live), `Ticket` (list + reply counts live), `Poll` (live relation query), `Channel` list, the `FAQ`/`Feed` **lists** (only their categories are Tier V), `Reminder` (zero cache code by design; one per-request static memo `openForUser()` cleared on write — see `reminderPattern.md`).

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
- **Off-label version keys.** `HasUserHooks` bumps `User::PRESENCE_VERSION_KEY` (a plain string, not a model class) when `presence`/`focus_until` change; Pulse reads it for the `presence-changed` event (statePattern "Presence part"). `touchLastSeen()`/`markOffline()` are `saveQuietly` and never bump it; any raw/`toBase()` presence write needs a manual `ModelCacheVersion::bump(User::PRESENCE_VERSION_KEY)` (as `presence:reset-stale` does).
- **Verification.** Snapshot listener counts per model per event before/after any hook refactor and require them identical; `ModelHooksTest` pins the structure (no inline hooks, one model per hooks trait, hooks trait last).

## Current staff, termination lifecycle and hook couplings (2026-11-14)

**One rule, four spellings — keep them in sync.** "Current staff" = active, `users.type != guest`, profile not `terminated` (no profile = current).
- `User::scopeVisibleOnBoard()` (whereDoesntHave a terminated profile); `scopeVisibleOnBoardOr($keep)` is the Filament-select form (an already-saved id stays selectable); `Profile::scopeCurrent()` delegates to it.
- `User::isVisibleOnBoard()` — instance check; reads the profile only when the relation is already loaded (no N+1; safe because a terminated user is always deactivated by the lifecycle below). It can disagree with the scope only for a legacy terminated-but-active row.
- `User::CURRENT_STAFF_SQL` + `User::currentStaff($query)` — raw/joined analytics; needs UNALIASED `users` and `profiles` joins.
- `Department::currentUsers()` (filtered twin; counts) vs `Department::users()` (unfiltered; admin RelationManager).
Change the rule in all four together. New code uses one of them instead of an inline `status` + guest pair; `User::active()` intentionally stays only where a guest owns the data (reminder overdue/due-today, shared events, task due-soon toast).

**Lifecycle (`HasProfileHooks`, `HasUserHooks`).**
- Profile saving: terminated + empty `end_date` => today; leaving terminated clears `end_date` unless edited; `end_date < start_date` throws; a terminated profile (on save, on `created`, or moved to another user) deactivates its user with a nested normal save, user errors re-keyed to `data.employment_status`. Rehire never reactivates.
- User saving: reactivating a user whose profile is terminated throws `terminated_locked` (`data.status`); deactivating/guesting a user who owns a non-project channel throws `owns_channels`. User saved: `cancelFutureReservations()` (active -> inactive or guest), `releaseFromProjectWork()` (non-guest originals).
- Profile saved bumps the `User` cache version on created / `employment_status` / `user_id` / `department_id` / `position`, so every User-keyed cache (incl. `Profile::cachedWithDates()`) rebuilds.
- Hook errors use `data.*` keys so they surface on the Filament form.
- `ProfileFactory` never emits `terminated` (it would auto-deactivate the user); a fixture needing it pins it, and "terminated but still active" is built raw with `Profile::whereKey()->update()` + `ModelCacheVersion::bump(User::class)`.

**Other couplings (private static methods in the hooks traits).** Reservation: status -> cancelled stamps `cancelled_by_id`/`cancelled_at`, -> active clears them, -> released trims a started range's future `end_time` to now. Reminder: `due_at` change clears `notified_at`. Feed: poll votes deleted when the category leaves Poll or the choice list changes (packed settings keep votes). Channel: a new owner is attached only if not already a member (join dates kept). Skill: `is_active` un-ghosts. Department: `code` locked after creation (`data.code`); delete blocked (`ValidationException`, key `department`) while referenced by profiles/authorities/faqs/photos/reports/reviews/task_details, sub-departments, tickets (`extra->target_department`), or JSON audiences (feeds/photos/posts/projects/reports/suggestions `departments`, `dms.owners`, `notify_rules.extra.departments`). Report: `expires_at >= report_date`; `ReportNudge` hides expired reports; the form's `department` and `departments` stay independent.

**Enum-select gotcha.** Filament dehydrates `Select::options(SomeEnum::class)` to the enum OBJECT; a model attribute without a cast holds that instance, so `=== 'string'` and `isDirty()` in hooks are wrong on admin saves (votes dropped, reservations cancelled, end dates filled on every edit). Any attribute edited through an enum Select needs the `set:` mutator that stores a `BackedEnum`'s `->value` (as `Task` does; now on `User.status`/`type`/`role`, `Profile.employment_status`/`position`, `Reservation.status`, `Feed.category`; the holder guard's `isDirty(['role', …, 'extra'])` (a non-developer admin cannot change a holder account's role/status/type/email/password or its `extra` bucket: signature image, sign code, registry permission) and the User cache bump on `position` depend on it).

**Known limits.** A report that expires naturally keeps its bells until the report is next touched. The department delete guard still counts soft-deleted rows in the plain tables (projects and tickets via `extra->target_department` are checked through their models). Rolling the code back does NOT undo cancelled reservations, deleted poll votes, moved ownership, auto-filled end dates or deactivated users.

**Deploy.** No migrations. Bump the `User` cache version once (`php artisan optimize:clear` / cache flush); reset OPcache and restart plain queue workers. On EVERY install run `SELECT COUNT(*) FROM users u JOIN profiles p ON p.user_id=u.id WHERE p.employment_status='terminated' AND u.status='active'`; a non-zero count must be deactivated through a model save (channel owners reassigned first) before or right after deploy, because those people are hidden from lists but can still log in (Fateh production is clean).

## Correspondence and Letterhead (2026-11-09)

Full contract: `app/Livewire/Dashboard/Correspondence/correspondencePattern.md`. Model-layer facts:

- **Column sets.** `Correspondence::LIST_COLUMNS` (no heavy JSON: never `spec/versions/events/parties/search_text/meta`), `ROW_COLUMNS` (side list + table rows; the waiting tab adds `route_mode, parties`, templates add `meta`), `ADMIN_COLUMNS` (Filament table). Single-record reads (PDF, link page) select `CorrespondencePdf::renderColumns()` instead. Status consts + `STATUS_COLORS`/`STATUS_ICONS`; `DRAFT_LIKE` = draft, generating, failed; `COMMENTABLE` = shared, received, routed. `$fillable` excludes number, status, parties, events, content_hash, approved_at, search_text, meta, pdf_*, `is_template`. `Letterhead::kindLabels()`, `defaultStyle()`, `defaultConfig()`, `defaultMargins($size)` are the only defaults; `counters` is never fillable.
- **`HasCorrespondenceHooks`** (declared last): `updating` frozen-edit guard (original status `approved/superseded/void/done` freezes `spec, subject, number, number_seq, letter_date, letterhead_id, type, direction, sign_mode, content_hash, approved_at`; exits only `approved→superseded|void`, `superseded→void`, `void`/`done` terminal; `received/routed` stay editable for the registry clerk); `saving`: linked-record validation (letterhead kind/active, reply/supersedes cycle guard), column allow-lists + `plain` scrub of subject/their_number/their_date, body sanitising + `search_text` (stored HTML is not re-sanitised, new HTML runs the full `Correspondence::sanitizeHtml` pipeline and the size caps), tag clean-up; `saved/deleted/restored`: `StateService::flush()` after commit when status/awaiting/recipients/parties changed (not draft↔draft); `forceDeleted`: removes `correspondence/{id}` and the temp dir.
- **`HasLetterheadHooks`**: allow-lists (kind, size, lang, sign_layout, `start_at`), pattern validity (tokens only, `{SEQ}` required on a root), image paths under `letterheads/` and unique per letterhead, parent rules (one level, same kind, no children when it gets a parent, no kind change once it has letters/children), borrow rules (`config.header_from`/`footer_from` = an active `head` letterhead that borrows nothing and is not itself), delete guard (no letters incl. trashed, no children, not borrowed), image clean-up after commit.
- **Quiet writes are part of the model contract.** Delivery bookkeeping on an approved letter (`parties`, `events`, `pdf_path`, `pdf_hash`) is written with `SendCorrespondenceJob::writeQuiet()` — a raw `toBase()->update()` that skips casts, hooks and `updated_at` (so the frozen guard, the menu flush and the list order are not triggered). Do not "fix" it into `save()`.

## Enums

- **Home.** All enums live flat in `app/Enums` (`App\Enums`); there are no per-resource `Enums/` folders. A new enum is a new file there, never under `app/Filament/Resources/<X>/` or a Livewire module.
- **One enum per concept.** A set of cases that differs between uses only in presentation (label, icon, colour, font) is ONE enum with methods (`getLabel()`/`getIcon()`/`getColor()`, or Filament `HasLabel`/`HasIcon`/`HasColor`), never two near-identical enums.
- **Catalogues stay separate.** Icon/option catalogues (`SkillIcon`, `LinkIcon`, `ResourceTypeIcon`) are their own enums because they are the stored choice.
- **DB-backed.** `ResourceType` (a `final class` that mimics an enum from a table) overrides `tryFrom()`/`cases()` and keeps one entry point with `forgetCache()`; do not split it.
- **Merging is its own step.** Merging two enums needs identical backing values AND identical meaning, and touches stored values and model casts, so it is a separately reviewed change, never part of a move or rename.
- **Tests.** One master test per enum that carries logic, in `tests/Feature/Enums/<Name>EnumTest.php`.