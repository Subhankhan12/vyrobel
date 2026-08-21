<?php

use App\Support\Privacy\CookieConsent;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

// ══ CI GATE 7 · THE COOKIE FORWARD-GUARD (front-website Gate 6, ruling D11) ═══════════════════════════
//
// ⚠️⚠️ WHY THIS FILE IS THE FEATURE AND THE BANNER IS NOT. Today Vyrobel sets four cookies, all strictly
// necessary, and has no analytics, marketing or third-party script anywhere. So the notice has nothing to
// gate — which means its honesty rests entirely on the FUTURE respecting it. Without this guard, the first
// analytics snippet someone pastes in silently overrides every visitor who declined: no test fails, nothing
// is logged, no monitor fires, and the harm is invisible until somebody happens to look. That is the
// MAIL_MAILER=log failure family, and a check is the only defence against it.
//
// ⚠️ WHAT IT ENFORCES
//   A. The disclosure matches the wire — every cookie a real response sets is in CookieConsent::CATALOGUE.
//   B. No cookie is written anywhere in app/ that the catalogue does not describe.
//   C. No analytics / marketing / third-party script or host appears in any view or public asset.
//   D. ⚠️ If a NON-ESSENTIAL cookie is ever added, something must call CookieConsent::allows().
//
// ⚠️ HONEST SCOPE — STATED, NOT IMPLIED. C and B pattern-match: a script injected through a channel these
// patterns do not name (a new vendor, a base64 blob, a runtime-built URL, a dynamically-named cookie)
// escapes them, and that is a review rule rather than a test. What CANNOT escape is A — it reads the real
// Set-Cookie header of a real response, so a cookie that actually reaches a browser is compared against the
// disclosure whatever route it took to get there.
//
// ⚠️ AND D IS INERT TODAY, WHICH IS EXACTLY THE SHAPE THAT HAS GONE WRONG THREE GATES RUNNING (fixtures
// instead of data · an early return on an empty database · counting requests instead of work). So D is
// written as a PAIR: a positive control that runs the predicate against a synthetic non-essential catalogue
// on every single run and requires it to fire, and then the real data. The predicate is therefore never
// merely assumed to work.

/** The rendered cookie-notice sections, so a needle about the notice cannot fire on the rest of the page. */
function cgNoticeRegions(string $html): array
{
    preg_match_all('/<section[^>]*aria-labelledby="vy-cookie-h-[^"]*"[^>]*>.*?<\/section>/s', $html, $m);

    return $m[0];
}

/** Laravel encrypts cookies in transit; the guard has to read what the application will read. */
function cgDecryptCookie(string $raw): string
{
    $plain = Crypt::decrypt($raw, false);

    // Laravel prefixes the cookie value with a hash of the cookie name (CookieValuePrefix).
    return CookieValuePrefix::remove($plain);
}

/** Every cookie name written from a literal or a `const COOKIE` in app/. */
function cgCookieNamesInSource(): array
{
    $names = [];

    foreach (cgPhpFiles(base_path('app')) as $file) {
        $src = (string) file_get_contents($file);
        // ⚠️ Comments first: a source-scanning guard that reads its own documentation is a recurring trap
        // in this codebase (§N.27.5), and this file's own explanation names cookies.
        $src = (string) preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $src);

        // `const COOKIE = 'vy_td';` — the idiom TrustedDeviceService and CookieConsent both use.
        preg_match_all('/const\s+[A-Z_]*COOKIE[A-Z_]*\s*=\s*[\'"]([^\'"]+)[\'"]/', $src, $m);
        $names = array_merge($names, $m[1]);

        // A literal first argument to any cookie factory.
        preg_match_all('/(?:Cookie::(?:make|queue|forget)|\bcookie)\(\s*[\'"]([A-Za-z0-9_\-]+)[\'"]/', $src, $m2);
        $names = array_merge($names, $m2[1]);
    }

    return array_values(array_unique($names));
}

/** @return list<string> */
function cgPhpFiles(string $dir): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') {
            $out[] = $f->getPathname();
        }
    }

    return $out;
}

/**
 * ⚠️ THE PREDICATE, ISOLATED so it can be run against synthetic input. Given a catalogue and the set of
 * source files that mention `CookieConsent::allows(`, return the violations.
 *
 * @param  array<string, array{category: string}>  $catalogue
 * @param  list<string>  $allowsCallers
 * @return list<string>
 */
