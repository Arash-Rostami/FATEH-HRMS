# Service Layer Pattern Guide

The single source of truth for **when logic becomes a Service class**, where it lives, and how it relates to Livewire `Actions/` and `app/Traits/`. Read this before adding any class to `app/Services/*` or before deciding "does this need its own class at all."

---

## 1. The one rule that decides everything

> **A Service holds logic that doesn't need to be inside a specific Livewire component and is (or will genuinely be) used by more than one caller.** If neither is true, it isn't a Service yet.

Three tiers exist in this codebase, and each solves a different problem — they are not interchangeable:

| Tier | Lives in | Needs `$this` to be a Livewire component? | Reusable across modules? |
|---|---|---|---|
| **Action** | `app/Livewire/Dashboard/{Module}/Actions/` | No (plain invokable, injected via method DI) | No — one write, one module |
| **Service** | `app/Services/{Domain}/` | No — plain PHP, callable from anywhere (Actions, Jobs, Commands, other Services) | Yes — proven or structural, not hypothetical |
| **Trait** | `app/Traits/` | **Yes**, when it shares Livewire-bound behavior (`#[Computed]`, `dispatch()`, lifecycle hooks) | Yes, but only mixable — see `app/Traits/traitPattern.md` |

**Default is Action.** A brand-new feature that only one module needs starts in that module's own `Actions/` folder — full stop. It is promoted to a Service only when one of these becomes true, not "might become true":

1. A second module already calls the same logic (proven, not anticipated).
2. The logic has no Livewire dependency at all and could run from a queued Job, an Artisan command, or another Service.
3. The logic needs interface-based polymorphism (see §3).

Promoting prematurely is not "cleaner architecture" — it is an indirection with a single caller. Wait for the second real caller.

---

## 2. The Action → Service layering (this already exists, keep doing it)

An Action is the Livewire-facing glue: it validates/shapes input from a Form object, calls a Service (or a Model directly) for the actual write, then handles the Livewire-specific side effects (`dispatch()`, toast messages). A Service never wraps that back around — Services do not call Actions, and Services do not know about Livewire, Forms, or `#[Computed]` properties.

Confirmed in this codebase: `TaskBoard\Actions\ReorderTaskAction`, `SaveEditReplyAction`, `DeleteReplyAction`, and `ToggleReplyReactionAction` all call into `App\Services\TaskBoard\*` for the portable part (rank math, activity logging), keeping the Action itself thin. That is the correct shape — replicate it, don't flatten it.

```
Livewire Main.php  →  Action (Livewire glue, one module)  →  Service (portable, ≥2 callers)  →  Model
```

---

## 3. Interface-based Services — when a Service needs polymorphism

Use an interface **inside** a Service namespace only when the same operation has genuinely different implementations selected by a runtime value (an enum, a type flag) — not just because "interfaces are good practice."

**Worked example (already in production, copy this shape):** `App\Services\TaskBoard\Contracts\ActivityLogRenderer`

```php
interface ActivityLogRenderer
{
    public function getIcon(Reply $reply): string;
    public function getLabel(): string;
    public function getBody(Reply $reply): string;
}
```

