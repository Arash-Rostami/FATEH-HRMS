# Reservation — resource booking + calendar sync

## `Main::historyReservations()` — SQL-level hard cap, not true pagination

The user-panel history tabs (previous/cancelled/released/upcoming) fold recurring-series occurrences
into one representative row (`groupBy(fn($r) => $r->parent_id ?? $r->id)`) with an accurate
`series_count` — needing every occurrence of a series in PHP to count correctly, so the query can't
`->limit($historyLimit)` at the SQL level the way `resources()`/`totalResources()` do. Before the fix
this meant `$query->get()` had **no SQL limit at all**: `scopeCancelled()`/`scopePrevious()`/
`scopeReleased()` carry no time window, so a long-tenured user's ENTIRE lifetime history in that status
was fetched, hydrated (with `resource` eager-loaded), and grouped/mapped in PHP on every page load of
the tab — only to discard everything past `$historyLimit` (5, +5 per "load more") via `->take()`.
Confirmed via direct code read, not a log symptom.

**Fix applied is a quick safety cap, not a true fix**: `private const HISTORY_QUERY_CAP = 500;`
applied as `$query->limit(self::HISTORY_QUERY_CAP)->get()` before the PHP-side grouping. This bounds
the worst case (truly unbounded growth) but does **not** make the tab genuinely paginated — every load
still fetches up to 500 rows regardless of `$historyLimit`. **Known, accepted tradeoff**: if a single
recurring series has more occurrences than fit before the 500-row cutoff (e.g. 500+ historical rows in
one status where the cutoff lands mid-series), that series' `series_count` badge could undercount — a
cosmetic edge case, not a functional bug, only reachable well past what any UI session realistically
pages through. A real fix would push the series-grouping and counting into SQL (e.g. a
`COALESCE(parent_id, id)` group-by + window function) so only `$historyLimit` rows are ever fetched —
deliberately not done; flagged as a future architecture change if this tab's cost ever becomes real
rather than theoretical. Regression test:
`ReservationTest::test_history_query_applies_hard_ceiling_before_series_folding` (asserts the actual
executed SQL contains `limit 500` via `DB::listen()`, not just that the component renders).

## `EventSyncService` — meeting bookings become one shared calendar Event, not two mirrored ones

`Reservation::booted()` calls `EventSyncService::sync()` on every `saved` and `EventSyncService::purge()`
on `deleted`, regardless of whether the reservation was created via the admin `ReservationResource` or
the user-panel `Dashboard\Reservation\Main` Livewire component — one model hook, both entry points, no
per-entry-point duplication.

### Shape

For an **active** reservation on a **Meeting**-type resource, `sync()`:
- `Event::updateOrCreate`s exactly **one** `Event`, owned by the booker (`user_id = $booker->id`),
  keyed on `(user_id, description)` where `description` embeds the reservation id
  (`"جلسه برنامه‌ریزی شده از طریق سیستم رزرواسیون #{id}"` — the stable identity key across repeat syncs).
  `private = true`. Title is party-agnostic (`"جلسه {booker} و {related}"`) since both viewers see the
  same row.
- `EventShare::firstOrCreate`s a share for the resource's `relatedUser` (`Resource::relatedUser()`,
  matched by `name`) — this rides the **already-existing** `App\Services\Menu` shared-events
  infrastructure (`app/Services/Menu/statePattern.md`) instead of building new notification plumbing:
  the invited colleague gets the `shared-events:nudge` bell immediately (`SharedEventsNudge`,
  `date >= now`) and the `shared-events` badge dot within 24h of the meeting
  (`EventShare::hasImminentFor`).
- Both writes go through **Eloquent** (`updateOrCreate`/`firstOrCreate`/model `->delete()`), never a
  bulk query, because `HasMenuState`'s flush and `NudgeServiceProvider`'s triggers are bound to Eloquent
  model events — a bulk `Event::query()->delete()` or `EventShare::insert()` here would silently break
  the badge/nudge wiring.

### Superseded design

Before this, `sync()` created **two** independent private `Event` rows (one per `user_id`, booker and
related) — an unlinked mirror, not a share. That meant the invited person got a silent calendar entry
with **zero** notification (no `EventShare` row, so `SharedEventsNudge`/`SharedEvents` never fired), and
each party could edit/delete their own copy independently. The new shape trades that independent-edit
capability for the existing, already-shipped nudge+badge visibility — the invited person's calendar
entry is a **shared, read-only** view (`Calendar.php`'s `editEvent()`/`deleteEvent()` are ownership-gated
to `user_id = Auth::id()`, so only the booker can edit/cancel; the related person sees it via the same
`orWhereHas('shares', ...)` clause any other shared event uses).

