# Suggestions Module Pattern Guide

Employee suggestion-box workflow: submit → team remark → department remarks → CEO/chairman decision → (accepted → optional referral implementation → closed). Two submission pipelines (**self-fill** vs **normal**) that converge on the same stage machine and the same access-control rules. Livewire v4.1 user panel + Filament v5 admin resource, Laravel 12, PHP 8.2. MySQL 5.7/8-compatible baseline.

This doc is the **single source of truth** for the module's access rules, stage machine, and both pipelines. General Livewire conventions live in `app/Livewire/livewirePattern.md`; this doc covers Suggestion-specific architecture.

---

## 1. The one invariant that drives everything

> **`App\Support\SuggestionAccessPolicy` is the only place eligibility (`canDecide`/`canGiveFeedback`/`canMarkComplete`) and review-row construction (`buildReviewRows`) are computed — user panel and admin panel both call it, neither reimplements it.**

Modeled on the existing `TicketAccessPolicy` precedent (Ths module). Before this rework, the admin `CreateSuggestion` page had its own duplicate (and separately buggy) copy of `buildReviewRows()`, and `Detail.php` had ~40 lines of duplicated eligibility logic — any fix applied to one panel silently didn't apply to the other. That duplication is gone; both panels now import the same class.

---

## 2. Two submission pipelines

