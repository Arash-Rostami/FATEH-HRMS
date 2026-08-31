# Contact Module Pattern Guide

The internal **one-to-one messenger** (`/contacts`). Every active user is reachable by every other active user — there is **no membership gate** (unlike the sibling `channels` module, which is one-to-many broadcast with a `channel_members` gate). Built with Livewire v4.1 islands, Filament v5 admin, Laravel 12, PHP 8.2. MySQL 5.7-compatible baseline (production 8+).

This doc is the **single source of truth** for every file, contract, rule, and gotcha in the module. Read it before touching anything contact-related. The canonical **general** rules for `@island` scope/memoization live in `app/Livewire/livewirePattern.md`; this doc covers the Contact-specific architecture, contracts, and the island-migration history (with the concrete island instance retained in §3 as the lesson that motivated those general rules).

---

## 1. Stack & compatibility (verified)

- **Livewire `^4.1`** (NOT v3) — `@island`, `#[Computed]`, `#[Locked]`, `#[Isolate]` (class-level, on `Main` — Channel parity), `#[Lazy]` (class-level, on `Main` — Channel parity; closes the same gap documented in `channelPattern.md` against `app/Livewire/livewirePattern.md:803`'s full-page-route-component rule), `#[Async]` (fire-and-forget; `markRead`), `#[Js]` (pure client-side JS return; `cancelReply`/`cancelEdit`), Livewire `Form` objects, `$wire.$island('name').method()`.
- **Filament `v5`** — no admin resource for the `messages` table (Contact writes are user-panel-only; no Contact-specific admin resource).
- **Laravel 12**, **PHP 8.2** (NO property hooks).
- **MySQL**: 5.7-compatible (dev) / 8+ (prod). `unionAll`, `leftJoinSub`, composite/covering indexes are used and safe. No `JSON_TABLE`, no window functions. `users.id` = `bigIncrements` → all FKs `unsignedBigInteger`.
- The `messages` table is **Contact-only** (`ChannelMessage` is a separate model on the separate `channel_messages` table; `Message` is the 1:1 model). Contact writes `messages` rows where `sender_id`/`recipient_id` are the two users.

---

## 2. The one invariant that drives everything

> **A user can message any active user. There is no membership gate — it is 1:1, not broadcast.**

Everything flows from this:

- **Sidebar list** = all colleague-visible users (`FetchContactsAction` returns `User::visibleOnBoard()` — excludes `UserType::Guest`, same scope the Status board uses — with `leftJoinSub` for last-message + unread; a user with no prior conversation still appears, with `last_message = null`, `unread_count = 0`). Contrast Channel, where the sidebar is your memberships only.
- **`selectContact`** guards only on `User::getCachedAllOptions()->has($userId)` (user exists/active) — no membership row, no `whereHas`. Silent `return` on fail (not a 403).
- **Unread** = messages where `recipient_id = me`, `read_at IS NULL`, `deleted_at IS NULL`, counted per contact (`unreadSub` in `FetchContactsAction`).
- **Marking read** = `MarkMessagesAsReadAction` sets `read_at` on the contact's inbound messages to me.
- There is **no create/browse flow** — every active user is already reachable. There is **no channel type**, no owner, no join/leave.

---

## 3. Architecture — the rules that matter

### 3.1 Islands (the critical lesson — read twice)

Two islands: `@island(name:'sidebar')` and `@island(name:'messages')`, wrapped in `contact.blade.php`.

**Island scope rule (hard-won, ported from Channel):** on an **island-scoped method call** (e.g. `$wire.$island('messages').selectContact(id)`, which re-renders the messages island natively after `selectContact`), the island re-renders with a **reduced scope**:
- ✅ Available: public properties (as bare vars: `$activeUserId`, `$search`, `$filter`, `$mobileShowChat`, `$hasOlder`) **and** `$this` (the component instance) **and** `$__livewire`.
- ❌ NOT available as bare vars: `#[Computed]` properties (`$activeContact`, `$contacts`, `$messages`, `$groupedMessages`, `$lastMessageId`, `$presenter`).

**Therefore:** inside islands and their `@include` partials, **always** reach computeds via `$this->` (e.g. `$this->activeContact`, `$this->contacts`, `$this->presenter`), never the bare variable. Each partial that needs the presenter opens with `@php($p = $this->presenter; ...)` and uses the **local** `$p`. A bare `$activeContact` in an island partial throws "Undefined variable $activeContact" on the refresh path. `Livewire::test()->html()` does a **full render** that shares scope globally and **masks this bug** — only the real browser refresh exposes it.

**This is Contact's own history, not just a copy of Channel's rule.** The pre-migration monolith injected `['activeContact' => $this->activeContact, 'p' => new ContactPresenter()]` into `render()` and every partial read the bare `$activeContact`/`$p`. The island migration (Phase B) replaced every one of those 29 reads with `$this->activeContact` / a local `$p = $this->presenter`, and only then — Phase A step 2, its own commit — dropped the `render()` injection. The grep audit in §10 is the artifact that proves §3 stays true going forward.

**Nested anonymous Blade components (`<x-...>`) inside an island do NOT inherit `$__livewire`.** So `@entangle` / `<x-ui.forms.search>` (which needs `$__livewire`) fatals inside an island. The sidebar search reuses Channel's shared `livewire.dashboard.channel.search-field` partial — island-safe: plain `wire:model.live.debounce` + Alpine clear + `x-teleport` overlay + `wire:ignore.self`; the `refreshSidebarOnClose` prop appends `$wire.$island('sidebar').refreshUnread()` to its close handler. **Alpine-state contract:** the partial binds component-level Alpine state named `{model}Fullscreen` and `{model}Value` (e.g. `model='search'` → `searchFullscreen` / `searchValue`; `model='messageSearch'` → `messageSearchFullscreen` / `messageSearchValue`). The consumer's Alpine data object MUST declare both, or Alpine throws `... is not defined` on every island morph — which silently breaks the morph and stops new content (e.g. a sent message bubble) from rendering. `contact()` declares `searchFullscreen`/`searchValue` (sidebar) and `messageSearchFullscreen`/`messageSearchValue` (in-chat `messaging/search.blade.php`), mirroring `channel()` 1:1.

### 3.2 Polling (D1 — why Contact's poll differs from Channel's)

**Contact polls TWO islands; Channel polls only the sidebar.** This is a deliberate divergence — do NOT "fix" it back to Channel's sidebar-only pattern.

`contact.js` `startPolling()` runs a `setInterval(10s)` (no `wire:poll` — `wire:poll` was removed in Phase B) that, each tick:
1. `$wire.$island('sidebar').refreshUnread()` — **always** (recompute the contact list → fresh unread counts + last-message previews for incoming messages).
2. `$wire.$island('messages').refreshActive()` — **only when `activeUserId` is set** (a conversation is open) — recompute the open thread so a new incoming message appears live without a manual reload.

**Why both:** Contact's open 1:1 conversation must reflect a new incoming message during the poll. Channel's sidebar-only poll works for broadcast because the channel list is the live-update surface; the open channel pane re-renders only on explicit interaction. For Contact, dropping the `messages.refreshActive` leg (i.e. matching Channel exactly) would lose live incoming-message updates in the open conversation — a regression. The `activeUserId` guard avoids a wasted re-render when no conversation is open.

`visibilitychange` gates polling: `document.hidden` → `stopPolling()`, visible → `startPolling()`. Both are wired in `init()` and torn down in `destroy()`.

### 3.3 Growing-LIMIT + anchor-window pagination (the +1-probe `hasOlder` — intentionally identical in shape to Channel §3.3)

`Main::messages()` has two modes, **both** using `take(N + 1) → count() > N → take(N)` so `hasOlder` costs zero extra queries:

- **Recent-N** (`focusAnchorId = null`, the default): ONE indexed query `latest('id')->take(messagesLimit + 1)`, `hasOlder = count() > messagesLimit`, trim to `messagesLimit`, `sortBy('id')` oldest-first (recent stay at bottom).
- **Anchor** (`focusAnchorId` set by `focusMessage`): TWO indexed queries — `where('id','>=',anchor)->oldest('id')->take(6)` (anchor + up to 5 newer) + `where('id','<',anchor)->latest('id')->take(focusOlder + 1)`; `hasOlder = older.count() > focusOlder`; trim older to `focusOlder`, merge, `sortBy('id')`.

`loadMoreMessages()` is two lines — anchor mode: `focusOlder += 10`; else `messagesLimit += 10`; then `invalidateMessageCache()` — the same query/queries now fetch the grown window, so older rows auto-prepend above recent in one backward scan. No client array, no cap; mutations (`send`/`deleteMessage`/`saveEdit`/`undoDelete`) stay byte-identical — their existing `invalidateMessageCache()` re-queries the full grown window. `#[Computed] messages` caches per-request; `unset()` invalidates.

**`FocusMessageAction::execute` returns `?bool`** (3-way): `null` = invalid/not-in-conversation/no-such-message → `Main::focusMessage` bails; `false` = the target is inside the recent window → clear any stale anchor + `invalidateMessageCache` + `dispatch('record-focus', type:'message')`; `true` = target is beyond the window → set `focusAnchorId=id`+`focusOlder=5` + `invalidateMessageCache` + dispatch. The 3-way return lets Main branch without re-querying; the over-limit check itself is a +1 probe (`take(loadedLimit + 1).count() > loadedLimit`).

### 3.4 What this module deliberately does NOT have

- **No modals** — true architectural fact (1:1 needs no create/manage-members modal; every active user is reachable).
- **No `HasMenuState` / bare-key badge / sitewide nudge bell** — unread is surfaced only via `data-total-unread` on the sidebar `<aside>`, read by `contact.js`'s `MutationObserver` (`syncPushNotify`) to fire browser push notifications. Adding a nudge/badge row to `notifications` would pollute the Filament bell — see `feedback_no_nudge_badge_proposals`.
- **No `wire:poll`** — replaced by JS `setInterval` + `visibilitychange` (§3.2) so polling is pauseable and island-scoped.
- **No `chat-ready` event / `dispatchReady` parameter** — removed (D2); see §3.5.
- **No membership gate / channel type / create-or-browse flow / owner concept.**

### 3.5 The D2 decision — `chat-ready` is gone, do not reintroduce it

The pre-migration code dispatched a `chat-ready` event from server-side `selectContact` whenever `dispatchReady = ($focusMsg <= 0)`, and `contact.js` listened `x-on:chat-ready.window` → `scrollToBottom(false)` (+ mobile textarea focus). This **raced** with `record-focus`'s `scrollIntoView` for a `focus_msg` deep link — both competed for `#msg-viewport`'s scroll position; the deferred `$nextTick` overrode the synchronous record-focus scroll. The `dispatchReady` flag was a patch on a fundamentally racy mechanism.

The migration **removed `chat-ready` entirely** (no event, no `dispatchReady` param, no `x-on:chat-ready` listener). The deep-link scroll-to-bottom is restored **race-free** instead: `contact.js init()` runs a **mount-only** block, gated on the exact old condition (`activeUserId set && focus_msg <= 0`), reading `focus_msg` from `window.location.search` and `activeUserId` from the snapshot. It is mutually exclusive with the `focus_msg` path (which uses `record-focus` message-scroll) and uses a different scroll container from `record-focus.js`'s `type:'people'` sidebar-row scroll — so no double-scroll, no race. See §13.2.

---

## 4. Data model (no new tables from this migration)

Contact rides the existing `messages` + `users` tables. No migration was added by the island migration. (The covering indexes that make `FetchContactsAction` fast — `idx_sent_covering` (`[sender_id, deleted_at, recipient_id, id]`) and `idx_received_covering` (`[recipient_id, deleted_at, read_at, sender_id, id]`) — live in the `2026_06_30_000019_create_messages_table.php` migration; `FetchContactsAction` went 1103ms → 8.8ms via `UNION ALL` + covering indexes. See `project_perf_audit_2026_06`.)

`messages` columns Contact uses: `sender_id`, `recipient_id`, `body`, `attachments` (JSON), `is_edited`, `read_at` (nullable), `reply_to_id` (nullable FK → messages), `created_at`, `deleted_at` (SoftDeletes). `sender_id`/`recipient_id` are `onDelete('cascade')` (a deleted user's messages are removed, not orphaned); `reply_to_id` is `onDelete('set null')`. The `ناشناس` sender fallback in the presenter is defensive — unreachable under cascade (the FKs are defined in the same `2026_06_30_000019_create_messages_table.php` migration referenced above; no schema change was added by the island migration).

---

## 5. File map (every file, one-line purpose)

```
app/Livewire/Dashboard/Contact/
├── Main.php                                            THE Livewire component (user panel)
├── Forms/
│   ├── MessageComposerForm.php                         send form (body/attachments) + inline #[Validate]
│   └── EditMessageForm.php                             edit form (editingBody) + inline #[Validate]
├── Actions/
│   ├── FetchContactsAction.php                         sidebar list — UNION ALL sent+received → last_message_id; leftJoinSub unread; User::visibleOnBoard()
│   ├── SendMessageAction.php                           DB::transaction send + attachment store + reply-link validation (drops out-of-conversation reply_to)
│   ├── SaveEditAction.php                              edit (sender-scoped + 600s limit)
│   ├── DeleteMessageAction.php                         soft-delete, returns snapshot for undo
│   ├── UndoDeleteAction.php                            restore from snapshot
│   ├── FocusMessageAction.php                          focusMessage guard — ?bool 3-way (null/false/true) via +1-probe over-limit check
│   ├── MarkMessagesAsReadAction.php                    set read_at on contact's inbound messages to viewer
│   ├── SearchMessagesAction.php                        in-chat message search (MySQL full-text `MATCH AGAINST` boolean mode, min 3 chars)
│   ├── ForceDeleteMessageAction.php                    $message->forceDelete() (pruning; hooks unlink files)
│   └── (no Join/Leave/Create — 1:1, no membership)
├── Presentation/ContactPresenter.php                   all view-shaping (sidebar/contact/totalUnread/messageGroup/messages)
└── (contactPattern.md — this file)

resources/views/livewire/dashboard/
├── contact.blade.php                  root view (x-data=contact(), two @island blocks; keydown handlers + modals + quote-chip + switch-tabs at root)
├── contact/sidebar.blade.php          contact list + shared search-field + filter tabs (uses $this->contacts; local $p=$this->presenter)
├── contact/header.blade.php           conversation header (uses $this->activeContact)
├── contact/messages.blade.php         message list + date groups + empty-state (uses $this->activeContact; local $p=$this->presenter)
├── contact/composer.blade.php         input + attachments + emoji + reply/edit
├── contact/info.blade.php             contact info side-panel (uses $this->activeContact)
├── (in-chat search → shared `messaging/search.blade.php`, @include'd by header; see §16A)
├── contact/empty.blade.php            no-contact-selected state (rendered inside the messages island @else)
└── (quote-chip → shared `messaging/quote-chip.blade.php`, @include'd by this root; see §16A)

resources/js/components/alpine/data/contact.js         Alpine component (dual-island polling, selectContact, send, reply/edit, undo, mount deep-link scroll)
resources/js/core/record-focus.js                      shared global record-focus handler (type:'people' → sidebar row; type:'message' → message row)
routes/web.php                                         GET /contacts → Contact::class → name 'contact'
app/Services/Search/NavigationService.php              nav entry 'contact' (route:contact + keywords)
resources/js/components/alpine/data/menu.js            menu item {id:'contacts-controller', href:'/contacts', icon:'perm_contact_calendar'}
resources/js/components/alpine/main.js                 import + Alpine.data('contact', contact)
```

---

## 6. `Main.php` — the user-panel component

### 6.1 Properties

| Property | Contract |
|---|---|
| `MessageComposerForm $composer` | send form (body/attachments) |
| `EditMessageForm $edit` | edit form (editingBody) |
| `?int $activeUserId = null` | selected contact (the 1:1 peer) |
| `string $search = ''` | sidebar search |
| `string $filter = 'all'` | `'all'` \| `'unread'` \| `'online'` |
| `bool $mobileShowChat = false` | mobile: main pane visible |
| `#[Locked] int $editTimeLimit = 600` | edit window seconds; passed to `SaveEditAction`/`DeleteMessageAction` and the presenter's `can_edit`/`can_delete` flags (Locked = client can't widen the gate) |
| `#[Locked] ?array $lastDeleted = null` | snapshot for 4s undo (Locked = client can't tamper the restore payload) |
| `#[Locked] int $messagesLimit = 10` | growing-LIMIT window (Locked = client can't inflate `take(N+1)`; grow by 10 per `loadMoreMessages`, reset to 10 server-side) |
| `string $messageSearch = ''` | in-chat message-search query (reset on `selectContact`/`backToList`) |
| `#[Locked] ?int $focusAnchorId = null` | message-id anchor for in-chat search "focus"; `null` = recent-N mode (reset on `selectContact`/`backToList`/`send`) |
| `#[Locked] int $focusOlder = 5` | older-side window size in anchor mode (grow by 10 per `loadMoreMessages`) |
| `bool $hasOlder = false` | more older messages exist (`count > limit`) |

### 6.2 Computeds (remember §3.1 — use `$this->` in islands)
- `activeContact(): ?User` — `User::with(['profile.department', 'profile.details'])->find(activeUserId)` (null when none selected).
- `contacts(): array` — `FetchContactsAction(viewer, search, filter)` + hydrates last-message bodies via a second `whereIn('id', $messageIds)`; maps each user to `{id,name,profile,unit,section,last_message,unread_count,is_online,presence}`; `online` filter applied post-map.
- `messages(): array` — **two modes** (both +1-probe `hasOlder` — §3.3): (a) recent-N (default): `latest('id')->take(messagesLimit + 1)`, `hasOlder = count() > messagesLimit`, trim, `sortBy('id')`; (b) anchor (`focusAnchorId` set): bounded window via two indexed queries, `hasOlder = older.count() > focusOlder`, merge + `sortBy('id')`.
- `groupedMessages(): array` — `messages` grouped by `created_at->toDateString()`.
- `lastMessageId(): ?int` — `collect(messages)->max('id')` (the edit & delete gate — only the absolute last message in the thread is editable/deletable).
- `messageSearchResults(): array` — null-guards `activeUserId`, then `SearchMessagesAction(activeUserId, messageSearch, auth()->id())`.
- `presenter(): ContactPresenter` — new instance (stateless).
- `totalStaff(): int` — `User::visibleOnBoard()->count()`.

### 6.3 Lifecycle + methods (contract + do/don't)
| Method | What it does | Rule |
|---|---|---|
| `selectContact(int)` | guard: `User::getCachedAllOptions()->has($userId)` → set active, reset messagesLimit/focusAnchorId/focusOlder/composer, `invalidateMessageCache`, `resetAllStates`, `$this->markRead($userId)`, `unset(contacts)` | no membership gate (§2); silent `return` on fail; re-selecting exits anchor mode; `markRead` is `#[Async]` (resolved internally, not injected) |
| `focusRecord(int): bool` | deep-link (`FocusOnRecord` trait, `#[Url] open`) → `selectContact($userId)` (guards user exists); if `?focus_msg={id}` present, call `focusMessage(id)` and return its bool; else return false (trait then dispatches `record-focus` type:'people') | `focus_msg` read via `request()->query` (mount-only); returns false on no-focus_msg so the trait's people-focus fallback fires (sidebar row scroll) |
| `focusMessage(int): bool` | delegates to `FocusMessageAction::execute(activeUserId, id, authId, messagesLimit)` → `?bool`; `null` → return false; `false` → clear stale anchor + `invalidateMessageCache` + `dispatch('record-focus', type:'message')`; `true` → set `focusAnchorId=id`+`focusOlder=5` + `invalidateMessageCache` + dispatch | #[Locked] `focusAnchorId`/`focusOlder` — client can't tamper; `activeUserId` not Locked but anchor can't escape the active peer; reuses the global `record-focus` standard (`scrollToRecord('message-{id}')`) |
| `refreshUnread()` | `unset($this->contacts)` | called by the 10s poll (sidebar leg); cheap recompute |
| `refreshActive()` | `invalidateMessageCache()` (unsets `messages` + `lastMessageId`) | called by the 10s poll (messages leg, only when `activeUserId` set — §3.2 D1) |
| `backToList()` | full reset (mobileShowChat/active/messagesLimit/focusAnchorId/focusOlder/hasOlder/messageSearch) + `resetAllStates` | clears the messages island back to the empty state |
| `send(SendMessageAction)` | `$action->execute(composer, activeUserId)` (reads `$composer->replyToId`) → reset composer (clears `replyToId`) + clear `focusAnchorId`/`focusOlder` (exit anchor → new message visible) + `invalidateMessageCache` + `unset(contacts)` + `dispatch('message-sent')` | catch `ValidationException` (toast) + `\Exception` (generic toast + report); reply target lives in the composer form, not a method arg (Channel parity) |
| `replyTo(int)` | `$composer->replyToId = $id` + `$edit->reset()` | parent `$wire.replyTo(id)` state-setter (Channel parity); reply chip itself is Alpine-local (`replyingTo`) |
| `markRead(int) #[Async]` | `app(MarkMessagesAsReadAction)->execute($userId, auth()->id())` | `#[Async]` = fire-and-forget from the client; called internally by `selectContact` (runs sync server-side); Channel parity |
| `cancelReply() #[Js]` | returns `$wire.composer.replyToId = null` | `#[Js]` = pure client-side, zero backend round-trip; Channel parity |
| `cancelEdit() #[Js]` | returns `$wire.edit.editingBody = ''` | `#[Js]` = pure client-side; clears the wire form body (Alpine `editingMsg` cleared by JS); Channel parity |
| `setFilter(string)` | set filter | `contacts` recomputed lazily on next render |
| `saveEdit(SaveEditAction, int)` | `$action->execute(edit, editingId, editTimeLimit)` → if false toast "مهلت به پایان رسیده" | edit window 600s |
| `deleteMessage(DeleteMessageAction, int)` | `$action->execute(id, editTimeLimit)` → store `lastDeleted` snapshot + `invalidateMessageCache` + `unset(contacts)` + `dispatch('show-undo-toast')` | only the absolute last message (`id === lastMessageId`) is deletable — gated in the blade |
| `undoDelete(UndoDeleteAction)` | restore from `lastDeleted` → clear + `invalidateMessageCache` + `unset(contacts)` + `dispatch('show-toast')` | 4s window (contact.js) |
| `loadMoreMessages()` | anchor mode: `focusOlder += 10`; else `messagesLimit += 10`; then `invalidateMessageCache()` | inert default branch; grows the older side of the anchor window |
| `removeAttachment(int)` | splice `composer.attachments` + reindex | |
| `downloadAttachment(int, int)` | sender/recipient guard + path-traversal confinement → `?Response` | returns `BinaryFileResponse` or `null`; island-safe (see §13.8) |
| `render()` | `view('livewire.dashboard.contact')->layout('layouts.app')` | one-liner — the `['activeContact'=>…, 'p'=>…]` injection was dropped after §3 was clean (Phase A step 2) |
| `recordFocusType()` | returns `'people'` | `FocusOnRecord` trait hook (sidebar row is the people-focus target) |

### 6.4 Shared `ChatComposer` trait (DRY with Channel)
Six methods are provided by `App\Traits\ChatComposer` (wired via `use ChatComposer, FocusOnRecord, WithFileUploads;`), **not** defined in this class body — grep for their definitions lands in the trait, not `Main.php`:
- `updated(string $name)` — lifecycle hook; on `composer.attachments` change → `dispatch('attachments-updated')->self()`.
- `syncAttachments()` — `unset($this->groupedMessages)` (invalidates the grouped cache).
- `removeAttachment(int $index)` — splice + reindex `composer.attachments`.
- `groupedMessages(): array` `#[Computed]` — §6.2.
- `lastMessageId(): ?int` `#[Computed]` — §6.2 (the edit & delete gate).
- `cancelReply()` `#[Js]` — §6.3.

All six are byte-identical to Channel's copies; Livewire reflects trait methods (incl. `#[Computed]`/`#[Js]`) as members of the using class, so `$this->groupedMessages`, `$this->lastMessageId`, `$wire.cancelReply()`, `$wire.removeAttachment()`, `$wire.syncAttachments()`, the `attachments-updated` self-dispatch, and the `unset($this->groupedMessages)` call in `invalidateMessageCache()` all resolve through the trait unchanged. `cancelEdit()` is deliberately **not** shared (Contact clears `$wire.edit.editingBody`, Channel clears `$wire.editingMsg`).

---

## 7. Actions catalog (contracts)

| Action | Signature | Core contract |
|---|---|---|
| `FetchContactsAction` | `execute(int $viewerId, string $search, string $filter): Collection` | `UNION ALL` sent+received → per-contact `last_message_id`; `leftJoinSub` unread_count (`read_at IS NULL`); returns `User::visibleOnBoard()` (excludes `UserType::Guest`) with `profile.department`/`profile.details`; covering-index-served (perf audit 2026-06) |
| `SendMessageAction` | `execute(MessageComposerForm, int $recipientId): Message` | `DB::transaction`; `Message::create`; attachment store; reads `$form->replyToId`; `resolveReplyToId` drops it if outside this conversation (`isValidContext` — sender/recipient pair both directions) |
| `SaveEditAction` | `execute(EditMessageForm, int $messageId, int $editTimeLimit): bool` | sender-scoped (`sender_id = auth()->id()`) + 600s limit; false if window expired |
| `DeleteMessageAction` | `execute(int $messageId, int $editTimeLimit): array\|bool\|null` | sender-scoped; soft-delete; returns snapshot for undo, or `false` if not found or edit window expired (`null` is in the declared type but never returned) |
| `UndoDeleteAction` | `execute(array $lastDeleted): void` | restore-or-recreate from snapshot |
| `FocusMessageAction` | `execute(int $userId, int $messageId, int $authId, int $loadedLimit): ?bool` | `null` = invalid/no-such-message; `false` = in recent window; `true` = over limit (anchor). Over-limit check is a +1 probe (`take(loadedLimit+1).count() > loadedLimit`) |
| `MarkMessagesAsReadAction` | `execute(int $contactId, int $viewerId): int` | sets `read_at` on the contact's inbound messages to viewer; returns count |
| `SearchMessagesAction` | `execute(int $userId, string $query, int $authId): array` | in-chat MySQL full-text search (`MATCH(body) AGAINST(? IN BOOLEAN MODE)`, `+word*` per token, min 3 chars, max 64, limit 20); empty for short/empty query |
| `ForceDeleteMessageAction` | `execute(Message $message): bool\|null` | `$message->forceDelete()` (pruning; `forceDeleted` hook unlinks attachments) |

---

## 8. Forms catalog

- `MessageComposerForm`: `body` (`#[Validate('required_without:attachments|string|max:2000')]`), `attachments` (array, validated), `replyToId` (`#[Validate('nullable|exists:messages,id')]` — the reply target; `SendMessageAction::resolveReplyToId` re-scopes it to the conversation, so the form-level `exists` is a first-line guard only). `reset()` after send (clears `replyToId`). Channel parity (`ChannelMessageComposerForm::replyToId`). **Soft-deleted nuance:** Laravel's `exists` rule queries the raw table (no `SoftDeletes` scope), so a soft-deleted reply target *passes* `exists` — but `resolveReplyToId` uses `Message::withoutTrashed()`, so it's dropped to `null` and the reply link is silently not created. The double-layer is intentional; the action is the authoritative gate.
- `EditMessageForm`: `editingBody` (`#[Validate('required|string|min:1|max:2000')]`). `reset()` after save.

---

## 9. Presenter `ContactPresenter`

Stateless view-shaping only — **never queries** (all data is passed in from `Main`):

`ContactPresenter` composes `use HasAvatar, BuildsChatBubbles;` (`App\Traits\BuildsChatBubbles` — the four universal `bubbleRadius`/`attachments`/`linkify`/`replyPreview` primitives, shared with `ChannelPresenter`/`ProjectPresenter`). `messageGroup()`/`messages()` below stay **locally defined**, NOT the shared `App\Traits\BuildsMessageGroups` — see §16C.5 for why.
- `sidebar(array $contacts, int $authId): array` — per-contact view row (name/last_message/unread/presence/online/avatar).
- `contact(array $c, int $authId): array` — single contact detail row.
- `totalUnread(array $contacts): int` — sum of unread across contacts (drives `data-total-unread` → push-notify observer).
- `messageGroup(string $date, array $messages, int $authId, int $editTimeLimit, ?int $firstUnreadId = null): array` — date-grouped bubble shaping; threads the thread-level `firstUnreadId` through (added 2026-08-31).
- `messages(array $messages, int $authId, int $editTimeLimit, ?int $firstUnreadId = null): array` — per-message view row (is_mine/is_last/can_edit/can_delete/sender/avatar/reply); adds `is_new_messages` (flags exactly the row whose id === `firstUnreadId` — zero new SQL, pure array transform).
- `firstUnreadId(array $messages, int $authId): ?int` — id of the FIRST unread received message (sender ≠ viewer, `read_at` null, not soft-deleted), null when none. Called from `Main::selectContact` against the `messages` computed **before `markRead` runs** (same query the render reuses — zero extra SQL) and stored on `#[Locked] Main::$newMessagesAnchorId`, which `messages.blade.php` reads once in its header `@php` and threads into every `messageGroup(...)` — the thread-level anchor means one divider even when unread spans date groups, and it survives the async read-marking (reset to null in `backToList`). Window-relative: unreads older than the loaded window don't get the divider (accepted; the sidebar unread badge still shows them).
- `contact(array $c, int $authId): array` — single contact detail row; `last_message.body` falls back to «پیوست» when the last DM has an empty body but non-empty `attachments` (attachment-only messages previously rendered a blank sidebar preview; 2026-08-31).

---

## 10. Views — and the island-scope rule for each

Every partial reads computeds via `$this->` (or a local `$p = $this->presenter` inside its `@php` block). **Never** use a bare computed (`$activeContact`, `$contacts`, `$messages`, `$groupedMessages`, `$presenter`) — see §3.1. This table is the audit artifact proving §3.

| Partial | Opens with / scope | Uses |
|---|---|---|
| `contact.blade.php` (root) | `x-data="contact()"`, **outside** both islands | two `@island` blocks; root-level: keydown handlers, `<x-ui.modals.max-backdrop/>`, quote-chip, switch-tabs, title; messages island branches `@if($this->activeContact)` → header+messages+composer+info · `@else` → empty |
| `sidebar.blade.php` (sidebar island) | `@php($p = $this->presenter; $contactList = $p->sidebar($this->contacts, auth()->id()); $totalUnread = $p->totalUnread($this->contacts))` | `data-total-unread="{{ $totalUnread }}"` (push-notify); shared `livewire.dashboard.channel.search-field` partial (`refreshSidebarOnClose` → `$wire.$island('sidebar').refreshUnread()`); filter tabs; row `x-on:click="selectContact(id)"` + enter/space |
| `header.blade.php` (messages island) | `$this->activeContact->...` | contact name, presence, back/info/search buttons, per-chat sound mute toggle |
| `messages.blade.php` (messages island) | `@php($p = $this->presenter; $hasOlder = $this->hasOlder)` | `#msg-viewport`; date-grouped bubbles; a «پیام‌های جدید» divider (`mark_chat_unread`, tertiary-container) rendered before the one row carrying `is_new_messages` — thread-level anchor read once in this partial's header `@php` from `$this->newMessagesAnchorId` (captured pre-read in `Main::selectContact`, §9); each row `data-rf="message-{id}"` (focus target); `@empty` uses `$this->activeContact->name`; avatar gated by `is_last` (last-in-group bubble, both directions, unlike Channel's every-row); edit & delete gated by `can_edit`/`can_delete && id === $this->lastMessageId` (same absolute-last window — in sync); load-more button gated by `$wire.hasOlder`; scroll-to-top auto-fires `loadMoreMessages` |
| `composer.blade.php` (messages island) | | textarea (`msg-ta`), attachments, emoji picker, reply/edit preview |
| `info.blade.php` (messages island) | `$this->activeContact->...` | `x-show="showInfo"` side panel |
| `search.blade.php` (shared → `messaging/search.blade.php`; see §16A) | `$this->messageSearch` / `$this->messageSearchResults` | `x-show="searchMessages"` in-chat search panel |
| `empty.blade.php` (messages island) | | no-contact-selected state — rendered inside the messages island `@else` so back-to-list re-renders only the island |
| `quote-chip` (shared → `messaging/quote-chip.blade.php`, @include'd at root; see §16A) | | floating reply chip on text-selection inside `#msg-viewport`; click binds `useQuoteChip()` (in `chatBase`) |

---

## 11. `contact.js` (Alpine)

```
init()              startPolling() + visibilitychange (halt on hidden) + scroll-FAB + scroll-to-top auto-load (loadMoreMessages w/ scroll-anchoring, overflow-anchor:none) + $wire.on listeners + MOUNT deep-link scroll (D2 race-free — see §3.5)
                    mount scroll block: const focusMsg = parseInt(new URLSearchParams(window.location.search).get('focus_msg'),10)||0;
                      if (activeUserId && focusMsg <= 0) $nextTick(() => { scrollToBottom(false); if (innerWidth<768) focus #msg-ta });
                    (mount-only; clicks scroll via selectContact.then; mutually exclusive with focus_msg record-focus path)
startPolling()      setInterval(10s) → $wire.$island('sidebar').refreshUnread().catch()  AND  if ($wire.activeUserId) $wire.$island('messages').refreshActive().catch()   (D1 dual-island — see §3.2; NO wire:poll)
stopPolling()       clearInterval
destroy()           stopPolling + remove listeners (visibilitychange/scroll/selectionchange/keydown) + disconnect unread observer
syncPushNotify()    reads [data-total-unread]; if increased → $store.push.notify (MutationObserver on data-total-unread)
selectContact(id)   guard !id; clear replyingTo/editingMsg/deletingId/openActionsId/searchMessages; $wire.cancelReply() (clear stale server replyToId); $wire.$island('messages').selectContact(id).then(() => $wire.$island('sidebar').refreshUnread()).then(() => $nextTick(() => scrollToBottom(true))).then(() => { if (innerWidth<768) $nextTick(() => focus #msg-ta) })  (NO .catch)
                    (Channel-parity chained chain — the sidebar refreshUnread round-trip BUFFERS timing so messages morph before the smooth scrollToBottom(true) fires; this is the scroll-on-open fix that brought Contact identical to Channel's selectChannel. Previously fire-and-forget + scrollToBottom(false) instant — divergent, now aligned.)
sendMessage()       guard empty + >2000 char; await $wire.$island('messages').send(); this.replyingTo=null; then $wire.$island('sidebar').refreshUnread().catch(); 500ms sending lock
                    (send() takes NO replyToId arg — the reply target lives in $wire.composer.replyToId, set by startReply→$wire.replyTo(id) and cleared server-side by composer->reset() inside send; Channel parity)
startReply(id,name,body)  set Alpine replyingTo + clear editingMsg/deletingId; $wire.replyTo(id) (sets composer.replyToId); $wire.cancelEdit(); focus #msg-ta
startEdit(id,body)  clear replyingTo/deletingId; set Alpine editingMsg={id,body}; $wire.cancelReply() (clear server replyToId so a stale reply can't tag the next send); $wire.set('edit.editingBody', body) (instant content — Contact's textarea binds the WIRE form prop, unlike Channel's Alpine-local editingBody); focus textarea
cancelReply()       replyingTo=null; $wire.cancelReply() (#[Js] — pure client-side, zero round-trip)
cancelEdit()        editingMsg=null; $wire.cancelEdit() (#[Js] — pure client-side)
closeOverlays()     cancelReply() + cancelEdit() + cancelDelete() + clear showInfo/searchMessages/emojiOpen/quoteChip/openActionsId (local methods do both Alpine-clear AND server-sync)
saveEdit(id)        guard !id || id!==editingMsg?.id; await $wire.$island('messages').saveEdit(id); if (editingMsg?.id===id) cancelEdit() (race-guarded — won't wipe a new edit started mid-flight) (try/catch toast)
deleteMessage()     await $wire.$island('messages').deleteMessage(this.deletingId) (try/catch toast)
loadMoreMessages()  $wire.$island('messages').loadMoreMessages() (scroll-to-top + scroll-anchoring)
focusMessage ×2     $wire.$island('messages').focusMessage(id) (scrollToMessage in-DOM fast path fallback + focusSearchResult)
scrollToMessage(id) in-DOM fast path: querySelector([data-rf="message-{id}"]) → sweep .record-focus-flash → scrollIntoView center + flash; fallback $wire.$island('messages').focusMessage(id)
$wire.on('message-sent')  → $nextTick(scrollToBottom smooth) + sending=false + $store.sound?.playOutgoing(activeUserId,'contact')
$watch($wire.lastDeleted) → 4s undo toast window
```

All Alpine-initiated server round-trips use `$wire.$island(...)`. contact.js itself has **10 `$island(` call sites** (verified: `grep -c '\$island(' contact.js` → 10): the polling leg `refreshUnread` + `refreshActive` (2), `selectContact` + its `.then` sidebar `refreshUnread` (2), `send` + its sidebar `refreshUnread` (2), `loadMoreMessages` (1), `syncAttachments` in the `attachments-updated` `$wire.on` listener (1), `focusMessage` in `scrollToMessage`'s in-DOM-fast-path fallback (1), `saveEdit` (1). Two more live in the shared `chatBase` mixin (spread into `contact()` via `...chatBase()`): `focusSearchResult`'s `focusMessage` (1) + `deleteMessage` (1) — component total 12. The undo-delete toast's `$wire.$island('messages').undoDelete().then(() => $wire.$island('sidebar').refreshUnread())` lives in `messages.blade.php` (an `x-on:click`), NOT contact.js — counted separately. HTML directives (`wire:click`/`wire:model`) auto-scope to their containing island.

**Two deliberate bare-`$wire` exceptions (Channel parity, NOT full re-renders to worry about):**
- `#[Js]` methods `cancelReply()`/`cancelEdit()` — bare `$wire.cancelReply()` / `$wire.cancelEdit()` execute **purely client-side** (Livewire ships the JS string to the browser and runs it there); **zero backend round-trip, zero re-render**. This is the lightest possible state clear.
- `replyTo(id)` — bare `$wire.replyTo(id)` IS a parent round-trip, but it mutates only `composer.replyToId`, which **no island blade reads during render** (the reply chip is Alpine-local `replyingTo`). Livewire only re-streams islands whose rendered output changed, so in practice no island HTML is re-streamed. Channel uses the identical bare `$wire.replyTo(id)`. (Do NOT infer from this that any bare `$wire` call is safe — it is safe here only because the mutated prop is render-invisible.)

**Bare `$wire.method()` from Alpine otherwise triggers a FULL re-render** — that's why the undo toast wraps `undoDelete` in `$wire.$island('messages').undoDelete()` instead of calling `$wire.undoDelete()` bare.

---

## 12. Dos and Don'ts (consolidated hard rules)

### DO
- ✅ Reach computeds inside islands via `$this->` (`$this->activeContact`, `$this->contacts`, `$this->presenter`). Public properties can stay bare (`$activeUserId`, `$search`, `$filter`, `$hasOlder`).
- ✅ Use a **local** `$p = $this->presenter` inside a partial's `@php` block when calling presenter methods (`$p->sidebar(...)`, `$p->messageGroup(...)`).
- ✅ Poll **both** islands (sidebar `refreshUnread` always + messages `refreshActive` when a conversation is open) — D1, §3.2.
- ✅ Use `$wire.$island('messages').method()` for every Alpine-initiated server call (so it stays island-scoped, not a full re-render).
- ✅ Scope message edits/deletes by `sender_id = auth()->id()` (in `Main` and the actions).
- ✅ Reuse the shared `livewire.dashboard.channel.search-field` partial for the sidebar search — it is island-safe (plain `wire:model` + Alpine, no `@entangle`).
- ✅ Keep MySQL 5.7-safe: `unionAll`, `leftJoinSub`, covering indexes — all fine.
- ✅ No code comments (project rule). Inline Persian for partials; `lang/fa` keys only for Filament.

### DON'T
- ❌ Don't use a bare computed (`$activeContact`, `$contacts`, `$messages`, `$presenter`) inside `@island` or its `@include` partials — it throws on the refresh path (masked by `Livewire::test`).
- ❌ Don't use `@entangle` / `<x-ui.forms.search>` / `<x-ui.modals.*>` inside an island — they need `$__livewire` which nested anonymous components don't inherit.
- ❌ Don't reintroduce `chat-ready` / `dispatchReady` — it raced with `record-focus` (D2, §3.5). The mount deep-link scroll is handled race-free in `contact.js init()`.
- ❌ Don't "fix" the dual-island poll back to Channel's sidebar-only pattern — it would lose live incoming-message updates in the open conversation (D1, §3.2).
- ❌ Don't use `wire:poll` — polling is JS `setInterval` + `visibilitychange` (pauseable, island-scoped).
- ❌ Don't call `$wire.method()` bare from an Alpine `x-on:click` — it triggers a full re-render. Wrap with `$wire.$island('<island>').method()`.
- ❌ Don't trust `Livewire::test()->html()` alone for island code — it does a full render and masks island-only scope bugs. Add a real island-refresh check (see §14).
- ❌ Don't add `HasMenuState` / a bare-key badge / a nudge row to `notifications` — unread is surfaced only via `data-total-unread` + the push-notify observer; a nudge row pollutes the Filament bell.
- ❌ Don't erase/truncate `fateh` DB data without explicit session approval; benchmark only in `perf_benchmark` DB.

---

## 13. Known edge cases / gotchas

1. **Bare `$activeContact` / `$p` in islands** — fixed (Phase B). All island partials use `$this->activeContact` / local `$p = $this->presenter`. The `render()` injection was dropped only after this was clean (Phase A step 2). See §10 for the audit table.
2. **`chat-ready` scroll race** — fixed (D2). `chat-ready` removed; mount deep-link scroll is race-free in `contact.js init()` (mutually exclusive with the `focus_msg` path; different scroll container from `record-focus` `type:'people'`). See §3.5.
3. **`@entangle`/`<x-ui.forms.search>` in sidebar island** — fixed by reusing Channel's shared `search-field` partial (plain `wire:model` + Alpine, no `@entangle`).
4. **Sender deleted** — `sender_id`/`recipient_id` are `onDelete('cascade')` (a deleted user's messages are removed, not orphaned); `reply_to_id` is `onDelete('set null')`. The presenter's `ناشناس` sender fallback is defensive — unreachable under cascade.
5. **Edit window** — 600s is the `Main::$editTimeLimit` default, passed to `SaveEditAction`/`DeleteMessageAction` and the presenter's `can_edit`/`can_delete` flags; one source, no separate constant to keep in sync.
6. **Undo after force-delete** — `UndoDeleteAction` falls back to recreating from the snapshot if the soft-deleted row was already force-deleted.
7. **Delete gate** — only the absolute last message in the thread is deletable (`can_delete && id === $this->lastMessageId`); edit is gated identically by `can_edit && id === $this->lastMessageId` (same absolute-last window — edit/delete in sync).
8. **`downloadAttachment` from inside the island** — returns a `BinaryFileResponse` (non-standard Livewire response); file downloads bypass the DOM-morph response entirely, so island scoping is irrelevant. Manually verify (plan §6).
9. **`refreshUnread`/`refreshActive` `unset()` is redundant-but-harmless** — Livewire `#[Computed]` cache is per-request-only; `->call()` re-hydrates a fresh instance with an empty cache, so the round-trip itself surfaces new DB rows. The `unset()` is belt-and-suspenders (matches Channel). PHPUnit verifies the observable contract (a refresh round-trip surfaces new rows), not the invalidation mechanic — the real island-poll verification is the §14 browser pass.
10. **Sidebar pagination (`$contactsLimit`) slices the Blade render, NOT `contacts()`'s return value.** `Main::contacts()` still returns every matching row (unchanged shape/contract — `refreshUnread`, `totalUnread`, `allContactIds` for the mute-all button, and the Presenter all still need the FULL list). `sidebar.blade.php` computes `$visibleContactList = array_slice($contactList, 0, $contactsLimit)` and only the `@forelse` loop reads it; the `<x-ui.buttons.load-more action="loadMoreContacts">` button lives inside the same `sidebar` island as everything else — no new island, no `$wire.$island()` call, plain `wire:click` auto-scopes. `contactsLimit` (default 30, `+30` per click) resets to 30 in `updatedSearch()` and `setFilter()` so a new search/filter always starts from page one. Matches Channel's identical `$channelsLimit` addition and Project's `$projectsLimit` (`project/sidebar.blade.php`) — same mechanism, three modules, deliberately NOT SQL-`LIMIT`-based (would require splitting the shared list query and isn't justified at current per-user list sizes; see `livewirePattern.md`'s Project section for the full trade-off writeup). Test: `test_sidebar_load_more_visibility_matches_whether_the_list_exceeds_the_page_size` asserts the `hasMore`-equivalent invariant against the *actual* live count rather than a hardcoded number, since `visibleOnBoard()` is org-wide and a shared dev DB's pre-existing active users make an absolute small-count assumption unreliable.

---

## 14. Verification commands (use these, not just `Livewire::test`)

`Livewire::test()->html()` does a **full render** and masks island-scope bugs. For island code, also verify the island-refresh path:

```bash
# clear compiled views after any blade edit (stale compiled views cause phantom errors)
php artisan view:clear

# full render (catches syntax + full-scope errors only)
php artisan tinker --execute "\Illuminate\Support\Facades\Auth::login(\App\Models\User::first()); \$t=\Livewire\Livewire::test(\App\Livewire\Dashboard\Contact\Main::class); echo strlen(\$t->html());"

# PHPUnit floor (ContactTest — 41 passed, 1 skipped pre-existing)
php artisan test --filter=ContactTest

# grep audit (§3 clean — run after any blade/js edit):
#   bare $activeContact (none expected):
grep -rn --include='*.blade.php' -E '(?<!\$this->)(?<!->)\$activeContact' resources/views/livewire/dashboard/contact/ resources/views/livewire/dashboard/contact.blade.php
#   wire:poll / chat-ready (none expected):
grep -rn --include='*.blade.php' -E 'wire:poll|chat-ready' resources/views/livewire/dashboard/contact*
#   $island call sites in contact.js (expect 10; +2 more in chatBase.js = 12 component-total):
grep -c '\$island(' resources/js/components/alpine/data/contact.js

# real browser check (REQUIRED for island code): open /contacts, click a contact,
# watch the Network tab for the livewire/update call carrying the island-scoped method
# (e.g. $island('messages').selectContact) and the console for "Undefined variable".
```

**For any future island edit, the mandatory verification is the real browser flow** (click contact → watch the island-refresh request + console). `Livewire::test` is necessary but not sufficient.

### §5 manual browser pass (the user runs this — the final arbiter)
Load `/contacts` cold · click a contact (the exact path that exposed Channel's bare-var bug) · send · edit/save/cancel · delete+undo · in-chat search → result scroll+flash · global-search deep link `?open={id}&focus_msg={id}` (scroll to the message, not the bottom) · global-search deep link `?open={id}` (scroll to bottom — D2 race-free restore) · load-more + scroll-to-top auto-trigger · switch contact then back-to-list (empty state inside the messages island) · wait one 10s poll tick with a conversation open (sidebar unread + open-thread live update — D1) · switch tabs away/back (polling pauses/resumes) · attach/remove/send-attachment-only · reply/cancel/reply-send · mobile viewport (sidebar toggle + `#msg-ta` focus after select) · emoji/copy/quote-chip · per-contact mute + mute-all + push toggle.

---

## 15. Pointers

- Migration plan (the historical record of *why* the architecture looks the way it does): the original `.claude/plans/contact-island-migration-plan.md` is no longer present; the surviving record is this doc (Phases A–E, decisions D1–D4, §3 bare-var audit, §5 testing, §6 edge cases, §7 rollback, §8 DoD — retained inline above).
- Livewire conventions: `app/Livewire/livewirePattern.md` — canonical `@island` rules.
- Filament conventions: `app/Filament/filamentPattern.md`.
- Sibling module to mirror: `app/Livewire/Dashboard/Channel/` (broadcast) — `channelPattern.md` is the structural twin. Divergences are enumerated in §16.
- Memory: `project_perf_audit_2026_06` (FetchContactsAction UNION ALL + covering indexes, 1103ms→8.8ms).

---

## 16. Channel↔Contact alignment audit (the full identical / similar / divergent map)

The 2026-08 alignment pass made Contact mirror Channel's `@island` architecture 1:1 everywhere functionality allows. This section is the authoritative diff map — read it before "fixing" a Contact/Channel asymmetry, because some divergences are deliberate (§16C).

### 16A. Identical (byte-for-byte or structurally same)

- **Two-island layout** — `@island('sidebar')` + `@island('messages')` in the root view; `#[Isolate]` on the class.
- **`#[Computed]` + `$this->`-in-islands rule** — computeds reached via `$this->`, local `$p = $this->presenter` in partials; bare computeds fatal on refresh (§3.1).
- **Shared `channel/search-field` partial** — sidebar search reuses Channel's partial verbatim; Alpine-state contract `{model}Fullscreen`/`{model}Value` declared in both `contact()` and `channel()`.
- **`+1`-probe `hasOlder` pagination** — `take(N+1) → count() > N → take(N)`; recent-N + anchor-window modes (§3.3).
- **`focusMessage` 3-way `?bool`** + global `record-focus` standard; `FocusOnRecord` trait + `focus_msg` deep-link.
- **`#[Async] markRead`** — fire-and-forget read-marking on select (Channel: `markRead`; Contact: `markRead`).
- **`#[Js] cancelReply` / `cancelEdit`** — pure client-side state clear, zero backend round-trip.
- **Reply flow** — `composer.replyToId` form prop (Channel: `ChannelMessageComposerForm::replyToId`); `replyTo(int)` parent setter; `SendMessageAction::resolveReplyToId` scopes the reply target to the conversation (drops out-of-conversation IDs); reply chip is Alpine-local; `send()` reads the form, takes no replyToId arg; `composer->reset()` clears `replyToId` after send.
- **State-setter vs data-mutation call discipline** — state-setters (`replyTo`/`cancelReply`/`cancelEdit`) use bare parent `$wire.method()`; data mutations needing a re-render (`send`/`saveEdit`/`deleteMessage`/`focusMessage`/`loadMoreMessages`/`selectContact`) use `$wire.$island('messages').method()`.
- **Alpine local-state counter** — composer char counter uses `x-data="{ len: 0 }" x-effect="len = ($wire.composer.body||'').length"` + `x-on:input="len = $el.value.length"` (no `x-ref`/`$refs` — dead `x-ref` on morphed islands throws `Cannot read properties of undefined (reading '_x_refs')`).
- **No `x-ref` on morphed islands** — `#msg-viewport` / `#msg-ta` are `getElementById` lookups, not `$refs`.
- **`closeOverlays`** — calls local `cancelReply()`/`cancelEdit()`/`cancelDelete()` (each does Alpine-clear + server-sync).
- **Sender-scoped edit/delete guards** + 600s edit window; undo-delete snapshot; attachment path-traversal confinement; `downloadAttachment` non-standard response.
- **Polling shape** — JS `setInterval` + `visibilitychange` (no `wire:poll`); `refreshUnread`/`refreshActive` `unset()` belt-and-suspenders.
- **No nudge/badge/`HasMenuState`** — unread via `data-total-unread` + push-notify observer only.
- **Shared messaging partials (`livewire/dashboard/messaging/`)** — `legends` (badge+feature legend modal), `header-actions` (4-button cluster + per-conversation sound-mute `@slot`), `empty-state` (empty shell + stats slot), and `quote-chip` (one blade, `useQuoteChip()` click) are centralized in one folder and `@include`/`@component`-rendered, not `<x-*>` (not used elsewhere → don't live in `components/`). `quote-chip` shares only after a JS alignment: `useQuoteChip()` is folded into `chatBase` (both inherit via `...chatBase()`) and Channel's `startReply` hides the chip (Contact's already did) — so both blades bind the identical `useQuoteChip()`. The in-chat message-search overlay (`search`) is shared too — both headers `@include` it with a `$placeholder`/`$overlayTitle` pair (the only two strings that differed). Three per-`$msg` blocks inside `messages.blade.php` are also shared via `@include(['msg' => $msg])`: `reply-quote` (reply preview, byte-identical), `message-attachments` (image/file list, byte-identical), and `message-actions` (hover copy/reply/edit/delete toolbar — param-free like the other two: the edit-button guard is inlined as `can_edit && $msg['id'] === $this->lastMessageId`, matching the delete gate `can_delete && $msg['id'] === $this->lastMessageId`; both absolute-last, in sync). Canonical rule + `@component`/`Htmlable`-slot rationale: `channelPattern.md` §10.1.

### 16B. Similar (same intent, shape differs by 1:1-data-model necessity)

- **Sidebar population** — Channel: your memberships (`whereHas channel_members`); Contact: all colleague-visible users (`User::visibleOnBoard()`, no membership gate — §2). Both use `FetchContactsAction`/`FetchChannelsAction` with `UNION ALL` + `leftJoinSub` + covering indexes.
- **Select guard** — Channel: membership exists; Contact: `User::getCachedAllOptions()->has($id)`. Both silent-`return` on fail (not 403).
- **Polling legs** — both poll sidebar `refreshUnread` always; Contact ADDS `messages.refreshActive` when a conversation is open (D1, §3.2) because a 1:1 open thread must reflect incoming messages live. Channel's open pane re-renders on interaction only. Do NOT "fix" Contact back to sidebar-only.
- **Edit & delete gates (aligned 2026-08-04)** — both modules identical: edit `can_edit && id === lastMessageId`, delete `can_delete && id === lastMessageId` (absolute last message only; edit/delete share the same window — in sync).
- **Avatar** — Contact: avatar on the last-in-group bubble (both directions); Channel: every row.

### 16C. Divergent (deliberate, do NOT align — functionality-first)

1. **Edit-flow state model** — Channel: 4-field Alpine-local edit state (`isEditing`/`editingMsgId`/`editingBody`/`editingOriginal`); the edit textarea binds Alpine `editingBody`, and `startEdit` calls `$wire.editMessage(id)` (server-authoritative reload, single round-trip, instant because the textarea is Alpine-local). Contact: `editingMsg={id,body}` (Alpine, toggle-only) + the WIRE form prop `edit.editingBody` (the textarea binds `wire:model.live="edit.editingBody"`); `startEdit` sets `$wire.set('edit.editingBody', body)` for instant content and **does NOT call `$wire.editMessage(id)`**. Reason: Contact's textarea is wire-bound, so a server `editMessage` reload would flash the textarea empty until the round-trip resolves (a real UX regression), and `SaveEditAction` already validates sender-scoped — so `editMessage` would add a redundant second round-trip. Contact's path is one round-trip, instant, equally secure. This is the single structural divergence retained for functionality; everything else is 1:1.
2. **No membership gate / no create-or-browse / no channel type / no owner / no join-leave** — Contact is 1:1, every active user reachable (§2).
3. **Dual-island poll** — §3.2 (D1).
4. **`chat-ready` removed** — §3.5 (D2); Channel never had it.
5. **`BuildsChatBubbles` composition, not `BuildsMessageGroups`** — `ContactPresenter` composes the shared `App\Traits\BuildsChatBubbles` (§9), but keeps its own local `messageGroup()`/`messages()` rather than the shared `App\Traits\BuildsMessageGroups` that `ChannelPresenter`/`ProjectPresenter` compose. Contact's read model is `read_at`-based (a per-message boolean), not Channel/Project's per-member cursor + `readersMap`/`readerSummary` shape, and Contact has no `@mention` feature — the shared group-shaping pipeline doesn't fit and shouldn't be forced to (full rationale in `livewirePattern.md`'s "Shared chat-bubble/message-group traits" section). Composing `BuildsChatBubbles` also fixed a previously-dead feature: its `replyPreview()` includes `reply_to.id` (the replied-to message's id), which `messages.blade.php`'s reply-preview block already reads for its click-to-jump `scrollToMessage(id)` handler but, under the old locally-duplicated bubble code, never received correctly — the click-to-jump now works.

### 16D. Why the `#[Async]` / `#[Js]` / island patterns are lighter on the backend

- **`#[Js]` (`cancelReply`/`cancelEdit`)** — the method body is a JS string shipped to the browser and executed there; **no HTTP request, no re-render, no DB**. The client runs one `$wire.composer.replyToId = null` setter. Pure client load — a single property assignment — negligible.
- **`#[Async]` (`markRead`)** — from the browser, Livewire fires the call **without awaiting** it and **without re-rendering**; the server runs the action and returns nothing to re-stream. The user's next action isn't blocked on the read-marking query. (When called internally from `selectContact` server-side, it runs synchronously — same query, no extra cost.)
- **`$wire.$island('name').method()`** — only the named island's HTML is re-computed and streamed, not the whole component. `selectContact`/`send`/`saveEdit`/`loadMoreMessages` re-render just `messages` (or `sidebar` for `refreshUnread`), so the sidebar list isn't re-queried when you send a message, and vice versa.
- **`#[Computed]` + `unset()`** — computed caches are per-request; `unset()` marks them stale so the next read re-queries only what changed, once.

Net: the backend does **less per interaction** (smaller diffs, fewer full re-renders, fire-and-forget writes) and the client does a few extra one-line JS setters — a favorable trade, not a load shift onto the client.