### `pruneOtherOwners()` — legacy-row cleanup, not resource-dependent

Both `sync()` and `purge()` delete any `Event` row matching this reservation's `description` but **not**
owned by the booker (`pruneOtherOwners()`), independent of whether `Resource::relatedUser()` currently
resolves. This both consolidates any pre-existing row left over from the old two-Event scheme (the next
time that reservation is saved or cancelled) and stays correct if the resource is later renamed/deleted
such that the name-match breaks — `purge()` no longer needs to load `resource.relatedUser` at all.

### Timing — nudge vs. badge are decoupled, by the existing system's own design

This decoupling is existing, unmodified behavior from `app/Services/Menu/statePattern.md` §
`SharedEvents` — the reservation flow simply now participates in it rather than introducing new
notification timing.

### Not yet backfilled

Reservations that were active before this change may still carry a leftover related-user-owned `Event`
row from the old scheme. It self-heals the next time that reservation is saved or cancelled
(`pruneOtherOwners()` runs on both paths) — no migration/backfill command has been run against
production data for this.

### Calendar UI — reservation-linked events are read-only, not just read-only-by-convention

`EventSyncService::isReservationEvent(?string $description)` / `::reservationIdFrom(?string $description)`
are the single source of truth for "was this `Event` produced by a reservation" (checked against the
`description` prefix, `DESCRIPTION_PREFIX`) — `Calendar.php` uses them rather than re-deriving the marker
string. Without this guard the booker could open the normal edit/share modal on a reservation-generated
`Event` and change its title/date/shares directly in the UI; those changes would either get silently
clobbered on the next `sync()` (title/date/the one canonical share) or drift permanently out of sync with
the reservation (extra manual shares — `sync()` prunes any share not belonging to the resource's current
`relatedUser`).

`Calendar::editEvent()`, `::deleteEvent()`, and `::openShareModal()` each early-return with an error toast
for a reservation-linked event instead of performing the action, even though the booker is technically the
`Event` owner. `events.blade.php` mirrors this: the normal share/edit/delete button row is swapped for a
single "مشاهده رزرو" (view reservation) link — `route('reservation')`, and a day-list tag distinguishes
these rows from an ordinary shared event. The calendar legend documents this row type alongside the
existing private/public/ownership rows.

**Not** `route('dashboard', ['tab' => 'reservation', ...])`: unlike `feed`/`gallery`/`post`/etc., `reservation`
is not one of `Tabs::tabs()`'s keys — it is registered in `TabPresenter::shortcuts()` as its own dedicated
route (`routes/web.php`: `Route::get('/reservation', Reservation::class)->name('reservation')`), so a
`dashboard?tab=reservation` URL silently falls back to the `home` tab via `Tabs::normalizeTab()`. The
`?open={id}` `FocusOnRecord` deep-link convention doesn't carry over either: `Reservation\Main::focusRecord()`
resolves `open` against `Resource::find($id)` (for highlighting a bookable resource from search), not
`Reservation::find($id)` — there is no UI wiring today to scroll to/highlight one specific past reservation
by id. `selectedDayEvents()` still exposes `reservation_id` per event for a future such deep-link; until
that's built, the link only routes to the reservation module's landing page.

## Long-hold (range) booking mode — one record, continuous span

A continuous hold of a resource (e.g. "reserve this seat for a whole year") is stored as **one** `Reservation` row with `start_time` today and `end_time` months/years ahead. No new column, no migration — `end_time` is already a `timestamp`. Range mode is **elevated-only (admin or developer) by construction**: only the Filament admin form (`ReservationFormPresenter`) — reachable by both `admin` and `developer` roles (`User::canAccessPanel` returns `true` for both; `developer` is super-by-role and bypasses `AuthorizesByPermission`) — exposes a free `end_time`; the user panel derives `end` from `selectedDuration` (minutes, bounded by `max_duration_minutes`), so a regular user can never produce a multi-day span.

### Deriving `isRange` — duration, not calendar-span

`BookingContext::isRange()` and `Reservation::isRange()` both compute `!is_full_day && start->diffInDays(end) >= 1`. This is a **>=24h elapsed** check, deliberately not a calendar-day (`isSameDay`) check: a legitimate short overnight slot (22:00→01:00, ~3h, crosses midnight) returns `diffInDays ≈ 0.08` → **not** range → stays under the normal intraday validators (`Duration` max/min, `AllowedHours`). Only holds ≥ ~1 day become range. A year is 365 days → range.

### Validators relaxed vs. kept in range mode

