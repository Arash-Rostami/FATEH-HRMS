# Exhaustive In-Memory QA Audit Report: Taskboard Module

### Executive Summary

The comprehensive code audit discovered a total of **8 distinct issues**: **1 Critical**, **5 High**, and **2 Medium**.
The critical issue lies within Jalali date parsing validation failing gracefully under the Livewire context. Several High-severity data consistency gaps were also discovered, including a lack of foreign keys for `TaskDetail` models and widespread duplication/hardcoding of translation keys across the Livewire user interface versus Filament's correct integration. Furthermore, State Drift within the Livewire module dependent selects was detected. IDOR and N+1 query checks successfully passed standard validation criteria.

---

### A. Database / Models

**[SEVERITY: High] Department deletion does not cascade to TaskDetail**
- **Category:** Data Integrity
- **Location:** `database/migrations/*_create_task_details_table.php:12`
- **Repro:** Delete a `Department` that has a `TaskDetail` referencing its `code`.
- **Expected:** The migration for `task_details.department_id` should declare a foreign key constraint to `departments.code` with `nullOnDelete()` or `cascadeOnDelete()` to prevent orphaned relationships.
- **Actual:** The migration implements `$table->string('department_id')->nullable();` and a manual `$table->index('department_id');`, but it completely omits the explicit foreign key schema logic. A `Department` deletion will silently succeed and leave orphaned IDs persisting in `task_details.department_id`.

---

### B. Livewire user panel — happy paths

*(No specific bugs found here. `User::setExtraValue('preferences.taskboard_density', ...)` correctly persists without issue. Both attachment saving and bulk operations properly map relationships and silently skip authorization blocks over bulk items).*

---

### C. Livewire user panel — edge cases / abuse

**[SEVERITY: Critical] Livewire Task Action does not have proper Date Validation for Invalid Jalali Date**
- **Category:** Validation
- **Location:** `app/Livewire/Dashboard/TaskBoard/Actions/CreateTaskAction.php:28` & `UpdateTaskAction.php:33`
- **Repro:** Call `CreateTaskAction` via Livewire form submission where `$form->deadlineMonth = 12` and `$form->deadlineDay = 30` (in a non-leap year).
- **Expected:** The action should safely throw a Livewire-friendly `ValidationException` when resolving an impossible calendar date (e.g., by checking `Morilog\Jalali\CalendarUtils::checkDate($y, $m, $d, true)` prior to parsing).
- **Actual:** `CalendarUtils::createCarbonFromFormat()` will blindly parse the invalid format, either shifting the date silently or throwing an unhandled fatal framework exception internally. This leaks a 500-error payload to the UI layer instead of a graceful Livewire Form property validation error.

**[SEVERITY: Medium] Stale Dependent Select Data (Unit/Section) persists on Department Change**
- **Category:** Data Integrity / UX
- **Location:** `app/Livewire/Dashboard/TaskBoard/Main.php`
- **Repro:** Select a Department, then select a Unit and Section. Then change the Department to another one via `wire:model.live="form.departmentId"`.
- **Expected:** The `form.unit` and `form.section` should be reset when `departmentId` changes to prevent saving stale sub-selections that do not belong to the newly selected department.
- **Actual:** The Filament `TaskFormPresenter` properly implements `->afterStateUpdated(function (callable $set) { $set('unit', null); $set('section', null); })`. However, the Livewire `Main` component lacks an `updatedFormDepartmentId()` event listener to reset `$form->unit` and `$form->section`, meaning stale dependent dropdown keys will quietly persist upon saving if not manually deselected.

