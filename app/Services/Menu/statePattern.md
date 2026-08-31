# StateLogic — the `App\Services\Menu` notification system

The single reference for the **entire** menu notification mechanism: three coexisting signals.
Signals 1 and 2 share one storage (Filament's `notifications` table) but never touch each other;
Signal 3 owns its own `edges` table. Read this before editing anything in `App\Services\Menu`,
adding an indicator, wiring a new nudge trigger, or adding a toast.

- **Signal 1 — Badge / dot** (`StateService` + `BadgeSyncService` + `Indicators\*`): the permanent,
  aggregate status dot on a menu item. One row **per indicator**. **Pull** — reconciled on menu
  render. Not dismissable. Stays lit the whole time the condition is true.
- **Signal 2 — Record nudge / bell** (`NudgeService` + `ReconcileNudge` + `NudgeServiceProvider`):
  a one-time, dismissable bell entry. One row **per qualifying record**. **Push** — reconciled on
  the record's Eloquent event. Never resurfaces once dismissed.
- **Signal 3 — Edge / toast** (`EdgeService` + `ReconcileEdge` + `EdgeServiceProvider` + `Toasts\*`):
  a persistent, dismissible floating card. One row **per (toast × user × subject)**. **Push** to
  materialize (reconciled on the Eloquent event into the `edges` table), **pull** to render (read on
  page load). Dismissible with a **developer-defined duration per toast** (default forever). See
  the "Signal 3 — Edge / toast" section below.

Signals 1 and 2 write rows into the same `notifications` table with `type = FilamentDatabaseNotification` and
`notifiable_type = User`, and both stamp `data->menu_key`. They are kept apart **only** by the key
string: badge keys are bare (`ads-controller`, `suggestion-controller`, `shared-events`); nudge keys
carry a `:nudge` suffix. Each system's queries filter by its own key shape, so the two namespaces
cannot collide and neither can delete the other's rows. Signal 3 is isolated from both by **table**
(it writes `edges`, not `notifications`); its `:edge` key suffix (`channels-controller:edge`,
`projects-controller:edge`) is a naming convention mirroring `:nudge`, not a partitioning mechanism.

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
│   ├── DmsBadge.php                key=dms-controller        isActive = auth()->user() !== null && DMS::hasPendingFor($user->id)
│   ├── TasksImminent.php           key=tasks-deadline        isActive = one of the user's non-done/pending tasks has deadline ≤3 days out or overdue (Task::urgency_state kind ∈ {overdue,due}); no dot surface, bell-only
│   └── TasksPendingApproval.php    key=tasks-pending-approval isActive = user owns a requires_approval project holding a done task with approved_at IS NULL; bell-only (taskboardPattern.md §19.2)
├── Notifications\
│   ├── AdNudge.php          key=ads-controller:nudge        triggers=Ad created/updated/deleted
│   ├── SharedEventsNudge.php       key=shared-events:nudge         triggers=EventShare created/deleted + Event updated/deleted
│   ├── SuggestionNudge.php         key=suggestion-controller:nudge triggers=Suggestion created/updated/deleted + Review created/updated
│   ├── PostNudge.php               key=posts-controller:nudge      triggers=Post created/updated/deleted show=true  for=User::active()
│   ├── FeedNudge.php                key=feeds:nudge                 triggers=Feed created/updated/deleted show=true  for=User::active()
│   ├── PhotoNudge.php              key=gallery-controller:nudge   triggers=Photo created/updated/deleted show=true  for=dept-scoped (Photo.all_departments + 'MA', empty→all active)
│   ├── ReportNudge.php             key=reports-controller:nudge   triggers=Report created/updated/deleted show=$report->active  for=User::active()
│   ├── TaskNudge.php               key=tasks-controller:nudge     triggers=Task created/updated/deleted/restored/forceDeleted + Reply created (subject=$reply->repliable, repliable_type-guarded)  show=true (false when latestReply is own & not owner)  for=owner + otherReplyParticipants([user_id, assigned_to]) + task->detail->collaborators
│   ├── TaskOverdueNudge.php        key=tasks-controller:overdue-nudge  triggers=Task updated (self)  show=urgency_state['kind']==='overdue'  for=owner (assigned_to ?? user_id)  badgeSuppressesCreate=false  — the only time-driven trigger: also swept by `tasks:nudge-overdue` (hourly console command), not just the Task-save event
│   ├── TaskApprovalNudge.php       key=tasks-controller:approval-nudge  triggers=Task updated (self)  show=isPendingApproval()  for=project owner only  badgeSuppressesCreate=false (the key does not end in the literal ':nudge', so the derived badge-suppress would no-op — explicit opt-out per the key-shape gotcha below)  title escalates: '' → 'یادآوری: ' >24h → '⏰ فوری: ' >48h, anchored on updated_at — known limitation: escalation only advances on a Task-save reconcile (no hourly sweep like TaskOverdueNudge's) and any save (e.g. a reply) resets the 24h/48h clock; accepted per plan §H/K2 (taskboardPattern.md §19.2)
│   ├── ProjectNudge.php            key=projects-controller:nudge  triggers=Project created (self) + Reply created (subject=$reply->repliable, Assignment-type + payload.added-guarded)  show=true  for=newly-added member ids (from the Assignment reply's payload, or all member_ids minus owner on the create trigger)
│   ├── ThsNudge.php                key=ths-controller:nudge       triggers=Ticket created/updated/deleted + Reply created (subject=$reply->repliable, repliable_type-guarded)  show=true (false when latestReply is own & not currentActionRecipient)  for=currentActionRecipient + otherReplyParticipants([requester_id, assigned_to])  badgeSuppressesCreate=false
│   ├── DmsNudge.php                key=dms-controller:nudge       triggers=DMS created/updated/deleted + Read created/updated/deleted show=true  for=DMS::pendingRecipients() (visible live + pending users)  badgeSuppressesCreate=false
│   ├── ChannelNudge.php            key=channels-controller:nudge  dual-state row migrates on entered_at (invited=entered_at IS NULL via Channel::invitedUserIds; unread=entered + count>0 via Channel::unreadCountsFor, whereNotNull(entered_at) + whereNull(msg.deleted_at)) like ThsNudge  triggers=Channel deleted/forceDeleted (cleanup) + ChannelMessage created/deleted (subject=$msg->channel)  show=true  for=invited∪unread (two indexed queries, for()-primes-body idiom)  reuses the three existing dispatch sites (SyncChannelMembers/MarkChannelRead/LeaveChannel); send path covered by ChannelMessage::created → no new dispatch
│   └── ContactNudge.php            key=contacts-controller:nudge  triggers=Message created/updated/deleted/forceDeleted/restored  subject=sender User  show=true  for=active recipients with unread (Message::unreadCountsFrom($sender), for()-primes-body idiom)  badgeSuppressesCreate=false
├── Toasts\
│   ├── ChannelToast.php            key=channels-controller:edge   triggers=ChannelMessage created/deleted + ChannelMember/Channel events  for=invited (Channel::invitedUserIds) ∪ mentioned (mentionedSenders regex)  icon=mail (invited) / alternate_email (mentioned)  url=route('channels',['open'=>id])
│   ├── ProjectToast.php            key=projects-controller:edge  triggers=Project created + Reply created (added ids) + ChannelMember created (unopened project channel)  for=resolveAddedIds ∪ Channel::invitedUserIds(channel_id)  icon=group_add (added) / workspaces (unopened)  url=route('projects',['open'=>id])
│   └── TaskDueSoonToast.php        key=tasks-controller:due-soon-edge  triggers=Task created/updated (event) + hourly `tasks:nudge-overdue` sweep (time — see taskboardPattern.md §18, deadline BETWEEN now and now+24h)  show=deadline within 24h, not past, status not done/pending — tighter than TasksImminent's 3-day badge window, deliberately coexisting  for=owner (assigned_to ?? user_id)  icon=schedule  url=route('tasks',['open'=>id])
├── StateService.php                cache + version + sync orchestration (badge side)
├── BadgeSyncService.php            one-row-per-indicator reconcile (badge side)
├── NudgeService.php          registry + dumb engine (nudge side); register(MenuNudge) adapts a nudge into the rule array the engine consumes
└── EdgeService.php           registry + reconcile engine (edge side); register(MenuEdge) adapts a toast, reconcile() lock-diff upserts/prunes the edges table, forUser() reads it (payload carries localRoute), dismiss() snoozes by dismissRule()

App\Jobs\ReconcileNudge.php            queued unit of work for a per-record reconcile (nudge side)
App\Jobs\ReconcileEdge.php             queued unit of work for a per-(toast×user×subject) reconcile (edge side); $tries=3, $backoff=[10,30], afterCommit(), carries only primitives
App\Providers\NudgeServiceProvider.php registers the nudge classes in boot(): NudgeService::register(new ...Nudge())
App\Providers\EdgeServiceProvider.php  registers the toast classes in boot(): EdgeService::register(new ...Toast())
bootstrap/providers.php                        one line appended to register each provider above
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
fresh and returns a bool. It owns no row and writes nothing. **All 13 indicators implement it**,
all structurally identical (no per-user method, no sub-interface).

| Indicator | `getKey()` | `isActive()` |
|---|---|---|
| `TasksPendingApproval` | `tasks-pending-approval` (bell-only — no `BadgeLegendCatalog` entry, deliberately: it is the project owner's lever, not ambient chrome) | `auth()->user() !== null` and the user owns a project with `settings->requires_approval` that has a done task with `approved_at IS NULL` (the `isPendingApproval()` shape, batched as one existence query — see `taskboardPattern.md` §19.2) |
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
| `TasksImminent` | `tasks-deadline` | `auth()->user() !== null` and one of the user's non-done/pending tasks (`forUser`) has a deadline that is overdue or ≤3 days out (`Task::urgency_state['kind']` ∈ `{overdue, due}`) |

`SharedEvents`, `TasksTodo`, `TasksImminent`, `UnreadPosts`, `UnreadFeeds`, `UnreadMessages`,
`EnergyTestBadge`, `ThsBadge`, `DmsBadge` read the logged-in user **explicitly** inside `isActive()`.
`PendingSuggestions`
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
>   exists org-wide — any active MA-department user, OR — when neither a top executive nor an active
>   MA user exists — any MG-department user as final fallback) lights on any `stage = 'awaiting_decision'`.
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
- **`tasks-deadline` (`TasksImminent`) has no dot surface at all** — no `menu.js` item id, no
  `Tabs.php` `'badge'` field. Its row still writes to `notifications` like every badge and is visible
  through Filament's own bell UI (which lists every row regardless of signal), but no dot lights
  anywhere; the catalog's `surface` text for it says so explicitly.

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

All 14 nudges implement `url()`: `AdNudge`→`route('ads', …)`,
`SharedEventsNudge`→`route('dashboard', ['tab'=>'calendar', …])`, `SuggestionNudge`→`route('suggestion', …)`,
`PostNudge`→`route('dashboard', ['tab'=>'post', …])`, `FeedNudge`→`route('dashboard', ['tab'=>'feed', …])`,
`PhotoNudge`→`route('dashboard', ['tab'=>'gallery', …])`, `ReportNudge`→`route('dashboard', ['tab'=>'reports', …])`,
`TaskNudge`→`route('tasks', …)`, `TaskOverdueNudge`→`route('tasks', …)`, `ProjectNudge`→`route('projects', …)`,
`ThsNudge`→`route('ths', …)`, `DmsNudge`→`route('dms', …)`,
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
  the badge condition is **not** a superset of the nudge condition. Six opt-outs: `SharedEventsNudge`
  (badge = imminent ≤24h, nudge = any future event), `ContactNudge` (badge = any unread, nudge =
  per-chat; a new chat must still alert even when another chat already lit the badge), `DmsNudge` /
  `ThsNudge` (a `Read`/`Reply` on an already-badge-lit doc/ticket must still CREATE a nudge for the
  reply-participants the badge does not track), `ChannelNudge` (nudge-only — no `Channel`
  indicator badge exists, so CREATE must always fire), and `TaskOverdueNudge` (its own badge,
  `tasks-deadline`, fires on *due-soon or overdue*, a superset of "overdue" — but see the key-shape
  note below; opting out sidesteps the question). Gallery/Reports/Projects have no matching badge row,
  so the guard is a no-op there; Ads/Posts/Feeds/Suggestion/Task keep the default because their badge
  is a superset of the nudge.
- **Key-shape gotcha (`badge_suppress` lookup silently no-ops on a non-`:nudge` suffix)** —
  `Str::beforeLast($key, ':nudge')` only strips a literal `:nudge` substring; when not found, Laravel
  returns the subject **unchanged** (`vendor/laravel/framework/.../Str.php::beforeLast`). A nudge key
  shaped like `TaskOverdueNudge`'s `tasks-controller:overdue-nudge` has no `:nudge` substring (the
  colon is followed by `overdue-nudge`, not `nudge`), so an unguarded lookup would search for a bare
  badge row keyed `tasks-controller:overdue-nudge` — which can never exist — making `badge_suppress`
  a permanent no-op rather than a real check. `TaskOverdueNudge` sidesteps this by opting OUT
  explicitly rather than relying on the (broken) default. Any future nudge whose key does not end in
  the exact literal `:nudge` suffix inherits the same trap and should opt out explicitly too.
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
| `Suggestion` | created, updated, deleted | self | `Suggestion::requiresAttentionFor($s, $user)` | `User::active()->whereHas('profile', department_id ∈ ['MA', 'MG', …$s->departments])` |
| `Review` | created, updated | `$review->suggestion` | `Suggestion::requiresAttentionFor($s, $user)` | same `SuggestionNudge` class |
| `Post` | created, updated, deleted | self | `true` | `User::active()->get()` |
| `Feed` | created, updated, deleted | self | `true` | `User::active()->get()` |
| `Photo` | created, updated, deleted | self | `true` | dept-scoped (`Photo::all_departments` + 'MA', empty→all active) |
| `Report` | created, updated, deleted | self | `$report->active` | `User::active()->get()` |
| `Task` | created, updated, deleted, restored, forceDeleted | self | `true` (false when `latestReply` is own & not owner) | owner (`assigned_to ?? user_id`) + `otherReplyParticipants([user_id, assigned_to])` + `task->detail->collaborators` (only once a reply exists) |
| `Reply` (Task) | created | `$reply->repliable` (repliable_type-guarded) | same `TaskNudge` class | same `TaskNudge` class |
| `Task` (overdue) | updated | self | `$task->urgency_state['kind'] === 'overdue'` (model-as-source-of-truth, same accessor `TasksImminent` badge partially reuses) | owner (`assigned_to ?? user_id`) — also swept hourly by `tasks:nudge-overdue`, see below |
| `Project` | created | self | `true` | newly-added member ids: on `created`, `member_ids` minus `owner_id`; see `Reply` row |
| `Reply` (Project) | created | `$reply->repliable` (repliable_type-guarded, `TaskActivityType::Assignment`-guarded, empty `payload.added`→null) | same `ProjectNudge` class | `payload['added']` ids from the latest Assignment reply |
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
- **`TaskOverdueNudge` — the only time-driven trigger.** Every other nudge in the system reconciles
  purely off an Eloquent event; this one's `triggers()` still declares a normal `Task {updated}` (a
  save that flips a task overdue reconciles immediately, same as any other nudge), but a task can also
  cross its deadline with **no accompanying save at all** — nothing fires. `App\Console\Commands\NudgeOverdueTasks`
  (`tasks:nudge-overdue`, scheduled `->hourly()->between('06:00','22:00')` in `routes/console.php`) closes
  that gap: it sweeps `Task::whereNull('archived_at')->where('status','!=','done')->whereNotNull('deadline')->where('deadline','<',now())`
  in `chunkById(200)` and dispatches the same `ReconcileNudge` job per row — an artificial "re-check"
  rather than a new trigger class, so `NudgeService`'s engine is untouched. `show()` still re-derives
  `urgency_state` fresh inside the job, so a task the sweep queues but that turns out `pending` (the
  query does not exclude it, only `done`) simply reconciles to no-op/delete, not a false nudge.
- **`ProjectNudge` recipient logic** — `for()` resolves "who was just added" two ways: on `Project
  {created}` (no reply yet), every `member_ids` entry except `owner_id`; on `Reply {created}`
  (`TaskActivityType::Assignment`, non-empty `payload['added']`), exactly the ids in that reply's
  `added` payload — so re-adding an already-a-member user, or a membership reply with no `added`
  delta, resolves to an empty/no-op recipient set rather than re-nudging the whole roster. No matching
  badge exists for Projects (nudge-only, like Gallery/Reports); `badgeSuppressesCreate` stays default
  `true` but is a no-op with no bare-key row to find.

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

## The three systems side by side

| | Badge / dot | Record nudge | Edge / toast |
|---|---|---|---|
| what | permanent status signal (menu dot) | one-time nudge (bell entry) | persistent floating card |
| granularity | one row **per indicator** | one row **per qualifying record** | one row **per (toast × user × subject)** |
| trigger | pull — reconciled on menu render (`StateService::get()`) | push — reconciled on the record's Eloquent event (one exception: `TaskOverdueNudge` is additionally swept hourly by `tasks:nudge-overdue`, a console command re-checking for silent deadline crossings — it still calls the same per-record `ReconcileNudge`, not a bespoke mechanism) | push to materialize (reconciled on the Eloquent event into `edges`) + pull to render (read on page load) |
| key shape | bare `ads-controller` / `suggestion-controller` / `shared-events` | suffixed `ads-controller:nudge` / … | suffixed `channels-controller:edge` / … (own `edges` table — the suffix is convention, not partitioning) |
| dismissal | not dismissable (lit while true) | dismissable (`markAsRead`); never resurfaces | dismissible (× → `snooze()` by `dismissRule()`; default forever → `dismissed_at`, never resurfaces; a duration → `snoozed_until`, resurfaces after expiry) |
| no-resurface mechanism | `syncBatch()` `existingByKey` leaves `read_at` alone | `reconcile()` `exists()` branch leaves `read_at` alone | `reconcile()` existing-row branch leaves `dismissed_at`/`snoozed_until` alone; `scopeVisible` re-includes after snooze expiry |
| invalidation | `StateService::flush()` (global version bump) | n/a (event-driven; re-fetches fresh) | n/a (event-driven; re-fetches fresh) |
| recipient model | one row per user per indicator | `for()` candidate set + per-recipient `show()` gate | `for()` candidate set + per-recipient `show()` gate (same shape as nudge) |
| auth at write time | `get()` runs in-request with `auth()->user()` | none — `for`/`show` carry the user explicitly | none — `for`/`show` carry the user explicitly |
| locality | always global | always global | `localRoute()` per-toast: `null`=global (every page), route-name=local (only that module URL, gated by blade `@if` vs `$currentRoute` captured at mount) |

Both rely on the **same** `exists()` → leave-it-alone primitive for no-resurface, in pull vs push
form. Both write rows of the same `type`/`notifiable_type`; only the `menu_key` shape separates them.

## Keys & isolation

- Badge keys are **bare** (`ads-controller`). `syncBatch()` filters `whereIn('data->menu_key', $keys)`
  — bare keys only.
- Nudge keys are **suffixed** (`ads-controller:nudge`). `reconcile()` filters
  `where('data->menu_key', $ruleKey)` — suffixed key only.
- A bare key can never equal a suffixed key, so the two query sets are disjoint. No const class
  needed — the `:nudge` suffix alone guarantees it.
- **One key does not follow the exact `:nudge` shape** — `TaskOverdueNudge` uses
  `tasks-controller:overdue-nudge`. Isolation from the badge namespace still holds (it is not a bare
  key), and `PruneStaleNudges`' `LIKE '%nudge'` predicate still catches it (it ends in `nudge`), but
  `Str::beforeLast($key, ':nudge')` does **not** find a `:nudge` substring in it — see the
  `badge_suppress` key-shape note above, which is why this nudge opts out of that check explicitly.

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

Every model whose writes can change a badge's truth uses `App\Models\Concerns\HasMenuState` instead of
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
| Tasks (deadline) | `Task::urgencyState()` accessor (`urgency_state['kind']`) | `TasksImminent` badge (kind ∈ `{overdue, due}`) + `TaskOverdueNudge::show()` (kind `=== 'overdue'`, a narrower slice of the same accessor) |
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
`$user->id` down. `PhotoNudge`/`ReportNudge`/`ProjectNudge` are nudge-only (no badge, no shared
condition) so they have no model method to extract; `SpecialDays` is badge-only and its `Profile`
date query is a one-off aggregate with no nudge counterpart, left inline.

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

`App\Models\Concerns\HasNudgeTracking` gates all per-user unread-nudge queries:

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
- **`TaskNudge`** — **owner + reply-participants + collaborators**: `for` pushes the owner
  (`User::active()->where('id', $subject->assigned_to ?? $subject->user_id)`, empty → skip) then
  merges `$subject->otherReplyParticipants([$subject->user_id, $subject->assigned_to])` — so a task
  with a reply thread also nudges the other party, not just the owner — then merges
  `collaboratorRecipients()`: **only once a reply exists** (`$task->latestReply()` non-null), the
  task's `detail->collaborators` ids (`User::active()->whereIn('id', …)`), so a task's collaborator
  list also gets nudged on the same reply-driven cadence as the reply participants, not on every bare
  create/update. The earlier assignee-only rule left unassigned tasks with **no** recipient;
  owner-based fixed that, reply-participants added reply-awareness, collaborators extended it further.
  `show` returns `false` when the latest reply is the user's **and** the user is not the owner (no
  self-nudge for your own reply unless you own the task), else `true` — same fire-once/no-resurface
  rationale (the badge carries the "still in todo" state; a state-driven `show = status==='todo'`
  would resurface a dismissed nudge on a todo→in-progress→todo cycle). `title`/`body` branch three
  ways: a `StatusChange` reply whose `payload['to'] === 'done'` (`isCompletionReply()`) gets «وظیفه
  تکمیل شد: …» / «وظیفهٔ شما به پایان رسید…» regardless of recipient; otherwise the owner gets
  «وظیفه جدید: …» / «وظیفه جدیدی به شما ارجاع داده شده است…», any other recipient (reply-participant
  or collaborator) gets «پاسخ جدید: …» / «پاسخ جدیدی برای وظیفه شما ثبت شده است…». `refresh = true`.
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
  Filament lang strings (`__('resources/dms/strings.type.systematic')` / `non_systematic` →
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
  label via the shared `Department::displayLabel()` (`HasDepartmentLabel`, `description ?: name ?: code`):
  `'گزارش جدید از ' . ($subject->department?->displayLabel() ?? 'سازمان') . ': ' . $subject->title`
  — the nullsafe `?->` short-circuits `?? 'سازمان'` for a department-less report; the same helper
  backs `Department` dropdown labels elsewhere, so this can't drift from that copy.
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

## `ProjectNudge` — membership-scoped, no badge

Key `projects-controller:nudge`. Nudge-only (no `MenuBadge`) — like Gallery/Reports, but scoped to
**newly-added members** rather than a department or a broadcast. `for()` resolves the added-ids two
ways depending on which trigger fired: `Project {created}` has no reply yet, so every `member_ids`
entry except `owner_id` is "newly added"; `Reply {created}` (subject resolver guards
`repliable_type === Project::class` **and** `type === TaskActivityType::Assignment`, returning
`null` — i.e. no reconcile — when `payload['added']` is empty) resolves to exactly that reply's
`added` ids via `resolveAddedIds()`, which reads the **latest** Assignment reply if one exists,
falling back to the `created`-trigger's member-list logic otherwise. `show = true`; `refresh = true`.
`url()` → `route('projects', ['open' => $subject->getKey()])`. Recipients not in `payload['added']`
(existing members untouched by the change) never get a row, so adding one person to a 20-member
project nudges only that person, not the roster — the engine's `whereNotIn` prune still removes a
since-removed member's row on the next Assignment reply.

## `TaskOverdueNudge` — the system's one time-driven nudge

Key `tasks-controller:overdue-nudge` (not the `:nudge` suffix shape — see "Keys & isolation"). No
badge condition of its own; `tasks-deadline` (`TasksImminent`) is a related but broader badge (due
**or** overdue) that happens to reuse the same `urgency_state` accessor — not a superset relationship
guaranteed to hold under every future tuning of either threshold, which is why `badgeSuppressesCreate`
is explicitly `false` rather than left at the (silently-broken, see above) default. `for = owner
(assigned_to ?? user_id)`; `show = urgency_state['kind'] === 'overdue'`; `refresh = true` so the row's
day-count label (`{days} روز تأخیر`, read off the same accessor at reconcile time) stays current while
unread. `triggers()` declares only `Task {updated}` — a save that flips a task overdue reconciles
immediately like any other nudge — but `App\Console\Commands\NudgeOverdueTasks` (`tasks:nudge-overdue`)
additionally sweeps tasks whose deadline silently passed with **no** accompanying save (see "The
rules" above for the query + schedule). `url()` → `route('tasks', ['open' => $subject->getKey()])`.

---

## Open items (flagged, not auto-fixed)

- **`projects-controller` catalog entry's `tone` (`gold`) is inconsistent with its own semantics.**
  `gold` per `viewPattern.md` §8.5 means "action-based, clears on completing the task" — but
  `ProjectNudge` is nudge-only (no matching badge), dismiss-only, same shape as `channels-controller`
  and `tasks-overdue-nudge`, both correctly tagged `sage` ("nudge-only with no matching dot/badge").
  `projects-controller` should likely be `sage` too. Not auto-fixed here (a `tone` value is
  application code in `BadgeLegendCatalog.php`, out of scope for a docs-only pass) — flagged for a
  one-line fix on confirmation.

## Pruning stale nudge rows — `notifications:prune-stale`

Nudge rows (`:nudge` suffix) are one **per (record × user)**, and for records that permanently qualify
(`show = true`: Post/Feed/Photo/Report/Ad) they are never deleted by `reconcile()` until the record
itself is deleted — read *or* unread, they linger indefinitely. With broadcast nudges
(`for = User::active()`) that is `#records × #users` and grows without bound. Badge rows (bare keys)
are bounded (~one per indicator per user) and are **not** the bloat.

`App\Console\Commands\PruneStaleNudges` (`notifications:prune-stale {--days=30}`) is the minimal,
provably-safe closer, scheduled `->daily()->withoutOverlapping()` in `routes/console.php`:

- Deletes `notifications` rows where `type = FilamentDatabaseNotification` AND
  `data->menu_key LIKE '%nudge'` AND `created_at < now()->subDays($days)`. The `nudge` ending partitions
  the namespace — every nudge key ends in `nudge` (the current `:nudge` suffix **and** the legacy
  `:reply-nudge` suffix left by a refactored-away iteration — no source code writes `:reply-nudge` any
  more; `TaskNudge`/`ThsNudge` now reuse the single `:nudge` key+item_id), and no bare badge key ends in
  `nudge` (`ads-controller`, `dms-controller`, `shared-events`, `special-days`, …). `LIKE '%nudge'`
  catches the whole nudge family in one predicate; the earlier `LIKE '%:nudge'` was too narrow and would
  have left the legacy `:reply-nudge` rows behind.
  (`data->menu_key` compiles to `json_unquote(json_extract(...))` on MySQL — `MySqlGrammar` line 572 —
  so the unquoted value is what `LIKE` compares. **Practically verified on live data** (MySQL 5.7.24,
  `DB_CONNECTION=mysql`): `LIKE '%nudge'` matched exactly the `:nudge` + `:reply-nudge` keys (all ending
  `nudge`), `NOT LIKE '%nudge'` matched exactly the bare badge keys (none ending `nudge`), and the two
  counts summed to the table total — a clean partition. Production runs MySQL 8.0+; the grammar emits
  identical SQL on 5.7 and 8.0 and the predicate is ASCII-suffix-based, so collation differences are
  irrelevant. This is also distinct from the `livewirePattern.md` LIKE+JSON bug family — that one is
  free-text user input (LIKE metacharacter over-match + `\uXXXX`-escaped Persian needle + `\` escape
  stripping); this is a static ASCII suffix with no user input and no escape surface.)
- **Aligned to `HasNudgeTracking::FRESHNESS_DAYS` (30).** Every badge query (`hasUnreadFor`,
  `seenIdsFor`, `isFresh`) already scopes `created_at >= now()->subDays(30)`, so a nudge row older
  than the horizon feeds **zero** badge signal — it is only bell clutter (Filament's bell has no
  freshness filter). Pruning it at 30 is therefore safe by construction, not a compromise.
- **Guarded `--days`**: `< 1` is rejected with `Command::FAILURE` (no accidental `--days=0` mass
  delete of every row with `created_at < now()`). `handle(): int` returns `SUCCESS`/`FAILURE`.
- **No-resurface, deliberately bounded past the horizon.** A pruned `:nudge` row can only re-create
  on a *future Eloquent event* for that same record (an edit) — `reconcile()` re-fetches, re-evaluates
  `show`, and creates a fresh unread nudge. This is an accepted, bounded weakening of the no-resurface
  guarantee for the >30-day window — the cost of bounding the table: a dismissed nudge may re-surface
  **only** when its record is later edited past the horizon (a record never touched again never
  re-surfaces), and the fresh row is itself pruned on the next cycle. Without this tradeoff, read nudge
  rows would linger forever — exactly the bloat this command exists to bound. (Channel is genuinely
  nudge-only — no badge indicator exists — and Contact/Dms/Ths/SharedEvents opt out of badge-overlap
  suppression via `badgeSuppressesCreate = false` because their badge condition is not a superset of
  the nudge condition, so CREATE is not skipped even when a bare-key badge row is present.)
- **Backlog + ongoing in one mechanism.** The first scheduled run deletes the existing backlog
  (everything older than the horizon) exactly as it keeps future growth bounded — no separate
  one-off cleanup. For immediate relief without waiting for the cron tick, run
  `php artisan notifications:prune-stale` once (optionally `--days=30`).
- No migration, no engine change, no behavior change to `BadgeSyncService`/`NudgeService`/indicators.
- **Tested** in `tests/Feature/Console/ConsoleCommandsTest.php` (4 cases): stale `:nudge` pruned while
  fresh `:nudge` and stale bare-key badge row survive; `--days` window honored; legacy `:reply-nudge`
  caught + its bare `:reply` sibling survives (locks the `LIKE '%nudge'` vs `LIKE '%:nudge'` choice);
  `--days<1` guard returns FAILURE.

### Closed 2026-08-13 — `BadgeLegendCatalog` coverage gap

The 7 modules missing their own `<x-dashboard.modal.badge-legend>` shortcut (Ads, Suggestions,
Calendar, Posts, Feeds, Gallery, Reports) now each carry a `notifications`-icon button
(`title="راهنمای نشانگر اعلان"`), placed first in the `actions` slot ahead of the module's existing
`help`-icon workflow/feature-legend button, per the DOM-order rule in `viewPattern.md` §8.5. Calendar
passes both `shared-events` and `special-days` as its `items` array (its badge has two indicators);
the other 6 pass a single-entry array for their own key. All 14 catalog entries that existed at the
time were reachable both from their own module and from `Profile`'s full-catalog reference (the
catalog has since grown to 17 — see the 2026-08-29 audit below; the 3 newer entries follow the same
reachability rule).

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

## Audit 2026-08-29 — `TaskOverdueNudge` added; full re-audit against source

`TaskOverdueNudge` is the system's first genuinely time-driven trigger (see its own section above and
the `tasks:nudge-overdue` bullet under "The rules"). Auditing it surfaced that this doc had drifted
from the live code independently of that addition — two indicator/nudge classes existed with **no**
documentation at all, and several counts were stale:

- **`TasksImminent` (badge, key `tasks-deadline`) and `ProjectNudge` (nudge, key
  `projects-controller:nudge`) were entirely undocumented** — pre-existing, not part of today's
  change. Both now have full coverage: `TasksImminent` in the Signal-1 table + namespace map +
  "Where the dot renders" (it has no dot surface at all) + Model-as-source-of-truth table;
  `ProjectNudge` in the namespace map + "The rules" table + its own "membership-scoped, no badge"
  section.
- **Counts corrected**: 11→12 badge indicators, 12→14 nudges implementing `url()`, 5→6
  `badgeSuppressesCreate() => false` opt-outs (added `TaskOverdueNudge`), 14→17
  `BadgeLegendCatalog` entries (also stale in `filamentPattern.md` and `viewPattern.md` §8.5, fixed
  there too).
- **`TaskNudge::for()` merges a third recipient group, `collaboratorRecipients()`** (task's
  `detail->collaborators`, only once a reply exists) — undocumented; added. Its `title`/`body` also
  branch on a completion reply (`StatusChange` to `done`) that the doc never mentioned — added.
- **`ReportNudge`'s title logic was refactored** from inline `description ?: name` to the shared
  `Department::displayLabel()` (`HasDepartmentLabel` trait) sometime after the doc's last read —
  corrected to describe the current code, noting the trait is shared with other `Department` label
  call sites (can't drift).
- **`DmsNudge`'s lang-key was mistyped in this doc** as `resources/dms/strings/type.systematic`
  (slash); the actual key is `resources/dms/strings.type.systematic` (dot) — corrected.
- **`Str::beforeLast($key, ':nudge')` key-shape gotcha documented** — a nudge key not ending in the
  literal `:nudge` substring (like `TaskOverdueNudge`'s `tasks-controller:overdue-nudge`) makes the
  `badge_suppress` bare-key lookup silently search for a badge row that can never exist, since
  `beforeLast` returns the subject unchanged when the search string isn't found. Not a live bug (every
  such nudge so far opts out explicitly), but a real trap for the next one that doesn't.
- **"Push" framing in "The two systems side by side"** given one exception without generalizing it —
  `TaskOverdueNudge` is still reconciled by the same per-record `ReconcileNudge` job on the same
  `Task {updated}` Eloquent trigger every other nudge uses; the console-command sweep is an additional
  *source* of that same dispatch for records that had no accompanying save, not a second reconciliation
  mechanism.
- Ran `MenuServiceTest` (62 passed) and `ConsoleCommandsTest` (18 passed, including
  `nudge_overdue_tasks_dispatches_reconcile_for_overdue_tasks_only`) as the ground-truth cross-check
  before writing the above — no test expectation contradicted anything corrected here.

**Stale count found while making the change below, not yet corrected everywhere:** the catalog now
holds 22 entries (`MenuServiceTest::test_badge_legend_catalog_grouped_folds_all_entries_into_five_groups`
already pins 22), not the 17 this doc's own "Counts corrected" bullet above states — `filamentPattern.md`
and `viewPattern.md` §8.5 likely still say 17 too. Pre-existing drift, unrelated to today's change;
flagged for a follow-up docs-only pass rather than fixed here to keep this session's diff scoped to the
actual feature.

## Notification legend — sub-pill grouping, max 2 items per subgroup (2026-08-30)

The `tasks` group had grown to 9 entries — visibly longer than the other four groups in the badge-legend
modal, a real usability complaint (user report: "super long"). Fixed the same way every module-local
legend in this codebase already handles a long tab (`taskboard/legend.blade.php`,
`project/legend.blade.php`, `authority/legend.blade.php` — see `viewPattern.md`'s "Legend restructure"
note): a secondary row of sub-pills sharing one Alpine `sub` state that each top-level pill resets, not
a new top-level group (which would break the catalog's fixed 5-theme taxonomy every consumer keys off).
Went through three rounds before landing on the final shape below — each round is a real, explicit user
rule, not a guess, and the final rule (**max 2 items per subgroup, no exceptions**) supersedes the
looser ">3-4 rows" heuristic used earlier in this doc for module-local legends; this catalog's own
subgrouping is stricter and should not be loosened back to the old heuristic without the user re-opening it.

`BadgeLegendCatalog::subgroups(): array` — a map, keyed by group id, of `subId => {label, icon}`. Four
of the five groups are configured; `opportunities` (2 rows: `ads-controller`, `suggestion-controller`)
stays flat with no `subgroups()` entry since it already satisfies max-2 as a plain list:

| Group | Subgroups (≤2 items each) |
|---|---|
| `tasks` (9 rows → 5 subgroups) | `list` (`tasks-controller`, `tasks-deadline`) · `deadline` (`tasks-controller:due-soon-edge`, `tasks-overdue-nudge`) · `approval` (`tasks-approval-nudge`, `tasks-pending-approval`) · `projects` (`projects-controller`, `projects-controller:edge`) · `tickets` (`ths-controller`) |
| `notifications` (4 rows → 2 subgroups) | `direct` (`posts-controller`, `contacts-controller`) · `channels` (`channels-controller`, `channels-controller:edge`) |
| `content` (4 rows → 2 subgroups) | `calendar` (`shared-events`, `special-days`) · `media` (`feeds`, `gallery-controller`) |
| `compliance` (3 rows → 2 subgroups) | `tracking` (`dms-controller`, `energy-controller`) · `reports` (`reports-controller`) |

Every catalog row that belongs to a configured group carries a `'subgroup'` key naming which bucket it
lands in (20 of the 22 rows — every row except `opportunities`'s 2). `grouped()` returns each group with an additional `'subgroups'` key — `[]`
for `opportunities` (backward-compatible: `items` is unchanged, so no pre-existing consumer of
`grouped()` needed updating), or `[{id,label,icon,items}]` for the four configured groups.
`badge-legend.blade.php` renders the sub-pill row only `@if(!empty($group['subgroups']))`, falling back
to the flat list otherwise — every other module's single-item `items=` prop path (the 14+ modules
passing one catalog entry, never `groups=`) is completely untouched, since only the `groups=` branch
(Profile's master catalog) ever reaches this code. Clicking a top-level pill resets `sub` to that
group's first subgroup id so a stale subgroup selection from a previously-viewed group never shows
empty content. Sub-pill labels are kept as short as the parent group's own context allows — e.g.
`tasks`'s pills are `فهرست`/`سررسید`/`تأیید`/`پروژه`/`تیکت` (one word each), not full phrases, since the
parent tab ("وظایف و تیکت") already carries the "tasks/tickets" framing and the sub-pill only needs to
disambiguate *within* it.

**Row height was a separate, second complaint — fixed independently.** Subgrouping only fixed item
*count* per screen; `badge-legend-row.blade.php` separately stacked `lights`/`clears`/`surface` as three
block `<p>` elements, so even a 2-item subgroup still read as a long scroll. Merged into one `<p>` with
inline `<span class="font-bold">label:</span>` segments separated by a `·` glyph — matches the
single-paragraph row style every module-local legend (`profile/legend.blade.php`,
`taskboard/legend.blade.php`, etc.) already uses; same information, roughly a third the vertical height.
See `viewPattern.md` §8.5 for the full row-shape spec, kept in sync there.

Tests: `MenuServiceTest.php` (`grouped()` partitions all 4 configured groups generically off
`subgroups()`'s own keys, every item lands in exactly one subgroup, `opportunities` stays flat, and a
generic assertion pins **no group or subgroup ever exceeds 2 items** — the actual regression this whole
section guards), `ProfileTest.php` (the master catalog modal renders all 11 sub-pill labels across the
4 configured groups).

---

## Signal 3 — Edge / toast

A persistent, dismissible floating card — the third menu signal. Unlike the ephemeral auto-dismiss
`x-ui.modals.toast` feedback tier (next section), Edge owns a DB table (`edges`), fires on Eloquent
events, and survives page reloads until dismissed.

### Storage — `edges` table

One row **per (toast × user × subject)**. Columns: `user_id`, `edge_key`, `subject_id`, `icon`,
`title`, `body`, `url`, `dismissed_at` (null until forever-dismissed), `snoozed_until` (set by a
duration dismiss; row re-appears once it passes). `scopeVisible` =
`whereNull('dismissed_at')->where(fn $q => $q->whereNull('snoozed_until')->orWhere('snoozed_until','<=',now()))`.
Migration `2026_08_29_000001_create_edges_table.php`; run `php artisan migrate` before exercising the
path — until then `reconcile()` hits a SQL error, the job retries 3× then lands in `failed_jobs`, but
the originating post-commit model save is **not** broken.

### Contract — `Contracts\MenuEdge`

Five **required** methods: `getKey()`, `triggers()`, `for($event, $user, $payload)`,
`title($subject, $user, $payload)`, `body($subject, $user, $payload)`. Six **optional**, probed via
`method_exists` (not declared on the interface, mirroring the nudge side): `show($subject, $user,
$payload)` (default true — gate skipped), `refresh()` (default true — re-fetch subject on existing
row), `icon($subject, $user)` (default `'info'`), `url($subject)` (default `''`), `dismissRule()`
(default `'forever'`), `localRoute()` (default `null` = global). `EdgeService::register()` stores each
optional's flag/value in the static rule array.

### `EdgeService` — registry + engine

- `register(MenuEdge $edge)` — stores the rule with `hasIcon/hasUrl/hasShow/hasDismissRule` flags + the
  `refresh` bool + the resolved `localRoute` (null when absent).
- `reconcile($key, $itemId, $userId, $payload)` — `Cache::lock` per `(key,itemId)`; re-fetches the
  subject fresh; prunes via `whereNotIn('subject_id', $ids)`; `show()`-gates; on an existing row
  honours `refresh()` (re-fetch + update icon/title/body/url) else leaves it; otherwise creates.
- `forUser($userId)` — `edges` visible for the user, mapped to a payload array (key, subject_id, icon,
  title, body, url, **localRoute**). `localRoute` is sourced from the static rule registry
  (`isset`-guarded — an orphan `edge_key` with no registered toast yields null, no warning).
- `dismiss($userId, $edgeKey, $subjectId)` — looks up the toast's `dismissRule()` (or `'forever'` when
  absent) → `Edge::snooze($optionKey)`: `'forever'`→`dismissed_at=now()`; `'1day'|'1week'|'1month'`→
  `snoozed_until = now()->addDay|addWeek|addMonth` (the model's `DURATIONS` map). Re-displays after
  snooze expiry; forever-dismissed never resurfaces.

### Delivery — render-driven, no poll

`App\Livewire\Dashboard\Edge` is mounted **once globally** in `layouts/app.blade.php` (beside
`Countdown`/`EventReminder`). `load()` runs in `mount()` + after `dismiss()` only — **not** in
`render()` and **no** `wire:poll` — so zero recurring query; the card set is recomputed only on page
load and on a dismiss. `render()` just returns the view.

### Local vs global — `localRoute()` (the per-toast scope switch)

A toast declares its locality by overriding `localRoute(): ?string`:

- `null` (default) → **global**: the card renders on every page.
- a route name → **local**: the card renders only on that module's URL.

The gate is a single blade `@if` in `livewire/dashboard/edge.blade.php`:
`@if($e['localRoute'] === null || $e['localRoute'] === $currentRoute)`. `$currentRoute` is captured once
in `mount()` (`request()->route()?->getName()`) into a public prop — **not** read live in the blade — so
it survives the dismiss-AJAX re-render (where `request()->route()` is the Livewire endpoint, not the
page route). Both `ChannelToast` and `ProjectToast` currently omit the override → global by default;
the hook is dormant, kept for a future toast that wants locality. This replaced an earlier per-module
`<livewire:dashboard.edge :scope=...>` mount — one global mount + a toast-declared route + one blade
`@if` is the minimal form.

### Reconcile trigger chain

`EdgeServiceProvider::boot()` → `EdgeService::register(new ChannelToast/ProjectToast())` → Eloquent
listeners (from `triggers()`) → `ReconcileEdge` job (`afterCommit`, carries only primitives) →
`EdgeService::reconcile()` → `edges` row → `Edge` Livewire `forUser()` → render. Push to materialize,
pull to render.

### Dismiss durations

`dismissRule()` returns one of `'forever'` (default), `'1day'`, `'1week'`, `'1month'`. `ChannelToast`
and `ProjectToast` currently use the default (forever) — the `'1month'` override was a test and removed.
A toast opts into a snooze window by returning a non-forever key; the snoozed card returns after the
window elapses (re-included by `scopeVisible`).

---

## Ephemeral toast (short-lived notice) — separate from the three signals above

A lighter, auto-dismissing feedback surface that lives **outside** `App\Services\Menu` — no DB row, no
badge, no nudge, no `edges`/`notifications` table. Always auto-dismisses (default 3000ms); never persistent.
This is the "action succeeded / action errored" feedback tier. Do **not** confuse it with Signal 3
above: that Edge toast *is* persistent (owns the `edges` table, survives reload until dismissed); this
ephemeral toast is the fire-and-forget `x-ui.modals.toast`. (A separate persistent tier —
`notice-card` / countdown banner — is tracked elsewhere, not here.)

### The one render component

`resources/views/components/ui/modals/toast.blade.php` (`<x-ui.modals.toast/>`) — self-contained
`x-data` with `show`/`message`/`type`/`timeout`; `init()` does
`window.addEventListener('toast', event => { … this.show = true; setTimeout(() => this.show = false,
this.timeout) })`. Four `type` styles (`info`/`success`/`error`/`warning`), close button, and a
`@props(['timeout' => 3000])` prop. Mounted **once globally**, not per module:
`resources/views/layouts/app.blade.php:55` → `<x-dashboard.global/>` →
`resources/views/components/dashboard/global.blade.php:8` → `<x-ui.modals.toast/>`. A single instance
catches every `toast` event from every module — do not mount a second one.

### The three emit paths that feed it

All converge on the same `window` `toast` event the component listens for:

**(a) `dispatch('toast', message:, type:)` from Livewire (no bridge needed).** Livewire emits a
browser `toast` event on the component root that bubbles to `window`, where the toast component's
`window.addEventListener('toast')` catches it directly. Used by most Livewire components: `TaskBoard\
Main`, `Project\Kanban`, `Ths\Main`/`Ths\Workspace`, `Tab\Calendar`, `Tab\Reports`, `Tab\Status`,
`Suggestion\Main`/`Suggestion\Detail`, `Reservation\Main`, `Profile\*` (Info/About/Details/Skills/
Documents), `Energy\Main`, `ReleaseRequest\Main`, and the `ManagesTaskModal` trait. This is the
default — prefer it; it needs no per-module JS.

**(b) `dispatch('show-toast', message:, type:)` from Livewire (requires a per-module JS bridge).**
Used by `Contact\Main`, `Channel\Main`, `Project\Main`. Livewire emits `show-toast`, which the toast
component does **not** listen for — each module's Alpine data wires `this.$wire.on('show-toast', e =>
this.toast(e.message, e.type ?? 'info'))`: `contact.js:119`, `channel.js:155`, `project.js:105`. The
bridge calls `chatBase.toast()` (`resources/js/components/alpine/mixins/chatBase.js:40`), which does
`window.dispatchEvent(new CustomEvent('toast', {detail:{message, type}}))` — the real event the toast
component catches. So `show-toast` is just an extra hop that re-enters path (a). **Known gap:**
`project.js:105` uses `this.toast?.(…)` (optional-call) but `project.js` does not define `toast()` nor
mix in `chatBase`, so the listener is a no-op — `Project\Main`'s `show-toast` dispatches currently go
nowhere. `contact.js`/`channel.js` mix in `chatBase` and work.

**(c) Alpine `this.$dispatch('toast', {message, type})` directly.** No Livewire involved — the
CustomEvent bubbles to `window` on its own. Used by `mixins/clipboard.js:35` (`_copyToast`) and
`data/calendar.js` (5 sites: resize/duration success+error toasts).

### `show-undo-toast` variant

`Contact\Main` and `Channel\Main` also `dispatch('show-undo-toast', message:)`, bridged in
`contact.js:121` / `channel.js:156` as `this.toast(e.message, 'warning')` — i.e. coerced to
`type:'warning'`. The "undo" intent is carried only by the event name; the toast itself is a plain
warning toast (no undo button). The in-message undo UI (`showUndo` + `undoTimeout`) is a separate
Alpine state in `contact.js`/`channel.js`, not part of the toast component.

### Do / Don't

- **DO** use path (a) `dispatch('toast', message:, type:)` from Livewire by default — no bridge needed.
- **DO** keep exactly one `<x-ui.modals.toast/>` (global.blade.php); never add a second instance.
- **DON'T** use `show-toast` in a new module — it needs a per-module bridge and exists only as legacy
  in Contact/Channel/Project. Use `toast` instead. (If fixing `project.js`'s broken bridge, prefer
  switching `Project\Main` to `dispatch('toast', …)` and dropping the `show-toast` listener over
  repairing the optional-call.)
- **DON'T** call `Filament\Notifications\Notification::make()->send()` from user-panel Livewire — that
  renders into a Filament mount that does not exist in the user panel. Admin uses `Notification::make()`
  (see `filamentPattern.md` "Admin notifications"); user-panel uses `x-ui.modals.toast`.