Both pipelines create exactly one `Suggestion` row and go through `mergeDepartments()` (prepends the submitter's own department as `departments[0]` — a **stable, frozen-at-creation fact**, never re-derived from a live profile lookup) and `SuggestionAccessPolicy::buildReviewRows()`.

### 2.1 Self-fill (`self_fill = true`)

The submitter fills in feedback for every stakeholder department up front (`form.feedback[dept]`, `form.descriptionDepts[dept]`), typically because they already know each department's stance. `buildReviewRows()` creates **one `Review` row per department in `departments[]`** (home dept + every other listed dept), each pre-populated from the form. Stage machine still runs normally afterward — a dept whose submitted feedback is a valid verdict (`agree`/`disagree`/`neutral`) counts as answered immediately, so a fully-self-filled suggestion can jump straight past `team_remarks`/`dept_remarks` to `awaiting_decision` in the same request.

### 2.2 Normal / non-self-fill (`self_fill = false`)

The submitter only writes the suggestion itself; departments are expected to review afterward. `buildReviewRows()` creates **exactly one `Review` row — the home department's**, with a placeholder verdict:
- If the submitter is a dept head (`isDeptHead()`), the home row is pre-agreed (`feedback = 'agree'`, auto-comment) — a manager submitting on behalf of their own team doesn't need to re-approve it.
- Otherwise (regular employee), the home row is `feedback = 'unknown'`, `comments = null` — a **blank placeholder**, not zero rows. This is the fix for the original production bug: a regular employee's suggestion used to create **no review row at all** for their home department, so `syncStage()` could never see a dept head's response and the suggestion sat permanently in `team_remarks` with nobody able to act on it (the dept head had no row to update, `canGiveFeedback()` requires a `!stageOk` match, and `myReview()` returned null). Now the placeholder row exists, the dept head sees it via `canGiveFeedback()` (stage `team_remarks`, `deptId === departments[0]`, feedback not yet a valid verdict) and can act.

No rows are created for other stakeholder departments up front in this pipeline — they get their placeholder-free entry the normal way, via `SubmitFeedbackAction::execute()` (`Review::updateOrCreate`) once the suggestion actually reaches `dept_remarks` for them.

### 2.3 `MA`-department users cannot submit suggestions at all

Because `syncStage()` (§3) reads *any* review row with `department_id === 'MA'` as the senior-decision-maker's verdict, an MA-department manager submitting their own suggestion had their pre-agreed home-department review misread as the final decision — auto-`accepted` in the same request, zero real review. Rather than reworking `syncStage()`'s MA-detection, creation itself is blocked for MA-department users, in both panels:
- User panel: `CreateSuggestionAction::execute()` throws `ValidationException` if `Auth::user()?->profile?->department_id === 'MA'`; the create-suggestion buttons (`list.blade.php`, `placeholder.blade.php`) are also hidden for MA users.
- Admin panel: `CreateSuggestion::createSuggestionRecord()` — the single shared creation path (§7) — throws the same guard for the resolved submitter, before opening its `DB::transaction()`. `SuggestionsRelationManager`'s `CreateAction` is `->visible()`-gated when the owner record's department is `MA` (UI-only; the server-side guard is the real boundary).

This means `departments[0] === 'MA'` can no longer occur on any *newly created* suggestion — the case described below (§2.3.1) is now legacy-data-only, preserved for suggestions created before this guard shipped.

#### 2.3.1 `MA` is never a stakeholder department (legacy home-dept case)

`MA` (the CEO/chairman department) must never appear as a *stakeholder* entry in `departments[1..]` — if it did, `syncStage()` would require a valid `agree/disagree/neutral` review for `'MA'` before leaving `dept_remarks`, but `canGiveFeedback()` explicitly excludes top executives, and the only path to an `'MA'` review is `SubmitDecisionAction`, which only fires once the suggestion is already `awaiting_decision`. That's a deadlock — the suggestion gets permanently stuck in `dept_remarks`. `MA` legitimately CAN be `departments[0]` (the home dept, when an MA-department employee submits their own suggestion) — that's a different, unrelated case and must not be touched by this guard.

Both panels filter `'MA'` out of stakeholder positions (never index `0`) before persisting:
- User panel: `CreateSuggestionAction::mergeDepartments()` filters `$form->departments` before prepending the home dept.
- Admin panel: `CreateSuggestion::createSuggestionRecord()` (the static, reusable creation method — see §7) applies the same filter, and is the single code path used by **both** the standalone `Pages/CreateSuggestion` page and `UserResource\RelationManagers\SuggestionsRelationManager`'s create action.
- Admin edit: `EditSuggestion::mutateFormDataBeforeSave()` strips `'MA'` from `departments[1..]` only, always preserving whatever sits at index `0` — this is deliberately index-aware, not a blanket `!== 'MA'` filter, because a blanket filter would corrupt a legitimate MA-home-dept record. The Filament `Select` options for `departments` intentionally still include `MA` (excluding it from `options()` breaks Filament's own state-vs-options revalidation on unrelated edits to any pre-existing MA-home-dept suggestion — confirmed via `claude-reviewer`).

---

## 3. Stage machine (`HasStageHelpers::syncStage()`)

Read-only over `reviews` (must be loaded — `syncStage()` self-loads if missing), driven entirely by DB state, **no `Auth::user()` dependency** — this is what lets the 48-hour scheduled command (§6) call it with no authenticated user in context.

```
no home-dept review with a valid verdict (agree/disagree/neutral)  → team_remarks
home-dept valid, but NOT all departments[] have a valid verdict    → dept_remarks
all departments[] valid, no MA (CEO/chairman) review yet            → awaiting_decision
MA review feedback = agree    → accepted, or closed if every referral dept has complete=true
MA review feedback = disagree → rejected
MA review feedback = incomplete → under_review
MA review feedback = anything else → awaiting_decision
```

`updateStageIfChanged()` only writes (`saveQuietly`, no observers) when the stage actually differs — this is also what makes `suggestions.updated_at` a free, already-existing "entered this stage at" timestamp; no new column needed for the 48-hour staleness check.

`departments[0]` is always the home/submitter department (guaranteed by `mergeDepartments()`'s `prepend()`), so both `syncStage()` and `SuggestionAccessPolicy` read it as a plain array index, never a live profile lookup.

---

## 4. `SuggestionAccessPolicy` (`app/Support/SuggestionAccessPolicy.php`)

| Method | Rule |
|---|---|
| `canDecide(?Suggestion)` | `Auth::user()->isSeniorDecisionMaker()` AND stage is `awaiting_decision` |
| `canGiveFeedback(?Suggestion)` | user is a dept head, **not** a top executive, has a `department_id`; stage `team_remarks` → must be the **home** dept (`departments[0]`); stage `dept_remarks` → dept must be **any** listed dept; AND that dept's review doesn't already carry a valid verdict |
| `canMarkComplete(?Suggestion)` | user is a dept head, **not** a top executive, has a `department_id`; the MA (CEO) review's `referral[]` must include that dept; that dept's own review must exist and not already be `complete` |
| `ceoReview(?Suggestion)` | `reviews->firstWhere('department_id', 'MA')` |
| `departmentReview(?Suggestion, deptId)` | `reviews->firstWhere('department_id', $deptId)` |
| `buildReviewRows(...)` | pure row-builder, no DB write — see §2 |

### Position-based authority (`HasProfileHierarchy`)

- `isTopExecutive()` — `position` is literally `chairman` or `ceo`. Used to **exclude** top executives from `canGiveFeedback` (they decide, they don't give department feedback) and from the `team_remarks`/`dept_remarks` branch of the badge scope (§5).
- `isSeniorDecisionMaker()` — `isTopExecutive()` OR (no chairman/ceo exists anywhere among active users AND the user is in the `MA` department). The MA-department fallback exists **only** for installations that haven't assigned a chairman/ceo position yet; the moment any active user holds one of those positions, the legacy department-based path stops applying to everyone else in MA. This is a live `exists()` check (not cached) — cheap, since it's only evaluated when a decision-eligible view renders.
- `isCeo()` (legacy, `department === 'MA'`) is **unchanged** and still used elsewhere in the app outside the Suggestion module — do not conflate it with `isSeniorDecisionMaker()`.

---

## 5. Badge/nudge parity (`HasSuggestionAlert::scopeAttentionRequired`)

Same three conditions `SuggestionAccessPolicy` encodes, expressed as a query scope (no pipeline branching — self-fill and normal suggestions both reach `team_remarks`/`dept_remarks` the same way once their placeholder/pre-filled rows are in):
- `awaiting_decision` for senior decision-makers.
- `team_remarks` for the home department's head — matched via the JSON-array-index path `departments->[0]` (see gotcha below).
- `dept_remarks` for any listed department's head, only where that dept hasn't answered yet.
- referral `complete=false` rows for dept heads whose department was referred by the CEO.

**Gotcha — MySQL JSON array-index path syntax.** Laravel's `column->N` query-builder path syntax (e.g. `->where('departments->0', $deptId)`) compiles the segment as a **JSON object key** (`json_extract(col, '$."0"')`), not an array index. Against a JSON array like `["WP","AS"]` that never matches — silently returns zero rows, no error. To address array element 0 you must write `->where('departments->[0]', $deptId)` (bracket syntax — Laravel's `wrapJsonPathSegment()` only emits `$[0]` when the segment itself is wrapped in `[...]`). Verified via `EXPLAIN`/tinker during this rework. `departments` is a `longText` column (not native `JSON` type) — MySQL's `JSON_EXTRACT`/`JSON_UNQUOTE` still work on any column holding valid JSON text regardless of declared SQL type, so this is unrelated to the bug; the bug is purely the missing brackets.

---

## 6. Scheduled auto-resolve (`suggestions:auto-resolve-stale`)

`app/Console/Commands/AutoResolveStaleSuggestions.php`, `routes/console.php` → `Schedule::command(...)->hourly()->withoutOverlapping()`. Auto-discovered (no `Console/Kernel.php` in this project — Laravel 12 streamlined skeleton).

Two independent chunked (`Model::query()->each()`) sweeps over suggestions whose `updated_at` (= time they entered their current stage, §3) is 48+ hours old. Both sweeps share one rule (private `autoFillNeutral()`): for each targeted department, if its review is still `unknown`/missing, fill `neutral`; any already-given verdict (`agree`/`disagree`/`neutral`/`incomplete`) is preserved untouched. `incomplete` is a final-stage "action needed" signal and is never auto-overwritten (silence = no opinion = `neutral`, not an endorsement = `agree`).
- `team_remarks` stale → `autoFillNeutral` for the home department (`departments[0]`) only, then `syncStage()`.
- `dept_remarks` stale → `autoFillNeutral` for **every** department in `departments[]`, then `syncStage()`.

**No new index added for `updated_at`.** Verified via `EXPLAIN`: MySQL uses the existing `suggestions_stage_index` (type `ref`) to narrow to the handful of suggestions currently in that stage, then filters `updated_at` in-memory ("Using where") over that already-small set — adding a composite index would be marginal and the command only runs hourly, so it was left alone per the "creatively minimal" principle.

`SuggestionPresenter::deadlineConfig()` (the "مهلت بررسی" info-row shown in both panels) mirrors this same 48h/`updated_at` window — it used to be a fixed `created_at + 5 days` figure, decoupled from the actual auto-resolve mechanism and actively misleading once this command shipped (a suggestion can be auto-resolved in as little as 48h). Keep the two in sync if either window ever changes.

**System-generated watermark.** Every row `autoFillNeutral` writes carries the exact fingerprint comment `Review::AUTO_RESOLVE_COMMENT` (single-source constant on `App\Models\Review`, referenced by both the command — writer — and `SuggestionPresenter::buildReviewItem` — reader). The presenter exposes this as `is_system_generated` on each review item, and the user-panel detail card (`detail.blade.php`) renders a low-opacity diagonal "خودکار" watermark + a "تولید خودکار" chip on those rows so a scheduler-filled neutral is visually distinct from a department head's manual neutral. The fingerprint-comment signal is deliberately **not** the naïve `user_id === suggestion.user_id && dept !== home` heuristic — that would false-positive on the **self-fill** pipeline (§2.1), where `buildReviewRows` legitimately attributes non-home rows to the submitter (`SuggestionAccessPolicy::buildReviewRows` line 76 sets `user_id = submitterUserId` in `$base`, merged into every self-fill row). The fingerprint comment is the only thing self-fill can never produce (its non-home comments come from `$deptComments[$dept] ?? ''`), so it's the robust differentiator. Regression-guarded by `SuggestionTest::test_review_items_flag_only_auto_resolved_rows_as_system_generated` and `…_self_filled_non_home_review_is_not_flagged_as_system_generated`. Admin panel (`ViewSuggestion`) uses a separate infolist and is not yet watermarked — mirror there if parity is wanted.

---

## 7. File map

```
app/Support/SuggestionAccessPolicy.php                  single source of truth (eligibility + buildReviewRows)

app/Models/Suggestion.php                                model — STAGES/PURPOSES/RULES/PRIORITIES consts, casts, scopeForDepartment
app/Models/Review.php                                     model — FEEDBACKS consts, isComplete(), referralDepartments()
app/Models/Traits/HasStageHelpers.php                     syncStage() — the stage machine (§3)
app/Models/Traits/HasSuggestionAlert.php                  scopeAttentionRequired() — badge/nudge parity (§5)
app/Models/Traits/HasProfileHierarchy.php                 isTopExecutive/isSeniorDecisionMaker/isDeptHead/isCeo (§4)

app/Console/Commands/AutoResolveStaleSuggestions.php     48h scheduled auto-resolve (§6)

app/Livewire/Dashboard/Suggestion/
├── Main.php                                              list/create panel (user panel)
├── Detail.php                                             single-suggestion view; canDecide/canGiveFeedback/canMarkComplete delegate to the policy
├── Forms/{SuggestionForm,FeedbackForm,DecisionForm}.php   create / feedback / decision forms
├── Actions/
│   ├── CreateSuggestionAction.php                        submit — mergeDepartments + policy buildReviewRows (user panel pipeline)
│   ├── SubmitFeedbackAction.php                          dept feedback — abort_unless(canGiveFeedback), updateOrCreate, syncStage
│   ├── SubmitDecisionAction.php                          CEO/chairman decision — abort_unless(canDecide), MA review + referral rows, syncStage
│   └── MarkImplementationCompleteAction.php               referral dept marks done — abort_unless(canMarkComplete), syncStage
└── Presentation/SuggestionPresenter.php                   view-shaping

app/Filament/Resources/SuggestionResource/
├── Pages/CreateSuggestion.php                            admin create — SAME SuggestionAccessPolicy::buildReviewRows (admin panel pipeline)
├── Pages/ViewSuggestion.php                               admin decision/feedback/mark-complete header actions — SAME policy visible() checks as Detail.php
├── Pages/{EditSuggestion,ListSuggestions}.php
├── Enums/{SuggestionStage,ReviewFeedback}.php
├── RelationManagers/ReviewsRelationManager.php
└── Schemas/{SuggestionFormPresenter,SuggestionInfolistPresenter,SuggestionTablePresenter}.php

database/migrations/migrated/2026_06_30_000037_create_reviews_table.php
database/migrations/migrated/2026_06_30_000039_create_suggestions_table.php

tests/Feature/Livewire/Dashboard/SuggestionTest.php
```

---

## 8. Data model

### `suggestions`
```
id, title, description, departments (longText, JSON array of dept codes, departments[0] = home dept),
purpose (longText JSON), rule (longText JSON), attachment, stage (enum, default 'pending'),
self_fill (bool, default 0), priority (enum low/medium/high, default 'medium'), comments,
abort (bool, default 0), user_id, timestamps
indexes: abort, user_id, stage, priority, self_fill  -- no dedicated updated_at index (§6)
```

### `reviews`
```
id, comments, actions, feedback (enum agree/neutral/disagree/incomplete/unknown),
department_id (text, dept code or 'MA' for the CEO/chairman review), complete (bool, default 0),
referral (longText JSON, dept codes the CEO referred to on acceptance), user_id, suggestion_id, timestamps
indexes: feedback, complete, user_id, suggestion_id, (suggestion_id, complete), department_id (prefix 191)
```

One `Review` row per (suggestion, department) pair, plus one row with `department_id = 'MA'` for the CEO/chairman decision.
