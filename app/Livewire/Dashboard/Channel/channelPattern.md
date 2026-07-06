# Channels Module Pattern Guide

Slack/Telegram-style **one-to-many broadcast channels**, fully isolated from the 1:1 `contacts` chat. One channel → many members → messages broadcast to all members. Built with Livewire v4.1 islands, Filament v5 admin, Laravel 12, PHP 8.2. MySQL 5.7-compatible baseline (production 8+).

This doc is the **single source of truth** for every file, contract, rule, and gotcha in the module. Read it before touching anything channel-related. The canonical **general** rules for `@island` scope/memoization, the `syncAttachments` upload-bridge, and the `lastIdForChannel` null-guard live in `app/Livewire/livewirePattern.md`; this doc covers the Channels-specific architecture, contracts, and the action-extraction / perf patterns specific to this module (with the concrete island instance retained in §3.1 as the lesson that motivated those general rules).

---

## 1. Stack & compatibility (verified)

- **Livewire `^4.1`** (NOT v3) — `@island`, `#[Async]`, `#[Js]`, `#[Computed]`, `#[Locked]`, Livewire `Form` objects, `$wire.$island('name').$refresh()`.
- **Filament `v5`** — `Filament\Schemas\Schema`, `FilamentHeaderActions`, `FilamentFilters`, `AuthorizesByPermission` traits.
- **Laravel 12.55.1**, **PHP 8.2.16** (NO property hooks — `ContentSanitizerService` mutator on `ChannelMessage::body()` stays).
- **MySQL**: dev 5.8 / prod 8+. No `JSON_TABLE`, no window functions, no enforced `CHECK`. `ROW_FORMAT=DYNAMIC`, composite/covering indexes, `leftJoinSub`, `unionAll`, `orderByRaw`, `insertOrIgnore`, `nullOnDelete`/`cascadeOnDelete` are all used and safe.
- `users.id` = `bigIncrements` → all FKs are `unsignedBigInteger`.

---

## 2. The one invariant that drives everything

> **A user can only see/open a channel if a `channel_members` row exists for them.**

Everything flows from this:

- **Sidebar list** = your memberships (`FetchChannelsAction` joins `channel_members` on `user_id = viewer`).
- **`selectChannel`** guards on membership — no row, no open (silent `return`, not a 403).
- **Unread** = messages with `id > your last_read_message_id` (index-only COUNT via covering index; no delta arithmetic).
- **`markRead`** advances `last_read_message_id` **forward-only** (#[Async], fire-and-forget).
- **Owner auto-membership** = the `Channel::created` boot hook + `CreateChannelAction::insertOrIgnore`. This is the **single source of truth** for "the owner can see their own channel" across **every** creation path (Filament admin, user-panel, future seeder). Without it, Filament-created channels were invisible to their owner — that was the original gap.

---

## 3. Architecture — the rules that matter

### 3.1 Islands (the critical lesson — read twice)

Two islands: `@island(name:'sidebar')` and `@island(name:'messages')`. Islands exist to avoid child-component snapshot/prop overhead — they re-render a scoped subtree natively instead of remounting a nested component.

**Island scope rule (hard-won):** on an **island-scoped method call** (e.g. `$wire.$island('messages').selectChannel(id)`, which re-renders the messages island natively after `selectChannel` — no manual `$refresh()`), the island re-renders with a **reduced scope**:
- ✅ Available: public properties (as bare vars: `$activeChannelId`, `$browseMode`, `$createMode`, `$mobileShowChat`, `$filter`, `$search`, `$loadedLimit`, `$hasOlder`) **and** `$this` (the component instance) **and** `$__livewire`.
- ❌ NOT available as bare vars: `#[Computed]` properties (`$activeChannel`, `$channels`, `$messages`, `$joinableChannels`, `$groupedMessages`, `$presenter`).

**Therefore:** inside islands and their `@include` partials, **always** reach computeds via `$this->` (e.g. `$this->activeChannel`, `$this->channels`, `$this->presenter`), never the bare variable. `sidebar.blade.php` already does this (`$p = $this->presenter; $p->sidebar($this->channels, auth()->id())`) — copy that pattern. A bare `$activeChannel` in an island partial throws "Undefined variable $activeChannel" on the refresh path. `Livewire::test()->html()` does a **full render** that shares scope globally and **masks this bug** — only the real browser refresh exposes it. This bit us three times (search `@entangle`, `markRead` null, bare `$activeChannel`). Do not repeat it.

**Nested anonymous Blade components (`<x-...>`) inside an island do NOT inherit `$__livewire`.** So `@entangle($model).live` (used by `<x-ui.forms.search>`) fatals inside an island with "Undefined variable $__livewire". Use plain `wire:model` + Alpine for in-island inputs. `wire:model`/`wire:click`/Alpine `$wire` work fine (HTML attributes / Alpine scope, no Blade-scope `$__livewire` needed).

### 3.2 Unread (index-only, no delta)

`idx_channel_messages_covering (channel_id, deleted_at, id)` — the unread subquery counts messages `id > COALESCE(last_read_message_id, 0)` with `deleted_at IS NULL`, fully served by the covering index. No `last_read_message_id` arithmetic on the message side; the cursor is forward-only.

### 3.3 Growing-LIMIT prepend + the +1-probe `hasOlder` (no cursor, no array, no unbounded COUNT)

`Main::messages()` has two modes, **both** using the same `take(N + 1) → count() > N → take(N)` +1-probe idiom so `hasOlder` costs zero extra queries beyond the rows already fetched:

- **Recent-N** (`focusAnchorId = null`): ONE indexed query `latest('id')->take(loadedLimit + 1)`, keep `loadedLimit`, `hasOlder = count() > loadedLimit`, `sortBy('id')` so oldest-first (recent stay at bottom).
- **Anchor** (`focusAnchorId` set by `focusMessage`): TWO indexed queries — `where('id','>=',anchor)->oldest('id')->take(6)` (anchor + up to 5 newer) + `where('id','<',anchor)->latest('id')->take(focusOlder + 1)`; `hasOlder = older.count() > focusOlder`; trim older to `focusOlder`, merge, `sortBy('id')`. (Was 3 queries — a separate `exists()` for `hasOlder` plus an `$oldestId` lookup; the +1 probe collapsed it to 2.)

`loadOlder()` is two lines — anchor mode: `focusOlder += 10`; else `loadedLimit += 10`; then `unset($this->messages)` — the same query/queries now fetch the grown window, so older rows auto-**prepend** above recent in one indexed backward scan of `idx_channel_messages_covering (channel_id, deleted_at, id)`. No client array, no cap; mutations (`send`/`deleteMessage`/`saveEdit`/`undoDelete`) stay byte-identical — their existing `unset($this->messages)` re-queries the full grown window with the change applied (no seam). Snapshot holds an `int`, not an array. `#[Computed] messages` caches per-request; `unset()` invalidates. The load-more affordance is the shared `<x-ui.buttons.load-more action="loadOlder">` (matches contact/FAQ/Report), gated by `x-show="$wire.hasOlder" x-transition` so it fades out on the last round; `channel.js` also auto-fires `loadOlder` on scroll-to-top with scroll-anchoring (`overflow-anchor:none` + `scrollTop += delta`).

**Why `take(N + 1)->count()` and not `COUNT(*)` or a separate `exists()`:** the `hasOlder` flag is a *threshold* question ("are there more than N?"), not a count question. `take(N + 1)` caps the index scan at N+1 rows — once the DB has produced N+1 it can stop, because the answer is already "yes". An unbounded `COUNT(*) WHERE id >= anchor` (or `WHERE id < anchor`) range-scans the whole tail/head of a high-traffic channel just to return a number the caller only compares to N. A separate `exists()` query adds a round-trip. The +1 probe is strictly cheaper than both and behavior-identical. This is the same idiom `FocusChannelMessageAction` uses (§7) — `take($loadedLimit + 1)->pluck('id')->count() > $loadedLimit` to decide whether the focus target sits beyond the loaded window.

### 3.4 Polling (Alpine, not wire:poll)

`channel.js` `setInterval(10s)` → `$wire.$island('sidebar').refreshUnread()`. Hard-halts on `document.hidden` (visibilitychange). **No `wire:poll`** — explicit interval + visibility gate is the project rule.

### 3.5 Pruning (routed through actions, not bare Prunable)

`Channel` and `ChannelMessage` use `Prunable` + `HasPrunableStatus`, but `prune()` delegates to `ForceDeleteChannel*Action` (which calls `forceDelete()`), and the `forceDeleted` model hooks unlink storage. `channel:prune` runs hourly via `routes/console.php`. Soft-deleted >30 days → force-deleted.

### 3.6 What this module deliberately does NOT have

- **No `HasMenuState`** — channels are not wired into the menu-state/badge/nudge pipeline.
- **No nudge, no badge** — project rule (users desensitized).
- **No cache layer** — computeds + DB only.
- **No nested Livewire component** — single `Main` + islands only.
- **No persistent invite/badge/nudge** — owner can now sync members (add/remove) from the user-panel info pane **and** Filament admin (`SyncChannelMembersAction`), but the "you were added" signal is a **one-time ephemeral client-side toast** seeded from `localStorage['channel-seen-invites']` (the membership row IS the signal) — no server nudge, no bell, no dot (§3.7). Open channels remain self-joinable via browse.
- **No DB unique index on `slug`** — uniqueness enforced in the form layer (`Rule::unique('channels','slug')->whereNull('deleted_at')`) so soft-deleted slugs can be reused.

### 3.7 Owner-managed members + ephemeral invite toast (Option B)

The minimal invite flow: the **owner** adds/removes members (the membership row is the signal), and the recipient gets a **one-time, client-side-only** "you were added to X" toast on their next `/channels` visit. No server nudge, no bell, no dot, no new column, no migration.

- **Server side (`SyncChannelMembersAction`, §7)** — owner-scoped diff-sync; empty input is a no-op (no purge); owner protected both directions. Called from `Main::saveManageMembers` (owner re-checked via `channel->owner_id === auth()->id()`), opened from `Main::openManageMembers` (owner-checked). Both Open and Private channels — the toggle now lives in `create.blade.php` (two-button `$wire.set('create.type', …)`), and `createChannel()` no longer forces `type='open'` (the form value is respected; `openCreate()` only sets the default selection).
- **`memberCandidates` #[Computed]** — `User::getCachedActiveOptions()->except(auth()->id())` → `[id => name]` mapped to `[{id, name}]`. `getCachedActiveOptions` is the `once(Cache::remember('user_active_options', hour, …active()->orderBy('name')->pluck('name','id')))` one-query cache; `->except(auth()->id())` drops the owner from their own picker. Unset in `openManageMembers`/`saveManageMembers` so it refreshes.
- **Modal** — `manage-members.blade.php` clones the `share.blade.php` shape (`<x-ui.modals.action wire:model="isManageMembersOpen" action="saveManageMembers">` + checkbox list `wire:model="memberRecipientIds"` over `$this->memberCandidates`). Included **inside the `messages` island** (after the view conditional) so it re-renders when `openManageMembers` fires via `$wire.$island('messages').openManageMembers(id)`. Owner-only button in `info.blade.php` (`$isOwner = activeChannel && owner_id !== null && auth()->check() && (int)owner_id === (int)auth()->id()`).
- **Ephemeral toast (`channel.js`)** — `inviteToasts: []`, `seenChannelIds: []`, `_firstVisit`. On `init`, `localStorage['channel-seen-invites']` missing → `_firstVisit=true` (seed, no toasts); present → load seen. `syncInviteToasts()` reads `[data-channel-id]`/`[data-channel-name]` from the sidebar DOM: first-visit seeds `seenChannelIds = current` (so pre-existing channels never toast); later, any `current` id **not** in `seen` and not already toasted → push `{id,name}`. Runs on init (`$nextTick`) and after every poll `refreshUnread`. **Self-actions mark seen to suppress false positives:** `joinChannel`/`createChannel` call `markSeen(id)` **inside the `.then()` chain after success** (not synchronously — see the gotcha below); `leaveChannel` **removes** the id from seen (so a later re-add re-toasts). `acceptInvite(id)` = `markSeen` (dismiss); `declineInvite(id)` = dismiss + call `leaveChannel` on the wire (no confirm — the toast's leave is explicit). The toast container is in the root `channel.blade.php` (Alpine `x-for` over `inviteToasts`, `x-show="inviteToasts.length"`).
- **Admin (`ChannelMembersRelationManager`)** — `members()` is HasMany (not belongsToMany), so Filament `AttachAction` won't fit; a header `Action::make('addMember')` with a `Select::make('user_id')` form (options = `User::getCachedActiveOptions()->except(existing member ids)`) creates the `ChannelMember` row via `insertOrIgnore`; a record `Action::make('remove')` deletes, **hidden for the owner row** (`user_id === owner_id`) so the channel can't be orphaned.

**Gotcha (hook-caught):** `joinChannel` originally called `markSeen(id)` *synchronously before* the async `$wire…joinChannel()` resolved — if the join failed, the toast was already gone and the id persisted as seen (user silently loses the invite). Fix: `markSeen` runs **inside `.then()` after success**, mirroring `createChannel` which marks `this.$wire.activeChannelId` seen only after `refreshUnread`. Both chains now end with `.catch(()=>{})`.

---

## 4. Data model (3 tables)

### `channels` (`2026_07_05_000001`)
```
id (PK, bigIncrements)
name        string(100)
slug        string(120)            -- indexed, NOT unique-DB (form-layer unique)
description string(500) nullable
type        string(20) default('open')   -- 'open'|'private' (ChannelType enum)
owner_id    unsignedBigInteger nullable  -- FK users, nullOnDelete
timestamps, softDeletes
indexes: slug, owner_id, deleted_at
```
Model: `App\Models\Channel` — `SoftDeletes, Prunable, HasPrunableStatus, HasFactory`. Fillable: `name,slug,description,type,owner_id`. Casts: `type=>ChannelType, deleted_at=>datetime`. Relations: `owner` (User), `members` (HasMany ChannelMember), `messages` (HasMany ChannelMessage). `booted()`: `static::created` → `ChannelMember::insertOrIgnore(owner)`; `static::forceDeleted` → `Storage::disk('public')->deleteDirectory("channel_messages/{$id}")`. `prunable()` = soft-deleted >30 days; `prune()` → `ForceDeleteChannelAction`.

### `channel_messages` (`2026_07_05_000002`)
```
id (PK)
channel_id  unsignedBigInteger  -- FK channels cascadeOnDelete
sender_id   unsignedBigInteger nullable -- FK users nullOnDelete (message survives sender deletion, sender becomes "ناشناس")
body        text
attachments json nullable
reply_to_id unsignedBigInteger nullable -- FK self nullOnDelete
is_edited   boolean default false
timestamps, softDeletes
indexes: idx_channel_messages_covering (channel_id, deleted_at, id), reply_to_id
```
Model: `App\Models\ChannelMessage` — `SoftDeletes, Prunable, HasPrunableStatus, HasFactory`. `const PRUNE_DAYS = 30`. Fillable: `channel_id,sender_id,body,attachments,reply_to_id,is_edited`. Casts: `attachments=>array, is_edited=>boolean, deleted_at=>datetime`. Relations: `channel, sender` (User sender_id), `replyTo` (self reply_to_id), `replies` (HasMany self). `body(): Attribute` setter → `ContentSanitizerService::clean($value)` (NO property hooks — keep the mutator). `attachmentUrls()` maps `Storage::url` over attachments. **`lastIdForChannel(int $channelId): ?int`** = `withoutTrashed()->where('channel_id', $channelId)->max('id')` — returns **null when the channel has no messages** (every caller MUST null-guard). `booted()`: `static::forceDeleted` → `Storage::disk('public')->deleteDirectory("channel_messages/{$channel_id}/{$id}")`.

### `channel_members` (`2026_07_05_000003`)
```
user_id              unsignedBigInteger  -- composite PK (user_id FIRST)
channel_id           unsignedBigInteger  -- composite PK
last_read_message_id unsignedBigInteger nullable -- FK channel_messages nullOnDelete
joined_at            timestamp useCurrent
timestamps
primary (user_id, channel_id)  -- composite, user_id leading (hot paths WHERE user_id=? on clustered PK)
index (channel_id)             -- covered by FK auto-index too
FKs: channel_id cascadeOnDelete, user_id cascadeOnDelete, last_read_message_id nullOnDelete
```
Model: `App\Models\ChannelMember` — `HasFactory` only (**no SoftDeletes** — a member row survives channel soft-delete because the FK is `cascadeOnDelete` = hard-delete only). `$incrementing = false`. `$primaryKey = ['user_id','channel_id']`. Fillable: `channel_id,user_id,last_read_message_id,joined_at`. Relations: `channel, user, lastReadMessage` (ChannelMessage).

### Enum `App\Enums\ChannelType: string` (implements `HasColor, HasIcon, HasLabel`)
- `Open = 'open'` → label `عمومی`, color `success`, icon `heroicon-o-lock-open`
- `Private = 'private'` → label `خصوصی`, color `warning`, icon `heroicon-o-lock-closed`

---

## 5. File map (every file, one-line purpose)

```
app/Enums/ChannelType.php                              enum: Open/Private + label/icon/color
app/Models/Channel.php                                  model + created/forceDeleted boots + prunable
app/Models/ChannelMessage.php                           model + body sanitizer + lastIdForChannel + forceDeleted unlink
app/Models/ChannelMember.php                            composite-PK pivot, no SoftDeletes, $incrementing=false

app/Livewire/Dashboard/Channel/
├── Main.php                                            THE Livewire component (user panel)
├── Forms/
│   ├── ChannelMessageComposerForm.php                  send form (body/attachments/replyToId) + inline #[Validate]
│   ├── EditChannelMessageForm.php                      edit form (editingBody) + inline #[Validate]
│   └── CreateChannelForm.php                           create form (name/slug/description/type) + rules() + Persian messages()
├── Actions/
│   ├── FetchChannelsAction.php                         sidebar list (membership + last_msg + unread subqueries)
│   ├── FetchJoinableChannelsAction.php                 browse list (Open type, not-already-member)
│   ├── SendChannelMessageAction.php                    send + attachment store + member cursor advance
│   ├── SaveEditChannelMessageAction.php                edit (sender-scoped + 300s limit)
│   ├── DeleteChannelMessageAction.php                  soft-delete, returns snapshot for undo
│   ├── UndoDeleteChannelMessageAction.php              restore-or-recreate from snapshot
│   ├── JoinChannelAction.php                           insertOrIgnore member (Open-only, abort_unless 403)
│   ├── LeaveChannelAction.php                          delete member row
│   ├── CreateChannelAction.php                         create channel + owner member (auth()->id()); resolveSlug() before validate
│   ├── FocusChannelMessageAction.php                   focusMessage guard — membership + in-window check (capped +1 probe, ?bool 3-way)
│   ├── DownloadChannelAttachmentAction.php             downloadAttachment guard — membership + path-traversal confinement, BinaryFileResponse
│   ├── MarkChannelReadAction.php                       forward-only cursor UPDATE (null-guard for empty channel)
│   ├── ForceDeleteChannelAction.php                    $channel->forceDelete() (hooks unlink files)
│   └── ForceDeleteChannelMessageAction.php             $message->forceDelete() (hooks unlink files)
├── Presentation/ChannelPresenter.php                   all view-shaping (sidebar/channelHeader/messages/...)
└── (channelPattern.md — this file)

app/Console/Commands/PruneChannels.php                 `channel:prune` (hourly, chunked force-delete >30d)
app/Filament/Resources/ChannelResource.php              admin resource (List/Create/Edit + 2 RMs + export)
app/Filament/Resources/ChannelResource/Pages/{ListChannels,CreateChannel,EditChannel}.php
app/Filament/Resources/ChannelResource/RelationManagers/{ChannelMessages,ChannelMembers}RelationManager.php
app/Filament/Resources/ChannelResource/Schemas/{ChannelFormPresenter,ChannelInfolistPresenter,ChannelTablePresenter}.php
app/Filament/Resources/ChannelResource/Exports/ChannelExporter.php

database/migrations/migrated/2026_07_05_00000{1,2,3}_*.php   channels / messages / members

resources/views/livewire/dashboard/
├── channel.blade.php                  root view (x-data=channel(), two @island blocks)
├── header.blade.php                   channel header (uses $this->activeChannel)
├── messages.blade.php                 message list + date groups + empty-state (uses $this->activeChannel)
├── composer.blade.php                 input + attachments + emoji + reply/edit
├── info.blade.php                     info side-panel (uses $this->activeChannel)
├── sidebar.blade.php                  channel list + search + filter + create/browse buttons (uses $this->channels)
├── browse.blade.php                   joinable Open channels
├── create.blade.php                   user-panel create form (Open-only)
└── empty.blade.php                    no-channel-selected state

resources/js/components/alpine/data/channel.js         Alpine component (polling, send, reply/edit, undo)
routes/web.php                                         GET /channels → Channel::class → name 'channels'
routes/console.php                                     Schedule::command('channel:prune')->hourly()
app/Services/Search/NavigationService.php             nav entry 'channels' (route:channels + keywords)
resources/js/components/alpine/data/menu.js            menu item {id:'channels', href:'/channels', icon:'campaign'}
resources/js/components/alpine/main.js                 import + Alpine.data('channel', channel)
lang/fa/resources/channel/strings.php                 Filament fa strings (label/fields/filters/hints/export)
```

---

## 6. `Main.php` — the user-panel component

### 6.1 Properties
```php
public ChannelMessageComposerForm $composer;   // send form
public EditChannelMessageForm $edit;           // edit form
public CreateChannelForm $create;              // create form
#[Locked] public ?int $activeChannelId = null; // selected channel (Locked = client can't tamper)
public string $search = '';                    // sidebar search
public string $filter = 'all';                 // 'all' | 'unread'
public bool $browseMode = false;               // show joinable list in main pane
public bool $createMode = false;               // show create form in main pane
public bool $mobileShowChat = false;           // mobile: main pane visible
public int $editTimeLimit = 300;               // edit window seconds (mirrors SaveEditChannelMessageAction::EDIT_TIME_LIMIT)
public ?array $editingMsg = null;               // {id,body} being edited (UI state)
public ?array $lastDeleted = null;               // snapshot for 4s undo
public int $loadedLimit = 10;                    // growing-LIMIT window (fetch latest N; grow by 10 per loadOlder)
public bool $hasOlder = false;                  // more older messages exist (count > loadedLimit)
#[Locked] public ?int $focusAnchorId = null;   // message-id anchor for in-chat search "focus" (focusMessage); null = recent-N mode (reset on selectChannel/backToList/leaveChannel/send)
#[Locked] public int $focusOlder = 5;           // older-side window size in anchor mode (5 older + anchor + 5 newer; grow by 10 per loadOlder)
public string $messageSearch = '';              // in-chat message-search query (search.blade.php input; reset on selectChannel/backToList/leaveChannel)
```

### 6.2 Computeds (remember §3.1 — use `$this->` in islands)
- `activeChannel(): ?Channel` — `Channel::with('owner')->withCount('members')->find(activeChannelId)`.
- `channels(): array` — `FetchChannelsAction(viewer, search, filter)` + hydrates last-message bodies.
- `joinableChannels(): array` — `FetchJoinableChannelsAction(viewer)`.
- `messages(): array` — **two modes** (both use the +1-probe `hasOlder` idiom — see §3.3): (a) recent-N (default, `focusAnchorId=null`): `latest('id')->take(loadedLimit + 1)`, `hasOlder = count() > loadedLimit`, trim to `loadedLimit`, `sortBy('id')` oldest-first; (b) **anchor mode** (`focusAnchorId` set by `focusMessage`): bounded window `focusOlder older + anchor + up-to-5 newer` via two indexed queries (`where('id','>=',anchor)->oldest('id')->take(6)` + `where('id','<',anchor)->latest('id')->take(focusOlder + 1)`), `hasOlder = older.count() > focusOlder`, trim older to `focusOlder`, merge + `sortBy('id')`. Default path (a) is inert vs pre-focus.
- `groupedMessages(): array` — `messages` grouped by `created_at->toDateString()`.
- `presenter(): ChannelPresenter` — new instance (stateless).
- `messageSearchResults(): array` — null-guards `activeChannelId` (mirrors `messages()`), then `SearchChannelMessagesAction(activeChannelId, messageSearch, auth()->id())`. FULLTEXT boolean-mode prefix match: `whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$term.'*'])` on idx `idx_channel_messages_body_fulltext` (Telegram-like prefix; `innodb_ft_min_token_size=3`, so `MIN_LEN=3`); boolean operators stripped via `preg_replace('/[+\->()~*"@]/u', '', $q)` before the `*` append; `with('sender:id,name')`, `latest('id')`, `limit 20`; preview `strip_tags`+`Str::limit(,80)`; membership re-checked inside the action via composite-PK `ChannelMember::exists()` (IDOR-safe despite client-writable `messageSearch`; `activeChannelId` is `#[Locked]`).

### 6.3 Lifecycle + methods (contract + do/don't)
| Method | What it does | Rule |
|---|---|---|
| `mount()` | fills composer/edit/create forms | always fill all three (Livewire instantiates form objects; mount sets defaults) |
| `selectChannel(int)` | guard: `withoutTrashed()->find` + `ChannelMember` exists → set active, reset loadedLimit/focusAnchorId/focusOlder/composer/edit, `markRead`, `unset(channels,messages,activeChannel)` | the membership guard is mandatory; silent `return` on fail (no 403 to client); re-selecting same channel exits anchor mode |
| `focusRecord(int)` | menu/search deep-link → membership check then `selectChannel`; if `?focus_msg={id}` present (global-search `ChannelMessageResource` deep-link `/channels?open={channelId}&focus_msg={messageId}`), call `focusMessage(id)` after the channel opens | used by `FocusOnRecord` trait (`#[Url] open` = channelId); `focus_msg` read via `request()->query` (mount-only, no-op on AJAX); IDOR-safe because `focusMessage` re-checks membership + in-channel |
| `focusMessage(int)` | delegates to `FocusChannelMessageAction::execute(channelId, id, userId, loadedLimit)` → `?bool`; `null` → bail (invalid/not-member/no-such-message); `false` → in recent window, clear stale anchor (if any) + `unset(messages,groupedMessages)` + `dispatch('record-focus', ...)`; `true` → set `focusAnchorId=id`+`focusOlder=5` + `unset(messages,groupedMessages)` + dispatch | the 3-way `?bool` return lets Main branch without re-querying; **#[Locked] props** (`focusAnchorId`/`focusOlder`) — client can't tamper; `activeChannelId` is `#[Locked]` so anchor can't escape channel scope; `messageSearch` cleared server-side; reuses the global `record-focus` standard (`resources/js/core/record-focus.js` → `scrollToRecord('channel-message-{id}')` → scrollIntoView center + `.record-focus-flash`); exits anchor on `selectChannel`/`backToList`/`leaveChannel`/`send` |
| `refreshUnread()` | `unset($this->channels)` | called by the 10s poll; cheap recompute |
| `setFilter(string)` | set filter + `unset(channels)` | |
| `toggleBrowse()` | flip browseMode + `unset(joinableChannels)` | |
| `openCreate()` / `closeCreate()` | createMode flag + reset create form (default selection `type='open'`) | Open is the default selection; the Open/Private toggle (§3.7) lets the user override before submit |
| `createChannel()` | call `CreateChannelAction` with the form's `create.type` (no longer forced to `'open'` — the toggle value is respected), `selectChannel(new id)`, toast | catch `ValidationException` (toast first error) + `\Exception` (generic toast); slug from Persian name works via `\p{L}\p{N}` regex — slug generation moved out of Main into the action so the form's `slug.unique` rule validates the populated slug in one pass |
| `backToList()` | full reset (mobileShowChat/browse/create/active/loadedLimit/focusAnchorId/focusOlder/editing/composer) + `unset` all computeds | also clears `createMode` |
| `replyTo(int)` | set `composer.replyToId` + clear `editingMsg` | wired via `channel.js startReply` |
| `editMessage(int)` | load message **sender-scoped** (`sender_id = auth()->id()`, same channel) → set `editingMsg` + `edit.editingBody` | the `where('sender_id', auth()->id())` is the security boundary |
| `loadOlder()` | anchor mode: `focusOlder += 10`; else `loadedLimit += 10`; then `unset(messages)` | inert branch — default recent-N path unchanged; grows the older side of the anchor window |
| `send(SendChannelMessageAction)` | guard activeChannelId → `$action->execute(composer, activeChannelId)` → reset composer + clear `focusAnchorId`/`focusOlder` (exit anchor → new message visible in recent-N) + `unset(messages,channels)` + `dispatch('message-sent')` | catch `ValidationException`; **re-throw `HttpException`** (so 403 from `ensureMember` propagates, not masked); other `\Exception` → generic toast |
| `saveEdit(action, int)` | `$action->execute(edit, editingId)` → if false toast "مهلت به پایان رسیده" | edit window is 300s |
| `deleteMessage(action, int)` | `$action->execute(id)` → store `lastDeleted` snapshot + `dispatch('show-undo-toast')` | |
| `undoDelete(action)` | restore from `lastDeleted` → clear | 4s window (channel.js) |
| `joinChannel(action, int)` | `$action->execute(channelId, auth()->id())` → `selectChannel` | Open-only enforced inside action |
| `leaveChannel(action, int)` | `$action->execute` → if was active, clear active+loadedLimit+focusAnchorId+focusOlder → `unset` computeds + toast | |
| `removeAttachment(int)` | splice `composer.attachments` + reindex | |
| `downloadAttachment(int, int)` | delegates to `DownloadChannelAttachmentAction::execute(messageId, index, userId)` → returns `?Response` (the `BinaryFileResponse` or `null`) | membership check + path-traversal confinement are the security boundary; both live in the action now, Main just returns the result |
| `markRead(int)` **#[Async]** | `MarkChannelReadAction::execute` | fire-and-forget; safe because action null-guards empty channels |
| `resetComposer()` / `cancelReply()` / `cancelEdit()` **#[Js]** | inline JS to clear client state | #[Js] returns JS executed client-side |
| `render()` | `view('livewire.dashboard.channel')->layout('layouts.app')` | |
| `recordFocusType()` | returns `'channel'` | `FocusOnRecord` trait hook |

---

## 7. Actions catalog (contracts)

| Action | Signature | Core contract / do-don't |
|---|---|---|
| `FetchChannelsAction` | `execute(int $viewerId, string $search='', string $filter='all'): Collection` | joins `channel_members` on viewer; `leftJoinSub` last-message + unread subqueries; `orderByRaw('lm.last_message_id IS NULL')` puts channels with messages first. **Unread subquery uses `COALESCE(last_read_message_id, 0)`** so a null cursor reads everything as unread. **Search matches `name OR slug`** — `when(filled($search), fn($q) => $q->where(fn($g) => $g->where('channels.name','LIKE',"%$search%")->orWhere('channels.slug','LIKE',"%$search%")))`; the nested `where(fn($g)…)` groups the OR so it ANDs with the membership `where('channel_members.user_id', $viewerId)` — the OR cannot escape its group and leak non-member channels. Both `LIKE %x%` are non-sargable (leading wildcard); acceptable because `channels` is a small table and the name search already scanned it. |
| `FetchJoinableChannelsAction` | `execute(int $viewerId, string $search=''): Collection` | `withoutTrashed` + `type=Open` + `whereNotIn(members.user_id=viewer)`. **Search matches `name OR slug`** (same grouped-OR pattern as `FetchChannelsAction`); `Main::joinableChannels()` currently calls with no `$search`, so this is wired for a future browse-search input. |
| `SendChannelMessageAction` | `execute(ChannelMessageComposerForm, int $channelId): ChannelMessage` | `ensureMember` → `abort_unless(403, 'شما عضو این کانال نیستید.')`; DB transaction: create message (attachments stored after save under `channel_messages/{channelId}/{messageId}/`), then advance sender's `last_read_message_id` to the new message id; `resolveReplyToId` validates reply target is in the same channel. Returns `$message->fresh()`. |
| `SaveEditChannelMessageAction` | `execute(EditChannelMessageForm, int $messageId): bool` | `const EDIT_TIME_LIMIT = 300`; fetch **sender-scoped** (`sender_id = auth()->id()`); if `!$message || created_at->diffInSeconds(now()) > 300` → `false`; else `update(['body'=>trim, 'is_edited'=>true])` → `true`. |
| `DeleteChannelMessageAction` | `execute(int $messageId): array\|bool\|null` | returns `false` if not found, else snapshot `{channel_id, sender_id, body, attachments, is_edited, reply_to_id, created_at, original_id}` then `delete()`. Snapshot is for `UndoDeleteChannelMessageAction`. |
| `UndoDeleteChannelMessageAction` | `execute(array $lastDeleted): void` | `withTrashed()->find(original_id)->restore()`; if restore failed (row force-deleted), recreate from snapshot with original `created_at`. |
| `JoinChannelAction` | `execute(int $channelId, int $userId): void` | `abort_unless($channel && $channel->type === Open, 403, 'عضویت در این کانال ممکن نیست.')`; `ChannelMember::insertOrIgnore(...)` with `last_read_message_id = lastIdForChannel` (joins read-up-to-now). |
| `LeaveChannelAction` | `execute(int $channelId, int $userId): void` | `ChannelMember::where(...)->delete()`. |
| `SyncChannelMembersAction` | `execute(int $channelId, int $ownerId, array $recipientIds): array{added,removed}` | **Owner-managed member diff-sync** (clone of `ShareEventAction`, adapted for the HasMany `members` relation). Scopes the channel by `owner_id` first (IDOR-safe — non-owner finds nothing → no-op return). Whole op inside one `DB::transaction`: `normalizeRecipients` (cast→filter `>0 && != ownerId`→unique→`User::active()->whereIn` pluck, **inside** the tx to close the TOCTOU the ShareEvent clone had), `pluck('user_id')` current members, `array_diff` toAdd/toRemove, `ChannelMember::insertOrIgnore(...)` for adds (timestamps + `joined_at=now`, `last_read_message_id=null`). **Owner is never removed** (`array_diff($current, $recipientIds, [$ownerId])`) and never re-added (normalize filter drops ownerId). **Empty input is a no-op, not a purge** — `if (empty($recipientIds)) return;` guards removal before the diff, so saving an empty checkbox list does NOT kick everyone out. Returns `['added' => insertOrIgnore count, 'removed' => delete() return]`. No bulk-op model events → no automatic nudge (channels have none anyway); `Main::saveManageMembers` unsets computeds and toasts only when something actually changed. |
| `CreateChannelAction` | `execute(CreateChannelForm $form): Channel` | `resolveSlug($form)` BEFORE `$form->validate()` (slug built from Persian name via `\p{L}\p{N}` regex + `Str::random(6)` suffix, same order as the old inline build; the form's `slug.unique` rule still catches collisions pre-insert on the now-populated slug); transaction: `Channel::create(owner_id=auth()->id())` (the `Channel::created` boot ALSO inserts the owner member via `insertOrIgnore` — idempotent with the explicit `insertOrIgnore` below) → `ChannelMember::insertOrIgnore(owner)`. Returns channel. |
| `FocusChannelMessageAction` | `execute(int $channelId, int $messageId, int $userId, int $loadedLimit): ?bool` | Guard for `Main::focusMessage`. Returns **`null`** = invalid id / not-a-member / target message doesn't exist in channel; **`false`** = target sits within the already-loaded recent window (no anchor needed); **`true`** = target is beyond `loadedLimit` (set anchor). Membership via composite-PK `ChannelMember::exists()` (IDOR-safe; `activeChannelId` is `#[Locked]`). In-window check is a **capped +1 probe** — `ChannelMessage::withoutTrashed()->where('channel_id',$channelId)->where('id','>=',$messageId)->orderBy('id')->take($loadedLimit + 1)->pluck('id')->count()`: `0` → not in channel (`null`), `<= $loadedLimit` → in window (`false`), `> $loadedLimit` → over (`true`). Never an unbounded `COUNT(*)` range scan; the `take($loadedLimit + 1)` cap stops the index scan the moment the answer is known. |
| `DownloadChannelAttachmentAction` | `execute(int $messageId, int $index, int $userId): ?Response` | Guard for `Main::downloadAttachment`. `ChannelMessage::withoutTrashed()->find($messageId)` → membership via `ChannelMember::exists()` → shape-guard the attachment (`is_array` + `isset(path,name)`) → `Storage::disk('public')` with **realpath-within-root confinement**: `realpath($disk->path(''))` and `realpath($disk->path($attachment['path']))` must both resolve and `str_starts_with($real, $root . DIRECTORY_SEPARATOR)` (blocks path traversal via a crafted `path`/`..`); returns `response()->download($real, $attachment['name'])` (a `BinaryFileResponse` — NOT `Storage::download()` which returns a `StreamedResponse` and would change the HTTP response type). Any guard miss → `null` (Main returns it unchanged; Livewire renders the empty response). |
| `MarkChannelReadAction` | `execute(int $channelId, int $userId): void` | `$lastId = ChannelMessage::lastIdForChannel($channelId)`; **`if ($lastId === null) return;`** (empty channel → nothing to mark; without this guard, `orWhere('last_read_message_id','<', null)` throws "Illegal operator and value combination"); forward-only UPDATE where `last_read IS NULL OR last_read < $lastId`. |
| `ForceDeleteChannelAction` | `execute(Channel $channel): ?bool` | `$channel->forceDelete()` — the `forceDeleted` boot hook unlinks `channel_messages/{id}/`. |
| `ForceDeleteChannelMessageAction` | `execute(ChannelMessage $message): ?bool` | `$message->forceDelete()` — the `forceDeleted` boot hook unlinks `channel_messages/{channel_id}/{id}/`. |

**`ChannelMessage::lastIdForChannel` returns `?int` — every caller MUST null-guard.** `JoinChannelAction` passes it straight into `last_read_message_id` (null is fine there — column nullable). `MarkChannelReadAction` MUST early-return on null (comparison operator can't take null).

---

## 8. Forms catalog

| Form | Properties (validation) |
|---|---|
| `ChannelMessageComposerForm` (extends `Livewire\Form`) | `body: string` `#[Validate('required_without:attachments\|string\|min:1\|max:4000')]`; `attachments: array` `#[Validate(['nullable\|array\|max:5', 'attachments.*' => 'file\|max:10240\|mimes:jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,rar'])]`; `replyToId: ?int` `#[Validate('nullable\|exists:channel_messages,id')]`. Custom `messages()` (8 Persian keys). |
| `EditChannelMessageForm` | `editingBody: string` `#[Validate('required\|string\|min:1\|max:4000')]`. `messages()` (3 keys). No `rules()` (attributes only). |
| `CreateChannelForm` | `name, slug, description: ?string, type: string='open'`. `rules()`: name `required\|string\|max:100`; slug `required\|string\|max:120\|Rule::unique('channels','slug')->whereNull('deleted_at')`; description `nullable\|string\|max:500`; type `required\|in:open,private`. `messages()` (8 keys). |

---

## 9. Presenter `ChannelPresenter` (uses `HasPublicAssetUrl`)

Stateless view-shaping. **Do not query in the presenter** — it only shapes arrays already fetched by `Main`/Actions.

| Method | Returns |
|---|---|
| `sidebar(array $channels, int $authId): array` | maps `channel()` over the list |
| `channel(array $c, int $authId): array` | `{id,name,slug,description,type,initials,unread,last_message{body,time,datetime,is_mine}}` |
| `totalUnread(array $channels): int` | `array_sum(array_column($channels, 'unread_count'))` |
| `channelHeader(Channel $channel): array` | `{id,name,slug,description,type,type_label,type_icon,type_color,owner_name,members_count}` — `type_label` via `ChannelType::getLabel()`; `type_icon`/`type_color` via `ChannelType::getMaterialIcon()`/`getMaterialColor()` (Material Symbols glyph + CSS var, used by the user-panel header/info tiles; NOT `getIcon()`, which returns Filament Heroicons for the admin resource) |
| `browseList(array $channels): array` | `{id,name,description,type,owner_name}` per item |
| `messageGroup(string $date, array $messages, int $authId, int $editTimeLimit): array` | `{date,label,messages}`; label = today `امروز` / yesterday `دیروز` / else `translatedFormat('j F Y')` |
| `messages(array $messages, int $authId, int $editTimeLimit): array` | per-message: `{id,body,body_html,created_at,time,datetime,is_mine,is_first,is_last,is_edited,is_deleted,sender_name,sender_avatar,can_edit,can_delete,attachments,gap_class,bubble_radius,reply_to,animation_delay}`; `can_edit = isMine && no deleted_at && diffInSeconds(now) <= editTimeLimit`; `sender_avatar` = `sender.avatar` URL (null when sender deleted → `<x-ui.avatar>` falls back to `person` icon). `Main::messages()` eager-loads `sender.profile` (both query branches) so `User::getProfileImageUrl()` → `Profile::getImageUrl()` → `HasAvatar::resolveImageUrl` (pure string logic, no `Storage::exists` stat) is N+1-free and filesystem-I/O-free per row |
| private `replyPreview, bubbleRadius, attachments, linkify` | helpers; `replyPreview` now includes `id` (the replied-to message id) so the reply-preview block can scroll to it |

Note: there is **no** `messageList()` or `groupedMessages()` method on the presenter — grouping is done in `Main::groupedMessages()` computed; the presenter's `messageGroup()` shapes one group.

---

## 10. Views — and the island-scope rule for each

Every partial opens with `@php($p = $this->presenter; ...)` and reads computeds via `$this->`. **Never** use a bare computed (`$activeChannel`, `$channels`, `$messages`, `$joinableChannels`, `$groupedMessages`, `$presenter`) — see §3.1.

| Partial | Opens with | Uses |
|---|---|---|
| `channel.blade.php` (root) | `x-data="channel()"` | two `@island` blocks; messages island branches `@if($createMode)` → create · `@elseif($activeChannelId && $this->activeChannel)` → header+messages+composer+info · `@elseif($browseMode)` → browse · `@else` → empty |
| `sidebar.blade.php` | `$p=$this->presenter; $channelList=$p->sidebar($this->channels, auth()->id()); $totalUnread=$p->totalUnread($this->channels)` | search = **plain `wire:model.live.debounce.200ms` input** (NOT `<x-ui.forms.search>` — `@entangle` fatals in island); filter tabs; create button (`openCreate()`) + browse button (`toggleBrowse()`) |
| `header.blade.php` | `$p=$this->presenter; $header=$p->channelHeader($this->activeChannel)` | channel name, type badge (icon+color via `type_icon`/`type_color`), members count, back/leave/info buttons + search toggle button (`openMessageSearch()`) |
| `search.blade.php` | `$this->messageSearch` / `$this->messageSearchResults` | `x-show="searchMessages"` absolute top-center panel inside the active-channel branch; `wire:model.live.debounce.300ms="messageSearch"` input + results list; toggle + focus + nav-resets driven by `channel.js` |
| `messages.blade.php` | `$hasOlder=$this->hasOlder` | date-grouped bubbles; each row `data-rf="channel-message-{id}"` (focus target for `record-focus`); **avatar on every `is_last`** (ALL senders incl. own — group/broadcast convention, NOT gated by `!is_mine` like contact 1:1): `<x-ui.avatar :existingImage="$msg['sender_avatar']" :alt="$msg['sender_name']" icon="person" icon-size="text-base">` inside `w-7 h-7 rounded-lg overflow-hidden` wrapper (island-safe — pure-props component, no `@entangle`); `is_last=false` rows get a `w-7` spacer to keep the bubble column aligned; **action toolbar (copy + reply) renders on EVERY message** (hover-revealed, absolute-positioned beside the bubble) — copy & reply are per-message ops available on all rows; edit + delete stay gated by `is_last` (`can_edit && is_last` / `can_delete && is_last`) since the user accepted edit/delete only on the group-final message; the timestamp/edited/read-receipt meta row also stays `is_last`-gated (one timestamp per sender group); reply-preview block is clickable (`data-id` + `x-on:click` + `keydown.enter/space.prevent` + `role="button"` + `tabindex="0"`) → `scrollToMessage(id)`; empty-state uses `$this->activeChannel->name`; shared `<x-ui.buttons.load-more action="loadOlder">` gated by `x-show="$wire.hasOlder" x-transition` (fades on last round); scroll-to-top auto-fires `loadOlder` (channel.js) |
| `composer.blade.php` | | textarea (`msg-ta`), attachments, emoji picker, reply preview, edit preview |
| `info.blade.php` | `$p=$this->presenter; $header=$p->channelHeader($this->activeChannel)` | `x-show="showInfo"` side panel |
| `browse.blade.php` | `$this->joinableChannels` | Open channels the user can join |
| `create.blade.php` | | create form: `wire:model="create.name"` + `create.description` + an **Open/Private two-button toggle** (`$wire.set('create.type', …)`); subtitle is dynamic on `$wire.create.type`; submit `createChannel`; cancel `closeCreate` |
| `empty.blade.php` | | no-channel-selected state |

---

## 11. `channel.js` (Alpine)

```
init()              startPolling() + visibilitychange (halt on hidden) + scroll-FAB + scroll-to-top auto-load (loadOlder w/ scroll-anchoring, overflow-anchor:none) + $wire.on listeners
startPolling()      setInterval(10s) → $wire.$island('sidebar').refreshUnread()   (NO wire:poll)
stopPolling()       clearInterval
destroy()           stopPolling + remove listeners
toast(msg, type)    dispatches window 'toast' CustomEvent
scrollToBottom(smooth)
closeOverlays()     showInfo=false, replyingTo=null, $wire.cancelReply(), $wire.cancelEdit()
insertEmoji(e)      insert at caret in #msg-ta
copyMessage(text)   clipboard + fallbackCopyText
selectChannel(id)   replyingTo=null; $wire.cancelReply(); $wire.$island('messages').selectChannel(id).then(() => $wire.$island('sidebar').refreshUnread()).then(() => $nextTick(scrollToBottom))  ← island-scoped method call re-renders messages island natively (no manual $refresh); this is the path that exposed the $activeChannel bug
toggleBrowse / openCreate / closeCreate   Alpine wrappers → $wire
startReply(id, name, body)  replyingTo={...}; $wire.replyTo(id); $wire.cancelEdit()
cancelReply / startEdit / cancelEdit
saveEdit(id)        await $wire.saveEdit(id) (try/catch toast)
scrollToMessage(id) if (!id) return; in-DOM fast path: querySelector(`[data-rf="channel-message-${id}"]`) → scrollIntoView smooth center + restart `record-focus-flash` (same class record-focus.js uses) + remove on animationend; fallback (row not in loaded window): `$wire.$island('messages').focusMessage(id)` (loads anchor/recent window + dispatches `record-focus` → record-focus.js scroll+flash). Wired from the reply-preview block click/Enter/Space; reuses the `data-*` + `$el.dataset` island-safe pattern (matches `focusSearchResult`)
confirmDelete(id)   $wire.deleteMessage(id)
sendMessage()       guard empty + >4000 char; await $wire.send(); 500ms sending lock
$watch($wire.lastDeleted)  4s undo toast window
```

`$wire.on('message-sent')` → scroll to bottom + `sending=false`. `$wire.on('show-toast'/'show-undo-toast')` → toast.

---

## 12. Filament admin (`ChannelResource`)

- Resource: `Channel`, nav icon `heroicon-o-chat-bubble-left-right`, sort 8. Pages: List/Create/Edit. RMs: `ChannelMessages`, `ChannelMembers`.
- Traits: `FilamentActions, FilamentFilters, AuthorizesByPermission` (permission gating via trait; no explicit `canCreate`/`canViewAny` overrides).
- `getEloquentQuery()` removes `SoftDeletingScope`, eager-loads `owner`, `withCount('members','messages')`.
- Global search attributes: `name, slug, description`; result title = type+owner; URL → edit route.
- **`ListChannels::getHeaderActions()`** returns `CreateAction::make()->icon('heroicon-o-sparkles')->label(__('resources/general/strings.table.action_create'))` (override restored; the `FilamentHeaderActions` trait's `listHeaderActions()` returns the same — the override is explicit, not redundant).
- `ChannelMessagesRelationManager`: `$relationship='messages'`, eager `sender`, columns id/sender.name/body(truncated 77)/is_edited/created_at(jalali), filter `self::createdAtFilter()`, actions view/edit/delete. **Must `use FilamentFilters` + `use App\Traits\FilamentFilters;` to call `self::createdAtFilter()`** (filamentPattern.md §39).
- `ChannelMembersRelationManager`: `$relationship='members'`, eager `user`, columns user.name/joined_at/last_read_message_id/created_at. No `createdAtFilter`.
- Schemas: `ChannelFormPresenter` (name/slug/type/owner/description), `ChannelInfolistPresenter` (name/slug/type/owner/membersCount/messagesCount/description/timestamps), `ChannelTablePresenter` (id/name/type/membersCount/messagesCount/createdAt/deletedAt + typeFilter + typeGroup).
- Export: `ChannelExporter` (columns incl. type label, owner.name, counts, timestamps).
- **Filament create path does NOT need a member-creation override** — the `Channel::created` boot hook auto-enrolls the owner. (Before that hook existed, Filament-created channels were invisible to their owner — that was the bug.)

---

## 13. Console / pruning

- `PruneChannels` command: `$signature = 'channel:prune'`, `$description = 'Force-delete channel messages and channels soft-deleted more than 30 days ago.'`. `handle(ForceDeleteChannelMessageAction, ForceDeleteChannelAction)`: cutoff `now()->subDays(30)`; `chunkById(200)` over `onlyTrashed()->where('deleted_at','<=',$cutoff)` for messages then channels, calling the force-delete actions (which trigger the `forceDeleted` storage-unlink hooks).
- Schedule: `routes/console.php` → `Schedule::command('channel:prune')->hourly();`

---

## 14. Wiring

- **Route**: `routes/web.php` → `use App\Livewire\Dashboard\Channel\Main as Channel; Route::get('/channels', Channel::class)->name('channels');`
- **Nav search**: `NavigationService::getSearchableItems()` → `{id:'channels', title:'کانال‌ها', subtitle:'کانال‌های موضوعی و گروهی', icon:'campaign', action:'route:channels', keywords:[channel,channels,room,topic,broadcast,group,announce,feed,کانال,کانال‌ها,گروه,موضوعی,گروهی,پخش,اعلام,موضوع,کانال اطلاع‌رسانی]}`
- **Menu**: `menu.js` → `{id:'channels', href:'/channels', icon:'campaign', title:'کانال‌ها', sub:'کانال‌های موضوعی'}`
- **Alpine**: `main.js` → `import channel from "./data/channel.js"; Alpine.data('channel', channel)`
- **Lang**: `lang/fa/resources/channel/strings.php` → keys `label, plural_label, nav_group, fields{name,slug,description,type,owner,members_count,messages_count,body,sender,user,is_edited,joined_at,last_read_message,created_at,updated_at,deleted_at}, form{section_meta,section_content}, filters{type}, hints{slug}, export{completed}`.

---

## 15. Dos and Don'ts (consolidated hard rules)

### DO
- ✅ Reach computeds inside islands via `$this->` (`$this->activeChannel`, `$this->channels`, `$this->presenter`). Public properties can stay bare (`$activeChannelId`, `$browseMode`, `$createMode`).
- ✅ Null-guard every `ChannelMessage::lastIdForChannel()` caller. It returns `?int`; empty channel → `null`.
- ✅ Re-throw `HttpException` in `send()` so `ensureMember`'s 403 propagates; only catch `ValidationException` + generic `\Exception`.
- ✅ Scope message edits/deletes by `sender_id = auth()->id()` (in both `Main::editMessage` and `SaveEditChannelMessageAction`).
- ✅ Use `insertOrIgnore` for member rows (composite PK, idempotent with the `Channel::created` boot).
- ✅ Use plain `wire:model` for in-island inputs (NOT `<x-ui.forms.search>` / `@entangle`).
- ✅ Route all force-deletes through `ForceDeleteChannel*Action` (so the `forceDeleted` storage hooks fire).
- ✅ Keep MySQL 5.7-safe: `leftJoinSub`, `unionAll`, `orderByRaw`, `insertOrIgnore`, composite/covering indexes — all fine. No `JSON_TABLE`, no window functions.
- ✅ Enforce slug uniqueness at the form layer (`Rule::unique(...)->whereNull('deleted_at')`) so soft-deleted slugs are reusable; no DB unique index.
- ✅ User-panel create supports both Open and Private (toggle in `create.blade.php`); owner can sync members for either type via the info-pane modal (§3.7).
- ✅ No code comments (project rule). Inline Persian for partials; `lang/fa` keys only for Filament.

### DON'T
- ❌ Don't use a bare computed (`$activeChannel`, `$channels`, etc.) inside `@island` or its `@include` partials — it throws on the refresh path (masked by `Livewire::test`).
- ❌ Don't use `@entangle` / `<x-ui.forms.search>` inside an island — `@entangle` needs `$__livewire` which nested anonymous components don't inherit.
- ❌ Don't trust `Livewire::test()->html()` alone for island code — it does a full render and shares scope globally, hiding island-only scope bugs. Add a real island-refresh check (see §17).
- ❌ Don't add `HasMenuState` / nudge / badge / wire:poll / a cache layer / a nested Livewire component — none belong here.
- ❌ Don't call `self::createdAtFilter()` in a Filament RM without `use App\Traits\FilamentFilters;` + `use FilamentFilters;`.
- ❌ Don't access `ChannelType` via `->label` — the enum implements `getLabel()` (and `getIcon/getColor`), not a `label` property.
- ❌ Don't write property hooks in models (PHP 8.2 runtime) — keep the `ContentSanitizerService` mutator on `ChannelMessage::body()`.
- ❌ Don't add a `DB::unique` on `channels.slug` — form-layer unique with `whereNull('deleted_at')` is the rule.
- ❌ Don't create a channel without enrolling the owner as a member (the `Channel::created` boot does this now; never bypass it).
- ❌ Don't erase/truncate `fateh` DB data without explicit session approval; benchmark only in `perf_benchmark` DB.

---

## 16. Known edge cases / gotchas

1. **Empty channel + `markRead`** — `lastIdForChannel` returns `null`; `MarkChannelReadAction` early-returns (fixed). Without the guard, `orWhere(col,'<',null)` throws "Illegal operator and value combination".
2. **Filament-created channel invisible to owner** — fixed by `Channel::created` boot inserting the owner member. Existing channels were backfilled once.
3. **Bare `$activeChannel` in messages island** — fixed (use `$this->activeChannel`). All three island partials (header/messages/info) + the root conditional.
4. **`@entangle`/`<x-ui.forms.search>` in sidebar island** — fixed (plain `wire:model` input + Alpine clear).
5. **Sender soft-deleted** — `sender_id` is `nullOnDelete`, so the message survives and shows `ناشناس` ("unknown").
6. **Channel soft-deleted** — member row survives (FK is `cascadeOnDelete`=hard-delete only, no SoftDeletes on members), but `FetchChannelsAction` joins `channels` which excludes soft-deleted via the model; `selectChannel` guards with `withoutTrashed()`.
7. **Undo after force-delete** — `UndoDeleteChannelMessageAction` falls back to recreating from the snapshot if the soft-deleted row was already force-deleted.
8. **Persian slug** — `Str::slug` returns empty for Persian; `CreateChannelAction::resolveSlug()` uses `preg_replace('/[^\p{L}\p{N}]+/u','-', mb_strtolower($name))` + `Str::random(6)` suffix for uniqueness. Slug generation runs **before** `$form->validate()` so the form's `slug.unique` rule validates the populated slug (moved out of `Main::createChannel` — Main no longer touches `Str`/slug).
9. **Edit window** — 300s enforced in `SaveEditChannelMessageAction::EDIT_TIME_LIMIT` and mirrored as `Main::$editTimeLimit` for the `can_edit` presenter flag; keep both in sync.

---

## 17. Verification commands (use these, not just `Livewire::test`)

`Livewire::test()->html()` does a **full render** and masks island-scope bugs. For island code, also verify the island-refresh path:

```bash
# clear compiled views after any blade edit (stale compiled views cause phantom errors)
php artisan view:clear

# full render (catches syntax + full-scope errors only)
php artisan tinker --execute "\Illuminate\Support\Facades\Auth::login(\App\Models\User::first()); \$t=\Livewire\Livewire::test(\App\Livewire\Dashboard\Channel\Main::class); echo strlen(\$t->html());"

# select an empty channel (was the markRead-null crash)
php artisan tinker --execute "\Illuminate\Support\Facades\Auth::login(\App\Models\User::first()); \$t=\Livewire\Livewire::test(\App\Livewire\Dashboard\Channel\Main::class); \$t->call('selectChannel',3); echo var_export(\$t->get('activeChannelId'),true);"

# create flow (rollback-wrapped, no test data persisted) — see §6.3 createChannel
# backfill owner members for existing channels lacking them:
php artisan tinker --execute "\Illuminate\Support\Facades\DB::affectingStatement(\"INSERT IGNORE INTO channel_members (channel_id,user_id,joined_at,last_read_message_id,created_at,updated_at) SELECT c.id,c.owner_id,NOW(),NULL,NOW(),NOW() FROM channels c WHERE c.owner_id IS NOT NULL AND c.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM channel_members cm WHERE cm.channel_id=c.id AND cm.user_id=c.owner_id)\");"

# real browser check (REQUIRED for island code): open /channels, click a channel,
# watch the Network tab for the livewire/update call that fires the island-scoped method (e.g. $island('messages').selectChannel)
# and the browser console for "Undefined variable $activeChannel" or similar.
```

**For any future island edit, the mandatory verification is the real browser flow** (click channel → watch the island-refresh request + console). `Livewire::test` is necessary but not sufficient.

---

## 18. How to extend

- **New message action**: add an `Actions/XxxChannelMessageAction` class (single responsibility, DB::transaction for multi-write), call from a `Main` method, `unset($this->messages)` to recompute, dispatch a toast. Never query in the presenter.
- **New sidebar filter**: add to `FetchChannelsAction` `$filter` switch + a tab in `sidebar.blade.php` + `setFilter()`.
- **New channel type**: add a `ChannelType` case + `getLabel/getIcon/getColor` branches + (if joinable) update `JoinChannelAction`'s `abort_unless` + a type selector in `create.blade.php` (currently Open-only).
- **Owner-managed members (§3.7)**: `SyncChannelMembersAction` + `manage-members.blade.php` modal + admin `ChannelMembersRelationManager` addMember/remove — done. An *invites-with-token* flow (request→approve→accept) is **not** built; the chosen minimal path auto-adds and signals client-side.
- **New in-island input**: use plain `wire:model` + Alpine, NOT `<x-ui.forms.search>`/`@entangle`.

---

## 19. Pointers

- Master plan: `.claude/plans/channels-master-plan.md` (architecture decisions, §20 PK order + pane-island rationale).
- Livewire conventions: `app/Livewire/livewirePattern.md` — canonical `@island` rules, the `syncAttachments` upload-bridge, and the `lastIdForChannel` null-guard (referenced from §3.1/§7/§16 here).
- Filament conventions: `app/Filament/filamentPattern.md` (esp. §39 RM-FilamentFilters rule).
- Memory: `project_channels_module_plan` (status, stack facts, constraints).
- Sibling module to mirror: `app/Livewire/Dashboard/Contact/` (1:1 chat) — but channels is **isolated**; do not modify contact files.