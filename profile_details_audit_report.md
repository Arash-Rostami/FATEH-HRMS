# Profile Details Form Audit Report

Based on the dry-run trace of both the Admin (Filament) and User (Livewire) implementations, here are the concrete bugs and inconsistencies found, indexed with exact file paths and line references:

### 1. Missing Core Profile Fields in User Panel (Silent Data Loss)
*   **Files:**
    *   `app/Livewire/Dashboard/Profile/Info.php` (Line 44)
    *   `app/Livewire/Dashboard/Profile/Forms/ProfileForm.php` (Lines 129-148)
*   **What is wrong:** Properties like `personnel_id`, `department_id`, `employment_type`, `employment_status`, and `position` are defined as properties with validation rules in `ProfileForm`. However, they are completely omitted from the `only([...])` array used to hydrate the form in `Info::mount()`. Furthermore, they are missing from the `ProfileForm::getProfileData()` array. Because they are omitted, users cannot see or edit them. Even if a user bypassed the frontend to submit them, `SaveProfileAction` would silently drop them.
*   **Minimal Fix:** Add `'personnel_id', 'department_id', 'employment_type', 'employment_status', 'position'` to the `only([])` array in `Info::mount()`, and include them in the return array of `ProfileForm::getProfileData()`.

### 2. Absent Start and End Dates in User Panel
*   **Files:**
    *   `app/Filament/Resources/ProfileResource/Schemas/ProfileFormPresenter.php` (Lines 314-322, 510-518)
    *   `app/Livewire/Dashboard/Profile/Forms/ProfileForm.php`
*   **What is wrong:** The Admin panel handles `start_date` and `end_date` fully via `PersianDateFieldService`. The Livewire User panel completely lacks these properties (`ProfileForm` has no fields for them), preventing users from seeing or updating their employment dates.
*   **Minimal Fix:** Add `start_date` and `end_date` (and their `Year`, `Month`, `Day` components) to `ProfileForm`, `Info::mount()`, and handle compilation in `SaveProfileAction` exactly like `birthdate`.

### 3. Enum Integrity Bypass (Invalid Data Risk)
*   **Files:**
    *   `app/Livewire/Dashboard/Profile/Forms/ProfileForm.php` (Lines 14, 26, 38, 62, 68)
*   **What is wrong:** The Admin form strictly maps select options to typed Enums (`Degree::class`, `Position::class`, `WorkExperience::class`, etc.). The Livewire User panel validates these fields merely as raw strings (e.g., `#[Validate('required|string|max:255')]` for `degree`, `#[Validate('required|string|max:50')]` for `work_experience`). A malicious user could bypass the UI dropdowns and inject arbitrary strings into enum-backed database columns.
*   **Minimal Fix:** Update the Livewire `#[Validate]` attributes to use Laravel's Enum rule: `#[Validate(['required', \Illuminate\Validation\Rule::enum(\App\Filament\Resources\ProfileResource\Enums\Degree::class)])]`.

### 4. Flawed Birthdate Requirement Logic (Silent Failures)
*   **Files:**
    *   `app/Livewire/Dashboard/Profile/Forms/ProfileForm.php` (Lines 77-85, 102-104)
    *   `app/Livewire/Dashboard/Profile/Actions/SaveProfileAction.php` (Lines 27-30)
*   **What is wrong:** `birthYear`, `birthMonth`, and `birthDay` are defined with `#[Validate('nullable|integer')]`. Although custom messages exist for `.required` failures, the core rule lacks `required_with` dependencies. Thus, a user can submit a partially empty date (e.g., just the Year). The form passes validation, but `SaveProfileAction` fails the condition `if ($form->birthYear && $form->birthMonth && $form->birthDay)`. The date update is silently ignored without alerting the user.
*   **Minimal Fix:** If `birthdate` is mandatory, change the rule to `required|integer`. If optional, enforce mutual dependency using `nullable|integer|required_with:birthYear,birthMonth,birthDay`.

