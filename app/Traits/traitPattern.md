# Trait Pattern Guide

The single source of truth for **when shared logic becomes a trait instead of a Service**, and which of the three trait categories in this codebase a new one belongs to. Read this before adding any file to `app/Traits/`, and read `app/Services/servicePattern.md` first if the question is "trait or service" — they are complementary, not competing.

---

## 1. The one rule that decides everything

> **A trait exists because the shared logic must execute *inside* the consuming class — it needs `$this` to already be that class.** If the logic could run standing alone, given plain arguments, it is a Service, not a trait.

This is a hard boundary, not a style preference:

- A **Service** method is called as `app(Foo::class)->bar($x, $y)` from anywhere — a Livewire component, a Job, a Console command, another Service.
- A **trait** method is compiled directly into the consuming class. It can read `$this->composer`, call `$this->dispatch(...)`, declare a `#[Computed]` property, or hook a Livewire lifecycle method (`updated()`, `mount...()`) — none of which a Service can do, because a Service is never "the component itself."

**Correcting a common misreading of the Action/Service rule (§ of `servicePattern.md`):** "shared across modules → Service" applies to portable write/query logic. It does **not** apply to Livewire-component behavior. `App\Traits\ChatComposer` is shared by Channel and Contact and is correctly a trait, not a Service — moving it to a Service would strand its `#[Computed] groupedMessages()` and `dispatch('attachments-updated')` calls, which only make sense as literal members of a Livewire component. The same is true for `FocusOnRecord` (used by Channel, Contact, TaskBoard, Project, Ths) and `ManagesTaskModal` (TaskBoard, Project). Being reused across modules is necessary but not sufficient for "should be a Service" — the deciding question is always "does this need to be inside the class."

---

## 2. Three trait categories in this codebase — know which one you're writing

### 2.1 Livewire-component behavior traits
Mixed directly into a `Livewire\Component` class. Hold `#[Computed]` properties, `dispatch()` calls, `#[Js]` methods, or lifecycle hooks (`updated()`, `mount...()`). **Cannot be Services** — see §1.

| Trait | Shared by | What it holds |
|---|---|---|
| `ChatComposer` (53 ln) | `Channel\Main`, `Contact\Main` | `updated()` hook on `composer.attachments`, `syncAttachments()`, `removeAttachment()`, `#[Computed] groupedMessages()`, `#[Computed] lastMessageId()`, `#[Js] cancelReply()` |
| `FocusOnRecord` (52 ln) | `Channel`, `Contact`, `TaskBoard`, `Project`, `Ths` | `#[Url] $open`, `mountFocusOnRecord()` deep-link dispatch, `isFocusing()`, `clearFocus()` — the `?open={id}` global-search/menu deep-link contract every focusable module implements |
| `ManagesTaskModal` (325 ln) | `TaskBoard\Main`, `Project\Main` | The unified task create/edit modal — form state, checklist/label array sync, tab badges, attachment staging |

### 2.2 Presenter/view-shaping mixins
Composed into multiple **Presenter** classes (stateless, `new Presenter()` per render — see `livewirePattern.md`), not into the Livewire component itself. Technically *could* be Services (no Livewire dependency), but stay traits because Presenters already compose several of these fluently (`use HasAvatar, BuildsChatBubbles;`) and call each other's methods via `$this->` inside one shaping pass — injecting a Service per presenter method would fragment that into DI ceremony for zero behavioral gain.

