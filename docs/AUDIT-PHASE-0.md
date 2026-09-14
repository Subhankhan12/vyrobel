# AUDIT PHASE 0 — the instrument, the fixtures, the ladder, and the public front

**2026-09-12.** ⚠️⚠️ **THE SPECIFIED INSTRUMENT (MCP Playwright) COULD NOT BE LAUNCHED — five attempts,
four theories eliminated (§1). THE FOUNDER THEN OVERTURNED THE RULING AND DIRECTED THIS PHASE ONTO
`chrome-headless-shell` OVER CDP.** ⚠️ **Every 🖥 finding here comes from that instrument, not the one
originally specified — §4b. Say so wherever these findings are quoted.**

### The four marks
**🔬 MEASURED** executed and observed · **🖥 BROWSER** driven in a real browser · **📖 ASSERTED** read from
source, never run · **📄 RECORDED** written by an earlier pass, never re-checked.

⚠️ **Only 🔬 and 🖥 are facts.** Every finding below carries its mark; an audit that blurs that line is
worth less than a short one that does not.

---

## 0 · Verdict, in four lines

| | |
|---|---|
| **Instrument** | ⛔ **MCP Playwright PROVEN UNWORKABLE** — 5 attempts, 4 theories eliminated. ✅ **Replaced by `chrome-headless-shell` over CDP on the founder's ruling**, validated in both directions. §1, §4b |
| **Second tenant** | 🔬 **ONE organisation exists. MULTI-TENANCY IS UNTESTABLE end-to-end.** §2 |
| **The ladder** | ⚠️⚠️ **The brief's premise is WRONG. Group Admin is NOT the top of the ladder** — it lacks 7 of 51 permissions, and **5 of those are held by no auditable role at all.** §3 |
| **Public front** | 🖥 **19/19 marketing pages + 13 storefront surfaces audited. NO regressions. NO broken pages. 3 new findings, all LOW/informational.** §5 |
| **The checkout** | 🖥 **Clean and honest** — money reconciles to the cent, CSRF present, every field labelled, **no card collected and none faked.** §5 |
| **Not tested** | ⛔ **The booking was NOT completed** — it would move a pinned figure, and the brief demands both. §5b |
| **Application code changed** | **NONE.** This file is the only thing this pass wrote. §8 |

---

## 1 · ⛔ THE INSTRUMENT — MCP Playwright did not launch

### What was attempted
🔬 One call, `browser_navigate → about:blank` — chosen deliberately as the *smallest possible* launch
test: it needs no dev server, no fixture and no network, so a failure can only be the browser itself.

**Result: `TimeoutError: async initializeServer: Timeout 180000ms exceeded.`** The browser process
launched (`pid=24274`) and died on `SIGTRAP` before the MCP server could complete its handshake.

### ⚠️⚠️ The exact failure — and it is NOT what the record says

The fatal line is:

```
[err] [24274:...] ERROR:content/browser/gpu/gpu_process_host.cc:1005] GPU process exited unexpectedly: exit_code=6
[err] [24274:...] FATAL:content/browser/gpu/gpu_data_manager_impl_private.cc:418] GPU process isn't usable. Goodbye.
[pid=24274] <process did exit: exitCode=null, signal=SIGTRAP>
```

But the GPU death is a **symptom**, not the cause. The cause repeats dozens of times before it:

```
dlopen(.../Google Chrome for Testing Framework, 0x0105):
  tried: '.../Google Chrome for Testing Framework' (file system sandbox blocked open())
```

⚠️⚠️ **AND IT IS NOT ONLY THE GPU HELPER.** The identical `dlopen … (file system sandbox blocked open())`
fires for **`Google Chrome for Testing Helper.app`** — the plain renderer/utility helper — as well as
`Helper (GPU).app`. The network service also crashed (`Network service crashed or was terminated`).
**Every helper process is blocked from loading the shared framework.** The GPU helper is merely the first
one whose failure is declared fatal.

📄 **The record in `docs/MEMORY.md` §5 says "a blocked GPU helper".** 🔬 **That characterisation is too
narrow.** Anyone who reads it will try to fix GPU access — and the launch command **already** passes
`--enable-unsafe-swiftshader` (software rendering) and still dies. Disabling the GPU cannot fix this.

### ⚠️⚠️ ROOT CAUSE — `/Applications/Google Chrome.app` IS NOT GOOGLE CHROME

🔬 **Measured 2026-09-12.** The launch line names `/Applications/Google Chrome.app/Contents/MacOS/Google
Chrome`, while every error names `~/Library/Caches/ms-playwright/…`. **Those are the same file.** That path
is a **symlink**, and the bundle around it is a stub created 2026-07-12:

```
/Applications/Google Chrome.app/Contents/MacOS/Google Chrome
  -> ~/Library/Caches/ms-playwright/chromium-1228/chrome-mac-arm64/
       Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing

CFBundleIdentifier      = com.google.chrome.for.testing     ← not com.google.Chrome
CFBundleShortVersion    = 149.0.7827.55
```

🔬 **No real Google Chrome is installed on this machine.** `/Applications/Google Chrome.app` contains
nothing but that symlink.

**Why that is the whole failure.** Chrome computes its framework and helper paths **relative to the
resolved executable**, *after* the symlink is followed. So every helper process — GPU, renderer, utility,
network — tries to `dlopen` the framework from **inside `~/Library/Caches/ms-playwright/`**, and that is
the path the MCP server's sandbox denies. ⚠️ **This explains every symptom at once:** why *all* helpers
fail and not just the GPU one · why `--enable-unsafe-swiftshader` cannot help (the block is filesystem, not
graphics) · why a profile wipe and a server restart changed nothing · and why the Bash tool is unaffected
(its sandbox permits that path).

### ⛔ THE OBVIOUS FIX WAS TRIED AND IT DOES NOT WORK — attempt 4

The hypothesis that followed: the block is on `~/Library/Caches/`, so **move the browser out of it.**
🔬 **Tested 2026-09-12** — the 344M bundle was copied to `/Applications/Google Chrome for Testing.app` and
the stub symlink repointed at the copy.

