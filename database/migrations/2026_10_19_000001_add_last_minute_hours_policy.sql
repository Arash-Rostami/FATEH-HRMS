-- Adds TWO reservation policies to every existing resource type: `last_minute_hours` and `cancel_window_days`.
-- Adds the `last_minute_hours` reservation policy (رزرو لحظه‌آخری) to every existing resource type.
-- Run once by hand on production (phpMyAdmin / mysql client). Safe to re-run: existing rows are kept.
-- Value NULL = feature OFF, so running this changes no behaviour until an admin fills the field
-- in the policy form (Reservation policies -> edit a type -> "رزرو لحظه‌آخری (ساعت)").
-- Future types get the row automatically from TypeProvisioner.

INSERT IGNORE INTO reservation_policies (resource_type, `key`, `value`, created_at, updated_at)
SELECT DISTINCT resource_type, 'last_minute_hours', NULL, NOW(), NOW()
FROM reservation_policies;

-- Also adds `cancel_window_days` (rolling window, in days, for the cancellation quota `max_cancel_count`).
-- Default 30 = exactly the previous hard-coded behaviour, so this changes nothing until an admin edits it.
INSERT IGNORE INTO reservation_policies (resource_type, `key`, `value`, created_at, updated_at)
SELECT DISTINCT resource_type, 'cancel_window_days', '30', NOW(), NOW()
FROM reservation_policies;

-- Verify: one row per type; last_minute_hours NULL, cancel_window_days 30 right after the first run.
-- SELECT resource_type, `key`, `value` FROM reservation_policies WHERE `key` IN ('last_minute_hours', 'cancel_window_days') ORDER BY resource_type, `key`;

-- Optional example: turn it on for parking (24 hours) AFTER checking that window_hours of that type is smaller.
-- UPDATE reservation_policies SET `value` = '24', updated_at = NOW() WHERE resource_type = 'spot' AND `key` = 'last_minute_hours';

-- Rollback (removes the policy completely):
-- DELETE FROM reservation_policies WHERE `key` IN ('last_minute_hours', 'cancel_window_days');
