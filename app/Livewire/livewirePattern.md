# Livewire Module Pattern

## Target Structure

```
ComponentName/
├── Main.php
├── Forms/
│   └── SomethingForm.php
├── Actions/
│   └── DoSomethingAction.php
├── Validators/
│   └── SomethingValidator.php
└── Presentation/
    └── SomethingPresenter.php
```

---

## Step 1 — Audit the existing component

Identify and categorize every method and property in the component and any traits it uses:

| Category | What it looks like |
|---|---|
| **Form state** | Public properties that are `wire:model` bound in Blade |
| **Validation** | `validate()` calls, rules arrays, message arrays |
| **Persistence** | DB writes, `Model::create`, `->update()`, file storage |
| **Component state** | `activeTab`, `perPage`, pagination, search, modal flags |
| **Livewire hooks** | `mount`, `updated*`, `#[Computed]` |
| **Presentation** | `sprintf`, `diffForHumans`, label lookups, string formatting |
| **Traits** | Any trait that mixes state + logic — candidate for removal |

---

## Step 2 — Create Form Objects

**One Form Object per form in the UI.**

```php
namespace App\Livewire\{Module}\Forms;

use Livewire\Form;

class SomethingForm extends Form
{
   #[Validate('...')]
    public string $field = '';

    protected function messages(): array { ... }

    public function validated(): array
    {
        return $this->validate();
    }
}
```

**Rules:**
- Move all `wire:model` fields from the component into the Form Object
- Move `rules()` and `messages()` from any trait or inline `validate()` call
- Keep only fields that belong to this form — no unrelated state
- Livewire auto-instantiates — no `new` or `mount` initialization needed
- `wire:model="form.field"` dot notation works identically in Blade — **no Blade changes** except the prefix

> **`#[Validate]` array-rule gotcha:** to set a wildcard rule on an array property's elements (e.g. `collaborators.*`), `#[Validate([...])]`'s array form is only parsed as a key-value rules map (`Livewire\Features\SupportValidation\BaseValidate::boot()`) when its **first key is non-numeric**. Writing `#[Validate(['array', 'collaborators.*' => 'exists:users,id'])]` puts `'array'` at numeric key `0`, so the whole array silently collapses into one malformed rule value instead of being parsed — the wildcard rule never registers, no error, no warning. Key every entry explicitly: `#[Validate(['collaborators' => 'array', 'collaborators.*' => 'exists:users,id'])]`.

---

## Step 3 — Create Actions

**One Action per write operation (submit, persist, store).**

```php
namespace App\Livewire\{Module}\Actions;

class DoSomethingAction
{
    public function __construct(private SomethingValidator $validator) {}

    public function execute(SomethingForm $form): void
    {
        $this->validator->validate(...);
        // DB writes, file storage, etc.
    }
}
```

**Rules:**
- Single public `execute()` method
- Receives Form Object(s) and/or Eloquent models as arguments
- No access to `$this` (the component) — no `dispatch`, no session, no redirect
- Inject into Livewire methods via method injection: `public function submit(DoSomethingAction $action)`
- Post-action side effects (toasts, tab switches, resets) remain in `Main`
- If pre-condition logic is complex (4+ checks, DB lookups, policy resolution), delegate to a Validator — see Step 3a

---

## Step 3a — Create Validators (only if needed)

**Only create when an Action's pre-condition logic is numerous, complex, or involves multiple DB checks.**

```php
namespace App\Livewire\{Module}\Validators;

class SomethingValidator
{
    public function validate(User $user, Resource $resource, ...): void
    {
        // throws Exception on failure — no return value needed
    }
}
```

Inject into the Action via constructor:

```php
class DoSomethingAction
{
    public function __construct(private SomethingValidator $validator) {}

    public function execute(...): Model
    {
        $this->validator->validate(...);
        // persist
    }
}
```

**Rules:**
- Single public `validate()` method — throws `Exception` on failure, returns `void` on pass
- No writes, no side effects — pure guard logic only
- Laravel's container auto-resolves it into the Action's constructor — no manual wiring needed
- **Do not create** for Actions with only 1–2 simple guards — inline those in the Action itself

**When a Validator is NOT needed:**

```php
// Two guards — inline is fine, no Validator needed
public function execute(Reservation $reservation, User $user): void
{
    if ($reservation->status !== 'active')
        throw new Exception("...");

    if (!$user->hasElevatedRole() && $reservation->user_id !== $user->id)
        throw new Exception("...");

    // persist
}
```

---

## Step 4 — Create Presenter (only if needed)

**Only create if the component has pure formatting/display methods called from Blade.**

```php
namespace App\Livewire\{Module}\Presentation;

class SomethingPresenter
{
    public function formatThing(mixed $data): string
    {
        // pure transformation, no DB, no state
    }
}
```

Pass via `render()`:

```php
public function render()
{
    return view('livewire.path', [
        'presenter' => new SomethingPresenter(),
    ]);
}
```

**Rules:**
- Only needed when Blade calls `$this->getSomething()` style methods for display
- Pure read-only — no writes, no DB, no state mutation
- Remember the view follows the ordinary and same pattern if component is dms the blade would be dms.blade and all of its partials would sit within dms folder
- If the formatting is one-liner and only used once, inline it in Blade with `@php` instead

---

## Step 5 — Clean up Main

After extraction, `Main` should contain **only**:

```php
class Main extends Component
{
    // Form Objects (auto-instantiated by Livewire)
    public SomethingForm $form;

    // Component-level state (not form fields)
    public string $activeTab = 'default';
    public ?array $selectedItem = null;
    public int $perPage = 10;
    public string $search = '';

    // Lifecycle
    public function mount(): void { /* set initial form values */ }

    // Event handlers — delegate to Actions
    public function submit(DoSomethingAction $action): void
    {
        $action->execute($this->form);
        // side effects only: dispatch, reset, tab change
    }

    // Livewire hooks
    public function updated($prop): void { ... }
    public function updatedFormField($value): void { ... }

    // Computed
    #[Computed]
    public function items() { ... }

    // Navigation / UI state
    public function switchTab(string $tab): void { ... }
    public function viewItem($id): void { ... }

    // Render
    public function render()
    {
        return view('livewire.path');
    }
}
```

**Remove from Main:**
- Any trait that mixes state and logic together
- DB calls outside of Actions
- Validation rules (belong in Form Objects)
- Formatting methods (belong in Presenter)
- `persistX`, `storeX`, `validateX` style private methods

---

## Step 6 — Update Blade

Only two types of Blade changes are ever needed:

**1. Form field prefix changed** (because fields moved into Form Object)
```blade
{{-- Before --}}
wire:model="subject"

{{-- After --}}
wire:model="form.subject"
```

**2. Presenter method calls** (if Presenter was extracted)
```blade
{{-- Before --}}
{{ $this->getFormattedId($item) }}

{{-- After --}}
{{ $presenter->formatId($item) }}
```

Everything else — `$activeTab`, computed properties, `$this->items`, event listeners — is unchanged.

---

## Checklist

- [ ] Identified all traits — removed and redistributed
- [ ] One Form Object per form — fields + rules + messages
- [ ] One Action per write operation — `execute()` only
- [ ] Validator extracted only when Action has 4+ guards or complex DB pre-checks
- [ ] Presenter created only if Blade calls formatting methods on `$this`
- [ ] `Main` contains only: state, mount, hooks, computed, navigation, render
- [ ] Blade updated for form field prefix and presenter calls only
- [ ] No `$this->reset()` wiping state that was just set — use scoped `$this->form->reset()`
- [ ] Actions receive Form Objects and models — never the component itself
- [ ] Post-action side effects (toast, redirect, tab) stay in `Main`, not in Action
- [ ] Public properties holding an Eloquent model or record id are `#[Locked]` unless the client is meant to change them — prevents the client swapping the id to act on another user's record (IDOR)
- [ ] Bulk-action Actions (operating on a client-supplied array of ids, e.g. `selectedTasks`) filter the loaded models through the same `can_*` accessor the single-record method already uses, *inside* the Action — e.g. `Task::whereIn('id', $taskIds)->get()->filter(fn($t) => $t->can_change_status)` — rather than trusting the array as-is. The accessor is computed server-side from `auth()->id()`, so any id the user doesn't own is silently dropped instead of acted on
- [ ] When Blade hides a button under an *extra* condition beyond the `can_*` accessor (e.g. `@if($task['is_delegator'] && $column !== 'done')`), that extra condition must be re-checked inside the Action too, not just the accessor — a UI-only restriction is not a server-side guard. Found via `UndoTaskAssignmentAction`, which checked `is_delegator` but not `status !== 'done'`, even though the card already hid the button for done tasks