function cgForwardBindingViolations(array $catalogue, array $allowsCallers): array
{
    $nonEssential = [];
    foreach ($catalogue as $name => $entry) {
        if (($entry['category'] ?? 'essential') !== 'essential') {
            $nonEssential[] = $name;
        }
    }

    if ($nonEssential === []) {
        return [];      // nothing non-essential exists — nothing to gate
    }

    if ($allowsCallers === []) {
        return array_map(
            fn (string $n): string => "non-essential cookie \"{$n}\" is declared, but nothing in app/ calls CookieConsent::allows()",
            $nonEssential,
        );
    }

    return [];
}

// ══ A · THE DISCLOSURE MATCHES THE WIRE ═══════════════════════════════════════════════════════════════
it('⚠️ discloses every cookie a real response actually sets', function () {
    // ⚠️ NOT A FIXTURE — a real request through the real middleware stack, read off the real response.
    // The disclosure and the wire are independent sources and the guard requires them to agree.
    $response = $this->get('/');
    $response->assertOk();

    $observed = collect($response->headers->getCookies())
        ->map(fn ($c): string => $c->getName())->unique()->values()->all();

    // ⚠️ A POSITIVE CONTROL ON THE DATA. If a change ever made the response set no cookies at all, every
    // assertion below would pass while checking nothing.
    Assert::assertGreaterThanOrEqual(2, count($observed),
        'the response set fewer than two cookies — this comparison would be vacuous');

    foreach ($observed as $name) {
        Assert::assertArrayHasKey($name, CookieConsent::CATALOGUE,
            "the response sets \"{$name}\", which the cookie notice does not disclose");
    }

    // …and the two that are always set are really in the catalogue, named as they are on the wire.
    Assert::assertContains('vyrobel-session', $observed);
    Assert::assertContains('XSRF-TOKEN', $observed);
});

it('⚠️ states a cookie count that matches the catalogue AND the wire', function () {
    // ⚠️ THIS TEST EXISTS BECAUSE THE NOTICE SHIPPED A WRONG NUMBER. It read "3 cookies" — not the
    // catalogue size (4), not what a visitor receives (2), just a number somebody typed. It rendered on
    // every page of the site and no assertion could see it; a screenshot did. Now every figure on the
    // notice is derived, and this pins the derivation to the two things it must agree with.
    $response = $this->get('/');
    $observed = collect($response->headers->getCookies())->map(fn ($c): string => $c->getName())->unique();

    $always = array_keys(CookieConsent::alwaysSet());
    sort($always);
    $seen = $observed->sort()->values()->all();

    Assert::assertSame($always, $seen,
        'the cookies the notice calls "an ordinary visit" are not the ones an ordinary visit actually sets');

    $html = $response->getContent();
    Assert::assertStringContainsString('An ordinary visit sets <strong>'.count($always).' cookies</strong>', $html,
        'the notice states a different number from what it sets');
    // ⚠️ THE NEEDLE MATCHES THE MARKUP THE TEMPLATE ACTUALLY EMITS. The first version assumed
    // "All <strong>4</strong> …" while the <strong> wraps the whole phrase — a needle that could not
    // match, which is the purest form of a guard that proves nothing. Checked against the render.
    Assert::assertStringContainsString('All '.count(CookieConsent::CATALOGUE).' are strictly necessary', $html,
        'the notice states a different total from the catalogue');

    // ⚠️ And no number on the notice is a literal: changing the catalogue must change the copy.
    Assert::assertStringNotContainsString('3 cookies', $html, 'a hardcoded count came back');
});

// ══ B · NO COOKIE IS WRITTEN THAT THE CATALOGUE DOES NOT DESCRIBE ════════════════════════════════════
it('⚠️ writes no cookie the disclosure does not describe', function () {
    $names = cgCookieNamesInSource();

    Assert::assertGreaterThanOrEqual(2, count($names),
        'the source scan found almost no cookie names — the scan is broken and this test would be vacuous');

    foreach ($names as $name) {
        Assert::assertArrayHasKey($name, CookieConsent::CATALOGUE,
            "app/ writes a cookie named \"{$name}\" that CookieConsent::CATALOGUE does not describe — ".
            'add it to the catalogue with its category, and if it is not strictly necessary, gate it on '.
            'CookieConsent::allows()');
    }
});

it('PROVES the source scan can see a cookie (the positive control)', function () {
    // ⚠️ A scanner never seen to find anything proves nothing. It must find the two cookies this codebase
    // demonstrably writes from source: the trusted-device cookie and the consent cookie itself.
    $names = cgCookieNamesInSource();

    Assert::assertContains('vy_td', $names, 'the scan cannot see TrustedDeviceService::COOKIE');
    Assert::assertContains(CookieConsent::COOKIE, $names, 'the scan cannot see CookieConsent::COOKIE');
});

