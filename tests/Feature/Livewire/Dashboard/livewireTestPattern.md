# Livewire Feature-Test Pattern — the complete guide for AI testers

This is the canonical guide for writing feature tests under `tests/Feature/Livewire/Dashboard/`. It is referenced from `app/Livewire/livewirePattern.md` — keep this file and that reference in sync when you change the test harness conventions.

**Who this is for:** any AI agent or human writing a new test or debugging a failing one. Read this first, then look at `ChannelTest.php`, `TaskBoardTest.php`, `ThsTest.php`, and `SuggestionTest.php` as reference implementations. Every convention below is verified against all 30 test files in this directory.

**Scope:** the 30 user-panel test files here (`AdsTest`, `ReportsTest`, `HomeTest`, `LinksTest`, `MessagingTest`, `ReservationTest`, `FaqsTest`, `AuthorityTest`, `DmsTest`, `StatusTest`, `CalendarTest`, `GalleryTest`, `ContactTest`, `ReleaseRequestTest`, `TaskBoardTest`, `ChannelTest`, `ProfileTest`, `ThsTest`, `EnergyTest`, `SuggestionTest`, `NavbarTest`, `FeedsTest`, `PostsTest`, `AnalyticsTest`, `TasksheetTest`, `ProjectTest`, `ReminderTest`, `ReminderPresenterTest`, `TabsTest`, `UnreadNotificationsTest`). Four former standalone files were folded into their parent module test (2026-08-24) per the "one file per module" rule: `ChannelMentionsTest` + `_ProbeAbortTest` + `Minor/SendSmsActionTest` → `ChannelTest`; `EventReminderTest` → `CalendarTest` (so `ChannelTest` now also carries the @mentions presenter/Livewire tests, the private-join probe, and the SendSmsAction scope tests; `CalendarTest` carries the EventReminder window/visibility tests). A fifth fold (2026-08-30): the pure `AnalyticsPresenterTest` unit file → `AnalyticsTest`. A sixth fold (2026-08-30): the six per-tab files under `Project/` (`ActivityTest`, `AnalyticsTest`, `CalendarTest`, `KanbanTest`, `ReportTest`, `TeamChatTest`) → the master `ProjectTest.php`, which owns every tab of the Project module in one file. **A module's tab components are part of that module, not separate modules — their tests belong in the module's master file. Never create a per-tab test folder or per-tab test file** (the deleted `Project/` folder was the drift this rule exists to prevent). The admin Filament tests under `tests/Feature/Filament/` are a separate world (they use a local `developer()` helper and Filament page mounts) and are NOT covered by this pattern. The core logic layer — Jobs, Support policies, validation Rules, Services, and Eloquent Models under `tests/Feature/{Jobs,Support,Rules,Services,Models}/` — has its own guide at `tests/Feature/coreTestPattern.md` (one file per target class, same `useMysql()` + transaction skeleton from §1 below).

---

## 1. DB wiring — `useMysql()` + transaction rollback, NOT `RefreshDatabase`

### Why `RefreshDatabase` doesn't work here

The `phpunit.xml` declares `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`. This **cannot run this project's migrations** because:

- Migrations were authored for MySQL: `utf8mb4_bin` collations, `tinyint(1)`, MySQL-specific column modifiers. SQLite rejects them with `no such collation sequence: utf8mb4_bin`.

So `use RefreshDatabase;` fails project-wide with a collation error before any assertion runs. (As of 2026-09-19, `database/migrations/migrated/` IS a registered migration path — `AppServiceProvider::boot()` calls `loadMigrationsFrom()` so a fresh install migrates cleanly — but that only fixes path *discovery*, not the MySQL-only syntax above, so this blocker still stands.) Use the established pattern instead.

### The pattern — copy verbatim

```php
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FooTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string) file_get_contents($env)) as $line) {
                if (preg_match('/^\s*(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)\s*=\s*(.*)$/', $line, $m)) {
                    $vals[$m[1]] = trim(preg_replace('/\s+#.*$/', '', trim($m[2])), "\"' \t");
                }
            }
            $map = ['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username', 'DB_PASSWORD' => 'password'];
            foreach ($map as $envKey => $cfgKey) {
                if (isset($vals[$envKey])) {
                    config(['database.connections.mysql.' . $cfgKey => $vals[$envKey]]);
                }
            }
        }
        DB::purge('mysql');
        config(['database.default' => 'mysql']);
    }
}
```

**What this does:**
1. Reads your `.env` MySQL credentials at runtime (not hardcoded).
2. Configures the `mysql` connection and sets it as the default.
3. Wraps every test in `DB::beginTransaction()` / `DB::rollBack()` — the real dev MySQL DB has the migrated schema, so tests run against it and all rows vanish after each test.

### Rules

- **No `RefreshDatabase` / `DatabaseMigrations` / `DatabaseTruncation` trait.** Ever. The transaction rollback is your isolation. (Verified: none of the 30 files use any of these — the migration-path issue is the permanent reason.)
- **The skeleton is copy-pasted, not shared.** There is no shared test trait or base class — every class extends `Tests\TestCase` directly and carries its own private `useMysql()`. Do not invent a shared base; copy the 30-line skeleton from `ChannelTest` or `TaskBoardTest`.
- **`Queue::fake()` only if the component dispatches jobs** — see §9. `ChannelTest` and `StatusTest` assert on the queue (`ChannelTest` members/nudge, `StatusTest` `LogMissingSkillJob`); `ContactTest`, `CalendarTest` add it defensively. Don't add it reflexively; it can hide real job behavior you meant to assert.
- **`Cache::flush()` / `Cache::forget(...)` only if the component reads a cached source** — see §9. 14 of the 30 files need it.
- **`User` and `Department` are master data — do not mutate them in tests.** Create fresh `User::factory()` rows inside the transaction (they roll back). Never `User::truncate()` / `Department::truncate()` / seed dependent fixtures against existing rows. (Per-file helpers that need a department use `Department::firstOrCreate(['code'=>...])` or `Department::create([...])` inside the transaction — `GalleryTest`, `ThsTest`, `SuggestionTest`.)
- **Auto-increment IDs are NOT reset between tests** — the transaction rollback removes rows but the next test's first `User::factory()->create()` gets `id=2`, not `id=1`. Never hardcode IDs; always capture them from the created model or query by a known attribute.
- **A `#[Lazy]` component needs `Livewire::withoutLazyLoading()` before EVERY fresh mount, not once per test.** The flag it sets is reset by Livewire's own `flush-state` event, which fires between component-mount cycles — so a global call in `setUp()` (the `SuggestionTest`/`StatusTest`/`ChannelTest`/`ContactTest` convention) only reliably protects the FIRST `Livewire::test(Main::class)`/`Livewire::actingAs(...)->test(Main::class)` call in a given test method. A test that makes a SECOND, separate fresh mount later in the same method (e.g. after an earlier helper call already interacted with the component, or after a DB-only step) needs its own explicit `Livewire::withoutLazyLoading();` immediately before that second `test(...)` call, or it silently gets the lazy placeholder — surfacing as either a mismatched `assertSee`/`assertSeeHtml` (seeing skeleton markup instead of real content) or a `PropertyNotFoundException` on a `#[Computed]` property (the placeholder-only mount never ran the real `mount()`/computed graph). See `ChannelTest::test_channel_sidebar_renders_pin_cluster_and_order_binding` for the pattern (two `test(Main::class)` calls, second one preceded by its own `withoutLazyLoading()`). **Attribute mounts still fire on `#[Lazy]` placeholder mounts:** `SupportAttributes::mount` has no `skipMount` check, so `#[Url]` properties ARE filled from the query string even when the placeholder short-circuits `mount()`/`render()` — but `assertSet` on that first harness mount then reads placeholder-era state (any `mount()`-level sanitize or mutation never ran). Two consequences: (a) in Livewire v4 `$disableWhileTesting` defaults to `false` (lazy is ON in tests), so put `Livewire::withoutLazyLoading();` immediately before the `test()` call whenever you assert mount-time query-param behavior; (b) for components reached via a signed/querystring URL, validate `#[Url]` input in `render()` (runs after every fill, on real mounts) rather than `mount()` — see `ProjectTest::test_a_bogus_tab_query_param_is_sanitized_to_activity`.

