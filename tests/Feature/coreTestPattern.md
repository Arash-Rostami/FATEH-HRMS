# Core (Jobs / Support / Rules / Services / Models) Feature-Test Pattern

This is the canonical guide for the unit/feature tests that sit **outside** the Livewire dashboard and the admin Filament panel — the project's "core logic" layer under `tests/Feature/{Jobs,Support,Rules,Services,Models}/`. It complements `tests/Feature/Livewire/Dashboard/livewireTestPattern.md` (user-panel components) and `app/Filament/filamentPattern.md` (admin resources); it does not duplicate them. When a convention here overlaps with that guide, the Livewire guide's §1 (DB wiring) and §11 (direct tests) win and are referenced rather than copied.

**Who this is for:** any AI agent or human writing a test for a Job, a Support policy, a validation Rule, a Service, or an Eloquent Model. Read this first, then look at `Services/NudgeServiceTest.php`, `Services/ValidationServiceTest.php`, `Services/ExceptionPresenterTest.php` (the flat multi-panel service — also pins the `bootstrap/app.php` renderable lanes: debug-off non-Livewire 500 view data, 404 passthrough; sets `setLocale('fa')` in setUp because `.env.testing` pins `en`), `Models/UserModelTest.php`, `Models/TicketModelTest.php`, `Rules/SuperAdminExclusionTest.php`, and `Support/SuggestionAccessPolicyTest.php` as reference implementations.

---

## 1. Scope and the "one file per target" rule

Each test file owns **one target class** and folds **all** of that target's relevant custom functionalities into that single file — the same shape the Livewire `ChannelTest`/`TaskBoardTest` and Filament `<X>ResourceTest` files use for their modules. Do not split one target's behavior across multiple files; do not write one giant file for a whole category.

| Directory | Target | File name | Reference |
|---|---|---|---|
| `tests/Feature/Jobs/` | a queued Job | `<Job>Test.php` | `ReconcileNudgeTest.php`, `ReconcileReminderDueTest.php` (the delayed due-time reminder reconcile) |
| `tests/Feature/Support/` | a Support policy / helper | `<Policy>Test.php` | `TicketAccessPolicyTest.php`, `SuggestionAccessPolicyTest.php`, `FilamentPanelCustomizerTest.php` (pure, no DB — private statics via reflection + an in-memory `User` for the auth branch) |

1a-b. **Pin `role => 'user'` on every non-elevated factory user a policy depends on** (2026-08-30): `UserFactory` assigns `role` RANDOMLY (`fake()->randomElement(['user','admin'])`). Before the `TicketAccessPolicy` elevated-bypass this was load-bearing nowhere, so old fixtures were written without a role. Any policy/authorization code that branches on `hasElevatedRole()` turns that randomness into a 50% test flake — a "stranger"/"requester" fixture can be a random admin and wrongly pass a deny-assertion. Pin `'role' => 'user'` explicitly on every non-elevated test user (see `TicketAccessPolicyTest`, `ThsResourceTest`, `ThsTest` after the 2026-08-30 fix); only explicitly-elevated fixtures set `role => 'admin'/'developer'`. (See filamentPattern.md Rule 56.)
| `tests/Feature/Rules/` | a validation Rule | `<Rule>Test.php` | `SuperAdminExclusionTest.php` |
| `tests/Feature/Services/` | a Service class | `<Service>Test.php` | `NudgeServiceTest.php`, `ValidationServiceTest.php` |
| `tests/Feature/Models/` | an Eloquent Model | `<Model>ModelTest.php` | `UserModelTest.php`, `TicketModelTest.php` |
| `tests/Feature/Config/` | one bespoke `config/*.php` file | `<Name>ConfigTest.php` | `AppConfigTest.php`, `TenantsConfigTest.php` |
| `tests/Feature/Enums/` | an Enum | `<Enum>EnumTest.php` | `PresenceStatusEnumTest.php`, `ReservationErrorEnumTest.php`, `SkillTierEnumTest.php`, `SkillIconEnumTest.php` — all 20 `app/Enums/*` now covered (`TicketError` + `TicketErrorEnumTest` deleted 2026-11-03 as zero-reference dead code once effectiveness became optional) (`NotifyEventEnumTest` completed the set 2026-10-28; `Country` + `CountryEnumTest` deleted 2026-10-30 as zero-reference dead code — `Nationality` is the wired-in sibling) |
| `tests/Feature/Helpers/` | a global helper in `app/Helpers/index.php` | `HelpersTest.php` | `HelpersTest.php` — one module file folding every helper function (one-file-folds-all shape; see the Exception paragraph below) |

The `ModelTest` suffix on the Models files is deliberate — it disambiguates `TicketModelTest` (the Eloquent model) from `ThsTest` (the Livewire component) and `ThsResourceTest` (the Filament resource).

Cover **core workflow dynamics, not tiny details.** Skip framework plumbing (plain `belongsTo`/`hasMany` wiring, default `$fillable`, default timestamps, plain relation-return checks — the Livewire guide §14i "don't test framework behavior" applies here too). Test the target's OWN custom logic: custom scopes, accessors/mutators, casts with defensive behavior, custom methods with branching, custom constants, and state-machine lifecycle hooks.

**Granularity floor — do not chase framework internals.** A test is worth writing when it exercises *your* logic, not when it pokes at the framework to reach it. The line: prefer a pure/static method test (`UserFormPresenter::normalizeBookingState` — blank→`[]`, assoc-pairs→bool-cast + `all`-sorted-first, scalar-pairs, missing-value→false) or a reflection-invoke of a data method on a `newInstanceWithoutConstructor` widget (`OperationalChart::getModuleAData` guest-exclusion via a distinctive-value fixture). Do **not** write tests that exist only to pull closures off Filament internals — e.g. reflecting `ExportColumn::$getStateUsing` to verify an exporter's per-column `formatStateUsing` null→`'-'` / bool→label / Jalali-date closures. Those closures are framework glue around trivial formatting; the export pipeline already runs them, and the reflection couples the test to Filament's private property names (which shift between versions and even between `ExportColumn` and its schemas-`Component` cousins — `ExportColumn::record()` does not exist, the record lives on the `Exporter`). If a piece of logic is genuinely core, lift it into a pure helper/Service/Presenter method and test *that*; if it only lives inside a Filament closure, it is not worth a dedicated test. Same spirit as §14i: test behavior, not plumbing.

