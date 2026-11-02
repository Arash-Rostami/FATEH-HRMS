# Notify — admin-configured notification rules (`App\Services\Notify`)

The single reference for the rule-driven mail/SMS system: an admin defines a `NotifyRule` in the
admin panel ("when a `Task` is created/updated/deleted, send this message to these users and these
extra contacts"), and every save of a tracked model fans out to the live matching rules. Admin-only
surface: there is no user-panel UI, no menu badge, no `BadgeLegendCatalog` entry and no user legend.
Read this before adding a channel, making a model trackable, or touching `HasNotifyEvents`.

The pipeline is **model event -> `NotifyService::handle()` -> one queued `SendNotifyRule` (coordinator) per live rule
-> `MessageBuilder` + `RecipientResolver::resolveWithSkips()` -> delayed `SendNotifyChunk` jobs (20 mails / 200 SMS each)
-> `NotifyChannel::sendMany()` -> one bulk `NotifyLog` insert per chunk.** See "Batch pipeline" below.
It is completely independent of the menu Badge/Nudge/Edge system (`app/Services/Menu/statePattern.md`).

## Namespace map

```
App\Services\Notify\
├── NotifyService.php            hot path: handle(Model, NotifyEvent), rulesFor(class), payloadFor(), samplePayload(rule), flush(); CACHE_KEY = 'notify:rules'
├── NotifyRegistry.php           CHANNELS const (channel classes) + channels()/channel($key)/channelOptions(); trackables() = models using HasNotifyEvents (cached 'notify:trackables', 1 day)
├── NotifyConditions.php         static record-filter engine: fields(class), kind/operators/options, matches(model, conditions), validate(class, conditions), summary(), label() — see "Conditions"
├── MessageBuilder.php           build(rule, payload): Message — placeholder substitution into subject + lines (CR/LF stripped from the subject)
├── RecipientResolver.php        resolveWithSkips(rule): [resolved, skipped] — resolved = [channelKey => [address => userId|null]] (active users from user_ids ∪ the audience + extra.contacts the channel accepts; the address is the array key so it is de-duplicated, first user wins, null user id = extra contact); skipped = [channelKey => [[user_id, name, reason]]]; resolve(rule) returns element 0 only
├── Message.php                  readonly value object: subject, lines, ruleName, modelLabel, event, template, url, action (optional button label); text()
├── Contracts\NotifyChannel.php  key(), label(), isConfigured(), addressFor(User), accepts(string contact), normalize(string), send(Message, address): bool, sendMany(Message, addresses): [address => ?error]
└── Channels\
    ├── MailChannel.php          key=mail  address=users.email  accepts=valid email  send = Mail::to()->send(new NotifyMail) — NOT queued (already inside the job); sendMany = send() per address with its own try/catch
    └── SmsChannel.php           key=sms   address=User::sms_number  accepts=Iranian mobile (^(\+98|0)?9\d{9}$)  send = delegates to the active SMS provider (`Channels/Sms/SmsProviderRegistry::active()`, config `services.sms.provider` ← `SMS_PROVIDER`, see "SMS providers"); text($to, $body) is the user-panel SendSmsAction entry (catches, returns bool)

App\Jobs\SendNotifyRule.php                  coordinator (tries = 1): resolves, writes the summary/skipped rows, dispatches the chunks — never sends; see jobsPattern.md
App\Jobs\SendNotifyChunk.php                 one channel, one slice of addresses: sendMany + its log rows (tries = 1)
config/notify.php                            chunk sizes, spacing, timeout, ceiling, detail threshold, resolve chunk
App\Mail\NotifyMail.php                      Mailable over resources/views/mail/notify.blade.php
App\Enums\NotifyEvent.php                    created | updated | deleted (+ label(), options())
App\Models\NotifyRule.php                    rule row; fires(event), watches(changed), matches(model), isLive(?at), scopeLive(), isOneOff(), scopeOneOff(), logs(); ONE_OFF = 'manual'; MassPrunable (one-off rows older than 90 days)
App\Models\NotifyLog.php                     one row per attempt (or per chunk/run: `summary`, `skipped`, aggregated `sent` rows carry a `count`) with `user_id` (null for extra contacts), `event` (created/updated/deleted) and `record_id` (the payload id as a string) copied from the payload; STATUS_SENT / STATUS_FAILED; MassPrunable (90 days)
App\Models\Concerns\HasNotifyEvents.php      the model-side hook (see below)
App\Filament\Resources\NotifyRuleResource    admin CRUD + Send-test action + one-off «ارسال فوری» action (see "One-off messages") + LogsRelationManager + guide tab + read-only logs page (see "Logs view")
database/migrations/2026_10_27_00000{1,2}_*  notify_rules, notify_logs
lang/fa/resources/notify_rule/strings.php    all admin copy
```

## The model hook — `HasNotifyEvents`

`bootHasNotifyEvents()` registers `created`, `updated`, `deleted` listeners that call
`NotifyService::handle($model, NotifyEvent::X)`. An `updated` event whose only changes are bookkeeping columns (`updated_at`, `notified_at`, `deleted_at`)
is skipped. The trait is on **26 models** (all admin-resource models, including `Reservation` and
`Task`) and deliberately **not** on `User`, `Message`, `Permission`, `Credential`, `NotifyRule`, `NotifyLog`.

Per-model hooks (all static/instance methods the model may override):

| Method | Default | Override when |
|---|---|---|
| `notifyLabel()` | the Persian plural label of the model's Filament resource (`NotifyRegistry::trackables()[class]`), else `class_basename` | the `{model}` placeholder needs different wording (the trackable dropdown always shows the resource label) |
| `notifyValues()` | every scalar attribute (minus `notifyExcept()`), dates as Jalali, strings capped at 500 chars | a computed value should be available as a placeholder |
| `notifyExcept()` | `[]` | columns must never reach a mail/placeholder/condition field: PII, tokens, blobs (`Profile` hides PII + `favorite_colors`, `EnergyTest` scores + `answers`, `ReleaseRequest` `attachments`, `Ticket` `requester_files`/`assignee_files`) |
| `notifyFields()` | `[]` | the condition-field catalog needs a kind the casts cannot give, or a dotted JSON key (`settings.sla`): `[field => kind]`, see "Conditions". **Hard rule: literals only** (strings, enum class names, literal arrays) — no queries, no `__()`, no cache calls, because it can run on the save hot path. Overrides (most trackable models): enum-like status/type/category columns (enum class or inline `[value => label]`), `user` and `department` id columns, bool flags, number columns the casts miss, dotted JSON keys (`Ticket` `extra.*`, `Project` `settings.*`, `Authority` `details.*`), list columns (`Department` `units`, `sections`); a model with nothing to add keeps `[]` |
| `notifyPlaceholders()` | `fillable` minus `notifyExcept()` | derived — normally never overridden; feeds the form's placeholder hint and the watch-field options |

Admin-facing placeholders are **Persian**: built-ins `{نام ماژول} {نوع تغییر} {شناسه} {انجام‌دهنده} {تاریخ و ساعت}`
(`strings.builtin_labels`) plus each field's label from the model's resource strings (`fields` array,
`NotifyRegistry::fieldLabels()`); the form hint lists only fields that have a label. `MessageBuilder` also still honours the
English keys (`{model}`, `{event}`, `{id}`, `{actor}`, `{date}`, raw column names) so existing rules keep working.

## Hot path / cache contract

`handle()` runs on **every save of 26 models**, so it must stay almost free:

- Rules live in one plain cache key `notify:rules` (1 day), holding active rules grouped by model class
  (`NotifyRule::where('is_active', true)->where('model', '!=', NotifyRule::ONE_OFF)->get()->groupBy('model')` — one-off rules never enter it). One `Cache::get` per tracked-model save;
  **zero DB queries** when no rule matches the model/event.
- The key is flushed by `NotifyService::flush()` from `NotifyRule::saved`/`deleted` (Tier T with a manual flush, see
  `app/Models/modelPattern.md`). It is deliberately a plain key, not a `ModelCacheVersion` key.
- The cache holds *active* rules only; the validity window (`active_from`/`active_until`) is checked **at fire time in
  PHP** (`NotifyRule::isLive()`), never in the cache, so a rule going live or expiring needs no flush and no scheduler.
- Dispatch is `dispatch(new SendNotifyRule($rule->id, $payload))->afterCommit()`: the job only exists once the saving
  transaction commits. (Inside the test suite's wrapped transactions `afterCommit` never fires — test `handle()` with
  `Bus::fake()` and the job by calling `handle()` directly.)
- The payload is **light for created/updated** (`model`, `id`, `event`, `label`, `changed`, `actor`, `at` — no `values`, so the
  coordinator job holds no record data; it re-reads the record and calls `notifyValues()` when it runs — a record deleted meanwhile = summary row «record_gone», nothing sent. The chunk jobs DO carry the rendered message + addresses, so `queue:prune-failed --hours=720` is scheduled daily) and a
  **full snapshot for deleted** (the record is gone). `payloadFor()`/`samplePayload()` (Send-test) stay full.
- `NotifyRegistry::trackables()` globs `app/Models/*.php` once per day and is wrapped in `once()`. `NotifyRegistry::permitted()` filters it by the acting admin's `canViewAny()` on the model's resource (rule form model list + Send-test sample) and is deliberately not cached.

## Email templates

`extra.template` (`plain` default | `styled`) picks the mail view. `NotifyRegistry::TEMPLATES` maps key => Blade view
(`mail.notify`, `mail.notify-styled`); labels live in `strings.templates`; a new template is one view + one map entry + one label.
`MessageBuilder` copies the key into `Message::$template`, `NotifyMail` resolves the view through `NotifyRegistry::templateView()`
(unknown key falls back to plain); Send-test uses the rule's template; SMS ignores it. The styled view takes its colours from
`TenantService::mailPalette()` (solid hex derived from the tenant's `theme_primary`: email clients cannot use
`color-mix()` or CSS variables) and the tenant logo/name; keep it table-based with inline styles (the small `<style>` block only adds mobile padding/Outlook fixes and the layout
must stay correct without it; no gradients, no web-font loading, no `{{ }}` colours that are not solid hex). It shows a tenant-primary
header (the app logo from `tenantLogo(false)` (the light-coloured variant, because the header is the tenant primary colour — `true` gave the dark-on-blue one) only when set and present in `public/`, app name, event pill), the module name in
the primary colour above the subject, an emphasised first line, a hidden preview line and a footer with the company logo above
the organization name plus the automatic-message notice; the optional `Message::$url` renders a «مشاهده» button (styled) or link (plain); rule messages leave it null, the reminder email sets it.

**Password-reset email (2026-11-02):** `App\Notifications\PasswordResetNotification` (sent via `User::sendPasswordResetNotification()`, synchronous, not gated by the notify switches, no `NotifyLog`) builds a styled `NotifyMail` whose `Message` carries the new optional last field `action` — the button label (default «مشاهده» in both `mail/notify*.blade.php` templates; the reset mail passes «تغییر رمز عبور»). Link lifetime is read from `config('auth.passwords.*.expire')`. The Filament admin reset uses the SAME class: `FilamentServiceProvider::registerPasswordResetMail()` (called from `boot()`, like its sibling `registerX()` methods) binds `Filament\Auth\Notifications\ResetPassword` to `PasswordResetNotification` (Filament resolves it from the container and then sets the public `$url` to its own `/admin/password-reset/reset` link, which the class prefers over the user-panel route). Its toasts are Persian through `lang/fa/passwords.php`. **Other senders on the same mail view:** `App\Notifications\ReminderDueNotification::toMail()` returns a `NotifyMail` (styled, `url` = the reminder's host URL, recipient set with `->to()`), and its `via()` is NOT gated by the notify switches (the reminder email predates Notify and must keep working when a tenant such as fateh has `notify` off). It writes no `NotifyLog` (the table is keyed to a `NotifyRule`). The user-panel Status tab «ارسال پیامک» is live (2026-10-27): the card button opens the shared `x-ui.modals.action` modal (`status/sms-modal.blade.php`, opened client-side via the `open-sms` window event so it appears instantly without re-rendering the whole Status grid — same pattern as `open-about-me`; opening writes two public properties client-side (deferred to the next request) and the server only runs `Status::submitSms()`), the text is validated (`required|max:140`), `SendSmsAction` prefixes the sender's name, refuses do-not-disturb colleagues (`presence()->isObscured()`), rate-limits 3 per minute per sender, reports a gateway failure (`SmsChannel::text()` returns false) and everything is gated by `tenantOff('notify:sms')` (button, modal and `sendSms()`); it writes no `NotifyLog`. The password-reset email still uses Laravel's default template.

## Text direction

`MessageBuilder::isolate()` wraps a subject whose first letter is Arabic-script in RLE…PDF (U+202B/U+202C) so brackets and braces
keep their place in mixed Persian/Latin subjects; Latin-first subjects are returned untouched, and any existing bidi marks are
stripped first (Send-test re-isolates after adding its «[آزمایشی]» prefix). Both mail views use `dir="auto"` + `text-align:start`
per heading/paragraph so each paragraph follows its own language. SMS text is not isolated.

## Trackable models and tenant switches

`NotifyRegistry::trackables()` (cached `notify:trackables`, 1 day) also drops every model whose admin resource the tenant switched off
(`tenantOffName('filament.admin.resources.{slug}.index')`, evaluated while the cache is built), so the rule form never offers a
module the tenant cannot see; `samplePayload()` then refuses such a model. Because the result is cached, a change to a tenant's `modules`
map needs `php artisan optimize:clear` (or `Cache::forget('notify:trackables')`) like any other cache of tenant state. Dependency policy:
Notify is a **soft consumer** of other modules (it only reacts to their saves) and Reminder / Status are soft consumers of Notify, so no
`with` entry is added anywhere; `with` is reserved for hard dependencies (see `config/tenantPattern.md`).

## SMS providers (Adapter + Strategy)

`SmsChannel` is a thin front that knows no vendor. Each vendor is an **adapter** implementing `Channels\Sms\SmsProvider`
(`key()`, `isConfigured()`, `send(string $to, string $body): void` — throw a key-free `RuntimeException` on failure; `sendMany(array $to, string $body): array` — `[number => ?error]` for exactly the numbers given, never throws for a gateway failure), and
`Channels\Sms\SmsProviderRegistry` **selects** one by `config('services.sms.provider')` (`SMS_PROVIDER`, default `kavenegar`; the config uses `env('SMS_PROVIDER') ?: 'kavenegar'` because a present-but-blank `.env` line would otherwise count as "set" and select no provider).

```
Channels/SmsChannel.php                       NotifyChannel front: address rules, bidi stripping, active provider
Channels/Sms/SmsProvider.php                  the contract
Channels/Sms/SmsProviderRegistry.php          PROVIDERS list + active(): the chosen provider if it exists AND is configured, else LogProvider
Channels/Sms/Providers/KavenegarProvider.php  native REST (Http::asForm → api.kavenegar.com), key from services.kavenegar.*
Channels/Sms/Providers/LogProvider.php        fallback + dev: logs the masked number + text length, delivers nothing; isConfigured() only when SMS_PROVIDER=log was chosen
```

**New SMS provider for a client** = one class in `Providers/` + one entry in `SmsProviderRegistry::PROVIDERS` + its
`services.<name>` credentials + `SMS_PROVIDER=<key>`. Nothing else changes: the rule form, jobs, Send-test, reminders and the
Status tab all go through `SmsChannel`, and every caller asks `isConfigured()` first (a misspelled `SMS_PROVIDER`, or a provider
that is out of service / has no credentials, falls back to `LogProvider`: the text is logged, nothing crashes, and `isConfigured()` is false
so every caller reports "not configured" instead of a false success).

## Tenant switches

Notify is a normal entry in `config/modules.php` (`id` `notify`, no `nav`, admin-only like `credentials`; `aliases` `notify-rules` so the
admin slug resolves to the module) with a feature-list entry in `config/module-features.json` and `ModulesGuideWidget` meta/icon maps.
`views` = `['rules', 'mail', 'sms']`: `rules` is the neutral always-on default (a tenant must never list a default view), `mail`/`sms`
are the switchable channels. A tenant lists them in its `modules` map: `'notify' => false` turns everything off (admin page 404s and
disappears, `NotifyService::handle()` returns first thing), `'notify:sms' => false` / `'notify:mail' => false` remove that channel only.
`NotifyRegistry::channels()` filters through `tenantOff('notify:{key}')` at read time, so the rule form, `RecipientResolver`, Send-test and
log labels all skip an off channel silently; a stored rule that lists an off channel simply sends through the remaining ones. Email
styles are not switchable. New channel = add its key to the `views` list in `config/modules.php` so tenants can switch it.

## Window semantics

A rule fires only if `is_active` **and** `active_from <= now` (or null) **and** `active_until >= now` (or null),
evaluated at the moment the event happens (`isLive()`); the job re-checks `is_active` when it runs (a rule switched off while
queued becomes a no-op) but does not re-check the window. `NotifyRule::scopeLive()` is the SQL twin (list "live" filter).
The create and edit pages reject `active_until < active_from` (`NotifyRuleFormPresenter::assertWindow`).

## Watch semantics

`extra.watch` is a list of attribute names and applies to `updated` events only. Empty (or absent) = any change fires;
non-empty = the rule fires only if at least one watched attribute is in `$model->getChanges()`
(`NotifyRule::watches()`). `created`/`deleted` ignore it. The form clears `extra.watch` when the model changes, and shows
the field only when `updated` is selected. The dropdown lists every placeholder column labelled through `NotifyConditions::label()` (raw name when no label resolves; stored values stay the column names). Watch answers "what was edited", Conditions answer "what is the value now": "became X" needs both, and the form hints under both fields and the guide topic say so.

## Extra JSON

`extra` = `{contacts: [...], watch: [...], template: 'plain'|'styled', audience: 'users'|'all'|'departments', departments: [codes]}` and may take more keys later (`departments` is dropped by `mergeExtra` unless `audience` is `departments`). The form only dehydrates
keys that have a visible field, so `EditNotifyRule` merges the form's `extra` over the stored one
(`NotifyRuleFormPresenter::mergeExtra`): unknown/future keys and hidden-field values survive an edit, a cleared contacts field still
empties it, and `watch` is deliberately dropped when the module changes or `updated` is unticked (a stale list would silently block
the rule). Any new `extra` key needs no migration and no change to this merge. `contacts` are free-form emails/mobile numbers (max 20) validated against
the registered channels' `accepts()`; each contact is added only to channels that accept it. `user_ids` (max 100)
resolves through `User::active()` — a deactivated user is dropped silently at send time. The separate `conditions` column is documented below.

## Conditions

`notify_rules.conditions` (existing JSON column, no migration) = a record filter `{"match":"all|any","rules":[{field, op, value|values}]}` or NULL (no filter). It answers "only when the record looks like this" and is evaluated by `NotifyRule::matches($model)` → `NotifyConditions::matches()`:

- **Where:** in `NotifyService::handle()` right after the watch filter, only for rules that carry rules (`blank(conditions.rules)` = match), purely in memory on the model as saved (zero queries, the hot-path contract holds). It tests the **new** state; to express «became X» pair it with `extra.watch` (watch = which field changed, condition = what it is now). `SendNotifyRule` re-checks it on the freshly re-read record (see "Batch pipeline").
- **Catalog:** `NotifyConditions::fields($class)` (memoised per request) = `fillable − notifyExcept() − hidden`, kind inferred from the cast (date/bool/number/list/BackedEnum class, else text), then `notifyFields()` overrides/adds (incl. dotted JSON keys such as `settings.sla`; a dotted key is only accepted if its root is in the catalog, and the raw JSON root that has declared dotted children is dropped from the selectable list). A class that is missing or lacks the trait returns `[]`.
- **Kinds:** `text`, `number`, `date`, `bool`, `list`, `department`, `user`, a BackedEnum class, or an inline `[value => label]` array. `OPS`: text `eq neq contains filled empty`; number `eq neq gt lt filled empty`; date `eq before after filled empty`; list `contains not_contains filled empty`; option kinds (department/user/enum/inline) `in not_in filled empty`; bool `in`/`not_in` only.
- **Matching semantics:** a stale field (no longer in the catalog) **or** an operator illegal for the field's current kind makes the **whole set false** — never match-everything. A null fact is false for every operator except `neq`/`not_in`/`not_contains`; bool null counts as `'0'`. Dates are compared as Jalali `Y/m/d` strings (time part stripped, empty = null; Gregorian input is converted). Persian/Arabic digits and Arabic `ي`/`ك` are normalised for text/list. `any` returns on the first hit, `all` on the first miss.
- **Validation:** the server is the authority — `NotifyRuleFormPresenter::assertConditions()` runs in `CreateNotifyRule`/`EditNotifyRule` (after `assertWindow`) and calls `NotifyConditions::validate()` (rejects unknown field, illegal op, bad value; `MAX_RULES` 10, a value ≤ 255 chars, `values` ≤ 100 and each must be an existing option); no rows stores NULL. The form widgets are convenience only.
- **Labels:** `label()` resolves the resource strings `fields` key by column → the part after the last `.` → `conditions.label_aliases` (`assigned_to`→assignee, `user_id`→creator; the alias map is resolved once per process) → the name without `_id` → for `HasAudience` models only, `general/strings.visibility.{active|departments}` → the raw key. The Conditions picker (`fieldOptions()`, memoised per class) offers only fields with a real localized label **or** fields declared in `notifyFields()`; unlabelled internal columns stay valid for stored rules, summaries and matching but are not offered, and the Edit select still shows a stored rule's hidden field on top of the memoised options. Ticket `attachment` is excepted. The strings lookup tries the resource key, its lowercase, the part after the last `_` and the part before the first `_` (so `EnergyTest` finds `lang/fa/resources/energy`); a resource must own its lang dir under one of those candidates, otherwise its labels fall back to a sibling directory matching by name. `Profile` exposes only a curated safe `fields` set (`department`, `position`, `employment_type`, `employment_status`, `degree`, `field`, `start_date`, `end_date`); gender, personnel id, interests, work experience and every contact/ID/birth/address column stay out. Field labels are cached 24 h (`notify:field-labels:*`, cleared by `optimize:clear`). `summary()` returns Persian lines (stale/invalid → `conditions.invalid_field`) for the table tooltip and the infolist.
- **Admin form:** a «شرط‌ها» section (funnel icon) between the main section and recipients: `Radio conditions.match` (all|any) + `Repeater conditions.rules` (field → op → a `TextInput` for `value`, or a `Select` for `values`; the `user` kind uses search-as-you-type, every other kind embeds its options). The whole value resets when the module changes. The table shows a count badge with a tooltip summary; the infolist shows the summary lines. **Send-test ignores conditions** (says so in `send_test_body`).
- **Hard rule for `notifyFields()`:** literals only (see the hook table) — a dynamic list would run on every save of the model.
- Tests: `tests/Feature/Services/NotifyConditionsTest.php` (master), plus additions in `SendNotifyRuleTest` and `NotifyRuleResourceTest`.

## Recursion rules

- **Never** put `HasNotifyEvents` on `NotifyRule` or `NotifyLog` (a rule save would fire rules about rules; log inserts
  would loop), nor on `User`/`Message`/`Permission`/`Credential` or any other hot/high-churn or auth-critical model.
- `SendNotifyRule` and `SendNotifyChunk` write logs with a bulk `NotifyLog::insert()` (no model events) and the mail channel sends through
  `Mail::to()` directly, so the pipeline itself can never re-enter `handle()`.
- A rule on a model whose own save the job (or a listener) triggers can still self-amplify — keep rules off models
  that the notification flow itself writes.

## Batch pipeline

A company of ~2000 people must never see a send hammer the DB/Redis or crash a job, so a rule firing is split in two stages.

**Coordinator — `SendNotifyRule(ruleId, payload)`** (never sends): finds the live rule, gives the light payload its `values`
(`class::find(id)?->notifyValues()` for a model using `HasNotifyEvents`; a deleted snapshot keeps its own), builds the `Message`, calls
`RecipientResolver::resolveWithSkips()`, and counts `targeted` = distinct people + extra contacts (skipped people included). For created/updated it then re-checks `$rule->matches($record)` on that
freshly re-read record (a deleted snapshot has no record and skips the check): the record may have changed between the save and the job, and on a
mismatch it writes ONE summary row (count 0, error `batch.conditions_changed`) and sends nothing. Over
`notify.ceiling` (5000) it writes ONLY the summary row with the reason and sends nothing. Otherwise it writes the summary + skipped rows
(inserted in slices of 500) and, per channel, `array_chunk`s the addresses (`notify.chunk.sms` 200, `notify.chunk.mail` 20 — small enough to finish inside the 60 s job limit on a slow SMTP) into
`SendNotifyChunk` jobs delayed `index * notify.delay` seconds (3; the index runs across channels so mail and SMS do not burst together,
so 2000 mails = 40 chunks ≈ 2 minutes). Any exception in its own work becomes the summary row's error (`report($e)`), never a crash.

**Chunk — `SendNotifyChunk(ruleId, runId, channelKey, message, addresses, detailed, event, recordId)`** (tries 1, `notify.timeout` 60, `failOnTimeout`):
looks the channel up at run time (switched off meanwhile or not configured = ONE `failed` row, count N), calls `sendMany()`, and writes all its rows with
ONE `NotifyLog::insert`. A throwable becomes one `failed` row (count N, 500 chars, the Kavenegar key masked).

**Row kinds** (all rows of one firing share `run_id`, `notify_rule_id`, `source = rule`, `event`, `record_id`; the Logs UI sums `count` per `run_id`):

| status | channel | meaning |
|---|---|---|
| `summary` | `all` | exactly one per run; `count` = people targeted; `error` = run-level refusal/failure (ceiling, internal error), else null |
| `sent` | channel | per person (`count` 1, recipient = normalized address, `user_id`) when the channel's recipients ≤ `notify.detail_below` (20), else ONE row per chunk (`count` N, recipient «N گیرنده», no user) |
| `failed` | channel | one per failed person with the error; a systemic failure (channel off, not configured, provider exception, or every address of a big chunk refused with the same error) = ONE row with `count` N |
| `skipped` | channel | one per person who could not be reached: recipient = name, `user_id`, error = reason (inactive/deleted, address missing, address invalid, duplicate address); above `detail_below` per channel = ONE row per distinct reason (`count` N, recipient «N نفر») |

Why aggregated above `detail_below`: a 2000-person "all employees" send would otherwise write 2000+ rows per firing into a table that is read by the Logs tab;
a small hand-picked rule keeps the per-person trail an admin needs to answer "did Ali get it?". Failures and skips are always per person (they are the actionable rows).

**Audience** (`extra.audience`): `users` (default) = only `user_ids`; `all` = `User::visibleOnBoard()` ids (active, no guests; one `pluck` per run); `departments` = `User::getCachedDepartmentOptions($code)` ids for each of `extra.departments`.
Hand-picked `user_ids` (max 100) are always added; extra contacts always apply. The id set comes from the cached option lists (no query), then users are loaded in
`notify.resolve_chunk` (500) slices with `whereIn('id')` + `User::active()` and the profile eager-loaded only when SMS is a selected channel. Resolution happens when the message is sent, not when the rule is saved.
Only hand-picked ids that come back missing are reported as «کاربر غیرفعال یا حذف‌شده» (an `all` list holds inactive users on purpose and they are not "skipped people").

Safety nets (2026-10-27 reviews): both jobs have `failed()` hooks (a worker kill still leaves a failed/summary row); an address missing from `sendMany()` results is a failed row «unknown_result», never a silent success; mail transport errors are stored as a fixed message (`report($e)` keeps the detail); `sms` rows with a `user_id` store the number masked (last 4 digits) like the Status/Reservation audit rows; `recipient` is cut to 191 chars; `NotifyLog::audit()` swallows its own insert failure. Sent totals on a summary are **deliveries per channel** (SUM of `count`), so mail + SMS can exceed `targeted` (distinct people). If the queue dies mid-dispatch, earlier chunks are already queued although the summary says the run failed. `HasNotifyEvents` models hide private fields via `notifyExcept()` (Profile adds `accessibility`; Reminder title/notes are intentionally relayed — values are cut at 500 chars and an SMS body at `notify.sms_max` 450 characters including the trailing «…», via `mb_strimwidth`). The `logs` relation manager and the Logs page both need `notify_rule` view (`canViewAny`). Status SMS: 3/min per sender and 3/hour per (sender, recipient) pair (`sms-to:{recipient}:{sender}`, shared with the Reservation urgent SMS). Index `(status, created_at)` serves the default summary view.

**Config** (`config/notify.php`, plain values in the file — no env keys): `chunk.sms`, `chunk.mail`, `delay`, `timeout`, `ceiling`, `detail_below`, `resolve_chunk`, `sms_max`.

## One-off messages

The «ارسال فوری» header button on the rules page (`Schemas/Action/SendMessageAction`, visible with `NotifyRuleResource::canCreate()`) sends a message
with no record event, reusing the whole batch pipeline — there is no separate sending code.

- **Design:** the action creates a normal `NotifyRule` row with `model = NotifyRule::ONE_OFF` (`'manual'`), `events = []` (so `fires()` is never true and the rule never reacts to a save),
  `is_active = true`, name «ارسال دستی — {Jalali now}», then dispatches the existing coordinator `new SendNotifyRule($rule->id, $payload)` (plain `dispatch()`, no transaction).
  Before anything is created, `RateLimiter::attempt('notify-manual:'.Auth::id(), 3, fn () => true, 60)` gates the send — 3 per minute per admin. Throttled → nothing is created or dispatched, one warning notice (`manual.throttled`), modal stays open (`$action->halt()`).
  A dispatch failure → the rule row is removed again, one danger notice (`manual.failed`, no throttle message, no 500), modal stays open for a retry (a failed attempt still counts as one of the 3 hits).
  The modal's subject/lines hints list only the placeholders that resolve for a manual send (`{انجام‌دهنده}`, `{تاریخ و ساعت}`) via `messageFields(oneOff: true)`.
- **Payload:** `{model: 'manual', id: <rule id>, label: «ارسال دستی», values: [], changed: [], actor, at}` — NO `event` key, so `notify_logs.event` stays null (`record_id` = the rule id);
  the present `values` key means `withValues()` never tries to load a record, and conditions are not checked (a one-off rule has none).
- **Form:** the modal is a two-step Filament wizard (`->steps([...])`, modal heading «ارسال پیام یک‌باره»). Step 1 «گیرندگان» = `channels()` + `NotifyRuleFormPresenter::recipientFields()` (audience, departments, userIds, extraContacts);
  step 2 «پیام» = a read-only summary line (`Text`, «ارسال به: … · از طریق: …», computed live from step-1 state via `Get` using `NotifyRegistry::channelOptions()` and the cached department options, no extra queries) + `messageFields(oneOff: true)` (template, subject, lines).
  Each step is validated before the wizard advances (step 1: channels + a recipient; step 2: subject + at least one line); the final button is «ارسال». Steps add no state path, so `needsRecipient` still reads `extra.audience` and the data shape the handler receives is unchanged.
  The same presenter methods build the corresponding sections of the rule form, so the field lists exist once.
- **Limits:** the `notify.ceiling` (5000) and all resolver rules apply unchanged; results are in the Logs page (`source = rule`).
- **Cache:** `NotifyService::rulesFor()` excludes `ONE_OFF` rows in its cache query, so the rules cache never holds them; the `NotifyRule` `saved`/`deleted` hooks therefore skip `NotifyService::flush()` for one-off rules (normal rules still flush).
- **Stats:** the dashboard `ModuleAnalyticsWidget::notifyData()` rule counts (total/active/mail/sms) exclude one-off rows.
- **Admin UI:** the model column/infolist/filter show the «ارسال دستی» badge; `NotifyRuleResource::canEdit()` is false for one-off rules (`/edit` URL and Send-test follow it). The table edit action is explicitly `->hidden()` for one-off rows because Filament's default action authorization goes through `AuthorizesByPermission::getAuthorizationResponse()`, which is record-blind (module-level `update` permission only) and never calls `canEdit($record)`; row click therefore opens the view modal. The inline active toggle is `disabled` via `canEdit($record)`; view and delete stay. The view modal's recipients entry lists the audience label (and the selected department names for «واحدهای انتخابی») before the picked users; the table recipients column carries the department names as a tooltip.
- **Retention:** `NotifyRule` is `MassPrunable` — `prunable()` = one-off rules older than 90 days, removed by the daily `model:prune` (auto-discovered in `app/Models`); the logs go with them through the `notify_logs.notify_rule_id` cascade FK (and are also pruned at 90 days on their own). MassPrunable fires no model events, which is why the cache query excludes one-off rules.
- Tests: `NotifyRuleResourceTest` (action, validation, queue failure, canEdit, refactor guard), `SendNotifyRuleTest` (one-off run), `NotifyServiceTest` (cache exclusion, prune).

## Extension recipes

**New channel** = one class + one line. Implement `Contracts\NotifyChannel` (`key()` unique, `label()`, `isConfigured()` = "are the credentials/gateway present" — callers use it to fail loudly instead of pretending, `normalize(string): string` = canonical form used by `RecipientResolver` to de-duplicate (lower-cased email; mobile as `09…`, so `0912…` and `+98912…` are ONE recipient), `addressFor(User): ?string`,
`accepts(string): bool`, `send(Message, string): bool` — throw or return false on failure, `sendMany(Message, array $addresses): array` — what the chunk job calls: return `[address => null|errorText]` with exactly the given addresses as keys, catch per-address failures yourself (a throw fails the whole chunk as one row), keep error text key-free) under `Channels\`, then add the class to
`NotifyRegistry::CHANNELS`. The form checkboxes, recipient resolution, contact validation, Send-test and log labels all read
the registry; nothing else changes. `RecipientResolver` only eager-loads `profile` when the `sms` channel is selected — a new
channel that needs a relation must extend that `when()`.

**New trackable model** = `use HasNotifyEvents;` on the model, override `notifyLabel()` (Persian label), `notifyValues()` if a
computed value is wanted, and `notifyExcept()` for any sensitive column. Then `Cache::forget('notify:trackables')` (or deploy
`optimize:clear`) so the dropdown picks it up. Check the model is not hot (see recursion rules) and that its `fillable` is
meaningful, since placeholders and watch options derive from it.

## Gotchas

- **Bulk and query writes skip events.** `Model::query()->update()`, `insert()`, `DB::table()` and `->delete()` on a builder fire no
  Eloquent event, so no rule runs. Per-model saves/deletes do.
- **One save = one mail per recipient per live rule.** A rule on a frequently edited model with many recipients mass-mails on
  every save — use `extra.watch` and a validity window.
- **Log columns:** `user_id`, `event` and `record_id` let an admin trace a send back to the person and the record; the Logs tab shows them and filters by event. `user_id` is null for extra contacts.
- **Jobs `tries = 1`:** a retry would re-send to everyone already mailed. A failed address is logged `failed` (error truncated
  to 500 chars) and the rest of the chunk continues; each chunk writes its rows in one insert after sending, and a throwable inside the chunk becomes one `failed` row for the whole chunk.
- **`NotifyMail` is not queued** — it is sent synchronously inside the already-queued job.
- **`Sms/Providers/KavenegarProvider` talks to Kavenegar natively** (`Http::asForm()->timeout(20)->post('https://api.kavenegar.com/v1/{key}/sms/send.json')`
  with `receptor`, `message`, optional `sender`; no SDK/package). Config `services.kavenegar.key|sender` from `KAVENEGAR_API_KEY` /
  `KAVENEGAR_SENDER`. `KavenegarProvider::isConfigured()` is `filled(key)`; with no key the registry falls back to `LogProvider` (dev can also choose
  `SMS_PROVIDER=log` on purpose), and every caller checks `isConfigured()` first so nothing reports a false success: `SendNotifyChunk` writes ONE `failed` row for the chunk with «سرویس پیامک پیکربندی نشده است.»,
  Send-test shows «سرویس این کانال هنوز پیکربندی نشده است», the Status tab action answers «سرویس پیامک هنوز پیکربندی نشده است؛ با مدیر سیستم تماس بگیرید.»
  and `SendReminderSms` does nothing. To swap the SMS provider for a client, add/choose a provider class (see "SMS providers") and set its `services.*` keys and `SMS_PROVIDER`. Success = JSON `return.status === 200`; any other status throws `RuntimeException("کاوه‌نگار {code}: {return.message}")`
  (the job logs it in `notify_logs.error`). The key is part of the URL, so **never log or surface the request URL/exception text**: every
  transport failure is rewrapped as a fixed Persian message. Direction marks (RLE/PDF…) are stripped from the SMS text. No retries
  (the job is `tries = 1`; a retry could double-send). `SmsChannel::text()` serves the user-panel `SendSmsAction` (catches, returns bool).
- **Send-test** (`SendTestAction::make('mail'|'sms')`, edit-page header + table row icon buttons `sendTestMail` / `sendTestSms`)
  sends ONE test through that single channel to the logged-in admin only, using the latest record of the model (or `…`
  placeholders when the table is empty), prefixes the subject with «[آزمایشی]», and writes **no** `NotifyLog` rows. Each button is
  visible only when the rule uses that channel and the tenant has it on. It uses the saved rule, not unsaved form state.
- **Rule limits and safety nets (2026-10-27 review):** `SendNotifyRule` has `$timeout = 60` and `SendNotifyChunk` `notify.timeout` (60), both with `failOnTimeout`; error text is cut
  with `mb_substr` to 500 *characters* (a byte cut could split a Persian letter and make MySQL reject the whole log insert);
  contacts longer than 191 characters are rejected (`notify_logs.recipient` is `string(191)`); `SmsChannel` rewraps EVERY
  transport throwable (in `KavenegarProvider::request`) as a fixed Persian message because the Kavenegar key is part of the URL; `fieldLabels()` is cached per model
  (`notify:field-labels:*`, cleared by `optimize:clear`). Placeholder values come from user-authored fields (e.g. a ticket subject)
  and reach recipients verbatim, so rules on user-writable models can relay arbitrary text.
- **SMS transport errors are diagnosable without leaking the key:** the admin only sees «اتصال به سرویس پیامک برقرار نشد.», but `KavenegarProvider::request` writes `Log::warning('SMS gateway request failed.', ['error' => …])` with the key replaced by `***` (look in `storage/logs`). Typical cause on a Windows/MAMP dev PC: PHP has no CA bundle (`curl.cainfo` / `openssl.cafile` empty in php.ini), so cURL error 60 «self-signed certificate in certificate chain» appears for Kavenegar's Certum certificate chain even though system `curl` works. Fix by pointing both ini settings at a current `cacert.pem` and restarting PHP and the queue workers; never disable TLS verification (the key is in the URL).
- **2026-10-27 final-review hardening:** `NotifyService::handle()` swallows and `report()`s any failure (a cache/Redis outage must never fail the host model's save), skips bookkeeping-only updates (`updated_at`, `notified_at`) BEFORE touching the rules cache, and the queue `retry_after` defaults are 180 s (above `SendNotifyRule::$timeout`); user addresses must pass the channel's `accepts()` (a malformed profile number is skipped, not sent), SMS numbers are masked in logs and `LogProvider` logs only the length; `SendSmsAction` hard-caps text at 140 characters server-side. `notifyValues()` reads RAW columns (never `$appends` accessors, so no lazy loads and no viewer-dependent values on the save path), `deleted_at` counts as bookkeeping (a soft-delete `restore()` is not an «ویرایش»), and a `forceDelete()` of an already soft-deleted record does not fire a second `deleted`. `EnergyTest::notifyExcept()` hides the wellbeing scores — every model with private data must declare its own `notifyExcept()` before rules are built on it. **Known limits:** (the one-job-sends-everyone and full-payload-in-queue limits were removed by the "Batch pipeline"; a created/updated payload no longer carries record data) `notify_rule` permission lets its holder send any non-excluded field to 20 external contacts (keep it out of default grants).
- **Hot-path caveat:** "zero queries" assumes a non-database cache store (redis/array); with `CACHE_STORE=database` the single
  `Cache::get` is one query on the `cache` table.
- **Reminder SMS:** `reminders.channels` may hold `'sms'`; the hourly sweep queues `App\Jobs\SendReminderSms` (via `SmsChannel`, so
  the tenant switch and Kavenegar key apply) once per reminder, sharing `notified_at` with the email. No `NotifyLog` row.
- A rule whose model class is no longer trackable fails Send-test with a clear error instead of silently sending.
- The payload snapshot limits string values to 500 characters (`Str::limit`, so up to 500 plus «...»).

## Logs view (admin, read-only)

There is **no second navigation item**. The Notify page header carries a two-button toggle (`Schemas/Action/ViewSwitchActions::make('rules'|'logs')`):
«قوانین اعلان» (the rules table) and «گزارش ارسال» (`Pages/ListNotifyLogs`, route `notify-rules/logs`, page name `logs`). The active side is
`primary`, the other `gray` + outlined; both pages merge the toggle into their header actions (`ListNotifyRules` aliases the `FilamentHeaderActions`
trait method and keeps «ایجاد قانون»). The page is a plain `Filament\Resources\Pages\Page` + `InteractsWithTable` hosting a table of **another model**
(`NotifyLog`) via `EmbeddedTable::make()` in `content()` — no Blade view. Access, 404 on `notify` off (slug alias `notify-rules`) and the permission
(`notify_rule` → `view`) are inherited from the resource; there is no create/edit/delete/bulk action anywhere, the only record action is «مشاهده»
(an infolist modal wired through `getDefaultActionSchemaResolver`).

Presenters (shared by the page and `LogsRelationManager`): `Schemas/NotifyLogTablePresenter` (columns, filters, groups, label helpers) and
`Schemas/NotifyLogInfolistPresenter`.

- **Row kinds as the UI reads them:** `summary` = one «خلاصهٔ اجرا» row per rule firing (`count` = people targeted, `error` = run-level failure);
  `sent` / `failed` / `skipped` rows share the summary's `run_id` (per person, or one aggregated row with `count` N); `source` `status` /
  `reservation` rows are single SMS audit rows (no rule, no run, `sender_id` = who sent).
- **Default filter = `status = summary`** (the «اجراها» view); the status filter's placeholder «همه» clears it. Other filters: «فقط مشکل‌دارها»
  (ternary: failed+skipped / summary+sent), source, channel (`all` + registry channels), event, rule, recipient user, department
  (`user_id IN (SELECT user_id FROM profiles WHERE department_id = ?)`), created-at range. The range reuses `FilamentFilters::createdAtFilter()` (Jalali picker) but
  its query is replaced by `created_at >= start-of-day / <= end-of-day` so the `created_at` index is used (the stock `whereDate` is not sargable). Because a
  summary row has channel `all` and no per-person fields, the channel/user/department/recipient filters only match anything once the status filter is cleared.
- **Groups:** rule, run, channel, source, status (each with a `getTitleFromRecordUsing` label).
- **Infolist:** a summary row shows rule/event/record/time/targeted, the run totals and the first 50 `failed`/`skipped` rows of the run as
  «name (status): reason». Totals are ONE aggregate (`SUM(count) GROUP BY status WHERE run_id = ? AND status <> 'summary'`) plus one LIMIT-50 problems
  query, both memoised on the record (`setRelation('runStats')`) so the several entries share them. Any other row shows its own fields (sender for audit rows).
  A person who is not among the failed/skipped rows received the message (aggregated failure rows carry no names).
- **Performance:** `->with('rule:id,name')` (no per-row query), user names through `once(User::getCachedAllOptions())`, `PaginationMode::Simple` (no
  `COUNT(*)` on a table that can hold hundreds of thousands of rows), default sort `created_at desc` on the `created_at` index, `run_id`/`user_id`/`notify_rule_id`
  indexes serve the run lookup and the rule/user filters. Recipient search is a `LIKE %…%` scan: acceptable for an occasional admin lookup, narrow with a rule/date filter first.- **Retention:** rows are deleted automatically after 90 days by `model:prune`; the page says so in its empty state and in the guide.
- Tests: `tests/Feature/Filament/NotifyLogsPageTest.php` (page, filters, groups, infolist totals, read-only, toggle, tenant off) and the logs-tab tests in `NotifyRuleResourceTest`.

## Deploy notes

0. Conditions feature: no migration (the `conditions` column already exists). Run `php artisan optimize:clear` (field labels are cached 24 h in `notify:field-labels:*`) and `php artisan queue:restart` (`SendNotifyRule` changed).
1. `php artisan migrate` (creates `notify_rules`, `notify_logs`; both guarded by `hasTable`). `notify_logs` is a single master schema (including `user_id`, `event`, `record_id`) with no patch migration: a dev DB that already ran an earlier version of migration 000002 must drop `notify_logs` and re-run it.
2. `php artisan optimize:clear` — clears `permission_modules` (so the new `notify_rule` module appears in the permission
   form) and `notify:trackables`.
3. `php artisan queue:restart` — workers keep the old job/trait code in memory.
4. Grant the `notify_rule` module to non-developer admins through the Permission resource.
5. `notify_logs` is `MassPrunable` (90 days) and is picked up by the already-scheduled `model:prune` (`routes/console.php`); `notify_logs.notify_rule_id` is an FK with cascade-on-delete (the dev DB lacks migration FKs — do not assert the cascade without a guard).

## Tests

`tests/Feature/Services/NotifyServiceTest.php` (hot path, window, watch, flush, trackables),
`tests/Feature/Services/NotifyConditionsTest.php` (conditions: catalog, operators, matching, validation, summary),
`tests/Feature/Jobs/SendNotifyRuleTest.php` (coordinator: row kinds, ceiling, skips, light payload, conditions re-check),
`tests/Feature/Jobs/SendNotifyChunkTest.php` (chunk failure paths), `tests/Feature/Services/RecipientResolverTest.php` (audience, skips),
`tests/Feature/Filament/NotifyRuleResourceTest.php` (CRUD, window validation, conditions form, logs tab, Send-test), `tests/Feature/Filament/NotifyLogsPageTest.php` (logs page), `tests/Feature/Services/SmsChannelTest.php` (incl. `sendMany`).

RTL icon note: the paper-airplane on the log toggle is `svg(..., 'rtl:-scale-x-100')`, like the Send-test button. The two log empty states cannot take an `Svg` (`emptyStateIcon()` only accepts a string/enum), so `NotifyLogTablePresenter::emptyDescription()` prepends a scoped `<style>.fi-ta-empty-state-icon-bg svg{transform:scaleX(-1)}</style>` to the description instead.

Round-1 strict review (2026-10-27): two users resolving to one address = the second gets a `skipped` row «تکراری»; a rule whose channels are all unavailable writes a summary error «no_channel» instead of a silent empty run; `withValues()` reads soft-deleted records (`withTrashed`) so an edit followed by a soft delete still sends; `NotifyRegistry::permitted()` is memoised per request per user; a rule form needs at least one recipient (`user_ids`, `extra.contacts`, or an audience other than `users`); Status SMS closes the modal only on success. Known and accepted: `NotifyRule::saved/deleted` flush the rules cache inside the save transaction (a concurrent read between flush and commit can cache the old set until the day TTL) — not wrapped in `afterCommit` because the test suite's wrapped transactions never fire it.

Round-2 adversarial review (2026-10-27): the rule form's model Select merges back ONLY the rule's stored model (`$record->model`), never the posted one (a tampered request could otherwise save a rule on a module the admin cannot open); `RecipientResolver` orders users by `id` so the lowest id keeps a shared address deterministically; `SendNotifyRule::$runId` falls back to a fresh ulid (`??=`) so a job queued before it existed does not crash; the `permitted()` memo key is `userId|md5(app.off)`; reminders overdue by more than 24 h are never emailed/texted (sweep window), which the release note states.

Round-4 review (2026-10-27): the chunk's final `NotifyLog::insert` is retried once and then only reported — a log-write failure after a delivered send must never mark the chunk «failed» (a manual resend would double-message people). Known and accepted: (1) a queue-push failure for the deferred `afterCommit` dispatch (Redis down) is thrown from the caller's `save()` after the data committed — wrapping it in `DB::afterCommit(rescue(...))` was rejected because the test suite's wrapped transactions would never fire it; run a reliable queue backend (redis/database). (2) Kavenegar `entries` are trusted: only known failure statuses fail a number, an absent entry counts as accepted by the gateway. (3) Chunks do not re-check `is_active`, so switching a rule off does not recall chunks already queued (the coordinator's check only stops new runs); add an `is_active` re-check to `SendNotifyChunk::handle()` if an emergency stop is ever wanted.
