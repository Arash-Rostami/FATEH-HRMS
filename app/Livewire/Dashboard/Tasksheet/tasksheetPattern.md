# Tasksheet module — patterns & conventions

Consolidates conventions for the user-panel `App\Livewire\Dashboard\Tasksheet\Main`, its
`App\Services\ProjectTask\TasksheetService` data layer, and the export/share flow around it.
Cross-panel/general rules (`#[Lazy]`, `dynamic-component`, `#[Url]`-bound state) stay in
`livewirePattern.md`; Tasksheet-specific decisions stay here.

## What Tasksheet is

A personal, cross-module performance report: for a chosen date window, it summarizes one user's
work spanning **both** TaskBoard (personal tasks) and Project (collaborative tasks) — completions,
on-time rate, cycle time, manager approvals, per-project/standalone breakdowns, highlights, and a
day-grouped activity feed — into one ERP-grade printable/exportable page, shareable with a manager
via a signed link. It reuses existing infrastructure end to end (activity log, risk chips, XLSX
export, org hierarchy, notification bell) rather than inventing parallel machinery.

## Event-sourced, not current-status

`TasksheetService` never counts "completed" by reading a task's *current* `status` column for a
past window — that would make a task completed-then-reopened silently vanish from a historical
report generated today, even though it genuinely was done during that window. Instead, "completed
in window" is derived from `TaskActivityType::StatusChange` activity rows (the latest done-event
per task, within the window), and the completion is attributed to **the event's actor**
(`replies.user_id`), never to the task's current `assigned_to` — so if person A closes a task and it
is later reassigned to person B, A's historical report still shows that completion and B's does not.
This is the same reasoning `servicePattern.md` §4 gives for why `TasksheetService` is not just an
extension of `ReportingService` (which is current-status-scoped by design, correct for a live board,
wrong for a frozen-in-time window).

## Single component, two modes — `readOnly` flag

One Livewire component, `Tasksheet\Main`, serves both the interactive self-view and the read-only
shared view — not two parallel components — because the two share ~90% of their rendering and state
shape. `mount(?int $subject = null)` sets `readOnly = $subject !== null` and resolves
`subjectUserId` from either the route parameter (signed share) or the `?user=` query param /
`auth()->id()` (self or admin-entry-point). The `?user=` path is otherwise unauthenticated by
routing/middleware, so `mount()` itself gates it: when `!$readOnly` and `subjectUserId !== auth()->id()`,
the viewer must be `isAdmin()`, `isDeveloper()`, or the subject's `highestRankingInDepartment()` —
anything else is a 403. This does not apply to the signed-share path, which is already gated by the
route's `signed` middleware. Two routes point at the same class:

```php
Route::get('/tasksheet', Tasksheet\Main::class)->name('tasksheet');
Route::get('/tasksheet/shared/{subject}', Tasksheet\Main::class)->middleware('signed')->name('tasksheet.shared');
```

**Security note, must not be relaxed:** every mutating method that changes window/scope
(`setPreset`, `setCustomRange`, `scopeToProject`, `clearScope`, `shareWithManager`, `export`,
`toggleBaselineWindow`) opens
with `if ($this->readOnly) return;`. This guard exists because Livewire methods are callable via
direct AJAX request regardless of what the Blade template hides — the `signed` middleware only
validates the initial GET that mounts the component; it does not re-validate subsequent Livewire
update requests. `toggleActivity()`/`loadMoreActivity()` are deliberately **not** guarded — they only
change local visibility/pagination of already-authorized data, not the report's window or subject.
The `#[Url]`-bound `preset`/`fromYear`/`fromMonth`/`fromDay`/`toYear`/`toMonth`/`toDay`/`scopeProjectId`
properties are safe to leave bound even in read-only mode specifically because the entire query
string is covered by the signed route's
signature — tampering with any of them invalidates the signature and 403s before the component ever
mounts. Do not "fix" this into two separate property sets later; the single-component design is safe
because of that fact, not despite it.

