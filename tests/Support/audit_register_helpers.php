<?php

/**
 * ⚠️⚠️ THE AUDIT REGISTER'S OWN CONSISTENCY DETECTOR — FIX 48.
 *
 * ⚠️⚠️ WHY A DETECTOR AND NOT A SIXTH HAND SWEEP. Five consecutive passes each fixed the stale finding-status
 * claims they happened to see and each left more behind. FIX 47 closed F-35, swept §8's list and F-35's own
 * register row, reported a clean sweep — and never knew a THIRD surface existed in §4's table. **A status is
 * not a row; it is a claim with more surfaces than anyone counts.** This is the F-44 shape applied to the
 * record instead of the code: a register is one reader's sweep, a guard finds the class.
 *
 * ⚠️⚠️ THE TOKEN BOUNDARY IS LOAD-BEARING, AND IT WAS MEASURED BEFORE A LINE OF THIS WAS WRITTEN. A bare
 * `F-\d+` matches inside `UTF-16BE`, `PROOF-2026`, `DO-PDF-2`, `RIF-1`, `%PDF-1.7` and the August register's
 * own `F-2a-04` — 🔬 six real strings in this repository, across ten files that carry no register id at all.
 * An unbounded scan would have reported every one of them. {@see VY_FINDING_TOKEN}
 *
 * ⚠️⚠️ AND IT ANCHORS ON A STATUS MARKER, NEVER ON A WORD IN PROSE. The verification's own first parse
 * classified F-57 as DEFERRED by matching the word "DEFERS" inside the description of its fix. Every pattern
 * here is `\b`-anchored so `DEFERS` cannot match `DEFERRED`, negations are stripped, and quoted historical
 * text is removed before the scan. **A source scan reads its own commentary** — this one is built not to.
 */

/**
 * ⚠️⚠️ NOT `F-\d+`. See the class note above — the bare form has six false-positive shapes in this repo.
 * A register id is never preceded or followed by a word character or a hyphen.
 */
const VY_FINDING_TOKEN = '/(?<![A-Za-z0-9_-])F-(\d+)(?![A-Za-z0-9_-])/u';

const VY_REGISTER_FILE = 'docs/AUDIT-SUMMARY.md';

/** How far past a finding id a status claim may sit and still be a claim ABOUT it. */
const VY_CLAIM_WINDOW = 160;

/**
 * ⚠️⚠️ THE STATE MARKERS ARE CASE-SENSITIVE AND UPPERCASE, AND THAT IS THE WHOLE ANTI-PROSE RULE.
 *
 * 🔬 Measured across every authoritative row: this register writes a STATUS in capitals — `✅ CLOSED (FIX 03)`,
 * `⚠️⚠️ **DEFERRED (2026-09-19)**`, `**CONTAINED (FIX 38), NOT CLOSED**`. Lowercase "closed", "open" and
 * "deferred" are ORDINARY PROSE: *"the `public` realm closed"*, *"all three dialogs open"*, *"FIX 12's
 * deferred controls"*. An earlier draft of this file matched case-insensitively and reported all three as
 * contradictions — **the needle reading its own commentary, in its fourth recorded costume.**
 *
 * ⚠️ The EXCEPTIONS are a short list of phrases that assert a state in ANY case and cannot mean anything else.
 * Without them this guard would miss V-1 itself, whose wording is the lowercase *"remains blocked on §4"*.
 *
 * ⚠️ The winner is the EARLIEST match by POSITION, never the first pattern in this list — a cell reading
 * "CONTAINED (FIX 38), NOT CLOSED" must resolve to CONTAINED, and one reading "ACCEPTED BY RULING — NEITHER
 * OPEN NOR CLOSED" must resolve to ACCEPTED.
 */
