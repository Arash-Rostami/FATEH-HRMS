RESERVATION MODULE — EDGE CASE COVERAGE CHECKLIST

USER STATE
inactive user cannot book
user without booking permission cannot book
flat JSON booking format recognized
array-of-objects booking format recognized
empty booking column denies all permissions

RESOURCE STATE
inactive resource cannot be booked
resource type matched against user booking permission

TIME INTEGRITY
booking in the past blocked
end time before or equal to start time blocked
full day bookings skip duration and allowed hours checks

BOOKING WINDOW
booking beyond window_days ceiling blocked
null window_days allows all future bookings
window_days zero blocks all future bookings
window_hours minimum notice applies across day boundaries
window_hours zero means no minimum notice

ALLOWED DAYS
booking on disallowed day blocked
empty allowed_days array blocks all days
null allowed_days allows all days

ALLOWED HOURS
booking outside allowed hours blocked
booking ending exactly at allowed end time passes
booking one minute past allowed end time blocked
overnight allowed hours window handled correctly
full day bookings skip allowed hours check

DURATION
booking below minimum duration blocked
booking above maximum duration blocked
full day bookings skip duration check
start equal to or after end blocked before duration calculated

POLICY VALUES
false policy values preserved not stripped
null policy values treated as feature off

RESOURCE AVAILABILITY
double booking same resource same slot blocked
multi day reservation overlap correctly detected
full day conflict detected via universal overlap formula
null start_time full day reservation detected as conflict
released slot blocks booking when allow_overlap_release is false
allow_overlap_release defaults to false matching ui behavior
excludeId prevents own reservation blocking its own edit

USER CONFLICT
same user overlapping booking on same resource type blocked
multi day overlap correctly detected for user conflict

ACTIVE LIMIT
user at monthly limit cannot book
released reservations also count toward active limit
limit scoped per resource type not global

CANCELLATION LIMIT BEFORE BOOKING
user who cancelled too many times cannot book
cancellation count scoped per resource type not global
null cancelled_at treated as recent not ignored

RECURRENCE
recurring booking blocked when allow_repeat is false
each occurrence individually validated before creation
conflicting occurrences skipped not silently created

EDIT VALIDATION
admin can edit any reservation without false conflict
non admin editing own reservation not falsely blocked

CANCELLATION RULES
cannot cancel already cancelled reservation
user cannot cancel another users reservation
admin can cancel any reservation
cancelling one occurrence cancels whole series when partial cancel disabled
cancelling one occurrence only affects that one when partial cancel enabled
cancellation count scoped per resource type
null cancelled_at in history treated as recent

SERIES INTEGRITY
parent_id self reference blocked at model save
parent_id self reference blocked at series cancel runtime
cancelling parent cancels all active children
already cancelled children not re-cancelled in series cancel

EVENT CALENDAR
event records created for meeting master booking
event records created for each occurrence in meeting series
cancelling meeting reservation removes linked event records

HISTORY VISIBILITY
active future reservations appear in upcoming
active past reservations appear in previous
cancelled reservations appear in cancelled tab
released reservations not visible in any tab

HAPPY PATH
full booking lifecycle create verify cancel verify
two users same slot first wins second blocked
same user same time different resource blocked
