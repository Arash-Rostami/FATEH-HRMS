# Search Service

The global search behind the **Command Palette** (`App\Livewire\Dashboard\Navbar\CommandPalette`).
It has **two independent engines**, both exposing the same `->search(string $query): array` method:

| Engine | Class | Answers the question | Source |
|--------|-------|----------------------|--------|
| **Navigation** | `NavigationService` | "Which *module/page* do I want to open?" | a static, keyword‑rich list |
| **Content**    | `ContentService`    | "Find this *actual record* (a post, a ticket, a person…)" | live database rows |

The palette flips between them with its "smart button" (`mode = 'navigate' | 'content'`).

```php
$results = $mode === 'content'
    ? app(ContentService::class)->search($query)   // real records
    : app(NavigationService::class)->search($query); // module shortcuts
```

Both return a flat, ranked array of **result items** (shape below) that the front‑end renders identically.

---

## 1. Folder layout

```
app/Services/Search/
├── Contracts/
│   ├── Searchable.php        # the interface every content resource fulfils
│   └── SearchContext.php     # immutable value object: normalized query + tokens
│   ├── SearchResource.php    # abstract base — ALL the shared search machinery
├── Resources/
│   ├── PostResource.php      # one tiny class per searchable module …
│   ├── FeedResource.php
│   └── … (20 in total)
├── ContentService.php        # orchestrator: registry + ranking of resources
└── NavigationService.php     # the module-shortcut engine (static list)
```

---

## 2. How `ContentService` works (the architecture)

It uses the **Template Method** pattern — the exact same shape as
`App\Services\Reservation\ValidationService` + its `BookingRule` validators.

Three roles:

1. **`Searchable` (interface)** — the *promise*: "I can be searched."
   ```php
   interface Searchable {
       public function search(SearchContext $context): array;
   }
   ```

2. **`SearchResource` (abstract base)** — the *master recipe*. Written **once**, it owns the
   whole procedure: apply scope → filter by tokens → rank in SQL → trim → shape rows.
   Each concrete resource inherits all of it via `extends SearchResource`.

3. **`XxxResource` (concrete classes)** — the *blanks filled in*. Each one only declares
   **what** to search; the base decides **how**.

   ```php
   class PostResource extends SearchResource
   {
       protected string $type = 'post';
       protected string $group = 'پست و اعلانات';
       protected string $icon = 'campaign';
       protected string $model = Post::class;
       protected array $columns = ['title', 'body'];
       protected ?string $titleField = 'title';
       protected ?string $subtitleField = 'body';

       public function action($row): string
       {
           return $this->tab('post', $row->getKey());
       }
   }
   ```

### The flow

```
CommandPalette
   └─ ContentService::search($query)
        ├─ SearchContext::for($query)        // normalize + tokenize (or null if too short)
        └─ for each registered resource:
             app(XxxResource::class)->search($context)   // runs SearchResource::search()
                 ├─ applyScope()        → $this->scope()      (child override or no-op)
                 ├─ applyTokenFilter()  → WHERE every token hits ≥1 column
                 ├─ relevanceExpression() → SQL score, ORDER BY it, LIMIT 5
                 └─ shapeRow()          → $this->titleFor() / subtitleFor() / action()
        → groups sorted by score, flattened into one ranked list
```

Because the base calls `$this->scope()`, `$this->action()`, etc., **polymorphism** makes each
child's version run automatically — the base never needs to know which resource it is.

---

## 3. Adding a new searchable module (the only steps)

1. Create `Resources/WidgetResource.php`:

   ```php
   namespace App\Services\Search\Resources;

   use App\Models\Widget;
   use Illuminate\Database\Eloquent\Builder; // only if you override scope()

   class WidgetResource extends SearchResource
   {
       protected string $type = 'widget';        // stable id + record-focus type
       protected string $group = 'ابزارک‌ها';     // RTL label for the result group
       protected string $icon = 'widgets';        // material symbol
       protected string $model = Widget::class;   // Eloquent model
       protected array $columns = ['name', 'note']; // columns to scan + rank
       protected ?string $titleField = 'name';      // title column
       protected ?string $subtitleField = 'note';   // subtitle column (null → group label)

       public function action($row): string
       {
           return $this->route('widgets', $row->getKey()); // see "navigation types" below
       }

       // OPTIONAL — restrict rows to what the user may see:
       protected function scope(Builder $query): void
       {
           $query->where('user_id', $this->me());
       }
   }
   ```

2. Register it in `ContentService::$resources` (order is the tie‑breaker when scores are equal).

That's it — no other file changes.

### Resource reference

| Member | Type | Purpose | Default |
|--------|------|---------|---------|
| `$type` | string | stable id, also the `record-focus` type used by the UI | — (required) |
| `$group` | string | result‑group heading (RTL) | — (required) |
| `$icon` | string | material symbol | — (required) |
| `$model` | class‑string | Eloquent model searched | — (required) |
| `$columns` | array | columns scanned by the filter + ranker | `[]` |
| `$titleField` | ?string | title column; `null` ⇒ override `titleFor()` | `null` |
| `$subtitleField` | ?string | subtitle column; `null` ⇒ falls back to `$group` | `null` |
| `$orderBy` | string | tie‑break ordering after relevance | `'id'` |
| `$groupLimit` | int | max rows returned per group | `5` |
| `action($row)` | method | **required** — builds the deep‑link (see §5) | — |
| `scope(Builder $q)` | method | optional — restrict visible rows (no‑op by default) | no‑op |
| `titleFor($row)` | method | optional — computed title (e.g. Feed, Message) | reads `$titleField` |

