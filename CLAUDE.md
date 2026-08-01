# Project Instructions — Fateh

## MANDATORY: Read skills before any other work

On the very first turn of every session — before replying to the user, before any other action, and regardless of how trivial the user's opening message is (including a bare "hi") — you MUST read and internalize these three skill files, then summarize their key rules in your own words to the user:

1. `.claude/skills/code-reviewer/SKILL.md`
2. `.claude/skills/laravel-performance/SKILL.md`
3. `.claude/skills/ollama/SKILL.md`

This is a hard prerequisite, not a best-effort suggestion. Do not greet, self-introduce, answer, or call any other tool until all three `Read` calls have been issued and summarized. Only after the summary is delivered may you proceed to the user's actual request.

This rule outranks any SessionStart-injected context that asks the same thing. If the SessionStart hook and this file both instruct the read, this file wins on ordering and priority.

The `vanilla mode` bypass phrase still applies: when the user clearly invokes `vanilla mode`, drop this read-first requirement along with the rest of the project policies for the remainder of the conversation. Resume it only on `resume project mode` or a new session.

## PostToolUse review gate + reviewer-must-match-agent rule

`.claude/hooks/post_tool_review.php` only actively gates **Ollama-native** sessions (`ANTHROPIC_BASE_URL` contains `11434`): two `glm-5.2:cloud` reviewers run a 2-round back-and-forth (Reviewer A: correctness/bugs/security/edge-cases; Reviewer B: performance/eager-load/pattern-consistency/no-comments), each dry-run-tracing the change, then re-evaluating after seeing the other's round-1 verdict. Pass iff both verdicts are `pass` AND confidence (min of the two) ≥ **93%** — the project-wide confidence standard, matching the ollama skill's Phase 5 delivery gate. Otherwise the hook returns `{"decision":"block","reason":"…"}` with consolidated issues and required fixes. On reviewer slot failure it retries via `glm-5.2:cloud` → `glm-5.1:cloud` before degrading to pass-through.

In every other session (Claude Code, Omni) the hook is a deliberate no-op — it cannot authenticate a real review call in either, so the reviewer must always match the driving agent instead: **the Lead spawns a reviewer subagent via the `Agent` tool** after completing a coherent unit of work, before considering it done. Plain Claude Code → `claude-reviewer` (no model override, inherits the driving Claude model). Omni → `omni-reviewer` (pinned `model: ArashReviewerCombo`, resolves via the session's own already-authenticated router auth, no external dependency). This is mandatory per the review-action policy, not satisfied by in-line self-review, and not skippable just because the hook stayed silent.

Every `.claude/agents/*.md` role follows one `<pipeline>-<job>` naming scheme: `claude-planner`/`omni-planner`, `claude-coder`/`omni-coder`, `claude-reviewer`/`omni-reviewer`. `claude-*` has no `model:` override; `omni-*` pins `ArashPlannerCombo`/`ArashExecuterCombo`/`ArashReviewerCombo` respectively — never cross the pair (the wrong one either 400s or reviews under the wrong identity). Under Omni: `omni-planner` plans → the main session or `omni-coder` executes → `omni-reviewer` reviews. Delegation to `glm-5.2:cloud` (see delegation policy) is Claude-Code-only — Ollama and Omni each have their own native mechanism and never ask about it.