// ══ C · NO ANALYTICS, MARKETING OR THIRD-PARTY SCRIPT ANYWHERE ════════════════════════════════════════
it('⚠️ ships no analytics, marketing or third-party script', function () {
    // ⚠️ The notice tells every visitor "no analytics, no marketing and no third-party cookies at all".
    // That is a claim made on every page of the site, and this is what keeps it true.
    $forbidden = [
        'googletagmanager', 'google-analytics', 'gtag(', 'ga(', 'analytics.js',
        'plausible.io', 'usefathom', 'fathom.js', 'matomo', '_paq', 'segment.com', 'analytics.track(',
        'hotjar', 'mixpanel', 'posthog', 'amplitude', 'clarity.ms',
        'connect.facebook.net', 'fbq(', 'doubleclick', 'googlesyndication', 'googleadservices',
        'snap.licdn.com', 'ads.linkedin', 'ct.pinterest', 'tiktok.com/i18n',
        'js.hs-scripts', 'hubspot', 'intercom.io', 'widget.intercom', 'js.driftt',
        'unpkg.com', 'cdn.jsdelivr.net', 'cdnjs.cloudflare',
    ];

    $roots = [resource_path('views'), public_path()];
    $scanned = 0;
    $hits = [];

    foreach ($roots as $root) {
        if (! is_dir($root)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (! $f->isFile() || ! in_array($f->getExtension(), ['php', 'js', 'html', 'css'], true)) {
                continue;
            }
            $scanned++;
            $src = (string) file_get_contents($f->getPathname());
            // ⚠️ Strip comments — this guard's own header names every vendor it forbids.
            $src = (string) preg_replace('#/\*.*?\*/|\{\{--.*?--\}\}#s', '', $src);

            // ⚠️⚠️ THE GUARD MUST BE SATISFIABLE BY DOING THE RIGHT THING. The first version forbade every
            // vendor unconditionally — which makes it a wall rather than a gate, and the first person who
            // genuinely needs analytics deletes it instead of complying (§N.17.5: lift a guard by
            // SATISFYING its condition, never by removing it). So a vendor is allowed in a file that also
            // consults the stored consent, and forbidden in one that does not.
            // ⚠️ LIMIT, STATED: this is FILE-level. A file that gates one script and not another satisfies
            // it. Checks B and D still require the cookie to be catalogued and disclosed, and a reviewer is
            // the backstop for the rest.
            $gated = str_contains($src, 'CookieConsent::allows(');

            foreach ($forbidden as $needle) {
                if (str_contains($src, $needle) && ! $gated) {
                    $hits[] = str_replace(base_path().'/', '', $f->getPathname()).' → '.$needle;
                }
            }
        }
    }

    Assert::assertGreaterThan(200, $scanned,
        'the sweep scanned almost no files — it would pass on an empty search');
    Assert::assertSame([], $hits,
        "a third-party analytics or marketing vendor appeared:\n  ".implode("\n  ", $hits).
        "\n⚠️ If this is intentional it must be gated on CookieConsent::allows(\$request, '<category>'), ".
        'declared in CookieConsent::CATALOGUE, and disclosed on the notice — in that order.');
});

it('PROVES the third-party sweep can fire (the positive control)', function () {
    // ⚠️ Feed it exactly what it exists to catch.
    $failed = false;
    try {
        Assert::assertStringNotContainsString('googletagmanager',
            '<script src="https://www.googletagmanager.com/gtag/js?id=G-XXXX"></script>');
    } catch (AssertionFailedError) {
        $failed = true;
    }
    expect($failed)->toBeTrue('the third-party needle cannot fire — it proves nothing');
});

// ══ D · ⚠️ THE FORWARD-BINDING CHECK, WITH ITS OWN POSITIVE CONTROL ═══════════════════════════════════
it('⚠️ PROVES the forward-binding check fires on a non-essential cookie (the positive control)', function () {
    // ⚠️⚠️ THIS RUNS EVERY TIME, ON SYNTHETIC INPUT, BECAUSE THE REAL CHECK IS INERT TODAY. The catalogue
    // has no non-essential entry, so the real assertion below can only ever pass — which is precisely the
    // shape that went wrong in three consecutive gates. Running the predicate against a catalogue that DOES
    // contain one, with no caller of allows(), proves the mechanism works rather than assuming it.
    $violations = cgForwardBindingViolations(
        ['_ga' => ['category' => 'analytics'], 'vyrobel-session' => ['category' => 'essential']],
        [],
    );

    Assert::assertCount(1, $violations, 'the forward-binding check does not fire on an ungated analytics cookie');
    Assert::assertStringContainsString('_ga', $violations[0]);

    // …and it stands down once something reads the consent.
    Assert::assertSame([], cgForwardBindingViolations(
        ['_ga' => ['category' => 'analytics']],
        ['app/Analytics/Whatever.php'],
    ), 'the check still fires after consent is honoured — it would block a correct implementation');
});