function vyRegisterStatusPatterns(): array
{
    return [
        // ⚠️ CASE-SENSITIVE. A capitalised state name is a MARKER; the same word in lower case is prose.
        'ACCEPTED' => ['/\bACCEPTED\s+BY\s+RULING\b/u'],
        'PARTIAL' => ['/\bPARTIAL\s+BY\s+RULING\b/u'],
        'CONTAINED' => ['/\bCONTAINED\b/u'],
        'DEFERRED' => ['/\bDEFERRED\b/u'],
        'CLOSED' => ['/\bCLOSED\b/u', '/\bDONE\b/u'],
        'OPEN' => [
            '/\bSTILL\s+OPEN\b/u',
            '/\bOPEN\b/u',
            '/\bUNWIRED\b/u',
            '/\bBUILD\s+QUEUED\b/u',
            '/\bQUEUED\b/u',
            // ⚠️ The case-INSENSITIVE exceptions: phrases that assert a state whatever their capitalisation.
            // Without the first of these the guard would miss V-1, which reads "remains blocked on §4".
            '/\bremains?\s+blocked\b/iu',
            '/\bremains?\s+open\b/iu',
            '/\bstill\s+open\b/iu',
            '/\bblocked\s+on\b/iu',
        ],
    ];
}

/**
 * ⚠️ A claim immediately behind a negation is not that claim. "NOT CLOSED", "NEITHER OPEN", "nor closed" and
 * "never open" all describe a state by excluding it.
 *
 * ⚠️⚠️ BYTE OFFSETS, NOT CHARACTERS. `preg_match` reports BYTE offsets and this text is full of emoji, so an
 * `mb_substr` window lands in the wrong place and the negation is silently missed. The first draft of this
 * file did exactly that, and "**Admin half NOT closed**" was reported as a CLOSED claim.
 */
function vyRegisterClaimIsNegated(string $haystack, int $offset): bool
{
    $before = substr($haystack, max(0, $offset - 32), min(32, $offset));
    $before = str_replace(['*', '_', '`'], '', $before);

    return (bool) preg_match('/\b(not|never|neither|nor|no\s+longer|isn\'t|aren\'t)\s*$/iu', $before);
}

/**
 * Remove quoted and inline-code spans. The register and the verification both record what a surface USED to
 * say — in quotes (*"BUILT AND UNWIRED"*) or in backticks (`⛔ STILL OPEN`) — beside the correction. That
 * historical text is EVIDENCE, not a live claim, and reading it as one is the commentary trap.
 *
 * ⚠️ Applied to the WHOLE LINE before mentions are located, so an id inside a quotation is not a mention at
 * all. Stripping only the trailing fragment leaves the opening quote behind and the span unbalanced.
 */
function vyRegisterStripQuotedHistory(string $text): string
{
    $patterns = [
        '/"[^"]{0,400}"/u',
        '/\x{201C}[^\x{201D}]{0,400}\x{201D}/u',
        '/`[^`]{0,400}`/u',
    ];

    foreach ($patterns as $pattern) {
        $text = preg_replace($pattern, ' ', $text) ?? $text;
    }

    return $text;
}

/**
 * The earliest non-negated status claim in a fragment, or null when the fragment makes no status claim at all.
 */
function vyRegisterClaimIn(string $text): ?string
{
    $best = null;
    $bestAt = PHP_INT_MAX;

    foreach (vyRegisterStatusPatterns() as $state => $patterns) {
        foreach ($patterns as $pattern) {
            if (! preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[0] as [$needle, $at]) {
                if (vyRegisterClaimIsNegated($text, $at)) {
                    continue;
                }

                if ($at < $bestAt) {
                    $bestAt = $at;
                    $best = $state;
                }
            }
        }
    }

    return $best;
}

/**
 * ⚠️⚠️ THE AUTHORITATIVE ROWS, AND ONLY THEY. §2's three severity tables carry the status in the FIRST cell,
 * beside the id; §2b's table carries it in the LAST. Every other table in this document — the pass ledger,
 * the closure ledger, §4's tables, §8's list — is a SURFACE of those claims, never a source of them.
 *
 * @return array<int, array{status: string, line: int, section: string}>
 */
