**VERDICT:** The application suffers from systemic N+1 issues in relation managers, severe over-polling in Livewire components (`wire:poll.10s`), missing indexes for `WHERE JSON_EXTRACT` queries, and expensive un-cached `selectRaw(SUM(CASE...))` badge calculations hitting the database on every Filament page load.

**TOP 3 IMPACT WINS:**
1. Globalize caching for Filament `getStats()` to prevent heavy `SUM(CASE...)` scans running on every page load.
2. Remove `wire:poll.10s` from `Contact/Main.blade.php` and use Echo/Pusher broadcast events.
3. Add Virtual Generated Columns with indexes for heavily queried JSON fields in `Authority` and `Gallery`.

---

### HIGH SEVERITY

**1. Livewire Contact Module Over-Polling**
- **Location:** `resources/views/livewire/dashboard/contact.blade.php:4`
- **Evidence:** `wire:poll.10s` triggers `contacts()` and `messages()` computed properties every 10 seconds. `FetchContactsAction` runs two dependent subqueries per render: `MAX(id)` and `COUNT(*)` over `messages` table without `sender_id`/`recipient_id` compound indexes. At 100 concurrent users, this is 600 heavy queries per minute.
- **Root Cause:** Polling instead of WebSocket broadcasting for chat sync.
- **Minimal Fix:** Remove `wire:poll.10s`. Dispatch and listen to a `Echo.private('chat.user.' + userId)` event to trigger `$refresh` only when an actual message is received.

**2. Per-Row SUM(CASE...) Table Scans in Filament Tabs**
- **Location:** All 18+ Filament Resource `List*.php` pages (e.g., `app/Filament/Resources/ThsResource/Pages/ListTickets.php:70`)
- **Evidence:** `once(fn() => Ticket::query()->selectRaw("SUM(CASE...")...)` caches the result *only for the duration of the current request*. Every time any user loads or refreshes a List page, a full table scan is performed to calculate the badge counts.
- **Root Cause:** Misunderstanding of Laravel's `once()`. It caches in-memory for one request, not globally across requests like `Cache::remember`.
- **Minimal Fix:** Wrap the query in `Cache::remember`:
```php
return Cache::remember("tickets_stats", now()->addMinutes(15), fn() => Ticket::query()->selectRaw("...")->first());
```

**3. Missing Indexes on High-Traffic JSON Queries**
- **Location:** `app/Filament/Resources/AuthorityResource/Schemas/AuthorityTablePresenter.php:21`
- **Evidence:** Queries use `whereRaw("JSON_UNQUOTE(JSON_EXTRACT(details, '$.impact_score')) = ?")`. In an FK-less MySQL environment without indexes, this forces a full table scan of the `authorities` table for every filter application.
- **Root Cause:** Filtering directly on JSON string attributes without generated columns.
- **Minimal Fix:** Add a virtual generated column for frequently filtered JSON keys and index them in a new migration:
```php
$table->string('impact_score')->virtualAs("JSON_UNQUOTE(JSON_EXTRACT(details, '$.impact_score'))")->index();
```

### MEDIUM SEVERITY

**4. N+1 Issue in Livewire Contact List**
- **Location:** `app/Livewire/Dashboard/Contact/Main.php:67`
- **Evidence:** In `contacts()`, the `User::with(['profile.department'])` is eager loaded, but `FetchContactsAction` uses raw subqueries for `last_message_id`. Then `Main.php` fetches `$lastMessages` via a separate query `Message::whereIn('id', $messageIds)->get()`, looping over it manually.
- **Root Cause:** Separation of subqueries and manual collection mapping instead of pushing relations to SQL.
- **Minimal Fix:** Use a native `hasOne` relation for `latestMessage` on the `User` model with `ofMany()` and eager load it `User::with(['profile.department', 'latestMessage'])`.

**5. N+1 in Profile Hierarchy Ranking**
- **Location:** `app/Models/Traits/HasProfileHierarchy.php:57`
- **Evidence:** `highestRankingInDepartments` gets users and then groups them using `groupBy` on the collection, sorting them using a PHP callback.
- **Root Cause:** Loading all users in a department into a Collection just to find the highest-ranking one.
- **Minimal Fix:** Push the ranking logic to the database using an `ORDER BY FIELD(position, 'c-manager', 'manager', ...)` and `LIMIT 1` per department.

### LOW SEVERITY

**6. BadgeSyncService Redundant Queries**
- **Location:** `app/Services/Menu/BadgeSyncService.php:14`
- **Evidence:** `sync()` method checks `$query->exists()` before writing, but the `$query` does not use an index. On large notification tables, this is a slow read.
- **Root Cause:** Missing compound index on `notifications(notifiable_id, type, data->'menu_key')`.
- **Minimal Fix:** Since data is JSON, add an index on `notifiable_id` and `type` at a minimum, or transition `menu_key` to a dedicated column on a custom notification table.
