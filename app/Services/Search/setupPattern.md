# Search Service

The global search behind the **Command Palette** (`App\Livewire\Dashboard\Navbar\CommandPalette`).
Two independent engines, both exposing `->search(string $query): array`:

| Engine | Class | Answers | Source |
|--------|-------|---------|--------|
| **Navigation** | `NavigationService` | "Which *module/page* do I open?" | a static, keyword-rich list |
| **Content**    | `ContentService`    | "Find this *actual record*" | live database rows |

The palette flips between them with its "smart button" (`mode = 'navigate' | 'content'`).

```php
$results = $mode === 'content'
    ? app(ContentService::class)->search($query)
    : app(NavigationService::class)->search($query);
```

Both return a flat, ranked array of result items (shape in §6) the front-end renders identically.

---

## 1. Folder layout

```
app/Services/Search/
├── Contracts/
│   ├── Searchable.php
│   ├── SearchContext.php
│   └── SearchResource.php
├── Resources/
│   ├── PostResource.php
│   ├── FeedResource.php
│   └── … (22 in total)
├── ContentService.php
└── NavigationService.php
```

- `Contracts/Searchable.php` — interface every content resource fulfils.
- `Contracts/SearchContext.php` — immutable value object: normalized query + tokens.
- `Contracts/SearchResource.php` — abstract base; ALL the shared search machinery.
- `Resources/` — one tiny class per searchable module (22 in total).
- `ContentService.php` — orchestrator: registry + ranking of resources.
- `NavigationService.php` — the module-shortcut engine (static list).

---

## 2. How `ContentService` works

Template Method pattern — same shape as `App\Services\Reservation\ValidationService` + its `BookingRule` validators. Three roles:

1. **`Searchable` (interface)** — the promise: `search(SearchContext $context): array`.
2. **`SearchResource` (abstract base)** — the master recipe, written once: apply scope → filter by tokens → rank in SQL → trim → shape rows. Concrete resources inherit all of it via `extends SearchResource`.
3. **`XxxResource` (concrete classes)** — the blanks filled in. Each declares **what** to search; the base decides **how** (full example in §3).

### The flow

```
CommandPalette
   └─ ContentService::search($query)
        ├─ SearchContext::for($query)        normalize + tokenize (or null if too short)
        └─ for each registered resource:
             app(XxxResource::class)->search($context)   runs SearchResource::search()
                 ├─ applyScope()        → $this->scope()      (child override or no-op)
                 ├─ applyTokenFilter()  → WHERE every token hits ≥1 column
                 ├─ relevanceExpression() → SQL score, ORDER BY it, LIMIT 5
                 └─ shapeRow()          → $this->titleFor() / subtitleFor() / action()
        → groups sorted by score, flattened into one ranked list
```

The base calls `$this->scope()`, `$this->action()`, etc., so polymorphism runs each child's version automatically.

---

## 3. Adding a new searchable module

1. Create `Resources/WidgetResource.php`:

   ```php
   namespace App\Services\Search\Resources;

   use App\Models\Widget;
   use Illuminate\Database\Eloquent\Builder;

   class WidgetResource extends SearchResource
   {
       protected string $type = 'widget';
       protected string $group = 'ابزارک‌ها';
       protected string $icon = 'widgets';
       protected string $model = Widget::class;
       protected array $columns = ['name', 'note'];
       protected ?string $titleField = 'name';
       protected ?string $subtitleField = 'note';

       public function action($row): string
       {
           return $this->route('widgets', $row->getKey());
       }

       protected function scope(Builder $query): void
       {
           $query->where('user_id', $this->me());
       }
   }
   ```

2. Register it in `ContentService::$resources` (order is the tie-breaker when scores are equal). No other file changes.

### Resource reference