**Exception — one module file for a tightly-coupled peer-family submodule:** when a service directory is a family of small peer classes that share one contract or one parent, write **one module test file** folding every class in that submodule into a single file — not one file per class. References: `Services/ReservationValidatorsTest.php` (14 `BookingRule` validators under `app/Services/Reservation/Validators/`), `Services/SearchServiceTest.php` (23 Search `*Resource` + navigation/content classes under `app/Services/Search/`), `Services/MenuServiceTest.php` (25 Badge/Indicator/Nudge classes under `app/Services/Menu/`, plus the 2026-10-25 nudge write-batching pins — identical shared payloads across a broadcast, id-only `audienceUsers()`, bulk delete bumping the notification version — and `PulseService` since 2026-10-25: per-part fingerprint diff, time-based changes with no write via `Carbon::setTestNow`, the bell's unread set). Same `useMysql()` + transaction skeleton, one shared helper layer across all classes in the module. This is the sanctioned alternative to the per-class rule for peer-family submodules only; a standalone service with its own distinct contract still gets its own dedicated file. The same one-file-folds-all shape covers `app/Helpers/index.php` — a flat bag of peer free functions rather than peer classes — in `Helpers/HelpersTest.php`: pure string/date/enum/file tests skip the DB; `canAdmin`/`isSpecialDay` run through `useMysql()` + transaction; `Auth::logout()` + `Cache::flush()` in **both** `setUp` and `tearDown` guard the auth and `isSpecialDay` cooldown leaks; `canAdmin`'s per-`user.id` function-local static is safe across tests because factory auto-increment ids are monotonic and never reused.

---

## 1a. Mirror tree, master files, and the importance bar (non-negotiable)

**The test tree mirrors `app/`.** When you refer to a Service/Livewire/Filament target you expect to find every related test annotated to that target's location under `tests/Feature/` — `app/Services/X/…` mapped to `tests/Feature/Services/`, `app/Livewire/Dashboard/<module>/…` to `tests/Feature/Livewire/Dashboard/`, `app/Filament/Resources/<X>Resource` to `tests/Feature/Filament/<X>Resource*Test.php`. Never invent a new ad-hoc directory or unconventional test file shape when the mirror slot exists. For Dashboard Livewire specifically, the mapping is **flat files, one master per module** (`app/Livewire/Dashboard/Project/Kanban.php` → folded into `tests/Feature/Livewire/Dashboard/ProjectTest.php`, NOT `Dashboard/Project/KanbanTest.php`) — per `Livewire/Dashboard/livewireTestPattern.md`; a `Project/`-style per-tab test folder is drift, not mirroring, and never belongs in the tree.

**One MASTER test file per important class.** `ChannelTest`/`CalendarTest` contain the module's actions, presentation/dto, and main component behavior in one file — fold those layers in rather than opening `<X>ActionTest.php`, `<X>FormTest.php`, `<X>PresenterTest.php` companions (§1's peer-family exception is about peer classes with one contract, not layers of one class). No per-method test files; no one-file-per-anything sprawl.

**No island/cross-cutting test file — a check about one target's code lives in that target's own mirrored file, even a static-scan check.** The instinct to write "one file that scans every resource for pattern X" is wrong almost every time: it breaks the mirror rule above, and it means a regression in Resource A's code shows up as a failure with Resource A's name nowhere in it. Case study (2026-09-29): `app/Filament/filamentPattern.md`'s closure-naming gotcha (a Group/filter closure with a misnamed first param silently breaks) was first caught with a single centralized `tests/Feature/Filament/FilamentClosureConventionTest.php` scanning the whole `app/Filament` tree — this was DELETED the same session and replaced with one `test_group_and_query_closures_use_correct_param_names` method folded into each affected resource's own `<X>ResourceTest.php`, scoped only to that resource's own presenter file. The `ViewConventionTest.php`/`ModulesConfigTest`-style scanners are the ONE sanctioned exception — a check with genuinely no app/ mirror slot at all (a repo-wide Blade-smell sweep, a hand-authored `config/*.php` shape check) still lives at the `tests/Feature/` root as its own file, per §7's data-only config files and this file's own header. If the thing you're checking traces back to one class/resource/module, it is NOT that exception — fold it into that target's file.

**The importance bar.** Write a test only when its failure would mean broken behavior or a silently killed feature/intelligence — not for plumbing, formatting glue, or per-line coverage. Nobody should grep for a forgotten test on a trivial helper: if the thing failing wouldn't ship a regression anyone lamented watering down, don't test it. This keeps the suite meaningful and review-able — the suite should show what is broken, not be fussy.

**Temp tests named `*Tmp*`, erased same turn.** Any probe/sanity/off-to-the-side test you write for a mid-debug question gets `*Tmp*` in its file name (`CalendarTmpTest.php`, `FooTmpTest.php`), and is deleted before the turn you created it in ends (§9a covers the discipline). `*Tmp*` files must never appear in a finished session.

---

## 2. DB wiring — same `useMysql()` + transaction skeleton as the Livewire guide

DB wiring + `useMysql()` skeleton — see `tests/Feature/Livewire/Dashboard/livewireTestPattern.md` §1. This project's migrations are MySQL-authored (collations, column modifiers) and fail under the sqlite testing DB, so `RefreshDatabase` / `DatabaseMigrations` is broken project-wide; every test that touches the DB copies that verbatim `useMysql()` helper and wraps each test in `DB::beginTransaction()` / `DB::rollBack()`.