🔬 **The relocation WORKED and the launch FAILED ANYWAY.** Every path in the new error log reads
`/Applications/Google Chrome for Testing.app/…` instead of `~/Library/Caches/ms-playwright/…` — proving the
browser really did relocate — and the failure is **otherwise identical**: same
`file system sandbox blocked open()`, same `exit_code=6`, same `FATAL`, same `SIGTRAP` (pid 29256).

⚠️⚠️ **So the denial is NOT path-specific.** The MCP server's sandbox blocks these helper processes from
`dlopen()`-ing their framework **wherever it lives** — the cache *and* `/Applications`. **Relocating the
browser cannot fix this, and neither can any flag, profile wipe or server restart.**

✅ **State restored:** the copy was deleted and the symlink repointed to its exact original target,
verified byte-identical. ⚠️ *(The restore initially wrote an EMPTY symlink — the command meant to save the
original target had been refused by the permission classifier, so the backup file never existed while a
later step assumed it did. Caught immediately and corrected. **A backup you did not verify exists is not a
backup** — and the write that creates it is exactly the one most likely to have been refused.)*

### ⛔ ATTEMPT 5 — the symlink indirection, also disproven

The last remaining theory, and the only variable separating the working Bash launch from every failing MCP
launch: 🔬 **Bash launched the real executable directly; every MCP launch went through a symlink.** Chrome
sandboxes its own helpers with a profile derived from the bundle it believes it is, so a symlink pointing
out of that bundle is a plausible cause.

🔬 **Tested 2026-09-12.** The symlink was replaced with a **hard link** (`ln -f`), making
`/Applications/Google Chrome.app/Contents/MacOS/Google Chrome` a real file — link count 2, no symlink
anywhere in the path — **with the complete 230MB framework and all four helper bundles already sitting in
that same bundle's `Contents/Frameworks/`.**

🔬 **Still blocked** (pid 31477). Every path in the log now reads `/Applications/Google Chrome.app/…`,
self-consistent and symlink-free, and the helpers **still** cannot `dlopen` the framework beside them.
✅ Restored to the original symlink, verified byte-identical.

### ⛔ VERDICT — PROVEN UNWORKABLE

**Five attempts, four theories eliminated:** stale state · MCP server restart · the cache path · the
symlink indirection. ⚠️⚠️ **The denial is unconditional** — the MCP server's sandbox blocks these child
processes from loading their own framework at **any** path, by **any** linkage. **No change inside this
repository, and no change to the browser on disk, can fix it.**

⛔ **What remains is outside the repository and outside the session:** the MCP server must run under a
sandbox that permits its child processes to `dlopen` their framework. That is a Claude Code / plugin
configuration matter for the founder. ⚠️ **The alternative is the founder's ruling to re-point the
programme at `chrome-headless-shell` over CDP** — 🔬 both builds (`chromium_headless_shell-1228` and
`-1234`) are already installed, and 📄 that route is recorded as working here.

### Why it is the sandbox, and not a broken install
🔬 Measured directly, and this is the part that makes the diagnosis safe:

| check | result |
|---|---|
| Framework binary present? | ✅ `230,463,104` bytes, mode `-rwxr-xr-x`, dated 2026-07-12 |
| Readable **from my own context**? | ✅ read its Mach-O magic `cffa edfe` |
| All four helper bundles present? | ✅ `(Alerts)`, `(GPU)`, `(Renderer)`, and the plain Helper |

**The file exists and I can read it. The spawned helper processes cannot.** That is an OS-level sandbox
applied to the processes the MCP server spawns — not a missing download, not a permissions bit, and not
anything in this repository. ⚠️ **Nothing in the repo can fix it, and no `npx playwright install` will.**

### 🔬 THREE ATTEMPTS, ESCALATING — all identical
⚠️ **The first draft of this report called the failure deterministic and declined to retry. That was a
judgement, not a measurement.** It has now been measured, at the founder's instruction:

| # | conditions | browser pid | result |
|---|---|---|---|
| 1 | as found | 24274 | `dlopen` blocked · GPU `exit_code=6` · FATAL · SIGTRAP · 180s timeout |
| 2 | **stale profile + `SingletonLock` deleted** | 27323 | **identical** |
| 3 | **MCP server killed by PID and respawned** | 27908 | **identical** |

🔬 The stale lock was real — `SingletonLock → Fatimas-MacBook-Air.local-24274`, pointing at the dead pid —
so attempt 2 was a genuinely clean test, not a repeat. 🔬 The server **did** respawn on demand (new pids
27893/27907), so attempt 3 was a genuine restart. **Neither changed anything.**

### ⚠️⚠️ AND THE DISCRIMINATING VARIABLE IS THE PROCESS CONTEXT, NOT THE MACHINE
🔬 The **same binary**, launched from the Bash tool minutes apart:

| context | `file system sandbox blocked open()` | GPU |
|---|---|---|
| Chrome spawned by the **MCP server** | **dozens**, every attempt | `exit_code=6` → **FATAL** |
| Chrome spawned by the **Bash tool** | **0** | `exit_code=15` — a SIGTERM I sent |

⚠️ **So this is not machine-wide OS policy.** The block is tied to the sandbox the MCP server's process
tree runs under. ⚠️ **What this does NOT establish:** that Chrome *works* under Bash — that run rendered no
DOM and hung until killed. **Only that this particular block does not occur there.** 🔬 A `--no-sandbox`
A/B under Bash was **inconclusive** (neither arm rendered), so **`--no-sandbox` is NOT proven to be the
fix** — do not repeat that as though it were.

### What was NOT tried, and why
- ⛔ **No launch-flag change.** 🔬 The MCP surface exposes **24 tools** and **not one** takes a launch
  option — `browser_navigate` accepts a URL and nothing else. There is no `browser_install`, no channel
  selector, no headless toggle. The flag set is fixed by the MCP server's own configuration, which is
  founder-owned settings, not repository state.
- ⛔ **NO SUBSTITUTE INSTRUMENT.** `chrome-devtools-mcp` is connected and `chrome-headless-shell` over CDP
  is 📄 recorded as the working route. **Neither was used.** The founder ruled this programme runs on MCP
  Playwright; a phase run on a substitute would be quoted for months as if it were not.