Eight implementations under `App\Services\TaskBoard\Renderers\` — `CommentRenderer`, `StatusChangeRenderer`, `AssignmentRenderer`, `ArchiveRenderer`, `AttachmentRenderer`, `ResponsibleChangeRenderer`, `DepartmentChangeRenderer`, `StateChangeRenderer` — one per `TaskActivityType` enum case. `ActivityLogger::render(Reply $reply)` looks up the class by `$reply->type->value`, memoizes the resolved instance in a static array, and delegates. Adding a ninth activity type means adding one Renderer class and one array entry — nothing else changes.

**The tell that you need this pattern:** a bare `if ($isPersonalBoard) { ... } else { ... }` (or any boolean/enum-branch) scattered across multiple methods doing structurally the same thing with a different data source. That branch is the interface's two implementations, not yet extracted. (Concrete, unresolved instance of this in the current codebase: `taskboard/card.blade.php`'s `$isPersonalBoard` flag and `TaskBoard\Main::schemeOptions()/assigneeOptions()/actionSourceDomainOptions()/actionSourceOptions()` duplicating the shape of `ReportingService::filterOptions()` scoped differently — candidate for a `BoardScope` interface with `PersonalBoardScope`/`ProjectBoardScope` implementations.)

---

## 4. Worked example: `App\Services\TaskBoard\*` — read this before assuming a Service is module-private

Despite the namespace, **every file under `App\Services\TaskBoard\` is already consumed by `Project\Main.php` too** (grep-verified). The namespace name is a naming *debt*, not a scope boundary — do not assume "TaskBoard" in the path means "only TaskBoard may call it."

| Class | What it does | Consumers |
|---|---|---|
| `ActivityLogger` | Polymorphic activity-feed writer/reader (`comment()`, `system()`, `render()`, `feedFor()`) over any `Model $repliable` | Task **and** Project (both `repliable`) |
| `Contracts/ActivityLogRenderer` + `Renderers/*` | Strategy pattern, one renderer per `TaskActivityType` — see §3 | via `ActivityLogger::render()` |
| `ReportingService` (336 lines) | Shared report/analytics query engine — `query()`, `rows()`, `summary()`, `schemeProgress()`, `filterOptions()`, `statusBreakdown()`, `priorityBreakdown()`, `departmentBreakdown()`, `schemeCompletion()` | TaskBoard's own report needs (still hand-rolled inline, not yet routed through this — see gap below) **and** Project's `report`/`analytics` tabs |
| `RankGenerator` | Pure LexoRank-style fractional-ranking algorithm for drag-drop reorder (`between()`, `rebalanceInsert()`, `sequence()`) — zero model coupling | `ReorderTaskAction`; anywhere ordered lists need insert-without-renumber |
| `MentionResolver` | `@name` parsing/highlighting over a participant collection | Task replies **and** Project activity comments |
| `ProjectHeartbeat` | Tiny static cache-version bump/read (`project:{id}:v`) — cross-tab "something changed" signal | Bumped by `ActivityLogger`, `EventSyncService`; read by Project's `tabDirty` badges |
| `EventSyncService` | Syncs a Task's deadline to the Calendar `Event`/`EventShare` system | TaskBoard's deadline-set path |
| `ChannelProvisioner` | Creates/resolves the real `Channel` backing a Project's "team chat" tab | **Project only** — never touches `Task` |

**Known naming debt, flagged not yet fixed:** `ChannelProvisioner` is pure Project logic living under a `TaskBoard` namespace — it is the strongest evidence that this namespace has outgrown its name. When this layer is next touched, rename `App\Services\TaskBoard\` → a neutral domain name (e.g. `App\Services\Board\`) so the namespace matches the proven cross-module reality instead of implying a scope it no longer has.

**Known gap:** `TaskBoard\Main::loadTasks()` (kanban-grouped, paginated-per-column, rank-ordered) is a genuinely different query shape from `ReportingService`'s flat report rows — do **not** force these two together, that difference is real. But `TaskBoard\Main`'s four filter-option `#[Computed]` methods (`schemeOptions`, `assigneeOptions`, `actionSourceDomainOptions`, `actionSourceOptions`) duplicate `ReportingService::filterOptions()`'s query shape with a different scope (personal tasks vs. `project_id`) — that duplication should collapse into one scoped call (§3).

---

## 5. Do / Don't

**DO**
- Start every new write operation as an Action in its own module's `Actions/` folder.
- Promote to a Service only on a proven second caller, a non-Livewire caller, or genuine polymorphism.
- Keep Services free of Livewire concerns — no `dispatch()`, no `#[Computed]`, no Form objects. If a "Service" needs those, it's actually Livewire-bound behavior — see `app/Traits/traitPattern.md`.
- Name the Service namespace after the **domain**, not the module that happened to write it first, once a second module depends on it.
- Reach for an interface only when there's real per-case behavior branching (§3), keyed off an enum or explicit type — not preemptively.

**DON'T**
- Don't create a Service for a single-caller operation "for cleanliness" — that's premature abstraction (see `feedback_minimal_creative_solutions`).
- Don't let a Service call back into an Action or a Livewire component — the dependency direction is one-way (Livewire → Action → Service → Model).
- Don't assume a namespace like `App\Services\TaskBoard\` is module-private without checking consumers first — §4 is the proof this assumption already failed once.
- Don't duplicate a Service's query shape in a Livewire component's own computed properties just because the scope differs slightly — parameterize the Service instead (§3's `BoardScope` gap).

---

## See also
- `app/Traits/traitPattern.md` — when shared logic must be a trait instead of a Service.
- `app/Livewire/livewirePattern.md` — `@island`/`#[Computed]` rules for the Livewire layer these Services are called from.
- `app/Livewire/Dashboard/Channel/channelPattern.md`, `.../Contact/contactPattern.md`, `.../Project/projectPattern.md` — module docs that consume this layer.
