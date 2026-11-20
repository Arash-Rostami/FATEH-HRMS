# `app/Support` — the access policies and upload helpers

Static, framework-light rule classes. Every screen, action, bulk path, report, calendar, badge and the admin panel asks THESE, never a hand-rolled check. Authorization lives here; lifecycle lives in model hooks (`Task::booted`, `TaskDetail`, `Project::booted`); metrics live in `App\Services\ProjectTask\TaskMetrics`.

Files: `TaskAccessPolicy`, `ProjectAccessPolicy`, `TicketAccessPolicy`, `SuggestionAccessPolicy` (unchanged, review-stage gate), `AiReportAccessPolicy` (§3b), `CorrespondenceAccessPolicy` (§3d), `LockPolicy` + `TopPositionPolicy` (§3e), `ChannelAccessPolicy` (§3f), `LaunchpadAccessPolicy` (§3g), `FileNamer` (§5).

Moved out (2026-11-10): `CorrespondencePdf` + `CorrespondenceFonts` -> `app/Services/Correspondence/` (`servicePattern.md` §4d); `ProjectCharter` -> `app/Services/ProjectTask/` (`servicePattern.md` §4e); `FaultTolerantGlobalSearchProvider` -> `app/Services/Search/` (`filamentPattern.md` rule 65); `FilamentPanelCustomizer` -> `app/Providers/Filament/` (`providersPattern.md`).

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
| `canApprove` | project owner (or any admin-panel session); the accountable only when the project owner is not `isVisibleOnBoard()` (none, inactive, guest or terminated — the same eligible-owner rule as `TaskNudge`) |
| `assignableIds` / `assertAssignable` | people allowed on a task: the project's charter roster when it has a project, else `User::visibleOnBoard()`; `assertAssignable` throws a `ValidationException` (also called from `Task::saving` and `TaskDetail::saving`, so every writer is covered) |

Cycle lock (`Task::isCycleLocked()`): the CURRENT step task of an ACTIVE cycle can be neither deleted nor moved to another project; past-step tasks stay free. Ticket mirror tasks (`ticket_id`) are locked to the ticket (`Task::guardTicketLock`); only `Task::syncingFromTicket()` bypasses it.

## 2. `ProjectAccessPolicy`

`canView` (= `Project::isVisibleTo`), `canManageAudience` (project owner OR an admin-panel session), and `canManageTasks` (alias of `canManageAudience`, added 2026-10-09 — the owner's task powers: create/take/reassign/reopen/archive/delete/approve and move tasks of his project). Call `canManageTasks` for task rights and `canManageAudience` for member/department rights so the two can diverge later without a sweep.

## 3. `TicketAccessPolicy`

`canView` (elevated roles, requester, assignee, or a HEAD of the target department — heads see every department ticket in any status), `canReply` (view and not closed), `canAssign` (requester only if he is a head; elevated or head otherwise), `canWithdraw` (requester, open/in-progress), `canReroute` (not closed; elevated or head), `canRelease` (the assignee of an in-progress ticket, or a head of its target department), `canSetEffectiveness` / `canClose`. A closed ticket is FINAL: it cannot be reopened or reassigned (`Ticket::saving` throws), withdrawn tickets never ask for a rating. "Head" = `User::isHeadOfDepartment` (ties-aware, cached — modelPattern Tier V). **Free assignment (2026-10-29):** while the target department's `free_assignment` flag is on, its current members (`isVisibleOnBoard()`: active, non-guest, not terminated; never the ticket's requester) ALSO pass `canView`/`canAssign`/`canReroute` for OPEN tickets targeted at it — and only those three verbs (`isFreeDepartmentMember` gate: same dept, no queries); `canRelease`/`canSetEffectiveness`/`canClose`/`canWithdraw` stay untouched, so a member loses every power the moment a colleague claims the ticket (`canReply` follows transitively from `canView` while the ticket is open — accepted, see thsPattern rule 22). `Ticket::scopeActionableBy` mirrors the gate, so every pool row passes `canView`.

## 3b. `AiReportAccessPolicy` (final, all static, user nullable -> false)

`canView` (the owner always, even when inactive; otherwise the report must be `active` AND the viewer an elevated role or an active recipient of a SAVED + ready report), `canGenerate` (`isVisibleOnBoard()` user AND `ReportAiService::isConfigured()`), `canRefine` (owner, `active`, ready or failed), `canSave` (owner, ready), `canShare` (`canSave` AND `active`), `canRename` / `canRevert` (`canSave` AND `active` AND no recipients - a shared or inactive report is frozen), `canDelete` (owner, not generating), `canToggleActive` (= `canDelete`). The presenter's permission helpers only delegate here. See `app/Livewire/Dashboard/AiReport/reportPattern.md`.

## 3d. `CorrespondenceAccessPolicy` (final, all static, user nullable -> false)