---

## 2. How to structure a Livewire test

Every test follows the arrange → act → assert shape:

```php
public function test_it_does_something_specific(): void
{
    $user = User::factory()->create(['status' => 'active']);
    $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();

    Livewire::actingAs($user)
        ->test(Main::class)
        ->set('form.newTitle', 'My task')
        ->call('createTask')
        ->assertHasNoErrors();

    $task = Task::where('title', 'My task')->firstOrFail();
    $this->assertSame('todo', $task->status);
}
```

**Key principles:**
- **One logical assertion per test.** `test_done_column_hides_tasks_older_than_45_days` should assert exactly that — not also the toggle, the search bypass, and the pagination. "One logical assertion" can mean several `assertSame` calls that together prove one claim (see §14c).
- **Name tests as full sentences** in snake_case: `test_toggle_show_all_done_reveals_old_done_tasks_and_resets_page`. The name is the spec.
- **Arrange comments are optional** when the setup is obvious; **inline comments on non-obvious assertions** (like "regression guard: '0' is falsy in PHP") are mandatory — `SuggestionTest::test_search_with_single_zero_character_does_not_filter_due_to_falsy_truthiness_check` cites the `Main.php:140 when() guard` it pins.
- **Never assert on something you didn't set up.** If a test creates 3 tasks, don't assert `totalCount.done` is 2 without explaining why the 3rd is excluded.

---

## 3. What to test — the coverage checklist

For every Livewire component (`Main.php`), cover these layers (pick the ones that apply; not every module has pagination, modals, or uploads):

### 3a. CRUD lifecycle (one test, covers the happy path end-to-end)

`TaskBoardTest::test_taskboard_crud_lifecycle` and `ChannelTest` are the references. Create → read → update → delete (+ undo-delete + force-delete if the module has soft-delete) — all in one test. This is the **smoke test**: if this passes, the component is wired correctly.

### 3b. Authorization rules (who can do what) — two conventions, see §10

