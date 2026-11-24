# AI UI Pattern — the smart-feature signature

The single source of truth for how every AI / smart feature of the user panel looks and behaves. Approved by the owner on 2026-11-23 via the interactive catalog `AI-UI-PREVIEW.html` (repo root, to be deleted after implementation). viewPattern §8.38 (amethyst tokens, `variant="ai"`, `auto_awesome` reserved for AI) stays in force; this doc adds the shape rules on top.

**Colour note (2026-11-24):** wherever this doc and the linked pattern docs say "amethyst" for an AI element, read `--ai-color` / `--ai-bg` / `--ai-text`, which now derive from the theme tertiary (§1); the element recipes are unchanged.

**Status: implemented 2026-11-23.** The rules below describe the code as it now stands. The design was approved via an interactive catalog artifact (deleted after implementation).

## 1. The signature and its one color rule

The AI look is the theme's own accent + sparkle (2026-11-24, owner call, replaces the per-theme amethyst): `--ai-color` / `--ai-bg` / `--ai-text` are derived once in `resources/css/core/theme.css` from `--md-sys-color-tertiary` (`--ai-color` = tertiary, `--ai-bg` = 16% of it over the surface, `--ai-text` = 22% over `on-surface`), so the colour follows every theme and mode and is not purple by rule; one guard token `--ai-dim` darkens it toward `on-surface` where a light theme's tertiary is too pale (Obsidian light, 50%: the gold measured 1.65:1 on the surface). The `auto_awesome` glyph, never anything else. The same tokens colour AI with an LLM and the smart features without one. **The star is always `--ai-color`, full strength** — never a muted blend (`color-mix(... 55%, white)` on the old right-rail star is exactly what is banned), never primary, in light and dark alike. One documented exception: the top-navbar assistant glyph keeps its 55%-white blend because every theme's header is dark (viewPattern §8.38 item 3).

## 2. Buttons — one model, everywhere

Every AI **control** is one shape: the amethyst tonal button (`x-ui.buttons.form variant="ai"`), star on the right, text on the left. In compact contexts (inside a form field, a message row) the same button renders small; standalone panel actions render at normal size. **No bare-star controls exist anywhere** — the label rule (owner, 2026-11-23): a cramped space gets ONE word without «هوشمند»; a roomy space gets a verb + «هوشمند». Never the literal word «AI».

- Feed suggest: small `✨ پیشنهاد` in the composer row and the per-comment row — replaced the filled 28px icon button with the invented `!h-7 !w-7 !rounded-lg` overrides (deleted with it).
- THS field triggers (`x-ui.forms.ai-trigger`): small `✨ پیشنهاد` at the field ends (tooltips keep the full «نوشتن هوشمند» wording).
- Contact/Channel smart edit («بهبود هوشمند متن» is retired): the menu row is a tonal fill row labeled `✨ ویرایش هوشمند`.
- Ai-report: generate `ساخت هوشمند`, refine `ویرایش هوشمند` (the per-block refine keeps its own «اصلاح این بخش با هوش مصنوعی» label).
- Correspondence: generate `ساخت هوشمند` (+ the viewer's per-block «اصلاح بند»).
- «منشی هوشمند» (channel header/panel — a noun) and «ارسال هوشمند» (SMS modal) unchanged.

## 3. Navigation exception — menu tiles and rail tabs

Menu tiles (`menu.js` `BASE_ITEMS`) and right-rail tab icons do **not** get text or a purple fill. An AI-capable tile is a normal tile plus:

1. the **shimmer halo** (§5) around its border, and
2. one small `auto_awesome` star **inside the tile's top-left corner** (`absolute top-1 left-1`, `!text-[11px]`, always `--ai-color`) — within the tile bounds (the stuck-out corner star was rejected as unpolished), never over the icon, TOP-left because the notification badge owns the top-right and the disabled-lock chip owns the bottom-left.

The word «AI» never appears in the menu. On hover an AI tile gets an amethyst wash (`color-mix(in_srgb,var(--ai-bg)_45%,surface)`) with an `--ai-color` 35% border and glow, its icon chip fills solid `--ai-color`, and the icon and the corner star turn light; the title keeps its colour (`--ai-color` text on the wash fails contrast on a few light themes); a disabled tile gets none of this (2026-11-24, owner pick "filled amethyst chip"; replaces the 2026-11-23 pale-fill-only hover). The halo and the star's place never change. Every AI tile — `ai: true` entries (گزارش‌ساز، دستیار، پیشخوان) and `aiView` entries (مکاتبات، تیکتینگ، مخاطبین، گروه‌ها) alike — shows its module icon in `--ai-color` (2026-11-24, owner call; `aiView` tiles used to keep the primary icon); the corner star and halo appear per-tenant exactly as the `aiView` logic already gates it. The right-rail «اخبار» tab takes the full menu AI-tile recipe in lockstep: `color-mix(primary 8%, surface)` bg + ai-tinted 25% border + icon in `--ai-color` (every state, `!`-bound) + the halo + the `!text-[11px]` star inside the top-left corner (never stuck out — unpolished; never top-right — the badge owns it; the pin button is no conflict — it anchors to the PARENT cell's corner (`top-1 end-1` on the grid wrapper), not the tile's) and the filled-amethyst hover (the tab itself is the chip: solid `--ai-color` bg, light icon and star, `!`-bound so the rail's generic hover utilities cannot win), replacing the tiny 10px star in `navbars/right.blade.php` (owner calls 2026-11-24).

## 4. Smart features without an LLM