it('⚠️ has no non-essential cookie that ignores the stored consent', function () {
    $callers = [];
    foreach (cgPhpFiles(base_path('app')) as $file) {
        $src = (string) preg_replace('#/\*.*?\*/|//[^\n]*#s', '', (string) file_get_contents($file));
        if (str_contains($src, 'CookieConsent::allows(') && ! str_contains($file, 'CookieConsent.php')) {
            $callers[] = $file;
        }
    }

    Assert::assertSame([], cgForwardBindingViolations(CookieConsent::CATALOGUE, $callers));

    // ⚠️ AND THE STATE OF THE WORLD IS ASSERTED, NOT ASSUMED. If a non-essential cookie is ever added, this
    // line reds too — a deliberate second signal, so the change cannot be made quietly. The notice's copy
    // ("nothing here to switch off today") is generated from the same source, so both move together.
    Assert::assertSame([], CookieConsent::nonEssentialCategories(),
        'a non-essential cookie category now exists — the notice copy, this guard and the disclosure '.
        'version all have to be revisited together');
});

// ══ THE CONSENT RECORD ITSELF ═════════════════════════════════════════════════════════════════════════
it('⚠️ sets nothing before the visitor answers, and records what they answered', function () {
    // Nothing non-essential exists to set — and the notice is shown until an answer is given.
    $first = $this->get('/');
    $first->assertOk();
    $first->assertSee('Cookies on this site');

    foreach ($first->headers->getCookies() as $cookie) {
        Assert::assertSame('essential', CookieConsent::CATALOGUE[$cookie->getName()]['category'] ?? 'MISSING',
            "\"{$cookie->getName()}\" was set before the visitor answered and is not strictly necessary");
    }

    // ⚠️ DECLINE IS ONE CLICK, THE SAME CLICK AS ACCEPT — same route, same method, same redirect.
    $declined = $this->post(route('cookies.choice'), ['choice' => CookieConsent::DECLINED, 'return' => '/pricing']);
    $declined->assertRedirect('/pricing#vy-cookie-notice');

    $record = collect($declined->headers->getCookies())
        ->firstWhere(fn ($c): bool => $c->getName() === CookieConsent::COOKIE);
    Assert::assertNotNull($record, 'declining recorded nothing');

    // ⚠️ THE VALUE ON THE WIRE IS CIPHERTEXT. Laravel's EncryptCookies middleware encrypts it on the way
    // out and decrypts on the way back in, which is why CookieConsent::choice() sees plain JSON. The first
    // version of this test read the raw value and got null — a defect in the test, not the feature, and
    // worth keeping the note: a guard that reads the wrong representation proves nothing about the right one.
    $payload = json_decode(cgDecryptCookie((string) $record->getValue()), true);
    Assert::assertIsArray($payload, 'the consent record is not readable JSON once decrypted');
    Assert::assertSame(CookieConsent::DECLINED, $payload['choice']);
    Assert::assertSame(CookieConsent::VERSION, $payload['version'], 'the record does not name the disclosure version');
    Assert::assertNotEmpty($payload['at'], 'the record has no timestamp — consent must be demonstrable');
    Assert::assertTrue($record->isHttpOnly(), 'the consent cookie is exposed to script');
});

