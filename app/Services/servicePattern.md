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

Confirmed in this codebase: `ReorderTaskAction` calls `RankGenerator`, and `SaveEditReplyAction`/`DeleteReplyAction`/`ToggleReplyReactionAction` call `ProjectHeartbeat` — all four now live in `App\Services\ProjectTask\` alongside the Services they call, since all four also turned out to be genuine Services themselves (§4 below covers why). The layering still holds one level down: `TaskBoard\Actions\CreateTaskAction`/`UpdateTaskAction`/etc. (the ones with exactly one caller) are the ones that should call into `App\Services\ProjectTask\*` for portable sub-logic when it exists — that shape is what to replicate.

```
Livewire Main.php  →  Action (Livewire glue, one module)  →  Service (portable, ≥2 callers)  →  Model
```

---

## 3. Interface-based Services — when a Service needs polymorphism

Use an interface **inside** a Service namespace only when the same operation has genuinely different implementations selected by a runtime value (an enum, a type flag) — not just because "interfaces are good practice."

**Worked example (already in production, copy this shape):** `App\Services\ProjectTask\Contracts\ActivityLogRenderer`

```php
interface ActivityLogRenderer
{
    public function getIcon(Reply $reply): string;
    public function getLabel(): string;
    public function getBody(Reply $reply): string;
}
```

Twelve implementations under `App\Services\ProjectTask\Renderers\` — `CommentRenderer`, `StatusChangeRenderer`, `AssignmentRenderer`, `ArchiveRenderer`, `AttachmentRenderer`, `ResponsibleChangeRenderer`, `DepartmentChangeRenderer`, `StateChangeRenderer`, `DeadlineChangeRenderer`, `PriorityChangeRenderer`, `LabelChangeRenderer`, `ProjectChangeRenderer` — one per `TaskActivityType` enum case. `ActivityLogger::render(Reply $reply)` looks up the class by `$reply->type->value`, memoizes the resolved instance in a static array, and delegates. Adding a ninth activity type means adding one Renderer class and one array entry — nothing else changes.

**The tell that you need this pattern:** a bare `if ($isPersonalBoard) { ... } else { ... }` (or any boolean/enum-branch) scattered across multiple methods doing structurally the same thing with a different data source. That branch is the interface's two implementations, not yet extracted. (Concrete, unresolved instance of this in the current codebase: `taskboard/card.blade.php`'s `$isPersonalBoard` flag and `TaskBoard\Main::schemeOptions()/assigneeOptions()` duplicating the shape of `ReportingService::filterOptions()` scoped differently — candidate for a `BoardScope` interface with `PersonalBoardScope`/`ProjectBoardScope` implementations. The action-source pair `actionSourceDomainOptions()`/`actionSourceOptions()` is no longer part of this candidate set — the report tab dropped those filters, so they have no shared counterpart to collapse into; see the §4 gap note.)

---

## 4. Worked example: `App\Services\ProjectTask\*` — read this before assuming a Service is module-private

**Most files under `App\Services\ProjectTask\` are consumed by both `TaskBoard\Main.php` and `Project\Main.php`** (grep-verified) — the namespace is named after the shared domain (Task + Project activity/reporting logic), not either module, precisely so its name doesn't mislead about scope the way `App\Services\TaskBoard\` used to (renamed 2026-08-29, see the former "known naming debt" note this section used to carry). Do not assume a class here is single-module-private without checking consumers first.

| Class | What it does | Consumers |
|---|---|---|
| `ActivityLogger` | Polymorphic activity-feed writer/reader (`comment()`, `system()`, `render()`, `feedFor()`) over any `Model $repliable` | Task **and** Project (both `repliable`) |
| `Contracts/ActivityLogRenderer` + `Renderers/*` | Strategy pattern, one renderer per `TaskActivityType` — see §3 | via `ActivityLogger::render()` |
| `ReportingService` (926 lines) | Shared report/analytics query engine — `query()`, `rows()`, `summary()`, `schemeProgress()`, `filterOptions(int $projectId)` (returns only `assignees`/`departments`/`schemes` — labels + action-source options were dropped when those filters were removed from the report tab), `analyticsInsights(int $projectId)` (3-tab Flow/Risk/People insights for Project's analytics tab — superseded and removed the old `statusBreakdown`/`priorityBreakdown`/`departmentBreakdown`/`schemeCompletion` 2×2 grid, which were dead code with no callers), `boardDetailOptions()`/`boardActionSourceDomainOptions()`/`boardActionSourceOptions()` (TaskBoard-only) | TaskBoard's own report needs (still hand-rolled inline, not yet routed through this — see gap below) **and** Project's `report`/`analytics` tabs |
| `RankGenerator` | Pure LexoRank-style fractional-ranking algorithm for drag-drop reorder (`between()`, `rebalanceInsert()`, `sequence()`) — zero model coupling | `ReorderTaskAction`; anywhere ordered lists need insert-without-renumber |
| `MentionResolver` | `@name` parsing/highlighting over a participant collection | Task replies **and** Project activity comments |
| `ProjectHeartbeat` | Tiny static cache-version bump/read (`project:{id}:v`) — cross-tab "something changed" signal | Bumped by `ActivityLogger`, `EventSyncService`; read by Project's `tabDirty` badges |
| `EventSyncService` | Syncs a Task's deadline to the Calendar `Event`/`EventShare` system | TaskBoard's deadline-set path |
| `ChannelProvisioner` | Creates/resolves the real `Channel` backing a Project's "team chat" tab | **Project only** — never touches `Task` |
| `CyclePriorityAction`, `ReorderTaskAction`, `UpdateTaskStatusAction`, `ToggleReplyReactionAction`, `SaveEditReplyAction`, `DeleteReplyAction` | Single-purpose Task/Reply mutations (priority cycling, drag-reorder, status move, reaction toggle, comment edit/delete) | TaskBoard **and** Project — relocated here from `TaskBoard\Actions\` after both callers were confirmed (§1 rule 1); the last three explicitly branch on `repliable_type` (`Task::class`/`Project::class`), so they were written polymorphic from day one and simply hadn't been moved to match |
| `BoardCollaboratorResolver` | `resolve(EloquentCollection $tasks): array` — batches a board's `detail->collaborators` ids into one `User::whereIn()` lookup, returns `[id => {id, name, avatar_url}]` | `TaskBoard\Main` (via `ManagesTaskModal::collaboratorLookup()`, now a one-line delegator) **and** `Project\Kanban` (calls it directly, since `Kanban` deliberately does not mix in `ManagesTaskModal` — see `projectPattern.md`'s Phase 3 Kanban-split section) |
| `CreateProjectAction` | `execute(ProjectForm $form): Project` — validates + creates a Project, filtering `memberIds` to existing users; `create(name, members, departments, settings)` — the primitive; `resolvePendingProject(TaskForm): ?int` — the inline "create pending project from the task form" seam (the single source for the guard + `create()` call both task Actions previously duplicated) | `Project\Main::createProject()` (original caller) **and** `TaskBoard\Main::createProjectFromTask()` (the "turn task into project" level-up tool) — relocated here from `Project\Actions\` after the second caller was confirmed (§1 rule 1); `resolvePendingProject` called by `CreateTaskAction`/`UpdateTaskAction` |
| `CreateTaskAction` / `UpdateTaskAction` | Full task create/update orchestration: `$form->validate()` + `$form->validateAttachments()` (field-level rules live ON `TaskForm`, per livewirePattern's form-object rule — these Services never build a `Validator` themselves), project-visibility/deadline-cap guards, stale guards (update), rank recompute, detail `updateOrCreate`. Shared helpers are single-sourced elsewhere: `storeAttachments` → `StoresAttachedFiles` concern, `resolvePendingProject` → `CreateProjectAction` above. **Documented exception to §2/§5's "Services never take Form objects":** both take `TaskForm` — kept because both modules call them and the form IS the payload carrier; the coupling is trigger-and-read: they trigger the form's own `validate()` (rules live on the Form, never defined in the Service) and read its fields — they never `dispatch()`, render, or hold Livewire state of their own | `TaskBoard\Main` **and** `Project` (via `ManagesTaskModal`) — the reason they're Services, not `TaskBoard\Actions\` |
| `ApproveTaskAction` | `execute(Task, User): bool` — the approval lifecycle's single writer; re-checks `TaskAccessPolicy::canApprove` + `isPendingApproval` inside `execute()` (the line-166 rule), stamps `approved_at`/`approved_by`, logs the `Approval` activity | Born here (never an `Actions/` class): three callers from day one — `ManagesTaskModal::approveTask()`, `Project\Kanban::approveTask()` (thin delegate), `TaskResource`'s admin record action |
| `LastTouchResolver` | `resolve(array $taskIds): array` — latest user-reply-per-task in one batched query (`whereIn('id', Reply::selectRaw('MAX(id)')->…->groupBy('repliable_id'))` subquery — one row per task, NOT hydrate-all-then-discard) + one `User` names query; returns `[task_id => {user_id, user_name, created_at}]` | `TaskBoard\Main::loadTasks()` **and** `Project\Kanban::kanbanBoard()` — the `BoardCollaboratorResolver` splice precedent, one call per board load |
| `ProjectSettings` | `bag()` — normalizes the 4 settings keys (`requires_approval`/`sla`/`deadline`/`custom_schema`) into the persisted `projects.settings` JSON | `ProjectForm` create/update **and** admin `ProjectFormPresenter::settings()` — one normalizer for both panels |

**Known gap:** `TaskBoard\Main::loadTasks()` (kanban-grouped, paginated-per-column, rank-ordered) is a genuinely different query shape from `ReportingService`'s flat report rows — do **not** force these two together, that difference is real. `TaskBoard\Main`'s `schemeOptions`/`assigneeOptions` `#[Computed]` methods still duplicate `ReportingService::filterOptions()`'s query shape with a different scope (personal tasks vs. `project_id`) — that duplication should collapse into one scoped call (§3). The action-source pair (`actionSourceDomainOptions`/`actionSourceOptions`) is now TaskBoard-only: the report tab dropped those filters and `filterOptions()` no longer returns them, so there is no shared counterpart to collapse into — leave them on `Main` (+ the underlying `boardActionSourceDomainOptions()`/`boardActionSourceOptions()` on this service) until/unless a second consumer reappears.

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
- Don't assume a namespace like `App\Services\ProjectTask\` is module-private without checking consumers first — §4 is the proof this assumption already failed once.
- Don't duplicate a Service's query shape in a Livewire component's own computed properties just because the scope differs slightly — parameterize the Service instead (§3's `BoardScope` gap).

---

## See also
- `app/Traits/traitPattern.md` — when shared logic must be a trait instead of a Service.
- `app/Livewire/livewirePattern.md` — `@island`/`#[Computed]` rules for the Livewire layer these Services are called from.
- `app/Livewire/Dashboard/Channel/channelPattern.md`, `.../Contact/contactPattern.md`, `.../Project/projectPattern.md` — module docs that consume this layer.
- `App\Services\ProjectTask\TasksheetService` — event-sourced (not current-status) two-method locked contract (`report()`/`activityFeed()`) shared by the Livewire page and its export action; see `app/Livewire/Dashboard/Tasksheet/tasksheetPattern.md`.
- `App\Services\ProjectTask\TasksheetShareService` — manager-resolution + signed-link share notification, two callers (`Tasksheet\Main` and admin `UserResource`); see `app/Livewire/Dashboard/Tasksheet/tasksheetPattern.md`'s "My manager" section.
