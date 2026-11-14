<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

// ⚠️ D-EDGE-CHECKS (seen 2026-10-06; fixed 2026-11-11, his go). `ops/public-checks.sh` read an answer from the CDN's EDGE as a FAIL of the SITE:
// after the CDN's bot check met this Mac's automated browser, a run answered 1 PASS · 10 FAIL (every route a 504 or a redirect to itself) while the
// application had logged nothing and the server, asking through the same public hostname, got 200 everywhere. A probe that fails is now asked a
// second time from the server; the answer decides the verdict. These tests drive the script's own decision functions with a stand-in `curl` and
// `ssh` (no network): the script is sourced with PUBLIC_CHECKS_LIB_ONLY=1.

/** A scratch dir holding a fake `ssh` (and `curl`), driven by environment variables; returns its path. */
function pcs_bin(): string
{
    $dir = sys_get_temp_dir().'/vy-pcs-'.bin2hex(random_bytes(4));
    mkdir($dir);
    // the server's answer: line 1 is "status|location", the rest is the body; STUB_SSH_EXIT != 0 means the server cannot be reached
    file_put_contents($dir.'/ssh', "#!/usr/bin/env bash\necho x >> \"\$STUB_SSH_LOG\"\n[ \"\${STUB_SSH_EXIT:-0}\" = 0 ] || exit \"\$STUB_SSH_EXIT\"\nprintf '%s\\n' \"\$STUB_SSH_FIRST\"\nprintf '%s' \"\${STUB_SSH_BODY:-}\"\n");
    file_put_contents($dir.'/curl', "#!/usr/bin/env bash\nout=''\nwhile [ \$# -gt 0 ]; do case \"\$1\" in -o) out=\"\$2\"; shift 2;; -w) shift 2;; *) shift;; esac; done\nprintf '%s' \"\${STUB_BODY:-}\" > \"\$out\"\nprintf '%s' \"\${STUB_LINE:-000|}\"\n");
    chmod($dir.'/ssh', 0755);
    chmod($dir.'/curl', 0755);

    return $dir;
}

/** @return array{0: int, 1: string, 2: int} exit code, output, and how many times the (fake) ssh was called */
function pcs_run(string $script, array $env = []): array
{
    $bin = pcs_bin();
    $log = $bin.'/ssh.log';
    touch($log);
    $p = new Process(['bash', '-c', 'source '.escapeshellarg(base_path('ops/public-checks.sh')).'; '.$script], base_path(), array_merge([
        'PATH' => $bin.':'.getenv('PATH'), 'PUBLIC_CHECKS_LIB_ONLY' => '1', 'STUB_SSH_LOG' => $log, 'HOME' => sys_get_temp_dir(),
    ], $env));
    $p->run();
    $calls = count(array_filter(explode("\n", (string) file_get_contents($log))));

    return [$p->getExitCode() ?? -1, $p->getOutput().$p->getErrorOutput(), $calls];
}

it('⚠️⚠️ a probe that failed HERE but is fine from the server is COULD NOT MEASURE — "the edge, not the site"', function () {
    [$code, $out, $calls] = pcs_run('second_look "/login renders" "HTTP 504" GET /login want_login; echo "PASS=$PASS FAIL=$FAIL CNM=$CNM"', [
        'STUB_SSH_FIRST' => '200|', 'STUB_SSH_BODY' => '<a>Continue with Google</a> <a>Continue with X</a>',
    ]);

    expect($out)->toContain('COULD NOT MEASURE')->toContain('/login renders')->toContain('HTTP 504')->toContain('the EDGE, not the site')
        ->and($out)->toContain('PASS=0 FAIL=0 CNM=1')
        ->and($calls)->toBe(1);
});

it('⚠️⚠️ when the server gets the wrong answer too, it is a FAIL — seen from two places', function () {
    [, $out] = pcs_run('second_look "/login renders" "HTTP 504" GET /login want_login; echo "PASS=$PASS FAIL=$FAIL CNM=$CNM"', [
        'STUB_SSH_FIRST' => '503|', 'STUB_SSH_BODY' => 'down',
    ]);

    expect($out)->toContain('FAIL ✗')->toContain('and from the server too: HTTP 503')->not->toContain('the EDGE, not the site')
        ->and($out)->toContain('PASS=0 FAIL=1 CNM=0');
});