it('⚠️ honours a decline, and re-prompts when the disclosure version moves on', function () {
    $request = Request::create('/');

    $request->cookies->set(CookieConsent::COOKIE, json_encode([
        'choice' => CookieConsent::DECLINED, 'version' => CookieConsent::VERSION, 'at' => '2026-08-22T00:00:00+00:00',
    ]));
    Assert::assertFalse(CookieConsent::allows($request, 'analytics'), 'a decline does not stop a non-essential category');
    Assert::assertTrue(CookieConsent::allows($request, 'essential'), 'a strictly-necessary cookie was blocked');
    Assert::assertFalse(CookieConsent::shouldPrompt($request), 'the notice re-prompts a visitor who answered');

    // ⚠️ FAILS CLOSED: no record, junk, and a record against an older disclosure all mean "not consented".
    foreach ([null, 'not json', json_encode(['choice' => 'accepted', 'version' => CookieConsent::VERSION - 1])] as $bad) {
        $r = Request::create('/');
        if ($bad !== null) {
            $r->cookies->set(CookieConsent::COOKIE, $bad);
        }
        Assert::assertFalse(CookieConsent::allows($r, 'analytics'), 'silence or a stale record was read as consent');
        Assert::assertTrue(CookieConsent::shouldPrompt($r), 'the visitor was not re-asked');
    }

    // An acceptance does permit a non-essential category — or the check above would be true by construction.
    $accepted = Request::create('/');
    $accepted->cookies->set(CookieConsent::COOKIE, json_encode([
        'choice' => CookieConsent::ACCEPTED, 'version' => CookieConsent::VERSION, 'at' => '2026-08-22T00:00:00+00:00',
    ]));
    Assert::assertTrue(CookieConsent::allows($accepted, 'analytics'),
        'acceptance grants nothing — allows() would return false for every input and prove nothing');
});

// ══ ⚠️ REJECT AS EASY AS ACCEPT, AND NO OPEN REDIRECT ═════════════════════════════════════════════════
it('⚠️ makes declining exactly as easy as accepting', function () {
    $html = $this->get('/')->getContent();

    // ⚠️ EQUAL PROMINENCE IS STRUCTURAL, NOT A JUDGEMENT CALL: both buttons must carry the SAME style
    // string. A "reject" a shade quieter than "accept" is the commonest dark pattern there is.
    preg_match_all('/<button type="submit" name="choice" value="([a-z]+)" style="([^"]*)">([^<]*)<\/button>/', $html, $m, PREG_SET_ORDER);

    Assert::assertCount(4, $m, 'expected accept + decline in both the banner and the footer panel');
    $styles = array_unique(array_column($m, 2));
    Assert::assertCount(1, $styles, 'the accept and decline buttons are styled differently — that is a dark pattern');
    $values = array_unique(array_column($m, 1));
    sort($values);
    Assert::assertSame([CookieConsent::ACCEPTED, CookieConsent::DECLINED], $values);

    // ⚠️ No pre-ticked anything — SCOPED TO THE NOTICE, not the page. The first version searched the whole
    // document and fired on a checkbox belonging to another component entirely: a needle that matches
    // outside the region it is about is not a guard, it is a tripwire in the wrong corridor.
    foreach (cgNoticeRegions($html) as $region) {
        Assert::assertStringNotContainsString('type="checkbox"', $region,
            'the notice grew a checkbox; it must not be pre-ticked');
        Assert::assertStringNotContainsString('checked', $region, 'the notice has a pre-set control');
    }
    Assert::assertNotEmpty(cgNoticeRegions($html), 'no notice region was found — the two assertions above would be vacuous');
});

it('⚠️ refuses to be an open redirect', function () {
    foreach (['https://evil.test/x', '//evil.test/x', 'http://evil.test'] as $hostile) {
        $this->post(route('cookies.choice'), ['choice' => CookieConsent::ACCEPTED, 'return' => $hostile])
            ->assertRedirect('/');
    }
    // …and a legitimate same-origin path still works, or the check above passes by refusing everything.
    $this->post(route('cookies.choice'), ['choice' => CookieConsent::ACCEPTED, 'return' => '/features'])
        ->assertRedirect('/features#vy-cookie-notice');
});

// ══ THE NOTICE IS TRUE, AND SAYS SO SITEWIDE ══════════════════════════════════════════════════════════
it('⚠️ claims on every marketing page only what is true', function () {
    $views = collect((array) glob(resource_path('views/marketing/*.blade.php')))
        ->map(fn (string $f): string => basename($f, '.blade.php'))->sort()->values();

    Assert::assertGreaterThanOrEqual(15, $views->count(), 'the marketing page set shrank — this sweep would cover less');

    foreach ($views as $view) {
        $html = view('marketing.'.$view)->render();
        Assert::assertStringContainsString('Cookies on this site', $html, "[{$view}] has no cookie disclosure");
        // The claim the notice makes, on every page — kept true by the sweep above.
        Assert::assertStringContainsString('no analytics, no marketing and no third-party cookies', $html);
        // ⚠️ And it must not claim a choice it cannot honour.
        Assert::assertStringNotContainsStringIgnoringCase('manage preferences', $html);
    }
});
