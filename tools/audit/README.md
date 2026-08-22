# Audit instruments — recovered 2026-08-23

⚠️ **NOT COMMITTED AND NOT READ ARE THE SAME FAILURE.** These produced findings the roadmap now rests on, and
they lived only in a garbage-collected `/private/tmp` scratchpad. One line each, so the next reader does not
have to open them to know what they are for.

| tool | kind | what it is for |
|---|---|---|
| `reach.py` | ⚠️ **audit instrument** | **The reachability sweep.** Searches many creation shapes per entity across `app/` vs `seeders/tests`, and reports what only the seeder can create. ⚠️ Produced **P-6** (a tenant could not create a room type, unit or rate plan), **P-7** (per-property scoping unreachable), the statement-finalisation gap, and the **12 seeder-only entities**. ⚠️ Its output is a claim of ABSENCE, so it deliberately searches many call shapes and must be validated against a known-reachable entity before any negative is trusted — its first version missed `firstOrNew` and `new Model;` and would have produced two false "SEEDER-ONLY" findings. **Wanted again once the doc clusters are built, to re-check what became reachable.** ✅ Runs; path made portable. |

## Judged disposable, and why — named rather than silently dropped

⚠️ *"It survived by luck, not process"* applies to these equally, so the decision is recorded rather than
implied. **Nothing was deleted from the scratchpad**; this is only about what enters the repo.

| not committed | why |
|---|---|
| `g5table.py` (45 lines) | Formatted the Gate-5 audit table from a one-off workflow run. ⚠️ Hardcodes a path into `~/.claude/projects/…/subagents/workflows/wf_2bc859df…` — **an input that no longer exists**, so it could not run again even if kept. Its output IS the committed Gate-5 appendix. |
| `rewrite.py` · `rewrite_lisbon.py` (77 / 69 lines) | One-off rewrites of specific pages during Gate 3b. Superseded by the converter transforms; re-running either would fight `convert.py`. Their result is the committed Blade. |
| `convert.bak.py` (654 lines) | An older copy of the converter. ⚠️ Two copies of a converter is exactly the drift this project keeps closing (§N.8f) — and the live one is committed. |

⚠️ **The line drawn:** a tool that can produce a NEW finding is kept; a tool whose only output is already
committed is not.
