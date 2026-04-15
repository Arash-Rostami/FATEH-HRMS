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

    if (!$user->isAdmin() && $reservation->user_id !== $user->id)
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

---

## What NOT to do

- Do not create Actions for read operations — those belong in `#[Computed]`
- Do not pass `$this` into an Action
- Do not put `dispatch()` or `redirect()` inside an Action
- Do not create a Presenter for values already available as model attributes or computed properties
- Do not create a Form Object for state that is never submitted (search, pagination, tab — these stay on `Main`)
- Do not create a Validator for Actions with only 1–2 simple guards — inline them
- Do not over-split: if a component has one simple form and no formatting helpers, Form Object + Action is enough — no Presenter or Validator needed
