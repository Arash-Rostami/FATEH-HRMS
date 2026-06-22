# Exhaustive In-Memory QA Audit Report: Taskboard Feature

### Executive Summary

The audit discovered a total of **6 distinct issues**: **1 Critical**, **4 High**, and **1 Medium**. The most significant vulnerability relates to the absence of robust Jalali date validation upon creation/update of a Task. A critical flaw also exists at the data integrity layer, where the `task_details` table lacks a proper foreign key constraint to `departments`, enabling silent orphaning.

We found the IDOR and State Drift concerns around the `editingTaskId` to be sufficiently mitigated, as Filament’s component inherently locks properties via `#[Locked]` and uses an explicit `.can_change_status` check in the underlying Action, resulting in a null finding on that specific point. On the positive side, performance sanity holds up cleanly with the correct implementation of `once()` caching and zero N+1 eager-loading regressions.

---

### A. Database / Models

**[SEVERITY: High] Department deletion does not cascade to TaskDetail**
- **Category:** Data Integrity
- **Location:** `database/migrations/*_create_task_details_table.php:12`
- **Repro:** Try to delete a `Department` that has a `TaskDetail` referencing its `code`. (Simulated in tinker: `DB::table('departments')->where('code', $testDept)->delete();`)
- **Expected:** The migration for `task_details.department_id` should declare a foreign key constraint to `departments.code` with `nullOnDelete()` or `cascadeOnDelete()` to prevent orphaned relationships.
- **Actual:** The migration implements `$table->string('department_id')->nullable();` and a manual `$table->index('department_id');`, but it completely omits the explicit foreign key schema logic. Therefore, a `Department` deletion will silently succeed and leave orphaned IDs persisting in `task_details.department_id`.

---

### B. Livewire user panel — happy paths

*(No specific bugs found here. `User::setExtraValue('preferences.taskboard_density', ...)` correctly persists without issue. Both attachment saving and bulk operations properly map relationships.)*

---

### C. Livewire user panel — edge cases / abuse

**[SEVERITY: Critical] Livewire Task Action does not have proper Date Validation for Invalid Jalali Date**
- **Category:** Validation
- **Location:** `app/Livewire/Dashboard/TaskBoard/Actions/CreateTaskAction.php:28` & `UpdateTaskAction.php:33`
- **Repro:** Call `CreateTaskAction` via Livewire form submission where `$form->deadlineMonth = 12` and `$form->deadlineDay = 30` (in a non-leap year).
- **Expected:** The action should safely throw a Livewire-friendly `ValidationException` when resolving an impossible calendar date (e.g. by using `Morilog\Jalali\CalendarUtils::checkDate($y, $m, $d, true)` prior to parsing).
- **Actual:** `CalendarUtils::createCarbonFromFormat()` will blindly parse the date format and will silently shift/wrap the date forward or throw an unhandled fatal framework exception internally, leaking a 500 error payload directly to the UI layer instead of a graceful Livewire Form property error.

**[SEVERITY: Medium] TaskBoard DeleteTaskAction returns false instead of aborting for authorization failure**
- **Category:** Consistency / Authorization
- **Location:** `app/Livewire/Dashboard/TaskBoard/Actions/DeleteTaskAction.php:14`
- **Repro:** Call `DeleteTaskAction->execute($taskId)` where `$task->can_delete` evaluates to false.
- **Expected:** Following the documented pattern rules found across other Actions (like `UpdateTaskAction::execute()` using `abort_if(...)`), an Action encountering a permission block should abort or throw a specific exception to halt execution immediately.
- **Actual:** It returns `false` silently. *(Note: While inconsistent with `abort_if()`, the underlying permission is still successfully enforced. However, this silent return differs significantly from the pattern established in other Actions.)*

---

### D. Filament admin panel — parity checks against section B/C

*(Filament side correctly aligns with the database model relations and does not experience eager-load issues. The `date` display on Filament `TaskInfolistPresenter` uses the identical `created_formatted` and `deadline_formatted` accessors as the Livewire side, confirming adherence to Rule 32 of `filament.md`.)*

**[SEVERITY: High] TaskState enum labels duplicate hardcoded strings in Blade**
- **Category:** Consistency
- **Location:** `app/Filament/Resources/TaskResource/Enums/TaskState.php` & `resources/views/livewire/dashboard/taskboard/detail-fields.blade.php:60`
- **Repro:** Compare how `TaskState` labels are defined inside Filament vs how they are displayed in the Livewire `detail-fields.blade.php` `<select>` dropdown.
- **Expected:** Livewire should either leverage the `TaskState` enum logic directly, or both should resolve from the central `lang/fa/resources/task/strings.php` file. In addition, the validation rule in `TaskForm` should use `Illuminate\Validation\Rule::enum(TaskState::class)`.
- **Actual:** The Livewire Blade component hardcodes the raw HTML: `<option value="extension">تمدید</option>`. Furthermore, `TaskForm.php` explicitly hardcodes the `in:extension,suspension,completion` validation instead of mapping natively to the Enum.

---

### E. Cross-panel string/label consistency (systematic)

**[SEVERITY: High] Livewire 'action_source_domain' label uses hardcoded string instead of translation key**
- **Category:** Consistency
- **Location:** `app/Livewire/Dashboard/TaskBoard/Forms/TaskForm.php:70` & `resources/views/livewire/dashboard/taskboard/detail-fields.blade.php:55`
- **Repro:** Examine the Livewire `TaskForm` labels array and `detail-fields.blade.php` rendering for the `actionSourceDomain` input.
- **Expected:** Both panels must read identically from the localization file via `__('resources/task/strings.fields.action_source_domain')`.
- **Actual:** The Livewire component bypasses the language map completely, using a hardcoded literal `'حوزه منشاء اقدام'` string for both the Blade label rendering and validation attributes.

**[SEVERITY: High] Livewire 'action_source' validation max length mismatch**
- **Category:** Consistency
- **Location:** `app/Livewire/Dashboard/TaskBoard/Forms/TaskForm.php:103`
- **Repro:** Compare the Livewire validation error messages in `TaskForm` against `lang/fa/resources/task/strings.php`.
- **Expected:** Livewire should consume the `__('resources/task/strings.validation.action_source.max_length')` payload dynamically, as implemented in Filament.
- **Actual:** `TaskForm` explicitly hardcodes `'actionSource.max' => 'منشاء اقدام نباید بیش از ۲۰۰۰ کاراکتر باشد.'` directly within its PHP Class property payload, ensuring future translation changes will not synchronize.

---

### F. Performance sanity

*(No severe findings detected. Performance sanity check completed. `Department::getCachedOptions()` executes effectively with Laravel's `once()` to bypass sequential duplicate queries, eliminating N+1 potential. `Main::loadTasks()` safely executes its `$relationsToLoad` map cleanly.)*