function vyAuthoritativeRegister(?string $path = null): array
{
    $path = $path ?? base_path(VY_REGISTER_FILE);
    $lines = file($path, FILE_IGNORE_NEW_LINES);

    $h2 = '';
    $h3 = '';
    $found = [];

    foreach ($lines as $i => $line) {
        if (str_starts_with($line, '## ')) {
            $h2 = trim(substr($line, 3));
            $h3 = '';

            continue;
        }

        if (str_starts_with($line, '### ')) {
            $h3 = trim(substr($line, 4));

            continue;
        }

        if (! str_starts_with($line, '|')) {
            continue;
        }

        $cells = array_map('trim', explode('|', trim($line, '|')));
        $anchor = $cells[0] ?? '';

        // The id must ANCHOR the cell — only markup may precede it. A row whose first cell merely mentions a
        // finding is not that finding's authoritative row.
        if (! preg_match('/^[*\s`]*(?<![A-Za-z0-9_-])F-(\d+)(?![A-Za-z0-9_-])/u', $anchor, $m)) {
            continue;
        }

        $id = (int) $m[1];

        $inSeverityTable = str_starts_with($h2, '2 ·')
            && preg_match('/\b(HIGH|MEDIUM|LOW)\b/u', $h3);

        $inProgrammeTable = str_starts_with($h2, '2b ·') && $h3 === '';

        if ($inSeverityTable) {
            $statusText = $anchor;
        } elseif ($inProgrammeTable) {
            $statusText = end($cells);
        } else {
            continue;
        }

        $claim = vyRegisterClaimIn(vyRegisterStripQuotedHistory($statusText));

        if ($claim === null) {
            continue;
        }

        $found[$id] = [
            'status' => $claim,
            'line' => $i + 1,
            'section' => $inSeverityTable ? '2' : '2b',
        ];
    }

    ksort($found);

    return $found;
}

/**
 * ⚠️ FROZEN FILES — dated artefacts that record their own moment and are never revised.
 *
 * ⚠️⚠️ THIS IS AN ALLOW-LIST AND ALLOW-LISTS ARE HOW A GUARD DIES QUIETLY. It is capped by a test that fails
 * when it grows. The RULE, not the list, is what admits a file: **a report of one dated pass, phase or audit,
 * whose claims are true of its own date and are superseded by the register rather than corrected in place.**
 * A live surface — one a reader consults to learn the CURRENT state — may never be added here.
 */
function vyFrozenFindingSurfaces(): array
{
    return [
        'docs/AUDIT-PHASE-0.md' => 'Dated phase report, 2026-09-12 — records findings as they were found.',
        'docs/AUDIT-PHASE-1.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-2.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-3.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-4.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-5.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-6.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-7.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-8.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-9.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-10.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-11.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-12.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-13.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-PHASE-14.md' => 'Dated phase report, 2026-09-12.',
        'docs/AUDIT-2026-08-29.md' => 'A dated audit that predates this register entirely.',
        'docs/BROWSER-AUDIT-2026-09-01.md' => 'A dated audit that predates this register entirely.',
        'docs/audits/DOCS-TRIAGE-2026-08-22.md' => 'A dated triage that predates this register entirely.',
    ];
}

/**
 * ⚠️⚠️ WHAT THIS SCAN ACTUALLY READ — because a detector that silently reads half of `docs/` looks EXACTLY
 * like a clean repository, and that is not hypothetical here.
 *
 * 🔬 The session memory's own index was 206 lines, over its limit, and had been silently truncating its tail
 * for an unknown number of passes: part of the record was simply not being read, and every gap it caused was
 * attributed to something else. **A coverage claim nobody asserts is a coverage claim nobody can check.**
 *
 * ⚠️ NOTHING IS TRUNCATED WHEN READING: every file is read whole with `file()`. The only files not scanned
 * are the frozen ones, which are COUNTED here rather than silently dropped.
 *
 * @return array{files_present:int, files_scanned:int, files_frozen:int, mentions_scanned:int, mentions_frozen:int, bytes_scanned:int}
 */
function vyRegisterScanCoverage(?string $root = null): array
{
    $root = rtrim($root ?? base_path(), '/');
    $frozen = vyFrozenFindingSurfaces();

    $present = 0;
    $scanned = 0;
    $skipped = 0;
    $mentions = 0;
    $mentionsFrozen = 0;
    $bytes = 0;

    foreach (vyRegisterMarkdownFiles($root) as $path) {
        $present++;
        $relative = ltrim(str_replace($root, '', $path), '/');
        $body = file_get_contents($path);
        $hits = preg_match_all(VY_FINDING_TOKEN, $body);

        if (isset($frozen[$relative])) {
            $skipped++;
            $mentionsFrozen += $hits;

            continue;
        }

        $scanned++;
        $mentions += $hits;
        $bytes += strlen($body);
    }

    return [
        'files_present' => $present,
        'files_scanned' => $scanned,
        'files_frozen' => $skipped,
        'mentions_scanned' => $mentions,
        'mentions_frozen' => $mentionsFrozen,
        'bytes_scanned' => $bytes,
    ];
}

