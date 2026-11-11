<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

// ⚠️⚠️ A7 (found 2026-09-29, again 2026-10-24; fixed 2026-11-11, his "do all") — `ops/proc-check.sh --here`.
// The mutation harness refused to start while ANY `pest` ran anywhere on the Mac, so another project's test run held it for half an hour and a hurried
// session could be tempted to weaken the guard. RULES.md: identify a process by its WORKING FOLDER, never by the look of its command line.
// Only a match whose working folder is READ and is OUTSIDE this repository (and whose command line does not name it) is "not ours"; everything
// uncertain counts. These tests plant real processes with a unique name — never `pest`, since this very run matches that.

/** Start a long-lived process whose command line carries $marker, in $cwd; returns once `ps` can see it. */
function pcPlant(string $marker, string $cwd): Process
{
    $p = new Process(['bash', '-c', 'sleep 90; :', $marker], $cwd);
    $p->disableOutput();
    $p->start();
    for ($i = 0; $i < 40; $i++) {
        usleep(100000);
        if (str_contains((string) shell_exec('ps -eo pid,command'), $marker)) {
            return $p;
        }
    }

    return $p;
}

/** @return array{0: int, 1: string} exit code and output */
function pcCheck(string $flags, string $marker, array $env = []): array
{
    $prefix = '';
    foreach ($env as $k => $v) {
        $prefix .= $k.'='.escapeshellarg($v).' ';
    }
    // ⚠️ to a FILE, not a pipe: the script's own control marker (`sleep 25`) outlives it and would hold a pipe open for 25 seconds
    $file = sys_get_temp_dir().'/vy-proccheck-'.bin2hex(random_bytes(4)).'.out';
    exec($prefix.'bash '.escapeshellarg(base_path('ops/proc-check.sh')).' '.$flags.' '.escapeshellarg($marker).' > '.escapeshellarg($file).' 2>&1', $out, $code);
    $text = (string) @file_get_contents($file);
    @unlink($file);

    return [$code, $text];
}

it('⚠️⚠️ --here: a run in ANOTHER project\'s folder does not hold the harness; the same run without --here still does', function () {
    $marker = 'vyplanted'.bin2hex(random_bytes(4));
    $foreign = realpath(sys_get_temp_dir());
    $p = pcPlant($marker, $foreign);
    try {
        [$withHere, $out] = pcCheck('-q --here', $marker);
        expect($withHere)->toBe(0, 'a foreign working folder must not block: '.$out);

        [$without] = pcCheck('-q', $marker);
        expect($without)->toBe(1);   // the default is UNCHANGED: any match blocks

        [, $said] = pcCheck('--here', $marker);
        expect($said)->toContain('not ours (working folder')->toContain($foreign);
    } finally {
        $p->stop(0);
    }
});

it('⚠️⚠️ --here: a run in THIS repository still blocks', function () {
    $marker = 'vyplanted'.bin2hex(random_bytes(4));
    $p = pcPlant($marker, base_path());
    try {
        [$code] = pcCheck('-q --here', $marker);
        expect($code)->toBe(1);

        // a subfolder of the repository is the repository
        $sub = pcPlant($marker.'b', base_path('tests'));
        try {
            [$subCode] = pcCheck('-q --here', $marker.'b');
            expect($subCode)->toBe(1);
        } finally {
            $sub->stop(0);
        }
    } finally {
        $p->stop(0);
    }
});

it('⚠️⚠️ --here: a process that NAMES this repository counts, whatever its working folder', function () {
    $marker = 'vyplanted'.bin2hex(random_bytes(4));
    $p = new Process(['bash', '-c', 'sleep 90; :', $marker, base_path('vendor/bin/pest')], realpath(sys_get_temp_dir()));
    $p->disableOutput();
    $p->start();
    usleep(600000);
    try {
        [$code] = pcCheck('-q --here', $marker);
        expect($code)->toBe(1);   // its command line names the repo path — ours
    } finally {
        $p->stop(0);
    }
});

it('⚠️⚠️ --here: when the working folder CANNOT be read, nobody is exempted (an uncertain match is ours)', function () {
    $marker = 'vyplanted'.bin2hex(random_bytes(4));
    $p = pcPlant($marker, realpath(sys_get_temp_dir()));
    try {
        // lsof fails → the verifier's own control fails → no exemption, whatever the folder
        [$blind, $out] = pcCheck('--here', $marker, ['VY_LSOF_CMD' => 'false']);
        expect($blind)->toBe(1)->and($out)->toContain('NO match was exempted');

        // lsof is healthy (the verifier passes) but says nothing for THIS process (a permission refusal reads as empty) → ours, never exempted
        $stub = sys_get_temp_dir().'/vy-lsof-'.bin2hex(random_bytes(3)).'.sh';
        // reports correctly for everything EXCEPT the planted `sleep 90` process (a permission refusal reads as empty — and the checker's own command line carries the marker too, so the marker cannot be the test): `lsof -a -p <pid> -d cwd -Fn` → $3 is the pid
        file_put_contents($stub, "#!/bin/bash\nif ps -p \"\$3\" -o command= 2>/dev/null | grep -q 'sleep 90'; then exit 1; fi\nexec /usr/sbin/lsof \"\$@\"\n");
        chmod($stub, 0755);
        try {
            [$denied, $deniedOut] = pcCheck('--here', $marker, ['VY_LSOF_CMD' => $stub]);
            expect($denied)->toBe(1, $deniedOut)->and($deniedOut)->not->toContain('NO match was exempted')->and($deniedOut)->not->toContain('not ours');
        } finally {
            @unlink($stub);
        }
    } finally {
        $p->stop(0);
    }
});

it('⚠️ --here: nothing matching at all is still a trusted zero (exit 0), and a vanished process is dropped', function () {
    [$code] = pcCheck('-q --here', 'vyneverrunning'.bin2hex(random_bytes(4)));
    expect($code)->toBe(0);
});

it('the mutation harness asks with --here (the other project\'s run no longer blocks it)', function () {
    expect((string) file_get_contents(base_path('tests/Mutations/run.php')))->toContain('bash ops/proc-check.sh -q --here ');
});
