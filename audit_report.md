# Eloquent Models, Factories, and Seeders Audit Report

## Model: User
- **Status/Fix Applied**: Adjusted the booking JSON array so it isn't a string to match Model $casts.

## Model: Credential
- **Status/Fix Applied**: Changed user_id FK to point to valid User IDs, and password to random password instead of paragraph.

## Model: ReservationPolicy
- **Status/Fix Applied**: Correct. No changes made.

## Model: Feed
- **Status/Fix Applied**: Changed user_id FK to reference existing users. Set media_paths and poll_options to valid arrays.

## Model: FAQ
- **Status/Fix Applied**: Changed user_id and department_id FKs to reference existing IDs.

## Model: Reservation
- **Status/Fix Applied**: Changed user_id and resource_id FKs to reference existing records.

## Model: Suggestion
- **Status/Fix Applied**: Changed user_id to reference existing users.

## Model: Authority
- **Status/Fix Applied**: Changed user_id and department_id FKs to reference existing records.

## Model: Report
- **Status/Fix Applied**: Changed user_id and department_id FKs. Removed 'active' attribute which does not exist on table.

## Model: EnergyTest
- **Status/Fix Applied**: Changed user_id FK. Set integer scores instead of empty strings.

## Model: Onboarding
- **Status/Fix Applied**: Changed user_id FK.

## Model: Profile
- **Status/Fix Applied**: Major discrepancy: changed user_id and department_id FKs. Replaced manager_id, personnel_code, phone, bio, and birthday with personnel_id, landline, about_me, birthdate, and added required fields gender, employment_type, marital_status, employment_status, degree.

## Model: Message
- **Status/Fix Applied**: Changed sender_id and recipient_id FKs. Set reply_to_id to null instead of random ID.

## Model: Comment
- **Status/Fix Applied**: Changed user_id and feed_id FKs. Renamed 'body' to 'content'.

## Model: DMS
- **Status/Fix Applied**: Changed combined_read_count to 0. Resolved array of department codes and user ids with safe fallbacks.

## Model: Link
- **Status/Fix Applied**: Correct. No changes made.

## Model: Reaction
- **Status/Fix Applied**: Changed user_id and feed_id FKs.

## Model: Event
- **Status/Fix Applied**: Changed user_id FK. Removed non-existent fields date_jalali and date_time_part. Corrected date to valid timestamp.

## Model: Permission
- **Status/Fix Applied**: Changed user_id FK.

## Model: Post
- **Status/Fix Applied**: Changed user_id FK.

## Model: Read
- **Status/Fix Applied**: Changed document_id and user_id FKs to reference existing records via inRandomOrder() by fixing syntax errors in the namespace bindings.

## Model: Task
- **Status/Fix Applied**: Changed user_id and assigned_to FKs. Corrected deadline to valid timestamp.

## Model: Resource
- **Status/Fix Applied**: Correct. No changes made.

## Model: Review
- **Status/Fix Applied**: Changed user_id, suggestion_id, and department_id FKs.

## Model: Ad
- **Status/Fix Applied**: Correct. No changes made.

## Model: Photo
- **Status/Fix Applied**: Major discrepancy: changed user_id to department_id FK. Replaced 'url' with 'path' array. Changed 'caption' to 'title' and added 'description' and 'event_date'.

## Model: Ticket
- **Status/Fix Applied**: Changed requester_id and assigned_to FKs. Added satisfaction_score as integer instead of empty string.

## Model: Department
- **Status/Fix Applied**: Correct. No changes made.

## Seeders
- Verified seeders structure. All factories are fixed to fall back correctly, therefore `Model::factory(N)->create()` in seeders won't throw constraint violations, and no direct over-seeding logic was introduced in `DatabaseSeeder` or individual seeders.