---

## What NOT to do

- Do not create Actions for read operations — those belong in `#[Computed]`
- Do not pass `$this` into an Action
- Do not put `dispatch()` or `redirect()` inside an Action
- Do not create a Presenter for values already available as model attributes or computed properties
- Do not create a Form Object for state that is never submitted (search, pagination, tab — these stay on `Main`)
- Do not create a Validator for Actions with only 1–2 simple guards — inline them
- Do not over-split: if a component has one simple form and no formatting helpers, Form Object + Action is enough — no Presenter or Validator needed

---

## Data-bag Presenter + lazy render (Feeds)

`Feeds` is the concrete example of Step 4's Presenter, extended to a **data-bag** shape: instead of one-off `formatX($value)` methods, `FeedPresenter` returns small arrays that bundle everything a Blade region needs in one call, so the view does one `@php $flags = $presenter->feedFlags($feed) @endphp` instead of re-deriving fields inline.

- `feedFlags($feed)` → `['isPoll','showComments','showReactions','settings']` (one call gates the whole card).
- `pollData($feed)` → `['isMultiple','options','total','counts','userVotes']` (computed once, before the option loop), and `optionState($index, $pollData)` → `['count','pct','isMine']` per option reusing the bag.
- `commentMeta($comment, $editingCommentId)` → `['user','avatarUrl','hasPhoto','isOnline','isOwner','isEditing']` per comment row.
- `mediaGrid($media)` → `['items','images','cols','rows']`; `$media` is `$feed->media_urls` (pre-resolved URLs — see the asset-URL convention below), so the grid is pure layout and the view never calls `Storage::`.
- `categoryValue($category)` normalizes the raw-DB-string `category` (not cast to the `FeedCategory` enum) using the codebase idiom `($category?->value ?? $category)` — note `?->value` on a non-null string returns `null` in PHP 8 (no throw), so the `?? $category` fallback recovers the string. Used by `categoryEmoji`/`feedFlags` instead of repeating the idiom in Blade.

---

## Layout-injected global banner (broadcast across all pages)

For a UI element that must appear on **every** user-panel page (not just one route), mount a single Livewire component once in the shared layout — `resources/views/layouts/app.blade.php` — via `@livewire(\App\Livewire\Dashboard\CountdownBanner::class)`, inside the existing `@unless(View::hasSection('minimal_layout'))` block so minimal/auth layouts stay bare. Do NOT pepper every page with the component; one layout mount broadcasts it everywhere.

`CountdownBanner` is the reference. The pattern's rules:

- **Guest-guarded, cached lookup:** `render()` returns early with an empty placeholder for guests; for authed users it calls one cached model lookup (`Event::activeCountdownEvent()`, `Cache::remember` 60s, public upcoming + PHP-filtered `countdown.enabled`) and returns `event => null` when nothing is active — the blade renders an empty `<div wire:key>` so the slot collapses cleanly.
- **Per-user dismissal is a preference, not a column:** the dismiss action writes `preferences.countdown_dismissed_at` via the matched `getPreference('x')` / `setExtraValue('preferences.x', v)` pair on `User` (see the User-preferences note in filamentPattern), compared against `today()->toDateString()` so dismissal expires at local midnight and the banner re-shows the next day. `dismiss()` is guarded by `abort_unless(auth()->check(), 401)` and wraps `save()` in try/catch + `report($e)` so a preference write failure never 500s the page.
- **No new state on `Main`:** there is no Form Object / Action / Validator here — the component only renders and dismisses; the active-record selection lives in the model (cached), not the component.
- **Asset:** the confetti library is a vendored browser-global loaded once in the shared layout `<head>` (`<script defer src="{{ asset('assets/js/lib/confetti.browser.min.js') }}">` in `app.blade.php`), NOT inside the banner blade — the banner blade must keep a single root element (Livewire `RootTagMissingFromViewException`), so an inline `<script>` sibling is illegal there. Copied to `public/assets/js/lib/` via the `viteStaticCopy` `resources/assets/js` target (see assetPattern §10). The Alpine factory (`countdownBanner.js`, registered `Alpine.data('countdownBanner', …)`) reads the event payload via `@js($event['date_iso'])` / `@js($event['messages'])` / `@js($event['mood'])` / `@js($event['confetti'])` — never `{{ }}` in a JS string literal. Confetti renders to an in-card `<canvas x-ref="confettiCanvas">` via `window.confetti.create(canvas, {resize, useWorker:false})` (persol-style, behind content at `z-index:1`), gated by the `confetti` payload flag — mourning keeps confetti but `confettiColors()` switches the palette to black grays and the banner gets `countdown-banner--mourning` (floral icon + somber muted CSS variant). The countdown JSON column uses bare sub-keys (`enabled`/`mood`/`confetti`/`messages`) — never `countdown_*`-prefixed; `HasCountdown::packCountdown`/`unpackCountdown` are the canonical read/write, `activeCountdownEvent()` re-derives `mood`/`confetti` for the payload.

Wiring: `render()` passes `['presenter' => new FeedPresenter()]`. Because `timeline` → `item` → `header`/`comments`/`media` are all `@include`s, `$presenter` is inherited by every partial (Laravel `@include` forwards all current vars); no per-include plumbing needed. The Presenter is pure (no DB, no state): `pollResults/pollChoices/pollSettings` operate on eager-loaded `polls`/`poll_options`, avatar/online read eager-loaded `profile`/`last_seen`.

**Lazy comment render-gate:** comments were previously `@include`d for *every* feed inside an `x-show` (CSS-hidden but still server-rendered), so a feed with many comments rendered every card on initial load. Now `item.blade.php` gates the `@include` behind a Livewire flag: the toggle button does `@click="open = !open; if (open && !loaded) { $wire.openComments(feedId); loaded = true; }"` (Alpine tracks `open`/`loaded` locally; survives Livewire morph), and `@if($openedCommentFeeds[$feedId] ?? false)` wraps the `@include`. `openComments($feedId)` only flips the flag — comments stay eager-loaded in `feeds()`, so the badge count and all write-method refreshes (`addComment`/`deleteComment`/`updateComment` still call `unset($this->feeds)` → re-eager-load) are unchanged. Initial load now renders zero comment cards; only the opened feed's cards render, on the round-trip.

**`withCount` vs a scoped eager-load are independent counts.** `feeds()` eager-loads `comments` through a closure that scopes to top-level only (`fn($q) => $q->whereNull('parent_id')->latest()`), so `$feed->comments->count()` in the badge counted **top-level comments** — but the admin `FeedTablePresenter::commentsCount()` uses `->counts('comments')` over the unscoped base relation (`hasMany(Comment::class)->latest()`), which counts **all comments incl. replies**. The two numbers drifted. The fix is to add `->withCount('comments')` to the same `feeds()` query and read `$feed->comments_count` in the badge: `withCount` always counts the **base relation definition**, ignoring any eager-load closure scope, so `comments_count` matches admin exactly while the scoped `comments` collection still drives the visible thread (top-level + `children`). General rule: when a relation is eager-loaded with a display scope but you also need the *true* total for a badge/counter, add `withCount` — never derive the total from the scoped collection.

---

## Asset URL resolution convention