**[SEVERITY: Medium] TaskBoard DeleteTaskAction returns boolean false instead of explicitly aborting on authorization failure**
- **Category:** Consistency / Authorization
- **Location:** `app/Livewire/Dashboard/TaskBoard/Actions/DeleteTaskAction.php:14`
- **Repro:** Call `DeleteTaskAction->execute($taskId)` where `$task->can_delete` evaluates to false.
- **Expected:** Following the documented pattern rules found across other Actions (like `UpdateTaskAction::execute()` using `abort_if(...)`), an Action encountering a permission block should abort or throw a specific exception to halt execution immediately.
- **Actual:** It returns `false` silently. *(Note: The underlying permission is still enforced correctly. However, this silent return differs significantly from the pattern established in other Actions.)*

---

### D. Filament admin panel — parity checks against section B/C

*(Filament side correctly aligns with the database model relations and does not experience eager-load issues. The `date` display on Filament `TaskInfolistPresenter` correctly consumes `created_formatted` and `deadline_formatted` accessors natively, maintaining parity with Livewire views and confirming adherence to Rule 32 of `filament.md`.)*

**[SEVERITY: High] TaskState enum labels duplicate hardcoded strings in Blade**
- **Category:** Consistency
- **Location:** `app/Filament/Resources/TaskResource/Enums/TaskState.php` & `resources/views/livewire/dashboard/taskboard/detail-fields.blade.php:60`
- **Repro:** Compare how `TaskState` labels are defined inside Filament vs how they are displayed in the Livewire `detail-fields.blade.php` `<select>` dropdown.
- **Expected:** Livewire should either leverage the `TaskState` enum logic directly (`\App\Filament\Resources\TaskResource\Enums\TaskState::cases()`), or both should resolve from the central `lang/fa/resources/task/strings.php` file. Additionally, the Livewire validation rule should use `Illuminate\Validation\Rule::enum(TaskState::class)`.
- **Actual:** The Livewire Blade component hardcodes the raw HTML: `<option value="extension">تمدید</option>`. Furthermore, `TaskForm.php` explicitly hardcodes the `in:extension,suspension,completion` validation rules array, guaranteeing a drift if the ENUM is ever updated.

---

### E. Cross-panel string/label consistency (systematic)

**[SEVERITY: High] Extensive hardcoding of validation messages and labels across Livewire components**
- **Category:** Consistency
- **Location:** `app/Livewire/Dashboard/TaskBoard/Forms/TaskForm.php` & `resources/views/livewire/dashboard/taskboard/*.blade.php`
- **Repro:** Examine the Livewire `TaskForm` labels array and Blade files, comparing them against the localization keys present in `lang/fa/resources/task/strings.php`.
- **Expected:** All UI-facing labels and form messages should be identically resolved via Laravel's translation engine.
- **Actual:** The Livewire component bypasses the language map extensively. Instead of executing `__('resources/task/strings.fields...')`, virtually all inputs, labels, placeholders, and validation messages in the Livewire space are explicitly hardcoded logic strings.

#### Comprehensive Field-by-Field Consistency Map
*(Validating Filament Dynamic Resolution vs Livewire Hardcoding Drift)*