it('⚠️ when the server cannot be asked, the FAIL STANDS — never downgraded without evidence', function () {
    [, $out, $calls] = pcs_run('second_look "/signup renders the form (sign-ups ON)" "HTTP 000" GET /signup want_signup; echo "PASS=$PASS FAIL=$FAIL CNM=$CNM"', [
        'STUB_SSH_EXIT' => '255', 'STUB_SSH_FIRST' => '200|',
    ]);

    expect($out)->toContain('FAIL ✗')->toContain('second look unavailable')->toContain('PASS=0 FAIL=1 CNM=0')
        ->and($calls)->toBe(1);

    // an ssh that answers nothing at all is the same
    [, $silent] = pcs_run('second_look "/x" "HTTP 000" GET /x want_200; echo "FAIL=$FAIL CNM=$CNM"', ['STUB_SSH_FIRST' => '']);
    expect($silent)->toContain('FAIL=1 CNM=0');
});

it('PUBLIC_CHECKS_SECOND_LOOK=0 never asks the server', function () {
    [, $out, $calls] = pcs_run('second_look "/login renders" "HTTP 504" GET /login want_login; echo "FAIL=$FAIL CNM=$CNM"', [
        'PUBLIC_CHECKS_SECOND_LOOK' => '0', 'STUB_SSH_FIRST' => '200|', 'STUB_SSH_BODY' => 'Continue with Google Continue with X',
    ]);

    expect($out)->toContain('FAIL=1 CNM=0')->and($calls)->toBe(0);
});

it('⚠️ a batch check splits its failures: the paths the server proves fine are edge noise, the rest are real', function () {
    // the fake server says: good for /admin/a, bad for /admin/b — it answers by the path in the command it is sent
    $bin = pcs_bin();
    file_put_contents($bin.'/ssh', "#!/usr/bin/env bash\necho x >> \"\$STUB_SSH_LOG\"\ncase \"\$*\" in\n  *'/admin/a'*) printf '302|https://x.test/admin/login\\n';;\n  *) printf '500|\\n';;\nesac\n");
    chmod($bin.'/ssh', 0755);
    $log = $bin.'/ssh.log';
    touch($log);
    $run = function (string $paths) use ($bin, $log): string {
        $p = new Process(['bash', '-c', 'source '.escapeshellarg(base_path('ops/public-checks.sh')).'; second_look_many "anonymous /admin GETs → /admin/login" GET want_admin_redirect 25 '.$paths.'; echo "PASS=$PASS FAIL=$FAIL CNM=$CNM"'],
            base_path(), ['PATH' => $bin.':'.getenv('PATH'), 'PUBLIC_CHECKS_LIB_ONLY' => '1', 'STUB_SSH_LOG' => $log, 'HOME' => sys_get_temp_dir()]);
        $p->run();

        return $p->getOutput().$p->getErrorOutput();
    };

    $mixed = $run("'/admin/a(504→)' '/admin/b(504→)'");
    expect($mixed)->toContain('FAIL ✗')->toContain('/admin/b(504→)(server:500)')->toContain('edge-only: /admin/a(504→)')->toContain('PASS=0 FAIL=1 CNM=0');

    $allEdge = $run("'/admin/a(504→)'");
    expect($allEdge)->toContain('COULD NOT MEASURE')->toContain('the EDGE, not the site')->toContain('PASS=0 FAIL=0 CNM=1');

    // nothing failed here → a plain PASS, and the server is not asked at all
    file_put_contents($log, '');
    $none = $run('');
    expect($none)->toContain('PASS')->toContain('25 routes')->toContain('PASS=1 FAIL=0 CNM=0')
        ->and(trim((string) file_get_contents($log)))->toBe('');
});

it('what each check WANTS is the same predicate on this Mac\'s answer and the server\'s', function () {
    $body = sys_get_temp_dir().'/vy-pcs-body-'.bin2hex(random_bytes(3));
    file_put_contents($body, 'x');
    [, $out] = pcs_run(implode('; ', [
        'want_200 200 "" '.$body.' && echo a1',
        'want_200 504 "" '.$body.' || echo a2',
        'want_admin_redirect 302 "https://v.test/admin/login" '.$body.' && echo b1',
        'want_admin_redirect 302 "https://v.test/login" '.$body.' || echo b2',
        'want_admin_redirect 200 "" '.$body.' || echo b3',
        'want_not_served 405 "" '.$body.' && echo c1',
        'want_not_served 200 "" '.$body.' || echo c2',
        'want_not_served 500 "" '.$body.' || echo c3',
        'want_no_5xx 404 "" '.$body.' && echo d1',
        'want_no_5xx 502 "" '.$body.' || echo d2',
        'want_no_5xx 000 "" '.$body.' || echo d3',
        'EXPECT_HOSTS="accounts.google.com"; want_provider 302 "https://accounts.google.com/o/oauth2" '.$body.' && echo e1',
        'EXPECT_HOSTS="accounts.google.com"; want_provider 302 "https://evil.test/" '.$body.' || echo e2',
    ]));
    @unlink($body);

    foreach (['a1', 'a2', 'b1', 'b2', 'b3', 'c1', 'c2', 'c3', 'd1', 'd2', 'd3', 'e1', 'e2'] as $mark) {
        expect($out)->toContain($mark);
    }
});

