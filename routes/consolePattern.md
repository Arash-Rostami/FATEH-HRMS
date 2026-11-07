# Scheduler — `routes/console.php`

Every scheduled task in this app lives in `routes/console.php` (no Kernel, no provider, no Horizon schedule). The schedule timezone is `Asia/Tehran` (`config/app.php`, no `schedule_timezone` override), so every daily task fires at its stated wall-clock time in Tehran local time. The server cron runs `schedule:run` every minute; tasks run serially on the default queue worker (no `runInBackground`), single scheduler host assumed.

## Logic of the layout

1. **Nothing piles on 00:00.** Before the 2026-11-06 rebalance, seven tasks all fired at midnight — the heaviest delete (`model:prune`, per-row deletes with events) beside the job-fanning edge sweep and the mail-sending reminder sweep, serially on one worker. Every daily task now has its own minute.
2. **Hourly tasks sit at :00 / :15 / :45** so no two fire on the same minute, and the business-hour sweeps stay inside their windows.
3. **DB prunes run first, file-IO last.** The row deletions (01:10 → 03:15) finish before the file sweeps (03:35, 04:05, 04:35), and everything lands before business hours (06:00+). No daily task shares a minute with the hourly :00/:15/:45 tasks.
4. **`->withoutOverlapping()` on every task** — a slow run never doubles up with the next tick.
5. **`->onFailure(fn () => Log::error('schedule failed: <command>'))` on the five DB prunes plus `storage:prune-tmp`** — the six destructive tasks. A silent failure of a prune otherwise goes unnoticed until the table/disk grows; this writes one error line per failed run to the log (`grep "schedule failed"` next morning). The other tasks either self-report (the temp pruner prints counts and exits non-zero) or are non-destructive.
6. **`->when(fn () => !tenantOff(...))`** — the two taskboard/reminder sweeps skip entirely on tenants with that module off; nothing else is tenant-gated (prunes must clean data of any tenant state, and `livewire-tmp` is global).
7. **Known hygiene gaps, left deliberately:** no `onOneServer()` anywhere (matters only the day a second scheduler host appears — add it then, not before); no `runInBackground()` (only if `model:prune` ever exceeds a minute); and before this rebalance NO task had failure alerting, which was the real gap — a nightly prune could fail silently until the table/disk grew. The six destructive tasks now log; if a log aggregation/alerting channel ever lands, those `schedule failed:` lines are the hook to wire it to. `reminders:sweep-due` staying at :00 is hour-aligned by its due-today/last-hour logic but was never re-verified against its tests — if it ever moves inside the hour, re-check `ConsoleCommandsTest`'s due-timing tests first.

## Times (2026-11-06 rebalance)

| Time | Command | Kind | Failure log |
|---|---|---|---|
| 01:10 daily | `model:prune` | DB prune (prunable models, per-row) | yes |
| 01:40 daily | `queue:prune-failed --hours=720` | DB prune (failed jobs table) | yes |
| :45 hourly | `suggestions:auto-resolve-stale` | workflow reconcile | — |
| 02:05 daily | `notifications:prune-stale` | DB prune (nudges/notifications) | yes |
| :15 hourly, 06:00–22:16 | `tasks:sweep-overdue` | task overdue nudges (`when` tenant) | — |
| :00 hourly | `reminders:sweep-due` | due reminders — email/SMS send | — |
| 02:35 daily | `tasks:archive-stale-done` | workflow | — |
| 03:05 daily | `edges:prune-stale` | DB prune (edge toasts) | yes |
| 03:15 daily | `reminders:prune-completed` | DB prune | yes |
| 03:35 daily | `exports:prune-old` | file prune (Filament exports) | — (prints own counts) |
| 04:05 daily | `presence:reset-stale` | presence reset | — |
| 04:35 daily | `storage:prune-tmp` | file prune (temp uploads; see below) | yes |

Kept at :00 on purpose: `reminders:sweep-due` — its due-today/last-hour logic is hour-aligned, so moving it inside the hour would change which reminders fire when.

## `storage:prune-tmp` — the app-wide temp sweeper

`app/Console/Commands/PruneTmpStorage.php`. One generic daily command for all temp files, not per-module.

**Why these two roots and nothing else.** Every file-upload form in the app parks its temp copy in Livewire's shared `livewire-tmp` inbox before the file moves to its final home; the command appends that root automatically in code (disk and path read from Livewire's own config, so they cannot drift). AI Report is the only module whose files must outlive the request (the worker reads them minutes later during the provider call), so it stages under `ai-report/tmp/{id}` — the one entry in `filesystems.tmp_prune.roots` (config `['disk', 'path']` pairs; a future module with the same need adds one line). Everything else in storage is a kept attachment on other disks/paths and is **structurally unreachable** — the command never walks a disk, only the top level of its allowlisted roots.

**Deletion rule (age-only, no DB).** A unit (top-level file or folder) is deleted when nothing inside it has been modified for `filesystems.tmp_prune.hours` (default 24); a folder is kept whole if any file inside is fresh — never a partial delete. Age is mtime-only: **write temp files by copy (`storeAs`/`put`), never by rename or move-in** — a file renamed or moved into a temp root keeps its old mtime and could be swept early (ctime cannot be used instead: the tests age files with `touch()`, which cannot backdate ctime). 24h is ~288x the real need: `GenerateReportJob` has `tries = 1`, `failed()` and its `finally` delete the folder, a refine stores new files (a file landing mid-run still keeps the unit: touched entries fail their delete-time mtime re-check, and a folder that gained one cannot be removed while non-empty), and Livewire itself already deletes inbox files older than a day on every finished upload — this command only adds the sweep for uploads that never complete.

**Safety guards (all in `safeRoot`/`sweep`/`inspect`/`unlink`).** Roots that are empty, `.`/`..`, symlinked (any parent segment), non-local-driver, unknown disks, tmp-less paths, or resolve outside their disk root are refused — counted invalid, error printed, run continues with other roots, overall exit FAILURE. An unreadable root counts as failed without aborting the remaining roots or the summary line. A symlink unit is kept (counted "skipped"), never followed or deleted (and `remove()` re-checks `isLink()` after `clearstatcache` right before descending, so a folder swapped for a symlink between the scan and the delete is refused; a Windows junction swap is not detectable this way and is accepted); a folder containing a symlink is kept whole. `hours`/`max` clamp at ≥ 1; at most `max` units per run (default 10000), so a huge backlog drains over nights — or rerun manually. Failures are per-unit: one warning log (`root/basename` only, no absolute paths) and the sweep continues; the summary line reports pruned count, freed MB, kept, failed, invalid.

**Dry run.** `php artisan storage:prune-tmp --dry` walks and decides identically but deletes nothing — use it on a new server before the first real run.

**First-deploy runbook.** Deploy the command + `config/filesystems.php` + `routes/console.php`; `php artisan optimize`; `storage:prune-tmp --dry` should report `0 failed; 0 invalid roots` (any invalid count means a bad config — fix first); then run it for real; `schedule:list` shows `storage:prune-tmp` at 04:35.

**Tests.** `tests/Feature/Console/ConsoleCommandsTest.php`, 14 `test_prune_tmp_*` tests, `Storage::fake('local')` — stale/fresh folders, empty folders, livewire-tmp, outside-allowlist survival, future mtimes, symlinks incl. a folder containing one (skip without privilege), refused roots, missing root no-op, dry run, per-run cap, idempotency.
