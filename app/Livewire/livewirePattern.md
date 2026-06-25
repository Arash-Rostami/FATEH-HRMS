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
- `mediaGrid($media)` → `['items','images','cols','rows']`.
- `categoryValue($category)` normalizes the raw-DB-string `category` (not cast to the `FeedCategory` enum) using the codebase idiom `($category?->value ?? $category)` — note `?->value` on a non-null string returns `null` in PHP 8 (no throw), so the `?? $category` fallback recovers the string. Used by `categoryEmoji`/`feedFlags` instead of repeating the idiom in Blade.

Wiring: `render()` passes `['presenter' => new FeedPresenter()]`. Because `timeline` → `item` → `header`/`comments`/`media` are all `@include`s, `$presenter` is inherited by every partial (Laravel `@include` forwards all current vars); no per-include plumbing needed. The Presenter is pure (no DB, no state): `pollResults/pollChoices/pollSettings` operate on eager-loaded `polls`/`poll_options`, avatar/online read eager-loaded `profile`/`last_seen`.

**Lazy comment render-gate:** comments were previously `@include`d for *every* feed inside an `x-show` (CSS-hidden but still server-rendered), so a feed with many comments rendered every card on initial load. Now `item.blade.php` gates the `@include` behind a Livewire flag: the toggle button does `@click="open = !open; if (open && !loaded) { $wire.openComments(feedId); loaded = true; }"` (Alpine tracks `open`/`loaded` locally; survives Livewire morph), and `@if($openedCommentFeeds[$feedId] ?? false)` wraps the `@include`. `openComments($feedId)` only flips the flag — comments stay eager-loaded in `feeds()`, so the badge count and all write-method refreshes (`addComment`/`deleteComment`/`updateComment` still call `unset($this->feeds)` → re-eager-load) are unchanged. Initial load now renders zero comment cards; only the opened feed's cards render, on the round-trip.

---

## Persian (Jalali) date convention

Dates are **stored as Gregorian `Y-m-d`** and converted to Jalali only at the edges. This matches the Filament side (`PersianDateFieldService`), so a date saved in one panel round-trips correctly in the other.

- **On submit** (Action): assemble the Jalali year/month/day parts into Gregorian — `Jalalian::fromFormat('Y/m/d', sprintf('%04d/%02d/%02d', $y, $m, $d))->toCarbon()->format('Y-m-d')`. Wrap in `try/catch` returning `null`, since a UI that exposes a flat 1–31 day list can submit an invalid Jalali date.
- **On hydrate** (`mount`): convert stored Gregorian back to Jalali parts — `Jalalian::fromCarbon(Carbon::parse($value))` → `getYear()/getMonth()/getDay()`.
- **Validation**: gate the parts all-or-nothing with `required_with`, and validate calendar validity with `CalendarUtils::checkDate($y, $m, $d, true)` (leap-year aware) inside a closure rule.
- **Display**: format with the `toJalali($value, 'Y/m/d')` helper — it is calendar-safe (returns already-Jalali strings untouched, converts Gregorian ones), so it tolerates legacy mixed-format rows.
- **Cross-panel consistency**: if a model exposes its own formatted-date accessor (e.g. `Task::createdFormatted`/`deadlineFormatted` via `$appends`), that accessor is the single source of truth for that field's display format — Blade reads it directly (`$task['created_formatted']`), and the matching Filament `TextColumn`/`TextEntry` must call the **same** accessor rather than calling `toJalali()` again with its own format string. See `filament.md` rule 32 — this is exactly how `TaskResource`'s admin table (`Y/m/d`) and the Kanban card (`j F Y`) drifted apart for the same two fields.