### 5. Partially Filled Detail Dates Silently Deleted in User Panel
*   **Files:**
    *   `app/Livewire/Dashboard/Profile/Actions/SaveDetailsAction.php` (Lines 31-33)
    *   `app/Livewire/Dashboard/Profile/Forms/DetailsForm.php` (Lines 29-31)
*   **What is wrong:** In the user details form, if a user fills out only the Year for a dynamic date (e.g., `marriage_dateYear`), `SaveDetailsAction` assigns `null` to `$formatted[$key]` because it fails the `($year && $month && $day)` check. Since `DetailsForm` lacks dependency rules (`required_with`), the form submits successfully. `Profile::syncDetails()` then sees the `null` value and totally deletes that detail record from the database. The user's partial entry is silently wiped out.
*   **Minimal Fix:** Add cross-field dependencies to `DetailsForm::rules()`: `$rules["values.{$key}Year"] = "nullable|integer|...|required_with:values.{$key}Month,values.{$key}Day";`. Do the same for Month and Day.

### 6. Date Fields Valid Year Range Mismatch
*   **Files:**
    *   `app/Filament/Resources/ProfileResource/Schemas/ProfileFormPresenter.php` (Line 256)
    *   `app/Livewire/Dashboard/Profile/Forms/DetailsForm.php` (Line 29)
*   **What is wrong:** The Admin details form uses `PersianDateFieldService` with explicit bounds `yearFrom: 1330` to `yearTo: 1410`. The Livewire `DetailsForm` rules limit the year input to `min:1300|max:1500`.
*   **Minimal Fix:** Update `DetailsForm::rules()` to perfectly match the Admin bounds: `min:1330|max:1410`.

### 7. Custom Admin Keys Break User Dashboard Sync
*   **Files:**
    *   `app/Filament/Resources/ProfileResource/Schemas/ProfileFormPresenter.php` (Line 213)
    *   `app/Livewire/Dashboard/Profile/Details.php` (Line 24)
*   **What is wrong:** The Admin panel allows adding unmapped, ad-hoc keys using `->createOptionForm()` inside the `details()` schema. However, `Details::mount()` and `SaveDetailsAction` in the User Livewire panel iterate *exclusively* over the static dictionary `ProfileDetailCatalog::keys()`. If an admin creates a custom key, the user will never see it, and it will remain a hidden ghost record.
*   **Minimal Fix:** Remove `->createOptionForm()` from the Filament schema to strictly enforce the Catalog dictionary across both panels.

### 8. Zip Code Nullability Inconsistency
*   **Files:**
    *   `app/Filament/Resources/ProfileResource/Schemas/ProfileFormPresenter.php` (Line 545)
    *   `app/Livewire/Dashboard/Profile/Forms/ProfileForm.php` (Line 44)
*   **What is wrong:** In the Admin panel, `zipCode()` uses `maxLength(20)` but omits `->required()`, meaning it is optional. The User panel strictly requires it via `#[Validate('required|string|max:20')]`. An administrator can clear the zip code, but the user is blocked from saving their profile if it is empty.
*   **Minimal Fix:** Align the forms by either adding `->required()` to the Filament `zipCode()` input, or changing the Livewire rule to `#[Validate('nullable|string|max:20')]`.

### 9. Personnel ID Uniqueness Validation Gap
*   **Files:**
    *   `app/Filament/Resources/ProfileResource/Schemas/ProfileFormPresenter.php` (Lines 484-490)
    *   `app/Livewire/Dashboard/Profile/Forms/ProfileForm.php` (Lines 11-12)
*   **What is wrong:** The Admin panel secures `personnel_id` against duplicates using `->unique(ignoreRecord: true)`. The Livewire panel merely validates it as `nullable|string|max:50`. If users are allowed to edit their `personnel_id`, they could overwrite it with an ID already belonging to someone else, breaking database integrity.
*   **Minimal Fix:** Update the Livewire rule to enforce database uniqueness: `#[Validate('nullable|string|max:50|unique:profiles,personnel_id')]`.
