# StateLogic — the `App\Services\Menu` notification system

The single reference for the **entire** menu notification mechanism: two coexisting signals that
share one storage (Filament's `notifications` table) but never touch each other. Read this before
editing anything in `App\Services\Menu`, adding an indicator, or wiring a new nudge trigger.

- **Signal 1 — Badge / dot** (`StateService` + `BadgeSyncService` + `Indicators\*`): the permanent,
  aggregate status dot on a menu item. One row **per indicator**. **Pull** — reconciled on menu
  render. Not dismissable. Stays lit the whole time the condition is true.
- **Signal 2 — Record nudge / bell** (`NudgeService` + `ReconcileNudge` +
  `NudgeServiceProvider`): a one-time, dismissable bell entry. One row **per qualifying
  record**. **Push** — reconciled on the record's Eloquent event. Never resurfaces once dismissed.

Both write rows into the same `notifications` table with `type = FilamentDatabaseNotification` and
`notifiable_type = User`, and both stamp `data->menu_key`. They are kept apart **only** by the key
string: badge keys are bare (`ads-controller`, `suggestion-controller`, `shared-events`); nudge keys
carry a `:nudge` suffix. Each system's queries filter by its own key shape, so the two namespaces
cannot collide and neither can delete the other's rows.

## Namespace map

```
App\Services\Menu\
├── Contracts\MenuBadge.php         the indicator contract (badge side)
├── Contracts\MenuNudge.php         the nudge contract (nudge side)
├── Indicators\
│   ├── ActiveAds.php               key=ads-controller        isActive = Ad::active()->exists()
│   ├── PendingSuggestions.php      key=suggestion-controller isActive = Suggestion::attentionRequired()->exists()
│   ├── SharedEvents.php            key=shared-events         isActive = EventShare::hasImminentFor($u) || Event::hasImminentSharedFor($u)
│   ├── UnreadPosts.php             key=posts-controller      isActive = Post::hasUnreadFor($u)
│   ├── TodayFeeds.php               key=feeds                 isActive = Feed::whereDate('created_at', today())->exists()
│   ├── SpecialDays.php             key=special-days          isActive = Profile (non-terminated) whereMonth/whereDay(birthdate|start_date) = today
│   └── TasksTodo.php                key=tasks-controller      isActive = auth()->user() !== null && Task::getTodoCount($user->id) > 0   (per-user)
├── Notifications\
│   ├── AdNudge.php          key=ads-controller:nudge        triggers=Ad created/updated/deleted
│   ├── SharedEventsNudge.php       key=shared-events:nudge         triggers=EventShare created/deleted + Event updated/deleted
│   ├── SuggestionNudge.php         key=suggestion-controller:nudge triggers=Suggestion updated/deleted + Review created/updated
│   ├── PostNudge.php               key=posts-controller:nudge      triggers=Post created/updated/deleted  show=true  for=User::active()
│   ├── FeedNudge.php                key=feeds:nudge                 triggers=Feed created/updated/deleted show=true  for=User::active()
│   ├── PhotoNudge.php              key=gallery-controller:nudge   triggers=Photo created/updated/deleted show=true  for=dept-scoped (Photo.all_departments + 'MA', empty→all active)
│   └── ReportNudge.php             key=reports-controller:nudge   triggers=Report created/updated/deleted show=$report->active  for=User::active()
│   └── TaskNudge.php               key=tasks-controller:nudge     triggers=Task created/updated/deleted/forceDeleted show=true  for=owner (User::active()->where('id', $subject->assigned_to ?? $subject->user_id), empty→collect())
│   └── ThsNudge.php                key=ths-controller:nudge       triggers=Ticket created/updated/deleted show=true  for=staged recipient (Ticket::currentActionRecipient(), empty→collect())
│   └── DmsNudge.php                key=dms-controller:nudge       triggers=DMS created/updated/deleted + Read created/updated/deleted show=isPendingFor($u)  for=DMS::pendingRecipients() (visible live + pending users)
│   └── ChannelNudge.php            key=channels-controller:nudge  dual-state row migrates on entered_at (invited=entered_at IS NULL via Channel::invitedUserIds; unread=entered + count>0 via Channel::unreadCountsFor, whereNotNull(entered_at) + whereNull(msg.deleted_at)) like ThsNudge  triggers=Channel deleted/forceDeleted (cleanup) + ChannelMessage created/deleted (subject=$msg->channel)  show=true  for=invited∪unread (two indexed queries, for()-primes-body idiom)  reuses the three existing dispatch sites (SyncChannelMembers/MarkChannelRead/LeaveChannel); send path covered by ChannelMessage::created → no new dispatch
├── StateService.php                cache + version + sync orchestration (badge side)
├── BadgeSyncService.php            one-row-per-indicator reconcile (badge side)
└── NudgeService.php          registry + dumb engine (nudge side); register(MenuNudge) adapts a nudge into the rule array the engine consumes

App\Jobs\ReconcileNudge.php            queued unit of work for a per-record reconcile (nudge side)
App\Providers\NudgeServiceProvider.php registers the nudge classes in boot(): NudgeService::register(new ...Nudge())
bootstrap/providers.php                        one line appended to register the provider above
```

No migration, no Observer class (events are bound from the service), and no edit to
`StateService`/`BadgeSyncService`/`MenuBadge`/indicators/models when the nudge layer was added — it
is purely additive.

---

## Signal 1 — Badge / dot

### `Contracts\MenuBadge`

```php
interface MenuBadge {
    public function getKey(): string;    // the bare menu_key (no suffix)
    public function isActive(): bool;     // is the condition true right now?
    public function getTitle(): string;
    public function getBody(): string;
}
```

An indicator is a **stateless, read-only** object: `isActive()` reads the DB (or `auth()->user()`)
fresh and returns a bool. It owns no row and writes nothing. Three implement it, all structurally
identical (no per-user method, no sub-interface):

| Indicator | `getKey()` | `isActive()` |
|---|---|---|
| `ActiveAds` | `ads-controller` | `Ad::active()->exists()` |
| `PendingSuggestions` | `suggestion-controller` | `Suggestion::attentionRequired()->exists()` |
| `SharedEvents` | `shared-events` | `auth()->user() !== null && (EventShare::hasImminentFor($user) \|\| Event::hasImminentSharedFor($user))` |
| `TasksTodo` | `tasks-controller` | `auth()->user() !== null && Task::getTodoCount($user->id) > 0` |

`SharedEvents` and `TasksTodo` are the per-user ones — they read the logged-in user inside `isActive()` and lights for
**both** parties of a share: the recipient (`EventShare::hasImminentFor`) and the owner
(`Event::hasImminentSharedFor`). The other two indicators are global existence checks. The attention
logic lives on the model (`Suggestion::attentionRequired` scope, `EventShare::hasImminentFor`,
`Event::hasImminentSharedFor`, `Ad::active`), so the indicator is just a thin wrapper — the query
lives on the model that owns the data. Adding a 5th badge indicator = one class implementing
`MenuBadge` + one line in `StateService::$indicators`.

> **Shared-events badge window — 24h proximity, both parties.** `SharedEvents::isActive()` lights
> only when a shared event is **imminent** — `date in [now, now + 24h]` — not for the whole upcoming
> span. `EventShare::hasImminentFor($user)` covers the recipient (events shared *with* me in the
> window); `Event::hasImminentSharedFor($user)` covers the owner (events I own that have ≥1 share and
> are in the window). Pure SQL `whereBetween`, no PHP loop. The badge `title`/`body` are
> party-agnostic approaching-reminder text («رویداد مشترک نزدیک است» / «…در ۲۴ ساعت آینده است…») —
> the same message fits both sharer and sharee.
>
> **Badge vs nudge window — decoupled.** The **nudge** (`SharedEventsNudge`, see below) is the
> share-time *announcement*: its `show` keeps the whole-upcoming span (`date >= now`), so the bell
> stays from the moment of sharing until the event passes. The **badge** is the 24h *reminder* that
> lights only near the event. So: share → bell (announcement); within 24h → dot + bell together;
> event passes → both clear. Because the badge is **pull-based** (recomputed only on menu render /
> `StateService::flush()`, TTL ≈ 2h) and time passage fires no event, the dot can lag up to ~2h
> after the 24h boundary crosses — accepted trade-off given the system's frequent flushes.

### `StateService` — cache + version + sync

The orchestrator. Two jobs: produce the cached bool map for the menu, and (when recomputing) drive
`BadgeSyncService` so the bell rows match.

```php
public function get(): array {
    $user = auth()->user();
    $version = self::version();
    $cacheKey = "menu_state:v{$version}:" . ($user ? "u{$user->id}" : 'guest');
    return Cache::remember($cacheKey, now()->addHours(2), function () use ($user) {
        $instances = array_map(static fn (string $class) => app($class), $this->indicators);
        $resolved = [];
        foreach ($instances as $indicator) {
            $resolved[$indicator->getKey()] = $indicator->isActive();
        }
        if ($user) {
            try { $this->syncService->syncBatch($user, $instances, $resolved); }
            catch (\Throwable $e) { report($e); }
        }
        return $resolved;
    });
}
```

- **Versioned cache.** `flush()` does exactly one thing: `Cache::forever(VERSION_KEY,
  now()->getPreciseTimestamp())` — a **global cache-version bump only**. There is no per-user
  purge, no row wipe. Every user's next `get()` builds a new cache key (`v{newVersion}:u{id}`), so
  the old entry simply expires; the closure re-runs and recomputes every indicator fresh.
- **The bool map is the menu dot's source.** `menu.blade.php` reads `@js($menuState)[item.id]` —
  the dot is lit/dark straight from the cached bool. The dot is therefore **not** dismissable: it
  reflects live status, not whether the user has seen it.
- **Sync runs only on a cache miss**, inside the `Cache::remember` closure — i.e. once per version
  per user, not on every render. A cache hit = one cache read and **zero DB queries** (the previous
  design fired `sync()` per render, issuing a SELECT + dedup-DELETE per active indicator). The whole
  `syncBatch()` call is wrapped in `try/catch (\Throwable $e) { report($e); }` so a notification-DB
  failure degrades gracefully (stale badge until the next `flush()`) instead of breaking the menu
  render or poisoning the cached bool map.
- **No more `syncIndicators`.** The per-indicator loop was removed. `get()` builds `$instances`
  **once** (reused for both `isActive()` resolution and the sync), then hands the whole batch to
  `$syncService->syncBatch($user, $instances, $resolved)` — indicators are no longer instantiated
  twice.

### `BadgeSyncService::syncBatch()` — whole batch in 3 queries

Reconciles **all** indicator rows for one user in one pass. Replaces the old per-indicator
`sync()` (which issued up to N×(SELECT + dedup-DELETE + INSERT) — ~22 queries for 11 indicators).
Now **at most 3 queries**: one SELECT (existence check), one bulk DELETE (inactive keys), one bulk
INSERT (new active rows) — best-case; the DELETE/INSERT are guarded so they're skipped when their
batch is empty. The state-machine semantics are preserved, just batched:

```php
$keys = array_map(fn (MenuBadge $i) => $i->getKey(), $indicators);

$existingByKey = $user->notifications()
    ->where('type', FilamentDatabaseNotification::class)
    ->whereIn('data->menu_key', $keys)->get(['data'])
    ->mapWithKeys(fn ($n) => [$n->data['menu_key'] ?? null => true])->toArray();

foreach ($indicators as $indicator) {
    try {
        $key = $indicator->getKey();
        if (!($state[$key] ?? false)) { $inactiveKeys[] = $key; continue; }
        if (isset($existingByKey[$key])) { continue; }
        $toInsert[] = [ …unread row, menu_key => $key … ];
    } catch (\Throwable $e) { report($e); }
}

if ($inactiveKeys) {
    $user->notifications()->where('type', FilamentDatabaseNotification::class)
        ->whereIn('data->menu_key', $inactiveKeys)->delete();
}
if ($toInsert) { $user->notifications()->insert($toInsert); }
```

- All queries are scoped to the **bare** `menu_key` via `whereIn('data->menu_key', …)`, so they
  physically cannot see `:nudge` rows.
- **No-resurface is preserved.** `existingByKey` counts **any** `read_at` state (read *or*
  unread). A dismissed nudge (read) for an indicator still active is left untouched — `syncBatch()`
  never re-unreads an existing row (it's in `$existingByKey`, so it's skipped in the build loop and
  never inserted again). The row only ever leaves by going inactive (the bulk `delete`). If the
  condition later re-occurs, a fresh unread row is inserted (a new nudge, not a resurface).
- **Per-indicator isolation.** The build loop body is wrapped in `try/catch (\Throwable $e) {
  report($e); }` so one throwing indicator (`getKey`/`getTitle`/`getBody`/`getDatabaseMessage`) is
  logged and skipped — the rest of the batch still partitions and the bulk delete/insert still run.
  The outer `try/catch` in `StateService::get()` covers the 3 queries themselves (select/delete/
  insert are outside the loop).
- **Bulk insert bypasses Eloquent.** `$user->notifications()->insert($toInsert)` writes rows
  directly, so morph columns (`notifiable_type`/`notifiable_id`), `json_encode($data)`, `id`
  (`Str::uuid()`), and timestamps must be set manually in each row — Eloquent casts/events don't
  fire on raw `insert()`.
- The old code did two things that both re-added dismissed nudges to the unread bell: it reset
  `read_at = null` on version change, *and* it globally purged all menu-badge rows on `flush()`.
  Both were removed. `flush()` is now a pure version bump; the dismissed row survives every flush
  and `syncBatch()` leaves it read.
- No `version`/`cleared` bookkeeping fields are written. The payload carries only `menu_key` plus
  the static title/body/action. The "حذف اعلان" action calls `markAsRead()`.

### Badge lifecycle

- condition becomes active (e.g. first active ad) → next `get()` cache miss → `sync` → no row →
  create unread → dot lit, bell shows it.
- user dismisses → row `read_at` set; **dot stays lit** (condition still true); bell unread clears.
- condition goes inactive → next `get()` recompute → `sync` → `!isActive` → delete row.
- condition re-activates → next recompute → no row → create fresh unread → a *new* nudge, not a
  resurface of the old one.

### Who calls `flush()` (invalidation)

`flush()` is the one invalidation primitive, reused across modules. Anything that can change a
badge's truth must bump the version:

- `Suggestion` model events (created/updated/deleted) — pending-suggestions badge.
- `Ad` model events — active-ads badge.
- `EventShare::booted()` `created`/`deleted` — shared-events badge (model-side path; the admin
  RelationManager revoke goes through `$record->delete()` and fires this). The user-panel
  `ShareEventAction` uses bulk `insertOrIgnore`/query-`delete()` which **bypass** model events, so
  it calls `flush()` explicitly afterward — no double-fire.
- `Event::booted()` `updated`/`deleted` — shared-events badge on an owner's date edit or event
  deletion (the nudge side is covered by the `Event` trigger; this hook makes the **badge** side
  follow promptly instead of waiting for the ≤2h TTL). Both `EventShare` and `Event` wrap the
  flush in `DB::afterCommit()`.
- Any explicit "recompute now" call site.

`flush()` does **not** purge rows (see no-resurface). It only forces the next `get()` to recompute.

### Where the dot renders (surfaces)

`$menuState` (the `get()` bool map) is injected by **one** `View::composer` array in
`ViewServiceProvider` into three views — `components.dashboard.modal.menu`,
`components.dashboard.navbars.right`, `components.dashboard.navbars.bottom` — all calling the same
cached `MenuStateService::get()`, so within a request the first call may hit the DB and the rest are
cache hits (one cache read, zero extra queries for the sidebar).

- **Menu modal** — dot per item via `@js($menuState)[item.id]`; item ids come from the static
  `resources/js/components/alpine/data/menu.js` array (`*-controller` style). `ads-controller`,
  `suggestion-controller`, and `tasks-controller` have matching item ids, so they render there.
  `shared-events` has **no** item id in `menu.js` → it is menu-invisible by design; the sidebar tab
  is its only surface.
- **Sidebar tabs** — `Tabs::tabs()` entries may carry a nullable `'badge' => '<indicator key>'`
  field. `navbars/right.blade.php` and `navbars/bottom.blade.php` render the ping dot when
  `isset($tab['badge']) && ($menuState[$tab['badge']] ?? false)`. Currently only the `calendar` tab
  carries `'badge' => 'shared-events'`. Adding a tab dot for any future indicator that gains a
  sidebar tab = one `'badge' => '<key>'` line on that tab entry; **both navbars are already wired**,
  so no blade change is needed. The left rail (`navbars/left.blade.php`, hard-coded `/tasks`
  `/dms` `/ths`) has no indicators mapped and is untouched.
- **Indicators with no sidebar tab** — `ads-controller`, `suggestion-controller`, and `tasks-controller`
  are full-page routes reached via the menu (`/ads`, `/suggestion`, `/tasks`), not sidebar tabs, so they
  stay menu-modal-only by design. Forcing a dot onto an unrelated tab would mislabel the signal.

---

## Signal 2 — Record nudge / bell

A **separate, additive** layer that produces **one new bell nudge per qualifying record** (a new
active ad, a suggestion entering an attention stage, a new shared event) rather than the badge's
one-row-per-indicator aggregate. It sits alongside the badge system and never edits it.

### The rule shape

```php
class SomeNudge implements MenuNudge
{
    public function getKey(): string { return 'some-menu_key:nudge'; } // unique nudge namespace (:nudge suffix is the convention)

    public function triggers(): array {
        return [
            ['class' => TriggerModel::class, 'on' => ['created','updated','deleted'], 'subject' => null],
            // a 2nd trigger when a different model flips this record's state:
            ['class' => ForeignModel::class, 'on' => ['created','updated'], 'subject' => fn($m) => $m->relation],
        ];
    }

    public function show($record, User $user): bool { ... }    // per-recipient gate
    public function for($record) { ... }                       // candidate recipients
    public function title($record, User $user): string { ... } // per-recipient title
    public function body($record, User $user): string { ... }  // body
    public function refresh(): bool { return false; }          // true → rewrite title/body on UNREAD rows when an edit re-fires reconcile
}

NudgeService::register(new SomeNudge());
```

`triggers()` declares each Eloquent model + the events on it that should re-fire this nudge's
reconcile. `subject` (per-trigger, default `null` = the trigger model itself) is set whenever the
nudge is about a **different** record than the trigger — `Review → $review->suggestion`, and
`EventShare → $share->event` (so the nudge is keyed per-event, not per-share-row). `show`, `title`,
and `body` all receive the **subject** and the **user**, so a single rule can produce a different
message per recipient (e.g. shared-event: one message for the sharer, another for each recipient).
`register()` is a thin **adapter** that builds the rule array the engine consumes from these
methods, then binds each trigger's events — the engine itself is unchanged.

### Engine — `register()`

1. **Idempotency guard** keyed by `key` (one nudge class = one rule, declaring all its triggers) —
   a provider re-boot (tests, Octane, queue worker restart) does **not** stack duplicate event
   listeners. `reset()` clears both maps for tests.
2. Store the rule under `menu_key`.
3. For each event in `on`, bind `TriggerModel::{event}` to one closure that:
   - resolves the **subject** via `subject` (default = the trigger model);
   - if `null` (foreign trigger whose parent is already gone), returns — nothing to do;
   - otherwise `dispatch(new ReconcileNudge($key, get_class($subject), $subject->getKey()))->afterCommit()`.

The dispatch carries only **primitives** (rule key, subject class string, subject id) — never a
model instance — so there is no model-serialization problem when the job runs later.

### Engine — `reconcile()` (runs inside the job)

```
look up rule by key; if missing, return
itemId = (string) subjectId

try { Cache::lock("nudge:k{key}:i{itemId}", 10)->block(3, function () {
    fresh() = a NEW query each call:
        DatabaseNotification::where('type', FilamentDatabaseNotification::class)
            ->where('notifiable_type', User::class)
            ->where('data->menu_key', key)            // :nudge key → never matches badge rows
            ->where('data->item_id', itemId)

    subject = subjectClass::find(subjectId)            // re-fetch FRESH (current state)

    if subject === null:                               // record gone (deleted)
        fresh()->delete(); return                       // blanket delete this record's rows

    recipients = rule.for(subject);  ids = recipients.id[]
    if ids empty:
        fresh()->delete(); return                       // no recipients → clean the record's rows

    fresh()->whereNotIn('notifiable_id', ids)->delete(); // prune dropped recipients

    foreach recipients as user:
        if !rule.show(subject, user):
            fresh()->where('notifiable_id', user.id)->delete(); continue
        if fresh()->where('notifiable_id', user.id)->exists():   // ANY read_at state
            if rule.refresh && existing.read_at === null:        // opt-in: rewrite data on UNREAD rows
                existing.update(['data' => buildData(…)])        // so a live label (e.g. event title) stays current
            continue                                    // read_at never touched → no re-notification
        if rule.badge_suppress
            && a bare-key badge row exists for user (menu_key = beforeLast(key, ':nudge')):
            continue                                    // badge already reminds this user → skip the nudge CREATE
        user->notifications()->create([ …unread row: title, body, menu_key, item_id ])
}); } catch (LockTimeoutException) { /* contested lock → skip; next event re-reconciles */ }
```

Each design choice, why:

- **`afterCommit()` dispatch** — the job runs **after the model's save transaction commits**, so the
  `Cache::lock` is acquired *outside* the transaction. A rolled-back save cannot un-release the lock
  (the deadlock class the inline-lock approach would hit on a same-connection DB store). This is the
  reason `QUEUE_CONNECTION` alone can switch sync↔worker with **no code branch**.
- **Re-fetch the subject fresh** — the job reads *current* state, not the event-time snapshot.
  Deleted records come back `null` → blanket delete. This also removes any need to serialize the
  model into the job (only class+id travel), so deleted-model deserialization can never fail.
- **Lock per `(key, item_id)`** — serializes only concurrent reconciles for the *same record*
  (different records run in parallel). Inside the lock, the existence check + insert is atomic, so
  **no duplicate rows can be created without a unique index** (the index path would need generated
  JSON columns and would tax *every* notification write; the lock avoids that cost).
- **`exists()` counts any `read_at` state** — a dismissed (read) nudge for a record that is still
  qualifying is left untouched and **never recreated**. That is the no-resurface guarantee — the same
  property the badge system gets from `BadgeSyncService::syncBatch()`'s `existingByKey` lookup, expressed in
  the push model.
- **`refresh` flag (opt-in)** — when a rule sets `'refresh' => true`, reconcile rewrites `data`
  (title/body) on an existing **unread** row instead of `continue`-ing past it. All three nudge
  titles embed a *live mutable label* (`{event.title}`, `{ad.position}`, `{suggestion->title}`),
  not a fire-time fact — so an edit that re-fires reconcile keeps the bell current (rename → unread
  row shows the new name). All three nudges set `refresh = true`. A **read** row is never rewritten
  (`read_at` preserved) → no re-notification. The `buildData()` helper is a pure DRY extraction
  shared by the create + refresh paths.
- **Prune `whereNotIn('notifiable_id', ids)`** — if a user leaves the recipient set (role/dept
  change) while the record still qualifies, their stale row is removed.
- **`notifiable_type = User::class` filter** — cross-user deletes/prunes never sweep notifications
  belonging to other notifiable types.
- **`LockTimeoutException` swallowed** — a contested lock degrades gracefully (the next event
  re-reconciles) instead of breaking the triggering save.
- **`:nudge` suffix** — isolation from the badge system (the badge's `syncBatch()` queries strictly by
  the bare key, this queries strictly by the suffixed key). It is also what makes badge-overlap
  suppression work: the nudge's bare key is recovered as `Str::beforeLast($key, ':nudge')`, so the
  guard can look up the matching badge row by convention with no key mapping.
- **Badge-overlap suppression (`badge_suppress`, default ON)** — before creating a recipient's
  nudge row, `reconcile()` checks whether that user already has a **bare-key badge row** for the
  same menu key (the row `BadgeSyncService::syncBatch()` writes when the indicator is active). If yes,
  the nudge CREATE is skipped (the badge already reminds them); the existing-row refresh path above
  is unaffected, so unread nudges already in flight still get refreshed. The check sits **after**
  the existing-row branch, so it only gates new creates — never deletes, never touches the badge.
  This is what prevents the duplicate "two cards for one event" the badge+nudge pair would otherwise
  produce on fully-overlapping modules (Tasks, Ads, Posts, Feeds, Suggestions). The lazy timing of
  the badge row (written at a prior `get()`, before the new subject existed) gives the
  "excluding the current item" semantics for free: the first occurrence finds no badge row → nudge
  fires; later duplicates, with the badge already lit → suppressed. `register()` captures the flag
  via `method_exists($nudge, 'badgeSuppressesCreate')` (default `true` when absent), so the
  `MenuNudge` contract is unchanged and non-participating nudges are untouched. A nudge opts OUT by
  implementing `badgeSuppressesCreate(): bool { return false; }` — required only where the badge
  condition is **not** a superset of the nudge condition: `SharedEventsNudge` (badge = imminent
  ≤24h, nudge = any future event) and `ContactNudge` (badge = any unread, nudge = per-chat; a new
  chat must still alert even when another chat already lit the badge). Gallery/Reports have no
  matching badge row, so the guard is a no-op there.
- **`subject` resolver** — lets a *foreign* trigger reconcile a *different* record's nudge. Review
  writes flip a Suggestion's attention but fire no `Suggestion` event; binding Review with
  `subject = $review->suggestion` and the **same key + item_id (suggestion id)** as the Suggestion
  rule makes both triggers reconcile the **same** per-recipient rows. Both triggers live in one
  `SuggestionNudge` class, so `show`/`for`/`title`/`body` are literally the same methods on one
  class and cannot drift.

### The rules (`NudgeServiceProvider`)

Each row is one trigger declared inside a `MenuNudge` class in `Notifications\` (`AdNudge`,
`SharedEventsNudge`, `SuggestionNudge`); one class can declare several triggers sharing the same key.

| Trigger | `on` | `subject` | `show` | `for` (recipients) |
|---|---|---|---|---|
| `Ad` | created, updated, deleted | self | `$ad->active` | `User::active()->get()` |
| `EventShare` | created, deleted | `$share->event` | per-user: owner → `hasShares && date>=now` (`hasShares` primed by `for()`); recipient → `date>=now` (membership guaranteed by `for()`) | `[owner] + all current share recipients` |
| `Event` | updated, deleted | self | same as EventShare (one `SharedEventsNudge` class) | same as EventShare (one `SharedEventsNudge` class) |
| `Suggestion` | updated, deleted | self | `Suggestion::requiresAttentionFor($s, $user)` | `User::active()->whereHas('profile', department_id ∈ ['MA', …$s->departments])` |
| `Review` | created, updated | `$review->suggestion` | `Suggestion::requiresAttentionFor($s, $user)` | same as Suggestion (one `SuggestionNudge` class) |

- Suggestion + Review share the `suggestion-controller:nudge` key and `item_id = suggestion id`.
- EventShare + Event share the `shared-events:nudge` key and `item_id = event id` (the `EventShare`
  rule sets `subject = $share->event`, so both triggers reconcile the **same per-event** rows). The
  nudge is keyed **per event**, not per share row — so sharing one event with N people creates
  exactly **one** owner self-nudge + N recipient nudges, and un-sharing prunes recipients in one
  reconcile. `title`/`body` differ per recipient: the owner (the event's `user_id`) gets
  «رویداد شما به اشتراک گذاشته شد: X» / «این رویداد توسط شما با همکاران به اشتراک گذاشته شده است.»;
  each recipient gets «رویداد مشترک: X» / «این رویداد توسط یکی از همکاران با شما به اشتراک گذاشته شده
  است.». The owner's `show` requires `shares()->exists()`, so the self-nudge disappears the moment
  the event has no shares. `for` is scoped to `User::active()`, so a recipient deactivated after
  being shared with is dropped from the set on the next reconcile and their nudge row is pruned via
  the `whereNotIn` delete. This rule sets `refresh = true` (like the other nudges) — see the engine
  design choices below: an edit re-firing reconcile rewrites `title`/`body` on a still-**unread** row
  so the bell reflects the event's current name.
- **EventShare firing (panel vs admin)** — the user-panel `ShareEventAction` writes shares with
  bulk `insertOrIgnore` + query `delete()`, which **bypass Eloquent model events**, so the
  `EventShare::created/deleted` hooks never fire there. The action therefore dispatches
  `ReconcileNudge('shared-events:nudge', Event::class, $event->id)->afterCommit()` explicitly
  after the diff-sync (one dispatch re-syncs the whole event's nudge set). The model-event path
  (`EventShare::deleted`) still covers the admin `EventSharesRelationManager` revoke
  (`$record->delete()`). `Event::updated` reconciles on a date/title edit (a date moved into the
  past clears the rows via `show=false`); `Event::deleted` re-fetches the event `null` → blanket
  delete (DB cascade removes the shares but fires no model events, so the Event trigger is the
  cleanup path). Public and private events behave identically — a share row is created either way,
  and both parties are nudged.
- **No-op short-circuit + post-commit flush** — `ShareEventAction` returns early (no `flush()`, no
  dispatch) when the diff is empty, so an unchanged re-save bumps no version and queues no job. Both
  `EventShare::booted()` and `Event::booted()` wrap `StateService::flush()` in `DB::afterCommit()` —
  the version bump publishes only post-commit (closes a mid-transaction cache-poisoning race where a
  concurrent cache miss would recompute against pre-commit state and cache it under the new version;
  `DB::afterCommit` runs inline when no transaction is active). `Event::booted()` flushes on
  `updated`/`deleted` only (not `created` — a new event has no shares), so the **badge** reflects an
  owner's date edit or event deletion promptly instead of lagging the ≤2h TTL; the nudge was already
  covered by the `Event` trigger.

### Recipients without an auth user

Model events have **no `auth()->user()`** (the save may come from a queue, a console command, or
another user's action). So a rule declares `for` (who *might* need to act) and a per-recipient
`show` (who *actually* needs to act right now). The user is **passed into** `show`, never read from
the session. `Suggestion::requiresAttentionFor($s, $user)` is the canonical attention logic the
badge's `attentionRequired` scope already uses — single source of truth, no drift between badge
and nudge.

### Nudge lifecycle (the cycle)

- **New active ad** → no row for its id → create unread → new nudge.
- **2nd active ad** → different id → no row → create unread → another nudge (per-record, not
  suppressed by the existing ad's row).
- **Dismiss one** → that row's `read_at` set; other records' rows untouched → still per-record.
- **Ad deactivated** (`updated`) → re-fetch → `active=false` → `!show` → delete its row → nudge
  gone.
- **Ad re-activated** → re-fetch → `active=true` → row was deleted → create new unread → new nudge
  (a new occurrence, not a resurface).
- **Ad deleted** → re-fetch `null` → blanket delete its row.
- **Unrelated ad edit** (active stays true) → re-fetch → row exists (any read_at) → skip → no
  resurface.

---

## The two systems side by side

| | Badge / dot | Record nudge |
|---|---|---|
| what | permanent status signal (menu dot) | one-time nudge (bell entry) |
| granularity | one row **per indicator** | one row **per qualifying record** |
| trigger | pull — reconciled on menu render (`StateService::get()`) | push — reconciled on the record's Eloquent event |
| key shape | bare `ads-controller` / `suggestion-controller` / `shared-events` | suffixed `ads-controller:nudge` / `suggestion-controller:nudge` / `shared-events:nudge` |
| dismissal | not dismissable (lit while true) | dismissable (`markAsRead`); never resurfaces |
| no-resurface mechanism | `BadgeSyncService::syncBatch()` `existingByKey` lookup leaves `read_at` alone | `reconcile()` `exists()` branch leaves `read_at` alone |
| invalidation | `StateService::flush()` (global version bump) | n/a (event-driven; re-fetches fresh) |
| recipient model | one row per user per indicator | `for()` candidate set + per-recipient `show()` gate |
| auth at write time | `get()` runs in-request with `auth()->user()` | none — `for`/`show` carry the user explicitly |

Both rely on the **same** `exists()` → leave-it-alone primitive for no-resurface, just expressed in
pull vs push form. Both write rows of the same `type`/`notifiable_type`; only the `menu_key` shape
separates them.

## Keys & isolation (the one rule that keeps them from colliding)

- Badge keys are **bare** (`ads-controller`). `BadgeSyncService::syncBatch()` filters
  `whereIn('data->menu_key', $keys)` — bare keys only.
- Nudge keys are **suffixed** (`ads-controller:nudge`). `NudgeService::reconcile()` filters
  `where('data->menu_key', $ruleKey)` — suffixed key only.
- A bare key can never equal a suffixed key, so the two query sets are disjoint. No const class is
  needed for this — the `:nudge` suffix alone guarantees it. The keys are written inline in
  `NudgeServiceProvider` and in each indicator's `getKey()`.

## Configuring local vs production

The nudge code has **no environment branch**. Only `.env` differs:

| | `.env` | effect |
|---|---|---|
| local (no worker) | `QUEUE_CONNECTION=sync` | nudge job runs **inline after commit** — no `queue:work` needed, still deadlock-safe |
| production (worker) | `QUEUE_CONNECTION=redis` (or `database`) | worker processes the job, also after-commit |

`CACHE_STORE` must be a **lock-capable** store (the nudge `Cache::lock` depends on it): `database`
(local) and `redis` (prod) both qualify; `file` does not. The badge `Cache::remember`/`forever`
work on any store. Production also needs a queue worker running (`php artisan queue:work`,
typically via Supervisor) — the app already runs one for its other jobs.

## Limitations / inherent drift

These fire **no model event**, so neither signal is auto-reconciled by them — the same Eloquent
limitation the badge's `flush()` hooks already live with:

- **Bulk ops** — `Model::query()->delete()` / mass updates bypass model events. Badge: the next
  unrelated `flush()` or render recompute fixes the dot. Nudge: rows for affected records rely on
  user dismissal or the next related event. Bulk paths should use `->each()` or call a manual
  reconcile / `flush()`.
- **Time passage** — an event passing its date fires no event. Badge: the `upcoming` recompute on
  the next render turns the dot off. Nudge: a shared-event nudge then lingers (unread) until the
  user dismisses it or the share is deleted.
- **`Review::deleted` not bound** — a removed review can leave a stale unread suggestion nudge until
  the next `Suggestion` write or user dismissal. Intentional: binding it would re-introduce the
  deleted-foreign-subject edge for little gain.

## Adding a new indicator

**Badge** — one class implementing `MenuBadge` + one line in `StateService::$indicators`. Call
`flush()` from the relevant model events so the version bumps on change. The engine never changes.

**Nudge** — one class implementing `MenuNudge` in `Notifications\` + one
`NudgeService::register(new ...Nudge())` line in `NudgeServiceProvider::boot()`. No engine edit, no
migration, no observer class. That is the future-proof contract for both layers.

---

## `HasMenuState` trait — the single flush primitive (replaces per-model `booted()`)

Every model whose writes can change a badge's truth now uses `App\Models\Traits\HasMenuState` instead
of a hand-written `booted()` closure. The trait ships one method:

```php
public static function bootHasMenuState(): void
{
    $events = defined('static::MENU_STATE_EVENTS') ? static::MENU_STATE_EVENTS : ['created', 'updated', 'deleted'];
    $flush = fn() => DB::afterCommit(fn() => StateService::flush());
    foreach ($events as $event) { static::{$event}($flush); }
}
```

- Default `['created','updated','deleted']` — used by `Ad`, `Post`, `Suggestion`, `Feed`, `Profile`.
- **Opt-out const** `MENU_STATE_EVENTS` narrows the set when flushing on a given event is pointless or
  noisy:
  - `Event` → `['updated','deleted']` — a brand-new event has no shares, so `created` would fire a
    global version bump on every personal/private unshared event creation (thundering-herd). `updated`
    (owner date/title edit) and `deleted` are the events that actually change the shared-events badge.
  - `EventShare` → `['created','deleted']` — a *created* share is the meaningful event; shares are never
    `updated`. (The user-panel `ShareEventAction` writes shares with bulk `insertOrIgnore` which bypasses
    model events and calls `flush()` explicitly, so this hook is the defensive cover for any Eloquent
    `EventShare::create()` plus the admin `EventSharesRelationManager` revoke `$record->delete()`.)
- `DB::afterCommit()` is preserved (mid-transaction cache-poisoning race stays closed; runs inline when
  no transaction is active). `bootHasMenuState()` coexists with a model's own `boot()` (e.g. `Feed::boot()`
  registers the `deleting` comment/reaction/poll cascade) — Laravel calls both.
- **Nudge side is unaffected**: `NudgeService::register()` binds a nudge's Eloquent triggers independently
  of `MENU_STATE_EVENTS`; the const only controls the **badge** version bump, not the bell reconcile.

## Posts unread badge (`UnreadPosts`) + per-record nudges (`PostNudge`), created-today feeds badge (`TodayFeeds`) + `FeedNudge`

A pair mirroring `ActiveAds`/`AdNudge`, but the "today" gate is the date, not a status flag:

- **Badge** — `UnreadPosts` (key `posts-controller`): `isActive()` =
  `Post::hasUnreadFor($u)` (per-user, reads `posts-controller:nudge` unread rows within `FRESHNESS_DAYS` via
  the `HasNudgeTracking` trait). `TodayFeeds` (key `feeds`): `isActive()` =
  `Feed::whereDate('created_at', now()->toDateString())->exists()` (global, stateless — same shape as `ActiveAds`).
- **Nudge** — `PostNudge` (key `posts-controller:nudge`) / `FeedNudge` (key `feeds:nudge`): triggers
  `created/updated/deleted`; `show = true` (ungated — mirrors `PostNudge`, no author exclusion; a feed
  has no "active" flag, so once created it qualifies until deleted); `for = User::active()->get()`;
  `refresh = true`. `FeedNudge::title()` falls back through `FeedCategory::tryFrom($subject->category)?->getLabel()`
  (Feed has no title column, unlike Post).
- **Surfaces** — `TodayFeeds` has no `feeds-controller` menu item (menu-modal-invisible by design, like
  `shared-events`); its only surface is the `feed` sidebar tab via `'badge' => 'feeds'` in `Tabs.php`.
  `UnreadPosts` likewise has no `posts-controller` menu item — surface it by adding `'badge' => 'posts-controller'`
  to the `post` tab when desired (currently orphaned: registered but no tab badge line).

### `HasNudgeTracking` trait — FRESHNESS_DAYS, `seenIdsFor` null-filter, `markReadFor` afterCommit

The `UnreadPosts` rename also landed the `App\Models\Traits\HasNudgeTracking` trait that gates all per-user
unread-nudge queries:

- **`FRESHNESS_DAYS = 30`** is the single horizon for the trait — `isFresh()`, `hasUnreadFor($u)`, and
  `seenIdsFor($u)` all scope `notifications` rows to `created_at >= now()->subDays(30)`. The `TodayPosts`
  global created-today/stateless indicator was renamed to `UnreadPosts` (per-user, reads
  `posts-controller:nudge` unread rows within `FRESHNESS_DAYS`) — a semantic change, not just a rename.
- **`seenIdsFor($u)` MUST filter null/empty `data->item_id` before the `(int)` cast.** A null `item_id`
  casts to `0`, which pollutes the seen-map with a bogus `0 => true` entry that can mask a real post id `0`
  (or just bloat the map). The correct shape is
  `->filter(fn($id) => $id !== null && $id !== '')->mapWithKeys(fn($id) => [(int)$id => true])`.
- **`markReadFor($u)` + `MarkPostAsReadAction` wrap `StateService::flush()` in `DB::afterCommit()`** so the
  badge clears only after the `notifications.update` commits (fires inline when no transaction is active).
  Without `afterCommit`, a rolled-back mark-read would still bump the version and the next `get()` would
  recompute against the stale unread set. `MarkPostAsReadAction` is the user-side write entry point; it
  delegates to `markReadFor` then flushes post-commit — one primitive, reused.

## Department-scoped + broadcast nudges (`PhotoNudge`, `ReportNudge`) — nudge-only, no badge

Both are **Signal-2 only** (no `MenuBadge` indicator, no menu dot) — the bell row is the whole signal.

- **`PhotoNudge`** (key `gallery-controller:nudge`) — `for()` reuses the Suggestion department idiom:
  `User::active()->whereHas('profile', department_id ∈ ['MA', …Photo::all_departments])`; empty
  `all_departments` → falls back to all active users (a public gallery photo broadcasts). `show = true`,
  `refresh = true`. Mirrors `SuggestionNudge` scoping.
- **`ReportNudge`** (key `reports-controller:nudge`) — broadcast: `for = User::active()->get()` (all active
  users), `show = $subject->active` (only published reports nudge — the `Report.active` boolean gate,
  same shape as `AdNudge::show = $ad->active`), `refresh = true`. The title carries the publishing
  department's full name: `'گزارش جدید از ' . (($subject->department?->description ?: $subject->department?->name) ?? 'سازمان') . ': ' . $subject->title`
  — `Department` exposes `description` (the complete display name, preferred) and `name` (short fallback);
  `?:` falls through an empty `description` to `name`, and `?? 'سازمان'` covers a department-less
  (organization-wide) report. Note `Department` has **no** `title` field.
- **Keys** — `gallery-controller` / `reports-controller` bare keys do **not** exist as menu items or tab
  badges; that is fine for nudge-only rules (the `:nudge` suffix is the only thing that matters for
  isolation, and there is no badge counterpart to collide with).

## `SpecialDays` — birthday/anniversary badge (per-day, like the shared-event proximity badge)

A **badge-only** (Signal 1, no nudge) indicator that lights **only on the exact day** someone has a
birthday (`Profile.birthdate`) or work anniversary (`Profile.start_date`), analogous to `SharedEvents`'
24h proximity window but scoped to the day:

- `isActive()` = `Profile::whereNotIn('employment_status', ['terminated'])->where(birthdate month/day = today OR
  start_date month/day = today)->exists()` — global, stateless (same shape as `ActiveAds`/`TodayFeeds`).
  The two date groups are wrapped in one outer `where(function …)` so the `employment_status` filter ANDs
  the whole OR group (a top-level `orWhere` would let the `start_date` branch escape the terminated filter).
  Uses Gregorian `now()->month/day` (dates are stored Gregorian; Jalali is display-only). The filter must
  read `employment_status` (enum `probational/working/terminated`), NOT `employment_type` (enum
  `fulltime/parttime/contract`) — `employment_type != 'terminated'` is a tautology (never 'terminated') so
  it never excludes anyone; the `whereNotIn('employment_status', ['terminated'])` form mirrors the sibling
  at `ModuleAnalyticsChartsRight.php:205`.
- Excludes terminated employees (a fired ex-coworker's birthday shouldn't light a "celebrate coworkers"
  dot); includes the auth user's own day (redundant with the `occasion` modal but harmless — the modal is
  a separate per-user confetti popup driven by `isSpecialDay()` + its own 8h cache key, no shared state).
- **Leap-year Feb 29** — `whereMonth/whereDay(2,29)` matches only leap years; the calendar grid
  (`Calendar::calendarDays()` `m-d` flip) behaves identically, so dot and grid stay in sync. No remap.
- **Invalidation** — `Profile` now uses `HasMenuState` (default all three), so an HR birthdate/start_date
  edit or profile delete bumps the version promptly. The day boundary fires no event, so the dot lags
  ≤~2h into the special day and ≤~2h after (accepted drift, same as `SharedEvents`); no scheduled flush.
- Title/body are occasion-agnostic («مناسبت امروز» / «امروز تولد یا سالگرد یکی از همکاران است؛ برای
  مشاهده به تقویم مراجعه کنید.») — an aggregate badge is one row and cannot list per-person names.

## `EnergyTestBadge` — monthly energy-test reminder (badge-only, Jalali month)

A **badge-only** (Signal 1, no nudge) per-user indicator, like `SpecialDays`. Lights when the auth
user has **not** completed an `EnergyTest` for the current Jalali month:

- `isActive()` = `$user !== null && !EnergyTest::hasForCurrentJalaliMonth($user->id)` — per-user,
  stateless dot; lit for the **entire month** while no record for that month exists, and it fades the
  moment a test for that month is saved. The condition lives on the model (see "Model as source of
  truth" below), not in the indicator.
- **Why badge-only (no nudge):** the badge is self-sufficient — it carries its own message, lights all
  month, and auto-clears on completion. A nudge ("first week of every month") would be **time-based**
  (no model event for "month started"), so it would need a **scheduled daily command** to dispatch the
  create during days 1–7; and because the nudge's condition (`no test this month`) is a subset of the
  badge's, it would have to opt out of badge-overlap suppression (`badgeSuppressesCreate = false`) just
  to fire at all. That scheduler + suppression machinery buys no marginal signal: the badge already
  reminds on every dashboard load, and a non-loading user sees neither. So energy-test is Signal-1 only.
- **Invalidation** — `EnergyTest` uses `HasMenuState`; `booted()` also calls `Cache::forget` on the
  user-averages cache for `saved`/`deleted`. Completing a test bumps the version → the badge clears on
  the user's next `get()`. Jalali month rollover fires no event, so the dot lags ≤~2h into the new
  month (same accepted drift as `SpecialDays`/`SharedEvents`); no scheduled flush.
- **Year scope (fixed).** `EnergyTest::hasForCurrentJalaliMonth($userId)` scopes by **Jalali year +
  month** via a half-open `completed_at` range: `>= first-of-current-Jalali-month` and `< first-of-next-
  Jalali-month` (constructed with `new Jalalian($year,$month,1)->toCarbon()->startOfDay()`, the same
  idiom as `Reservation/Main.php`). A test from the same Jalali month of a **prior year** no longer
  satisfies the check, so the badge re-lights that month this year. The query uses `completed_at`
  (already set on submit) rather than `month_index`, so it is also robust to a stale/zero `month_index`.

## Model as source of truth — conditions live on the model, indicators/nudges delegate

The condition that defines a badge's `isActive()` (and, where it exists, the matching nudge's
`show()`/`for()`) belongs **on the model** as a static/scope method, and the indicator + nudge are thin
adapters that call it. One query, one place — reusable, testable, and impossible to drift between the
badge and nudge. Every module now follows this:

| Module | Model method | Used by |
|---|---|---|
| Ads | `Ad::active()` scope | `ActiveAds` badge (`Ad::active()->exists()`) |
| Suggestions | `Suggestion::attentionRequired()` / `requiresAttentionFor($s,$u)` (in `HasSuggestionAlert`) | `PendingSuggestions` badge + `SuggestionNudge::show()` |
| Shared events | `EventShare::hasImminentFor($u)` / `Event::hasImminentSharedFor($u)` | `SharedEvents` badge |
| Tasks | `Task::getTodoCount($u)` / `scopeForUser` / `scopeStatus` | `TasksTodo` badge |
| Contacts | `Message::hasUnreadFor($u)` / `Message::unreadCountsFrom($sender)` | `UnreadMessages` badge; `ContactNudge::for()` batches per-recipient counts via `unreadCountsFrom` (no per-user query in `show()`) |
| Posts | `Post::hasUnreadFor($u)` (via `HasNudgeTracking` trait, reads `posts-controller:nudge` unread rows within `FRESHNESS_DAYS`) | `UnreadPosts` badge |
| Feeds | `Feed::postedToday()` (delegates to existing `Feed::getTodayCount()`) | `TodayFeeds` badge |
| Energy test | `EnergyTest::hasForCurrentJalaliMonth($u)` | `EnergyTestBadge` |
| THS tickets | `Ticket::hasUnclosedActionFor($u)` / `Ticket::currentActionRecipient()` | `ThsBadge` + `ThsNudge::for()` |
| DMS docs | `DMS::needsSignCount($u)` / `needsReadCount($u)` / `hasPendingFor($u)` / `DMS::isPendingFor($u)` | `DmsBadge` + `DmsNudge::show()/for()` |

Convention: the method takes the **user id** as a parameter (`hasUnreadFor($u)`, `getTodoCount($u)`,
`hasForCurrentJalaliMonth($u)`) rather than reading `auth()->user()` inside the model, so it is callable
from the queued nudge reconcile job (no auth context) and from tests — mirroring
`requiresAttentionFor($subject,$user)`. The indicator reads `auth()->user()` once and passes `$user->id`
down. `PhotoNudge`/`ReportNudge` are nudge-only (no badge, no shared condition) so they have no model
method to extract; `SpecialDays` is badge-only and its `Profile` date query is a one-off aggregate with
no nudge counterpart, left inline.

`ContactNudge` batches its per-recipient unread count to avoid an N+1 across recipients: `for()` calls
`Message::unreadCountsFrom($subject->id)` (one grouped `COUNT(*) … GROUP BY recipient_id` query,
sender-scoped, soft-delete-respecting via `Message`'s `SoftDeletes` scope), stores the
`[recipient_id => count]` map on the instance, and returns the active users among those ids; `body()`
then reads `$this->unreadCountCache[$user->id] ?? 0` and `show()` returns `true` (every recipient
returned by `for()` structurally has unread messages, so a per-user re-check is redundant). This relies
on `NudgeService::reconcile` always calling `for()` once before the per-recipient loop calls `body()` —
guaranteed because `body()` is invoked only via `buildData` inside that loop. The cache is a flat array
overwritten each reconcile (not keyed by subject), so it neither stales nor grows across the
process-singleton nudge instance's lifetime. The same `for()`-primes-`body()` pattern applies to any
nudge that needs per-recipient aggregates. `SharedEventsNudge` uses the same idea for `show()`: `for()`
sets a flat `$hasShares` bool from its existing `shares()->pluck('user_id')` query, and the owner branch
of `show()` reads it; non-owners pass the `date >= now` guard (the first check in `show()`) and then
return `true` since `for()` already guaranteed their membership — zero extra queries, no per-subject
cache key, no staleness/leak.

## Array `badge` slot — multiple indicators lighting one sidebar tab

A sidebar tab previously carried a single `'badge' => '<key>'` string and the navbars rendered
`$menuState[$tab['badge']]`. To let the `calendar` tab show **both** `shared-events` and `special-days`
through one dot, `'badge'` now accepts a string **or** an array of keys:

- `Tabs.php`: `'badge' => ['shared-events', 'special-days']` on the `calendar` tab; `'badge' => 'feeds'`
  on the `feed` tab.
- `navbars/right.blade.php` + `navbars/bottom.blade.php` (both page chunks): the dot condition is now
  `isset($tab['badge']) && collect((array) $tab['badge'])->contains(fn($k) => $menuState[$k] ?? false)` —
  `(array)` normalizes a string key to `['key']`, so the same line serves both shapes. The dot is a pointer
  to the tab; the tab content disambiguates (the single-dot design already had this property).

## `TasksTodo` + `TaskNudge` — per-user todo badge (menu-modal) + assignee nudge

The task board is a **full-page Livewire route** (`/tasks`, `TaskBoard\Main`), not a sidebar tab, so
the badge dot renders on the **menu modal** item `tasks-controller` (already declared in `menu.js`,
already rendered by `modal/menu.blade.php` via `@js($menuState)[item.id]`) — same surface as
`ads-controller`/`suggestion-controller`. No `Tabs.php`/navbar/blade change.

- **`TasksTodo` indicator** (key `tasks-controller`) — **per-user**, the second per-user indicator
  alongside `SharedEvents`: `isActive = auth()->user() !== null && Task::getTodoCount($user->id) > 0`.
  This reuses the model's own canonical predicate `Task::getTodoCount()` = `forUser($userId)->status('todo')->count()`,
  i.e. "the logged-in user has ≥1 task in the `todo` column" (`scopeForUser` = `assigned_to = me` OR
  `user_id = me AND assigned_to null`). The dot means *my* board has a todo task — a global
  `Task::where('status','todo')->exists()` would light everyone's dot regardless of their own tasks
  and is wrong here. Feasible because `StateService::get()` caches per user (`menu_state:v{ver}:u{id}`)
  and runs `isActive()` inside that per-user closure, so `auth()->user()` is available. The "new col"
  = `TaskStatus::Todo` ('todo', label «انجام نشده»), the default on create.
- **`TaskNudge`** (key `tasks-controller:nudge`) — **owner-based**: `for = User::active()->where('id',
  $subject->assigned_to ?? $subject->user_id)->get()` (empty → `collect()`). The recipient is the task's
  **owner** — the assignee if set, else the creator — which matches `scopeForUser`'s ownership (the
  creator owns an unassigned task; it sits in their `todo` col). This is *not* broadcast: a task is
  personal, so PostNudge/ReportNudge's all-active scoping would spam; only the owner gets the bell. The
  earlier assignee-only rule left unassigned tasks with **no** recipient (and thus no nudge row at all),
  which surprised users who create tasks for themselves unassigned — owner-based fixes that. `show = true`
  (fire-once, like PostNudge) — the bell persists until dismissed; the badge carries the "still in todo"
  state, so the nudge need not track status (a state-driven `show = status==='todo'` would auto-clear on
  todo→in-progress, but the reconcile engine deletes the row when `show` flips false, so a
  todo→in-progress→back-to-todo cycle would **resurface** a previously-dismissed nudge — a no-resurface
  violation; `show=true` avoids it). `refresh = true` (title embeds `$subject->title`).
- **Triggers** — `['created','updated','deleted','forceDeleted']`. `Task` uses `SoftDeletes`, so
  `forceDeleted` is a distinct Eloquent event that `deleted` does **not** cover; without it a
  force-deleted task's nudge row would orphan. `restored` is intentionally **not** in the nudge `on`
  (restore does not re-nudge) but **is** in `MENU_STATE_EVENTS` so the badge still updates. Soft-delete
  fires `deleted` and `Task::find()` then returns null (SoftDeletingScope) → reconcile blanket-deletes
  the rows. Reassignment works via `reconcile()` line `whereNotIn('notifiable_id', $ids)->delete()`
  (prunes the old assignee) + empty `for()` → blanket delete (unassign clears everyone).
- **`HasMenuState` on `Task`** — `const MENU_STATE_EVENTS = ['created','updated','deleted','restored','forceDeleted']`
  (the two extra events beyond the default are needed because of SoftDeletes: a restored todo task must
  re-bump the badge version, and a force-delete must too). Task had no `boot()`, so `bootHasMenuState()`
  coexists cleanly with `bootSoftDeletes()`.

## `ThsBadge` + `ThsNudge` — staged ticket routing (one row that migrates with status)

THS (`/ths`, `ths-controller` menu item) is a full-page Livewire route like tasks, so the badge dot
renders on the menu-modal item — no `Tabs.php`/navbar change. One ticket produces **one nudge row**
whose recipient changes with the ticket's stage; the row migrates rather than multiplies.

- **`ThsBadge`** (key `ths-controller`) — **per-user**, lit while the user has a not-closed ticket
  needing their action: `isActive = auth()->user() !== null && Ticket::hasUnclosedActionFor($user->id)`.
  `hasUnclosedActionFor($u)` = an `open` ticket whose `extra.target_department`'s highest-ranking member
  (via `User::highestRankingInDepartment()`, `HasProfileHierarchy`) is `$u`, **or** an `in-progress`
  ticket `assigned_to` `$u`. The requester is **not** an action recipient while waiting (no badge for
  them); they get the closing nudge instead.
- **`ThsNudge`** (key `ths-controller:nudge`) — **staged**: `for = collect([$subject->currentActionRecipient()])`
  where `Ticket::currentActionRecipient()` returns the single user responsible at the current status:
  `open` → highest-ranking of the target department, `in-progress` → `assignee`, `closed` → `requester`.
  The engine's `reconcile()` line `whereNotIn('notifiable_id', $ids)->delete()` is what makes the row
  migrate: open→in-progress prunes the dept-head's row and creates the assignee's; in-progress→closed
  prunes the assignee's and creates the requester's. `show = true` (the recipient set already encodes
  the stage, so no extra gate; matches `TaskNudge`). `title`/`body` are `match($subject->status)` so the
  refreshed row reads correctly for whichever stage it is in. `refresh = true`.
- **Triggers** — `['created','updated','deleted']`. `Ticket` has **no `SoftDeletes`**, so `forceDeleted`
  is not a valid event (unlike `Task`); a hard `deleted` is the only removal event and `Ticket::find()`
  returning null blanket-deletes the row. `MENU_STATE_EVENTS` mirrors this (no `restored`/`forceDeleted`).
- **Auto open→in-progress on assignment** — already wired in `TicketFormPresenter::assignedTo()`
  (`afterStateUpdated`: filled assignee + status open/null → `InProgress`; blank + InProgress → `Open`),
  matching the helper text «با انتخاب مسئول، وضعیت تیکت به «در حال بررسی» تغییر خودکار می‌کند». No new
  code needed for the admin-side shift; the nudge picks up the new stage off the updated ticket.
- **`HasMenuState` on `Ticket`** — `const MENU_STATE_EVENTS = ['created','updated','deleted']`; flushes
  the badge version on every ticket change so the dept-head/assignee dot updates promptly.
- **Default target department — never null (`PS ?? BS`)** — `SubmitTicketAction` stores `null` when no
  target is chosen, which would leave an `open` ticket unroutable. `Ticket::defaultTargetDepartment()`
  returns `'PS'` if `User::highestRankingInDepartment('PS')` is non-null, else `'BS'`, else `null`. A
  `booted()` `creating` hook persists this into `extra.target_department` for any ticket saved without one
  (covers both the user Livewire submit and the Filament admin create — one model-level chokepoint).
  `currentActionRecipient()` and `hasUnclosedActionFor()` also fall back to it at read time, so the
  pre-existing null-target `open` tickets route to the PS head without a backfill migration.

## `DmsBadge` + `DmsNudge` — sign/read pending (the `reads` row encodes both)

DMS (`/dms`, `dms-controller` menu item) is a full-page Livewire route, so the badge dot renders on the
menu-modal item — no `Tabs.php`/navbar change. The `reads` table already encodes the two distinct pending
states with **no schema change** and **no extra helpers** — the existing DMS Main computed props map 1:1:

- `reads.read = 1` → the document is **signed/confirmed** (the existing `ConfirmReadAction` flips this).
- `reads.read_count = 0` → **not read**; `read_count > 0` → read (incremented by `ConfirmReadAction` increment path).
- So: **needs sign** = visible live doc with no `reads` row having `read=1` (the existing `receivePendingCount`
  / `getUnsignedDocumentsCount`); **needs read** = visible live doc with `reads.read=1 AND read_count=0`
  (the existing `readPendingCount`).

Model source-of-truth (userId-parameterized, queue-safe — the auth-bound `scopeVisibleToUser` is not
callable from the queued reconcile job, so `DMS::visibleTo(int $userId)` mirrors it with an explicit id):

- `DMS::needsSignCount($u)` / `needsReadCount($u)` / `pendingCount($u)` / `hasPendingFor($u)` — the counts.
  `getUnsignedDocumentsCount()` is now a one-line delegate to `needsSignCount(auth()->id())` (single source).
- `DMS::requiresSignFor($u)` / `requiresReadFor($u)` / `isPendingFor($u)` — per-doc predicates (now unused by
  the nudge; `requiresSignFor` was replaced by the batched `signedUserIds()` below — keep until removal is
  confirmed, since it's a public model method).
- `DMS::signedUserIds()` — batched sign-status for one doc (`reads()->where('read', true)->pluck('user_id')`,
  one query); the nudge primes a flat set from it in `for()`.
- `DMS::pendingRecipients()` — visible live + pending users for one doc (the nudge `for()`). Fetches the
  doc's `reads(read=true)` once, derives `$signedIds` (users whose read rows have none with `read_count=0`,
  i.e. signed **and** read) from that collection, then excludes them via SQL `whereNotIn('id', $signedIds)`
  on the `User::active()` query — so signed-and-read users are dropped before hydration instead of pulling
  every active user and filtering in PHP (was `User::active()->get()` + post-fetch `->filter()`). Empty
  `$signedIds` compiles `whereNotIn` to `1 = 1` (all rows), preserving the "nobody signed yet" case.

- **`DmsBadge`** (key `dms-controller`) — **per-user**, `isActive = auth()->user() !== null &&
  DMS::hasPendingFor($user->id)`; lit while the user has any doc needing sign or read, fades once all are
  signed+read.
- **`DmsNudge`** (key `dms-controller:nudge`) — **per-document**, `for = $subject->pendingRecipients()`,
  `show = $subject->isPendingFor($user->id)`. `title`/`body` embed the doc's type label via the Filament lang
  strings (`__('resources/dms/strings/type.systematic')` / `non_systematic` → «سیستمی»/«غیر سیستمی») and the
  flavor: `requiresSignFor` → «نیازمند تأیید», else «نیازمند مطالعه». `refresh = true` so signing a doc
  rewrites the same row from «نیازمند تأیید» to «نیازمند مطالعه» (the row persists across the sign→read
  transition rather than being recreated). `badgeSuppressesCreate = false` — opted OUT, like
  `SharedEventsNudge`/`ContactNudge`, because the spec wants a nudge **per new record** even when the
  aggregate badge is already lit (the per-record bell is not redundant with the persistent dot here).
- **Triggers** — `DMS {created,updated,deleted}` (subject = the doc) **+ `Read {created,updated,deleted}`**
  (subject = `$read->dms`). The Read trigger is what makes the row clear when a user signs/reads:
  `ConfirmReadAction`'s `updateOrCreate` fires `Read created/updated` → reconcile the doc → `show` re-eval →
  sign flips the row to «نیازمند مطالعه», read (`read_count>0`) flips `show=false` → row deleted. The DMS
  `updated` trigger covers the revision-reset path (`DMS::booted()` mass-resets reads on file/revision change
  via a query-builder update that bypasses Read model events — but the DMS `updated` event still fires and
  re-reconciles, so a new revision re-notifies everyone). DMS `deleted` → `DMS::find()` null → blanket-delete
  the doc's rows (orphan cleanup).
- **`HasMenuState` on `DMS` and `Read`** — flushes the badge version on every doc and read change so the dot
  updates promptly when a user signs/reads (default `['created','updated','deleted']`; neither model uses
  SoftDeletes).

> **>4 aggregate — open.** The spec wants, when a user's pending docs exceed 4, **two distinct aggregate
> nudges** (one "total for read", one "total for sign") instead of per-document rows. The engine keys rows
> per `(menu_key, item_id)` where `item_id = subject->getKey()` and assumes one subject per item_id, so a
> single per-user aggregate row does not fit cleanly. It needs one additive engine hook — an optional
> `reconcileOn($subject): array` on `MenuNudge` (default `[[get_class($s), $s->getKey()]]`, preserving all 9
> existing nudges) letting a nudge dispatch per affected user with `subject = User` → one row per user
> (`item_id = userId`). Flagged for confirmation before touching the engine contract; per-doc `show` would
> then gate at `pendingCount ≤ 4` and two `DmsSignNudge`/`DmsReadNudge` classes would show at `> 4`.

## Open items (flagged, not auto-fixed)

(none — the `post` tab now carries `'badge' => 'posts-controller'`, surfacing `UnreadPosts`.)
## Audit #16–#25 (unanimous A+B+me)

**Code (behavior-preserving):**
- **#16** `ReconcileNudge` now declares `$tries = 3` / `$backoff = [10, 30]`; the `LockTimeoutException`
  swallow in `NudgeService::reconcile` is KEPT (it is de-dup, not a leak — two jobs reconcile the same
  `(ruleKey, itemId)`, the loser's lock-timeout is expected; the winner does the full reconcile). `$tries`
  closes the real gap: a crashed lock-holder re-runs instead of leaving stale rows, while normal concurrent
  duplicates stay silent (no `failed_jobs` noise).
- **#19** `EnergyTest::booted()` no longer manually calls `static::bootHasMenuState()` — Laravel auto-calls
  `boot{Trait}` on boot, so the manual call double-registered the flush (idempotent no-op, removed). DMS and
  Ticket already rely on auto-boot; EnergyTest was the outlier.
- **#21** `DMS::booted()` adds `static::deleted(fn (DMS $d) => $d->reads()->delete())`. The live DB has NO
  foreign key on `reads.document_id` (project runs FK-less; verified via `information_schema`), so a model
  hard-delete orphaned reads. The hook cascades on model-event deletes (consistent with the existing
  `updated` reset hook). `DMS use HasMenuState` → `DMS::deleted` already flushes the global menu cache, so
  the mass `reads()->delete()` (no Read events) leaves no cache gap. Query/bulk deletes still bypass — see
  #17.

**Document-only (no code, no behavior change):**
- **#17** Query/bulk DMS deletes (`DMS::where(...)->delete()` / `DB::table('dms')->delete()`) bypass model
  events → no reconcile, so nudge rows + reads orphan. Model-event deletes are covered (`DmsNudge` triggers
  on `DMS::deleted` → reconcile null-subject branch cleans nudge rows; #21's hook cleans reads). A periodic
  sweeper is the only real closer for query-deletes and is a separate infra task (a DB FK would not help —
  nudge rows live in `notifications.data->item_id` JSON, not FK-able).
- **#20** `DMS::pendingRecipients()` (PHP, doc→users) and `DMS::visibleTo($userId)` (SQL, user→docs) encode
  the same visibility rule (`owners` contains `ALL`/dept OR `users` contains id) in OPPOSITE directions and
  runtimes. A shared primitive would have to abstract over both a PHP collection-filter and a SQL builder —
  a mini-DSL, not a minimal helper; forcing it obscures both. Touch both deliberately on any visibility
  change.
- **#22** Invariant `read_count > 0 ⇒ read = true` (must sign before reading; `read=true & read_count=0` is
  the signed-not-read state). Enforced BY CONSTRUCTION, not by a model guard: `ConfirmReadAction::execute`
  does `updateOrCreate([…],['read'=>true])` BEFORE `increment('read_count')`; `DMS::updated` mass-sets
  `['read'=>false,'read_count'=>0]` together. A `Read::saving` guard was rejected — `saving` does NOT fire
  on `Model::increment()` (query-level UPDATE) or mass `Builder::update()`, the only paths setting
  `read_count>0`, so the guard would be dead code advertising protection it can't deliver. Revisit only if a
  future writer does `$read->read_count = N; $read->save();`.
- **#23** `Ticket::currentActionRecipient()` keeps its live fallback `$this->targetDepartmentId ?:
  static::defaultTargetDepartment()`. `targetDepartmentId` is the FROZEN `extra['target_department']` (set
  at creation via the `creating` hook); `defaultTargetDepartment()` is the `once()`-cached LIVE default.
  Empirically the majority of open tickets are legacy null-target (pre-hook) — dropping the fallback would
  silence their nudge AND break badge↔nudge consistency (`hasUnclosedActionFor` keeps the `COALESCE →
  defaultTargetDepartment()` fallback so the badge still lights). Both on the live fallback = consistent
  today, zero behavior change. Legacy-vs-frozen divergence is inherent (the creation-era default is not
  recoverable); an optional one-time backfill freezing the current default into legacy `extra` would be a
  behavior change needing explicit sign-off.
- **#24** A department-head change does not re-fire a THS reconcile, so open-ticket NUDGE rows target the
  old head until the next `Ticket {created,updated,deleted}` event re-reconciles (`whereNotIn` prunes the
  old head, creates for the new). The THS BADGE `hasUnclosedActionFor()` is pull/live → reflects the new
  head immediately (correct authoritative signal); the one-time nudge lags and self-heals. A User/Profile
  listener re-dispatching `ReconcileNudge` was rejected — it must detect the head-change condition and
  couples the THS nudge to non-Ticket events, breaking the per-subject trigger model. Badge-covers-it is
  acceptable.

**No code (already covered):**
- **#18** re-poke on refresh — closed by #3's write-gate (`if ($existing->data != $data) update(…)`):
  identical data → no `update()` → no `updated_at` bump → no Filament re-sort/re-poke; legit content changes
  still update.
- **#25** "duplicated open-dept logic" between `currentActionRecipient` (PHP) and `hasUnclosedActionFor`
  (SQL `COALESCE`) — moot under #23's keep-the-fallback: only one PHP caller, the SQL `COALESCE` can't reuse
  a PHP accessor, and `booted().creating` is the WRITER (different role). Extracting
  `resolvedTargetDepartment()` would DRY zero call sites.