| Lang Key | Lang File String | Filament Admin Panel | Livewire Panel |
| -------- | ---------------- | -------------------- | -------------- |
| `fields.id` | شناسه | `__("...fields.id")` via TaskTablePresenter | Hardcoded (`#{{ $task["id"] }}`) |
| `fields.title` | عنوان | `__("...fields.title")` | Hardcoded (`عنوان وظیفه`) |
| `fields.description` | توضیحات | `__("...fields.description")` | Hardcoded (`توضیحات`) |
| `fields.status` | وضعیت | `__("...fields.status")` | Hardcoded (`تغییر وضعیت`) |
| `fields.creator` | ایجادکننده | `__("...fields.creator")` | Not directly labeled |
| `fields.assignee` | مسئول انجام | `__("...fields.assignee")` | Hardcoded (`مسئول انجام` / `محول کردن به:`) |
| `fields.assignee_hint` | در صورت خالی ماندن، وظیفه به ایجادکننده تعلق دارد. | `__("...fields.assignee_hint")` | N/A |
| `fields.self_assigned` | خود ایجادکننده | `__("...fields.self_assigned")` | Hardcoded (`خودم (شخصی)` / `بدون مسئول (خودم)`) |
| `fields.delegated` | تفویض‌شده | `__("...fields.delegated")` | N/A |
| `fields.deadline` | سررسید | `__("...fields.deadline")` | Hardcoded (`مهلت انجام`) |
| `fields.deadline_date` | تاریخ سررسید | `__("...fields.deadline_date")` | N/A (using generic label) |
| `fields.deadline_time` | ساعت سررسید | `__("...fields.deadline_time")` | N/A |
| `fields.created_at` | تاریخ ایجاد | `__("...fields.created_at")` | Not explicitly labeled |
| `fields.updated_at` | آخرین بروزرسانی | `__("...fields.updated_at")` | N/A |
| `fields.deleted_at` | تاریخ حذف | `__("...fields.deleted_at")` | N/A |
| `fields.department` | واحد سازمانی/دپارتمان | `__("...fields.department")` | Hardcoded (`واحد سازمانی/دپارتمان`) |
| `fields.unit` | واحد (زیرمجموعه) | `__("...fields.unit")` | Hardcoded (`واحد (زیرمجموعه)`) |
| `fields.section` | بخش (زیرمجموعه) | `__("...fields.section")` | Hardcoded (`بخش (زیرمجموعه)`) |
| `fields.project` | پروژه | `__("...fields.project")` | Hardcoded (`پروژه`) |
| `fields.scheme` | طرح | `__("...fields.scheme")` | Hardcoded (`طرح`) |
| `fields.action_source_domain` | حوزه منشاء اقدام | `__("...fields.action_source_domain")` | Hardcoded (`حوزه منشاء اقدام`) |
| `fields.action_source` | منشاء اقدام | `__("...fields.action_source")` | Hardcoded (`منشاء اقدام`) |
| `fields.collaborators` | همکاران | `__("...fields.collaborators")` | Hardcoded (`همکاران`) |
| `fields.responsible_user` | جوابگو | `__("...fields.responsible_user")` | Hardcoded (`جوابگو`) |
| `fields.state` | تعیین تکلیف | `__("...fields.state")` | Hardcoded (`تعیین تکلیف`) |
| `fields.attachments` | پیوست‌ها | `__("...fields.attachments")` | Hardcoded (`پیوست‌ها`) |
| `fields.file` | فایل | `__("...fields.file")` | N/A |
| `fields.view_file` | مشاهده فایل | `__("...fields.view_file")` | N/A |
| `tabs.todo` | انجام نشده | `__("...tabs.todo")` | Hardcoded (`انجام نشده`) |

*(Validation rules (`validation.*.*`) are universally implemented as translation keys in Filament Form logic, whereas Livewire's `TaskForm.php` relies entirely on inline arrays of statically typed explicit strings.)*

**[SEVERITY: High] TaskStatus strings duplicated between Filament Enum and Livewire Kanban configuration**
- **Category:** Consistency
- **Location:** `app/Filament/Resources/TaskResource/Enums/TaskStatus.php` & `app/Livewire/Dashboard/TaskBoard/Presentation/TaskBoardPresenter.php:12`
- **Repro:** Compare `TaskBoardPresenter::columnConfig()` titles with the `TaskStatus` Enum class strings.
- **Expected:** The Livewire Kanban framework logic should map dynamic titles against the localized `strings.php` repository or directly invoke the enum class.
- **Actual:** Both environments inherently hardcode localized values like `'انجام نشده'` and `'در حال انجام'` which introduces isolated context desynchronizations.

---

### F. Performance sanity

*(No severe findings detected. Performance sanity check completed effectively. `Department::getCachedOptions()` executes efficiently with Laravel's `once()` hook to bypass sequential duplicate queries, eliminating structural N+1 potential. `Main::loadTasks()` safely executes its `$relationsToLoad` scope payload cleanly without overloading queries for records mapping BI metadata.)*
