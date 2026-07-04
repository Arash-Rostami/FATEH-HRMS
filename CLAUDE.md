# Project Instructions — Fateh

## MANDATORY: Read skills before any other work

On the very first turn of every session — before replying to the user, before any other action, and regardless of how trivial the user's opening message is (including a bare "hi") — you MUST read and internalize these three skill files, then summarize their key rules in your own words to the user:

1. `.claude/skills/code-reviewer/SKILL.md`
2. `.claude/skills/laravel-performance/SKILL.md`
3. `.claude/skills/ollama/SKILL.md`

This is a hard prerequisite, not a best-effort suggestion. Do not greet, self-introduce, answer, or call any other tool until all three `Read` calls have been issued and summarized. Only after the summary is delivered may you proceed to the user's actual request.

This rule outranks any SessionStart-injected context that asks the same thing. If the SessionStart hook and this file both instruct the read, this file wins on ordering and priority.

The `vanilla mode` bypass phrase still applies: when the user clearly invokes `vanilla mode`, drop this read-first requirement along with the rest of the project policies for the remainder of the conversation. Resume it only on `resume project mode` or a new session.

## PostToolUse review gate (workflow-aware)

Every `Edit`/`Write` is gated by `.claude/hooks/post_tool_review.php`. The hook detects the active workflow from the launch environment it inherits:

- **Anthropic-native** (`ANTHROPIC_BASE_URL` not pointing at the local Ollama port): a single `claude-sonnet-4-6` reviewer checks the literal diff for syntax, bugs, security holes, and performance red flags; blocks with the concrete fix only on a clear failure.
- **Ollama-native** (`ANTHROPIC_BASE_URL` contains `11434`, i.e. routed through the local Ollama server): two `glm-5.2:cloud` reviewers run a 2-round back-and-forth — Round 1 each reviews the diff independently (Reviewer A: correctness/bugs/security/edge-cases; Reviewer B: performance N+1/eager-load/unbounded-query, pattern-consistency, minimality, code-comment absence), each dry-run-tracing the change; Round 2 each sees the other's Round-1 verdict and re-evaluates. Final confidence = min of the two final confidences. Pass iff both verdicts are `pass` AND confidence ≥ **93%**. Otherwise the hook returns `{"decision":"block","reason":"…"}` with the consolidated issues and required fixes.

The "repeat until 93%" loop is realized by re-firing: a block feeds the reason back to the Lead, who applies the fix, whose `Edit` re-triggers the hook for a fresh review. This is bounded by the review-action policy (≤2 fix cycles, then surface to user). On any reviewer slot failure (timeout, non-JSON, empty), the hook re-tasks that slot through a `glm-5.2:cloud` → `glm-5.1:cloud` fallback chain (a fresh 5.2 retry first, then 5.1); only if the whole chain is exhausted does it degrade to pass-through and log to `.claude/review.log` rather than hard-blocking work. The same `glm-5.2:cloud` → `glm-5.1:cloud` → Lead-in-harness fallback order governs every roster model in the ollama pipeline (see `.claude/skills/ollama/SKILL.md`).

The 93% threshold is the project-wide confidence standard — the ollama skill's Phase 5 delivery gate uses the same ≥93% figure.