**Pure-logic tests skip the skeleton entirely.** Accessors, mutators, casts, validation rules, sanitization, and Jalali-assembly that work on an in-memory `new Model([...])` or a pure service need no DB — just `extends Tests\TestCase` (see `ContentSanitizerServiceTest`, `PersianDateFieldServiceTest`, `SuperAdminExclusionTest`, the in-memory accessor tests inside `UserModelTest`/`TicketModelTest`). Mix freely within a single file: `UserModelTest` runs scope/DB-backed tests through `useMysql` and the accessor/`extra`-bucketing tests on `new User()` in the same class.

**Actor creation:** `User::factory()->create(['status' => 'active'])` always (Livewire guide §14b). Add `type`/`role`/`profile.department_id`/`position` only when the target gates on them.

**Auto-increment IDs are not reset between tests** — capture IDs from the created model; never hardcode.

---

## 3. Static-memoization and cache leak guards — clean in BOTH setUp and tearDown

Cache and static state are **not** transactional, so a warmed value leaks across the rollback boundary and breaks the next test. Add the cleanup in **both** `setUp` and `tearDown`:

- **Targeted `Cache::forget('exact-key')`** when you know the key — preferred. `ValidationServiceTest` forgets `reservation_policies_{$type}` per `ResourceType` + `reservation_disabled_types`.
- **Blanket `Cache::flush()`** when several keys are touched — `StateServiceTest`, `NudgeServiceTest`, `UserModelTest`, `TicketModelTest`.
- **Static arrays on the target class** — `NudgeService::reset()` in `ReconcileNudgeTest` and `NudgeServiceTest` (the booted `NudgeServiceProvider` pre-registers real nudges; `reset()` clears `$rules`/`$registered`/`$wired` so a test's fake rule is the only one live).
- **Laravel `once()` memoization** — `Once::flush()` + `Cache::forget('permission_modules')` in `SuperAdminExclusionTest`, because `Permission::availableModules()` memoizes via `once(Cache::remember(...))` and the call-site hash survives between tests.
- **First-class callable `Cache::forget(...)` inside model events is unreliable** — a `booted()` closure written as `fn() => collect($keys)->each(Cache::forget(...))` does NOT reliably forget keys that were primed via `Cache::remember($key, <DateTime TTL>, ...)`. `Collection::each` passes `(value, key)`, so the call becomes `Cache::forget('user_all_options', 1)` (2-arg), and that path silently fails to evict `Cache::remember`-stored entries while still evicting `Cache::put`-stored ones. Use an explicit `foreach ($keys as $k) { Cache::forget($k); }` (or `each(fn($k) => Cache::forget($k))`) — direct 1-arg `Cache::forget($k)` works inside `created`/`saved`/`deleted` listeners. `UserModelTest::test_creating_user_invalidates_cached_all_option_list` guards this: prime `user_all_options` via `getCachedAllOptions()`, create a user, assert `Cache::has('user_all_options')` is false and the refreshed map includes the new user.
- **Private static caches on a class** — reset via reflection in tearDown: `ContentSanitizerServiceTest` resets `ContentSanitizerService::$dbIsUtf8mb4` to `null`; `UserModelTest` calls `User::resetTodaysDeskCache()` (and the `resetTodaysDeskCache` test reflects into `$todaysDeskCache` to prove it clears).
- **Auth state** — `SuggestionAccessPolicyTest` calls `Auth::logout()` in `tearDown` before rollback, because the policy reads `Auth::user()`.

If you grep the target for `Cache::`, `->shouldCache()`, `once(`, `static $`, or a `private static ... $...cache` and find any of those, you almost certainly need a guard here.

---

## 4. Direct-invocation patterns (not everything goes through a component)

These tests exercise the class directly, not through Livewire/Filament. Resolve DI with `app()`:

```php
app(ValidationService::class)->validateCancellation($reservation, $user);
app(EventSyncService::class)->sync($reservation);
app(SmsChannel::class)->text($recipient, $message);
```

**Mocking collaborators:**
- `Mockery::mock(...)` + `Mockery::close()` in `tearDown` when you mock a collaborator (Livewire guide §11).
- `Http::fake()` for real HTTP services; assert with `Http::assertSent(fn ($r) => ...)`.
- `Log::shouldReceive(...)` when the service logs instead of calling HTTP — `SmsChannelTest` uses `Log::shouldReceive('info')->andThrow(...)` + `Log::shouldReceive('error')` to prove `text()` catches a log failure and returns `false` without propagating.

**Static methods** (policies, `NudgeService::reconcile`, `HolidayService::getAll`, `Ticket::isClosingStatus`) are called directly; do not mock them. To test a static method that defers to registered state, register a stub (`NudgeServiceTest` builds an anonymous `MenuNudge` implementation; `ReconcileNudgeTest` does the same to drive `handle()`).

---

## 5. Rule-test shape — the `fails()` helper + message-placeholder test

Mirror `Rules/UniqueLiveDocumentTest.php` and `Rules/SuperAdminExclusionTest.php`:

```php
private function fails(SuperAdminExclusion $rule, mixed $value): bool
{
    $failed = false;
    $rule->validate('excluded_modules', $value, function () use (&$failed): void {
        $failed = true;
    });
    return $failed;
}
```

- Test the boundary both ways: at-cap passes, cap+1 fails (proves `>` not `>=`).
- Test the guard arithmetic explicitly — `SuperAdminExclusion`'s `max(1, floor($total * 0.20))` is pinned by seeding 2 modules (max becomes 1 via the `max(1,…)` floor guard), 5 modules (cap 1), and 10 modules (cap 2).
- Fixture strategy follows the implementation: `SuperAdminExclusionTest` seeds `Cache::put('permission_modules', [...])` because `Permission::availableModules()` reads from that cache via `once()`. `UniqueLiveDocumentTest` instead builds a synthetic table with `Schema::create` in `setUp` / `Schema::dropIfExists` in `tearDown`. Pick whichever matches the target; do not touch the real production table.
- Add a message-placeholders test: set locale `fa`, trigger a fail, capture the `$fail` message, assert it contains the substituted values and NOT the raw `:placeholder` tokens.

---

## 6. Model-test conventions

- **One file per model**, folding scopes + accessors + mutators + casts + custom methods + constants + lifecycle hooks into that file.
- **Custom scopes**: apply the scope, assert included/excluded rows; prove the un-scoped query still returns everything.
- **Accessors/mutators**: construct `new Model([...])` (in-memory, no DB), assert the computed value; cover null/empty and each branch. For accessors that need a loaded relation (`assignee_name`, `delegator_name`), `->load('relation')` first.
- **Casts with defensive logic**: round-trip a value and assert the decoded shape; cover null and malformed-stored-value where the cast defends.
- **Enum-agnostic mutators** — test with a **test-local backed enum**, not the production enum. `TaskModelTest` declares `enum TaskTestStatus: string` and `TicketModelTest` declares `enum TicketTestStatus: string`; assigning `TaskTestStatus::Manager` proves the mutator extracts `->value` (`'manager'`) without coupling the test to the production enum's cases (which may need DB/config seeding). The assertion verifies the extracted string, so the test is not trivially passing.
- **Set-mutator normalization + default**: `TicketModelTest` proves `status='OPEN'` normalizes to `'open'` and `'invalid'` defaults to `'open'`; `satisfaction_score=6` (out of range) → `null`.
- **Lifecycle hooks** (`saving`/`saved`/`creating` booted on the model): exercise them by mutating + `save()` and asserting the side effect — `TicketModelTest` covers "clear assignee reverts in-progress→open", "assign advances open→in-progress", "closing does not require effectiveness (optional since 2026-11-03)", "closing composes `action_result` from replies". Hooks live in `app/Models/Concerns/Hooks/Has<Model>Hooks` traits (`modelPattern.md`); the DB-free `ModelHooksTest` pins the structure (no inline hooks in a model, one model per hooks trait, the trait declared last), while each hook's behaviour stays in that model's own test file.
- **Custom methods with branching**: cover each return path. `UserModelTest` covers `isCeo` (MA-only), `isTopExecutive` (chairman/ceo position), `isSeniorDecisionMaker` (top-exec OR MA-fallback), `isDeptHead` (no-superior / superior-exists / no-dept), `highestRankingInDepartment` (lowest rank number / null on empty / ignores inactive), `canAccessPanel`, `permits`.
- **Constants**: assert the value (`User::RANKS['chairman'] === 1`) — cheap but pins the contract.
- **Private statics**: reflect with `ReflectionMethod`/`ReflectionProperty` (`PersianDateFieldServiceTest` tests private static `assemble`/`toJalaliParts`; `UserModelTest` reflects `$todaysDeskCache`).
- **Baseline-delta for count assertions** on tables that carry pre-existing dev-DB rows: assert the delta from before/after your inserts, not an absolute count.

---

## 7. Config-test conventions (`tests/Feature/Config/`)

Only test config files that carry **bespoke logic or hand-authored content data** — precedence rules, computed values, cross-file consumer contracts. Skip stock Laravel skeleton files (`auth.php`, `cache.php`, `database.php`, `fortify.php`, `livewire.php`, `mail.php`, `queue.php`, `services.php`, `session.php`, `logging.php`) and pure pass-through data (`colors.php` — a flat map to Filament's own `Color` enum, nothing to assert). Current coverage: `AppConfigTest`, `FilesystemsConfigTest`, `TenantsConfigTest`, `ModulesConfigTest`, `ReleasesConfigTest`, `FileUploadLimitsConfigTest`.

- **Data-only files with no env layer** (`tenants.php`, `modules.php`, `releases.php`) are read straight through the booted config repository — `config('tenants.fateh.name')`, `config('modules')`, `config('releases')` — since Laravel auto-loads every `config/*.php` file under its own filename key, no manual `require` needed.
- **Files with an env-override-over-tenant-default resolver** (`app.php`, `filesystems.php`) can't be exercised via `config()` once the app is booted (the value was already resolved at boot-time env state) — `require config_path('<file>.php')` directly instead, wrapped in a small `putenv()`/`$_ENV`/`$_SERVER` set-and-restore helper (see `AppConfigTest::loadAppConfig()` / `FilesystemsConfigTest::loadFilesystemsConfig()`). Verified empirically that `putenv()` changes are visible to Laravel's `env()` helper immediately (the Dotenv repository's "immutable" flag only blocks `.env`-file loading from overwriting an already-set var, not live `getenv()` reads) — always restore the previous value in a `finally` block so no test leaks env state to a sibling file.
- **Shape/parity checks** guard onboarding mistakes in hand-authored arrays: every tenant/module/release entry has the same required keys with non-empty values, and ids are unique. `ModulesConfigTest` additionally cross-checks every `config('modules')` id against `ModulesGuideWidget::moduleMeta()`/`moduleFilamentIcons()` (invoked via `ReflectionMethod` since they're `protected`) — those two hand-maintained maps are a separate source that must stay in sync with `modules.php`, and a silent gap only shows up as a missing `major_category`/icon in the admin widget, not an error.
- **Valid-value whitelists** for keys interpolated into raw strings (CSS custom properties, route names) rather than passed through a typed enum: `releases.php`'s `color` is spliced directly into `var(--md-sys-color-{$color}-container)` in `release.blade.php` (`app/Livewire/livewirePattern.md`'s "Release-note sync" section) — a typo resolves to nothing at render time with no PHP error, so the test pins the value to the actual `--md-sys-color-*` role names defined in `resources/css/core/theme.css` (`primary`/`secondary`/`tertiary`/`error`).
- **Documented invariants get a dedicated test**, not just a shape check: `ReleasesConfigTest::test_exactly_one_release_carries_the_newest_badge_and_it_is_the_last_entry` pins the hard rule from `livewirePattern.md` §"Release-note sync" — exactly one entry ever carries `'جدیدترین'`, and it must be the last array entry (the renderer does `collect(config('releases'))->reverse()` and treats `$loop->first` as newest).

---

## 8. `markTestSkipped` discipline

Skip only for a genuinely unreachable branch, and always leave a one-line note. **As of 2026-08-14 the combined core suite (Jobs/Support/Rules/Services/Models/Config/Enums/Helpers) has exactly 9 legitimate skips, in 4 categories — this is the full, current list. Before adding a new skip or investigating an existing one, check here first.**

1. **Dev-DB seed state makes a branch unreachable inside a transaction** (4 skips): `UserModelTest::test_is_senior_decision_maker_returns_true_for_ma_user_when_no_top_executive_exists`, `DepartmentModelTest::test_any_has_custom_ticket_options_returns_false_when_none_have_options`, `Support/TicketAccessPolicyTest`'s no-target-department test, and `HrAnalyticsServiceTest::test_hr_a_appends_other_slice_when_invalid_position_exists` (the dev DB already holds invalid-position rows, so the "appends a سایر slice when an invalid position exists" delta cannot be attributed to a freshly seeded row inside the transaction) — all four guard with a conditional: query the pre-existing dev-DB state, `markTestSkipped` only when it makes the branch impossible (dev DB seeds real departments/PS users/top-executive users/invalid-position rows that make the "none exist" or "clean delta" branch unreachable inside the test transaction), otherwise run the real assertion. They exercise the real path on a clean DB.
2. **The target is a placeholder with no real contract to assert** (0 skips since 2026-10-27): `SmsChannel` now calls the Kavenegar REST API natively, so `SmsChannelTest` asserts the real `Http::fake` URL/payload, error mapping and key-leak guard instead of skipping; the category stays for any future placeholder service.
3. **A DB schema constraint makes the test's premise impossible to insert** (3 skips): `EnergyModelTest`'s "answers cast returns null when column empty" — the `answers` column is `NOT NULL` per migration `2026_09_19_000018_create_energy_tests_table`, so a null/empty value can never reach the DB to exercise the cast's null-handling branch. `PersolTeamServiceTest`'s "Ma viewer with a gender outside female/male applies no extra position filter" — `profiles.gender` is `enum("female","male") NOT NULL` per migration `2026_09_19_000031`, so a third gender value can never reach `applyMARules` to exercise the no-else branch. `HrAnalyticsServiceTest::test_hr_b_other_slice_branch_unreachable_due_to_enum_constraint` — `profiles.degree` is a MySQL `enum NOT NULL`, so an invalid degree value cannot be inserted to exercise the `سایر` (other-slice) branch. All three skip rather than force an impossible fixture. The deleted `ReservationPolicyModelTest::test_value_cast_returns_null_for_malformed_stored_json` was a bare unconditional `markTestSkipped` with no body (the `value` JSON column rejects malformed JSON at insert, so the branch is permanently unreachable) — removed 2026-08-14 as dead weight per the Photo-incident rule below; a permanently-unreachable branch with no assertion is not a test, and its rationale is already captured here in Cat 3.
4. **A MySQL-only integration test skips on a non-mysql connection** (1 skip): `DmsKeyGrouperTest`'s integration test guards `config('database.default') !== 'mysql'` since the extraction logic is raw MySQL SQL, not portable — only skips if the suite is somehow run against a non-mysql default connection.

Never `markTestSkipped` to dodge a fragile mock or an unfinished test — fix the test or surface the issue. Never skip silently; the note is the spec. **Real incident (2026-08-01):** `PhotoModelTest`'s 3 db-round-trip tests (`test_scope_by_department_filters_by_department_code`, `test_path_cast_round_trips_array_through_db`, `test_departments_cast_round_trips_array_through_db`) were bare `markTestSkipped('no PhotoFactory')` calls with no test body — but `database/factories/PhotoFactory.php` existed and worked the whole time; the note was simply stale and nobody circled back. Fixed by writing the real assertions (`Photo::factory()->create([...])` + a fresh `Photo::find()` re-fetch to prove the DB round-trip, `ZZ-`-prefixed department codes per the file's own convention to avoid colliding with dev-DB-seeded ones). If you find a skip whose stated reason no longer holds (referenced class/file exists, referenced constraint no longer applies), that's not one of the 4 categories above — fix it immediately rather than re-investigating why it's "supposed" to be skipped.

---

## 9. Comment policy

Match the rest of the suite: **no code comments.** A 1-2 line class-level docblock describing what the file covers is acceptable (see `ReconcileNudgeTest`, `DmsKeyGrouperTest`); a few essential inline words on a non-obvious regression guard are acceptable. No narration, no per-method comments. (Project-wide preference: `feedback_no_comments`.)

---

## 9a. Throwaway diagnostic tests — write freely, delete before finishing

A test written purely to investigate/verify a hypothesis mid-debugging (a repro test, a sanity check, a one-off dump of rendered HTML) is not real coverage and must not be left in the suite once it has answered its question — name it `*Tmp*` (§1a) and delete it in the same turn, before moving on to the next task. This is different from a real regression/coverage test (which stays permanently): the distinguishing question is intent — "did I write this to answer a question for myself, or as lasting coverage for this target class" — not the file's name or location. When a diagnostic investigation turns up a real, permanent test worth keeping (e.g. a mounting-regression guard), fold it into the existing target file per §1's "one file per target" rule rather than leaving it in its own throwaway file — do not invent a new ad-hoc test file/directory (e.g. a bespoke `tests/Feature/Http/` for one route) when an existing file already owns that target's behavior.

---

## 10. Running

```bash
php artisan test tests/Feature/Jobs
php artisan test tests/Feature/Support
php artisan test tests/Feature/Rules
php artisan test tests/Feature/Services
php artisan test tests/Feature/Models
php artisan test tests/Feature/Config
php artisan test tests/Feature/Enums
php artisan test tests/Feature/Helpers

php artisan test tests/Feature/Jobs tests/Feature/Support tests/Feature/Rules tests/Feature/Services tests/Feature/Models tests/Feature/Config tests/Feature/Enums tests/Feature/Helpers
```

The combined command runs all core layers at once — the cross-file cache/static-leak check.

Run the single class while iterating (`--filter="NudgeServiceTest"`), then the whole category, then the combined command before finishing — the combined run catches cross-file cache/queue/static leaks that a single-class run hides.

**Status as of 2026-08-14:** the combined core + Enums suite is green at the committed state with 9 categorized skips. Enum coverage is now complete: all 17 `app/Enums/*` have a dedicated `*EnumTest.php` (`SkillIconEnumTest`/`SkillRequestStatusEnumTest`/`SkillTierEnumTest` filled the last gaps; `SkillIcon` is a pure-cases enum — cases-count/order/uniqueness/`default()`/`tryFrom`/`from` only, no method map). Run: `php artisan test tests/Feature/Jobs tests/Feature/Support tests/Feature/Rules tests/Feature/Services tests/Feature/Models tests/Feature/Config tests/Feature/Enums tests/Feature/Helpers`. Test/assertion totals drift while the working tree is mid-edit, so they are intentionally not pinned here; the stable figure is the §8 skip count (4 categories, 9 skips as of 2026-08-14) — if a run shows a different skip count, re-verify each extra/missing skip against §8 and update §8's list rather than trusting the raw number, and never record a skip count in this status line that §8 does not account for.

---

## 11. Two model-test gotchas found 2026-08-24 (TaskModelTest / TaskDetailModelTest)

**`wasRecentlyCreated` stays `true` for the lifetime of an in-memory instance.** Laravel sets it on the initial insert and never resets it on a later `->update()` call against the *same* PHP variable — only a fresh fetch starts clean. Any `booted()` `saved` hook gated by `if (!$wasRecentlyCreated) { ...log... }` (see `Task::booted()`, `TaskDetail::booted()`) will silently skip its branch if the test reuses the just-created variable: `$task->update([...])` after `$task = Task::factory()->create()` exercises the *creation* branch, not the *update* branch, even though the assertion looks like it's testing an update. Always re-fetch before the mutating call: `Task::find($task->id)->update([...])` / `TaskDetail::find($detail->id)->update([...])`. This is not a production bug (real requests always hydrate a fresh instance per request) — it is purely a test-authoring trap, and it is easy to reintroduce by copy-pasting a working test and trimming the re-fetch as "unnecessary." A re-fetched update through `ActivityLogger::system()` also needs `$this->actingAs($user)` beforehand — `replies.user_id` is `NOT NULL` and the logger reads `auth()->user()?->id`.

**MySQL JSON columns can reorder object keys on round-trip.** `assertSame` uses PHP's `===`, which for arrays requires identical key *order*, not just identical key/value pairs. A JSON-cast column read back after a DB round-trip (`payload`, `reactions`, `checklist`, or any assoc-array cast) can come back with keys in a different order than they were written, failing an `assertSame` that is logically correct. Use `assertEquals` for JSON-cast associative-array assertions; keep `assertSame` for plain scalars and simple indexed arrays (e.g. `[$id]`), where ordering is never in question.

**`tests/Feature/Config` added 2026-08-01:** 5 files (`AppConfigTest`, `FilesystemsConfigTest`, `TenantsConfigTest`, `ModulesConfigTest`, `ReleasesConfigTest`), 21 tests, 489 assertions, all green — see §7 for conventions.

**`tests/Feature/ViewConventionTest.php` added 2026-09-14 — the design-system tripwire (no target class, DB-free).** One root-level `Tests\Feature` file (no ad-hoc subdirectory — there is no `app/` mirror slot for a view-convention scan, so the Feature root is the slot): a `#[DataProvider]`-driven scanner that walks every `.blade.php` under `resources/views/livewire` + `resources/views/layouts` and fails on any file containing a known component-family bypass smell outside its committed baseline allowlist. Current smells: raw `md3-btn`, raw `md3-input-group`, raw `animate-pulse` (skeleton bypass), hand-rolled lightbox shell (`min(96vw,1600px)`). The allowlist constants ARE the inventory of known legacy bypasses (auth forms, toCome, kanban/taskboard input groups, ~24 inline-shimmer module files) — a new digression goes red until it adopts the shared component or is allowlisted with a justified reason. When a new bypass family gets extracted into a component (§ viewPattern.md §8.15's sweep pattern), remove its files from the allowlist AND add the shape's smell string to the provider. Add new smells as one more provider row, never as a second scanner file. It runs with the whole suite (`php artisan test tests/Feature/ViewConventionTest.php` standalone); no `useMysql` skeleton — it touches no DB.

**Persian-digit guards (2026-09-18, all named `*persian_digits*` → one command `php artisan test --filter=persian_digits`):** display numbers render via `convertToPersian()` (viewPattern.md convention paragraph); the tripwires live in master files — HomeTest `test_ui_title_count_renders_persian_digits` (component-level, DB-free: guards all `<x-ui.title>` header counts), TaskBoardTest `test_column_task_count_renders_persian_digits` (regex on the badge span), AuthorityTest `test_row_index_renders_persian_digits` (regex on the first-row index span). Gotcha behind the TaskBoard one: a fresh `Livewire::actingAs($u)->test(Main::class)->html()` on a lazy component returns the LOADING PLACEHOLDER (skeleton), not the real view — assert on the SAME test instance after a `->call()` (the post-call render is real), or the assertion silently inspects the wrong DOM.

When in doubt about how to test a specific pattern, `grep -rln "useMysql" tests/Feature/` and copy the closest sibling in this guide's reference list.

---

## 12. Justifiable failure categories — classify before debugging (suite triage, added 2026-10-05)

A full-suite run that shows errors/failures is NOT automatically a regression. Classify against this list FIRST — each category names its signature and its redress — before touching any app code. Compiled from the 2026-10-05 full-suite run (5082 tests, 43 errors + 11 failures → zero real regressions except one real gate bug).

1. **Mid-run-edit artifact — mass `ViewException: Undefined array key "<x>"` errors.** Signature: dozens of errors in the SAME file cluster (ChannelTest/ContactTest/ProjectTest), all "Undefined array key", on keys a PHP payload edit this session ADDED (`is_online`, `sender_online`). Cause: the long-running suite process holds old in-process PHP classes while a mid-run `view:clear` recompiled new blades against them. Redress: isolated re-run of ONLY the affected files (`php -d memory_limit=2G vendor/phpunit/phpunit/phpunit -c phpunit.xml <files>`); green = artifact, delete nothing, fix nothing.

2. **Committed app change vs gitignored local tests — count pins.** `tests/` is local-only (`.gitignore`), so a committed app change (new enum case `TaskActivityType::WorkflowStep`, new catalog key `display_name`, new `workflow:nudge` legend entry) lands WITHOUT its local count/list assertion. Signature: `Failed asserting that N is identical to N-1` on a count/list pin. Redress: verify the app change is legitimate (trace it to its commit), update the local test's count/list, re-run that file. Never loosen the test to match an unvetted app change.

3. **Windows view-cache rename race.** Signature: `rename(...): Access denied` under `storage/framework/views` during a run with concurrent/recently-cleared view cache. Redress: re-run the same invocation once in isolation; not a code failure. (See [[project_windows_view_cache_test_race]].)

4. **Faker collision with leftover rows in the shared test DB.** Tests run against the live `fateh` DB; leftover factory rows from prior runs can collide with a fresh random Faker value. Signature: `UniqueConstraintViolation: Duplicate entry '...' for key 'users_email_unique'` in a fixture `User::factory()->create()`. Redress: isolated re-run of that test; passes = flake. Do NOT clean the leftover rows without approval (no-data-erase rule).

5. **Factory value vs narrow column — real test-data bug, fix the FACTORY.** Signature: `SQLSTATE[22001] Data too long for column` on a factory insert. Case: `DepartmentFactory` generated `code = fake()->word()` (12+ chars) while consumer columns (`faqs.department_id`, `photos`, `reports`, `profiles`) are `string(10)` — fixed 2026-10-05 to `strtoupper(fake()->unique()->lexify('???'))`. The column design is right; the factory must respect the narrowest consumer.

6. **Filament action-exception swallow — a gate failure masquerades as a null side-effect.** The custom `HandlesActionExceptions` trait (`app/Filament/RelationManagers/RelationManager.php` + Filament pages base) converts ANY `Throwable` inside a mounted action — including `abort(403)` — into a danger `Notification` and returns null. Signature: an admin-panel action test fails `Failed asserting that null is not null` on the created-record lookup, with `assertHasNoTableActionErrors` green. Debug tip: suspect an authorization gate inside the Action class, not a silent no-op — grep the Action for `abort_unless`. Real case 2026-10-05: `ApplyTemplateAction`'s template-owner gate had no admin-panel bypass while `canManageAudience` did.

7. **Suite mechanics — memory limits.** `php artisan test`'s PHPUnit child ignores a parent `-d memory_limit`; long/full runs must go through the direct binary: `php -d memory_limit=2G vendor/phpunit/phpunit/phpunit -c phpunit.xml <files>`. The Lead never runs the suite directly — dispatch a subagent (isolate failures: re-run only the specific failing test/file, never the whole suite).

Standing rule for triage: classify EVERY failure into one of these categories (or declare it a real bug with evidence — the isolated re-run is the arbiter for categories 1/3/4), fix, and record any NEW category here the same session.

8. **Dev-DB schema drift — migration-declared foreign keys are missing (added 2026-10-16).** The live `fateh` dev DB carries exactly ONE foreign key (`channels_owner_id_foreign`) across its 55 tables (`information_schema.REFERENTIAL_CONSTRAINTS`), although the migrations declare `ON DELETE CASCADE`/`SET NULL` everywhere. Any test that asserts a DB-level cascade (the app code relies on the FK, it has no model hook) therefore fails here and passes on a migrated DB. Signature: a parent row is deleted but the child row survives (`1 is identical to 0`). Redress: do NOT add app-level deletes to paper over dev-DB drift; the cascade tests carry a `REFERENTIAL_CONSTRAINTS` guard and `markTestSkipped` when the FK is absent (see §13).

---

## 13. Known failing / skipped tests (2026-10-16)

**Status:** zero known failures at this date; 16 tests skip in isolated runs (13 long-standing + 3 added by the FK guard, all listed below). None of these is caused by the visibility/active/departments, permission-hardening or ticket-label/insight work of 2026-10-15/16.

**Fixed 2026-10-16 (all were test/data defects, none an app bug):**

| Test | Cause | Class | Fix |
|---|---|---|---|
| `EventSyncServiceTest` purge-pre-existing and purge-directly (2) | `event_shares` FK cascade absent from dev DB (§12.8); the event IS deleted, only the share survives | dev-DB drift | `skipUnlessSharesCascade()` guard; real assertions run on a migrated DB |
| `ProjectTest::test_project_force_deletion_cascades_workflow_instances_and_leaves_templates_untouched` | `workflows.project_id` FK cascade absent from dev DB (§12.8) | dev-DB drift | inline FK guard + skip |
| `ProjectTest::test_team_chat_messages_row_map_has_nested_sender_and_raw_attachments` | stale pin: `TeamChat` falls back to `getInitialsAvatarUrl()` when the user has no profile image (committed in `0411ec94`); test expected `null` | stale test | expectation is now `getProfileImageUrl() ?? getInitialsAvatarUrl()` |
| `ConsoleCommandsTest::test_prune_stale_edges_reevaluates_each_stale_dismissed_subject_once_...` | global `Edge::count()` (4 vs 14) counted pre-existing dev-DB edges; the job-count assertion was also global | data-dependence | counts scoped to the test's own users / the `'101'` subject (`Queue::pushed(...)` filter) |
| `AuthorityTest` load-more, `NavbarTest` command-palette toggle (flaky, full runs only) | `UserFactory` used `fake()->unique()->safeEmail()`: `unique()` is per-process, so it collides with users already in the dev DB (`users_email_unique`) | flake (§12.4) | `UserFactory` email is now `userName().'.'.<8 random chars>@example.org` — fixes every test at the source |

**If one of the above fails again:** FK-guard tests that RUN (not skip) and fail are a real regression — the FK exists, so the cascade is broken. A `users_email_unique` error means someone bypassed `UserFactory` (hardcoded email). A global-count failure in a console/service test means a new un-scoped `Model::count()` — scope it to the ids the test created.

**Skipped tests — the 13 long-standing skips (reasons verified against the code) plus the 3 FK-guard skips:**

| Test | Skip reason | Kind | Cheaply fixable? |
|---|---|---|---|
| `Http/HttpRoutesTest::test_full_page_200_smoke_across_dashboard_routes_is_blocked_by_release_modal_redeclare_bug` | unconditional; `navbars/top.blade.php` includes the release modal whose `@php` block declares `releaseNoteItem()`, so a 2nd full-page render in one PHP process fatals "Cannot redeclare" | real pre-existing app bug (blade function declared in-template) | yes in app code: wrap in `if (!function_exists(...))` or move to a helper; then un-skip. Not done: app change out of scope |
| `Livewire/Dashboard/ChannelTest::test_channel_search_finds_message` | unconditional; InnoDB FULLTEXT does not see uncommitted rows inside the rollback harness | harness limit | no (needs committed rows; no-data-erase rule) |
| `Livewire/Dashboard/ContactTest::test_search_messages_action_finds_committed_message` | same FULLTEXT-in-transaction reason | harness limit | no |
| `Models/DepartmentModelTest::test_any_has_custom_ticket_options_returns_false_when_none_have_options` | conditional; skips when any dev-DB department already has `ticket_options` | data-dependent | no (would need to mutate master data) |
| `Models/EnergyModelTest::test_answers_cast_returns_null_when_column_empty` | unconditional; `answers` is NOT NULL | schema-impossible | delete it (bare skip with no body, see §8 Photo incident) |
| `Models/UserModelTest` ×2 senior-decision-maker (MA fallback, MG-only fallback) | conditional; dev DB already has an active chairman/ceo (and MA) user | data-dependent | no |
| `Services/DmsKeyGrouperTest::test_integration_mysql_extraction_returns_merged_keys` | conditional; skips only on a non-mysql default connection | environment | never skips on the dev DB |
| `Services/HrAnalyticsServiceTest::test_hr_a_appends_other_slice_when_invalid_position_exists` | conditional; skips when dev DB already has invalid-position rows (the assertion still runs first) | data-dependent | convertible to a baseline-delta test |
| `Services/HrAnalyticsServiceTest::test_hr_b_other_slice_branch_unreachable_due_to_enum_constraint` | unconditional; `profiles.degree` is NOT NULL enum | schema-impossible | delete it |
| `Services/PersolTeamServiceTest::test_ma_viewer_with_a_gender_outside_female_male_applies_no_extra_position_filter` | unconditional; `profiles.gender` enum NOT NULL | schema-impossible | delete it |
| `Support/TicketAccessPolicyTest::test_head_check_returns_false_when_no_target_department_resolves` | unconditional; dev DB seeds PS users so `defaultTargetDepartment()` never returns null | data-dependent | no (needs master-data mutation) |
| `Services/EventSyncServiceTest` ×2 (purge cascade) | FK guard, §12.8 | dev-DB drift | un-skips itself on a migrated DB |
| `Livewire/Dashboard/ProjectTest::test_project_force_deletion_cascades_workflow_instances_...` | FK guard, §12.8 | dev-DB drift | un-skips itself on a migrated DB |

Other `markTestSkipped` calls exist but are conditional and do not fire on the current dev DB (`HttpRoutesTest` dashboard-500 guards, `TicketModelTest`/`TicketInsightsServiceTest` "no default target department", `FilamentIconOptionsTest` missing heroicon asset). §8's "9 skips" figure is historical; this section is the current list.

**Dev-DB hazards (read before running anything):**
- All DB tests share ONE live dev DB inside transactions; other agents/sessions run against it concurrently. On a lock wait/timeout/deadlock, wait a minute and retry once. Never run the whole suite from an agent; run one file at a time.
- Use `php -d memory_limit=3G vendor/bin/phpunit <file>` — PHP's default 128M limit kills long files, and `php artisan test` ignores a parent `-d memory_limit` (§12.7). `ProjectTest` alone takes ~4.5 minutes.
- Faker emails: never hardcode or `safeEmail()` a `User`; use `User::factory()` (unique suffix since 2026-10-16). `Faker->unique()` does not know about rows already in the DB.
- Pre-existing dev-DB rows (edges, departments with `ticket_options`, chairman/ceo users, invalid positions, PS users) make absolute counts and "none exist" branches fragile: assert deltas or scope to ids the test created (§6).
- The dev DB lacks migration FKs (§12.8): never assert a cascade without the guard.
## Jalali-boundary rule (2026-10-19)

Any rule or screen that buckets by month needs at least one test that straddles a Jalali month boundary (31 Mordad / 1 Shahrivar, 30 Mehr / 1 Aban); a Gregorian regression must fail loudly. See `resources/views/viewPattern.md` §8.36–8.37.