| Member | Type | Purpose | Default |
|--------|------|---------|---------|
| `$type` | string | stable id, also the `record-focus` type used by the UI | — (required) |
| `$group` | string | result-group heading (RTL) | — (required) |
| `$icon` | string | material symbol | — (required) |
| `$model` | class-string | Eloquent model searched | — (required) |
| `$columns` | array | columns scanned by the filter + ranker | `[]` |
| `$titleField` | ?string | title column; `null` ⇒ override `titleFor()` | `null` |
| `$subtitleField` | ?string | subtitle column; `null` ⇒ falls back to `$group` | `null` |
| `$orderBy` | string | tie-break ordering after relevance | `'id'` |
| `$recencyMonths` | int | months bounding the `created_at` scan (§7); 0 = unbounded | `0` |
| `$groupLimit` | int | max rows returned per group | `5` |
| `action($row)` | method | **required** — builds the deep-link (see §5) | — |
| `scope(Builder $q)` | method | optional — restrict visible rows (no-op by default) | no-op |
| `titleFor($row)` | method | optional — computed title (e.g. Feed, Message) | reads `$titleField` |

> **Permissions ⚠️** `scope()` MUST replicate the owning module's own listing query so the palette never surfaces a row the user couldn't otherwise see. A `null`/no-op scope means the resource is org-wide.
>
> **The mirror check runs both ways.** Don't only check `scope()` doesn't show a row the module hides — also check it doesn't *hide* a row the module's own policy grants. `TicketResource` originally matched only `requester_id = me OR assigned_to = me`, missing the department-head visibility into unassigned open tickets that `Ticket::scopeActionableBy()` (the Ths module's own "actionable" inbox) already grants. Fixed 2026-08-04 by adding the same open-ticket-targeted-at-my-department branch, gated on `User::highestRankingInDepartment()` exactly like `scopeActionableBy()`. Regression-covered by `test_ticket_search_resource_includes_open_department_tickets_for_department_head` / `_does_not_grant_department_visibility_to_a_non_head`. When writing or auditing a `scope()`, diff it against the owning module's own access-control method (policy, model scope, or listing query) rather than re-deriving the rule from scratch.
>
> **Three more no-op/partial scopes found and fixed by a two-pass independent audit (2026-09-11):**
> - `GalleryResource` had no `scope()` at all (org-wide), while `Gallery::getBaseQuery()` (`app/Livewire/Dashboard/Tab/Gallery.php`) restricts photos to the viewer's own department (or department-less/public photos). Beyond the plain privacy leak, this produced a confusing symptom: a cross-department photo was findable via search, but `Gallery::mount()` silently drops `$open` when the matched photo isn't in `getBaseQuery()`'s result — so opening the hit landed on the plain gallery tab with no focus-chip, no error. Fixed, then DRY'd further (2026-09-11): the department where-clause now lives once as `Photo::scopeVisibleTo(?string $departmentCode)` (mirrors the Filament admin `GalleryResource` table's `departmentFilter()` query shape — `department_id = X OR JSON-contains(departments, X)` — confirmed correct), and both `Gallery::getBaseQuery()` and `GalleryResource::scope()` just call `->visibleTo($dept)` — the exact two-independent-copies drift that caused this bug can't recur.
> - `ReportResource::scope()` only checked `active = 1`, missing `Report::notExpired()` and `Report::scopeVisibleTo($dept)` that `Reports::visibleReportsQuery()` (`app/Livewire/Dashboard/Tab/Reports.php`) also requires — so expired or audience-restricted reports were searchable (and, same silent-chip-drop pattern, `Reports::focusRecord()` re-checks visibility and no-ops the chip on an unauthorized hit). Fixed by chaining the same `active()->notExpired()->visibleTo($dept)` scopes.
> - `PeopleResource::scope()` only excluded the current user (`whereKeyNot($this->me())`), missing `User::scopeVisibleOnBoard()` (active, non-Guest) that the real Contact directory (`FetchContactsAction::execute()`) applies — so inactive/departed and Guest-type accounts were searchable via global search but never appear in the actual Contact list. Fixed by adding `->visibleOnBoard()`.
>
> All three follow the same shape: read the owning module's own visibility query first, then make `scope()` call the identical scopes/conditions — never invent new department/visibility logic in a `Resources/*.php` class. `AuthorityResource` was also found fully implemented and tested (`SearchServiceTest`) but never registered in `ContentService::$resources` — a real "search returns nothing for this module" bug, deliberately left unfixed pending a product decision on whether Authority content should be globally searchable at all.
>
> **Front-end race, not a `scope()` issue:** `command-palette.blade.php`'s Enter-key handler read `$wire.results[selectedIndex]` with no guard against the in-flight debounce window (`wire:model.live.debounce.{150|300}ms`) — pressing Enter right after typing could act on the previous query's stale results and navigate to an unrelated hit. Fixed by tracking freshness in `search.js` (`resultsFresh`, set `false` on `$wire.query` change, `true` on `$wire.results` change) and gating the Enter handler on it.

---

## 4. Relevance ranking

`SearchContext::for()` normalizes the query (lowercase, fold Persian `ي→ی` / `ك→ک`, collapse whitespace) and splits it into tokens. Queries shorter than 2 chars return `[]`.

Each resource then runs, in MySQL:

- **Coarse filter** — every token must appear in at least one column (AND across tokens, OR across columns).
- **Score** (summed per row):

  | Match | Points |
  |-------|--------|
  | title equals the whole query exactly | **120** |
  | title starts with the query | **40** |
  | a token appears in the title | **40** |
  | a token appears in any scanned column | **8** |

- Rows ordered by `search_relevance DESC`, then `$orderBy DESC`, capped at `$groupLimit` (5). Groups sorted by their top score, then flattened into one list.

---

## 5. Action strings & URL navigation types

Every result item carries an **`action`** string `type:target`. `CommandPalette::selectResult()` splits on the first `:` and routes it:

```php
match ($type) {
    'tab'   => $this->handleTab($target),
    'route' => $this->handleRoute($target),
    'url'   => $this->handleUrl($target),
    'event' => $this->handleEvent($target),
};
```

| Prefix | Target is… | What happens | Example |
|--------|-----------|--------------|---------|
| `tab:` | a dashboard tab key | If already on the dashboard → SPA `switch-tab` (no reload); otherwise redirect to `/dashboard?tab=…`. | `tab:home` |
| `route:` | a named route | `redirectRoute($target, navigate: true)` — SPA navigate. | `route:reservation` |
| `url:` | a raw URL path | `redirect($target, navigate: true)`. The destination reads `?open={id}` (via `FocusOnRecord`) and focuses that record. | `url:/profile?tab=credentials&open=8` |
| `event:` | a Livewire/Alpine event name | `dispatch($target)` — opens an in-page widget or logs out. | `event:logout` |

### Which engine emits what

- **`NavigationService`** (module shortcuts) emits all four: `tab:`, `route:`, `url:`, `event:`.
- **`ContentService`** (real records) **always emits `url:`** — every hit deep-links to a specific record. The base provides three helpers that all produce a `url:` action:

  ```php
  $this->tab('post', $id);
  $this->route('dms', $id);
  $this->url('/profile?tab=credentials&open=' . $id);
  ```

  > `tab()` is named for convenience but returns a **`url:`** action (a full path with `?open=`), *not* a `tab:` action — content results need to focus a record, which the `url:` handler + `FocusOnRecord` trait do. Plain tab-switching is a navigation concern.

### Two-param deep-link (channel message → focus a message, not just a channel)

`ChannelMessageResource` extends `?open={id}` with a second query param to focus a **nested record** (a message inside a channel):

```php
return 'url:' . route('channels', [
    'open'       => (int) $row->channel_id,
    'focus_msg'  => (int) $row->getKey(),
], false);
```

- `?open={channelId}` is consumed by `FocusOnRecord` as usual (`#[Url] $open` → `focusRecord(channelId)` → `selectChannel`).
- `?focus_msg={messageId}` is read by the channel component's `focusRecord()` via `request()->query('focus_msg')` (mount-only; no-op on later AJAX), which then calls `focusMessage(id)` — reusing the global `record-focus` standard with `type:'channel-message'`.
- **Scope** — any message in a channel the user is a **member** of: `whereHas('channel', fn $q => $q->whereHas('members', fn $q2 => $q2->where('user_id', $me)))`. Membership excludes channels the user left (so a hit's deep-link always opens); it does **not** additionally restrict by `sender_id` — a fellow member's message is as findable as your own, matching the in-chat search bar (`SearchChannelMessagesAction`), which has never restricted by sender. An earlier version added `where('sender_id', $me)` on top of membership — safe (no leak, *stricter* direction) but wrong: global search found less than the in-chat search in the same channel, with no error. Fixed 2026-08-04; regression-covered by `test_channel_message_search_resource_finds_any_fellow_members_message_action_and_title` / `_excludes_messages_from_channels_i_am_not_a_member_of` in `tests/Feature/Services/SearchServiceTest.php`.
- The `type` (`'channel-message'`) is shared with the in-chat search's `data-rf="channel-message-{id}"` focus key, so both entry points reuse the same `scrollToRecord` + `.record-focus-flash` UX.

---

## 6. Output shape (the contract the front-end consumes)

```php
[
    'id'       => 5,
    'title'    => 'عنوان مورد',
    'subtitle' => 'توضیح کوتاه',
    'icon'     => 'campaign',
    'group'    => 'پست و اعلانات',
    'type'     => 'post',
    'action'   => 'url:/dashboard?tab=post&open=5',
    'score'    => 168,
]
```

Keep this shape stable — the palette JS and the `record-focus` flow depend on it.

---

## 7. Recency window (bounded by default, expanded explicitly)

The content engine's coarse filter is a leading-wildcard `LIKE` per token — non-sargable, so every query is O(table size). On the high-volume history tables that grows linearly forever; benchmarked at ~150–730ms per query at only ~2.5K total rows (TicketResource heaviest, 28ms alone).

The fix is the standard ERP pattern — **bound by default, expand explicitly**:

- **`$recencyMonths`** on the resource (default `0` = unbounded, for small/static tables). The 7 high-volume resources set `6`: `TicketResource`, `DmsResource`, `ChannelMessageResource`, `MessageResource`, `FeedResource`, `PostResource`, `ReservationResource`. `applyRecencyWindow()` in the base adds `WHERE created_at >= now() - N months` **before** the LIKE filter, so the sargable date range prunes the scan set.
- **`SearchContext::$allHistory`** — a per-request escape hatch. `ContentService::search($query, $allHistory = false)` threads it through, and a resource with `$recencyMonths > 0` skips its window when it's true.
- **UI** (`CommandPalette`) — the bound is ambient, not one-shot: the content-mode placeholder names «۶ ماه اخیر»; the footer scope badge (replacing the old "Heuristic" chip) always shows the live scope — `history` + «۶ ماه اخیر» ⇄ `all_inclusive` + «کل تاریخ»; a "جستجو در کل تاریخ" chip appears when results are thin (≤ 5) letting the user re-run whole-history (`searchAllHistory()`); an active-mode chip confirms the expansion; and the no-results empty state in content mode explicitly says the window was the reason («در ۶ ماه اخیر نتیجه‌ای نبود؛ دکمهٔ «جستجو در کل تاریخ» را بزنید») instead of a generic "try another term". `toggleMode()` and `resetPalette()` both clear `allHistory`, so it's always an explicit per-query act.

**Indexes:** the window only helps if `created_at` is indexed — migration `2026_09_14_000001_add_created_at_indexes_for_content_search.php` covers `tickets`, `dms`, `channel_messages`, `messages`, `reservations` (feeds/posts already had one). When adding `$recencyMonths > 0` to a new resource, check `SHOW INDEX` for its table first and extend the migration pattern if needed.

**Rule:** a resource that grows without bound (user-generated content, messages, tickets) ships with `$recencyMonths = 6` + a `created_at` index from day one — not as a retrofit when the palette starts feeling slow. Small/static tables (people, skills, links…) stay `0` so nothing is silently hidden.