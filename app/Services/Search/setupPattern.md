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
│   └── … (20 in total)
├── ContentService.php
└── NavigationService.php
```

- `Contracts/Searchable.php` — interface every content resource fulfils.
- `Contracts/SearchContext.php` — immutable value object: normalized query + tokens.
- `Contracts/SearchResource.php` — abstract base; ALL the shared search machinery.
- `Resources/` — one tiny class per searchable module (20 in total).
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
| `$groupLimit` | int | max rows returned per group | `5` |
| `action($row)` | method | **required** — builds the deep-link (see §5) | — |
| `scope(Builder $q)` | method | optional — restrict visible rows (no-op by default) | no-op |
| `titleFor($row)` | method | optional — computed title (e.g. Feed, Message) | reads `$titleField` |

> **Permissions ⚠️** `scope()` MUST replicate the owning module's own listing query so the palette never surfaces a row the user couldn't otherwise see. A `null`/no-op scope means the resource is org-wide.
>
> **The mirror check runs both ways.** It's not enough to check `scope()` doesn't show a row the module hides — also check it doesn't *hide* a row the module's own policy grants. `TicketResource` originally only matched `requester_id = me OR assigned_to = me`, missing the department-head visibility into unassigned open tickets that `Ticket::scopeActionableBy()` (used by the Ths module's own "actionable" inbox) already grants. Fixed 2026-08-04 by adding the same open-ticket-targeted-at-my-department branch (see `TicketResource::scope()`), gated on `User::highestRankingInDepartment()` exactly like `scopeActionableBy()` is. Regression-covered by `test_ticket_search_resource_includes_open_department_tickets_for_department_head` / `_does_not_grant_department_visibility_to_a_non_head`. When writing or auditing a `scope()`, diff it against the owning module's own access-control method (policy, model scope, or listing query) rather than re-deriving the rule from scratch — that's where both of these bugs came from.

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
- **`ContentService`** (real records) **always emits `url:`** — every hit must deep-link to a specific record via `?open={id}`. The base provides three helpers that all produce a `url:` action:

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
- **Scope** — any message in a channel the user is a **member** of: `whereHas('channel', fn $q => $q->whereHas('members', fn $q2 => $q2->where('user_id', $me)))`. Membership excludes any channel the user is no longer a member of (so a hit's deep-link always opens); it does **not** additionally restrict by `sender_id` — a fellow member's message is just as findable as your own, matching the in-chat search bar (`SearchChannelMessagesAction`), which has never restricted by sender. An earlier version of this scope added `where('sender_id', $me)` on top of the membership check, silently narrowing global search to "messages I personally wrote" — safe (no leak, since it's the *stricter* direction) but wrong: it made global search find far less than the in-chat search already finds inside the same channel, with no error or indication anything was missing. Fixed 2026-08-04; regression-covered by `test_channel_message_search_resource_finds_any_fellow_members_message_action_and_title` / `_excludes_messages_from_channels_i_am_not_a_member_of` in `tests/Feature/Services/SearchServiceTest.php`.
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