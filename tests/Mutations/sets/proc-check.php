<?php

declare(strict_types=1);

// Mutation set proc-check — A7 (2026-11-11): `ops/proc-check.sh --here`, each rule that keeps it from ever exempting a run that is ours, removed one at a time.
// Run with `php tests/Mutations/run.php --set=proc-check`.

$file = 'ops/proc-check.sh';
$test = 'tests/Feature/ProcCheckTest.php';

return [
    [
        'id' => 'PC-M1',
        'name' => 'an UNREADABLE working folder is treated as foreign (an uncertain match is exempted instead of counted)',
        'file' => $file,
        'old' => '      if [ -n "${_cwd}" ]; then',
        'new' => '      if true; then',
        'tests' => [['file' => $test, 'filter' => 'CANNOT be read']],
    ],
    [
        'id' => 'PC-M2',
        'name' => 'a process that NAMES this repository is exempted because its working folder is elsewhere',
        'file' => $file,
        'old' => '          *) if printf \'%s\' "${_line}" | grep -qF "${REPO_ROOT}"; then _foreign=0; else _foreign=1; fi ;;',
        'new' => '          *) _foreign=1 ;;',
        'tests' => [['file' => $test, 'filter' => 'NAMES this repository']],
    ],
    [
        'id' => 'PC-M3',
        'name' => 'a run inside this repository is exempted (the folder test is inverted)',
        'file' => $file,
        'old' => '          "${REPO_ROOT}"/*) _foreign=0 ;;',
        'new' => '          "${REPO_ROOT}"/*) _foreign=1 ;;',
        'tests' => [['file' => $test, 'filter' => 'THIS repository still blocks']],
    ],
    [
        'id' => 'PC-M4',
        'name' => 'a foreign run is never exempted (--here does nothing — the other project holds the harness again)',
        'file' => $file,
        'old' => '    if [ "${_foreign}" -eq 1 ]; then
      FOREIGN=$((FOREIGN + 1))',
        'new' => '    if false; then
      FOREIGN=$((FOREIGN + 1))',
        'tests' => [['file' => $test, 'filter' => 'ANOTHER project']],
    ],
];