User-panel blades never call `Storage::` to build image/file URLs — that re-stats the filesystem per render. Files can live in two places (the `public` disk served at `/storage/...`, or hand-placed directly under `public/...` served as `/...`), so resolution is **two-tier**: storage-disk-first, `asset()` fallback. The single source is the `HasPublicAssetUrl` trait (`app/Models/Traits`): a `public static resolvePublicAssetUrl(?string $path): string` (blank→`''`, `http(s)://`/`//`→as-is, `Storage::disk('public')->exists()`→`->url()`, else `asset()`). Models expose cached accessors over it (`Post`/`Link::imageUrl`, `Photo::imageUrls`, `Feed::mediaUrls` — all `->shouldCache()`). Presenters that build display bags pull the trait in directly (`use HasPublicAssetUrl` + `self::resolvePublicAssetUrl($path)`) — `DocumentPresenter::parseAttachments`, `OnboardingPresenter` (videos/thumbnail/guides), `ContactPresenter::attachments`, `SuggestionPresenter::attachmentUrl`; elsewhere `Model::resolvePublicAssetUrl($path)` (`Ticket` files via `Ths/Main::resolveTicketFileUrls`). Distinct from `HasAvatar::resolveImageUrl` (prefix-routed avatars). The trait ships only a static method, so applying it to a model has **no Filament side effect** — no new virtual attribute is created; only models that own an image column add an accessor. `Storage::path()/exists()/download()/delete()` (DMS + Contact downloads, `DeleteProfileImageAction`) are filesystem I/O, not display URLs — left as-is. **Always name the disk explicitly on these calls** — `filesystems.default = local` (root `storage/app/private`, no `url`), so a bare `Storage::exists()`/`Storage::download()` checks the private disk and misses anything the admin/user panels saved to `storage/app/public`. `Reports::download()` was hitting exactly that: it used `Storage::exists()`/`Storage::download()` (default disk) for files the admin FileUpload saves to the `public` disk, so every admin-uploaded report fell through to the "not found" toast — fixed to `Storage::disk('public')->exists()/->download()` as tier 1, keeping the `storage/app/<path>` and `public/<path>` fallbacks for legacy/hand-placed files. Filament's `feed/video-preview` blade and the Filament-only `Message::attachmentUrls()` are out of scope.

---

## Persian (Jalali) date convention

Dates are **stored as Gregorian `Y-m-d`** and converted to Jalali only at the edges. This matches the Filament side (`PersianDateFieldService`), so a date saved in one panel round-trips correctly in the other.

- **On submit** (Action): assemble the Jalali year/month/day parts into Gregorian — `Jalalian::fromFormat('Y/m/d', sprintf('%04d/%02d/%02d', $y, $m, $d))->toCarbon()->format('Y-m-d')`. Wrap in `try/catch` returning `null`, since a UI that exposes a flat 1–31 day list can submit an invalid Jalali date.
- **On hydrate** (`mount`): convert stored Gregorian back to Jalali parts — `Jalalian::fromCarbon(Carbon::parse($value))` → `getYear()/getMonth()/getDay()`. The canonical `x-ui.forms.date` select **option values are non-padded** (`range`/`$i+1`/`range(1,31)` → `"5"`, not `"05"`), so hydrate parts must be non-padded to match the selected option — `getYear()/getMonth()/getDay()` (ints → `"5"`) land correctly; when splitting a zero-padded `Y-m-d` string (e.g. seeding `EventForm` from `selectedDate`), strip padding via `(string)(int)$part`. The assembled **submit** string is the zero-padded inverse (`sprintf('%04d-%02d-%02d', …)`). `Tab/Calendar` (`EventForm`) follows this triplet convention; its action reassembles `Y-m-d` then combines with `time` via `Jalalian::fromFormat('Y-m-d H:i', "$date $time")->toCarbon()` (date+time variant of the pure-date case).
- **Validation**: gate the parts all-or-nothing with `required_with`, and validate calendar validity with `CalendarUtils::checkDate($y, $m, $d, true)` (leap-year aware) inside a closure rule.
- **Display**: format with the `toJalali($value, 'Y/m/d')` helper — it is calendar-safe (returns already-Jalali strings untouched, converts Gregorian ones), so it tolerates legacy mixed-format rows. `toJalaliSmart($value)` is the "drop midnight" variant: it returns `Y/m/d` when the time is `00:00` and `Y/m/d H:i` otherwise — right for most date columns, but **wrong for all-day events**, where the user panel intentionally shows a uniform `00:00` chip (regular events render `Jalalian::fromCarbon($event->date)->format('H:i')`, so an all-day event stored at midnight shows `00:00`; birthdays/anniversaries hardcode `00:00`). To make the admin Event table/infolist match that "uniform 00:00" behavior, `EventTablePresenter::date()`/`EventInfolistPresenter::date()` use `toJalali($state, 'Y/m/d H:i')` (always shows the time) instead of `toJalaliSmart($state)` (drops it). Pick `toJalaliSmart` for normal date fields, the explicit `Y/m/d H:i` form when a `00:00` time is meaningful and must not be hidden.
- **Cross-panel consistency**: if a model exposes its own formatted-date accessor (e.g. `Task::createdFormatted`/`deadlineFormatted` via `$appends`), that accessor is the single source of truth for that field's display format — Blade reads it directly (`$task['created_formatted']`), and the matching Filament `TextColumn`/`TextEntry` must call the **same** accessor rather than calling `toJalali()` again with its own format string. See `filamentPattern.md` rule 32 — this is exactly how `TaskResource`'s admin table (`Y/m/d`) and the Kanban card (`j F Y`) drifted apart for the same two fields.
- **Font**: dates render in the **natural/system font, never `font-mono`**. Monospace is reserved for genuine code-like values — IDs/serials (`SN-20260101-000001`, ticket `#0001`), document codes/versions (`DOC-1 - 2.0`), credentials/passwords, emails, version badges (`β.5`), calculator/keycap displays, and numeric counters (`tabular-nums` counts/pagination, which is `font-variant-numeric`, not a mono family). Stripped `font-mono` off every calendar-date / event-time / timeline-date / release-date display so they inherit the body font; `tabular-nums` was left in place since it is not a monospace family.

## Calendar event sharing + StateService-driven notification

An event owner can share one of their events with one or more coworkers; chosen recipients get a menu badge driven by `StateService` (the same indicator→`BadgeSyncService` chain the Suggestion module uses). The badge is **pull-only**: it appears when the recipient next opens their menu, not pushed at share time. Sharing reuses the existing `StateService::flush()` — a **global cache-version bump only** (the `purgeMenuNotifications()` global wipe was removed; see badge-vs-notification below) — so every indicator recomputes on the recipient's next menu render. The sharer gets an in-app **toast** only; no persistent confirmation notification is written.