Heuristic features wear the same signature but **always with text, never a lone star**: chips like «✨ هوشمند (خودپنهان)» (bottom-bar auto-hide preset — «نوار» dropped from the label, 2026-11-23), «✨ چینش خودکار» (home shortcuts), «✨ پیشنهادها» (status skill suggestions), and «✨ یادآوری» for status occasions — a reminder chip whose person's name sits BESIDE it, outside the chip, never a bare pulsing star next to a name. The home «دسترسی سریع سازمانی» card (the smart-arrangement container) carries the shimmer halo instead of its old primary shimmer strip.

## 5. The shimmer halo — a state, not decoration

The rotating border light (ported from the AI sibling's `#thinking-mode-btn`): a `::before` ring of masked `conic-gradient(from var(--shimmer-angle), ...)` in `--ai-color`, 1px (thinner reads more elegant on large cards; 1.5px was tried first and felt heavy), `mask-composite: exclude`, `--shimmer-angle` declared via `@property` and animated `0→360deg`, **one revolution per 5 seconds** (3s reads busy, 8s reads dead), `pointer-events: none`, inherits the element's radius.

It applies to two things only:

1. **AI-capable navigation** — menu tiles and rail tabs (§3), always on while the capability is on for the tenant, plus the home smart-arrangement card (§4).
2. **Parents hosting live AI** — the container of an open AI panel or an in-flight AI action: the channel «منشی هوشمند» panel while open, the feed comment composer while `suggestReply` runs (`wire:loading.class="ai-halo"`, bare target = any suggestReply call, intended), the THS create-field wrappers and the contact + channel edit boxes while their AI call is busy. Closed/idle containers get nothing.

The CSS class is `.ai-halo` (`resources/css/core/animation.css`): `@property --ai-shimmer-angle`, `aiHaloSpin 5s linear infinite`, `prefers-reduced-motion` kills it.

## 6. Quota — the meaningful bar

Every AI feature with a daily cap (caps in §9) shows the `x-ui.quota-meter` bar (`«سهمیهٔ …» + fill + used/cap in Persian digits`) above or beside its trigger surface. The «N بار دیگر امروز» remaining-chip style is retired.

## 7. Loading

In-flight AI work uses the existing `x-ui.loaders.ai-shimmer` placeholder skeleton; when the parent also carries the halo (§5.2) both show together.

## 8. Rules of engagement

- No new colors, sizes or radii beyond the tokens and the two button scales; the halo's 1px ring and 5s pace are fixed constants.
- `auto_awesome` stays reserved for AI (ViewConventionTest guards this).
- Tenancy gates at the shared source unchanged: `feedAiOn()`-style helpers, `tenantOff('<module>:ai')`, `ai.assistant.access`; the look of a feature never advertises AI where the switch is off.
- Implementation must mirror this doc exactly; when the design evolves, this doc changes first, then the code.

## 9. Limits — defaults and per-module values (2026-11-24)

This section is the ONLY place the numbers live; every other pattern doc points here instead of restating them.

- **Where they are set.** `config/ai.php` → `gateway` holds the defaults. `AiClient::setting($module, $key)` reads `ai.<module>.<key>` and falls back to `ai.gateway.<key>`; `HasDailyQuota::quotaCap()` and the cooldown / pause / retry reads use the same fallback, so a module that omits a key still gets the default and never a cap of 0. Each module file `config/ai/<module>.php` ALSO states the same values explicitly, so an override is a one-line edit; keys a module's code never reads are not defined.
- **Defaults (`ai.gateway`):** daily cap **5** per user per day, cooldown **3 s**, HTTP timeout **45 s**, connect timeout **10 s**, max output **16384** tokens (the largest, for the heavy generators), service-down pause (`down_for`) **30 s**, retry window (`retry_within`) **10 s**.

| Module (config file) | Quota counter on | Cap | Cooldown | Timeout | Max output | Text limits (unchanged) |
|---|---|---|---|---|---|---|
| Contact smart edit (`contact`) | `Message` | 5 | 3 s | 45 s | 2048 | message 2000, answer 2000 |
| Channel summaries (`channel`) | `Channel` | 5 | 3 s | 45 s | 4096 | question 300, 300 messages / 24000 characters, answer 3000 |
| Channel smart edit (`channel.enhance`) | `ChannelMessage` (`channel.enhance`) | 5 | 3 s | 45 s | 2048 | message 2000, answer 2000 |
| Feed suggest (`feed`) | `Feed` | 5 | 3 s | 45 s | 2048 | 3000 characters |
| Ticket writing assistant (`ticket`) | `Ticket` | 5 | 3 s | 45 s | 2048 | input 1500 |
| Correspondence draft (`correspondence`) | `Correspondence` | 5 | 3 s | 45 s | 16384 | uploads |
| AI report (`report`) | `AiReport` | 5 | 3 s | 45 s | 16384 | uploads |

- **Cooldown** is per user and starts only after a call that reached the gateway; a second try inside it returns the soft `busy` result without calling the model. **`down_for`** is the circuit breaker after a 5xx / 429 / connection failure.
- **Max output** must leave room for the model's hidden thinking tokens, so it is sized well above the visible answer (the 900-token cut-off story is in `channelPattern.md` §27, not repeated here).
- **Long generators.** The HTTP call of correspondence and AI report is capped at 45 s while their queue jobs allow longer (`app/Jobs/jobsPattern.md`); if real generations hit the cap, raise only that module's `timeout` in its own file.
- **Where each rule is explained:** the shared AI layer and the `<module>:ai` convention → `app/Services/AiReport/aiReportPattern.md`; the quota trait and its holders → `app/Models/modelPattern.md`; module flows → `contactPattern.md` §20, `channelPattern.md` §27–28, `thsPattern.md`, `correspondencePattern.md` §11, `reportPattern.md`; the meter look → §6 above.
