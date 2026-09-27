# Briefs with open work, saved word for word

Each file is the founder's brief **exactly as pasted into the session**, extracted from the session transcript by
MEMORY 01 (2026-09-27). Nothing is rewritten and no header is added; this README holds the provenance. **A brief is an
instruction as written on its date. Where it and `../STATE.md` disagree, the state is newer.** Briefs are written in the
founder's separate planning chat.

| File | Pasted (UTC) | sha256 (first 16) | Open work |
|---|---|---|---|
| `TONIGHT-01.md` | 2026-09-25 19:12 | `d3272d3cd9216006` | Part 2 (after `MAIL_PASSWORD`). Part 1 is done (R: `docs/DECISIONS.md`, 2026-09-25). |
| `MAIL-02.md` | 2026-09-25 16:58 | `6c5e23dec44fed5a` | Planned for Postmark, run on Hostinger SMTP. The switch-over waits on `MAIL_PASSWORD` (see `TONIGHT-01.md`). "Finish MAIL 02 exactly as you laid it out" in TONIGHT 01 means the session's Hostinger plan; its outcome is the MAIL 02 entry in `docs/DECISIONS.md`. |
| `DNS-01.md` | 2026-09-25 16:58 | `e9ff3098b5df9cb6` | **Done** (it found the Hostinger mail DNS already correct and changed nothing). Kept only because `MAIL-02.md` cites it. |
| `KEYBOARD-01.md` | 2026-09-26 18:35 | `5a0ba1f863a3b139` | Not run. The **later** of two pasted versions; the earlier one (2026-09-25) is superseded. Job 4 (Stripe) waits on ADMIN 06 being live. |
| `ADMIN-06.md` | 2026-09-26 20:06 | `d2a49b1777020838` | Built locally, not committed or deployed; see `../STATE.md` and `../OPEN.md` A1. The **later** version, with #3b. |
| `PW-SIGNUP-DELETE-1.md` | 2026-09-26 18:35 | `84ff7fadf442df94` | The before/after proof for the first real deletion. Not started: the probe is not suspended (V 2026-09-27). |
| `PW-SIGNUP-DELETE-2-follow-up.md` | 2026-09-26 19:00 | `660cc5d919b8c6e5` | The founder's follow-up: explain 17 vs ~230 (answered: 232), and search every table after the delete. It began "suspended." but the probe was **not** suspended (V). |

**Where a brief's premise is now out of date:**
- `ADMIN-06.md` #5 says the mailer was `log` "until the email pass switched mail on". Mail was **never** switched on: it
  is still `log` (V 2026-09-27).
- `ADMIN-06.md` and `KEYBOARD-01.md` speak of "test-mode Stripe" and "test keys". Production actually runs the **fake**
  Stripe client with no key at all (V).
- `TONIGHT-01.md` says Hostinger has "no aliases or catch-all". The `sales@` alias was created afterwards (R 2026-09-26).
- `ADMIN-06.md` #6 "three laptop-only check scripts" means two scripts plus one reference file (`../OPEN.md` D-OPS3).
- `ADMIN-06.md` #7 "no `.env*` files under the web root" means **no stray copies**. The one shared `.env` lives in
  `public_html/` by design: mode 600, and refused from the web.

**Missing:** none known. The done passes' briefs (ADMIN 01–05, SESSIONS 01, FINISH 01 and the rest) are not kept here
(DNS 01 is, only because MAIL 02 cites it); their outcomes are in `docs/DECISIONS.md` and `../HISTORY.md`. **Never rewrite a brief from memory:** if one
is needed and missing, ask the founder to attach it.
