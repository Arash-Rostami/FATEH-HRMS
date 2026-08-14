# StateLogic — the `App\Services\Menu` notification system

The single reference for the **entire** menu notification mechanism: two coexisting signals that
share one storage (Filament's `notifications` table) but never touch each other. Read this before
editing anything in `App\Services\Menu`, adding an indicator, or wiring a new nudge trigger.

- **Signal 1 — Badge / dot** (`StateService` + `BadgeSyncService` + `Indicators\*`): the permanent,
  aggregate status dot on a menu item. One row **per indicator**. **Pull** — reconciled on menu
  render. Not dismissable. Stays lit the whole time the condition is true.
- **Signal 2 — Record nudge / bell** (`NudgeService` + `ReconcileNudge` + `NudgeServiceProvider`):
  a one-time, dismissable bell entry. One row **per qualifying record**. **Push** — reconciled on
  the record's Eloquent event. Never resurfaces once dismissed.

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
│   ├── SharedEvents.php            key=shared-events         isActive = !StateService::viewedToday('calendar') && (EventShare::hasImminentFor($u) || Event::hasImminentSharedFor($u))
│   ├── UnreadPosts.php             key=posts-controller      isActive = Post::hasUnreadFor($u)
│   ├── UnreadFeeds.php             key=feeds                 isActive = auth()->user() !== null && Feed::hasUnreadFor($user->id)   (per-user, via HasNudgeTracking)
│   ├── UnreadMessages.php          key=contacts-controller   isActive = auth()->user() !== null && Message::hasUnreadFor($user->id)
│   ├── SpecialDays.php             key=special-days          isActive = !StateService::viewedToday('calendar') && Profile (non-terminated) whereMonth/whereDay(birthdate|start_date) = today
│   ├── TasksTodo.php               key=tasks-controller      isActive = auth()->user() !== null && Task::getTodoCount($user->id) > 0   (per-user)
│   ├── EnergyTestBadge.php         key=energy-controller     isActive = $user !== null && EnergyTest::canSubmit($user->id)
│   ├── ThsBadge.php                key=ths-controller        isActive = auth()->user() !== null && Ticket::hasUnclosedActionFor($user->id)
│   └── DmsBadge.php                key=dms-controller        isActive = auth()->user() !== null && DMS::hasPendingFor($user->id)
├── Notifications\
│   ├── AdNudge.php          key=ads-controller:nudge        triggers=Ad created/updated/deleted
│   ├── SharedEventsNudge.php       key=shared-events:nudge         triggers=EventShare created/deleted + Event updated/deleted
│   ├── SuggestionNudge.php         key=suggestion-controller:nudge triggers=Suggestion created/updated/deleted + Review created/updated
│   ├── PostNudge.php               key=posts-controller:nudge      triggers=Post created/updated/deleted show=true  for=User::active()
│   ├── FeedNudge.php                key=feeds:nudge                 triggers=Feed created/updated/deleted show=true  for=User::active()
│   ├── PhotoNudge.php              key=gallery-controller:nudge   triggers=Photo created/updated/deleted show=true  for=dept-scoped (Photo.all_departments + 'MA', empty→all active)
│   ├── ReportNudge.php             key=reports-controller:nudge   triggers=Report created/updated/deleted show=$report->active  for=User::active()
│   ├── TaskNudge.php               key=tasks-controller:nudge     triggers=Task created/updated/deleted/restored/forceDeleted + Reply created (subject=$reply->repliable, repliable_type-guarded)  show=true (false when latestReply is own & not owner)  for=owner + otherReplyParticipants([user_id, assigned_to])
│   ├── ThsNudge.php                key=ths-controller:nudge       triggers=Ticket created/updated/deleted + Reply created (subject=$reply->repliable, repliable_type-guarded)  show=true (false when latestReply is own & not currentActionRecipient)  for=currentActionRecipient + otherReplyParticipants([requester_id, assigned_to])  badgeSuppressesCreate=false
│   ├── DmsNudge.php                key=dms-controller:nudge       triggers=DMS created/updated/deleted + Read created/updated/deleted show=true  for=DMS::pendingRecipients() (visible live + pending users)  badgeSuppressesCreate=false
│   ├── ChannelNudge.php            key=channels-controller:nudge  dual-state row migrates on entered_at (invited=entered_at IS NULL via Channel::invitedUserIds; unread=entered + count>0 via Channel::unreadCountsFor, whereNotNull(entered_at) + whereNull(msg.deleted_at)) like ThsNudge  triggers=Channel deleted/forceDeleted (cleanup) + ChannelMessage created/deleted (subject=$msg->channel)  show=true  for=invited∪unread (two indexed queries, for()-primes-body idiom)  reuses the three existing dispatch sites (SyncChannelMembers/MarkChannelRead/LeaveChannel); send path covered by ChannelMessage::created → no new dispatch
│   └── ContactNudge.php            key=contacts-controller:nudge  triggers=Message created/updated/deleted/forceDeleted/restored  subject=sender User  show=true  for=active recipients with unread (Message::unreadCountsFrom($sender), for()-primes-body idiom)  badgeSuppressesCreate=false
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
    public function getKey(): string;
    public function isActive(): bool;
    public function getTitle(): string;
    public function getBody(): string;
}
```

An indicator is a **stateless, read-only** object: `isActive()` reads the DB (or `auth()->user()`)
fresh and returns a bool. It owns no row and writes nothing. **All 11 indicators implement it**,
all structurally identical (no per-user method, no sub-interface).

| Indicator | `getKey()` | `isActive()` |
|---|---|---|
| `ActiveAds` | `ads-controller` | `Ad::active()->exists()` |
| `PendingSuggestions` | `suggestion-controller` | `Suggestion::attentionRequired()->exists()` |
| `SharedEvents` | `shared-events` | `auth()->user() !== null && (EventShare::hasImminentFor($user) \|\| Event::hasImminentSharedFor($user))` |
| `UnreadPosts` | `posts-controller` | `Post::hasUnreadFor($user)` (via `HasNudgeTracking`) |
| `UnreadFeeds` | `feeds` | `auth()->user() !== null && Feed::hasUnreadFor($user->id)` (via `HasNudgeTracking`) |
| `UnreadMessages` | `contacts-controller` | `auth()->user() !== null && Message::hasUnreadFor($user->id)` |
| `SpecialDays` | `special-days` | `!StateService::viewedToday('calendar') && Profile non-terminated whereMonth/whereDay(birthdate\|start_date)=today` |
| `TasksTodo` | `tasks-controller` | `auth()->user() !== null && Task::getTodoCount($user->id) > 0` |
| `EnergyTestBadge` | `energy-controller` | `$user !== null && EnergyTest::canSubmit($user->id)` |
| `ThsBadge` | `ths-controller` | `auth()->user() !== null && Ticket::hasUnclosedActionFor($user->id)` |
| `DmsBadge` | `dms-controller` | `auth()->user() !== null && DMS::hasPendingFor($user->id)` |

`SharedEvents`, `TasksTodo`, `UnreadPosts`, `UnreadFeeds`, `UnreadMessages`, `EnergyTestBadge`,
`ThsBadge`, `DmsBadge` read the logged-in user **explicitly** inside `isActive()`. `PendingSuggestions`
calls `Suggestion::attentionRequired()->exists()` with no `$user` argument — but
`scopeAttentionRequired()` (`HasSuggestionAlert::scopeAttentionRequired`) defaults `$user ??= auth()->user()`
internally, so it is **also per-user**, not global. Only `ActiveAds` (`Ad::active()->exists()`) and
the date-branch of `SpecialDays` are genuinely global, no-user existence checks. Per-user indicators
work because `StateService::get()` caches per user (`menu_state:v{ver}:u{id}`) and runs `isActive()`
inside that per-user closure, so `auth()->user()` is available.

> **`PendingSuggestions` condition, precisely (`HasSuggestionAlert::scopeAttentionRequired`)** —
> returns no rows (`whereRaw('1=0')`) if the auth user has no `profile->department_id`. The scope
> unions two role-guarded branches (the second with three query sub-conditions); a regular employee
> matches neither, so it never lights — by design.
> - **`isSeniorDecisionMaker()`** (a `chairman`/`ceo` top executive, OR — when no top executive
>   exists org-wide — any MA-department user as fallback) lights on any `stage = 'awaiting_decision'`.
> - **`isDeptHead() && !isTopExecutive()`** (a department head who is NOT chairman/ceo) lights on
>   either (a) `stage = 'team_remarks'` routed to their dept (`departments->[0] = deptId`), or
>   (b) `stage = 'dept_remarks'` targeting their dept (`whereJsonContains('departments', $deptId)`)
>   whose dept hasn't yet submitted a review (`agree`/`disagree`/`neutral`), or (c) MA referred a
>   suggestion to their dept (a review with `department_id = 'MA'` whose `referral` JSON contains
>   their dept) and their dept's own review is still `complete = false`.
> `PendingSuggestions` is action-based (clears only by submitting the review/decision, never by
> merely viewing the Suggestions page), not global-status like `ActiveAds`.
> `SuggestionNudge::show()` reuses `Suggestion::requiresAttentionFor($s, $user)` — one condition,
> no drift between badge and bell.

The attention logic lives on the model (`Suggestion::attentionRequired` scope,
`EventShare::hasImminentFor`, `Event::hasImminentSharedFor`, `Ad::active`), so the indicator is a
thin wrapper — the query lives on the model that owns the data. Adding a badge indicator = one class
implementing `MenuBadge` + one line in `StateService::$indicators`.

### Calendar "viewed today" gate — `StateService::markViewed()` / `viewedToday()`

Both Calendar badges (`SharedEvents`, `SpecialDays`) additionally check
`!StateService::viewedToday('calendar')` before their date logic. This is a **separate mechanism
from the nudge/notification tables** — a plain per-user `Cache::put` keyed
`tab_viewed:{tab}:{userId}`, storing today's date string, expiring at `now()->endOfDay()`:

```php
public static function markViewed(string $tab): void { … }
public static function viewedToday(string $tab): bool { … }
```

`Calendar::mount()` calls `StateService::markViewed('calendar')` on every open (the tab is recreated
per switch via `<livewire:dynamic-component :key="$activeTab">`, so this fires each time).
`markViewed()` short-circuits when `viewedToday()` is already true — without this guard, every
same-day re-open would re-bump the **global** version (`flush()` is not per-user), forcing every
other user's cached menu state to recompute (the thundering-herd `HasMenuState` avoids elsewhere).
The gate is intentionally coarse — it does not distinguish *which* event/special day was seen, only
"this user opened Calendar today." Trade-off: if a new event becomes imminent later the same day
after Calendar was viewed, the dot stays suppressed until the flag expires at midnight (no per-record
freshness signal without building real nudge-tracking for `SharedEvents`, which would change its
24h-window semantics — out of scope). No such gap exists for `SpecialDays` — a birthday/anniversary
is a once-a-day occurrence. This gate does **not** touch the `shared-events:nudge` bell rows.

### `StateService` — cache + version + sync

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
  now()->getPreciseTimestamp())` — a **global cache-version bump only**. No per-user purge, no row
  wipe. Every user's next `get()` builds a new cache key, so the old entry expires; the closure
  re-runs and recomputes every indicator fresh.