`canView` (live user + letter not trashed; the author always; anyone else only when the status is not draft-like/saved AND they are in `recipients` or hold a `parties` entry), `canEdit` (author while draft-like/saved/rejected; an `approver` party while `shared`), `isAuthor`, `partyOf`, `isRegistry` (active user with `extra.admin.correspondence_registry` truthy — the admin toggle «مسئول ثبت نامه‌های دریافتی»), `canRegistry` (a clerk may open, metadata-only, a letter in `CorrespondencePresenter::REGISTRY_STATUSES` she cannot otherwise view), `canSteward` (author, or a clerk on a normal-classification letter / one she can view: wet-sign steps and manual delivery). The presenter, the `*LetterAction` classes and `Letter*` support classes, `GenerateAction`, Search and the PDF controller all ask here; `LetterFlow::run` re-asks on the locked row. See `app/Livewire/Dashboard/Correspondence/correspondencePattern.md` §4.

## 3e. `LockPolicy` and `TopPositionPolicy` (final, all static; admin-panel module locks, 2026-11-12)

`LockPolicy`: `blocks($module)` (kill switch, developer and top executives never wait, no key rows = open, else the session fingerprint must match one key), `remember($module, $lock)` (stores the unlock; a record lock stores under `locks.{module}:{id}`), `blocksRecord($module, $record)` and `lockedRecordIds($module)` (the per-record twin: channel-owner keys; the record's `owner_id` is exempt), `isHolder`, `manages($user, $slot)` (both thin forms of `Lock::slotFor`), `lockedKeys()`, `lockableModules()` (admin resources that are not tenant-off, minus `permission`; memoised per request), `flush()`. `TopPositionPolicy`: `isLocked($position)` for chairman/ceo and `mayAssign($actor)` (developer or no actor). Full rules: `app/Services/Lock/lockPattern.md`.

## 3f. `ChannelAccessPolicy` (final, all static, user nullable -> false; 2026-11-17)

`role(?Channel, ?User)` (`owner` via `owner_id` first, else the `viewer_role` attribute when the channel was loaded through `withViewerRole`, else one `channel_members` PK lookup), `isOwner`, `isAdmin`, `canManageMembers` (owner or admin), `canChangeType` (manage + not project-linked), `canManageAdmins` (owner + not project-linked), `canSetKey`, `canDelete`, `canLeave` (everyone but the owner), `abilities()` for views. Every membership/type/admin/delete/leave Action re-checks it; Blade only displays (the channel key keeps `Lock::ownsRecord`, so `canSetKey` is display-only). See channelPattern §26.

## 3g. `LaunchpadAccessPolicy` (final, all static, user nullable -> false; 2026-11-17)

`canView` (active user + row not trashed; the owner and elevated roles always, an audience member only while `status()` is `live`/`soon`; any active user while it is `live`/`soon` when the row is public, `settings['public'] === true`, see `launchpadPattern.md` §7), `canCreateIn` (`isVisibleOnBoard()` user; elevated, or the given department is their own), `canManage` (owner or elevated — the replace and delete levers), `workspaceBlock` (null only for a viewable row that is not `inactive`/`scheduled`/`expired`), `token(Launchpad, User)` / `tokenUser(int, string)` (the `{uid}-{exp}-{sig}` HMAC-SHA256 path token the outside-auth file route accepts — anchored-regex parse, `hash_equals`, TTL `launchpad.token_ttl_minutes`; why a path token and not a session route: `app/Livewire/Dashboard/Launchpad/launchpadPattern.md` §3). `Main`, the create/delete Actions, the hero-deck slide and `LaunchpadFileController` all ask here; the file route re-checks `canView` on every request (no cache, instant revocation). See `app/Livewire/Dashboard/Launchpad/launchpadPattern.md`.

## 5. `FileNamer` (2026-10-14) — the one upload-naming standard

`FileNamer::prefix()` = `strtoupper(config('app.tenant'))` (the `config/tenants.php` array key, never a hardcoded brand). `FileNamer::stored($module, $file)` returns `{PREFIX}-{MODULE}-{Ymd}-{random12}-{time}.{ext}` with the ext mime-guessed (`extension()`, client ext only as fallback) — the variables are defined first, the return composes them, and callers pass it as the `$fileName` closure of `storeAttachment()`/`storeAttachments()` (resolved BEFORE `storeAs()`, per livewirePattern.md's metadata-before-store rule) or from a Filament `getUploadedFileNameForStorageUsing`. `FileNamer::download($ext, ...$parts)` builds the ASCII-safe name a user receives (`PERSOL-CODE-v2-excel-2.xlsx`), derived at serve time so old files get it too. Adopted by DMS (prefix only — `DmsFormPresenter::forgeFileName` keeps its own variable block plus slug), THS, release request, report, suggestion, task, resource, feed, gallery, post, link, onboarding. Deliberately NOT applied to private uploads (profile docs/images, chat attachments). Never rename or rewrite stored paths: old records keep their old names.
## 6. Rules

- A new "who may do X" question becomes a policy verb here first, then every caller (Livewire action, Kanban, bulk action, Filament page/action, search) uses it. Filament actions additionally require the panel's `update` permission (`filamentPattern.md`).
- Never read `settings.deadline|sla|labels|auto_archive_days` directly; go through the charter.
- Keep policies side-effect free and query-light: they run per card on boards; use already-loaded relations (`relationLoaded('project')`, `detail`) and `CycleLinkResolver::prime` for the cycle flag.