| Trait | Shared by | What it holds |
|---|---|---|
| `BuildsChatBubbles` (62 ln) | `ChannelPresenter`, `ContactPresenter`, `ProjectPresenter` | `bubbleRadius()`, `attachments()`, `linkify()`, `replyPreview()` — the four universal chat-bubble primitives |
| `BuildsMessageGroups` (133 ln, `use BuildsChatBubbles`) | `ChannelPresenter`, `ProjectPresenter` — **deliberately NOT** `ContactPresenter` (its `read_at`-boolean read model and lack of `@mention` don't fit the per-member-cursor `readersMap` shape this trait assumes; see `contactPattern.md` §16C.5) | `messageGroup()`, `messages()` (per-message view row: is_mine/is_first/is_last/can_edit/can_delete/readers/mentions_you), `readerSummary()` |
| `HasTimelineMonths` (18 ln) | Timeline-style module presenters (Links/Gallery/Feeds — see `project_timeline_modules_second_view`) | `months()` — buckets a collection into distinct "Month Year" labels, sorted desc |
| `RiskEscalationChip` | `ProjectPresenter`, `TasksheetPresenter`, `TicketPresenter` (Ths) | `riskToneClasses(string $tone)` — the success→warning→error chip-tone primitive extracted from `ProjectPresenter`'s header risk/deadline chips so `TasksheetPresenter`'s project-health chip and `TicketPresenter`'s `deadlineChip()` reuse the exact same escalation classes, not a second copy; see `app/Livewire/Dashboard/Tasksheet/tasksheetPattern.md` |

**Rule for this category:** before composing a new one of these into a fourth presenter, re-check the assumptions the trait bakes in (e.g. `BuildsMessageGroups`'s per-member cursor) actually hold for the new consumer — Contact's exclusion above is the proof this isn't automatic.

### 2.3 Model traits
Mixed into Eloquent models, not Livewire components or Presenters. Static or instance helpers tied to a model's own lifecycle hooks (`booted()`, `forceDeleted`).

| Trait | What it holds |
|---|---|
| `CleansAttachedFiles` (101 ln) | `deleteStoredFiles()`, `deleteStoredDirectory()` — safe (path-traversal-guarded) storage cleanup called from a model's `forceDeleted`/`deleted` boot hook. Used by `Channel`, `ChannelMessage`, etc. |
| `StoresAttachedFiles` | `storeAttachment()` — normalizes an `UploadedFile` into the canonical `{path, name, mime, size}` shape (see `project_attachment_writers_footprint` memory); `storeAttachments(array $files, string $directory)` — batch wrapper with rollback (`deleteStoredFiles` on partial failure). **Composition requirement:** the rollback calls `CleansAttachedFiles::deleteStoredFiles()`, so any class calling `storeAttachments()` must use both concerns (every current caller does). |

### 2.4 Cross-domain mixins (Auth / Filament — out of scope for the Livewire-dashboard modules, listed for completeness)
`AuthValidationRules`, `AuthorizesByPermission` (the one-true-admin-model `permits()` gate — see `skillsPattern.md`'s "Ownership-bypass" note), `InteractsWithNotifications`, and the ten `Filament*` traits (`FilamentActions`, `FilamentAdminGuide`, `FilamentDateHandler`, `FilamentEditHeading`, `FilamentFilters`, `FilamentFormDivider`, `FilamentHeaderActions`, `FilamentIconOptions`, `FilamentPageBehavior`, `FilamentPreferences`) are admin-panel/Resource/Page mixins governed by `app/Filament/filamentPattern.md`, not this doc.

### 2.5 Small single-concern helper traits
Sometimes a trait exists purely to DRY two Actions in the *same* module without becoming a full Service (§ of `servicePattern.md` — no second module needs it, no non-Livewire caller exists). Example: `ResolvesTaskDeadline` — two private methods shared between the create/update Task Actions: `resolveDeadline(TaskForm $form): ?Carbon` (the form's Jalali Y/M/D fields into a Carbon instant, always at 12:00:00) and `guardProjectDeadline(?Carbon $deadline, ?Project $project): void` (rejects a deadline past the project's `deadline` setting with the `form.deadline` validation error — both Actions call it, the update path included, so the cap can't be dodged by editing). This is legitimately a trait-sized helper, not a Service-in-waiting — promote it only if a second module needs the same conversion/guard.

---

## 3. Where traits live (convention differs from Actions/Services)

Unlike Actions (nested per-module under `app/Livewire/Dashboard/{Module}/Actions/`) and Services (grouped per-domain under `app/Services/{Domain}/`), **traits live flat in `app/Traits/`** regardless of how many modules consume them — even a trait used by exactly one module today (e.g. a future TaskBoard-only Livewire mixin) still goes in `app/Traits/`, not inside the module folder. This is existing, consistent convention — don't nest a new trait under a Livewire module folder to "match" the Actions pattern.

**The one exception: Eloquent-only model traits live in `app/Models/Concerns/`** (namespace `App\Models\Concerns`), not `app/Traits/` — and every file there is named `Has*` (`HasModelCache`, `HasReplies`, `HasMenuState`, …). A trait consumed **only** by classes under `app/Models/` goes there; cross-domain traits that models share with Filament/Resource classes (e.g. `CleansAttachedFiles`, `StoresAttachedFiles`, consumed by both `App\Models\*` and `App\Filament\*`) stay in `app/Traits/` under this doc's rules.

---

## 4. Do / Don't

**DO**
- Ask "does this need `$this` to already be the consuming class" before reaching for a trait — if no, it's a Service (`servicePattern.md`).
- Put a new Livewire-component-behavior trait in `app/Traits/` flat, even for a single current consumer.
- Re-verify a Presenter mixin's baked-in assumptions before composing it into a new Presenter (§2.2).
- Keep Model traits scoped to model-lifecycle concerns (storage cleanup, static query scopes) — not business rules that belong in an Action.

**DON'T**
- Don't treat "used by multiple modules" alone as proof something belongs in `app/Services/` — Livewire-bound behavior stays a trait no matter how many modules mix it in (§1).
- Don't add Livewire-specific code (`dispatch()`, `#[Computed]`, Form properties) to a class under `app/Services/` — that class is secretly a trait that hasn't been renamed yet.
- Don't create a per-module trait subfolder — traits are flat in `app/Traits/` by convention (§3).
- Don't assume a two-Action helper trait like `ResolvesTaskDeadline` should immediately become a Service — wait for a real second module, same as the Action→Service rule.

---

## See also
- `app/Services/servicePattern.md` — Action vs Service, the interface pattern, and the layering rule these traits sit alongside.
- `app/Livewire/livewirePattern.md` — `@island`/`#[Computed]` scope rules that Livewire-component traits (§2.1) must respect.
- `app/Livewire/Dashboard/Contact/contactPattern.md` §16C.5 — the worked example of a Presenter mixin's assumptions NOT transferring to a new consumer.