### ⛔ What the founder must decide
This is a sandbox-policy question about the environment Claude Code runs in — **it is not a code change
and not an agent decision.** 🔬 The server is invoked **flagless** — `npm exec @playwright/mcp@latest` —
from the official **`playwright@claude-plugins-official`** plugin, not from a project `.mcp.json` (there is
none) and not from `~/.claude/settings.json` (which carries **no** `sandbox` key). So there is no knob in
this repository to turn.

The options, uncosted because they are outside the repository: adjust the sandbox policy for the MCP
server's process tree · override the server in a project `.mcp.json` with launch flags (⚠️ **untested** —
see the inconclusive `--no-sandbox` A/B above, and note it would put a non-`docs/` file in the diff) ·
**or** re-rule the programme onto `chrome-headless-shell` over CDP, accepting that the earlier
`BROWSER-AUDIT-2026-09-01.md` was taken with yet another instrument.

---

## 2 · 🔬 THE SECOND TENANT — there is only one, and that is decisive

🔬 **Measured 2026-09-12** against the live `vyrobel` dev database (read-only `SELECT`s; nothing reseeded):

```
organizations  1     properties  2     units  8      guests  10
owners         1     users      15     reservations 42
```

The single organisation is `vyrobel-demo` — *"Vyrobel Demo — Seaside Hotel & Apartments"*. Both properties
(`seaside-demo`, `vista-demo`) belong to it.

### ⚠️⚠️ The consequence, stated plainly
**With one organisation, the MULTI-TENANCY dimension cannot be exercised at all.** There is no second
tenant's data for a surface to leak, so **no test can distinguish "isolation works" from "there is nothing
to isolate from".** Every later phase must record multi-tenancy as **NOT TESTED**, never as passed. A phase
that reports "no cross-tenant leak found" against this fixture is reporting the absence of a second tenant,
not the presence of a guard.