- **Data** — `event_shares(event_id, user_id recipient, shared_by, timestamps)` with `unique(event_id,user_id)`; `EventShare` model; `Event::shares()` HasMany. Shares are created **only by the owner** (guarded in `ShareEventAction` and again in `Calendar::openShareModal`/`sharingEvent`), so a recipient has a row only if the owner chose them — the visibility query `where user_id=me OR private=false OR whereHas shares user_id=me` cannot leak a private event to anyone else, and public events were already visible (sharing a public event is redundant but harmless).
- **Action** — `ShareEventAction::execute($eventId, $sharerId, $recipientIds)` owner-guards (`Event::where('user_id',$sharerId)->find`), drops the sharer and non-positive IDs, validates recipients against real `User` rows, then **diff-syncs** (`array_diff` add/remove against the current pluck) so re-saving an unchanged selection touches no rows. After the diff it calls `StateService::flush()` (global cache-version bump only) — one invalidation primitive, reused from the Suggestion module, no separate `invalidate()`/push and no row purge. Recipients are sourced from `User::getCachedActiveOptions()->except(auth)` (the same staff picker TaskBoard uses), passed as a Livewire array `shareRecipientIds`; pre-fill casts to `(string)` to match the checkbox `value=""` attributes (Alpine array binding uses strict `===`, so the model values must be strings to match the stringified value attrs). The bulk `insertOrIgnore()`/query-`delete()` the action uses deliberately bypass `EventShare` model events, then `flush()` explicitly — so the model `booted()` hooks (below) never double-fire on the user-panel flow. `insertOrIgnore` (not `insert`) makes the unique `(event_id,user_id)` constraint race-safe: a concurrent same-event share skips the duplicate row instead of throwing `QueryException`, and the returned row count drives `added` so an idempotent re-share reports `added=0` (no false toast). `Calendar::shareEvent` catches the intended `InvalidArgumentException` (owner guard → inline `@error('share')`, which the modal renders) and adds a `catch (\Throwable)` backstop that `report()`s + emits an error toast, so no exception ever surfaces to the user.
- **Sharer feedback (toast + self-nudge)** — the sharer gets the **immediate** toast below (action summary), and now also a **persistent self-nudge** bell row («رویداد شما به اشتراک گذاشته شد: X») via the per-record nudge layer — one per event, only while the event still has ≥1 share and is upcoming. `Calendar::shareEvent` maps the action summary to a success toast: `added>0` → «رویداد «X» با N نفر به اشتراک گذاشته شد.», `removed>0` → «اشتراک رویداد برای N نفر لغو شد.», else info «تغییری در اشتراک‌گذاری اعمال نشد.». `ShareEventAction` stays pure share-domain orchestration (owner-guard, diff-sync, `flush`) returning the summary the toast consumes — it does not build Filament `Notification`s itself; it just dispatches `ReconcileNudge('shared-events:nudge', Event::class, $event->id)->afterCommit()` after `flush()` so the nudge engine (not the action) writes the bell rows for both sharer and recipients. The full sharer-vs-recipient message + per-event keying logic is in `app/Services/Menu/statePattern.md`.
- **Notification (proximity badge, both parties)** — `Indicators\SharedEvents implements MenuBadge` (`key=shared-events`), registered in `StateService::$indicators`, **structurally identical** to `ActiveAds`/`PendingSuggestions` (no per-user method, no sub-interface). `isActive()` reads `auth()->user()` and lights for **both** sides of a share when the event is **imminent** (within 24h, not the whole upcoming span): recipient via `EventShare::hasImminentFor($user)` (`where('user_id', me)->whereHas('event', fn $q => $q->whereBetween('date', [now, now+24h])->exists()`), owner via `Event::hasImminentSharedFor($user)` (`where('user_id', me)->whereHas('shares')->whereBetween('date', [now, now+24h])->exists()`). Pure SQL `whereBetween`, no PHP loop; the query lives on the model that owns the data and the indicator is just a thin wrapper. The `title`/`body` are party-agnostic approaching-reminder text («رویداد مشترک نزدیک است» / «…در ۲۴ ساعت آینده است…») — the same message fits sharer and sharee. Deliberately **stateless** (no `read_at`): the badge naturally clears once the shared event leaves the 24h window, avoiding any write-side-effect in the read-only `selectedDayEvents` computed. The badge is **pull-only**: `flush()` bumps the global cache version, so each recipient's next `StateService::get()` recomputes `SharedEvents` and `BadgeSyncService::sync()` reconciles the per-user database notification — the same model `PendingSuggestions` uses. There is no eager push and no `isActiveFor` / `UserMenuBadge` (removed as redundant: `sync` already creates/clears the row on recompute). **Sync cadence**: `sync()` runs **once per version**, inside `StateService::get()`'s `Cache::remember` closure (only when the bool map is recomputed, i.e. on a cache miss) — not on every menu render. A cache hit therefore costs a single cache read and **zero DB queries** (previously `sync()` fired per render and issued SELECT + dedup-DELETE per active indicator). Each `sync()` call is wrapped in `try/catch` so a notification-DB failure degrades gracefully (stale badge until the next version bump) instead of breaking the menu render or poisoning the cached bool map. **Badge vs notification (two signals)**: the menu **dot** is driven by the cached `$menuState[key]` bool (`menu.blade.php` `@js($menuState)[item.id]`), so it stays lit the whole time the condition is true and is **not** dismissable — it is a permanent status signal, independent of the bell. The **bell entry** is the Filament DB notification row; it is a one-time **nudge**: it fires once (created unread) when the condition first becomes active, and the user can mark it read ("حذف اعلان" → `markAsRead`) to clear it from the unread bell. **`sync()` never re-unreads an existing row** — its entire logic is *active + no row → create; active + row exists → leave it alone (preserve `read_at`); inactive → delete*. So a dismissed nudge **never resurfaces** while the condition stays continuously active — `flush()` is a pure version bump (no purge), so the dismissed row survives every flush and `sync()` leaves it read. The old code both reset `read_at=null` on version change *and* globally purged all menu-badge rows on `flush()`; either alone re-added dismissed nudges to the unread bell, and both were removed. When the condition goes inactive the row is deleted; if it later re-occurs, a fresh unread row is created (a new nudge). No `version`/`cleared` bookkeeping fields are written anymore — the payload carries only `menu_key` plus the static title/body/action.
- **Model-side sync** — `EventShare::booted()` hooks `created`/`deleted` and `Event::booted()` hooks `updated`/`deleted` to call `StateService::flush()` (both wrapped in `DB::afterCommit()`), so shares and **event edits/deletes** through the **model** bump the version for the next recompute. `Event::booted()` makes the **badge** reflect an owner's date edit or deletion promptly (the nudge side was already covered by the `Event` trigger) instead of lagging the ≤2h TTL. The user-panel `ShareEventAction` bypasses these via bulk ops + its own explicit `flush()` (and short-circuits before it on an empty diff), so there is no double-fire.
- **Admin panel** — no standalone resource: an **`EventSharesRelationManager`** on `EventResource` renders a per-event "اشتراک‌های این رویداد" table (`recipient.name`, `sharer.name`, Jalali `created_at`). It is **read-only** (`isReadOnly()` → no create/edit/attach) because shares originate only from the user-panel flow; the one allowed write is a custom plain `Action::make('revoke')` (not a `DeleteAction`, so it is not caught by the read-only deny-list) that calls `$record->delete()` (firing the model `deleted` hook → `flush()` → the recipient badge clears on their next menu recompute). `canViewForRecord` hides the table on events with no shares, and the revoke button is gated by `EventResource::canDeleteAny()`.
- **UI** — `share.blade.php` is an `x-ui.modals.action` with the `modal-inner-card !w-full !max-w-none` + scoped-defer pattern (identical to `create.blade.php`); a checkbox list bound to `shareRecipientIds`. The owner-only `share` button sits next to edit/delete in `events.blade.php`; recipients see a `مشترک` chip via the `is_shared` flag (`$event->shares->contains('user_id', Auth::id())` after eager-load `with('shares:user_id,event_id')` to avoid an N+1 on the day list).
- **Calendar grid signs (shared + imminent)** — the month grid in `calendar/grid.blade.php` now marks shared days and imminent shared days so the calendar itself signals "we shared this" / "it's near", not just the sidebar. `Calendar::calendarDays` eager-loads `shares:user_id,event_id` and computes per day a `hasShared` flag (any event the auth user owns-with-shares OR is a recipient of) and a `hasImminentShared` flag (a shared event whose `date` is within `[now, now+24h]` — the same 24h window as the proximity badge). The cell appends a tiny `group` glyph after the `+N` counter: **outlined** in `--md-sys-color-secondary` for a shared-but-not-near day, **filled** in `--md-sys-color-error` when imminent; an imminent non-selected, non-today cell also gets a thin `ring-1 ring-[var(--md-sys-color-error)]/70`. The legend gains two chips (`مشترک` outlined-secondary, `نزدیک` filled-error) reusing the sidebar's existing `group`/secondary vocabulary so the grid, sidebar chip, and legend all speak the same language. Glyph/ring are suppressed on the selected and today cells to keep their distinct states readable.
- **Per-record nudges (`NudgeService`)** — a **separate, additive** bell-nudge layer alongside the badge/dot above: one new notification row **per qualifying record** (new active ad, suggestion entering an attention stage, new shared event) instead of one per indicator. Declarative registry + dumb engine — each rule is a `MenuNudge` class in `Notifications\` (declares key, triggers, show/for/title/body, refresh); `NudgeService::register(new ...Nudge())` binds its Eloquent events and reconciles per-record under a lock, `->afterCommit()`, with a `:nudge`-suffixed `menu_key` so it never collides with the badge namespace. One new class + one `register()` line adds a rule; the engine is never edited. Full mechanism + logic (both badge and nudge) is documented in `app/Services/Menu/statePattern.md` — read that before editing anything in `App\Services\Menu` or wiring a new trigger.

## Reservation date strip — policy-derived horizon, Jalali-month paged

The user-panel reservation date strip (`Livewire/Dashboard/Reservation/Main.php::availableDates`) is driven by **the same admin policy the validator enforces**, not a hardcoded horizon — one source of truth across both panels. It reads `window_days` from `ValidationService::getPolicies($activeTab)`, the exact call `TimeWindow` uses, so admin → `ReservationPolicy` → strip + validator cannot drift; the `ReservationPolicy::saved/deleted` boot flush (`flushPolicyCache`) keeps the policy cache in sync on admin edits.

- **`window_days` set** → the strip **pages by Jalali month** (`$currentYear`/`$currentMonth`, `nextMonth`/`prevMonth` with 1↔12 wrap — the same idiom as `Tab/Calendar`), rendering only days in `[today, now+windowDays]`. `canPrevMonth` hides + disables the back arrow past the current Jalali month (TimeWindow blocks past → ERR-019); `canNextMonth` hides + disables the forward arrow once the next month's first day exceeds the horizon (else ERR-003). The Blade renders `{{ $canX ? '' : 'hidden disabled' }}` so an unusable arrow is removed from interaction, and `nextMonth`/`prevMonth` re-check the same flag server-side, so a tampered client cannot navigate out of bounds. Every visible date is genuinely bookable — no clickable-but-failing dates, no second source of truth.
- **`window_days = null` (unrestricted)** → flat **21 days from today**, no month-jumper rendered (`@if($this->dateWindow !== null)` gate in `reservation/date.blade.php`).
- Jump is **view-only**: `$this->date` changes only via the existing `setDate()`, so browsing months never disturbs the booking flow or the resource panel. The within-strip scroll chevrons (`scrollPrev/scrollNext`, Alpine-only) are a separate affordance from the month-jump buttons (Livewire state).
- **Time picker** (`reservation/time.blade.php`, rendered only for the `meeting` tab — `@includeWhen($activeTab == 'meeting', ...)`) is likewise policy-driven from the same `getPolicies($activeTab)`: `availableTimeSlots()` reads `allowed_hours` (`['start','end']`) and renders 30-minute slots in `[start, end)`, falling back to 08:00–19:30 when unset or misconfigured (`end <= start`). This closes the drift the old hardcoded 08–19:30 had with `AllowedHours`, so the picker never offers a start/end slot the validator would reject. A `window_hours` notice badge (`minNoticeHours()` computed) renders above the pickers when `window_hours > 0`, stating the minimum lead time — informative only, it does not disable slots (passive-badge decision). Both read the same admin policy, keeping user + admin panels in sync. Full-day tabs (seat/spot/car) skip `AllowedHours` (the validator returns early for `isFullDay`), so the picker/badge are meeting-only; `window_hours` still applies to full-day bookings through `TimeWindow` (the `start` is `startOfDay`) but is not previewed on the date strip.
- **The remaining four policies are reflected the same way — each is a `#[Computed]` reading `getPolicies($activeTab)`, never a DB write, so admin stays the sole writer and user + admin cannot drift:**
  - **`allowed_days`** → the date strip skips disallowed weekdays via `allowedDays()` (normalizes to lowercase weekday keys matching `lang/.../strings.php` `days`, e.g. `saturday`), applied as one extra `continue` in **both** branches of `availableDates()` (flat-21 and windowed-month). It mirrors `AllowedDays` exactly: `null`/non-array ⇒ no filtering (validator accepts all), an array (even empty) ⇒ `in_array(strtolower($date->englishDayOfWeek), …)` — so the strip never shows a day the validator would reject (ERR-005) nor hides one it would accept. The window_days horizon itself is untouched; this is a purely additive filter.
  - **`min_duration_minutes` / `max_duration_minutes`** → a passive hint chip (`durationBounds()`) above the meeting time pickers, with three text forms (both ⇒ "مدت مجاز: N تا M دقیقه", min-only, max-only) and nothing rendered when both are unset. `Duration` still enforces; the chip only previews so the user picks a compliant length up front.
  - **`max_per_user`** → an active counter chip (`activeLimitUsage()`) under the date strip, mirroring `ActiveLimit`'s exact query (user_id, `whereHas('resource', type)`, Gregorian `whereMonth`/`whereYear` of the **selected** `$this->date`'s `start_time`, status `Active`+`Released`, `toBase()->count()`), returning `['count','max','near']` where `near = count >= max` is the same threshold the validator uses to throw ERR-012 — so the chip turns error-colored exactly when the next booking would be rejected. It is `null` when `max_per_user` is unset, and is invalidated on `setDate`/`switchTab` (it depends on `$this->date` + `$activeTab`).
  - **`allow_repeat`** → the recurring toggle (`recurring.blade.php`) is wrapped in `@if($this->allowsRepeat)`, where `allowsRepeat()` is `(bool)(policies['allow_repeat'] ?? true)` — the same default-true `Recurrence` uses, so the toggle hides only when the admin explicitly disabled recurrence. Because Livewire public state can go stale (user toggled recurring, admin later disabled it), `book()` builds the recurrence as `($this->isRecurring && $this->allowsRepeat) ? […] : null`, so a stale `isRecurring=true` can never reach `Recurrence` and throw ERR-010 — the validator remains the backstop, the guard prevents the surprise.
  - All five policy-derived computeds (`dateWindow`, `allowedDays`, `durationBounds`, `activeLimitUsage`, `allowsRepeat`, plus the earlier `availableTimeSlots`/`minNoticeHours`) are unset together in `resetFilters()` (called by `switchTab`); `setDate` additionally unsets `activeLimitUsage`. They reuse the cached `getPolicies()` (`Cache::remember`, flushed by `ReservationPolicy::saved/deleted`), so adding them costs one cache hit per computed, no extra policy reads.

