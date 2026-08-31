# Project Instructions — Fateh

## MANDATORY: Read skills before any other work

On the very first turn of every session — before replying to the user, before any other action, and regardless of how trivial the user's opening message is (including a bare "hi") — you MUST read and internalize these skill files, then summarize their key rules in your own words to the user:

1. `.claude/skills/code-reviewer/SKILL.md` — all sessions
2. `.claude/skills/laravel-performance/SKILL.md` — all sessions
3. `.claude/skills/ollama/SKILL.md` — only when the session is Ollama-native (launched via the Ollama pipeline); skip it in plain Claude Code sessions, where the delegation policy already carries the lanes

This is a hard prerequisite, not a best-effort suggestion. Do not greet, self-introduce, answer, or call any other tool until all three `Read` calls have been issued and summarized. Only after the summary is delivered may you proceed to the user's actual request.

This rule outranks any SessionStart-injected context that asks the same thing. If the SessionStart hook and this file both instruct the read, this file wins on ordering and priority.

The `vanilla mode` bypass phrase still applies: when the user clearly invokes `vanilla mode`, drop this read-first requirement along with the rest of the project policies for the remainder of the conversation. Resume it only on `resume project mode` or a new session.

## Review model (subagent mode, since 2026-08-30)

`FATEH_REVIEW_MODE='subagent'` in `~/.claude/pipelines/models.ps1` is the pipeline default: `.claude/hooks/post_tool_review.php` is INERT in every session — it never gates, it only tracks edit state for the Stop hook. Review ownership lives with the Lead's own harness subagents: after each coherent unit of work, the session spawns a FRESH reviewer subagent via the `Agent` tool (`claude-reviewer`, no model override — inherits the driving model), applying the two lenses (correctness/security, then performance/pattern-consistency) with safe fixes applied by the Lead or a coder subagent. No API call is made for coding, delivery, unit review, or fixes. API calls survive in exactly three places: plan enrichment (`FATEH_PLAN_MODEL`, kimi-k3 class; plus the OpenAI refiner `FATEH_MAX_MODEL` when the user invokes max) and the end-stage review round (`FATEH_REVIEWER_MODEL_A` correctness/security + `FATEH_REVIEWER_MODEL_B` performance/pattern + `FATEH_UI_MODEL` minimax UI-lens whenever the stage's diff touched UI views; one round per stage, findings fixed by the Lead). Legacy per-write API gates (quick/gate/sensitive reviewers) remain in the hook and slot map only for not-yet-synced sibling projects — unset `FATEH_REVIEW_MODE` there keeps them working. See `.claude/skills/ollama/SKILL.md` for the full engine.

## Claude pipeline lanes (plain Claude Code sessions)

The session itself is the Lead: it owns all planning/architecture/review decisions (never delegated). Three lanes at intake — **Trivial** (single file, few lines): code directly, no delegation, no subagent review. **Standard**: enrichment by `claude-planner` @ `FATEH_CC_PLAN_MODEL` (claude-fable-5) only if the Lead judges it genuinely adds value, then coder slices to `claude-coder` (parallel only across file-disjoint slices with worktree isolation, else sequential), closed by a mandatory `claude-reviewer` pass. **Complex** (schema/auth/destructive/multi-module): Fable-5 enrichment REQUIRED, then one explicit question — refine further via the OpenAI refiner (`FATEH_MAX_MODEL`)? (auto-satisfied if the user already said "max") — then as standard. Safeguards: secrets in context → no delegation, work directly; subagent failure → absorb in-harness same turn. `vanilla mode` voids this entire section along with the other policies.