In range mode these **early-return** (a continuous hold isn't bound to working hours/days or intraday caps): `Duration`, `AllowedHours`, `ResourceSchedule`, `AllowedDays`, and the `window_days`/`window_hours` future-cap inside `TimeWindow`. `TimeWindow`'s **past-start guard is kept** even in range (no backdated starts).

These **stay enforced** in range: `ResourceAvailability` (the per-resource double-book guard — the seat is exclusively held for the whole span; nobody else can book it), `TypeActive`, `ResourceActive`, `FullDay`, `Recurrence`, `UserActive`, `BookingPermission`. `UserConflict` is **skipped** in range: it is per-(user, resource-type) and would lock the user out of *every* same-type booking for the year; `ResourceAvailability` already prevents true per-resource double-booking, so the per-type lockout serves no purpose for an elevated-assigned (admin or developer) hold.

`ActiveLimit` (`max_per_user`) counts a range **once**, in its start month — elevated-authoritative (admin or developer) ranges are exempt from the monthly cap for later months (documented, accepted).

### Form guards (`ReservationFormPresenter::isRangeState`)

When the form's `start_time`/`end_time` form a range (`!is_full_day && diffInDays >= 1`): the `is_full_day` toggle is `disabled()` (with `->dehydrated()` so `false` persists) — you can't flip a range to full-day; `is_recurring` + `recur_pattern` + `recur_count` are `disabled()` and hidden — you can't combine range + recurring (which would otherwise make `GenerateSeriesAction` shift the year-long `end` per child into overlapping year-long holds). `end_time` and `start_time` pickers carry `->maxDate(now()->addYears(10))` to reject absurd spans.

### Page hooks

`CreateReservation::afterCreate` guards `GenerateSeriesAction` with `!$this->record->isRange()`. `EditReservation::mutateFormDataBeforeSave` forces `is_full_day = false` for a range and only applies the same-day `startOfDay/endOfDay` pin when **not** range — so editing a range never truncates it to a single day.

### Event sync — no meeting event for a range

`EventSyncService::sync` routes a range through the same `purge()` branch as a non-meeting/non-active reservation: no `Event::updateOrCreate` (a seat/desk hold is not a calendar meeting), and the `DB::afterCommit` `countdown:active`/`StateService::flush` still runs. The countdown is `Event::activeCountdownEvent()`-driven, not reservation-`end_time`-driven, so a far `end_time` never produces a "525600 minutes remaining" artifact.

### Cancel / partial release — one record, whole-only by design

A range hold is one record. **Whole-cancel** (status → cancelled) and **shorten** (move `end_time` earlier) both work in one step. **Release** (`releaseAction`) on an **ongoing** range (start in the past) truncates `end_time` to now and sets status `released` — the remainder of the span is freed for others; on a **future** range (not yet started) it sets status `released` only (does *not* free — use cancel to free a not-yet-started hold). A mid-range **single day/time-slot cannot be excluded** from within the one record — there is no per-day exception mechanism. To free a mid-span gap: truncate `end_time` to the gap start, then create a second reservation for the remainder (two records). This is the accepted tradeoff vs. the 365-discrete-records approach (which silently truncated on `window_days`/`max_per_user`/non-working-days and lied about coverage). If real per-day blackouts inside a span are later required, a child `reservation_exceptions` table is the documented extension point — not built.

### Rendering

`Reservation::displayTime` shows the end **date** when end is on a later day than start (`date • H:i تا end_date H:i`), covering the user-panel history card (`history.blade.php` uses `$reservation->display_time`). The admin list table gained an `endTime()` column (`toJalaliSmart`, toggleable, hidden by default).

## Admin form date handling — shared `PersianDateFieldService` + `FilamentDateHandler`

The reservation admin form is now consistent with the other 6 Filament modules (Task, Ths, Event, Gallery, Profile, Report): date/datetime fields use `PersianDateFieldService` (Jalali year/month/day dropdowns → hidden `Y-m-d`) + a native `type('time')` TextInput, merged by the `FilamentDateHandler` trait — **no raw `DatePicker`/`DateTimePicker`**. `ReservationFormPresenter` was the last holdout.

- `start_time`/`end_time` are split in the form as `{field}_date` (`PersianDateFieldService::make`, `fullWidth: true`, `yearTo: now+10y`) + `{field}_time` (TextInput, default 09:00/17:00). `end_time_date`/`end_time_time` are `visible` only when `!is_full_day`; `start_time_date` is always visible (full-day needs the start date; end is implicit `endOfDay`).
- `ReservationResource\Pages\CreateReservation` and `EditReservation` `use FilamentDateHandler` with `datetimeFields() = [start_time, end_time]` (`default_time '00:00'`).
- **Lifecycle gotcha (do not regress):** the merge + validation + isFull-day/range normalization all happen inside `mutateFormDataBeforeCreate`/`mutateFormDataBeforeSave` (which receive the `$data` that is actually persisted via `handleRecordCreation`/`handleRecordUpdate`). There is **no `beforeCreate`/`beforeSave`** — those hooks read `$this->data` which, for Create, is NOT the same array as the persisted `$data`, so validating there would see un-merged split fields (`start_time` null) and diverge from what is saved. Keep the merge+validate+normalize in the `mutateFormDataBefore*` method.
- Carbon `startOfDay()`/`endOfDay()` mutate in place — in the isFull-day branch use `$start->startOfDay()` then `$end = $start->copy()->endOfDay()` (copy before endOfDay) so `validateEdit` receives `$start=00:00:00`, not the mutated `23:59:59`.
- `isRangeState(Get)` (form guard) reads `start_time_date`/`end_time_date` (the hidden assembled values), not `start_time`/`end_time` (those only exist post-merge).
- Range `maxDate(now+10y)` became `PersianDateFieldService`'s `yearTo: Jalalian::now()->getYear() + 10` (the service's native cap, not a chained `->maxDate`).
- Strings: `fields.start_time_time` / `fields.end_time_time` added to `lang/fa/resources/reservation/strings.php`.