### Reservation UX-batch — preview computeds (read-only hints, validator stays authoritative)

A second layer of `#[Computed]` hints previews *what the validator will do* without ever enforcing — the user picks compliant values up front, the validator remains the backstop. All reuse the same `getPolicies($activeTab)` source and the existing policy computeds, so they never add a second source of truth. They are unset alongside the policy computeds in `resetFilters()`/`invalidateResourceCache()`.

- **`recurPreview()`** — when `isRecurring && allowsRepeat`, mirrors `BookAction::generateOccurrences` exactly: `intervalDays = weekly ? 7 : 1`, `count = max(2, min(52, recurCount))`, dates from `$date` stepping the interval, each `ok = >= startOfToday && (dateWindow null || <= horizon endOfDay) && (allowedDays null || in_array(weekday))`. Rendered as chips in `recurring.blade.php` ("تاریخ‌های برنامه‌ریزی‌شده"); ok chips primary-container, !ok chips surface + strikethrough + `block` icon. Labeled *planned* — it cannot preview DB conflicts (overlap/availability), only policy/weekday/horizon, so a !ok chip means "the validator would drop this occurrence," and the action still skips failures with `continue` rather than aborting. Empty array when not recurring or `allow_repeat` off, so the chip block is `@if(!empty($this->recurPreview))`-gated.
- **`selectedDuration()` / `humanizeMinutes()`** — meeting-only; `minutes = start->diffInMinutes(end)`, `valid = minutes > 0 && within min/max_duration_minutes` (null bound ⇒ unbounded), text is `humanizeMinutes` ("<60 ⇒ "N دقیقه", exact hours ⇒ "N ساعت", else "N ساعت و M دقیقه", all `convertToPersian`'d) or "زمان پایان باید بعد از شروع باشد" when `<= 0`. Badge in `time.blade.php` above the pickers, error-colored when `!valid`. `null` for non-meeting tabs.
- **`bookingBlockReason()`** — the pre-submit guard rendered on each card (`image.blade.php`): returns the active-limit message when `activeLimitUsage['near']` (priority), else for meeting only, the end-before-start message when `minutes <= 0`, else `durationBounds` (or a generic out-of-bounds line) when `!valid`; `null` when bookable. The card shows the reason line + `disabled` submit button (`{{ $blocked ? 'disabled' : '' }}`). It composes only existing computeds (`activeLimitUsage`/`selectedDuration`/`durationBounds`), so across N cards it resolves once per render (Livewire `#[Computed]` caching) — the quota query runs once, not per card.
- **`startSlotMeta()`** — server-authoritative time-of-day dimming applied to **both** start and end pickers: today ⇒ each slot is `past` (`< now`), `soon` (`< now + window_hours` — the same minNotice `TimeWindow` enforces), or `ok`; a future date ⇒ all `ok`; returns `['states'=>[…], 'first'=>first-ok-time-or-null]`. The Blade reads `$state = $slotMeta['states'][$time] ?? 'ok'` for **both** pickers (end mirrors start): dimming end slots before `now + minNotice` is correct, since a valid end must be `> start ≥ now + minNotice`, so any end slot in the past/soon zone can never be a valid end. Blocked slots get `disabled` + line-through + a past/soon `title`; the selected-but-past slot keeps its blocked styling (not primary). Dimming is a **hint**, not a guard — `BookAction`/`TimeWindow` still reject past bookings (ERR-019) server-side; the server clock is authoritative, avoiding client-clock skew. Uses server `now` (not client), matching the validator.
- **`syncDefaultTimes()`** — called from `mount()` and `resetFilters()` (not per-render): picks a sane default start from `allowed_hours` + `window_hours`. Today ⇒ first 30-min slot `>= now + window_hours`; a future date ⇒ the first slot of the day; clamps end to `dayEnd` when the next slot would overshoot; returns early (keeps prior defaults) when no slot remains today (late evening) — `startSlotMeta` then dims the stale default as `past`, and the validator rejects it, so no invalid booking succeeds. Deliberately **not** called on `setDate` so a user's chosen time persists across date navigation (a chosen time valid for tomorrow stays put; on today it's dimmed if past).