/**
 * Every `.md` under `docs/`, sorted. ONE walker, used by both the scan and its coverage count — two walkers
 * would let the coverage figure describe a different set of files than the scan actually read.
 *
 * @return array<int, string>
 */
function vyRegisterMarkdownFiles(string $root): array
{
    $files = [];
    $walker = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/docs'));

    foreach ($walker as $file) {
        if ($file->isFile() && $file->getExtension() === 'md') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

/**
 * Every finding-status claim anywhere under `docs/` that disagrees with its authoritative row.
 *
 * ⚠️⚠️ THREE LIMITS, STATED RATHER THAN DISCOVERED LATER. None of them truncates a FILE; all three bound how
 * far a CLAIM is searched for, and two of them can cause a MISS:
 *
 *   1. ⚠️ **Line-scoped.** A claim that wraps onto the next line is not seen. **Can miss.** Markdown tables —
 *      which is where statuses actually live — are single-line, so the exposure is prose.
 *   2. ⚠️ **{@see VY_CLAIM_WINDOW}** bounds how far past an id a claim may sit. **Can miss** a claim further
 *      away; without it the scan read a sentence 200 characters downstream about something else and reported
 *      it against F-38.
 *   3. The quote stripper's 400-character span bound. A longer quotation is NOT stripped, so a quoted
 *      historical status would be read as live — **a false positive, never a miss**, which is the safe
 *      direction: it surfaces for triage instead of passing silently.
 *
 * @return array<int, array{file: string, line: int, id: int, claimed: string, authoritative: string, text: string}>
 */
function vyRegisterContradictions(?string $root = null): array
{
    $root = rtrim($root ?? base_path(), '/');
    $authoritative = vyAuthoritativeRegister($root.'/'.VY_REGISTER_FILE);
    $frozen = vyFrozenFindingSurfaces();

    $files = vyRegisterMarkdownFiles($root);

    $registerPath = realpath($root.'/'.VY_REGISTER_FILE);
    $authoritativeLines = [];

    foreach ($authoritative as $id => $row) {
        $authoritativeLines[$row['line']] = $id;
    }

    $offenders = [];

    foreach ($files as $path) {
        $relative = ltrim(str_replace($root, '', $path), '/');

        if (isset($frozen[$relative])) {
            continue;
        }

        $isRegister = realpath($path) === $registerPath;
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        foreach ($lines as $raw => $original) {
            $i = $raw;
            $line = vyRegisterStripQuotedHistory($original);

            if (! preg_match_all(VY_FINDING_TOKEN, $line, $hits, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $isTableRow = str_starts_with(ltrim($line), '|');
            $anchorId = ($isRegister && isset($authoritativeLines[$i + 1])) ? $authoritativeLines[$i + 1] : null;

            foreach ($hits[0] as $k => [$needle, $at]) {
                $id = (int) $hits[1][$k][0];

                if ($id === $anchorId) {
                    continue;
                }

                if (! isset($authoritative[$id])) {
                    continue;
                }

                $from = $at + strlen($needle);
                $next = $hits[0][$k + 1][1] ?? strlen($line);

                // ⚠️ A status claim sits BESIDE its finding. Without a cap the scan reads a sentence 200
                // characters downstream about something else entirely — "the general form of this gap is
                // still open" was read as a claim about F-38, which is closed.
                $fragment = substr($line, $from, min($next - $from, VY_CLAIM_WINDOW));

                // An id that anchors its own row may have its status in any later cell (§4's table puts it
                // three columns over). An id merely referenced mid-row is bounded by its own cell.
                if ($isTableRow && ! vyRegisterIsRowAnchor($line, $at)) {
                    $cellEnd = strpos($fragment, '|');

                    if ($cellEnd !== false) {
                        $fragment = substr($fragment, 0, $cellEnd);
                    }
                }

                // ⚠️⚠️ AN ID INSIDE PARENTHESES IS A CROSS-REFERENCE, AND ITS CLAIM IS BEHIND IT, NOT AHEAD.
                // §2's state legend reads "CONTAINED — … (F-53, F-54) · ACCEPTED BY RULING — …": scanning
                // forward from F-54 reaches the NEXT state's name and reports a contradiction that is really
                // a definition list.
                if (vyRegisterIsParenthesised($line, $at)) {
                    $close = strpos($fragment, ')');
                    $fragment = $close === false ? '' : substr($fragment, 0, $close);
                }

                $claim = vyRegisterClaimIn($fragment);

                if ($claim === null || $claim === $authoritative[$id]['status']) {
                    continue;
                }

                $offenders[] = [
                    'file' => $relative,
                    'line' => $i + 1,
                    'id' => $id,
                    'claimed' => $claim,
                    'authoritative' => $authoritative[$id]['status'],
                    'text' => trim(mb_substr($line, 0, 200)),
                ];
            }
        }
    }

    return $offenders;
}

/**
 * Does this id sit inside an unclosed `(` on its line — i.e. is it a parenthesised cross-reference?
 */
function vyRegisterIsParenthesised(string $line, int $offset): bool
{
    $before = substr($line, 0, $offset);
    $open = strrpos($before, '(');

    if ($open === false) {
        return false;
    }

    $close = strrpos($before, ')');

    return $close === false || $close < $open;
}

// ═══ THE RULINGS — the same class, one namespace over ═══════════════════════════════════════════
//
// ⚠️⚠️ THREE REAL CONTRADICTIONS WERE INVISIBLE TO THE F-ID SCAN BECAUSE THEY CARRY NO F-ID:
// `STATUS-LADDERS.md` said "R1 and R3 were NOT put and remain open" while its OWN ROWS below read ✅ RULED;
// R6's block still read as undecided after being ruled and pinned. Both were found by eye. This closes that.
//
// ⚠️⚠️ AND IT IS NAMESPACE-AWARE, BECAUSE F-63 PROVED IT HAS TO BE. `R2` names a DIFFERENT, ALREADY-RULED
// question in `HOUSEKEEPING-LADDER.md` (a cleaned room going backward — §4 calls it H2), and a THIRD thing
// again in `DECISIONS.md`. A scan that treated `R<n>` as global would manufacture exactly the false
// conclusion F-63 exists to warn about.

/** ⚠️ A NAMED ruling (`R-GATE`, `R-SEASON`, …) is unambiguous across the whole record. */
const VY_RULING_NAMED_TOKEN = '/(?<![A-Za-z0-9_-])(R-[A-Z][A-Z0-9]*(?:-[A-Z0-9]+)*)(?![A-Za-z0-9_-])/u';

/** ⚠️ A NUMBERED ruling (`R1`, `H4`) is only the reservation/housekeeping ladder INSIDE the two files below. */
const VY_RULING_NUMBERED_TOKEN = '/(?<![A-Za-z0-9_-])([RH]\d+)(?![A-Za-z0-9_-])/u';

/**
 * ⚠️⚠️ THE ONLY FILES WHERE A BARE `R1`/`H4` MEANS THE LADDER RULING. This is a SCOPE rule, not an
 * allow-list of excused defects: outside these two the same token is a different namespace (F-63), so
 * scanning it would invent contradictions rather than find them. Capped by a test.
 */
function vyRulingNumberedScope(): array
{
    return [
        'docs/AUDIT-SUMMARY.md' => '§4 defines the ladder numbering; its answers table is authoritative.',
        'docs/STATUS-LADDERS.md' => 'The reservation ladder itself — R1…R6 and the H-rulings by reference.',
    ];
}

/**
 * ⚠️ TWO STATES, NOT FIVE. The contradiction that actually happens is "decided" versus "nobody decided",
 * and collapsing BUILT/SATISFIED/NOT BUILT into DECIDED makes the check robust without making it vague:
 * all three mean the founder answered. A mention claiming a ruling is unruled when it is answered — or
 * answered when it is not — is the defect.
 */
function vyRulingClaimIn(string $text): ?string
{
    $decided = ['/\bRULED\b/u', '/\bSATISFIED\b/u', '/\bBUILT\b/u', '/\bDECIDED\b/u', '/\bANSWERED\b/u'];
    // ⚠️ "unruled"/"undecided" are CASE-INSENSITIVE: unlike "closed" or "open", neither is an ordinary
    //    English word that turns up incidentally here — every occurrence is a status claim.
    $undecided = ['/\bUNRULED\b/iu', '/\bUNDECIDED\b/iu', '/\bNOT\s+PUT\b/iu', '/\bnever\s+put\b/iu',
        '/\bremains?\s+open\b/iu', '/\bstill\s+open\b/iu', '/\bnot\s+ruled\b/iu'];

    $best = null;
    $bestAt = PHP_INT_MAX;

    foreach ([['DECIDED', $decided], ['UNDECIDED', $undecided]] as [$state, $patterns]) {
        foreach ($patterns as $pattern) {
            if (! preg_match_all($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($m[0] as [$needle, $at]) {
                // ⚠️ "NOT BUILT" is a DECIDED state (R-REVERSE was ruled: quarantine it), so a negation in
                // front of BUILT must NOT flip it — but "not ruled" is already an UNDECIDED pattern above.
                if ($state === 'DECIDED' && ! str_contains($needle, 'BUILT')
                    && vyRegisterClaimIsNegated($text, $at)) {
                    continue;
                }

                if ($at < $bestAt) {
                    $bestAt = $at;
                    $best = $state;
                }
            }
        }
    }

    return $best;
}

/**
 * Every ruling with a recorded answer, derived from §4's answers table, the R1/R3 table beneath it, and the
 * ladder rows' own `RULED` markers in `STATUS-LADDERS.md`.
 *
 * @return array<string, string>
 */
function vyAuthoritativeRulings(?string $root = null): array
{
    $root = rtrim($root ?? base_path(), '/');
    $rulings = [];

    // §4 and its neighbours: any table row anchored on a ruling id, inside section 4.
    $h2 = '';
    foreach (file($root.'/'.VY_REGISTER_FILE, FILE_IGNORE_NEW_LINES) as $line) {
        if (str_starts_with($line, '## ')) {
            $h2 = trim(substr($line, 3));
        }

        if (! str_starts_with($h2, '4 ·') || ! str_starts_with($line, '|')) {
            continue;
        }

        $cells = array_map('trim', explode('|', trim($line, '|')));
        $anchor = vyRegisterStripQuotedHistory($cells[0] ?? '');

        // ⚠️ Anchored, and the NAMED form is tried first: `R-HK-11` must not be read as the bare `R`…
        if (preg_match('/^[*\s`]*(R-[A-Z][A-Z0-9]*(?:-[A-Z0-9]+)*)(?![A-Za-z0-9_-])/u', $anchor, $m)) {
            $id = $m[1];
        } elseif (preg_match('/^[*\s`]*([RH]\d+)(?![A-Za-z0-9_-])/u', $anchor, $m)) {
            $id = $m[1];
        } else {
            continue;
        }

        // A row inside §4's answers/decision tables records an ANSWER; the ladder table records today's
        // default and is not itself a ruling, so only rows whose cells state a state are taken.
        $state = vyRulingClaimIn(vyRegisterStripQuotedHistory(implode(' ', array_slice($cells, 1))));

        if ($state !== null && ! isset($rulings[$id])) {
            $rulings[$id] = $state;
        }
    }

    // ⚠️⚠️ §4 WINS. The ladder's own rows fill in ONLY what §4 does not carry — an earlier draft of this
    // function let this second loop overwrite §4 and reported R6 and H4 as UNDECIDED when §4 rules both
    // SATISFIED. Two sources of truth, and the wrong one winning, in a guard built to stop exactly that.
    foreach (file($root.'/docs/STATUS-LADDERS.md', FILE_IGNORE_NEW_LINES) as $line) {
        if (! str_starts_with($line, '|')) {
            continue;
        }

        $cells = array_map('trim', explode('|', trim($line, '|')));
        $anchor = $cells[0] ?? '';

        if (! preg_match('/^[*\s`]*([RH]\d+)(?![A-Za-z0-9_-])/u', $anchor, $m)) {
            continue;
        }

        if (isset($rulings[$m[1]])) {
            continue;
        }

        if (str_contains($anchor, 'RULED')) {
            $rulings[$m[1]] = 'DECIDED';

            continue;
        }

        // ⚠️⚠️ AN ABSENT MARKER MEANS "UNRULED" ONLY WHERE THE RECORD SAYS SO. §2a establishes exactly that
        // for the RESERVATION ladder, so a bare `R<n>` row there is asserted UNDECIDED. ⚠️ It does NOT hold
        // for the H-rulings: **R-HK-11 ruled "keep the housekeeping ladder permissive — all fourteen
        // permitted moves stay", which arguably answers H1–H3 without naming them** — the F-32 shape, and a
        // question for the founder rather than for a parser. **So they are left OUT of the map entirely
        // rather than asserted either way.** An id with no authoritative answer is simply not checked.
        if (str_starts_with($m[1], 'R')) {
            $rulings[$m[1]] = 'UNDECIDED';
        }
    }

    ksort($rulings);

    return $rulings;
}

/**
 * Every ruling-state claim under `docs/` that disagrees with the authoritative answer.
 *
 * @return array<int, array{file: string, line: int, id: string, claimed: string, authoritative: string, text: string}>
 */
function vyRulingContradictions(?string $root = null): array
{
    $root = rtrim($root ?? base_path(), '/');
    $rulings = vyAuthoritativeRulings($root);
    $frozen = vyFrozenFindingSurfaces();
    $numberedScope = vyRulingNumberedScope();
    $offenders = [];

    foreach (vyRegisterMarkdownFiles($root) as $path) {
        $relative = ltrim(str_replace($root, '', $path), '/');

        if (isset($frozen[$relative])) {
            continue;
        }

        $numbered = isset($numberedScope[$relative]);

        foreach (file($path, FILE_IGNORE_NEW_LINES) as $i => $original) {
            $line = vyRegisterStripQuotedHistory($original);
            $hits = [];

            foreach ([VY_RULING_NAMED_TOKEN, VY_RULING_NUMBERED_TOKEN] as $k => $token) {
                if ($k === 1 && ! $numbered) {
                    continue;
                }

                if (preg_match_all($token, $line, $m, PREG_OFFSET_CAPTURE)) {
                    foreach ($m[1] as [$id, $at]) {
                        $hits[$at] = $id;
                    }
                }
            }

            if ($hits === []) {
                continue;
            }

            ksort($hits);
            $positions = array_keys($hits);

            foreach ($positions as $k => $at) {
                $id = $hits[$at];

                if (! isset($rulings[$id])) {
                    continue;
                }

                // The ladder rows and §4's tables ARE the record — never read them as claims about it.
                if (str_starts_with(ltrim($line), '|') && vyRegisterIsRowAnchor($line, $at)) {
                    continue;
                }

                $from = $at + strlen($id);
                $next = $positions[$k + 1] ?? strlen($line);
                $fragment = substr($line, $from, min($next - $from, VY_CLAIM_WINDOW));

                if (vyRegisterIsParenthesised($line, $at)) {
                    $close = strpos($fragment, ')');
                    $fragment = $close === false ? '' : substr($fragment, 0, $close);
                }

                $claim = vyRulingClaimIn($fragment);

                if ($claim === null || $claim === $rulings[$id]) {
                    continue;
                }

                $offenders[] = [
                    'file' => $relative,
                    'line' => $i + 1,
                    'id' => $id,
                    'claimed' => $claim,
                    'authoritative' => $rulings[$id],
                    'text' => trim(mb_substr($original, 0, 200)),
                ];
            }
        }
    }

    return $offenders;
}

/**
 * Is this id the anchor of its table row — i.e. the first cell holds it and nothing else of substance?
 */
function vyRegisterIsRowAnchor(string $line, int $offset): bool
{
    $head = substr($line, 0, $offset);

    if (substr_count($head, '|') !== 1) {
        return false;
    }

    return trim(str_replace(['*', ' ', '`'], '', substr($head, strpos($head, '|') + 1))) === '';
}
