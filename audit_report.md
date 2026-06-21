# Model, Factory, and Seeder Audit Report

This report outlines the discrepancies found between Eloquent models/migrations and their corresponding factories/seeders, along with the fixes applied. The migration schema is the absolute source of truth.

## Summary of Fixes

### 1. `User` Model
- **Discrepancy:** The `UserFactory` was generating columns `booking`, `last_seen`, `extra`, and `remember_token` which do not exist on the `users` table according to `0001_01_01_000000_create_users_table.php`.
- **Authoritative Source:** `database/migrations/migrated/0001_01_01_000000_create_users_table.php`.
- **Fix:** Removed the nonexistent keys `booking`, `last_seen`, `extra`, and `remember_token` from `UserFactory`.

### 2. `Profile` Model
- **Discrepancy:** The `ProfileFactory` had incorrect column names and extra columns:
  - `personnel_code` instead of `personnel_id`
  - `phone` instead of `landline`
  - `bio` instead of `about_me`
  - `birthday` instead of `birthdate`
  - `manager_id` which does not exist.
  - Hardcoded foreign keys `user_id` and `department_id` to random integers, violating FK constraints.
- **Authoritative Source:** `database/migrations/migrated/2026_02_15_080828_create_profiles_table.php`.
- **Fix:**
  - Renamed columns to match the migration.
  - Removed `manager_id`.
  - Replaced random integers for foreign keys with `\App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory()` and similarly for `department_id`.

### 3. `Comment` Model
- **Discrepancy:** `CommentFactory` used `body` instead of the correct `content` column. Hardcoded `feed_id` and `user_id` to random integers.
- **Authoritative Source:** `database/migrations/migrated/2026_02_17_112057_create_feeds_reactions_comments_tables.php`.
- **Fix:**
  - Renamed `body` to `content`.
  - Used `inRandomOrder()->value('id')` with factory fallbacks for `feed_id` and `user_id`.

### 4. `Event` Model
- **Discrepancy:** `EventFactory` generated `date_jalali` and `date_time_part` which are not in the `events` table, and generated `user_id` which also doesn't exist. Date was set to an empty string.
- **Authoritative Source:** `database/migrations/migrated/2026_02_18_072127_create_events_table.php`.
- **Fix:**
  - Removed `date_jalali`, `date_time_part`, and `user_id`.
  - Set `date` to a valid `fake()->date()`.

### 5. `Photo` Model
- **Discrepancy:** `PhotoFactory` generated `url`, `caption`, and `user_id` which don't match the schema (`path`, `title`). The `department_id` was missing. The `path` column is cast to `json` in the schema.
- **Authoritative Source:** `database/migrations/migrated/2026_02_19_221021_create_photos_table.php`.
- **Fix:**
  - Renamed `url` to `path` and wrapped it in `json_encode([fake()->imageUrl()])`.
  - Renamed `caption` to `title`.
  - Removed `user_id`.
  - Added `department_id` using the fallback pattern.

### Models that Required No Changes (Already Correct)
The following models and factories were verified against their migrations and required no changes because their definitions strictly matched the schema and foreign keys were appropriately handled or unconstrained.
- `Credential`
- `ReservationPolicy`
- `Feed`
- `FAQ`
- `Reservation`
- `Suggestion`
- `Authority`
- `ProfileDetail`
- `Report`
- `EnergyTest`
- `Onboarding`
- `Message`
- `DMS`
- `Link`
- `Reaction`
- `Permission`
- `Post`
- `Read`
- `Task`
- `Resource`
- `Review`
- `Ad`
- `Ticket`
- `Department`

*Note: For complex JSON fields like `DMS.owners`, the existing factory already implemented the fallback pattern properly (`[Department::inRandomOrder()->value('code') ?? 'ALL']`).*