### Reservation UX-batch — Blade affordances

- **Quota progress bar** (`reservation.blade.php`) replaces the active-limit text chip: `pct = max>0 ? min(100, round(count*100/max)) : 100`, track `h-1.5 bg-surface-variant`, fill `width:%` `bg-primary` or `bg-error` when `near` — a glanceable "N / M this month" with the bar going red exactly at the rejection threshold (ERR-012).
- **"امروز" pill** (`date.blade.php`) — `wire:click="goToday"` next to the month-jumper, `disabled` + opacity-40 when `$this->date` is already today. `goToday()` resets `date` to today, snaps `currentYear/Month` to now, unsets the date computeds (it routes through `setDate`, so resource + quota caches invalidate too), and dispatches `scroll-to-selected`. The date strip's scroll container carries `@scroll-to-selected.window="$el.querySelector('[data-selected]')?.scrollIntoView({inline:'center',block:'nearest',behavior:'smooth'})"`; the selected date button carries `data-selected`. So clicking today (enabled only when `date != today`, avoiding a pagination reset when already on today) smoothly swipes the strip back to center the current date — the Livewire browser event fires after the morph, so the newly-selected button is found. The same direct-`x-data`-in-HTML rule applies: Alpine `.window` listeners live on elements inside the root `x-data="reservation()"` tree, never emitted through `{{ }}`.
- **Staggered entrance** — cards (`image.blade.php`) and history rows (`history.blade.php`) get `animate-slide-up-fade` + `animation-delay: {index * 0.04|0.05}s` for a cascade on initial render. **History rows animate on insertion**, not on every render: Livewire morph reuses existing row nodes by `wire:key` (moves them, no re-animation), while a freshly booked reservation is a *new* node morph inserts — so its entrance animation runs exactly once, the robust "booked" confirmation without any event wiring (the earlier `resource-booked` dispatch was removed as dead — no listener, and a card-pop-on-success is impossible anyway since the booked card is removed from the available list in the same response).
- **Scroll-to-first-ok** — the start picker's scroll container carries a direct `x-data="{ init() { this.$el.querySelector('[data-first-ok]')?.scrollIntoView({inline:'center',block:'nearest'}) } }"` (Alpine auto-calls `init()`). `data-first-ok` is emitted only on the start picker's first-ok button, so the end picker's `init()` is a harmless no-op (no matching child ⇒ `?.` short-circuits), and a no-ok day (all past) scrolls nothing. **Write `x-data` directly in HTML, never through `{{ }}`** — Blade's `{{ }}` HTML-escapes `"`/`'` to `&quot;`/`&#039;`, which breaks the attribute and silently kills the Alpine init; the direct-attribute form matches the `image.blade`/`cards.blade` `x-data` convention.

## Reservation asymmetry-batch — released history, cancel-limit preview, enum source-of-truth, permission-tab-gating

Four interconnected asymmetries closed so the user + admin panels and the validator share one source of truth.

