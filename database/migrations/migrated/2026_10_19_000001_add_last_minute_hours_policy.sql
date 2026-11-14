-- Contains THREE one-off changes for an EXISTING database (all safe to re-run):
--   1) `last_minute_hours` reservation policy, 2) `cancel_window_days` reservation policy,
--   3) the unique index `uniq_reads_document_user` on `reads` (document_id, user_id) — see the section near the end.
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

-- ------------------------------------------------------------------------------------------------
-- 3) `reads`: unique index (document_id, user_id) — one read row per document and user.
-- Fresh installs already get it from migrated/2026_09_19_000033_create_reads_table.php; an existing
-- database that was last updated before 2026-10-01 never received it (its separate migration
-- 2026_10_01_000001_add_unique_document_user_index_on_reads_table.php was folded into that file and deleted).
-- Safe to re-run: the ALTER only runs when the index is missing.
--
-- STEP A - check for duplicates FIRST. The ALTER fails with "Duplicate entry" if this returns any rows:
-- SELECT document_id, user_id, COUNT(*) AS n FROM `reads` GROUP BY document_id, user_id HAVING COUNT(*) > 1;
--
-- STEP B (only if STEP A returned rows) - keep the newest row of each pair and delete the older duplicates.
-- This DELETES data: take a backup first, then un-comment and run once.
-- DELETE r1 FROM `reads` r1 JOIN `reads` r2 ON r1.document_id = r2.document_id AND r1.user_id = r2.user_id AND r1.id < r2.id;
--
-- STEP C - add the index when it is missing (MySQL 5.7 / 8 compatible):
SET @has_reads_unique := (SELECT COUNT(*) FROM information_schema.statistics
                          WHERE table_schema = DATABASE() AND table_name = 'reads' AND index_name = 'uniq_reads_document_user');
SET @reads_sql := IF(@has_reads_unique = 0,
                     'ALTER TABLE `reads` ADD UNIQUE INDEX `uniq_reads_document_user` (`document_id`, `user_id`)',
                     'SELECT ''uniq_reads_document_user already exists - nothing to do'' AS info');
PREPARE reads_stmt FROM @reads_sql;
EXECUTE reads_stmt;
DEALLOCATE PREPARE reads_stmt;

-- Verify: should list the index with both columns, Non_unique = 0.
-- SHOW INDEX FROM `reads` WHERE Key_name = 'uniq_reads_document_user';

-- Rollback (removes the policy completely):
-- DELETE FROM reservation_policies WHERE `key` IN ('last_minute_hours', 'cancel_window_days');
-- Rollback of section 3 (drops only the unique index):
-- ALTER TABLE `reads` DROP INDEX `uniq_reads_document_user`;
