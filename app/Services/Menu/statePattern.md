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
│   └── SharedEvents.php            key=shared-events         isActive = EventShare::hasImminentFor($u) || Event::hasImminentSharedFor($u)
├── Notifications\
│   ├── ActiveAdsNudge.php          key=ads-controller:nudge        triggers=Ad created/updated/deleted
│   ├── SharedEventsNudge.php       key=shared-events:nudge         triggers=EventShare created/deleted + Event updated/deleted
│   └── SuggestionNudge.php         key=suggestion-controller:nudge triggers=Suggestion updated/deleted + Review created/updated
├── StateService.php                cache + version + sync orchestration (badge side)
├── BadgeSyncService.php            one-row-per-indicator reconcile (badge side)
└── NudgeService.php          registry + dumb engine (nudge side); register(MenuNudge) adapts a nudge into the rule array the engine consumes

App\Jobs\ReconcileNudge.php            queued unit of work for a per-record reconcile (nudge side)
App\Providers\NudgeServiceProvider.php registers the 3 nudge classes in boot(): NudgeService::register(new ...Nudge())
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

`SharedEvents` is the per-user one — it reads the logged-in user inside `isActive()` and lights for
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
        $resolved = [];
        foreach ($this->indicators as $indicatorClass) {
            $indicator = app($indicatorClass);
            $resolved[$indicator->getKey()] = $indicator->isActive();
        }
        if ($user) { $this->syncIndicators($user, $resolved); }
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
  design fired `sync()` per render, issuing a SELECT + dedup-DELETE per active indicator). Each
  `sync()` call is wrapped in `try/catch` so a notification-DB failure degrades gracefully (stale
  badge until the next `flush()`) instead of breaking the menu render or poisoning the cached bool
  map.
- **`syncIndicators`** loops the same `$indicators` list and calls
  `$syncService->sync($user, $indicator, $state[$key] ?? false)`.

### `BadgeSyncService::sync()` — one row per indicator

Reconciles the **single** notification row for one indicator on one user. Pure state-machine, three
branches:

```php
$query = $user->notifications()
    ->where('type', FilamentDatabaseNotification::class)
    ->where('data->menu_key', $indicator->getKey());   // bare key, no suffix → never matches nudge rows

if (!$isActive) { $query->delete(); return; }          // inactive → delete the row
if ($query->exists()) { return; }                      // active + row exists → LEAVE IT (preserve read_at)
$user->notifications()->create([ …unread row, menu_key => $indicator->getKey() ]);  // active + no row → create
```

- The query is scoped to the **bare** `menu_key`, so it physically cannot see `:nudge` rows.
- **No-resurface is here.** `exists()` counts **any** `read_at` state (read *or* unread). A dismissed
  nudge (read) for an indicator that is still active is left untouched — `sync()` never re-unreads
  an existing row. The row only ever leaves by going inactive (`delete`). If the condition later
  re-occurs, a fresh unread row is created (a new nudge, not a resurface).
- The old code did two things that both re-added dismissed nudges to the unread bell: it reset
  `read_at = null` on version change, *and* it globally purged all menu-badge rows on `flush()`.
  Both were removed. `flush()` is now a pure version bump; the dismissed row survives every flush
  and `sync()` leaves it read.
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
  `resources/js/components/alpine/data/menu.js` array (`*-controller` style). Only
  `ads-controller` and `suggestion-controller` have matching item ids, so they can render there.
  `shared-events` has **no** item id in `menu.js` → it is menu-invisible by design; the sidebar tab
  is its only surface.
- **Sidebar tabs** — `Tabs::tabs()` entries may carry a nullable `'badge' => '<indicator key>'`
  field. `navbars/right.blade.php` and `navbars/bottom.blade.php` render the ping dot when
  `isset($tab['badge']) && ($menuState[$tab['badge']] ?? false)`. Currently only the `calendar` tab
  carries `'badge' => 'shared-events'`. Adding a tab dot for any future indicator that gains a
  sidebar tab = one `'badge' => '<key>'` line on that tab entry; **both navbars are already wired**,
  so no blade change is needed. The left rail (`navbars/left.blade.php`, hard-coded `/tasks`
  `/dms` `/ths`) has no indicators mapped and is untouched.
- **Indicators with no sidebar tab** — `ads-controller` and `suggestion-controller` are full-page
  routes reached via the menu (`/ads`, `/suggestion`), not sidebar tabs, so they stay menu-modal-only
  by design. Forcing a dot onto an unrelated tab would mislabel the signal.

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
  property the badge system gets from `BadgeSyncService::sync()`'s `exists()` branch, expressed in
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
- **`:nudge` suffix** — isolation from the badge system (the badge's `sync()` queries strictly by
  the bare key, this queries strictly by the suffixed key).
- **`subject` resolver** — lets a *foreign* trigger reconcile a *different* record's nudge. Review
  writes flip a Suggestion's attention but fire no `Suggestion` event; binding Review with
  `subject = $review->suggestion` and the **same key + item_id (suggestion id)** as the Suggestion
  rule makes both triggers reconcile the **same** per-recipient rows. Both triggers live in one
  `SuggestionNudge` class, so `show`/`for`/`title`/`body` are literally the same methods on one
  class and cannot drift.

### The rules (`NudgeServiceProvider`)

Each row is one trigger declared inside a `MenuNudge` class in `Notifications\` (`ActiveAdsNudge`,
`SharedEventsNudge`, `SuggestionNudge`); one class can declare several triggers sharing the same key.

| Trigger | `on` | `subject` | `show` | `for` (recipients) |
|---|---|---|---|---|
| `Ad` | created, updated, deleted | self | `$ad->active` | `User::active()->get()` |
| `EventShare` | created, deleted | `$share->event` | per-user: owner → `shares()->exists() && date>=now`; recipient → `isSharedWith($user) && date>=now` | `[owner] + all current share recipients` |
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
| no-resurface mechanism | `BadgeSyncService::sync()` `exists()` branch leaves `read_at` alone | `reconcile()` `exists()` branch leaves `read_at` alone |
| invalidation | `StateService::flush()` (global version bump) | n/a (event-driven; re-fetches fresh) |
| recipient model | one row per user per indicator | `for()` candidate set + per-recipient `show()` gate |
| auth at write time | `get()` runs in-request with `auth()->user()` | none — `for`/`show` carry the user explicitly |

Both rely on the **same** `exists()` → leave-it-alone primitive for no-resurface, just expressed in
pull vs push form. Both write rows of the same `type`/`notifiable_type`; only the `menu_key` shape
separates them.

## Keys & isolation (the one rule that keeps them from colliding)

- Badge keys are **bare** (`ads-controller`). `BadgeSyncService::sync()` filters
  `where('data->menu_key', $indicator->getKey())` — bare key only.
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