Test every guard in the component, **both directions** (can AND can't). The "can't" half uses either silent-refuse (assert no mutation) or a 403/401/422 abort (assert status) depending on how the guard is implemented — §10 tells you which.

### 3c. Filtering, scoping, and search

- Tab scoping ("my tasks" vs "assigned tasks", department scoping in `Gallery`/`Authority`/`Dms`).
- Search by each searchable column, and **the falsy-zero regression guard** (`test_search_with_single_zero_character...` — present in Ads, Reports, Faqs, TaskBoard, Authority, Dms, Status, Suggestion, Feeds; `'0'` is falsy in PHP so a truthiness guard and a strict-empty guard diverge).
- `updated_search` resets pagination.
- JSON-column search (`AuthorityTest::test_search_filters_authorities_by_duty_details_json_key`, `whereHas` department-name branch).

### 3d. Pagination and state transitions (where applicable)

- `nextPage` / `prevPage` / `jumpToPage` clamp at boundaries; `jumpToPage` with an unknown column is silently ignored (`TaskBoardTest`).
- `switchTab` resets pages, filters, and selections.
- `loadMore` reveals the next page and hides the button at the end (`Dms`, `Gallery`, `Reports`, `Authority`).
- `mount` with an `open` query param bumps `perPage` to 50 and dispatches `record-focus` (Ads, Reports, Faqs, Authority, Dms, Links, Posts, Feeds, Calendar).

### 3e. Module-specific behavior windows

Only test the window/toggle that your module actually has — these are NOT universal:
- **TaskBoard** — Done column hides tasks older than 45 days; `toggleShowAllDone` reveals them and resets `page.done`; search bypasses the window independently of the toggle; the windowed empty state shows a "show older" nudge.
- **Energy** — survey shows/hides on a 25-day window; `canProceed`/`canSubmit` gating.
- **Contact/Channel** — edit window 600s; delete rejects after the window.
- **ReleaseRequest** — rate limiter blocks the 6th attempt; validation failure does not consume the limiter; identical `title`+`body` from the same user is rejected as a duplicate, but allowed across different users.

### 3f. Edge cases and empty states

- Empty column/list shows the generic empty state.
- Form validation errors surface correctly (required, max-length, enum-valid, invalid Jalali date).
- Falsy-zero search, negative/overshoot page numbers, nonexistent record ids (`selectContact`/`selectChannel`/`focusRecord` with a bad id is a no-op, not a crash).
- Guest path — see §12b.

---

## 4. Assertion patterns used in this project

### Livewire assertions (on the component instance)

```php
->assertSet('totalCount.done', 2)           // public property value
->assertSet('page.done', 1)                  // nested array key
->assertSet('showAllDone', true)             // boolean
->assertHasNoErrors()                        // no validation/form errors
->assertHasErrors(['form.newTitle'])         // specific field has errors
->assertHasFormErrors(['assigned_to' => 'required'])  // Filament-style form error
->assertDispatched('task-created')
->assertDispatched('toast', message: '...', type: 'success')
->assertDispatchedTo('dashboard.energy.chart', 'energy-test-submitted')  // event to a specific component
->assertNotDispatched('statusSwitcher-updated')  // assert a guard refused (no event fired)
```

### Authorization assertions — two flavors (see §10 for when to use which)

```php
// Silent-refuse: guard returned, nothing changed
->call('updateTaskStatus', $task->id, 'done')->assertHasNoErrors();
$this->assertNotSame('done', $task->refresh()->status);
$this->assertDatabaseMissing('channel_members', ['channel_id' => ...]);

// Abort: the action called abort_unless / abort(403)
Livewire::actingAs($nonCeo)->test(Main::class)
    ->call('submitDecision', ...)
    ->assertStatus(403);
// or, for a #[Locked] / abort that throws before a response:
$this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
```

### PHPUnit assertions (on the DB / models)

```php
$this->assertSame('todo', $task->status);
$this->assertNotNull($task->detail);
$this->assertNull(Task::find($task->id));                    // soft-deleted
$this->assertTrue(Task::withTrashed()->find($task->id)->trashed());
$this->assertNull(Task::withTrashed()->find($task->id));    // force-deleted
$this->assertSame($assignee->id, $task->assigned_to);
$this->assertNotSame('done', $task->status);                 // guard refused the change
```

### JSON-column DB queries (nudge/badge, classifier, attachments)

```php
// notification nudge row lookup
DB::table('notifications')->where('data->menu_key', Task::NUDGE_KEY)->value('read_at');
DB::table('notifications')->where('data->item_id', $post->id)->exists();
// classifier / details JSON key
Profile::where('details->duty', 'like', "%$term%")->exists();
```

### Chaining Livewire interactions

```php
Livewire::actingAs($user)
    ->test(Main::class)
    ->set('form.newTitle', 'Task')
    ->call('createTask')
    ->assertHasNoErrors()
    ->set('search', 'query')
    ->assertSet('totalCount.done', 1);
```

Each `->call()` or `->set()` re-renders the component. You can chain multiple interactions in one fluent call.

---

## 5. Factory state helpers — per-file, never shared

The project uses `Database\Factories\*Factory` with custom states. There is **no shared factory-helper trait** — state methods live on each factory, and test-side helpers (`deptUser()`, `actor()`, `makeSuggestion()`, `cleanSlate()`) are private to the test class that needs them. When you add a factory state, follow this pattern:

```php
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'status' => 'todo',
            'user_id' => User::factory(),  // lazy — creates a user only if not overridden
            'assigned_to' => null,
        ];
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn() => ['user_id' => $user->id, 'assigned_to' => null]);
    }

    public function withStatus(string $status): static
    {
        return $this->state(fn() => ['status' => $status]);
    }
}
```

**Rules for factory states:**
- **Use `User::factory()` in `definition()`** (lazy creation), not `User::inRandomOrder()->value('id')` (wasted query). The lazy form only creates a user if no state overrides `user_id`.
- **Name states as verb phrases:** `ownedBy($user)`, `delegatedBy($delegator, $assignee)`, `withStatus('done')`, `pinned()`, `forUser($user)`, `completedDaysAgo(N)`.
- **Chain them fluently:** `Task::factory()->ownedBy($user)->withStatus('done')->updatedAt('-60 days')->create()`.
- **When a state stamps timestamps, be explicit about which columns it touches.** `updatedAt('-60 days')` stamps BOTH `updated_at` AND `created_at` — fine when the filter keys on `updated_at`, but non-done columns order by `created_at`, so a future test asserting creation order would get wrong results. Add a separate `createdAt` state if needed.
- **Test-side private helpers stay private to their class.** `ReservationTest::actor()`, `SuggestionTest::makeSuggestion()`, `FeedsTest::cleanSlate()`, `ThsTest::departmentHead()` — do not extract a shared helper; the next class that needs a variant should define its own.

---

## 6. Testing Livewire events (dispatch, toast, cross-component)

The component dispatches browser events via `$this->dispatch('event-name', ...)`. Test them with:

```php
->assertDispatched('task-created')
->assertDispatched('toast', message: 'وظیفه با موفقیت اضافه شد.', type: 'success')
->assertDispatchedTo('dashboard.energy.chart', 'energy-test-submitted')  // target a sibling component
->assertNotDispatched('statusSwitcher-updated')                          // guard refused -> no event
```

**When to assert events:**
- After create/update/delete operations that dispatch toast notifications.
- After operations that trigger Alpine-side effects (modal close, focus, `open-confirmation`, `record-focus`).
- Cross-component recompute: `EnergyTest` dispatches `energy-test-submitted` to the nested `Chart` and asserts the chart's `latestTest`/`history` refreshed (`assertDispatchedTo`).
- Do NOT assert events for internal state changes that are already covered by `assertSet`.

---

## 7. Testing modal state

Components with modals expose `isModalOpen`, `isEditMode`, `isReadOnly` (and an `open` query param that mounts with the modal pre-opened):

```php
->assertSet('isModalOpen', false)
->call('openCreateModal')
->assertSet('isModalOpen', true)
->assertSet('isEditMode', false)
->call('editTask', $task->id)
->assertSet('isModalOpen', true)
->assertSet('isEditMode', true);
```

---

## 8. Testing file uploads + disk-failure rollback

Components using `WithFileUploads` need `UploadedFile::fake()`:

```php
use Livewire\Livewire;
use Illuminate\Http\UploadedFile;

$file = UploadedFile::fake()->image('photo.jpg');

Livewire::actingAs($user)
    ->test(Main::class)
    ->set('form.attachments', [$file])
    ->call('createTask')
    ->assertHasNoErrors();
```

For full file-lifecycle assertions (stored path exists, old file deleted on reupload, removal), wrap with `Storage::fake('public')` and assert the disk:

```php
Storage::fake('public');
// ... act ...
Storage::disk('public')->assertExists("tasks/attachments/{$token}.jpg");
Storage::disk('public')->assertMissing("tasks/attachments/{$oldToken}.jpg");
```

**Disk-failure rollback test** (`TaskBoardTest` reference): to prove an Action rolls back and cleans up when storage throws, fake a file whose `store`/`storeAs` throws via an anonymous class:

```php
$failing = new class(['photo.jpg', 'image/jpeg']) extends \Illuminate\Http\Testing\File {
    public function store($path = '', $options = []) { throw new \RuntimeException('disk down'); }
    public function storeAs($path, $name = null, $options = []) { throw new \RuntimeException('disk down'); }
};
Livewire::actingAs($user)->test(Main::class)
    ->set('form.attachments', [$failing])
    ->call('createTask');   // assert the half-formed row + any staged file are gone
```

**Mime-guess test** (`ThsTest::test_submit_ticket_stores_mime_guessed_extension_not_raw_client_extension`): upload `UploadedFile::fake()->image('x.gif')->mimeType('image/gif')` and assert the stored extension is guessed from the mime, not the raw client extension (polyglot-RCE guard).

---

## 9. Cache hygiene in setUp — when and how to flush

**Add cache cleanup to `setUp` only when the component (or a collaborator it reads) consults a cache.** A warmed cache from a prior test leaks across the transaction boundary (cache is not transactional), so the next test sees stale data and fails confusingly. 14 of the 30 files need this.

**Targeted `Cache::forget('key')` when you know the exact key:**
- `AuthorityTest` — `Cache::forget('authority-departments'); Cache::forget('authority-global-count');`
- `StatusTest` — `User::resetTodaysDeskCache(); Cache::forget(UserKeyGrouper::CACHE_KEY);` (in setUp **and** tearDown — the desk-extension + user-key-grouper caches drive the nudge/badge board).
- `ReservationTest` — `app(ValidationService::class)->flushPolicyCache();` (no argument — the disabled-types cache is version-keyed inside the service, no direct key flush).
- `CalendarTest` — per-test `Cache::forget('user_active_options');` + `Cache::forget("tab_viewed:calendar:{$user->id}");` (active-options + tab-viewed caches; also reads `menu_state:version`).

**Blanket `Cache::flush()` when the component touches several keys:**
- `HomeTest` (guards the `home_stats` per-user cache key), `PostsTest`, `LinksTest`, `NavbarTest` (weather `Cache::flexible('weather.tehran')` + status-switcher state), `FeedsTest`, `ProjectTest`, `ThsTest`, `TasksheetTest`.

**Rules:**
- Prefer the targeted `Cache::forget('exact-key')` when the key is named in the component — blanket `flush()` nukes other tests' weather/policy caches and can cause cross-file flakiness in the full suite.
- If the component reads through `StateService` (badge/nudge version), a model's `getCachedOptions()`, or a `Cache::remember`/`flexible`, you almost certainly need a flush — grep the component for `Cache::` / `->shouldCache()` / `once(` before deciding.
- A cache-lookup **query-count** assertion (`ReservationTest::test_disabled_types_helper_issues_bounded_cache_lookups_when_warm`) swaps `config(['cache.default'=>'database'])` + `DB::enableQueryLog()` to prove the warm path issues zero DB queries.

---

## 10. Authorization — two coexisting conventions

The suite uses **two different patterns** depending on how the guard is implemented. Match the test to the implementation, do not mix them.

### 10a. Silent-refuse — the dominant pattern for component/UI guards

When the guard is a server-side check that returns early / drops the disallowed id / no-ops (ownership, edit-window, membership, bulk-id filtering), the action **does not throw**. Assert no mutation + no error:

```php
// CAN'T: stranger cannot change status of a task they don't own
Livewire::actingAs($stranger)->test(Main::class)
    ->call('updateTaskStatus', $task->id, 'done')
    ->assertHasNoErrors();
$this->assertNotSame('done', $task->refresh()->status);

// CAN'T: non-sender edit is silently ignored
Livewire::actingAs($stranger)->test(Main::class)
    ->call('saveEdit', $msg->id, 'hacked');
$this->assertNotSame('hacked', $msg->refresh()->body);
```

Used in 10 files (Dms, Calendar, Contact, Channel, TaskBoard, Status, Ths, Suggestion, Feeds, Navbar). Bulk actions silently drop ids the actor doesn't own (`TaskBoardTest::test_bulk_assign_silently_drops_task_ids_the_actor_does_not_own`) — the same `can_*` accessor the single-record path uses, applied inside the Action.

### 10b. Abort (403/401/422) — for policy-backed actions

When the action calls `abort_unless(Policy::can..., 403)` / `abort(401)` / throws a validation 422, assert the HTTP-style status (or the thrown exception):

```php
// Suggestion decision is CEO-only via abort_unless(canDecide, 403)
Livewire::actingAs($nonCeo)->test(Main::class)
    ->call('submitDecision', ...)
    ->assertStatus(403);

// ReleaseRequest mount aborts 401 for guests
$this->get('/release-request')->assertStatus(401);   // or via the component mount

// Ths Workspace reply rejects an unrelated user (403)
Livewire::actingAs($unrelated)->test(Workspace::class, ['ticketId' => $ticket->id])
    ->assertStatus(403);
```

Used in 7 files (ReleaseRequest 401, Profile 403 — skill re-request non-owner, Suggestion 403 non-CEO/wrong stage, TaskBoard 403, Ths 403 — Workspace non-member, Tasksheet 403/404, Reminder 403 cross-user). **Rule:** if the production code `abort()`s, the test asserts the status code; if it returns, the test asserts no mutation. Never convert an `abort` guard into a silent-refuse test or vice versa — you'd be testing a behavior the code doesn't have.

---

## 11. Direct Action / Policy / Presenter tests — not everything goes through Livewire

Many of the 30 files test a unit **directly**, separately from the Livewire component, when the logic is worth isolating. Use `app()->make()` (resolves constructor-injected deps) or `new` for stateless presenters, and mock collaborators with Mockery.

```php
// Action with injected validator
app(CreateTaskAction::class)->execute($form);
app(ForceDeleteMessageAction::class)->execute($msg);

// Pure action with a mocked collaborator
$sms = Mockery::mock(SmsChannel::class);
$sms->shouldNotReceive('text');                 // assert it does NOT send for a no-phone user
$this->expectException(ModelNotFoundException::class);
app(SendSmsAction::class)->execute((string)$inactiveUserId, $sms);

// Static policy unit test
$this->assertTrue(TicketAccessPolicy::canView($ticket, $assignee));
$this->assertFalse(TicketAccessPolicy::canView($ticket, $stranger));
// ^ "stranger"/"requester" fixtures MUST pin role => user — UserFactory randomizes user|admin,
//   and a random admin passes the elevated bypass, flaking the deny-assertion
$this->assertFalse(TicketAccessPolicy::canView($ticket, null));   // null-user guard

// Presenter unit test
$this->assertSame(100, (new ProfilePresenter())->completion($user->fresh()));
$months = (new GalleryPresenter())->months($photos);
```

**When to add a direct test:**
- The Action has rollback/cleanup logic worth proving on its own (`SendChannelMessageAction` storage-rollback, `ForceDelete*Action`).
- A Policy class has its own `can*` methods — test each plus the null-user case (`ThsTest` covers `canView`/`canReply`/`canAssign`/`canSetEffectiveness`/`canClose`).
- A Presenter has pure formatting a Livewire assertion would obscure (`ProfilePresenter::completion`, `GalleryPresenter::months`, `TicketPresenter::formatId`).
- An Action is the only entry point (no Livewire component) — the `SendSmsAction` scope tests (folded into `ChannelTest`) are a pure action test, no component at all.

If a direct test uses Mockery, add `Mockery::close();` to `tearDown` (`ChannelTest` and `ProjectTest` do — they carry the folded `SendSmsAction`/`ApplyTemplateAction` Mockery tests). The remaining 28 Livewire-only files skip this entirely.

---

## 12. Module-specific patterns to know

### 12a. `#[Locked]` tamper test

When a public property holds a record id and is `#[Locked]` (per `livewirePattern.md` Step 5), prove the client cannot swap it:

```php
$this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
Livewire::actingAs($user)->test(Workspace::class, ['ticket' => $ticket->getRouteKey()])
    ->set('ticketId', $otherTicket->id);
```

Reference: `ThsTest::test_workspace_ticket_id_is_locked_against_client_tampering`.

### 12b. Guest-path no-op tests

Components that render for guests but must not write on their behalf are tested **without `actingAs`**:

```php
Livewire::test(Feeds::class)->call('addComment', 'hi')->assertDatabaseMissing('comments', ['body' => 'hi']);
Livewire::test(Posts::class)->call('selectPost', $id);  // selectPost for guest does not mark a notification read
```

Reference: `FeedsTest` (addComment/reaction/vote guest no-ops), `PostsTest` (guest selectPost leaves `seenIds` empty).

### 12c. Nudge / badge / StateService pipeline

When a write dispatches `ReconcileNudge` (via `NudgeService::reconcile('key', Model::class, $id)->afterCommit()`), assert the queued job and the resulting `notifications` row:

```php
// ChannelTest asserts the job is pushed (requires Queue::fake in setUp)
Queue::assertPushed(ReconcileNudge::class);
Queue::assertPushed(ReconcileNudge::class, 1);   // one job per request (markRead)

// TaskBoardTest / ThsTest / PostsTest query the notification row directly
DB::table('notifications')->where('data->menu_key', Task::NUDGE_KEY)->exists();
DB::table('notifications')->where('data->item_id', $post->id)->value('read_at');
```

The "author is not self-notified" and "third-party head notifies both" branches are their own tests (`ThsTest`, `TaskBoardTest`) — do not fold them into the happy-path nudge test.

### 12d. Jalali date-part submit → Gregorian storage

`Calendar` and `Profile` expose Jalali date parts as separate select fields (`form.dateYear/dateMonth/dateDay`, `form.birthYear/Month/Day`). Test that submit assembles them into a stored Gregorian `Y-m-d`, and that hydrate (`mount`/`openCreateModal`/`editEvent`) populates the parts non-padded to match the select option values:

```php
->set('form.dateYear', 1403)->set('form.dateMonth', 1)->set('form.dateDay', 5)
->call('saveEvent')
->assertHasNoErrors();
$this->assertSame('2024-03-24', $event->fresh()->date->format('Y-m-d'));   // Jalali 1403/1/5 → Gregorian
```

Reference: `CalendarTest` lifecycle/open-create-modal/edit-populate/out-of-range-day, `ProfileTest::test_info_save_persists_required_fields_and_assembles_jalali_birthdate` (uses `(new Jalalian(1400,1,1))->toCarbon()`). Invalid Jalali dates are their own validation test (`TaskBoardTest::test_invalid_jalali_deadline_surfaces_form_deadline_error`).

**Never get a Carbon by `Carbon::parse`-ing a Jalali `Y-m-d` string.** Morilog has no hook into `Carbon::parse`, so `Carbon::parse('1405-06-09')` silently builds a GREGORIAN year-1405 date; the failure surfaces much later as morilog's `InvalidArgumentException: Provided "784" is neither greater than or equal to "1000"…` (Jalali mirror-year assert) when the mis-dated row is read back with `Jalalian::fromCarbon`. The correct round-trip is `Jalalian::fromFormat('Y-m-d', $jalaliYmd)->toCarbon()` (Jalali→Gregorian) or `Jalalian::now()->addMonths(1)->toCarbon()` directly — `CalendarTest`'s deadline-factory lines are the reference.

### 12e. FULLTEXT search — `markTestSkipped`

`Contact` and `Channel` have a `search_messages` FULLTEXT query that does not commit inside the test transaction (InnoDB FULLTEXT indexing is deferred). These tests call `$this->markTestSkipped(...)` rather than asserting — they exist as placeholders and show up in the suite as `Skipped`, not failures. Do not "fix" them by asserting against uncommitted FULLTEXT rows.

### 12f. Computed recompute after a round-trip

When a `#[Computed]` property depends on a relationship that a `->call()` mutates, prove the computed re-evaluates on the next render (Livewire unsets the memo between calls): `TaskBoardTest::test_editing_task_computed_returns_null_after_user_is_reassigned_away`. Energy's nested `Chart` recomputes on a dispatched event (`assertDispatchedTo`).

### 12g. The folded `joinChannel` private-channel test — real assertion, not a probe

The former `_ProbeAbortTest.php` was a debug probe (`echo` + `$this->assertTrue(true)`) investigating whether `joinChannel` aborts for a non-owner on a private channel. It is now folded into `ChannelTest::test_probe_join_private_aborts` as a **real** assertion: it calls `joinChannel` and asserts **no `ChannelMember` row is created** (the actual security invariant — the original probe established the call does not throw, so no try/catch is needed). Do not copy the old `echo`/`assertTrue(true)` probe shape; assert the real invariant instead.

---

## 13. Common pitfalls and how to avoid them

| Pitfall | Symptom | Fix |
|---|---|---|
| Using `RefreshDatabase` | `no such table: users` | Use `useMysql()` + `DB::beginTransaction()` |
| Forgetting `Cache::flush`/`forget` for a cached component | Test passes alone, fails in the full suite (stale warmed cache) | Add the targeted `Cache::forget('key')` (or `Cache::flush()` if multiple keys) in setUp — see §9 |
| Hardcoding IDs | Test passes in isolation, fails when run with others | Always capture IDs from `->id` or query by attribute |
| Forgetting `actingAs` | `Target class [auth] does not exist` or 401 | `Livewire::actingAs($user)->test(...)` |
| Inactive actor, no `status => 'active'` | Empty dropdowns / option lists, confusing failure | Always set `status => 'active'`; add `presence`/`type`/Profile only if the component gates on them (§14b) |
| Testing with stale data after a Livewire call | Assertion fails because `$task` wasn't refreshed | Call `$task->refresh()` after the Livewire interaction |
| Asserting silent-refuse on an `abort` guard (or vice versa) | Test fails or tests the wrong behavior | Match the test to the guard — §10 |
| Asserting `page.done` reset without proving it was on page 2 first | Test passes even if the reset code is deleted | Create enough rows for page 2, navigate there, then trigger the reset |
| Factory `definition()` runs a DB query even when overridden | Slow tests, unnecessary DB round-trips | Use `User::factory()` (lazy) instead of `User::inRandomOrder()->value('id')` |
| Testing with `Queue::fake()` when no jobs are dispatched | Test passes but hides real job behavior | Only add `Queue::fake()` when the component actually dispatches jobs; assert with `Queue::assertPushed` if it does |
| Asserting on FULLTEXT search inside the transaction | Flaky / always-empty results | `markTestSkipped` — see §12e |
| `assertDontSee`/`assertSee` colliding with an always-DOM legend/example | `assertDontSee(convertToPersian('09:00').'–')` fails even though the real pill hides the end-time — because the module's `legend.blade.php` (rendered in-DOM via `x-show`, NOT removed by `@if`) contains the example string `مثلاً ۰۹:۰۰–۱۰:۳۰` | Assertions run against the **whole** rendered HTML, including `x-show`-hidden legends/modals (CSS-hidden, still in DOM) and any quoted/example text inside them. Pick a fixture value that does NOT appear in any always-DOM legend/example (e.g. use `13:00` instead of `09:00` for a Calendar "hides end-time" test), or assert on a per-row DOM fragment instead of the full response. Proven pre-existing by reverting the blade to original inline logic — if the test still fails, the legend is the culprit, not your refactor. |
| Calling a `mountFocusOnRecord()`-invoked method directly to simulate a `?open={id}` deep-link | `assertSee`/`assertSet('open', ...)` fails even though the real URL-arrival path works fine | A module's `focusRecord(int $id)` (or equivalent) is called BY the `FocusOnRecord` trait's `mountFocusOnRecord()` AFTER `#[Url] $open` is already hydrated from the query string — the method itself typically only reacts to the id (switch tab, widen a limit, etc.) and does **not** assign `$this->open`. Calling `->call('focusRecord', $id)` directly in a test never sets `open` at all. To simulate a genuine deep-link arrival, set the property directly — `->set('open', $resource->id)` — not the method that only runs downstream of it (`ReservationTest::test_deck_focus_suppresses_search_chip_but_focus_record_shows_it`, caught by an actual failed run, not inferred). That shortcut only reaches `focusDeck`; to exercise the real `mount()` path with a deep-link (e.g. a rejected `?open=` id), use `Livewire::withQueryParams(['tab' => 'meeting', 'open' => 999999999])->actingAs($user)->test(Main::class)` (**auth() trap:** a test that reads presenter/computed state off `->instance()` OUTSIDE a request sees the LAST authenticated user — with more than one user, call `$this->actingAs($user)` right before each read; a two-user summary test once failed exactly this way) (`ReservationTest::test_mount_with_a_rejected_open_id_lists_resources_for_the_fresh_default_times`). |

---

## 14. Common patterns shared across all existing tests

The conventions every `tests/Feature/Livewire/Dashboard/*Test.php` follows. Most restate rules already captured in §1–§9; the unique gotchas not covered elsewhere are listed here, the rest are cross-references.

- **14a Skeleton:** copy-paste the §1 `setUp`/`tearDown` + `useMysql()` skeleton verbatim. The only lines that vary class-to-class are the cache/queue lines in §9 — 16 "pure skeleton" files have neither, 14 have one or both (the former `AnalyticsPresenterTest` pure-unit file was folded into `AnalyticsTest`, 2026-08-30).
- **14b Actor creation:** always `User::factory()->create(['status' => 'active'])` (the default factory state may leave `status` null/inactive; several components' `#[Computed]` option lists filter to active users — an inactive actor makes the test silently see empty dropdowns). Add extras only when the component gates on them: `presence` (channel membership, status board, status switcher — `ContactTest`/`SuggestionTest` omit it), `type` (`StatusTest` board excludes guests, the folded `SendSmsAction` tests (in `ChannelTest`) reject guests), Profile `department_id`+`position` (dept-scoped/dept-head authority: Gallery, Authority, Dms, Ths, Suggestion, Reservation — use the file's private `deptUser($dept, $position)` / `dept()` helper), `role`/`booking` (Reservation only, via its private `actor()` helper).
- **14c Lifecycle + focused tests:** one `test_<thing>_crud_lifecycle` smoke test (§3a) + one named behavior test per logical assertion (§2). A single logical claim may need several `assertSame` calls to prove it robustly (e.g. "search bypasses the window" asserts the count, the search count, and that `showAllDone` stayed false) — that's one test, not three.
- **14d Authorization:** §10 — both directions, in the convention the guard actually uses.
- **14e Re-fetch, don't trust the local handle:** after any mutating `->call()`, the in-memory model is stale — call `$task->refresh()` before asserting (§13 pitfall). For soft-delete tests, re-query instead of refresh: `Task::find($id)` returns null for a soft-deleted row; use `Task::withTrashed()->find($id)->trashed()` to prove a soft-delete, `Task::withTrashed()->find($id)` null to prove a force-delete.
- **14f Bulk operations:** set the selection array, call the bulk action, assert on every affected row. Add a "silently drops ids the actor doesn't own" test (§10a) — bulk guards reuse the same `can_*` accessor as the single-record path.
- **14g Prove preconditions before proving the claim:** if a test claims "X resets the page," first assert the page was elsewhere (`->call('nextPage','done')->assertSet('page.done', 2)`) — otherwise the test passes even if the reset code is deleted (§13 pitfall).
- **14h Mirror the component's own API:** if the component calls `toggleShowAllDone` and exposes `totalCount.done`, the test calls/asserts those exact names — not a paraphrased `showAll`/`doneCount`. Tests that mirror the real API stay correct when the component changes; paraphrased ones drift.
- **14i Don't test framework behavior:** test that *your* public properties hold the values *your* method computed (`assertSet('totalCount.done', 2)`), not that Livewire re-rendered after a `->call()` — that's the framework's contract.

---

## 15. How to run tests

```bash
# Run a single test class
php artisan test --filter="TaskBoardTest"

# Run a single test method
php artisan test --filter="test_done_column_hides_tasks_older_than_45_days"

# Run all user-panel Livewire dashboard tests
php artisan test tests/Feature/Livewire/Dashboard

# Run with verbose output
php artisan test --filter="TaskBoardTest" -v
```

**Run the full `Dashboard` directory for current counts** (`php artisan test tests/Feature/Livewire/Dashboard`) — the only consistently skipped tests are the §12e FULLTEXT placeholders (Contact/Channel) plus one `ProjectTest` skip (dev DB lacks the `workflows.project_id` ON DELETE CASCADE foreign key, `ProjectTest.php` markTestSkipped). Run your single class while iterating, then the whole `Dashboard` directory before finishing to catch cross-file cache/queue leaks.

Note: the broader `php artisan test` (all suites) may show failures in **admin Filament** tests (e.g. `ThsResourceTest`) — those are a separate world from this pattern and not in scope here.

---

## 16. Reference tests — all 30 user-panel test files

| Test file | What it covers |
|---|---|
| `AdsTest.php` | `Ads\Main` listing/filter/search, falsy-zero search, search stays inside the active/archived filter (grouped `orWhere`), focus-mode bypasses active filter to pin archived, mount `open` query param |
| `ReportsTest.php` | `Tab\Reports` render, view-toggle session persistence, thumbnail URL, search/dept filter, `loadMore`, download inactive/restricted report dispatches an error toast (silent-refuse flavor) |
| `HomeTest.php` | **team-pulse quiet chip (`home-team-pulse-quiet`) shows only when nobody else is online (2026-11-02)**, `Tab\Home` render, stat scoping (tasks/tickets/events), 1-week event window, `EventShare` inclusion, per-user `home_stats` cache: stale serve until a stamp-source write flips the key (no inline flush; `Task::resetStatusCountsCache()` right after the factory create so Task's static count cache can't mask a broken `home_stats` cache), + `TabPresenter::statStamp($userId)` tripwires (idle-stable; flips on ChannelMessage/Reminder/Event writes, `StateService::flush()`, the per-reader `channel-read:{userId}` bump; inert on Comment/Reaction/User writes — afterCommit flushes never fire in the rolled-back harness) (`Cache::flush` in setUp) |
| `LinksTest.php` | `Tab\Links` external/internal sources ordered by sequence, `totalLinks`, focus mode, mount `open` query param (`Cache::flush`) |
| `MessagingTest.php` | `Messaging\SwitchTabs` unread counts (contacts/channels) + the `messaging-poll` event recompute (view has no `wire:poll`), `last_read_message_id` boundary, soft-delete (message and whole channel)/sender/non-member exclusion, deactivated/guest-sender exclusion (`Message::totalUnreadFor` visibility scoping) |
| `ReservationTest.php` | `Reservation\Main` book/cancel lifecycle, policy-driven disabled types + validation (past/window/weekday/duration/full-day/allowed-hours), recurring series, rate limiter, Jalali month cursor, bounded-lookups query-count test, `focusDeck`/`$deckOnly`/`clearFocus` override + deck-vs-search-deep-link focus-chip suppression contrast (2026-09-15); `focusRecord` locked-type/unpermitted-type no-op guard, stale `open` auto-clears when the resource drops out of a re-filtered list, avantgarde booking-panel renders the recurring toggle (2026-09-15), both views' Book button opens the confirm dialog (card text from `ReservationSummaryPresenter::summary`, read in tests through a private `summaryOf($instance, $resource)` helper) instead of `wire:click="book"` while a direct `book()` call still books (2026-10-28); ~179 tests, 2026-10-28 additions: the confirm dialog HTML + presenter lines (hours, role-aware `recurPreview` and «تا N بار» / «فقط همین یک‌بار», per-resource `available_days`, the monthly-quota cap incl. last-minute-relaxed occurrences, the meeting invite line, no repeat line when the policy forbids it, a tampered `recurPattern` reset), the danger tone and accessibility pins (in `HelpersTest`: tone arg order + rendered `role`/`aria-*`/`x-trap="active"`/danger tokens), keep-hours clear (`resetFilters(true)`) vs re-seat, auto-roll to the next bookable day on mount / tab switch / `activeTab` write / deep link / `focusDeck`, invalid `?open=` clearing, default end vs `min_duration_minutes` (and the start+30 fallback), `setStartTime` auto-moving the end / the no-valid-end warning, `setEndTime` refusal incl. the stale-start exception, `lastValidStartSlot` + the rendered start/end guards and per-render-date args, `bookingBlockReason` to the second, the `goToday` toast, unavailable-deck toast vs silence after book / tab switch, notice-window slot states on future dates (with the baked server wall), `mount()` rejected-open unset, time-rail key following busy segments, dossier branch key, month arrows disabling on the first response |
| `FaqsTest.php` | `Tab\Faqs` render/filter, `totalFaqs`, perPage 10→20→30, `filterByCategory/Department` resets page, falsy-zero search, focus mode |
| `AuthorityTest.php` | `Authority\Main` dept-scoped authorities, JSON `details->duty` search, `whereHas` dept-name branch, falsy-zero search, `load_more`, totalCount global (`Cache::forget` authority keys) |
| `DmsTest.php` | `Dms\Main` CRUD (`confirmRead`/`incrementRead`), type/tag filter, visibility tiers, silent-refuse on invisible doc, `combined_read_count` reset on revision, filter-button `$wire.` scope binding |
| `StatusTest.php` | `Tab\Status` sendSms, today's-desk extension, guest-type board exclusion, classifier write preserves preferences, hidden `_`-prefixed keys, tampered hidden value no-op, rank + last_seen ordering (`User::resetTodaysDeskCache` + `UserKeyGrouper::CACHE_KEY` flush); **folds the `StatusPresenter` data-bag tests into this one file** — org-tree shaping (level/subordinate_to placement, chairman/CEO apex hoist, unit/section clusters, zero-extra-query guard), `chartData` stats + level2-interleave best-mid, `nodeData`/`gridData`/`aboutPayload` (incl. node≡grid unify), `orgViewData` Main-computed delegation, occasion `tone`. These presenter tests are DB-backed and share StatusTest's `useMysql()` skeleton + `makeDept`/`makeUser`/`orgTree` helpers, so they live here per the one-file-folds-all-functionalities rule (§1/§11) |
| `CalendarTest.php` | **gotcha (2026-11-02, `EventReminder` became `#[Defer]`): a test that calls `Livewire::test()` twice must call `Livewire::withoutLazyLoading()` again before the second mount — the setUp call only covers the first**, `Tab\Calendar` event lifecycle + share diff-sync, `Profile::cachedWithDates()` roster cache (warm read skips the profiles table; follows a profile edit and a user rename; `setUp` bumps the `Profile` version so rolled-back rows never leak between tests), Jalali date-part assemble/hydrate, `HolidayService` static, month wrap, `hasShared`/`hasImminentShared` flags, silent-refuse non-owner (`Queue::fake` defensive) **+ folded `EventReminder` window/visibility tests (2026-08-24)** + `EventReminder` bridge tests (2026-11-02: a due reminder arms the hidden `window.__eventReminder` JS bridge — asserted via the truthy-branch-only `eventAtIso` key plus absence of the null-branch `event-reminder-none` key, never via the always-rendered `event-reminder-armed` dispatch string; placeholder stays a bare `<div class="hidden">`; stays error-free on the `event-reminder-changed` pulse listener) + long-hold reservation rendering (month-grid boundary-only dot + day-count badge, week-view no-spanning-bar + start/end marker, `selectedDayEvents()` `long_hold_days`/`long_hold_end_date` on start day only, 2026-09-05) |
| `GalleryTest.php` | `Tab\Gallery` render/`loadMore`, `totalPhotos` cache is per-department and rotates on a Photo write, dept-scoped photos, public/hidden, month filter, `GalleryPresenter::months` unit, scope meta icons |
| `ContactTest.php` | **two-way anchor paging (2026-11-02): `focusNewer`/`hasNewer`, `loadNewerMessages` paging to the tail + exit to recent-N + markRead, recent-mode no-op, every reset site, bottom `load-newer` block**, `Contact\Main` messenger CRUD + undo/force-delete, edit window 600s, attachment path scoping, `downloadAttachment` rejects non-participant, direct `MarkMessagesAsRead`/`ForceDelete`/`SearchMessagesAction` tests, `markRead` dispatches `ReconcileNudge('contacts-controller:nudge', ...)` (`Queue::assertPushed`), FULLTEXT skip (`Queue::fake`, no longer defensive-only) |
| `ReleaseRequestTest.php` | `ReleaseRequest\Main` submit with server-forced user/status, validation, rate limiter, duplicate title+body rejected per-user (allowed across users), HTML strip, enum validation, history own-only, mount 401 for guest |
| `TaskBoardTest.php` | `TaskBoard\Main` CRUD + bulk, 45-day done window + toggle + search bypass, **version-cached `archivedCount` badge stays fresh across outside writes and own archive/unarchive (2026-11-02)**, `jumpToPage` clamp, `switchTab`/`updated_search` resets, ticket-linked task read-only, `addReply` rollback + disk-failure cleanup, `NudgeService::reconcile`, direct `Create/Update/ForceDeleteTaskAction` tests **+ `TaskBoardPresenter::priorityChip` unit (null/unknown → `null`, every case → label/class/`isUrgent`) (2026-08-25)** **+ the `userPickerResults()` people-picker computed (2026-10-05): popover availability with the modal closed (12-limit still holds), `loadMoreUsers` paging + `hasMore` flip + auth-user exclusion, name and department-name search with garbage→empty, dept-code filter, selected-id pinning above a non-matching search incl. the string-collaborator `intval` cast — all against whole-org visible users, so each test narrows the world with a `Pk…`-prefixed fixture name/dept-code rather than asserting absolute org counts** **+ ticket-linked done-task archive trio (2026-10-28): requester archives (pre-existing), assignee sees the `archiveTask` rail button in HTML and archives, stranger is refused with `archived_at` unchanged — the presenter's ticket-archive gate mirroring `TaskAccessPolicy::canArchive`** |
| `ChannelTest.php` | **two-way anchor paging parity (2026-11-02): `loadNewer` paging/exit/markRead, resets, `load-newer` block**, `Channel\Main` CRUD + member management, edit window, non-member/non-owner/non-sender silent-refuse, owner cannot leave, `markRead`/`saveManageMembers`/`leaveChannel` dispatch `ReconcileNudge` (`Queue::assertPushed`), direct `SendChannelMessageAction`/`ForceDeleteChannelMessageAction` tests, FULLTEXT skip **+ folded `@mention` presenter/tests, private-join probe, `SendSmsAction` scope tests (2026-08-24)** **+ direct `MarkChannelReadAction` home-stat-stamp probe (2026-10-28): `execute` flips `TabPresenter::statStamp($reader)` and leaves another user's stamp byte-identical — the per-reader `channel-read:{userId}` bump, tested at the action level so the bump line itself is exercised** |
| `ProfileTest.php` | `Profile\About`/`Details`/`Info`/`Documents`/`Onboarding` save, `Credentials` search never leaks another user's rows (grouped `orWhere` under the `user_id` scope), Jalali birthdate assemble, `Storage::fake` lifecycle (upload/replace/remove), `ProfileDetailCatalog` admin-only keys, `ProfilePresenter::completion` unit |
| `ThsTest.php` | `Ths\Main` ticket CRUD + `Workspace` reply, `TicketAccessPolicy` unit (canView/canReply/canAssign/canSetEffectiveness/canClose + null user), `#[Locked]` ticketId, `Workspace` reply 403 for an unrelated user, close composes action_result, mime-guessed extension, nudge (author not self-notified) |
| `EnergyTest.php` | `Energy\Main` submit, 25-day survey window, `canProceed`/`canSubmit`, none-of-the-above exclusion, `energy-test-submitted` → nested `Chart` recompute (`assertDispatchedTo`) |
| `SuggestionTest.php` | **leaderboard (`topContributors`, versioned cache) refreshes as soon as a suggestion is saved (2026-11-02)**, `Suggestion\Main`+`Detail` CRUD (submit/feedback/decision/markComplete), validation, MA-injection blocked, unknown dept code rejected, serial-number search, falsy-zero search (cites `Main.php` guard), 403 aborts for non-CEO / wrong stage, silent-refuse non-referred dept, orphaned-dept `myReview` |
| `NavbarTest.php` | `StatusSwitcher` (changeStatus/enum cases/invalid reject/event listener/mount), `QuickSettings` focus mode, `CommandPalette` (toggleMode/results/selectResult edge cases), `Weather` + direct `FetchWeatherAction` (`Cache::flexible`), hamburger x-data HTML regex (`Cache::flush`) |
| `FeedsTest.php` | `Feeds` render/pagination, comments add/reply/delete/reparent-to-grandparent/edit-own/reject-other, reactions toggle, polls single/multiple mode, guest no-ops, falsy-zero search, `openComments` flag, `cleanSlate()` isolation |
| `PostsTest.php` | `Posts` render auth + guest, pinned computed (capped at 1), ordering, pagination, `selectPost` marks notification read for auth only, `seenIds`, `insertPostNudge` JSON query (`Cache::flush`) |
| `AnalyticsTest.php` | `Analytics\Main` lazy placeholder markup for authed, guest redirect, `chartData` computed returns all 17 module keys shaped correctly, full render shows category + module labels once lazy-loaded **+ folds the pure `AnalyticsPresenter` unit tests (2026-08-30): `chartType` (doughnut only `hr_d`, bar otherwise), `description` non-empty/distinct/fallback, demographics/qualification/unit-health/engagement options stacking, `chartConfig` shapes all 17 keys** |
| `TasksheetTest.php` | `Tasksheet\Main` self vs signed-share readOnly modes, guarded mutating methods inert for recipient, raw-set 403 on guarded props (`updating()` hook) + `CannotUpdateLockedPropertyException` on `#[Locked]` ones, tampered signed URL 403, admin/developer/manager subject entry, inactive subject 404, scope-to-project, custom-range validation + >1yr cap resync, `viewingBaseline` time-machine toggle (shift/restore equal-length window, inert in readOnly, 403 raw-set, reset on preset/custom change), share + activity paging (`Cache::flush`, `Auth::logout` in tearDown) |
| `ProjectTest.php` | `Project\Main` sidebar list/search/paging/percent, pending invites, tabs + dirty flags, heartbeat, renderless `warm`, `#[Url]` bogus-tab sanitize, project CRUD, stale-save guard, stale cache version; **folds all six former per-tab files (2026-08-30):** Activity feed + replies (`logDoneReply` helper), TeamChat messages/attachments/mentions/Locked props, Calendar month grid + gantt buckets/resize/toggle view (`ganttFor` helper), Kanban columns/filters/my-tasks toggle/approve/reorder/state guards/placeholder parity, Report toolbar/sort/expand, Analytics presenter unit tests — `Cache::flush` + `withoutLazyLoading` in setUp |
| `ReminderTest.php` | `Reminder\Main` open/scope deep-links (unscoped widget vs task-scoped prefilled form), scoped/unscoped mount listing, pagination (10) + `setFilter` reset, create/update with morph fields (null vs task), badge-channel gating on the row's own scope, recurring successor spawn + stop-recurrence, complete/snooze (presets + custom hours clamp 1–12), snoozed/filter navigation, due-count respects the badge toggle, the closed widget renders no modal body until `open`, the shared open-reminders list is refreshed by snooze/complete/delete while snooze expiry applies on read, host URL resolution, cross-user actions abort 403 |
| `ReminderPresenterTest.php` | `ReminderPresenter` pure unit (**`channelBadges` hides email/SMS when `notify:mail`/`notify:sms` is off, 2026-11-02**; overdue label whole-day math + today's time branch) — no `useMysql()` skeleton; the one sanctioned separate-presenter file (historical exception, see §17.1) |
| `TabsTest.php` | `Dashboard\Tabs` embed query param (hides chrome, survives re-render), default chrome view, off-tab absent + query-tab fallback to home, bottom-nav tab pagination |
| `UnreadNotificationsTest.php` | unread-notifications source query: panel-dependent scope (unread-only on user panel, read rows included on admin); the user-panel bell has no poll of its own (`getPollingInterval()` null, admin keeps one) |
| `PulseTest.php` | `Dashboard\Pulse` mounts the Alpine leader hook with the starting fingerprint and no `wire:poll`; `pulse($known)` returns no events when quiet, only the changed part's event then settles, a tampered `$known` never errors, a guest gets nothing (the fingerprint/diff cases live in `Services/MenuServiceTest.php`, the browser election in `data/__tests__/pulse.test.js`) |

When in doubt about how to test a specific pattern, `grep -rln "useMysql" tests/` and copy the closest sibling in the table above.

---

## 17. The AI tester's checklist

Before writing a test, ask yourself:

1. **Which existing master file owns this module?** One flat master file per module under this directory — a module's tab/sub components (e.g. `Project\Kanban`, `Project\Calendar`) are part of that module, so their tests fold into the module's existing master file (e.g. `ProjectTest.php`). **Never create a new test file or subfolder for a tab, action, or presenter of a module that already has a master file** — the only sanctioned reasons to add a file here are (a) a brand-new user-panel module with no master yet, or (b) one of the historical peer-family folds documented in §1. If a `useMysql()` skeleton must be copied, that is the smell that says you are creating a file you should be folding into. (Historical exception: `ReminderPresenterTest.php`, a pure presenter unit predating the current enforcement of this rule.)
2. **Does this test class have `setUp()` + `useMysql()` + `DB::beginTransaction()`?** (Yes — good. No — copy the §1 skeleton.)
3. **Does the component read a cache?** (If yes, add the targeted `Cache::forget('key')` or `Cache::flush()` in setUp — §9.)
4. **Does the component dispatch jobs?** (If yes, add `Queue::fake()` and assert with `Queue::assertPushed` — §9. If no, omit it.)
5. **Does the test name describe exactly one behavior?** (If it says "and" in the name, split it.)
6. **Have I covered the authorization guards in the right convention?** (§10 — silent-refuse for return-guards, abort-status for `abort` guards; both can AND can't.)
7. **Have I covered the empty states and edge cases?** (Falsy-zero search, bad ids, boundary dates, guest path if the component renders for guests.)
8. **Have I covered the state transitions?** (Tab switch resets pagination; filter resets page; `updated_search` resets pages.)
9. **Does the test prove what it claims?** (If it tests "page reset," make sure it was on a different page first — §14g.)
10. **Are the factory states / private helpers clean and per-file?** (No wasted queries, no hardcoded IDs, no shared helper trait, timestamps explicit about which columns they touch.)
11. **Is the test isolated?** (No dependency on other tests' data, no shared state, no hardcoded IDs, cache flushed if needed.)
12. **Does the test run green in isolation AND in the full `Dashboard` directory?** (`php artisan test --filter="YourTest"` then `php artisan test tests/Feature/Livewire/Dashboard` — the latter catches cross-file cache/queue leaks.)
13. **Should any logic be tested directly instead of through Livewire?** (Policy `can*`, Presenter formatting, Action rollback — §11.)
## Lessons of 2026-10-19 (reservation module)

- One `Livewire::test(Main::class)` per test: a second component in the same test throws "validationService before initialization" — split the test instead.
- Dev-DB tests run in rolled-back transactions; tag rows (`zz`+uniqid) and filter by the tag so real data never changes counts; factories randomise images/types, so pass `type`/`image`/`metadata` explicitly.
- Month rules: use `travelTo()` with dates on both sides of a JALALI boundary; `jalaliMonthBounds()` drives the expectations (e.g. 10 Oct 2026 → reset in 13 days, 1 Aban = 23 Oct).
- Policy writes via raw SQL don't bump the model cache version; tests use `setPolicy()`/`clearPolicies()` helpers.
- **Relative dates must be pinned (2026-10-22):** any test that books "tomorrow" (`now()->addDay()`) and then reads a monthly quota, a calendar cell or a month-scoped count fails ONLY on the last day of a Jalali month (tomorrow lands in the next month). Call `ReservationTest::pinToMidJalaliMonth()` (sets `Carbon::setTestNow` to the 10th of the current Jalali month; `tearDown` resets it) at the top of such tests instead of relying on today's date — the four flaky quota/calendar-cell tests were fixed this way.
