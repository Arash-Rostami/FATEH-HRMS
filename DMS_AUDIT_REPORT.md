# DMS Feature Audit Report

Based on a complete dry-run trace through the full document lifecycle across both the Filament Admin and Livewire User implementations, here are the concrete bugs and inconsistencies found:

## 1. Document Lifecycle & Visibility
*   **BUG: Fatal Logic Error in Visibility Scope**
    *   **File:** `app/Models/DMS.php` (line ~61, inside `scopeVisibleToUser()`)
    *   **What is wrong:** The query uses `auth()->user()?->profile?->department` in a `whereJsonContains` clause. This returns the Eloquent `Department` model instance, not a scalar ID or code. Passing a model to `whereJsonContains` attempts to match it against string codes in the `owners` array, which will universally fail, rendering department-specific documents invisible to their owners.
    *   **Minimal Fix:** Change to `auth()->user()?->profile?->department_id` (assuming department_id holds the string code, or `department?->code` based on the relations).
*   **BUG: File Download Path Mismatch (404 Error)**
    *   **File:** `app/Livewire/Dashboard/Dms/Main.php` (line ~92, inside `getAuthorizedFile()`)
    *   **What is wrong:** The method looks up the document using `where('file', $safeFilename)`. However, Filament's `FileUpload` (with `->directory('dms')`) stores the path relative to the disk (e.g., `dms/FATEH-DMS-....pdf`). Matching `basename()` exactly against the prefixed database column will always return `null`, causing legitimate downloads to fail with a 404.
    *   **Minimal Fix:** Update the query to account for the directory prefix: `where('file', "dms/{$safeFilename}")` or use a `LIKE` clause.

## 2. Read Confirmation Correctness
*   **BUG: Insecure Direct Object Reference (IDOR)**
    *   **File:** `app/Livewire/Dashboard/Dms/Actions/ConfirmReadAction.php` (line ~11, inside `execute()`)
    *   **What is wrong:** The action blindly queries `$document = DMS::find($docId);` without applying the visibility scope. A user can manipulate the Livewire payload to confirm reading an `obsolete` document or one securely isolated to another department.
    *   **Minimal Fix:** Scope the lookup to authorized documents only: `$document = DMS::visibleToUser()->find($docId);`.
*   **BUG: Stale Read Confirmations on Edited Documents**
    *   **File:** `app/Models/DMS.php`
    *   **What is wrong:** `Read` records are permanently linked via `document_id`. If an admin uploads a new file version or significantly updates the document revision, existing read confirmations remain intact. A user who confirmed reading "Version 1" will silently appear as having read "Version 2".
    *   **Minimal Fix:** Add a `saving` or `updated` Eloquent event on the `DMS` model that truncates/archives associated `Read` relations if the `file` or `revision` attributes are modified.
*   **BUG: Race Condition / Duplicate Confirmations**
    *   **File:** `app/Livewire/Dashboard/Dms/Actions/ConfirmReadAction.php` (line ~14)
    *   **What is wrong:** `firstOrCreate` is not atomic without a database unique constraint. Concurrent Livewire requests from an impatient user clicking multiple times will create duplicate `Read` rows, falsely inflating read volumes.
    *   **Minimal Fix:** Ensure a composite unique index on `['user_id', 'document_id']` exists in the database migration for the `reads` table, and optionally use `upsert`.

## 3. Admin Visibility of Reads
*   **BUG: Phantom Metric Data Display**
    *   **File:** `app/Filament/Resources/DmsResource/RelationManagers/ReadsRelationManager.php` (line ~55)
    *   **What is wrong:** The table includes `TextColumn::make('combined_read_count')` which attempts to read this field from the `Read` model. However, `ConfirmReadAction` increments `combined_read_count` exclusively on the parent `DMS` model (`$document->increment('combined_read_count')`). This column will permanently display blank or `0` for all records in the relation manager.
    *   **Minimal Fix:** Remove the `combined_read_count` column from the RelationManager's table schema entirely.

## 4. Enum Handling
*   **BUG: Bypassed Enum and Hardcoded States**
    *   **File:** `app/Models/DMS.php`
    *   **What is wrong:** The `status` field is not cast to the available Enum. The model relies on hardcoded static mapping arrays (`$statusMapping`, `$statusIconMapping`) and uses string literals in query scopes (`->where('status', 'live')`), completely duplicating and ignoring the standardized `DocumentStatus` Enum. If an admin edits statuses in the Enum, the User panel's hardcoded logic will break.
    *   **Minimal Fix:** Add `'status' => \App\Filament\Resources\DmsResource\Enums\DocumentStatus::class` to the `$casts` array in `DMS.php`. Remove the static arrays and replace instances of `'live'` with `DocumentStatus::Live->value`.

## 5. Filter Parsing (Architectural Constraint Violation)
*   **BUG: Ignored Comma-Separated Tags**
    *   **File:** `app/Livewire/Dashboard/Dms/Main.php` (lines ~76 and ~196)
    *   **What is wrong:** As highlighted in your architecture constraints, the `tags` column may contain comma-separated strings inside its JSON arrays (e.g., `{"Category": "Safety, Legal"}`). `filterGroups()` directly loops over values without exploding them, creating concatenated UI filter buttons. Furthermore, `getBaseQuery()` uses `whereJsonContains`, which explicitly performs exact-match checks and will fail entirely against comma-separated internal values.
    *   **Minimal Fix:** Add `explode(',', $v)` inside `filterGroups()` and map them with `trim()`. Update `getBaseQuery()` to utilize `LIKE` clauses alongside `whereJsonContains` properly.

## 6. Assorted Edge Cases
*   **Empty Document List State:** Evaluated cleanly. If no results are yielded, `$this->docIds` initializes to `[]` and the `docs()` computed property returns an empty `collect()`, failing gracefully in the UI.
*   **No Assigned Owner:** Evaluated cleanly. A document saved with empty `owners` and `users` will correctly fail the `orWhereJsonContains` cascade in `scopeVisibleToUser` and gracefully remain invisible to non-admins.
*   **GenerateOwnerPreview Action:** Evaluated cleanly. It correctly guards against empty states, handles the 'ALL' wildcard properly, and executes a clean grouped aggregate query with safe array mappings.
*   **User Removed After Reading:** Evaluated cleanly. The `ReadsRelationManager` uses `TextColumn::make('user.name')`, which is null-safe by default under Eloquent and won't crash if the user was hard-deleted.
