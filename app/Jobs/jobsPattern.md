# `app/Jobs` — queued reconcile/sync jobs

Five jobs, all `ShouldQueue`, `tries = 3`, `backoff = [10, 30]`, queue = redis (Horizon). Rule: a job is a thin wrapper around a Service/Model method; it holds no business rules and re-finds its model by id (a missing row is a silent no-op).

| Job | Dispatched from | Notes |
|---|---|---|
| `ReconcileNudge(ruleKey, subjectClass, subjectId)` | `NudgeService` on every registered trigger event, `Profile::saved` (open tickets; on a department move also Post/Feed/Report/Photo whose audience holds the old/new code), `User::saved` (ticket heads), `Reminder` | `ShouldBeUniqueUntilProcessing`, `uniqueFor = 2`s, id = `rule|class|subject` — a burst of saves on one record queues ONE reconcile. Calls `NudgeService::reconcile`. Master-class kinds (`TaskNudge`, `ProjectNudge`) share the job; the rule key tells them apart. |
| `ReconcileEdge(...)` | `NudgeService`-style edge (toast) rules | Same contract for Signal 3; not unique. |
| `SyncTaskCalendarEventJob(taskId)` | `HasTaskActivityLog` saved hook (Task and TaskDetail) | `afterCommit()` in the constructor (dispatched inside the save transaction otherwise ran before the row was visible and missed events) and `ShouldBeUniqueUntilProcessing` with `uniqueFor = 5`s so create + detail-save dispatch once. Delegates to `EventSyncService::sync`; done/archived/deadline-less tasks purge their calendar event. |
| `SyncProjectChannelMembershipJob(projectId)` | `Project::saved` (members/departments/owner changed), `Profile::saved` (department move touches every project that targets the old/new department) | `afterCommit()`. Audience = `Project::charter()->rosterIds()` (owner + members + department members, active non-guest); adds missing members with a fresh read cursor, detaches the rest except the owner, and re-points `channel.owner_id` to the project owner. A project-linked channel cannot be deleted or made Open by hand (`Channel::isProjectLinked`). |
| `LogMissingSkillJob` | Skills directory | Unchanged. |

Gotchas
- `ReconcileNudge::handle` wraps `NudgeService::reconcile` in `DB::transaction(..., 3)` (2026-10-23): the hourly sweeps queue a burst, and two workers' `delete from notifications where data->menu_key/item_id` (JSON filter, no index) deadlocked (MySQL 1213) once per hour, costing a 10 s backoff retry. `transaction()`'s attempts arg retries a deadlock instantly. Keep it on any job whose body deletes by a JSON path.
- Dispatch raw/bulk-written changes yourself: `DB::table`/query `update` fires no model event, so no job runs (the overhaul replaced those with per-model updates; the nightly `ArchiveStaleDoneTasks` bumps the `Task` cache version explicitly).
- `afterCommit` jobs dispatched with `dispatch(...)->afterCommit()` outside a transaction run immediately — safe either way.
- Horizon keeps code in memory: after changing a job or a nudge class run `supervisorctl restart horizon-*`, not just an FPM reload.