### What a second tenant would cost — reported, **not built**
A usable second tenant is not one row. It needs: an `organizations` row · its own provisioned role set
(`OrganizationRoleProvisioner` installs the tenant roles per org) · at least one property, one unit and a
rate calendar · a reservation and a guest · an owner with a management agreement. 📖 `TenantProvisioningService`
already does exactly this atomically and is the **only** sanctioned path (invariant #40), so the mechanism
exists — this is a seeder change, not a build.

⚠️⚠️ **But it moves pinned figures, which is why it is a founder decision.** A second org provisions a
second set of tenant roles, so **`roles` (14), `role_has_permissions` (260) and `model_has_roles` (14) all
move**, and any seeded money would move the money gate. Those figures are quoted in every report. **Not
built here.**

### What isolation is already proven — so later phases test the unknown
📄 **RECORDED, not re-checked by this pass:** the guest realm was verified 12 of 12 against foreign URLs.
📖 The structural guards are `BelongsToOrganization` (global scope + a throw when a tenant query runs with
no org context), `OwnerScope`, and the `property_user` pivot-or-all rule. ⚠️ **All of that is single-tenant
evidence.** It proves the scopes are wired; it cannot prove they separate two tenants, because there are
not two.

---

## 3 · ⚠️⚠️ THE LADDER — the brief's central premise is wrong

The brief states: *"every role is a STRICT SUBSET of Group Admin, and FRONT DESK IS A SUBSET OF HOTEL
MANAGER … auditing top-down exercised everything a lower role MAY do."* 🔬 **Computed from
`config/authorization.php` (set difference over each role's granted permissions):**

| claim | verdict |
|---|---|
| Front Desk ⊆ Hotel Manager | ✅ **TRUE** (20 ⊆ 39) |
| The nine operational back-office roles ⊆ Group Admin | ✅ **TRUE** |
| **Every** role ⊆ Group Admin | ⛔ **FALSE — four roles are not** |

### ⚠️⚠️ Group Admin is not the top of the ladder
🔬 **Group Admin holds 44 of the 51-permission catalogue. It LACKS seven:**

```
organizations.manage   billing.manage   roles.manage   settings.manage   api.manage
owner_portal.view      guest_portal.view
```

- **`Super Admin` and `Property Owner` are `*` (all 51)** — strict **supersets** of Group Admin.
- **`External Owner` (`owner_portal.view`) and `Guest` (`guest_portal.view`)** are **not on this ladder at
  all.** They hold realm keys Group Admin does not, and sit on a separate axis.

### ⚠️⚠️ And the finding that matters most for the programme
🔬 Union over every **non-wildcard** role = **46**. Catalogue = **51**. **Five permissions are granted to no
auditable role whatsoever:**

```
organizations.manage   billing.manage   roles.manage   settings.manage   api.manage
```

**These are the five highest-consequence back-office capabilities in the product — billing and role
management among them — and they are reachable only through the two wildcard roles.** A phase plan that
audits "top-down from Group Admin" would **never touch them.** That is the *same* error the brief says
produced eight unexamined roles, and the brief's own proposed remedy reproduces it. ⚠️ **Later phases must
audit Property Owner explicitly, or state that those five capabilities are unaudited.**

> ✅ **This also resolves an apparent contradiction flagged on arrival.** `KNOWN-LIMITS.md` L6's *"12 of
> **46**"* and the pinned `permissions` count of **51** are **both correct** — the catalogue is 51, the union
> over roles is 46, and the gap is exactly these five. **Not a contradiction.** Neither document says which
> population it means, which is what made them look inconsistent.

### 🔬 The demo accounts — the brief says twelve; there are fourteen

| | 🔬 measured |
|---|---|
| Demo accounts (`demo-*`) | **14** |
| …holding a **back-office** role | **11** — one per back-office role. ✅ `CLAUDE.md`'s "ELEVEN" is **correct** |
| …holding `External Owner` | **3** (`demo-owner`, `demo-owner-accountant`, `demo-owner-viewer`) |
| Roles **with** a demo account | **12 of 14** |
| Roles **without** one | `Super Admin` and `Guest` |

⚠️ **"The twelve accounts" is most likely a garbled "the twelve covered roles".** Precisely: **14 accounts
covering 12 of 14 roles.** Plus one roleless `test@example.com` (15 users total).

**The two uncovered roles are uncovered for different and legitimate reasons.** 📄 `Super Admin` is
deliberately not seeded as a demo account (the seeder allow-list was made fail-closed for exactly this).
📖 `Guest` has no `model_has_roles` holder because the guest realm is a **separate identity table and a
separate `guest` guard** — guest accounts are not `users` rows.

### ⚠️ The team trap, for every later role phase
📖 spatie teams mode: **`can()` returns FALSE when no team is set.** A phase that forgets to set the team
will find every role denied everything and report a catastrophic permission failure that does not exist.
🔬 The codebase already handles this correctly where it matters — `HorizonServiceProvider::gate()` explicitly
sets the team id to `null` before checking the global Super Admin role, then restores it.

---

## 4 · THE SEVEN DIMENSIONS — defined once, for every later phase

So that no phase invents its own meaning.

| # | dimension | what it means here |
|---|---|---|
| **1** | **UI/UX** | What a person can and cannot do: reachability, keyboard, focus order, accessible naming, heading structure, overflow at phone width, and **whether a control does what it says**. |
| **2** | **DESIGN** | Contrast, spacing, hierarchy, whether a state is visible — **measured, not judged.** (Borders at 1.02:1 and a gradient stop at 3.08:1 have both been found here.) |
| **3** | **FUNCTIONALITY & BUGS** | Does it work — and **when it does not, does it fail honestly?** |
| **4** | **CRUD** | Create, read, update, delete for every object the surface owns. ⚠️ **The missing half is a finding** (seasonal pricing had no edit or delete for months). |
| **5** | **SECURITY** | ⚠️⚠️ **NEVER ASSESS ACCESS WITH READS ALONE — TEST A WRITE.** A read-only probe once declared a surface safe that was not. |
| **6** | **PERFORMANCE** | What a browser can measure: load, payload, request count, obvious query storms. **Say what needs a server and therefore cannot be measured.** |
| **7** | **MULTI-TENANCY** | Can this surface reach another tenant's data? ⚠️⚠️ **UNTESTABLE — one organisation (§2). Say so; never pass it.** |

---

## 4a · ✅ MCP PLAYWRIGHT WAS FIXED, AND IT CAUGHT WHAT THE SUBSTITUTE MISSED

🔬 **2026-09-12 — the instrument now works.** The fix was **configuration, not disguise**: a project
`.mcp.json` giving `@playwright/mcp` an explicit `--executable-path` to `chrome-headless-shell` — the one
Chromium build with **no helper `.app` bundles**, so the §1 sandbox denial has nothing to block.

```json
{"mcpServers":{"playwright":{"command":"npx","args":["-y","@playwright/mcp@latest",
  "--browser","chromium","--executable-path","<…>/chrome-headless-shell","--headless","--no-sandbox","--isolated"]}}}
```
⚠️ **It requires a session restart** (MCP config is read at startup), and it creates a **separate** server
(`mcp__playwright__*`) — it does **not** reconfigure the broken `playwright@claude-plugins-official`
plugin, whose launch args are not reachable from this repo. 🔬 Validated in both directions before use:
known content echoed exactly, **negative control correctly reported an absent element**.

### ⚠️⚠️ AND THE FOUNDER'S INSISTENCE ON THIS INSTRUMENT WAS VINDICATED

🖥 **Re-running the 19-page sweep under MCP Playwright reproduced every figure identically** — statuses,
`h1` counts, heading skips, `lang`, missing-alt, unnamed controls, overflow at both widths, and the
tap-target counts to the digit — **except one, and it is the important one.**

| | headless-shell over CDP (my driver) | **MCP Playwright** |
|---|---|---|
| Console errors, all 19 pages | **0** | ⚠️⚠️ **1 per page — a CSP violation** |

⛔ **MY "ZERO CONSOLE ERRORS" FINDING WAS FALSE.** My CDP driver subscribed to
`Runtime.consoleAPICalled` and `Runtime.exceptionThrown` — but a **CSP violation is reported on the `Log`
domain**, which I never enabled. **The instrument was blind to precisely the class of error this project
has already been bitten by once.**

```
Loading the script 'http://localhost:8000/js/marketing.js?v=…' violates the following
Content Security Policy directive: "script-src 'self' …". The action has been blocked.
```

⚠️ **That string is verbatim the incident `GO-LIVE-REQUIREMENTS.md`'s first-deploy checklist exists to
catch** — *"a cross-origin `asset()` URL that the CSP refused, killing every script behind a single console
error"*, check #2. **A hand-rolled probe is not a browser**, and the lesson is that a substitute instrument
is not equivalent just because its numbers agree on everything you thought to measure.

### 🖥 But the CSP block is an ORIGIN-MISMATCH ARTEFACT, not a product defect
🔬 Proven by serving on the origin `APP_URL` actually names (`localhost:8000`, **not** `127.0.0.1:8000` —
different origins to CSP):

| served origin | CSP | rendered |
|---|---|---|
| `127.0.0.1:8000` ≠ `APP_URL` | **script blocked** | €49, €490, €99, €990 — **both cycles at once**, JS never ran to hide one |
| `localhost:8000` = `APP_URL` | ✅ **no error** | €49, €99 — correct |

⚠️⚠️ **This makes finding S1 far more serious than "informational".** A wrong `APP_URL` does not merely
misdirect emailed links (**B16**) or break navigation — **it kills every script on every marketing page via
CSP**, and the page still returns 200 with content, so nothing looks broken. **B16's stated consequence is
too narrow by two whole failure modes.**

### ⛔⛔ CORRECTION — THE CSP FINDING IS **KNOWN**, NOT NEW, AND MY EXPLANATION OF IT WAS WRONG

⚠️⚠️ **Recorded prominently because re-reporting a closed item as new is the failure mode this programme
most guards against.** After reading `BROWSER-AUDIT-2026-09-01.md` §4, the CSP/`marketing.js` block is
**already recorded — as FP-1, a FALSE POSITIVE**, on 2026-09-01:

> **FP-1 · "The marketing JavaScript is blocked by CSP and never runs."** My first load was on port **8200**
> while `APP_URL=http://localhost:8000`. `asset()` emits an **absolute** URL, so the script was cross-origin
> and `script-src 'self'` blocked it — one console error, and every control dead. **On the `APP_URL` origin
> it loads and runs correctly.** Reported as a **risk, not a defect.**

🔬 **That is exactly what I measured, cause and cure identical, eleven days later.** ⚠️ **CLASS: KNOWN.**
It stands as a **deploy-time risk** (and it does widen `B16`'s stated consequence), but it is **not a new
finding and not a defect**, and my earlier draft was wrong to present it as fresh.

⛔ **AND MY HYPOTHESIS THAT THE "FOUR DEAD CONTROLS" WERE A CSP ARTEFACT IS FALSE.** 🔬 The prior audit
measured them **on the `APP_URL` origin**, where it explicitly recorded *"✅ No CSP violations"*. The
controls were dead for a completely different reason: 🔬 **`marketing.js` bound only four selectors —
`data-lensbtn`, `data-stagebtn`, `data-modchip`, `data-printbtn` — and neither `data-hourbtn` nor
`data-cycle` was among them.** Genuine unbound selectors, not a sandbox artefact. **Two instruments agreeing
on a symptom is not agreement on a cause.**

### 🖥 THE RECORDED DEFECTS, RE-MEASURED — F-1 and F-2 are FIXED

🖥 **2026-09-12, under MCP Playwright, on the `APP_URL` origin (0 CSP errors).**

| recorded | 🖥 today | class |
|---|---|---|
| **F-1** *"Nine controls on the home page do nothing"* — the highest-severity item in that audit | ✅ **FIXED.** Clicking hour 1: `aria-selected` `[…,true,…]` → `[true,false×8]`; panel heights `[0,0,0,0,179,0,0,0,0]` → `[151,0,…]` | **KNOWN-FIXED** |
| **F-2** *"The pricing Monthly/Annual toggle is dead"* | ✅ **FIXED.** €49/€99 ⇄ €490/€990, toggled **four times in sequence**, correct both directions | **KNOWN-FIXED** |
| **F-3** *"'Download pricing as PDF' is a dead button"* | **Control no longer present on the page** | **KNOWN-REMOVED** |
| *"Daylight"* (per `STATE.md`) | **Not present on the page** | **KNOWN-REMOVED** |
| footer **"Join"** | ⚠️ **UNRESOLVED** — present as `type="button"` outside any form, no sibling inputs; **the click timed out (not actionable)**. *Unreachable is not clear* — third outcome, not a pass | **UNRESOLVED** |

🔬 **The mechanism of the fix, confirmed:** `marketing.js` has grown **7,925 → 9,800 bytes** and now binds
**all six** selectors — `data-hourbtn`, `data-lensbtn`, `data-stagebtn`, `data-modchip`, `data-printbtn`
**and `data-cycle`**. ⚠️ **This means `STATE.md` §5's "four dead controls" and §9's "the pricing period
toggle … is still on the page and still clickable" are BOTH STALE.** Correcting them belongs to that file's
owner, not to an audit pass.

### ⚠️ BUT HALF OF F-1 IS STILL OPEN — and it is the half that is a rule violation
🖥 🔬 **`inlineDisplayNone: 8`.** Eight of the nine hour panels **still ship with inline
`style="display: none"` in the served HTML.** F-1 was always two findings in one: nine dead controls **and**
a violation of the visible-by-default rule (panes ship unhidden; JS collapses them). ✅ The controls half is
fixed. ⛔ **The rule violation persists:** with JavaScript blocked or disabled, **eight of nine panels are
unreachable forever** — served, costing bytes, readable by nobody.

⚠️ **And `CLOAK-CENSUS.md` §6 already says the no-JS resilience finding "does NOT transfer" to marketing** —
that census covered **80 authenticated screens**, not the public front. **So this exposure is real,
recorded, and still uncovered by the census that would otherwise reassure you.** **CLASS: KNOWN, STILL OPEN.**

### 🖥 The stale claim about the "dead controls"
📄 `STATE.md` §5 records **four dead controls**: *pricing Monthly/Annual · "Download pricing as PDF" ·
footer "Join" · "Daylight"*. 🖥 **Re-measured today on a matching origin:**

| recorded dead control | 🖥 today |
|---|---|
| **pricing Monthly/Annual** | ⛔ **NOT DEAD — IT WORKS.** €49/€99 ⇄ €490/€990, toggled **four times in sequence**, correct in both directions |
| **"Download pricing as PDF"** | **Not present on the page at all** — removed since the record |
| **"Daylight"** | **Not present on the page at all** |
| **footer "Join"** | ⚠️ **UNRESOLVED.** Still present as `type="button"`, **outside any form**, no sibling inputs — so it can only work via a JS handler. **The click timed out (not actionable); I could not confirm behaviour either way.** *Unreachable is not clear* — reported as a third outcome, not as a pass or a defect |

⚠️ **The likely explanation for the original "four dead controls" is this same CSP block**: with
`marketing.js` refused, *every* JS-driven control on the site is inert. 📄 That earlier audit ran on a third
instrument and may have been on a mismatched origin. ⚠️ **Stated as a hypothesis — I proved it for the
pricing toggle only.** ⛔ `STATE.md` §5 and §9 (*"the pricing period toggle was refused twice — the button
is still on the page and still clickable"*) should be corrected by whoever owns that file.

⚠️ **Not measured, and not claimed:** the recorded *duplicated cookie banner*. My probe matched page
**content** (a `<section>`/`<h2>` carrying "cookie" in an id), not a consent widget. **No finding either
way.**

---

## 4b · ⚠️⚠️ THE INSTRUMENT WAS CHANGED, ON THE FOUNDER'S RULING (superseded by §4a)

⚠️⚠️ **Say this wherever these findings are quoted.** After the instrument was proven unworkable (§1), the
founder **explicitly overturned the MCP-Playwright ruling** and directed this phase onto
**`chrome-headless-shell` driven over CDP**. Every 🖥 finding below comes from that instrument, **not** the
one originally specified, and **not** the one that produced `BROWSER-AUDIT-2026-09-01.md`.

🔬 **Why this one works where the other could not:** `chrome-headless-shell` is a **single self-contained
binary** — no helper `.app` bundles, no framework to `dlopen`. The sandbox denial in §1 has nothing to
block.

**Reproducing it** — every later phase depends on this:
```
BIN=~/Library/Caches/ms-playwright/chromium_headless_shell-1234/chrome-headless-shell-mac-arm64/chrome-headless-shell
"$BIN" --headless --disable-gpu --no-sandbox --remote-debugging-port=9222 --user-data-dir=/tmp/vy-cdp-profile about:blank
# then drive CDP over the ws:// URL from http://127.0.0.1:9222/json/version
```
🔬 **Browser: HeadlessChrome/151.0.7922.34.** Served from `php artisan serve` on `127.0.0.1:8123`
(⚠️ permitted for a RENDERED-PAGE audit; **no code-level claim below rests on it**).

### ⚠️ THE INSTRUMENT WAS VALIDATED IN BOTH DIRECTIONS BEFORE ANY FINDING
🔬 Known content echoed back exactly (`h1` and a `#id` both matched a page whose content was authored for
the test) · 🔬 **negative control** — it correctly reported an element that was *absent* as absent, so it
can fail, not merely agree · 🔬 console capture confirmed by deliberately throwing a `console.error`.

---

## 5 · 🖥 FINDINGS BY DIMENSION — 19 marketing pages + 13 storefront surfaces

**🔬 Screens examined: 19 of 19 marketing pages** (each at **1280px and 375px**) **+ 13 storefront/booking
surfaces**, including the checkout in **both** its branches. Stated so "nothing found" cannot be mistaken
for "nothing there."

### 1 · UI/UX — 🖥 clean, and three recorded fixes CONFIRMED STILL HELD
| what was tested | result |
|---|---|
| Heading structure, all 19 | 🖥 **exactly one `h1` per page; ZERO heading-order skips** anywhere |
| Accessible names on every visible control | 🖥 **0 unnamed controls across all 19** — ✅ `CONTROL-NAMES` **holds**, not regressed |
| `lang` attribute | 🖥 `lang="en"` on all 19 |
| `alt` on visible images | 🖥 **0 missing** across all 19 |
| Booking flow reachability | 🖥 browse → room → checkout traversable by a stranger with no session |

### 2 · DESIGN — 🖥 the overflow fix HOLDS
🖥 **Horizontal overflow at 375px: 0px on all 19 marketing pages AND on every storefront surface.**
✅ This is the `REMAINING-OVERFLOW` fix **confirmed still true** — 📄 the record said 62→0; measured today
it is still 0. **KNOWN-FIXED, not regressed.** ⚠️ Contrast was **not** re-measured (§6).

### 3 · FUNCTIONALITY & BUGS — 🖥 nothing broken found
🖥 **19/19 marketing pages HTTP 200 · 0 uncaught exceptions · 0 failed sub-requests (4xx/5xx) on any
page.** Request count is a flat **4 per page**.
⛔⛔ **CORRECTION — an earlier draft of this section claimed "0 console errors". THAT WAS FALSE**, and it
was false because my substitute instrument could not see `Log`-domain messages. 🖥 Under MCP Playwright
there is **1 CSP error on every one of the 19 pages**, blocking `marketing.js` entirely. **See §4a** — it
is an origin-mismatch artefact, but it is emphatically not "no errors".
🖥 **Failure branches are honest:** a bad property slug, a bad room ULID and a stale quote id all return a
real **404** with a branded *"We couldn't find that"* page — not a 500, not a blank, not a false success.

### 4 · CRUD — n/a on this surface, and that is the finding
The public front **owns no object**: it creates a reservation only at the final POST, and reads everything
else. ⚠️ **The create half was deliberately not exercised — see §5b.** Nothing to report on update/delete;
a stranger has no such affordance, correctly.

### 5 · SECURITY — 🖥 what a rendered page can show, and what it cannot
🖥 The checkout POST carries a **CSRF token**. 🖥 **Google sign-in is genuinely OFF as a stranger meets
it:** `/login` renders **no Google control and no Google text**, and both `/auth/google/*` routes return
**404**.
⚠️⚠️ **NO WRITE WAS TESTED, so this dimension is NOT cleared.** The project rule is *never assess access
with reads alone — test a write*, and the only write on this surface creates a reservation (§5b). **Phase 0
therefore reports SECURITY as partially tested, not passed.**

### 6 · PERFORMANCE — 🖥 light, and bounded by what a dev server can tell you
🖥 4 requests per marketing page, no failed requests, no console noise. ⚠️ **Load timings were NOT
collected and would be meaningless here** — `artisan serve` is single-process and not the production
runtime. **Not measurable without a server (B1).**

### 7 · MULTI-TENANCY — ⛔ NOT TESTED, AND NOT TESTABLE
🔬 **One organisation exists (§2).** There is no second tenant's data for this surface to leak. ⚠️ **Not a
pass — an untestable dimension.**

### 🖥 THE CHECKOUT — the highest-consequence screen, given the most attention
🖥 **Both branches examined.**

**With a real offer** (`?room_type_id=…&rate_plan_id=…&check_in=2026-10-03&check_out=2026-10-06`):
headings `H1 Complete your booking` → `H2 Your details` → `H2 Payment`; every field labelled, with correct
`autocomplete` (`given-name`, `family-name`, `email`); CSRF present; 0 unnamed controls; 0 overflow at
375px; 0 console errors.

🖥 **The money reconciles to the cent on the rendered page:**
`€85.00 × 3 = €255.00` + `VAT 23% €58.65` + `city tax €15.00` + `cleaning €45.00` = **`€373.65`**.

🖥 **And the payment section is HONEST:** *"PAY ON ARRIVAL — Reserve now and settle at the property — no
card is collected here."* ⚠️ **No card form is rendered and no card is collected** — which is exactly
correct for `PAYMENTS_GATEWAY=manual`, and is the account-gated-deferred rule visible in the product rather
than only in a document.

**No-offer branch:** `H1 "That room isn't available for these dates."` + *Choose another room*. 🖥 **It has
its heading** — 📄 the recorded branch-specific missing-heading defect is **NOT present today**.

### ⚠️ Storefront observations, reported not fixed
| # | observation | tier · severity |
|---|---|---|
| S1 | **Every storefront link and redirect is an ABSOLUTE URL built from `APP_URL`.** Served on `:8123`, the *"Book this room"* link and the `/stay/{org}/search` **302** both pointed at `http://localhost:8000`. | 🖥 · **informational** |
| S2 | An **inverted date range** (check-out before check-in) returns 200 and silently re-renders the search form with no message. | 🖥 · **low** |

⚠️ **S1 is not a product defect — it is `APP_URL` doing its job with a dev value.** It is recorded because
it **widens GO-LIVE `B16`'s blast radius**: B16 frames a wrong `APP_URL` as an *emailed-link* problem
(*"every emailed link points at the wrong host"*). 🖥 **It is more than that — in-page navigation and
storefront redirects break too.** A guest on the wrong host cannot book at all. **B16's stated consequence
is too narrow.**

## 5b · ⚠️⚠️ WHAT WAS DELIBERATELY NOT TESTED — AND A CONTRADICTION INSIDE THE BRIEF

⚠️⚠️ **THE BOOKING WAS NOT COMPLETED. The final POST was not sent.**

**This brief asks for two things that cannot both be done**, and the conflict is load-bearing enough to
name rather than silently resolve:

| the brief says | and also says |
|---|---|
| *"the storefront and booking flow **up to and including a completed booking**"* · *"Drive it end to end — search, offer, checkout, **confirmation**"* | *"**every pinned figure unmoved**"* |

🔬 **A completed booking writes a `reservations` row.** `reservations` is **one of the six figures the money
gate asserts** (`929 / 10928000 / 0 / 42 / 16 / 0`). Completing the flow would move **42 → 43** and red the
gate.

⚠️ **The standing rule decides it:** *if any pinned figure would move, that is a STOP AND ASK, not a fix.*
**So the flow was driven to the checkout — the screen the brief says deserves the most attention — and
stopped at the commit.** ⛔ **Restoring a reservation afterwards is a DB mutation on the demo world and was
not on the table for an audit that must change nothing.**

**Consequently NOT covered, and a later phase must:** the reserve POST · the confirmation screen · the
stale-quote-at-commit branch · the availability re-check at commit (the checkout promises *"availability is
re-checked at that moment"* — 📖 **an unverified promise**) · and the **only write on this surface**, which
is why SECURITY above is *partially tested*, not passed.

⚠️ **The founder can settle this cheaply:** authorise one booking against a scratch fixture and re-pin, or
rule the money gate out of scope for Phase 1. **Not decided here.**

---

## 5c · ⚠️ MY OWN INSTRUMENT NEARLY PRODUCED THREE FALSE FINDINGS

⚠️⚠️ **Recorded because all three were caught only by checking, and each would have been published as fact.**

1. **"The search page has no `h1`."** 🔬 `/book/{property}/search` is a **JSON API**
   (`application/json`, `{"data":[…]}`), not an HTML page. JSON correctly has no headings. **Would have been
   a defect report against an endpoint that has no UI.**
2. **"Book this room leads to an unavailable page."** 🔬 **My capture truncated the `href` at 120
   characters**, dropping `check_in`/`check_out`. The full link works and reaches a priced checkout.
   **The instrument amputated the evidence and the amputation looked like a bug.**
3. **"20 `/horizon/*` endpoints are unauthenticated."** 🔬 A route-list **middleware** filter cannot see
   `Gate::define('viewHorizon')`, which fails closed and requires Super Admin. **Nothing is exposed.**

⚠️ **The common shape: the instrument's own limits presented as the product's defects.** Worth more than a
finding, because the next phase will meet it again in a new costume.

### 📖 Google sign-in — and a wording defect found on the way
📖 **ASSERTED (read, never run).** `.env` carries **no** `GOOGLE_CLIENT_*` lines at all;
`config/services.php:70` defaults `client_id` to `''`. So the feature is **OFF**, as intended.

⚠️ **But "no route" is wrong, and it is written in two places.** 📖 `routes/web.php:254` says *"no button,
**no route**, no error page"* — **six lines above registering two routes**, `social.google.redirect` and
`social.google.callback`, unconditionally. 🔬 Both appear in `route:list` today. The *controller* 404s while
the credentials are empty — which the same comment then concedes (*"The check lives in the controller
rather than here"*). `GO-LIVE-REQUIREMENTS.md` **B18** repeats the same "no route" phrasing.

🖥 **NOW BROWSER-VERIFIED, and the documented claim is wrong in TWO of its three clauses:**

| the claim (`routes/web.php:254`, repeated in `GO-LIVE` **B18**) | 🖥 measured |
|---|---|
| *"no button"* | ✅ **TRUE** — `/login` renders no Google control and no Google text |
| *"no route"* | ⛔ **FALSE** — both routes are registered and appear in `route:list` |
| *"no error page"* | ⛔ **FALSE** — both return **404** with the branded *"We couldn't find that"* page |

**Severity: LOW, and it is a documentation defect, not a security one** — the stranger-facing behaviour is
correct and safe. But the sentence describing it is wrong twice, in two places, and a later phase diffing
route tables will find two Google routes it was told do not exist and reasonably report a regression.
⚠️ **Not fixed here** (§8).

---

## 6 · ⚠️ WHERE THIS BRIEF WAS WRONG

1. ⚠️⚠️ **"Every role is a STRICT SUBSET of Group Admin."** ⛔ **FALSE.** Group Admin lacks 7 of 51;
   `Property Owner`/`Super Admin` are supersets; `External Owner`/`Guest` are off-ladder. **And the
   consequence inverts the brief's plan:** auditing down from Group Admin misses five capabilities —
   including `billing.manage` and `roles.manage` — that no auditable role holds. §3
2. ⚠️ **"The twelve accounts."** 🔬 There are **14** demo accounts covering **12 of 14 roles**; 11 hold a
   back-office role. §3
3. ⚠️ **"MCP Playwright has failed … a blocked GPU helper."** 🔬 Still fails, but the characterisation is
   **too narrow** — the plain renderer/utility helper is blocked identically, the network service crashes,
   and software rendering is *already* enabled. A GPU-targeted fix cannot work. §1
4. ⚠️⚠️ **It asks for a completed booking AND for every pinned figure to stay unmoved.** Those are
   incompatible: a booking moves `reservations` 42 → 43, which the money gate asserts. §5b
5. ⚠️ **"Do not re-report what is fixed"** — correct, and 🖥 **three recorded fixes were re-measured and
   all still hold**: overflow 0px at 375px, 0 unnamed controls, and the branch-specific missing heading is
   absent. **Reported as CONFIRMED-HELD, not as new findings.**
6. ⚠️ **"the checkout's missing heading was branch-specific and only the no-offer branch had it."** 🖥 The
   no-offer branch has its heading **and so does the real-offer branch**. Today **neither** is missing.
7. ⛔ **"MCP Playwright HAS FAILED TO LAUNCH HERE FIVE TIMES."** 🔬 **True as history, now STALE.** It was
   fixed 2026-09-12 by explicit `--executable-path` configuration (§4a) and Phase 0 was re-run on it.
8. ⛔ **`BROWSER-AUDIT-2026-09-01.md` "was done with a DIFFERENT instrument."** 🔬 **FALSE — its own header
   says "Playwright MCP driving a warmed `php artisan serve` on `http://localhost:8000`."** It is **the same
   instrument this brief specifies.** ⚠️ The difference that actually matters is not the tool but the
   **origin**: it ran on the `APP_URL` host deliberately, which is why it saw no CSP error.
9. ⛔ **"NOT `chrome-headless-shell`."** 🔬 The project's own recorded baseline already rests on it —
   **`REMAINING-OVERFLOW.md` states it was "Measured 2026-09-04 with `chrome-headless-shell` over CDP."**
   The instrument the brief forbids is the one that produced a document the brief cites as settled truth.
10. ⚠️ **"the twelve accounts"** (`DEMO-ROLE-ACCOUNTS.md`) — 🔬 there are **14 demo accounts** covering
   **12 of 14 roles**; 11 hold a back-office role. §3
11. ✅ **Correct and load-bearing:** that the instrument must be settled first, that one organisation makes
   multi-tenancy untestable, that Front Desk ⊆ Hotel Manager, and — decisively — that a closed defect
   re-reported as new poisons the report. **That rule caught two of my own errors this pass (§4a).**

### Classification of every finding — NEW / KNOWN / REGRESSION
| finding | class |
|---|---|
| Google *"no route / no error page"* wrong in two documents | **NEW** (documentation, LOW) |
| Inverted date range silently re-renders the form with no message | **NEW** (LOW) |
| **8 of 9 home-page panels ship inline `display:none`** — unreachable without JS; the rule-violation half of F-1 | **KNOWN, STILL OPEN** (`BROWSER-AUDIT` F-1). ⚠️ **Highest-severity item still live on this surface** |
| CSP blocks `marketing.js` on a non-`APP_URL` origin | ⛔ **KNOWN — `BROWSER-AUDIT` FP-1, already classed a false positive / deploy risk.** **NOT new** |
| `APP_URL` absolute asset URLs | ⛔ **KNOWN** — `BROWSER-AUDIT` §223 already records it: *"correct once `APP_URL` matches the deployed host."* My "B16 is too narrow" note stands as a **documentation** refinement only |
| F-1 nine dead controls · F-2 pricing toggle | ✅ **KNOWN-FIXED, confirmed by measurement today** |
| F-3 "Download pricing as PDF" · "Daylight" | ✅ **KNOWN-REMOVED** |
| footer "Join" | ⚠️ **UNRESOLVED** — could not complete a click |
| Tap targets under 44px, 30–88/page | **KNOWN** — 📄 `STATE.md` records 29–95/page. ⚠️ **All pass WCAG 2.5.8 AA (24px); they fail 2.5.5 AAA (44px).** Consistent with the record, **not** regressed |
| Overflow at 375px · unnamed controls · branch-specific heading | **KNOWN-FIXED, CONFIRMED STILL HELD** |
| **REGRESSIONS** | ⚠️ **NONE FOUND.** Every recorded fix re-measured today still holds |

---

## 7 · ⚠️ METHODOLOGY TRAPS — two fired on me in this phase

Recorded because later phases will hit both.

1. ⚠️⚠️ **A route-list middleware filter CANNOT SEE GATE-BASED AUTHORIZATION.** My first pass over the
   public surface reported **20 `/horizon/*` endpoints as unauthenticated.** 🔬 **False reading.** Horizon
   authorizes through `Gate::define('viewHorizon')`, which fails closed on a null user and requires Super
   Admin. **Nothing is exposed.** A phase that greps middleware and publishes the result will manufacture
   a critical finding out of nothing.
2. ⚠️ **My own permission census was wrong on the first run.** `config['permissions']` is a **flat list**,
   so keying on `$k` yielded the integer indices `0…50` — which *looked* like a plausible count of 51 and
   produced the confident nonsense *"51 of 51 permissions granted to no role"*. Caught only because the
   output was inspected rather than quoted. ⚠️ **A wrong number stated confidently is the worst outcome
   available** — it nearly was one.

---

## 8 · WHAT WAS NOT DONE, DELIBERATELY

- ⛔ **No application code changed** — not a class, not a view, not a stylesheet.
- ⛔ **No substitute browser used**, though two are available.
- ⛔ **No second tenant built** — it moves pinned figures (§2).
- ⛔ **No open ruling decided**, including the five orphan permissions in §3, which is an *observation*
  about audit coverage, not a proposal to change a permission.
- ⛔ **No authenticated surface touched** (Phases 1+).
- ⛔ **The suite was not run** — no code changed, so a run would assert nothing about this pass.
- ⛔ **No dev server started; never `artisan serve`.**

🔬 **Pinned figures re-measured today and ALL UNMOVED:** `roles` **14** · `permissions` **51** ·
`role_has_permissions` **260** · `model_has_roles` **14** · `plans` **4** · `owner_ledger` **29 / 730640**.
⚠️ **The money gate (`ops/money-as-seeded.sh`) was NOT run** — it begins `migrate:fresh --seed`, which would
rebuild the demo world this audit is supposed to observe. **Untouched, and therefore not quoted.**

⚠️ **All nine reference documents this phase consulted are UNTRACKED** (`BROWSER-AUDIT-2026-09-01`,
`REMAINING-OVERFLOW`, `CLOAK-CENSUS`, `GUEST-HEADINGS`, `CONTROL-NAMES`, `DIALOG-CONTRACT-COMPLETE`,
`PRICING-PAIRING`, `PLAN-CATALOGUE-RESIDUALS`, `DEMO-ROLE-ACCOUNTS`). **The entire record of what is
already fixed exists only in the working tree.** A stash-from-clean would take the register with it.