Only `preset` and `scopeProjectId` carry `#[Locked]`: they are set exclusively via the guarded methods
above (`setPreset()`/`setCustomRange()`/`scopeToProject()`/`clearScope()`), never via `wire:model`, so
locking them costs nothing. The 6 date-window properties (`fromYear`/`fromMonth`/`fromDay`/`toYear`/
`toMonth`/`toDay`) **cannot** carry `#[Locked]` — `header.blade.php` renders
`<x-ui.forms.date prefix="from" .../>` / `prefix="to"`, and `date.blade.php` expands that into
`wire:model="{{ $prefix }}Year"` etc., i.e. literally `wire:model="fromYear"`, `"toMonth"`, and so on,
only in the non-read-only branch. A plain grep for the literal property name misses this because the
binding name is built dynamically from `$prefix`. Locking these 6 breaks the interactive custom-range
picker outright (`CannotUpdateLockedPropertyException` the moment the user picks a date). The gap this
would otherwise reopen — a raw `/livewire/update` request against an already-mounted read-only
component bypassing the named-method guards — is closed instead by a class-wide `updating(string $name,
mixed $value)` lifecycle hook (Livewire's generic before-any-property-update hook, called once per
dirty property ahead of the specific `updating{Property}` hook) that `abort_if($this->readOnly &&
in_array($name, self::GUARDED_WHEN_READ_ONLY, true), 403)`s for all 9 properties, `preset`/
`scopeProjectId` included for defense in depth even though they're also statically locked. This guard
only ever fires against tamper attempts: the date picker, preset buttons, and scope controls are only
rendered in the `@else` (non-read-only) branch of `header.blade.php` and sibling partials, so a
legitimate `wire:model` sync on these properties never runs while `$this->readOnly` is true.

## "My manager" is an approximation, not a real field

The recipient-resolution and notification-send logic lives in its own class,
`App\Services\ProjectTask\TasksheetShareService::shareWithManager(User $subject, ?User $recipient,
?User $requestedBy, array $windowParams)` — extracted out of `Main.php` because it has two real
callers from day one (`Main::shareWithManager()` for the self-service picker, and the admin
`UserResource`'s `shareTasksheet` record action), which clears `servicePattern.md` §1's promotion bar
immediately. It resolves the recipient via `User::highestRankingInDepartment($subject->profile?->department_id)`
(the existing `HasProfileHierarchy` trait) when no explicit `$recipient` is passed — there is no
`manager_id` field on `users`/`profiles`. This is consistent with the "no new schema for sharing"
decision (the whole share flow rides on a Laravel signed URL, no `shares` table either). It is a
known, accepted approximation: the highest-ranking person in a department is not guaranteed to be
someone's literal line manager. When resolution is ambiguous or null (no department, or the subject
already is the top of their department), the method returns a failure message rather than guessing or
silently failing, and `Main.php`/`UserResource` surface it as a toast/notification.

Note: it does **not** go through `InteractsWithNotifications::notifyWithAction()` — that trait
hardcodes the recipient to `auth()->user()` and its action always `dispatch()`es an event, never
`->url()`s. Tasksheet needs to notify a *different* user (the manager) with a clickable `->url()`
action, which that trait's contract can't express, so `TasksheetShareService` calls
`Filament\Notifications\Notification::make()->sendToDatabase($manager)` directly instead.

`Main::shareRecipientOptions()`/`shareRecipientId` let the sender pick any active user, not only the
resolved manager — the manager is only the pre-selected default. Since a sender can only ever share a
report they're already authorized to view (their own, or an already-gated admin view), this is a UX
choice, not an authorization gap.

## `viewingBaseline` — one-flag time machine over the scorecard's own baseline math

`window()` is the single funnel for every window consumer (`report`, `activityFeed`, `export`), so
flipping the in-memory `viewingBaseline` flag shifts the entire page (scorecard, drilldowns,
activity feed) to the **previous equal-length window** by re-windowing inside `window()`:
`end = current.start − 1s`, `start = current.start − (current.start.diffAsCarbonInterval(end))` —
the exact same baseline math `TasksheetService::baselineMetrics()` uses, so what the user sees when
they click a «قبلی: X%» delta chip is literally the window that number compares against. The flag is
a plain bool, deliberately **not** `#[Url]`-bound: a shared/saved link must stay truthful to its
preset, so baseline viewing is transient page state. `setPreset()`/`setCustomRange()` reset it to
false (a new current window makes the old baseline view meaningless). Every delta chip in
`scorecard.blade.php` toggles it (non-read-only mode; read-only renders chips as inert text), and
while active a «بازهٔ قبلی فعال است — بازگشت» banner sits on the window statement. `viewingBaseline`
carries no `#[Locked]` (it is toggled via `wire:click` only, but the readOnly gap is closed the
established way: method early-return + the `GUARDED_WHEN_READ_ONLY` `updating()` entry).

## Scorecard tiles are owned-only, drilldown tables are touched-scope