- **Released history tab (#1)** — released reservations still count against the monthly quota (`activeLimitUsage` already queries `Active`+`Released`, unchanged) but had nowhere to be *seen*. Add a 4th history tab: `getHistoryTabs()` gains `['id'=>'released','icon'=>'autorenew','label'=>'آزادشده']`; `historyReservations()`/`totalHistoryReservations()` gain a `'released' => $query->released()` match branch (ordered by `start_time` desc); the new `Reservation::scopeReleased` is `where('status', ReservationStatus::Released->value)`. The pre-existing `scopeCancelled` was later converted too — `whereIn('status', [ReservationStatus::CancelledUser->value, ReservationStatus::CancelledAdmin->value])` — since the enum values are byte-identical to the old raw strings (`cancelled_user`/`cancelled_admin`, verified), the SQL is unchanged and the enum/string drift risk closes with zero behavior change. `history.blade.php` gains an `@elseif($activeHistoryTab === 'released')` indicator (neutral `surface-variant` + `autorenew`, no cancel button — a released record is not active and not cancellable) and the empty-state `match` adds `'released' => 'autorenew'`. `switchTab` already validates any tab via `array_column(getHistoryTabs(),'id')`, so the new tab is wired with no extra guard.
- **Cancel-limit preview (#2)** — `max_cancel_count` was enforced only by `CancellationLimit` (ERR-011) at submit time, with no pre-submit hint. Added `cancelLimitUsage()` — a `#[Computed]` that **mirrors `CancellationLimit::validate` exactly**: `null` when `max_cancel_count` unset (preview off), else `limit = max(1,(int)limit)`, `count = Reservation::user_id + whereHas(resource,type) + status=CancelledUser + (cancelled_at null OR >= now->subDays(30)) + toBase->count()`, returning `['count','max','blocked'=>count>=limit]` (the same `>=` threshold that throws). It is wired into `bookingBlockReason()` as a second priority check (after `activeLimitUsage['near']`, before the meeting-duration checks) so a user at the cancel limit sees the block line + a disabled book card on **every** tab — not just meeting — because `CancellationLimit` applies to all types. `$this->activeTab` substitutes for the validator's `$context->resource->type`, equivalent since every resource in a tab is of that tab's type. Invalidated in `resetFilters()` (depends on `$activeTab`).
- **Post-mutation invalidation** — `book()` and `cancel()` previously invalidated nothing, so `#[Computed]` caches went stale after a mutation: a just-booked resource stayed in the available list (re-click → `ResourceTaken`), and `activeLimitUsage`/`historyReservations`/`bookingBlockReason`/`cancelLimitUsage` didn't refresh. Added `invalidateAfterMutation()` (called on the success path of both, inside the try so a thrown validator/action error skips it): it calls `invalidateResourceCache()` (resets `resourcesLimit` to 6 + unsets `resources`/`totalResources`/`recurPreview`/`selectedDuration`/`bookingBlockReason`/`startSlotMeta`) then unsets `activeLimitUsage`/`cancelLimitUsage`/`historyReservations`/`totalHistoryReservations`. This makes the cancel-limit preview stay accurate after a cancel (the one in-session event that changes the count) and fixes the pre-existing book/cancel staleness.
- **ResourceType enum as single source of truth (#6)** — the 4-type list was duplicated in `Resource::getTabs()` (hardcoded), `Main::timeRange()` (`in_array($activeTab,['seat','spot','car'])` for full-day), and `ReservationTablePresenter::resourceTypeFilter()` (hardcoded options), so adding a type meant 3 edits. `App\Enums\ResourceType` now owns it: `tabs()` returns `[['id'=>value,'icon'=>getMaterialIcon(),'label'=>getLabel()]]` from `cases()`, `isFullDay()` is `$this !== Meeting`, and `getMaterialIcon()` gives the Material Symbol per case. `Resource::getTabs()` delegates to `ResourceType::tabs()`, the `icon` accessor uses `ResourceType::tryFrom($this->type)?->getMaterialIcon() ?? 'chair'`, `Main::timeRange()` uses `ResourceType::tryFrom($this->activeTab)?->isFullDay() ?? false`, and the filter options are `collect(ResourceType::cases())->mapWithKeys(fn($t) => [$t->value => $t->getLabel()])`. The per-type `'meeting'` conditionals in Main/Resource/EventSyncService now read `ResourceType::Meeting->value` (rename-safe). Per-type **field-visibility** conditionals (e.g. `@includeWhen($activeTab == 'meeting', time)` / `ResourceFormPresenter` `in_array`) stay as literals — they are field-specific display logic, not a duplicated type list, so converting them would add Blade `@php` ceremony without removing duplication.
- **Permission-tab-gating (#7)** — `Main::render()` now filters `ResourceType::tabs()` through `userCanBook($type)` before passing to the tab selector, hiding any type the user lacks booking permission for. `userCanBook()` mirrors `BookingPermission` exactly: `($booking['all'] ?? false) === true || ($booking[$type] ?? false) === true` — the same strict `=== true` the validator uses, so a tab hidden in the UI is exactly one the validator would reject (`NoPermission` backstop remains). `User::booking` (the `Attribute` that flattens the DB `[{key,value}]` format into `[type=>bool]` + the `all` key) is the single permission source. `mount()` now calls `ensurePermittedTab()` **first** (before `resetMonthCursor`, preserving the cursor-before-`availableDates` invariant): if the current `activeTab` (from `#[Url] ?tab=` or the `'seat'` default) isn't `userCanBook`-permitted, it jumps to the **first** permitted type from `ResourceType::tabs()`, so a meeting-only user no longer lands on a filtered-out `seat` tab with no tab highlighted and unbookable seat content. A user with **zero** booking permission keeps `activeTab` as-is (no permitted type exists) — `render()` then filters every tab out, the tab bar is empty, and any book attempt is still server-rejected by `BookingPermission` (`NoPermission` backstop) — defense in depth, minimal behavior change.
- **Recurring-series grouping (#5)** — a recurring series previously rendered as one row **per occurrence** in every history tab, and canceling one warned nothing. `historyReservations()` now loads the full per-user tab scope (still `with('resource')`, no query-level `limit`), then `groupBy(fn($r) => $r->parent_id ?? $r->id)` so the master (parent null, key = own id) and its occurrences (parent_id = master id) collapse into **one row per series**, representative = `$group->first()` (the row already first in the tab's query order — earliest for `upcoming`, latest past for `previous`, most-recent for `cancelled`/`released`). The representative carries a runtime `series_count` attribute (`$group->count()`, the **tab-scope** count — accurate per tab) rendered as a `repeat` + `×N` badge in `history.blade.php` when `> 1`; an orphan occurrence (master deleted, parent_id points to a gone row) keys to its parent_id and stands alone (`series_count=1`, no badge). `totalHistoryReservations()` mirrors this at the DB with `selectRaw('COUNT(DISTINCT COALESCE(parent_id, id)) as total')->value('total')` so the load-more gate counts **series**, not raw rows. The cancel confirmation becomes dynamic: `cancelWarningFor($rep, $count)` returns a warning **only** when `count > 1` **and** `allow_partial_cancel` is false (read from `getPolicies($rep->resource->type)`, cached per type) — because `CancelAction` cancels only the clicked reservation when `allow_partial_cancel` is true (the default) and the whole active series when false, so a warning would be *wrong* by default. The message is deliberately **count-free** ("تمام رزروهای این سری تکرارشونده را لغو می‌کند"): a hard count would understate (an active series also has past-active members in the `previous` tab) or overstate (orphaned occurrence, master deleted → `CancelAction` cancels only one), so only the `×N` badge carries a number; the warning states the invariant ("the whole series") which is true in normal operation. `history.blade.php` injects the dynamic title/message via `@json(...)` (valid JS string literal) and keeps `params: {{ $reservation->id }}` raw — canceling the representative (master or earliest occurrence) routes through `CancelAction::seriesReservations` which resolves the full series from `parent_id`. `cancel_warning` is now set on the representative **only when `activeHistoryTab === 'upcoming'`** (the sole tab where the cancel button reads it) — zero observable change, but it skips the cached `getPolicies` call per group on the other 3 tabs. **Tradeoff**: grouping loads the full per-user tab scope instead of `limit(N)`; this is bounded by `max_per_user`/usage (a per-user set, not a log/audit table), so it is acceptable — the performant alternative (SQL window functions `ROW_NUMBER() OVER (PARTITION BY COALESCE(parent_id,id))`) is the documented next step if a user ever has pathological volume.

## Reservation smart-default date + safe dedupe batch

A two-part change to `Main` initialization, validated by an adversarial 3-subagent unanimous-confidence gate (dispatch 3 independent `general-purpose` agents with the exact diff + framing, implement only if unanimous ≥99%; re-dispatch on a no-output glitch). The dedupes were the no-op half (all 3 passed 99%+); the smart default is an intentional behavior change.

- **Smart default date (`mount`)** — `mount()` now sets the date from `$this->availableDates[0]['value'] ?? now()->toDateString()` instead of always `now()->toDateString()`. When today's weekday is disallowed by `allowed_days`, the default becomes the **first allowed future day within the horizon**; when today is allowed, it stays today (byte-identical to the old behavior); when **no** allowed day fits the horizon (e.g. window=2, next allowed is +3d), it falls back to today via `??`. The load-bearing detail — and what the first validation round unanimously flagged — is **ordering**: `resetMonthCursor()` **must run first**, because `availableDates()`' windowed branch reads the typed-no-default `public int $currentYear; public int $currentMonth;` (`new Jalalian($this->currentYear, $this->currentMonth, 1)`). The old order (date-first) accessed `availableDates` before the cursor was set → `Error: Typed property must not be accessed before initialization`, swallowed by `availableDates`' `catch(Throwable)` → returned `[]` → fallback today → the feature was silently **inert** for windowed tabs. Correct order: `resetMonthCursor() → smart date → syncDefaultTimes()`. `availableDates` never reads `$this->date` (no circular dependency — verified in both branches), and `#[Computed]` access in `mount()` is fine because Livewire hydrates `#[Url] activeTab` **before** `mount()` runs, so the cache is keyed on the correct tab; `switchTab`→`resetFilters` unsets `availableDates`/`dateWindow`/`allowedDays` so first-load cache is never stale. `goToday()` is intentionally **not** changed — its `now()->toDateString()` is correct because "today" is by definition allowed on that path; the smart default only applies to initial load.
- **Dedupe A — `allowedHoursBounds()`** — `availableTimeSlots()` and `syncDefaultTimes()` both parsed `allowed_hours['start'??'08:00']` / `['end'??'20:00']` with the same `if ($end <= $start)` wraparound guard. Extracted to `private function allowedHoursBounds(?array $allowed): array` returning `[$startCarbon, $endCarbon]`; both callers now do `[$start, $end] = $this->allowedHoursBounds($policies['allowed_hours'] ?? null)`. Verified byte-identical: old inline slot output === new helper output across 6 `allowed_hours` shapes (null, normal, wrapped, missing start, missing end, equal). Zero logic change.
- **Dedupe B — `forUser` scope alignment** — `activeLimitUsage()` and `cancelLimitUsage()` both wrote `Reservation::where('user_id', auth()->id())`. Switched both to `Reservation::forUser(auth()->id())` (the existing `scopeForUser` is exactly `where('user_id', $userId)` with an `int` typehint). Identical query, just consistent with the rest of the file which already uses the scope.
- **Dedupe C — `resetMonthCursor()`** — `mount()`, `goToday()`, and `resetFilters()` each inlined `Jalalian::now()` → `getYear()`/`getMonth()`. Extracted to `private function resetMonthCursor(): void`; called in each, preserving the exact original order relative to surrounding lines (cursor-then-date in mount, date-then-cursor in goToday, etc.). `Jalalian::now()` reads the real clock each call, so extracting it changes nothing about timing.

A standalone diff harness (replicated `availableDates` exactly, ran old-vs-new `mount` across 7 scenarios) confirmed: today-allowed → identical (A/C/E); today-disallowed + next-in-horizon → NEW jumps to that future day (B/D/F); today-disallowed + none-in-horizon → NEW falls back to today == OLD (G). 3 changed, 4 identical — exactly the intended shape.

## Card meta-row pattern (report + gallery)

The report **grid card** (`tab/reports/cards.blade.php`) is the canonical split: a `flex flex-col justify-between` info panel holding a title+description block, then a meta row separated from it by a divider. The meta row is exactly `flex justify-between items-center pt-3 border-t border-[var(--md-sys-color-outline-variant)]/20 mt-3` — the `border-t` **is the divider** — with a **plain** date `<span dir="rtl" class="text-xs text-[var(--md-sys-color-on-surface-variant)] opacity-70">` (no chip) on the start side and the action button (download) on the end side. Description is `text-[var(--md-sys-color-on-surface-variant)] text-xs line-clamp-2 leading-relaxed font-light opacity-80` with `Str::limit(strip_tags(...), 100)`. The report **list card** (`tab/reports/list.blade.php`) is the horizontal variant: `[thumb] | [title → description → meta(chip + date)] | [download]`, meta row `flex items-center gap-3 text-xs text-[var(--md-sys-color-outline)]` with a chip (`bg-[var(--md-sys-color-surface-container-high)] px-2 py-0.5 rounded`) + `dir="rtl"` date, no divider. The gallery card (`tab/gallery/item.blade.php`) **adapts** the grid card rather than copying it: the date stays in the **header** under the title (`text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]`, the small elegant spot it always had), and only the description moves below a divider at the bottom — `pt-3 border-t border-[var(--md-sys-color-outline-variant)]/20` then the same description classes (`text-xs line-clamp-2 leading-relaxed font-light opacity-80`, `Str::limit(strip_tags(...), 100)`). The description+divider block is conditional on `!empty($photo->description)` (no dangling divider when a photo has no description); there is no meta-row date and no download button (gallery opens in Fancybox). Reuse the grid-card divider + description classes for consistency; only the date placement differs (gallery keeps it in the header, report keeps it in the meta row).

The gallery card header icon is **not** decorative — `GalleryPresenter::scopeMeta(Photo)` (`app/Livewire/Dashboard/Tab/Presentation`) classifies the photo's department scope and returns `['icon','label']`, mirroring the admin `GalleryTablePresenter::department()` three-way rule so both panels agree: `count($photo->all_departments) > 1` → multi (`groups`, `resources/gallery/strings.filters.multiple_departments`), `=== 1` → single (`lock`, `...filters.single_department`), `=== 0` → public (`public`, `...fields.public_gallery`). It uses the `all_departments` accessor (department_id + departments JSON merged/deduped) rather than the literal "department_id filled / departments > 1" rule, because the latter leaves gaps (e.g. department_id null + a single departments entry). `Gallery::render()` passes `['presenter' => new GalleryPresenter()]`; because gallery → timeline → item are all `@include`s, `$presenter` reaches `item.blade.php` with no per-include plumbing, and the icon box reads `$scope = $presenter?->scopeMeta($photo)` (null-safe with a `photo_library` fallback if ever included without the presenter). The icon box carries `cursor-help` + `title="{{ $scope['label'] }}"` so the scope label is discoverable on hover. General rule: when a user-panel card icon encodes a classification the admin already computes, put the logic in a tab Presenter that mirrors the admin presenter — single source of truth, no classification duplicated in Blade.

## Universal aggregate fold (UnreadNotifications)

`UnreadNotifications extends Filament\Notifications\Livewire\DatabaseNotifications` and folds the bell modal so >4 unread rows sharing a `data.menu_key` collapse to one or two **synthetic** `DatabaseNotification` rows rendered natively by Filament's modal — a UI-side anti-flood cap ("security lock to not overwhelm users"), zero engine change, applies to **every** nudge key (DMS, posts, tasks, feeds, …), not only DMS. Admin panel is fully untouched: `isPaginated()` returns true only for the `admin` panel, so both `getNotifications()` and `getUnreadNotificationsCount()` early-return the parent result; the non-admin `getNotificationsQuery()` adds `->unread()`.

- **Fold rule** (`getNotifications`) — `countBy` the parent collection by `data.menu_key`; iterate (parent relation is `morphMany->latest()`, so first-encountered = most-recent). Per group: `count <= AGGREGATE_THRESHOLD(4)` ⇒ keep individual; `>4` ⇒ emit one aggregate and skip the rest (`$emitted[$key]` dedupe). **DMS** is special-cased to **two** aggregates — `agg:dms-controller:nudge:sign` (count = `DMS::needsSignCount`) and `agg:dms-controller:nudge:read` (count = `DMS::needsReadCount`) — so sign-pending and read-pending show as distinct nudges. **Every other** over-threshold group ⇒ one generic aggregate titled «{count} اعلان جدید», body `«آخرین: {most-recent row title}»` (the "آخرین:" prefix is load-bearing — without it the body reads as "5 of that one item"; with it, "latest is X"). **DMS both-zero guard**: if `sign===0 && read===0` but stale rows exist (the `afterCommit` reconcile window before `ReconcileNudge` clears them), do **not** fold — keep the individual rows and skip the count adjustment, so `list == count == bell-dot` (the dot is a separate raw `unreadNotifications()->count()` and would otherwise stay lit while the modal went empty).
- **Header count** (`getUnreadNotificationsCount`) — start from `parent::getUnreadNotificationsCount()`, then a grouped SQL (`->reorder()->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.menu_key')) as menu_key, COUNT(*) as cnt")->groupBy('menu_key')`) adjusts each over-threshold group: DMS ⇒ `+= (sign>0?1:0)+(read>0?1:0) - cnt` (skipping the adjustment on the both-zero guard); generic ⇒ `+= 1 - cnt`. `->reorder()` is mandatory — the `notifications()` relation is `morphMany->latest()`, so an inherited `ORDER BY created_at` collides with `groupBy` under `sql_mode=only_full_group_by` (1055). `max(0, …)` floor.
- **Synthetic row** (`makeAggregateRow`) — `new DatabaseNotification(['id' => 'agg:{key}[:{mode}]', 'type' => FilamentDatabaseNotification::class, 'data' => Notification::make()->title()->body()->warning()->persistent()->getDatabaseMessage(), 'read_at'=>null, 'created_at'=>now()])`. The **deterministic `agg:` id** is load-bearing: `Notification::fromDatabase` sets `getId()` from `DatabaseNotification::getKey()`, and the inline view builds `wire:key = "{id}.notifications.{id}"` — a `Str::uuid()` id regenerates every render ⇒ `wire:key` churns ⇒ Alpine re-mounts + replays the enter transition every 30s poll; the stable `agg:` id stops the flicker. No `actions` key ⇒ no per-row action buttons, but the **close X is unconditional** in the inline render (`x-on:click="close"` → `notificationClosed` with `notification.id`), so dismissing a synthetic via the base `removeNotification` would delete-by-id (0 rows) and the row would reappear next render (vanish-then-return).
- **Honest dismiss** (`removeNotification` override, `#[On('notificationClosed')]`) — `str_starts_with($id,'agg:')` ⇒ parse the `menu_key` out of the id (strip `agg:` prefix, then strip a trailing `:sign`/`:read` mode suffix — only DMS uses a mode and no nudge key ends in `:sign`/`:read`, so `preg_match('/:(?:sign|read)$/')` is unambiguous) and `$this->getNotificationsQuery()->where('data->menu_key',$key)->update(['read_at'=>now()])` — **mark-read, never delete** (non-destructive), so the aggregate stays dismissed (those rows leave the unread set). Real rows fall through to `parent::removeNotification`. DMS dismiss marks the whole DMS group read (sign+read rows share one `menu_key`, the sign/read split is computed at render from the `reads` table, not stored per row) — acceptable because the **DMS badge (Signal 1, pull-based `hasPendingFor`)** persists independently of the bell rows, so the pending-work signal is not lost.
- **`dmsCounts` instance memo** — `DMS::needsSignCount`/`needsReadCount` are plain statics (no `once()`) called in **both** `getNotifications()` and `getUnreadNotificationsCount()` on the same render; `once()` won't share across two call sites (distinct closure objects). `protected ?array $dmsCountsCache = null` + `dmsCounts(int $userId): array` (`??= [needsSignCount, needsReadCount]`) memoizes per-render — Livewire does not persist protected properties across requests, so it resets cleanly each request and the second call site is free.