## Range is elevated-only (admin or developer) — server-side guard in `BookAction`

Range mode is elevated-only (admin or developer) **by construction** (only the Filament admin form exposes a free `end_time`; the user panel derives `end` from `selectedDuration` bounded by `max_duration_minutes`). As defense-in-depth against a misconfigured `max_duration_minutes >= 1440` (which would let a regular user pick a 24h+ duration and create a range), `BookAction::execute` (user-panel entry point, called only from `Reservation\Main::book()`) rejects up front using `hasElevatedRole()` (admin **or** developer — developers are not blocked):

```php
if (!$user->hasElevatedRole() && !$isFullDay && $start->diffInDays($end) >= 1) {
    throw new \Exception('رزرو بلندمدت فقط از طریق پنل ادمین قابل ثبت است.');
}
```

`Main::book()` catches the exception and toasts the message (no 500). This guard lives in `BookAction`, NOT in the shared `ValidationService` pipeline, because `validateBooking` runs with `enforceAllRules=true` on elevated create too — a shared `skip_admin` rule would still run for elevated users (admin or developer) on create (the `skip_admin` optimization only applies to `validateEdit`) and wrongly reject elevated ranges. The user-panel-only `BookAction` is the correct place. Test: `RangeBookingTest::test_book_action_rejects_range_for_non_admin`.

## Range length cap — `max_range_days` policy + `RangeDuration` validator

The intraday `Duration` (`max_duration_minutes`) is skipped in range mode (a continuous hold isn't an 8h meeting), so without a separate cap a 20-day hold sails through. The `max_range_days` policy (per resource type, in `reservation_policies`, configurable in `ReservationPolicyResource` form next to `max_per_user`) closes that gap: the `RangeDuration` validator (registered in `ValidationService::$bookingRules` with `skip_admin => false`, so admin create **and** edit are both bound) rejects a range whose `start->diffInDays(end) > max_range_days` with `ReservationError::RangeTooLong` (ERR-025, "حداکثر مدت رزرو بلندمدت N روز است"). Unset/`null` ⇒ no cap. Only applies in range mode (`isRange()` early-return for ≤1-day). This also bounds the 24h threshold discontinuity from the range side. Tests: `RangeBookingTest::test_range_duration_*` (blocks exceeding, allows within, no-cap-when-unset, ignored-for-non-range).

## Release on a range — truncate ongoing, not future

`releaseAction` is range-aware: an **ongoing** range (`isRange() && start_time->isPast()`) is truncated to `end_time = now()` + `status = released` — the remainder of the span is freed for others (the `start_time->isPast()` guard prevents `end_time < start_time` for a not-yet-started range, which would be an invalid record). A **future** range (start in the future) falls back to the existing `status = released` only — it does *not* free the resource (use cancel for that), matching the existing intraday release semantics (released still blocks unless `allow_overlap_release`). Note the prior guide text claiming release "frees the resource" was inaccurate for intraday (it only frees when `allow_overlap_release` is on) — the statuses + admin-ops guide hints were rectified to match the actual `ResourceAvailability` behavior (`released` blocks unless `allow_overlap_release`). Tests: `ReservationResourceTest::test_release_truncates_ongoing_range_to_free_resource`, `test_release_does_not_truncate_future_range`.
