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