- **The bool map is the menu dot's source.** `menu.blade.php` reads `@js($menuState)[item.id]` —
  the dot is lit/dark straight from the cached bool. Not dismissable: reflects live status.
- **Sync runs only on a cache miss**, inside the `Cache::remember` closure — once per version per
  user, not on every render. A cache hit = one cache read and **zero DB queries**. The whole
  `syncBatch()` call is wrapped in `try/catch (\Throwable $e) { report($e); }` so a notification-DB
  failure degrades gracefully (stale badge until next `flush()`) instead of breaking the menu render.
- `get()` builds `$instances` **once** (reused for `isActive()` + sync), then hands the whole batch
  to `$syncService->syncBatch($user, $instances, $resolved)` — indicators are not instantiated twice.

### `BadgeSyncService::syncBatch()` — whole batch in 3 queries

Reconciles **all** indicator rows for one user in one pass (replaces the old per-indicator `sync()`).
**At most 3 queries**: one SELECT (existence), one bulk DELETE (inactive keys), one bulk INSERT
(new active rows); DELETE/INSERT are guarded so skipped when their batch is empty.

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

- All queries scoped to the **bare** `menu_key` via `whereIn('data->menu_key', …)`, so they cannot
  see `:nudge` rows.
- **No-resurface preserved.** `existingByKey` counts **any** `read_at` state (read *or* unread). A
  dismissed nudge for an active indicator is left untouched — never re-unread. The row only leaves
  by going inactive (bulk `delete`). Re-activation inserts a fresh unread row (new nudge).
- **Per-indicator isolation.** The build loop body is wrapped in `try/catch` so one throwing
  indicator is logged and skipped — the rest of the batch still partitions. The outer `try/catch` in
  `StateService::get()` covers the 3 queries themselves.
- **Bulk insert bypasses Eloquent.** `$user->notifications()->insert($toInsert)` writes rows
  directly, so morph columns, `json_encode($data)`, `id` (`Str::uuid()`), and timestamps must be set
  manually — Eloquent casts/events don't fire on raw `insert()`.
- `flush()` is a pure version bump; the dismissed row survives every flush and `syncBatch()` leaves
  it read. No `version`/`cleared` bookkeeping fields are written — payload carries only `menu_key`
  + static title/body/action. The "حذف اعلان" action calls `markAsRead()`.

### Badge lifecycle

- condition active → next `get()` cache miss → sync → no row → create unread → dot lit, bell shows.
- dismiss → `read_at` set; **dot stays lit** (condition still true); bell unread clears.
- condition inactive → next recompute → `!isActive` → delete row.
- re-activate → next recompute → no row → create fresh unread → a *new* nudge, not a resurface.

### Who calls `flush()` (invalidation)

`flush()` is the one invalidation primitive, reused across modules. Anything that can change a
badge's truth must bump the version. It is invoked via the `HasMenuState` trait (see below) on
`Ad`, `Post`, `Suggestion`, `Feed`, `Profile`, `Task`, `Ticket`, `DMS`, `Read`, `EnergyTest`, plus
`EventShare::booted()` `created`/`deleted` and `Event::booted()` `updated`/`deleted`. The user-panel
`ShareEventAction` uses bulk `insertOrIgnore`/query-`delete()` which **bypass** model events, so it
calls `flush()` explicitly afterward — no double-fire. Both `EventShare` and `Event` wrap the flush
in `DB::afterCommit()`. `flush()` does **not** purge rows; it only forces the next `get()` to
recompute.

### Where the dot renders (surfaces)

`$menuState` (the `get()` bool map) is injected by **one** `View::composer` array in
`ViewServiceProvider` into three views — `components.dashboard.modal.menu`,
`components.dashboard.navbars.right`, `components.dashboard.navbars.bottom` — all calling the same
cached `MenuStateService::get()`, so within a request the first call may hit the DB and the rest are
cache hits.

- **Menu modal** — dot per item via `@js($menuState)[item.id]`; item ids come from the static
  `resources/js/components/alpine/data/menu.js` array (`*-controller` style). `ads-controller`,
  `suggestion-controller`, `tasks-controller`, `ths-controller`, `dms-controller`, `contacts-controller`,
  `energy-controller` have matching item ids (`menu.js` lines for `contacts-controller`/`energy-controller`
  verified 2026-08-13 — a prior revision of this doc incorrectly listed both as menu-invisible), so they
  render there. `shared-events`, `posts-controller`, `feeds`, `special-days` have **no** item id in
  `menu.js` → menu-invisible by design; their surface is the sidebar tab (or no surface).
- **Sidebar tabs** — `Tabs::tabs()` entries may carry a `'badge' => '<indicator key>'` field (string
  **or** array of keys — see "Array `badge` slot" below). `navbars/right.blade.php` and
  `navbars/bottom.blade.php` render the ping dot when
  `collect((array) $tab['badge'])->contains(fn($k) => $menuState[$k] ?? false)`. Currently:
  `calendar` → `['shared-events','special-days']`, `post` → `posts-controller`, `feed` → `feeds`.
  Adding a tab dot for any future indicator = one `'badge' => '<key>'` line on that tab entry;
  **both navbars are already wired**. The left rail (`navbars/left.blade.php`, hard-coded `/tasks`
  `/dms` `/ths`) has no indicators mapped and is untouched.
- **Full-page routes without a sidebar tab** (`ads-controller`, `suggestion-controller`,
  `tasks-controller`, `ths-controller`, `dms-controller`, `contacts-controller`, `energy-controller`)
  stay menu-modal-only by design. Forcing a dot onto an unrelated tab would mislabel the signal.

---

## Signal 2 — Record nudge / bell

A **separate, additive** layer producing **one new bell nudge per qualifying record** rather than the
badge's one-row-per-indicator aggregate. Sits alongside the badge system and never edits it.

### The rule shape

```php
class SomeNudge implements MenuNudge
{
    public function getKey(): string { return 'some-menu_key:nudge'; }
    public function triggers(): array {
        return [
            ['class' => TriggerModel::class, 'on' => ['created','updated','deleted'], 'subject' => null],
            ['class' => ForeignModel::class, 'on' => ['created','updated'], 'subject' => fn($m) => $m->relation],
        ];
    }
    public function show($record, User $user): bool { ... }
    public function for($record) { ... }
    public function title($record, User $user): string { ... }
    public function body($record, User $user): string { ... }
    public function refresh(): bool { return false; }
}
```