`report()`'s top-level scorecard tiles (`completed`, `on_time_percent`, `cycle_time_days`,
`approvals_received`, `still_overdue`, `in_progress`, `upcoming_deadline`) are scoped to tasks the
subject **owns** (`assigned_to`, `user_id`, or listed as a `collaborators` entry on the task's
`detail`) — never the wider candidate/touched set, which also includes tasks the subject merely
*commented on* without owning. `buildReport()` builds `$ownedSet = $ownedTaskIds->flip()` and filters
both the events passed into `scorecardMetrics()` and the tasks passed into `taskStatusCounts()`
through it (`baselineMetrics()` does the same for its own baseline-window scorecard). Without this,
one comment left on a colleague's task would leak that colleague's approvals/overdue status into the
subject's personal report tiles. The per-project/standalone **drilldown tables** (`groupStats()`,
feeding `projects`/`standalone`) are intentionally **not** owned-scoped — they show "tasks in this
project the subject was involved with," so they correctly use the full touched-task collection for
that group. Do not conflate the two: fixing a scorecard leak by touching `groupStats()` would be
wrong. The `narrative` sentence's denominator is `$candidateTaskIds->count()` (owned ∪ acted-in-window)
— it deliberately reports on tasks touched at all in the window, not the narrower owned-only count,
since the sentence reads "X of Y assigned tasks completed" against the subject's full workload
footprint for that window.

## `TasksheetService`'s two-method locked contract

`report(User $subject, Carbon $start, Carbon $end): array` and
`activityFeed(User $subject, Carbon $start, Carbon $end, int $page, int $perPage = 30): array` are
the **only** two public entry points, called identically by the Livewire component's `#[Computed]`
properties and by `ExportTasksheetAction`. This contract must stay locked — export must source its
rows from `report()`'s already-computed data, never re-derive numbers via a second, independent
query path, or the Livewire page and the exported file could show different numbers for the same
window. `report()` is Tier T cached (`Cache::flexible`, key embedding `ModelCacheVersion::version()`
for `Task`/`TaskDetail`/`Reply`, matching `ReportingService::analyticsInsights()`'s shape);
`activityFeed()` stays uncached (Tier L, same as `Reply` project-wide).

## Scorecard sparklines + full-report export (2026-08-31)

**Per-tile sparkline.** Each of the 4 scorecard tiles with a delta chip (`completed`, `on_time_percent`,
`cycle_time_days`, `approvals_received`) gained a tiny 2-bar `<x-ui.decor.sparkline>` (previous vs.
current, reusing the same generic bar-chart component the header's weekly-trend chart already uses,
just at `width=20 height=14`) next to its delta chip. Pure presentation of numbers `deltaChip()` already
computes — no new query, no new presenter method; `scorecard.blade.php`'s own `$sparkOf` closure just
reshapes `[previous, current]` into the array the sparkline component expects, returning `null` (no
sparkline) whenever either value is null, matching exactly when the delta chip itself would also be
null. `cycle_time_days`'s sparkline compares `median`/`previous_median` (the value actually shown and
delta-tracked), not `avg` (which has no tracked previous).