it('the server it asks is the host ops/release.sh deploys to (a drift guard — two copies of three constants)', function () {
    $release = (string) file_get_contents(base_path('ops/release.sh'));
    preg_match('/HOST="([^"]+)"; PORT=(\d+); KEY="\$HOME\/(\.ssh\/[a-z_]+)"/', $release, $r);
    $checks = (string) file_get_contents(base_path('ops/public-checks.sh'));

    expect($r)->not->toBeEmpty()
        ->and($checks)->toContain('PUBLIC_CHECKS_SSH_HOST:-'.$r[1].'}')
        ->and($checks)->toContain('PUBLIC_CHECKS_SSH_PORT:-'.$r[2].'}')
        ->and($checks)->toContain('PUBLIC_CHECKS_SSH_KEY:-$HOME/'.$r[3].'}');
});

it('bash accepts the script and every check still reports in the three verdicts', function () {
    $p = new Process(['bash', '-n', base_path('ops/public-checks.sh')]);
    $p->run();
    expect($p->getExitCode())->toBe(0, $p->getErrorOutput());
});

// ── ⚠️ D-PROD-CSP (2026-11-14) — OUR Content-Security-Policy must ARRIVE WHOLE; the probe reads the four directives, never the bare presence of a header ──
/** Run `csp_has_all` against a header block written to a scratch file; returns [exit code of the function, output]. */
function pcs_csp(string $headers): array
{
    $file = tempnam(sys_get_temp_dir(), 'vy-hdr-');
    file_put_contents($file, $headers);
    [$code, $out] = pcs_run('csp_has_all '.escapeshellarg($file).'; echo "RESULT=$?"');
    @unlink($file);

    return [$out, (bool) preg_match('/RESULT=0\b/', $out)];
}

const PCS_OURS = "content-security-policy: default-src 'self'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'\r\n";

it('⚠️ passes when our policy arrives whole — even beside the host\'s own upgrade-insecure-requests header (a browser enforces both)', function () {
    [, $ok] = pcs_csp("HTTP/2 200\r\n".PCS_OURS."content-security-policy: upgrade-insecure-requests\r\nx-frame-options: SAMEORIGIN\r\n");

    expect($ok)->toBeTrue();
});

it('⚠️⚠️ fails on the October 2026 failure: our policy REPLACED by a bare upgrade-insecure-requests', function () {
    [, $ok] = pcs_csp("HTTP/2 200\r\ncontent-security-policy: upgrade-insecure-requests\r\nx-frame-options: SAMEORIGIN\r\n");

    expect($ok)->toBeFalse();
});

it('⚠️ fails when ANY one of the four directives is missing (each is named on its own)', function () {
    foreach (['frame-ancestors', 'form-action', 'base-uri', 'object-src'] as $missing) {
        $policy = trim(preg_replace('/;?\s*'.$missing.'[^;]*/', '', "default-src 'self'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'"), '; ');
        [, $ok] = pcs_csp("HTTP/2 200\r\ncontent-security-policy: {$policy}\r\n");

        expect($ok)->toBeFalse("a policy without {$missing} must not pass");
    }
});

it('does not count a Report-Only policy (it enforces nothing) and does not care about header-name case', function () {
    [, $reportOnly] = pcs_csp("HTTP/2 200\r\ncontent-security-policy-report-only: default-src 'self'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'\r\n");
    [, $shouting] = pcs_csp("HTTP/2 200\r\nContent-Security-Policy: default-src 'self'; Frame-Ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'\r\n");

    expect($reportOnly)->toBeFalse()->and($shouting)->toBeTrue();
});

it('says no when there is no headers file at all (an unreadable answer is never a pass)', function () {
    [, $out] = pcs_run('csp_has_all /nonexistent/headers; echo "RESULT=$?"');

    expect($out)->toContain('RESULT=1')->not->toContain('RESULT=0');
});