NudgeService::register(new SomeNudge());
```

`triggers()` declares each Eloquent model + the events that re-fire this nudge's reconcile. `subject`
(per-trigger, default `null` = trigger model itself) is set when the nudge is about a **different**
record than the trigger — `Review → $review->suggestion`, `EventShare → $share->event`,
`Message → sender User`. `show`/`title`/`body` receive the **subject** and the **user**, so one rule
can produce a different message per recipient. `register()` is a thin **adapter** building the rule
array the engine consumes, then binds each trigger's events — the engine itself is unchanged.

### Optional: clickable deep-link (`url()`)

A nudge MAY implement `url($subject): ?string` to make its bell entry clickable. Additive and
independently removable — `register()` captures it via `method_exists($nudge, 'url')` (default
`null`), the same optional-hook convention `badgeSuppressesCreate()` uses; not a new `MenuNudge`
contract method. No new UI: `NudgeService::buildData()` prepends a
`Filament\Actions\Action::make('view')->label('مشاهده')->url($href)` to the notification's `actions`
array; Filament's database-notifications modal already renders any action with a `url` as a real
link. Every `url($subject)` reuses the existing deep-link convention
`route($name, ['open' => $subject->getKey()])` (or `route('dashboard', ['tab' => $tab, 'open' => $id])`
for tab-hosted modules) — the same `?open={id}` param `App\Traits\FocusOnRecord` consumes and
`SearchResource::route()`/`tab()`/`url()` use. `refresh` governs the URL the same way it governs
title/body: set `refresh() => true` and an edit re-fires reconcile to rewrite `url` on a still-
**unread** row; a **read** row's `url` is never rewritten.

All 12 nudges implement `url()`: `AdNudge`→`route('ads', …)`,
`SharedEventsNudge`→`route('dashboard', ['tab'=>'calendar', …])`, `SuggestionNudge`→`route('suggestion', …)`,
`PostNudge`→`route('dashboard', ['tab'=>'post', …])`, `FeedNudge`→`route('dashboard', ['tab'=>'feed', …])`,
`PhotoNudge`→`route('dashboard', ['tab'=>'gallery', …])`, `ReportNudge`→`route('dashboard', ['tab'=>'reports', …])`,
`TaskNudge`→`route('tasks', …)`, `ThsNudge`→`route('ths', …)`, `DmsNudge`→`route('dms', …)`,
`ChannelNudge`→`route('channels', …)`, `ContactNudge`→`route('contact', …)` (subject is the message
sender `User`, so this links to the conversation, not a specific message).

### Engine — `register()`

1. **Idempotency guard** keyed by `key` (one nudge class = one rule, declaring all its triggers) —
   a provider re-boot (tests, Octane, queue worker restart) does **not** stack duplicate listeners.
   `reset()` clears both maps for tests.
2. Store the rule under `menu_key`.
3. For each event in `on`, bind `TriggerModel::{event}` to a closure that resolves the **subject**;
   if `null` (foreign trigger whose parent is gone), returns; otherwise
   `dispatch(new ReconcileNudge($key, get_class($subject), $subject->getKey()))->afterCommit()`.

The dispatch carries only **primitives** (rule key, subject class string, subject id) — never a
model instance — so no model-serialization problem when the job runs later.

### Engine — `reconcile()` (runs inside the job)

```
look up rule by key; if missing, return
itemId = (string) subjectId