**Export was statistics-only; now mirrors the full page.** `ExportTasksheetAction` used to write only 3
header numbers, the narrative, and one flat task table mixing every project together. Rewritten (still
sourcing everything from the same locked `report()` call above — no new query path) to also write: the
full scorecard (all 4 metrics with previous/delta, plus the 3 plain counters), `highlights` (hardest
close / fastest turnaround / most collaborated, when non-null), `weekly_totals` as a 2-row table, a
per-project breakdown table (completed/on-time%/overdue/in-progress per project + standalone), and the
task-details table now grouped under a bold project-name sub-header per project (+ a "بدون پروژه"
group) instead of one undifferentiated list. Each section is its own private method on the Action
(`writeScorecard`/`writeHighlights`/`writeWeeklyTrend`/`writeProjectBreakdown`/`writeTaskDetails`) so
the file stays readable despite writing much more. Legend gained a row explaining the export is now a
full report, not just top-line numbers. Tests: `TasksheetTest.php`
(`test_scorecard_tile_renders_a_sparkline_when_a_baseline_exists` /
`..._omits_the_sparkline_without_a_baseline`, `test_export_produces_a_full_report_not_just_top_line_statistics`
— reads the generated xlsx back via `OpenSpout\Reader\XLSX\Reader` and asserts the new sections/project
grouping actually appear, not just that the file doesn't throw).

## Legend rework + badge-legend wiring (2026-08-31)

**Legend restructured into grouped tabs**, matching Project's tab/subgroup pattern instead of a flat
8-row list: معیارها (3 sub-pills: کارت‌های اصلی / نمودار و مقایسه / اولویت و وضعیت لحظه‌ای), وظایف و
پروژه‌ها (2 sub-pills), اشتراک‌گذاری و خروجی (flat), نکات (flat). Every scorecard metric, chart, and
chip now has its own row, including several that previously had none (میانگین/میانهٔ زمان انجام,
تأییدیهٔ دریافتی, نمودار ریز, نمودار هفتگی, معوق/در حال انجام/مهلت نزدیک, نکات برجسته, جدول ریز,
آکاردئون فعالیت‌ها, چاپ گزارش). The priority-chip row explicitly says the chips are a per-priority
count, never a combined/averaged number — this had read as vague before.

**Tasksheet was missing the standard per-module badge-legend button entirely** — unlike every other
module (see `viewPattern.md` §8.5), `main.blade.php` only ever had the "help" (`?`) header button, no
"notifications" (🔔) one beside it, even though a real Filament DB notification already fires on
`shareWithManager()`. Added: `BadgeLegendCatalog::all()['tasksheet-controller']` (new
`compliance.tasksheet` subgroup, `tone: sage` — bell-only, no matching dot, same family as
`reports-controller`), a `<x-dashboard.modal.badge-legend name="tasksheet-badge-legend" :items="[...]">`
include, and the header trigger button in the required DOM order (notifications button first, help
button second — swapping this order visually inverts the render under RTL flex, see §8.5).

**Activity timestamps now pair `toJalaliRelative()` with an absolute-time tooltip and fix its bidi
rendering.** `activity-accordion.blade.php`'s timestamp span gained `title="{{ toJalali($item['created_at']) }}"`
so hovering shows the exact date/time, not just "همین الان"/"۵ دقیقه پیش". Because the whole page is
`dir="rtl"` and a native `title` attribute's directionality follows its host element's `dir`, the
tooltip's digit groups would render reversed without help — fixed with `dir="ltr"` on the
title-carrying span plus a nested `<span dir="rtl">` wrapping just the visible relative label, so the
on-screen Persian text is unaffected and only the tooltip's bidi context flips. `cursor-help` added to
match every other native-`title` hover spot in the app. Same fix applied so far only here; `app/Livewire/livewirePattern.md`
documents the rule for the other `toJalaliRelative()` call sites (Project activity, TaskBoard history,
Feeds timeline, Posts grid) still pending the same three-part treatment.

## Entry points (5)

TaskBoard toolbar, Project Report tab toolbar, Project sidebar header (icon-only button beside "ایجاد
پروژه", `resources/views/livewire/dashboard/project/sidebar.blade.php`), and the admin
`TaskResource`/`ProjectResource`/`UserResource` record actions — all open `/tasksheet` (or
`?user={id}` from the admin side) in a new tab, never in-page, so the originating page's own state is
untouched. The user-panel header also carries a `<x-ui.buttons.tab-selector>` (Tasks/Projects, hidden
in read-only mode) going the other direction — from Tasksheet back into either module — matching the
same toggler `taskboard.blade.php`/`project.blade.php` already use between each other.

## File map

- `Main.php` — the component: mode flag, `#[Url]`-bound window/scope state, mutating methods, share/export triggers.
- `Presentation/TasksheetPresenter.php` — Jalali window statement, delta-chip shaping, role badges, project-health chip.
- `app/Services/ProjectTask/TasksheetService.php` — the data layer (§ above).
- `app/Services/ProjectTask/TasksheetShareService.php` — manager resolution + signed-link notification send (§ "My manager" above); called by both `Main::shareWithManager()` and admin `UserResource`'s `shareTasksheet` action.
- `Actions/ExportTasksheetAction.php` — OpenSpout XLSX streaming export, sourced from `TasksheetService::report()`.
- `resources/views/livewire/dashboard/tasksheet/` — `main`, `header`, `scorecard`, `highlights`, `projects-accordion`, `standalone-accordion`, `row-table`, `task-table`, `activity-accordion`, `legend`, `placeholder`.
- `app/Traits/RiskEscalationChip.php` — the success→warning→error tone-class primitive, extracted out of `ProjectPresenter` so `TasksheetPresenter` reuses the exact same escalation logic instead of a second copy (see `traitPattern.md` §2.2).
