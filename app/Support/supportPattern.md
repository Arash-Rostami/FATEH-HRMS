# `app/Support` — the access policies and the project charter

Static, framework-light rule classes. Every screen, action, bulk path, report, calendar, badge and the admin panel asks THESE, never a hand-rolled check. Authorization lives here; lifecycle lives in model hooks (`Task::booted`, `TaskDetail`, `Project::booted`); metrics live in `App\Services\ProjectTask\TaskMetrics`.

Files: `TaskAccessPolicy`, `ProjectAccessPolicy`, `TicketAccessPolicy`, `SuggestionAccessPolicy` (unchanged, review-stage gate), `ProjectCharter`, plus the Filament helpers `FaultTolerantGlobalSearchProvider` / `FilamentPanelCustomizer` (see `app/Filament/filamentPattern.md`).

## 1. `TaskAccessPolicy` (final, all static, user nullable -> false)

Roles it knows: creator (`user_id`), assignee (`assigned_to`), collaborator (`task_details.collaborators`), accountable (`task_details.responsible_user_id`), project owner (via `ProjectAccessPolicy::canManageTasks`).

| Verb | True for |
|---|---|
| `canView` / `canReply` | creator, assignee, collaborator, accountable, or anyone who can see the task's project (so project members and the project owner see AND reply to task comments) |
| `canChangeStatus` / `canEdit` | not a ticket mirror task, and worker (assignee; creator when unassigned; project owner) or collaborator — collaborators CAN move status |
| `canReassign` | worker; or the accountable taking it for himself |
| `canMoveProject` | creator or project owner, and not cycle-locked |
| `canDelete` | not a ticket mirror task, creator or project owner, and not cycle-locked |
| `canArchive` / `canUnarchive` | creator, assignee or project owner; archive additionally needs `done` and not pending approval |
| `canUndoAssignment` | the creator who delegated it away |
| `canApprove` | project owner (or any admin-panel session); the accountable only when the project has no active owner |
| `assignableIds` / `assertAssignable` | people allowed on a task: the project's charter roster when it has a project, else `User::visibleOnBoard()`; `assertAssignable` throws a `ValidationException` (also called from `Task::saving` and `TaskDetail::saving`, so every writer is covered) |

Cycle lock (`Task::isCycleLocked()`): the CURRENT step task of an ACTIVE cycle can be neither deleted nor moved to another project; past-step tasks stay free. Ticket mirror tasks (`ticket_id`) are locked to the ticket (`Task::guardTicketLock`); only `Task::syncingFromTicket()` bypasses it.

## 2. `ProjectAccessPolicy`

`canView` (= `Project::isVisibleTo`), `canManageAudience` (project owner OR an admin-panel session), and `canManageTasks` (alias of `canManageAudience`, added 2026-10-09 — the owner's task powers: create/take/reassign/reopen/archive/delete/approve and move tasks of his project). Call `canManageTasks` for task rights and `canManageAudience` for member/department rights so the two can diverge later without a sweep.

## 3. `TicketAccessPolicy`

`canView` (elevated roles, requester, assignee, or a HEAD of the target department — heads see every department ticket in any status), `canReply` (view and not closed), `canAssign` (requester only if he is a head; elevated or head otherwise), `canWithdraw` (requester, open/in-progress), `canReroute` (not closed; elevated or head), `canRelease` (the assignee of an in-progress ticket, or a head of its target department), `canSetEffectiveness` / `canClose`. A closed ticket is FINAL: it cannot be reopened or reassigned (`Ticket::saving` throws), withdrawn tickets never ask for a rating. "Head" = `User::isHeadOfDepartment` (ties-aware, cached — modelPattern Tier V).

## 4. `ProjectCharter` (final; `Project::charter()` memoizes one instance per Project, reset on save)

The project governs people, dates and labels for its tasks. Zero migrations: everything reads `projects.settings` JSON through the instance.

- Roster: `rosterQuery()` = `User::visibleOnBoard()` where owner OR `member_ids` OR profile department in `departments`; `rosterIds()` (memoized, one query), `rosterOptions()` ([id => name] for pickers), `isMember()`. Used for assignment validation, task/cycle pickers, @mention list, header member count, project chat audience (`SyncProjectChannelMembershipJob`), owner succession (`Project::ensureActiveOwner`).
- Dates: `cap()` (settings.deadline end of day), `slaHours()`, `defaultDeadline($from)` (SLA default clamped to the cap; only when the user gave no deadline), `clamp()` (cycle population only), `assertDeadline($deadline, $current, $key)` — the cap is checked ONLY when the deadline actually changed or the project changed, so legacy over-cap tasks stay editable.
- Labels: `labels()` is the project vocabulary (`settings.labels`); `assertLabels($new, $old, $key)` is STRICT when the vocabulary is non-empty and validates only labels ADDED since the task's current ones (legacy labels survive); empty vocabulary = free text. `normalizeLabels()` (static) folds Arabic ي/ك to Persian and dedupes.
- Other knobs: `requiresApproval()`, `customSchema()`, `autoArchiveDays()` (default 45; `ArchiveStaleDoneTasks` reads it), `escalationDays()` (default 3, 0 = off; overdue escalation to the accountable), `dueSoonHours()` (default 24), `defaultDepartment()` (the single department when the project has exactly one; applied on create only).
- `Project::KNOWN_SETTING_KEYS` = requires_approval, sla, deadline, custom_schema, labels, auto_archive_days; `Project::SETTING_LABELS` names the extra keys (`escalation_days`, `due_soon_hours`) for the settings chips; `Project::pruneSettings` drops empty values on save.
- Null project (personal task) means the caller skips the charter entirely.

## 5. `FileNamer` (2026-10-14) — the one upload-naming standard

`FileNamer::prefix()` = `strtoupper(config('app.tenant'))` (the `config/tenants.php` array key, never a hardcoded brand). `FileNamer::stored($module, $file)` returns `{PREFIX}-{MODULE}-{Ymd}-{random12}-{time}.{ext}` with the ext mime-guessed (`extension()`, client ext only as fallback) — the variables are defined first, the return composes them, and callers pass it as the `$fileName` closure of `storeAttachment()`/`storeAttachments()` (resolved BEFORE `storeAs()`, per livewirePattern.md's metadata-before-store rule) or from a Filament `getUploadedFileNameForStorageUsing`. `FileNamer::download($ext, ...$parts)` builds the ASCII-safe name a user receives (`PERSOL-CODE-v2-excel-2.xlsx`), derived at serve time so old files get it too. Adopted by DMS (prefix only — `DmsFormPresenter::forgeFileName` keeps its own variable block plus slug), THS, release request, report, suggestion, task, resource, feed, gallery, post, link, onboarding. Deliberately NOT applied to private uploads (profile docs/images, chat attachments). Never rename or rewrite stored paths: old records keep their old names.

## 6. Rules

- A new "who may do X" question becomes a policy verb here first, then every caller (Livewire action, Kanban, bulk action, Filament page/action, search) uses it. Filament actions additionally require the panel's `update` permission (`filamentPattern.md`).
- Never read `settings.deadline|sla|labels|auto_archive_days` directly; go through the charter.
- Keep policies side-effect free and query-light: they run per card on boards; use already-loaded relations (`relationLoaded('project')`, `detail`) and `CycleLinkResolver::prime` for the cycle flag.