try { Cache::lock("nudge:k{key}:i{itemId}", 10)->block(3, function () {
    fresh() = a NEW query each call:
        DatabaseNotification::where('type', FilamentDatabaseNotification::class)
            ->where('notifiable_type', User::class)
            ->where('data->menu_key', key)
            ->where('data->item_id', itemId)

    subject = subjectClass::find(subjectId)

    if subject === null:
        fresh()->delete(); return
    recipients = rule.for(subject);  ids = recipients.id[]
    if ids empty:
        fresh()->delete(); return
    fresh()->whereNotIn('notifiable_id', ids)->delete();
    foreach recipients as user:
        if !rule.show(subject, user):
            fresh()->where('notifiable_id', user.id)->delete(); continue
        if fresh()->where('notifiable_id', user.id)->exists():
            if rule.refresh && existing.read_at === null:
                existing.update(['data' => buildData(…)])
            continue
        if rule.badge_suppress
            && a bare-key badge row exists for user (menu_key = beforeLast(key, ':nudge')):
            continue
        user->notifications()->create([ …unread row: title, body, menu_key, item_id ])
}); } catch (LockTimeoutException) { … }
```

Design choices:

- **`afterCommit()` dispatch** — job runs **after the save transaction commits**, so the `Cache::lock`
  is acquired *outside* the transaction. A rolled-back save cannot un-release the lock. This is why
  `QUEUE_CONNECTION` alone switches sync↔worker with **no code branch**.
- **Re-fetch the subject fresh** — job reads *current* state, not the event-time snapshot. Deleted
  records come back `null` → blanket delete. Also removes any need to serialize the model.
- **Lock per `(key, item_id)`** — serializes only concurrent reconciles for the *same record*
  (different records run in parallel). Inside the lock, existence-check + insert is atomic, so
  **no duplicate rows without a unique index** (the index path would tax *every* notification write).
- **`exists()` counts any `read_at` state** — a dismissed (read) nudge for a still-qualifying record
  is left untouched and **never recreated** — the no-resurface guarantee, the push counterpart of
  `BadgeSyncService::syncBatch()`'s `existingByKey`.
- **`refresh` flag (opt-in)** — when `refresh => true`, reconcile rewrites `data` (title/body/url) on
  an existing **unread** row instead of `continue`-ing. All nudges with a live mutable label
  (`{event.title}`, `{ad.position}`, `{suggestion->title}`, etc.) set `refresh = true`. A **read**
  row is never rewritten (`read_at` preserved) → no re-notification. `buildData()` is a pure DRY
  extraction shared by create + refresh paths.
- **Prune `whereNotIn('notifiable_id', ids)`** — a user leaving the recipient set (role/dept change)
  while the record still qualifies has their stale row removed.
- **`notifiable_type = User::class` filter** — cross-user deletes never sweep other notifiable types.
- **`LockTimeoutException` swallowed** — a contested lock degrades gracefully (next event
  re-reconciles) instead of breaking the triggering save.
- **`:nudge` suffix** — isolation from the badge system. Also enables badge-overlap suppression: the
  nudge's bare key is recovered as `Str::beforeLast($key, ':nudge')`.
- **Badge-overlap suppression (`badge_suppress`, default ON)** — before creating a recipient's nudge
  row, `reconcile()` checks whether that user already has a **bare-key badge row** for the same menu
  key. If yes, the CREATE is skipped (badge already reminds them); the existing-row refresh path is
  unaffected. Prevents duplicate "two cards for one event" on fully-overlapping modules (Tasks, Ads,
  Posts, Feeds, Suggestions). The lazy timing of the badge row (written at a prior `get()`, before
  the new subject existed) gives "excluding the current item" semantics for free. `register()`
  captures the flag via `method_exists($nudge, 'badgeSuppressesCreate')` (default `true`). A nudge
  opts OUT by implementing `badgeSuppressesCreate(): bool { return false; }` — required only where
  the badge condition is **not** a superset of the nudge condition. Five opt-outs: `SharedEventsNudge`
  (badge = imminent ≤24h, nudge = any future event), `ContactNudge` (badge = any unread, nudge =
  per-chat; a new chat must still alert even when another chat already lit the badge), `DmsNudge` /
  `ThsNudge` (a `Read`/`Reply` on an already-badge-lit doc/ticket must still CREATE a nudge for the
  reply-participants the badge does not track), and `ChannelNudge` (nudge-only — no `Channel`
  indicator badge exists, so CREATE must always fire). Gallery/Reports have no matching badge row, so
  the guard is a no-op there; Ads/Posts/Feeds/Suggestion/Task keep the default because their badge is
  a superset of the nudge.
- **`subject` resolver** — lets a *foreign* trigger reconcile a *different* record's nudge. Review
  writes flip a Suggestion's attention but fire no `Suggestion` event; binding Review with
  `subject = $review->suggestion` and the **same key + item_id (suggestion id)** makes both triggers
  reconcile the **same** per-recipient rows, in one `SuggestionNudge` class — `show`/`for`/`title`/
  `body` cannot drift.

### The rules (`NudgeServiceProvider`)

Each row is one trigger declared inside a `MenuNudge` class in `Notifications\`; one class can
declare several triggers sharing the same key.

| Trigger | `on` | `subject` | `show` | `for` (recipients) |
|---|---|---|---|---|
| `Ad` | created, updated, deleted | self | `$ad->active` | `User::active()->get()` |
| `EventShare` | created, deleted | `$share->event` | owner → `hasShares && date>=now`; recipient → `date>=now` | `[owner] + current share recipients` |
| `Event` | updated, deleted | self | same `SharedEventsNudge` class | same `SharedEventsNudge` class |
| `Suggestion` | created, updated, deleted | self | `Suggestion::requiresAttentionFor($s, $user)` | `User::active()->whereHas('profile', department_id ∈ ['MA', …$s->departments])` |
| `Review` | created, updated | `$review->suggestion` | `Suggestion::requiresAttentionFor($s, $user)` | same `SuggestionNudge` class |
| `Post` | created, updated, deleted | self | `true` | `User::active()->get()` |
| `Feed` | created, updated, deleted | self | `true` | `User::active()->get()` |
| `Photo` | created, updated, deleted | self | `true` | dept-scoped (`Photo::all_departments` + 'MA', empty→all active) |
| `Report` | created, updated, deleted | self | `$report->active` | `User::active()->get()` |
| `Task` | created, updated, deleted, restored, forceDeleted | self | `true` (false when `latestReply` is own & not owner) | owner (`assigned_to ?? user_id`) + `otherReplyParticipants([user_id, assigned_to])` |
| `Reply` (Task) | created | `$reply->repliable` (repliable_type-guarded) | same `TaskNudge` class | same `TaskNudge` class |
| `Ticket` | created, updated, deleted | self | `true` (false when `latestReply` is own & not currentActionRecipient) | `Ticket::currentActionRecipient()` + `otherReplyParticipants([requester_id, assigned_to])` |
| `Reply` (Ticket) | created | `$reply->repliable` (repliable_type-guarded) | same `ThsNudge` class | same `ThsNudge` class |
| `DMS` | created, updated, deleted | self | `true` | `DMS::pendingRecipients()` |
| `Read` | created, updated, deleted | `$read->dms` | `true` | `DMS::pendingRecipients()` |
| `Channel` | deleted, forceDeleted | self | `true` | invited∪unread (cleanup) |
| `ChannelMessage` | created, deleted | `$msg->channel` | `true` | invited∪unread |
| `Message` | created, updated, deleted, forceDeleted, restored | sender `User` | `true` | active recipients with unread (`unreadCountsFrom`) |

- Suggestion + Review share `suggestion-controller:nudge`, `item_id = suggestion id`.
- EventShare + Event share `shared-events:nudge`, `item_id = event id` (EventShare sets
  `subject = $share->event`). Per-event keying: sharing one event with N people creates exactly one
  owner self-nudge + N recipient nudges; un-sharing prunes recipients in one reconcile. `title`/`body`
  differ per recipient: owner gets «رویداد شما به اشتراک گذاشته شد: X» / «این رویداد توسط شما با
  همکاران به اشتراک گذاشته شده است.»; each recipient gets «رویداد مشترک: X» / «این رویداد توسط
  یکی از همکاران با شما به اشتراک گذاشته شده است.». Owner `show` requires `shares()->exists()`,
  so the self-nudge disappears when the event has no shares. `for` scoped to `User::active()` → a
  deactivated recipient is dropped on next reconcile via `whereNotIn`. `refresh = true`.
- **EventShare firing (panel vs admin)** — the user-panel `ShareEventAction` writes shares with bulk
  `insertOrIgnore` + query `delete()`, which **bypass Eloquent model events**, so it dispatches
  `ReconcileNudge('shared-events:nudge', Event::class, $event->id)->afterCommit()` explicitly after
  the diff-sync. The model-event path (`EventShare::deleted`) covers the admin
  `EventSharesRelationManager` revoke. `Event::updated` reconciles on a date/title edit;
  `Event::deleted` re-fetches null → blanket delete (DB cascade removes shares but fires no model
  events). Public and private events behave identically.
- **No-op short-circuit + post-commit flush** — `ShareEventAction` returns early (no `flush()`, no
  dispatch) when the diff is empty. Both `EventShare::booted()` and `Event::booted()` wrap
  `StateService::flush()` in `DB::afterCommit()` — the version bump publishes only post-commit
  (closes a mid-transaction cache-poisoning race; `DB::afterCommit` runs inline when no transaction
  is active). `Event::booted()` flushes on `updated`/`deleted` only (not `created` — a new event has
  no shares), so the **badge** reflects an owner's date edit/deletion promptly instead of lagging
  the ≤2h TTL.

### Recipients without an auth user

Model events have **no `auth()->user()`** (the save may come from a queue, a console command, or
another user's action). So a rule declares `for` (who *might* need to act) and a per-recipient
`show` (who *actually* needs to act right now). The user is **passed into** `show`, never read from
the session. `Suggestion::requiresAttentionFor($s, $user)` is the canonical attention logic the
badge's `attentionRequired` scope already uses — single source of truth, no drift.

### Nudge lifecycle (the cycle)

- **New active ad** → no row for its id → create unread → new nudge.
- **2nd active ad** → different id → no row → another nudge (per-record, not suppressed by the
  existing ad's row).
- **Dismiss one** → that row's `read_at` set; other records' rows untouched.
- **Ad deactivated** (`updated`) → re-fetch → `active=false` → `!show` → delete its row.
- **Ad re-activated** → row was deleted → create new unread → a new occurrence, not a resurface.
- **Ad deleted** → re-fetch `null` → blanket delete its row.
- **Unrelated ad edit** (active stays true) → row exists (any read_at) → skip → no resurface.

---

## The two systems side by side

| | Badge / dot | Record nudge |
|---|---|---|
| what | permanent status signal (menu dot) | one-time nudge (bell entry) |
| granularity | one row **per indicator** | one row **per qualifying record** |
| trigger | pull — reconciled on menu render (`StateService::get()`) | push — reconciled on the record's Eloquent event |
| key shape | bare `ads-controller` / `suggestion-controller` / `shared-events` | suffixed `ads-controller:nudge` / … |
| dismissal | not dismissable (lit while true) | dismissable (`markAsRead`); never resurfaces |
| no-resurface mechanism | `syncBatch()` `existingByKey` leaves `read_at` alone | `reconcile()` `exists()` branch leaves `read_at` alone |
| invalidation | `StateService::flush()` (global version bump) | n/a (event-driven; re-fetches fresh) |
| recipient model | one row per user per indicator | `for()` candidate set + per-recipient `show()` gate |
| auth at write time | `get()` runs in-request with `auth()->user()` | none — `for`/`show` carry the user explicitly |

Both rely on the **same** `exists()` → leave-it-alone primitive for no-resurface, in pull vs push
form. Both write rows of the same `type`/`notifiable_type`; only the `menu_key` shape separates them.

## Keys & isolation

- Badge keys are **bare** (`ads-controller`). `syncBatch()` filters `whereIn('data->menu_key', $keys)`
  — bare keys only.
- Nudge keys are **suffixed** (`ads-controller:nudge`). `reconcile()` filters
  `where('data->menu_key', $ruleKey)` — suffixed key only.
- A bare key can never equal a suffixed key, so the two query sets are disjoint. No const class
  needed — the `:nudge` suffix alone guarantees it.

## Configuring local vs production

The nudge code has **no environment branch**. Only `.env` differs:

| | `.env` | effect |
|---|---|---|
| local (no worker) | `QUEUE_CONNECTION=sync` | nudge job runs **inline after commit** — no `queue:work` needed, still deadlock-safe |
| production (worker) | `QUEUE_CONNECTION=redis` (or `database`) | worker processes the job, also after-commit |

`CACHE_STORE` must be a **lock-capable** store (the nudge `Cache::lock` depends on it): `database`
(local) and `redis` (prod) both qualify; `file` does not. The badge `Cache::remember`/`forever` work
on any store. Production also needs a queue worker running (`php artisan queue:work`, typically via
Supervisor) — the app already runs one for its other jobs.

## Limitations / inherent drift

These fire **no model event**, so neither signal is auto-reconciled by them:

- **Bulk ops** — `Model::query()->delete()` / mass updates bypass model events. Badge: next unrelated
  `flush()` or render recompute fixes the dot. Nudge: rows for affected records rely on user
  dismissal or the next related event. Bulk paths should use `->each()` or call a manual
  reconcile / `flush()`.
- **Time passage** — an event passing its date fires no event. Badge: the `upcoming` recompute on the
  next render turns the dot off. Nudge: a shared-event nudge then lingers (unread) until dismissed
  or the share is deleted.
- **`Review::deleted` not bound** — a removed review can leave a stale unread suggestion nudge until
  the next `Suggestion` write or dismissal. Intentional: binding it would re-introduce the
  deleted-foreign-subject edge for little gain.

## Adding a new indicator

**Badge** — one class implementing `MenuBadge` + one line in `StateService::$indicators`. Call
`flush()` from the relevant model events (via `HasMenuState`) so the version bumps on change. The
engine never changes.

**Nudge** — one class implementing `MenuNudge` in `Notifications\` + one
`NudgeService::register(new ...Nudge())` line in `NudgeServiceProvider::boot()`. No engine edit, no
migration, no observer class.

---

## `HasMenuState` trait — the single flush primitive

Every model whose writes can change a badge's truth uses `App\Models\Traits\HasMenuState` instead of
a hand-written `booted()` closure:

```php
public static function bootHasMenuState(): void
{
    $events = defined('static::MENU_STATE_EVENTS') ? static::MENU_STATE_EVENTS : ['created', 'updated', 'deleted'];
    $flush = fn() => DB::afterCommit(fn() => StateService::flush());
    foreach ($events as $event) { static::{$event}($flush); }
}
```

- Default `['created','updated','deleted']` — used by `Ad`, `Post`, `Suggestion`, `Feed`, `Profile`,
  `DMS`, `Read`, `EnergyTest`.
- **Opt-out const** `MENU_STATE_EVENTS` narrows the set:
  - `Event` → `['updated','deleted']` — a brand-new event has no shares, so `created` would fire a
    global version bump on every personal/private unshared event creation (thundering-herd).
  - `EventShare` → `['created','deleted']` — shares are never `updated`. (The user-panel
    `ShareEventAction` writes shares with bulk `insertOrIgnore` which bypasses model events and calls
    `flush()` explicitly; this hook covers `EventShare::create()` + the admin
    `EventSharesRelationManager` revoke `$record->delete()`.)
  - `Task` → `['created','updated','deleted','restored','forceDeleted']` — the two extra events are
    needed because of `SoftDeletes`: a restored todo task must re-bump the badge version, and a
    force-delete must too. `bootHasMenuState()` coexists with `bootSoftDeletes()`.
  - `Ticket` → `['created','updated','deleted']` — no `SoftDeletes`, so no `restored`/`forceDeleted`.
- `DB::afterCommit()` is preserved (mid-transaction cache-poisoning race stays closed; runs inline
  when no transaction is active). `bootHasMenuState()` coexists with a model's own `boot()` (e.g.
  `Feed::boot()` registers the `deleting` comment/reaction/poll cascade) — Laravel calls both.
- **Nudge side is unaffected**: `NudgeService::register()` binds a nudge's Eloquent triggers
  independently of `MENU_STATE_EVENTS`; the const only controls the **badge** version bump.

## Array `badge` slot — multiple indicators lighting one sidebar tab

A sidebar tab `'badge'` accepts a string **or** an array of keys. The `calendar` tab carries
`['shared-events', 'special-days']` through one dot; the `feed` tab carries `'feeds'`. The navbar
dot condition is `isset($tab['badge']) && collect((array) $tab['badge'])->contains(fn($k) => $menuState[$k] ?? false)`
— `(array)` normalizes a string key to `['key']`, so the same line serves both shapes (both
`navbars/right.blade.php` and `navbars/bottom.blade.php`, both page chunks). The dot is a pointer to
the tab; the tab content disambiguates.

## Model as source of truth — conditions live on the model

The condition that defines a badge's `isActive()` (and, where it exists, the matching nudge's
`show()`/`for()`) belongs **on the model** as a static/scope method; the indicator + nudge are thin
adapters. One query, one place — reusable, testable, impossible to drift between badge and nudge.

| Module | Model method | Used by |
|---|---|---|
| Ads | `Ad::active()` scope | `ActiveAds` badge |
| Suggestions | `Suggestion::attentionRequired()` / `requiresAttentionFor($s,$u)` (`HasSuggestionAlert`) | `PendingSuggestions` badge + `SuggestionNudge::show()` |
| Shared events | `EventShare::hasImminentFor($u)` / `Event::hasImminentSharedFor($u)` | `SharedEvents` badge |
| Tasks | `Task::getTodoCount($u)` / `scopeForUser` / `scopeStatus` | `TasksTodo` badge |
| Contacts | `Message::hasUnreadFor($u)` / `Message::unreadCountsFrom($sender)` | `UnreadMessages` badge; `ContactNudge::for()` batches per-recipient counts via `unreadCountsFrom` (no per-user query in `show()`) |
| Posts | `Post::hasUnreadFor($u)` (via `HasNudgeTracking`, reads `posts-controller:nudge` unread rows within `FRESHNESS_DAYS`) | `UnreadPosts` badge |
| Feeds | `Feed::hasUnreadFor($u)` / `markAllReadFor($u)` (via `HasNudgeTracking`, reads `feeds:nudge` within `FRESHNESS_DAYS`) | `UnreadFeeds` badge |
| Energy test | `EnergyTest::canSubmit($u)` | `EnergyTestBadge` + `Energy\Main::mount` + `SubmitTestAction` |
| THS tickets | `Ticket::hasUnclosedActionFor($u)` / `Ticket::currentActionRecipient()` | `ThsBadge` + `ThsNudge::for()` |
| DMS docs | `DMS::needsSignCount($u)` / `needsReadCount($u)` / `hasPendingFor($u)` / `DMS::isPendingFor($u)` | `DmsBadge` + `DmsNudge::show()/for()` |

Convention: the method takes the **user id** as a parameter (`hasUnreadFor($u)`, `getTodoCount($u)`,
`canSubmit($u)`) rather than reading `auth()->user()` inside the model, so it is callable from the
queued nudge reconcile job (no auth context) and from tests — mirroring
`requiresAttentionFor($subject,$user)`. The indicator reads `auth()->user()` once and passes
`$user->id` down. `PhotoNudge`/`ReportNudge` are nudge-only (no badge, no shared condition) so they
have no model method to extract; `SpecialDays` is badge-only and its `Profile` date query is a
one-off aggregate with no nudge counterpart, left inline.

`ContactNudge` batches its per-recipient unread count to avoid an N+1: `for()` calls
`Message::unreadCountsFrom($subject->id)` (one grouped `COUNT(*) … GROUP BY recipient_id` query,
sender-scoped, soft-delete-respecting), stores the `[recipient_id => count]` map on the instance,
and returns the active users among those ids; `body()` reads `$this->unreadCountCache[$user->id] ?? 0`
and `show()` returns `true`. Relies on `NudgeService::reconcile` always calling `for()` once before
the per-recipient loop calls `body()` — guaranteed because `body()` is invoked only via `buildData`
inside that loop. The cache is a flat array overwritten each reconcile (not keyed by subject), so it
neither stales nor grows across the process-singleton nudge instance's lifetime. The same
`for()`-primes-`body()` pattern is used by `SharedEventsNudge::show()` (`for()` sets a flat
`$hasShares` bool; non-owners pass `date >= now` then return `true` since `for()` guaranteed
membership) and `ChannelNudge`.

---

## Per-indicator technical details

The shared shape: each indicator is a stateless `MenuBadge` whose `isActive()` delegates to a model
method; its badge row is reconciled by `BadgeSyncService::syncBatch()` on a cache miss; invalidation
flows from model events via `HasMenuState` → `StateService::flush()` (global version bump); the
matching nudge (if any) is a `MenuNudge` reconciled on its triggers via `ReconcileNudge`. The facts
below are the per-indicator specifics that do not fit the table.

### `SharedEvents` — 24h proximity, both parties; decoupled from the nudge window

`SharedEvents::isActive()` lights only when a shared event is **imminent** — `date in [now, now+24h]`
— not for the whole upcoming span. `EventShare::hasImminentFor($user)` covers the recipient (events
shared *with* me in the window); `Event::hasImminentSharedFor($user)` covers the owner (events I own
that have ≥1 share and are in the window). Pure SQL `whereBetween`, no PHP loop. The badge
`title`/`body` are party-agnostic («رویداد مشترک نزدیک است» / «…در ۲۴ ساعت آینده است…») — one
message fits both sharer and sharee.

`EventShare` rows are not only user-authored (`ShareEventAction`) — `App\Services\Reservation\EventSyncService`
also creates one per active meeting-resource booking (booker owns the `Event`, the resource's
`relatedUser` gets the share), so a reservation rides this exact badge/nudge pair with no bespoke
notification code. See `app/Services/Reservation/reservationPattern.md`.

**Badge vs nudge window — decoupled.** The **nudge** (`SharedEventsNudge`) is the share-time
*announcement*: `show` keeps the whole-upcoming span (`date >= now`), so the bell stays from sharing
until the event passes. The **badge** is the 24h *reminder* that lights only near the event. Share →
bell; within 24h → dot + bell; event passes → both clear. The badge is **pull-based** (recomputed
only on menu render / `flush()`, TTL ≈ 2h) and time passage fires no event, so the dot can lag up to
~2h after the 24h boundary crosses — accepted trade-off. `badgeSuppressesCreate = false` (badge
condition is not a superset of nudge condition).

### `SpecialDays` — birthday/anniversary badge (badge-only, per-day)

Lights **only on the exact day** someone has a birthday (`Profile.birthdate`) or work anniversary
(`Profile.start_date`), analogous to `SharedEvents`' 24h window but scoped to the day. The
underlying existence check is global/stateless (same shape as `ActiveAds`); the leading
`viewedToday('calendar')` gate is the only per-user layer (see above). The two date groups are
wrapped in one outer `where(function …)` so the `employment_status` filter ANDs the whole OR group
(a top-level `orWhere` would let the `start_date` branch escape the terminated filter). Uses Gregorian
`now()->month/day` (dates are stored Gregorian; Jalali is display-only). The filter must read
`employment_status` (enum `probational/working/terminated`), NOT `employment_type` (enum
`fulltime/parttime/contract`) — `employment_type != 'terminated'` is a tautology; the
`whereNotIn('employment_status', ['terminated'])` form mirrors `ModuleAnalyticsChartsRight.php:205`.
Excludes terminated employees; includes the auth user's own day (redundant with the `occasion` modal
— a separate per-user confetti popup driven by `isSpecialDay()` + its own 8h cache key, no shared
state). **Leap-year Feb 29** — `whereMonth/whereDay(2,29)` matches only leap years; the calendar grid
behaves identically, so dot and grid stay in sync. No remap. **Invalidation** — `Profile` uses
`HasMenuState` (default all three); the day boundary fires no event, so the dot lags ≤~2h into/after
the day (accepted drift). Title/body occasion-agnostic («مناسبت امروز» / «امروز تولد یا سالگرد یکی
از همکاران است؛ برای مشاهده به تقویم مراجعه کنید.») — an aggregate badge cannot list per-person
names.

### `UnreadPosts` + `UnreadFeeds` — per-user, read-tracked via `HasNudgeTracking`

Both driven by per-user unread state rather than a global date check. `UnreadFeeds` was originally a
global `whereDate('created_at', today())` check (same shape as `ActiveAds`); it was upgraded to the
`UnreadPosts` shape so opening the Feed tab actually clears the dot (previously it could only clear
at midnight, identically for every user).

- **Nudge** — `PostNudge` / `FeedNudge`: triggers `created/updated/deleted`; `show = true` (a feed
  has no "active" flag, so once created it qualifies until deleted); `for = User::active()->get()`;
  `refresh = true`. `FeedNudge::title()` falls back through `FeedCategory::tryFrom($subject->category)?->getLabel()`
  (Feed has no title column, unlike Post). These rows already existed before the `UnreadFeeds`
  upgrade — the badge simply started reading what the nudge layer was already writing.
- **Mark-read on view** — `Posts::selectPost($id)` calls `MarkPostAsReadAction` (per-post, via
  `HasNudgeTracking::markReadFor($itemId, $userId)`). `Feeds::mount()` calls `MarkFeedsAsReadAction`
  (per-tab-open, via `HasNudgeTracking::markAllReadFor($userId)` — Feed has no per-item "select" like
  Posts' modal, so opening the tab marks *all* that user's unread feed rows read at once). Both
  actions wrap `StateService::flush()` in `DB::afterCommit()`. Because dot and bell share the same
  rows, viewing either tab also clears its own bell entries.
- **Surfaces** — `UnreadFeeds` surface is the `feed` sidebar tab via `'badge' => 'feeds'`.
  `UnreadPosts` is surfaced via `'badge' => 'posts-controller'` on the `post` tab in `Tabs.php` —
  not orphaned (a prior revision of this doc incorrectly claimed it was; verified 2026-07-29).

### `HasNudgeTracking` trait — FRESHNESS_DAYS, `seenIdsFor` null-filter, `markReadFor` afterCommit

`App\Models\Traits\HasNudgeTracking` gates all per-user unread-nudge queries:

- **`FRESHNESS_DAYS = 30`** is the single horizon — `isFresh()`, `hasUnreadFor($u)`, and
  `seenIdsFor($u)` all scope `notifications` rows to `created_at >= now()->subDays(30)`. The
  `TodayPosts` global created-today/stateless indicator was renamed to `UnreadPosts` (per-user,
  reads `posts-controller:nudge` unread rows within `FRESHNESS_DAYS`) — a semantic change, not just
  a rename.
- **`seenIdsFor($u)` MUST filter null/empty `data->item_id` before the `(int)` cast.** A null
  `item_id` casts to `0`, polluting the seen-map with a bogus `0 => true`. Correct shape:
  `->filter(fn($id) => $id !== null && $id !== '')->mapWithKeys(fn($id) => [(int)$id => true])`.
- **`markReadFor($u)` + `MarkPostAsReadAction` wrap `StateService::flush()` in `DB::afterCommit()`**
  so the badge clears only after the `notifications.update` commits (fires inline when no
  transaction is active). Without `afterCommit`, a rolled-back mark-read would still bump the
  version and the next `get()` would recompute against the stale unread set.
- **`markAllReadFor($userId)`** — list-level sibling of `markReadFor`, for models with no per-item
  "select" interaction. Marks every unread row for that user's `NUDGE_KEY` read in one `UPDATE`.
  `MarkFeedsAsReadAction` uses it from `Feeds::mount()`, same `DB::afterCommit` pairing.

### `EnergyTestBadge` — energy-test reminder (badge-only, 25-day rolling window)

Per-user, like `SpecialDays`. Lights when the auth user has **not** completed an `EnergyTest` in the
last 25 days. `isActive()` = `$user !== null && EnergyTest::canSubmit($user->id)`; clears the moment
a test with `completed_at >= now()->subDays(25)` is saved.

**Single source aligned with the gate.** `EnergyTest::canSubmit(int $userId, bool $lock = false)` is
the one method the badge, `Energy\Main::mount` (`showSurvey`), and `SubmitTestAction` (submission
gate, `lock: true`) all call — all three read the same 25-day rolling window, so the badge can never
nag while the survey is hidden/blocked. (The prior design lit the badge by Jalali-month while the
gate used a 25-day rolling window, so across a month boundary the badge could nag up to ~24 days
with no way to act.)

**Why badge-only (no nudge):** the badge is self-sufficient — carries its own message, lights while
the window is open, auto-clears on completion. A nudge would be **time-based** (no model event for
"month started"), needing a **scheduled daily command** to dispatch during days 1–7; and because the
nudge's condition is a subset of the badge's, it would have to opt out of badge-overlap suppression
just to fire. That scheduler + suppression machinery buys no marginal signal: the badge already
reminds on every dashboard load, and a non-loading user sees neither. `EnergyTest` uses
`HasMenuState`; `booted()` also calls `Cache::forget` on the user-averages cache for `saved`/`deleted`.
The 25-day window is rolling, so the badge re-lights exactly 25 days after the last submission —
always in lockstep with the gate, no scheduled flush needed.

### `TasksTodo` + `TaskNudge` — per-user todo badge + assignee nudge

Full-page Livewire route (`/tasks`, `TaskBoard\Main`), so the dot renders on the menu-modal item
`tasks-controller` — same surface as `ads-controller`/`suggestion-controller`. No `Tabs.php`/navbar
change.

- **`TasksTodo`** — **per-user**: `isActive = auth()->user() !== null && Task::getTodoCount($user->id) > 0`.
  Reuses `Task::getTodoCount()` = `forUser($userId)->status('todo')->count()`, i.e. "the logged-in
  user has ≥1 task in the `todo` column" (`scopeForUser` = `assigned_to = me` OR
  `user_id = me AND assigned_to null`). A global `Task::where('status','todo')->exists()` would light
  everyone's dot regardless of their own tasks and is wrong. `TaskStatus::Todo` ('todo', label
  «انجام نشده»), the default on create.
- **`TaskNudge`** — **owner + reply-participants**: `for` pushes the owner
  (`User::active()->where('id', $subject->assigned_to ?? $subject->user_id)`, empty → skip) then
  merges `$subject->otherReplyParticipants([$subject->user_id, $subject->assigned_to])` — so a task
  with a reply thread also nudges the other party, not just the owner. The earlier assignee-only
  rule left unassigned tasks with **no** recipient; owner-based fixed that, reply-participants added
  reply-awareness. `show` returns `false` when the latest reply is the user's **and** the user is not
  the owner (no self-nudge for your own reply unless you own the task), else `true` — same
  fire-once/no-resurface rationale (the badge carries the "still in todo" state; a state-driven
  `show = status==='todo'` would resurface a dismissed nudge on a todo→in-progress→todo cycle).
  `title`/`body` branch on ownership: owner gets «وظیفه جدید: …» / «وظیفه جدیدی به شما ارجاع داده
  شده است…», a reply-participant gets «پاسخ جدید: …» / «پاسخ جدیدی برای وظیفه شما ثبت شده است…».
  `refresh = true`.
- **Triggers** — `['created','updated','deleted','restored','forceDeleted']` + `Reply{created}`
  (`subject = $reply->repliable`, repliable_type-guarded to `Task`). `Task` uses `SoftDeletes`, so
  `forceDeleted` is a distinct Eloquent event that `deleted` does **not** cover (without it a
  force-deleted task's nudge row would orphan), and `restored` re-bumps the badge version for a
  restored todo task. `restored` is also in `MENU_STATE_EVENTS` so the badge updates. Soft-delete
  fires `deleted` and `Task::find()` then returns null (SoftDeletingScope) → reconcile
  blanket-deletes the rows. Reassignment works via `whereNotIn('notifiable_id', $ids)->delete()`
  (prunes the old assignee) + empty `for()` → blanket delete (unassign clears everyone).
  `Reply{created}` reuses the same `TaskNudge` class (key + item_id = task id), so a reply
  reconciles the task's existing rows.

### `ThsBadge` + `ThsNudge` — staged ticket routing (one row that migrates with status)

Full-page Livewire route (`/ths`, `ths-controller`), dot on menu-modal item. One ticket produces
**one nudge row** whose recipient changes with the ticket's stage; the row migrates rather than
multiplies.

- **`ThsBadge`** — **per-user**, lit while the user has a not-closed ticket needing their action:
  `isActive = auth()->user() !== null && Ticket::hasUnclosedActionFor($user->id)`.
  `hasUnclosedActionFor($u)` = an `open` ticket whose `extra.target_department`'s highest-ranking
  member (via `User::highestRankingInDepartment()`, `HasProfileHierarchy`) is `$u`, **or** an
  `in-progress` ticket `assigned_to` `$u`. The requester is **not** an action recipient while waiting
  (no badge for them); they get the closing nudge instead.
- **`ThsNudge`** — **staged + reply-participants**: `for` pushes
  `$subject->currentActionRecipient()` (the single user responsible at the current status:
  `open` → highest-ranking of the target department, `in-progress` → `assignee`, `closed` →
  `requester`) then merges `$subject->otherReplyParticipants([$subject->requester_id, $subject->assigned_to])`.
  The engine's `whereNotIn('notifiable_id', $ids)->delete()` is what makes the staged row migrate
  (open→in-progress prunes the dept-head's row and creates the assignee's; in-progress→closed prunes
  the assignee's and creates the requester's); the reply-participant rows are additive on `Reply{created}`.
  `show` returns `false` when the latest reply is the user's **and** the user is not the current
  action recipient (no self-nudge for your own reply), else `true`. `title`/`body` branch on
  `currentActionRecipient()?->is($user)`: the current recipient gets `match($subject->status)`
  («تیکت جدید ارجاع‌شده به واحد شما» / «تیکت محول‌شده به شما» / «پاسخ تیکت ثبت شد» / …), a
  reply-participant gets «پاسخ جدید: …». `refresh = true`. `badgeSuppressesCreate = false` — a
  reply on an already-badge-lit ticket must still CREATE a nudge for the reply-participants the
  badge does not track.
- **Triggers** — `['created','updated','deleted']` + `Reply{created}` (`subject = $reply->repliable`,
  repliable_type-guarded to `Ticket`). `Ticket` has **no `SoftDeletes`**, so `forceDeleted` is not a
  valid event; a hard `deleted` is the only removal event and `Ticket::find()` returning null
  blanket-deletes the row. `MENU_STATE_EVENTS` mirrors this.
- **Auto open→in-progress on assignment** — wired in `TicketFormPresenter::assignedTo()`
  (`afterStateUpdated`: filled assignee + status open/null → `InProgress`; blank + InProgress →
  `Open`), matching the helper text «با انتخاب مسئول، وضعیت تیکت به «در حال بررسی» تغییر خودکار
  می‌کند».
- **Default target department — never null (`PS ?? BS`)** — `SubmitTicketAction` stores `null` when
  no target is chosen, which would leave an `open` ticket unroutable.
  `Ticket::defaultTargetDepartment()` returns `'PS'` if `User::highestRankingInDepartment('PS')` is
  non-null, else `'BS'`, else `null`. A `booted()` `creating` hook persists this into
  `extra.target_department` for any ticket saved without one (covers both the user Livewire submit
  and the Filament admin create — one model-level chokepoint). `currentActionRecipient()` and
  `hasUnclosedActionFor()` also fall back to it at read time, so pre-existing null-target `open`
  tickets route to the PS head without a backfill migration.
- **Default requester department — `extra.department`** — the same `creating` hook defaults
  `extra.department` from the requester's `profile.department_id` when the creating path did not set
  it (the admin create path and seeders left it null, which made the admin "واحد سازمانی" column
  blank). `department()`/`departmentId()` read it back. Requesters with no profile department are
  left unset (`?? '-'` display fallback) rather than polluting filters with a `'N/A'` sentinel.

### `DmsBadge` + `DmsNudge` — sign/read pending (the `reads` row encodes both)

Full-page Livewire route (`/dms`, `dms-controller`), dot on menu-modal item. The `reads` table
encodes the two distinct pending states with **no schema change**:

- `reads.read = 1` → the document is **signed/confirmed** (`ConfirmReadAction` flips this).
- `reads.read_count = 0` → **not read**; `read_count > 0` → read (incremented by `ConfirmReadAction`).
- **needs sign** = visible live doc with no `reads` row having `read=1` (`receivePendingCount` /
  `getUnsignedDocumentsCount`); **needs read** = visible live doc with `reads.read=1 AND read_count=0`
  (`readPendingCount`).

Model source-of-truth (userId-parameterized, queue-safe — the auth-bound `scopeVisibleToUser` is not
callable from the queued reconcile job, so `DMS::visibleTo(int $userId)` mirrors it with an explicit
id):

- `DMS::needsSignCount($u)` / `needsReadCount($u)` / `pendingCount($u)` / `hasPendingFor($u)` — the
  counts. `getUnsignedDocumentsCount()` is now a one-line delegate to `needsSignCount(auth()->id())`.
- `DMS::requiresSignFor($u)` / `requiresReadFor($u)` / `isPendingFor($u)` — per-doc predicates (now
  unused by the nudge; `requiresSignFor` was replaced by the batched `signedUserIds()` — keep until
  removal is confirmed, since it's a public model method).
- `DMS::signedUserIds()` — batched sign-status for one doc
  (`reads()->where('read', true)->pluck('user_id')`, one query); the nudge primes a flat set from it
  in `for()`.
- `DMS::pendingRecipients()` — visible live + pending users for one doc (the nudge `for()`). Fetches
  the doc's `reads(read=true)` once, derives `$signedIds` (users whose read rows have none with
  `read_count=0`, i.e. signed **and** read) from that collection, then excludes them via SQL
  `whereNotIn('id', $signedIds)` on the `User::active()` query — so signed-and-read users are dropped
  before hydration (was `User::active()->get()` + post-fetch `->filter()`). Empty `$signedIds`
  compiles `whereNotIn` to `1 = 1` (all rows), preserving the "nobody signed yet" case.

- **`DmsBadge`** — **per-user**, `isActive = auth()->user() !== null && DMS::hasPendingFor($user->id)`;
  lit while the user has any doc needing sign or read.
- **`DmsNudge`** — **per-document**, `for = $subject->pendingRecipients()`,
  `show = true` (pending filtering is already inside `for()`/`pendingRecipients()`, so `show` is
  unconditional). `badgeSuppressesCreate = false` — a `Read` on an already-badge-lit doc must still
  CREATE a nudge for the newly-pending recipient. `title`/`body` embed the doc's type label via the
  Filament lang strings (`__('resources/dms/strings/type.systematic')` / `non_systematic` →
  «سیستمی»/«غیر سیستمی») and flavor: `requiresSignFor` → «نیازمند تأیید», else «نیازمند مطالعه».
  `refresh = true` so signing rewrites the same row from «نیازمند تأیید» to «نیازمند مطالعه» (the
  row persists across the sign→read transition rather than being recreated).
  `badgeSuppressesCreate = false` — opted OUT, like `SharedEventsNudge`/`ContactNudge`, because the
  spec wants a nudge **per new record** even when the aggregate badge is already lit.
- **Triggers** — `DMS {created,updated,deleted}` (subject = the doc) **+ `Read {created,updated,deleted}`**
  (subject = `$read->dms`). The Read trigger makes the row clear when a user signs/reads:
  `ConfirmReadAction`'s `updateOrCreate` fires `Read created/updated` → reconcile the doc → `show`
  re-eval → sign flips the row to «نیازمند مطالعه», read (`read_count>0`) flips `show=false` → row
  deleted. The DMS `updated` trigger covers the revision-reset path (`DMS::booted()` mass-resets
  reads on file/revision change via a query-builder update that bypasses Read model events — but the
  DMS `updated` event still fires and re-reconciles, so a new revision re-notifies everyone). DMS
  `deleted` → `DMS::find()` null → blanket-delete the doc's rows. Neither `DMS` nor `Read` uses
  SoftDeletes; `HasMenuState` default events.

> **>4 aggregate — open.** The spec wants, when a user's pending docs exceed 4, **two distinct
> aggregate nudges** (one "total for read", one "total for sign") instead of per-document rows. The
> engine keys rows per `(menu_key, item_id)` and assumes one subject per item_id, so a single
> per-user aggregate row does not fit cleanly. It needs one additive engine hook — an optional
> `reconcileOn($subject): array` on `MenuNudge` (default `[[get_class($s), $s->getKey()]]`,
> preserving all existing nudges) letting a nudge dispatch per affected user with `subject = User` →
> one row per user (`item_id = userId`). Flagged for confirmation before touching the engine
> contract; per-doc `show` would then gate at `pendingCount ≤ 4` and two `DmsSignNudge`/`DmsReadNudge`
> classes would show at `> 4`.

### `UnreadMessages` + `ContactNudge` — contacts

`UnreadMessages` (key `contacts-controller`) is per-user: `isActive = auth()->user() !== null && Message::hasUnreadFor($user->id)`.
`ContactNudge` (key `contacts-controller:nudge`) is per-sender: `subject` = the sender `User`;
`for()` batches per-recipient unread counts via `Message::unreadCountsFrom($sender)` (the
`for()`-primes-`body()` idiom); `show = true`; `badgeSuppressesCreate = false` (a new chat must still
alert even when another chat already lit the aggregate badge).

## Department-scoped + broadcast nudges (`PhotoNudge`, `ReportNudge`) — nudge-only, no badge

Both are **Signal-2 only** (no `MenuBadge`, no menu dot) — the bell row is the whole signal.

- **`PhotoNudge`** (key `gallery-controller:nudge`) — `for()` reuses the Suggestion department idiom:
  `User::active()->whereHas('profile', department_id ∈ ['MA', …Photo::all_departments])`; empty
  `all_departments` → all active users (a public gallery photo broadcasts). `show = true`,
  `refresh = true`.
- **`ReportNudge`** (key `reports-controller:nudge`) — broadcast: `for = User::active()->get()`,
  `show = $subject->active` (only published reports — the `Report.active` boolean gate, same shape
  as `AdNudge::show = $ad->active`), `refresh = true`. Title carries the publishing department's
  full name: `'گزارش جدید از ' . (($subject->department?->description ?: $subject->department?->name) ?? 'سازمان') . ': ' . $subject->title`
  — `Department` exposes `description` (complete display name, preferred) and `name` (short
  fallback); `?:` falls through empty `description` to `name`, `?? 'سازمان'` covers a department-less
  report. `Department` has **no** `title` field.
- **Keys** — `gallery-controller` / `reports-controller` bare keys do **not** exist as menu items or
  tab badges; fine for nudge-only rules (the `:nudge` suffix is the only thing that matters for
  isolation, and there is no badge counterpart to collide with).

## `ChannelNudge` — dual-state row migrates on `entered_at`

Key `channels-controller:nudge`. Invited = `entered_at IS NULL` (via `Channel::invitedUserIds`);
unread = `entered + count > 0` (via `Channel::unreadCountsFor`, `whereNotNull(entered_at)` +
`whereNull(msg.deleted_at)`). Like `ThsNudge`, one row per channel per user that migrates between
states. Triggers = `Channel deleted/forceDeleted` (cleanup) + `ChannelMessage created/deleted`
(subject = `$msg->channel`). `show = true`; `for = invited ∪ unread` (two indexed queries,
`for()`-primes-body idiom). Reuses the three existing dispatch sites (`SyncChannelMembers` /
`MarkChannelRead` / `LeaveChannel`); the send path is covered by `ChannelMessage::created` → no new
dispatch.

---

## Open items (flagged, not auto-fixed)

None currently — the `BadgeLegendCatalog` coverage gap below was closed 2026-08-13.

### Closed 2026-08-13 — `BadgeLegendCatalog` coverage gap

The 7 modules missing their own `<x-dashboard.modal.badge-legend>` shortcut (Ads, Suggestions,
Calendar, Posts, Feeds, Gallery, Reports) now each carry a `notifications`-icon button
(`title="راهنمای نشانگر اعلان"`), placed first in the `actions` slot ahead of the module's existing
`help`-icon workflow/feature-legend button, per the DOM-order rule in `viewPattern.md` §8.5. Calendar
passes both `shared-events` and `special-days` as its `items` array (its badge has two indicators);
the other 6 pass a single-entry array for their own key. All 14 catalog entries are now reachable both
from their own module and from `Profile`'s full-catalog reference.

## Audit 2026-08-13 — `BadgeLegendCatalog` content accuracy pass

Two stale/wrong content bugs found and fixed while cross-checking every catalog entry's Persian text
against the actual model logic it describes (not just against this doc, since this doc itself had a
stale claim — see below):

- **`posts-controller`'s `clears`/`surface` named the wrong tab.** Said `«اطلاعات»` (a word meaning
  "Information"); the real tab (`Tabs.php`) is labeled `«اعلانات»` ("Announcements") — similar-looking,
  different word. Fixed to the correct label.
- **`energy-controller`'s `lights`/`clears` described calendar-month timing** ("این ماه" / "همان ماه").
  `EnergyTest::canSubmit()` (`app/Models/EnergyTest.php:78-84`) uses `now()->subDays(25)` — a rolling
  25-day window, not a calendar month. This doc's own "`EnergyTestBadge`" section already documents
  that the badge was deliberately moved OFF month-based logic (a prior bug: "across a month boundary
  the badge could nag up to ~24 days with no way to act") — the catalog text was never updated after
  that fix and kept describing the replaced behavior. Fixed to describe the 25-day window.
- **This doc's own "Menu modal" surfaces list was also stale** — it claimed `contacts-controller` and
  `energy-controller` have no `menu.js` item id ("menu-invisible by design"). Verified against
  `resources/js/components/alpine/data/menu.js` (lines 14, 18): both DO have matching item ids and do
  render a menu-modal dot, same as `ads-controller`/`suggestion-controller`/etc. Corrected above (the
  two "Menu modal"/"Full-page routes" bullets under "Where the dot renders").

## Audit #16–#25 (unanimous A+B+me)

**Code (behavior-preserving):**
- **#16** `ReconcileNudge` now declares `$tries = 3` / `$backoff = [10, 30]`; the
  `LockTimeoutException` swallow in `NudgeService::reconcile` is KEPT (de-dup, not a leak — two jobs
  reconcile the same `(ruleKey, itemId)`, the loser's lock-timeout is expected; the winner does the
  full reconcile). `$tries` closes the real gap: a crashed lock-holder re-runs instead of leaving
  stale rows, while normal concurrent duplicates stay silent (no `failed_jobs` noise).
- **#19** `EnergyTest::booted()` no longer manually calls `static::bootHasMenuState()` — Laravel
  auto-calls `boot{Trait}` on boot, so the manual call double-registered the flush (idempotent
  no-op, removed). DMS and Ticket already rely on auto-boot; EnergyTest was the outlier.
- **#21** `DMS::booted()` adds `static::deleted(fn (DMS $d) => $d->reads()->delete())`. The live DB
  has NO foreign key on `reads.document_id` (project runs FK-less; verified via
  `information_schema`), so a model hard-delete orphaned reads. The hook cascades on model-event
  deletes (consistent with the existing `updated` reset hook). `DMS use HasMenuState` →
  `DMS::deleted` already flushes the global menu cache, so the mass `reads()->delete()` (no Read
  events) leaves no cache gap. Query/bulk deletes still bypass — see #17.

**Document-only (no code, no behavior change):**
- **#17** Query/bulk DMS deletes (`DMS::where(...)->delete()` / `DB::table('dms')->delete()`) bypass
  model events → no reconcile, so nudge rows + reads orphan. Model-event deletes are covered
  (`DmsNudge` triggers on `DMS::deleted` → reconcile null-subject branch cleans nudge rows; #21's
  hook cleans reads). A periodic sweeper is the only real closer for query-deletes and is a separate
  infra task (a DB FK would not help — nudge rows live in `notifications.data->item_id` JSON, not
  FK-able).
- **#20** `DMS::pendingRecipients()` (PHP, doc→users) and `DMS::visibleTo($userId)` (SQL, user→docs)
  encode the same visibility rule (`owners` contains `ALL`/dept OR `users` contains id) in OPPOSITE
  directions and runtimes. A shared primitive would have to abstract over both a PHP
  collection-filter and a SQL builder — a mini-DSL, not a minimal helper; forcing it obscures both.
  Touch both deliberately on any visibility change.
- **#22** Invariant `read_count > 0 ⇒ read = true` (must sign before reading; `read=true &
  read_count=0` is the signed-not-read state). Enforced BY CONSTRUCTION, not by a model guard:
  `ConfirmReadAction::execute` does `updateOrCreate([…],['read'=>true])` BEFORE
  `increment('read_count')`; `DMS::updated` mass-sets `['read'=>false,'read_count'=>0]` together. A
  `Read::saving` guard was rejected — `saving` does NOT fire on `Model::increment()` (query-level
  UPDATE) or mass `Builder::update()`, the only paths setting `read_count>0`, so the guard would be
  dead code advertising protection it can't deliver. Revisit only if a future writer does
  `$read->read_count = N; $read->save();`.
- **#23** `Ticket::currentActionRecipient()` keeps its live fallback `$this->targetDepartmentId ?:
  static::defaultTargetDepartment()`. `targetDepartmentId` is the FROZEN `extra['target_department']`
  (set at creation via the `creating` hook); `defaultTargetDepartment()` is the `once()`-cached LIVE
  default. Empirically the majority of open tickets are legacy null-target (pre-hook) — dropping the
  fallback would silence their nudge AND break badge↔nudge consistency (`hasUnclosedActionFor` keeps
  the `COALESCE → defaultTargetDepartment()` fallback so the badge still lights). Both on the live
  fallback = consistent today, zero behavior change. Legacy-vs-frozen divergence is inherent (the
  creation-era default is not recoverable); an optional one-time backfill freezing the current
  default into legacy `extra` would be a behavior change needing explicit sign-off.
- **#24** A department-head change does not re-fire a THS reconcile, so open-ticket NUDGE rows target
  the old head until the next `Ticket {created,updated,deleted}` event re-reconciles (`whereNotIn`
  prunes the old head, creates for the new). The THS BADGE `hasUnclosedActionFor()` is pull/live →
  reflects the new head immediately (correct authoritative signal); the one-time nudge lags and
  self-heals. A User/Profile listener re-dispatching `ReconcileNudge` was rejected — it must detect
  the head-change condition and couples the THS nudge to non-Ticket events, breaking the per-subject
  trigger model. Badge-covers-it is acceptable.

**No code (already covered):**
- **#18** re-poke on refresh — closed by #3's write-gate (`if ($existing->data != $data) update(…)`):
  identical data → no `update()` → no `updated_at` bump → no Filament re-sort/re-poke; legit content
  changes still update.
- **#25** "duplicated open-dept logic" between `currentActionRecipient` (PHP) and
  `hasUnclosedActionFor` (SQL `COALESCE`) — moot under #23's keep-the-fallback: only one PHP caller,
  the SQL `COALESCE` can't reuse a PHP accessor, and `booted().creating` is the WRITER (different
  role). Extracting `resolvedTargetDepartment()` would DRY zero call sites.