> **Permissions ⚠️** `scope()` MUST replicate the owning module's own listing query so the
> palette never surfaces a row the user couldn't otherwise see. A `null`/no‑op scope means
> the resource is org‑wide.

---

## 4. Relevance ranking

`SearchContext::for()` first **normalizes** the query (lowercase, fold Persian `ي→ی` / `ك→ک`,
collapse whitespace) and splits it into **tokens**. Queries shorter than 2 chars return `[]`.

Each resource then runs, in MySQL:

- **Coarse filter** — every token must appear in **at least one** column
  (AND across tokens, OR across columns).
- **Score** (summed per row):

  | Match | Points |
    |-------|--------|
  | title equals the whole query exactly | **120** |
  | title starts with the query | **40** |
  | a token appears in the title | **40** |
  | a token appears in any scanned column | **8** |

- Rows ordered by `search_relevance DESC`, then `$orderBy DESC`, capped at `$groupLimit` (5).
- Groups are sorted by their top score, then **flattened** into one list.

---

## 5. Action strings & URL navigation types

Every result item carries an **`action`** string in the form **`type:target`**.
`CommandPalette::selectResult()` splits on the first `:` and routes it:

```php
match ($type) {
    'tab'   => $this->handleTab($target),   // switch dashboard tab
    'route' => $this->handleRoute($target), // go to a named route
    'url'   => $this->handleUrl($target),   // go to a raw URL (focuses ?open=id)
    'event' => $this->handleEvent($target), // fire a Livewire/Alpine event
};
```

### The four types

| Prefix | Target is… | What happens | Example |
|--------|-----------|--------------|---------|
| `tab:` | a dashboard tab key | If already on the dashboard → SPA `switch-tab` (no reload); otherwise redirect to `/dashboard?tab=…`. | `tab:home` |
| `route:` | a **named route** | `redirectRoute($target, navigate: true)` — SPA navigate. | `route:reservation` |
| `url:` | a **raw URL path** | `redirect($target, navigate: true)`. The destination module reads `?open={id}` (via the `FocusOnRecord` trait) and focuses that record in place. | `url:/profile?tab=credentials&open=8` |
| `event:` | a Livewire/Alpine event name | `dispatch($target)` — opens an in‑page widget (radio, calculator, timer) or logs out. | `event:logout` |

### Which engine emits what

- **`NavigationService`** (module shortcuts) emits all four: `tab:`, `route:`, `url:`, `event:`.
- **`ContentService`** (real records) **always emits `url:`** — because every hit must deep‑link
  to a *specific record* via `?open={id}`. The base class provides three helpers that all
  produce a `url:` action:

  ```php
  $this->tab('post', $id);   // → url:/dashboard?tab=post&open={id}
  $this->route('dms', $id);  // → url:/dms?open={id}
  $this->url('/profile?tab=credentials&open=' . $id); // → url:/profile?...
  ```

  > Note: `tab()` here is named for convenience but still returns a **`url:`** action (a full
  > path with `?open=`), *not* a `tab:` action — content results need to focus a record, which
  > the `url:` handler + `FocusOnRecord` trait do. Plain tab‑switching is a navigation concern.

### Two-param deep-link (channel message → focus a message, not just a channel)

`ChannelMessageResource` extends the standard `?open={id}` pattern with a second
query param so a content hit can focus a **nested record** (a message inside a channel),
not just the top-level record (the channel):

```php
// ChannelMessageResource::action($row)
return 'url:' . route('channels', [
    'open'       => (int) $row->channel_id,   // FocusOnRecord opens this channel
    'focus_msg'  => (int) $row->getKey(),    // Channel::focusRecord() then focuses this message
], false);
```

- `?open={channelId}` is consumed by the `FocusOnRecord` trait as usual (`#[Url] $open` → `focusRecord(channelId)` → `selectChannel`).
- `?focus_msg={messageId}` is read by the channel component's `focusRecord()` via `request()->query('focus_msg')` (mount-only; no-op on later AJAX), which then calls `focusMessage(id)` — the same method the in-chat search uses, reusing the global `record-focus` standard with `type:'channel-message'`.
- **Scope** — the user's **own sent messages** only: `where('sender_id', $me)` + `whereHas('channel', fn $q => $q->whereHas('members', fn $q2 => $q2->where('user_id', $me)))`. The `sender_id` clause excludes other members' messages; the membership clause excludes any channel the user is no longer a member of (so a hit's deep-link always opens). No org-wide scope, no non-member (public or private) channel content. (The in-chat channel search is deliberately broader — it searches the whole conversation once the channel is open.)
- The `type` (`'channel-message'`) is shared with the in-chat search's `data-rf="channel-message-{id}"` focus key, so both entry points reuse the same `scrollToRecord` + `.record-focus-flash` UX.

---

## 6. Output shape (the contract the front‑end consumes)

Every item in the returned array looks like this:

```php
[
    'id'       => 5,                                  // model key
    'title'    => 'عنوان مورد',                        // superClean, max 80, '—' if empty
    'subtitle' => 'توضیح کوتاه',                       // subtitleField (max 120) or the group label
    'icon'     => 'campaign',                          // material symbol
    'group'    => 'پست و اعلانات',                     // group heading
    'type'     => 'post',                              // resource type / record-focus type
    'action'   => 'url:/dashboard?tab=post&open=5',    // see §5
    'score'    => 168,                                 // relevance (debug/sort)
]
```

Keep this shape stable — the palette JS and the `record-focus` flow depend on it.
```
