<?php

use App\Support\Http\JsonErrorRoutes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

// D-GM-EMPTY's SWEEP (2026-11-12). This app renders errors as JSON ONLY under `/api` (`bootstrap/app.php` `shouldRenderJsonWhen`), so a JSON endpoint that
// lives anywhere else and calls `$request->validate()` answers a bad call with a REDIRECT, whatever Accept header the caller sends — the guest lookup did
// exactly that to a blank email on the live site. The public booking engine's machine contract (`/book/{property}/search|quote|book|confirmation`) is the
// other place in the app that answers JSON outside `/api`: it must answer a bad call with a 422 and the field errors, and a missing thing with a JSON 404.

beforeEach(fn () => flushQueue());
afterEach(fn () => cleanupCommittedTenantData());

it('answers a search with no dates as a 422 with the field errors, not a redirect', function () {
    $a = publicPropertyScenario(units: 1);

    test()->getJson("/book/{$a['property']->slug}/search")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['check_in', 'check_out']);
});

it('answers a quote with nothing in it as a 422 with the field errors, not a redirect', function () {
    $a = publicPropertyScenario(units: 1);

    test()->postJson("/book/{$a['property']->slug}/quote", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['room_type_id', 'rate_plan_id', 'check_in', 'check_out']);
});

it('answers a booking with nothing in it as a 422 with the field errors, not a redirect', function () {
    $a = publicPropertyScenario(units: 1);

    test()->postJson("/book/{$a['property']->slug}/book", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['quote_id', 'payment_token', 'email']);
});

it('answers an unknown quote as a JSON 404, not an HTML page', function () {
    $a = publicPropertyScenario(units: 1);

    test()->getJson("/book/{$a['property']->slug}/confirmation/does-not-exist")
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('still answers a browser form on the same prefix with a redirect (the checkout form is not JSON)', function () {
    $a = publicPropertyScenario(units: 1);

    // The checkout's POST is a page form: a bad call goes back with its errors, as it always did.
    test()->from("/book/{$a['property']->slug}/checkout")
        ->post("/book/{$a['property']->slug}/checkout", [])
        ->assertRedirect();
});

it('lists every JSON endpoint outside /api that calls validate() in JsonErrorRoutes — a new one cannot be added and forgotten', function () {
    $offenders = [];
    $seen = 0;

    foreach (Route::getRoutes() as $route) {
        if (str_starts_with($route->uri(), 'api/') || ! $route->getControllerClass()) {
            continue;
        }

        // An invokable controller's "action method" is its own class name — its entry point is `__invoke`.
        $class = $route->getControllerClass();
        $method = new ReflectionMethod($class, method_exists($class, $route->getActionMethod()) ? $route->getActionMethod() : '__invoke');
        $returns = $method->getReturnType();

        // Only an endpoint that DECLARES it answers JSON — a page action returns a view or a redirect and is out of this rule.
        if (! $returns instanceof ReflectionNamedType || ltrim($returns->getName(), '\\') !== JsonResponse::class) {
            continue;
        }
        $seen++;

        // Tokenised, comments skipped: a docblock that explains the trap names `validate()` and must not count as a call.
        $lines = array_slice(file($method->getFileName()), $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1);
        $tokens = array_values(array_filter(
            token_get_all('<?php '.implode('', $lines)),
            fn ($t) => ! is_array($t) || ! in_array($t[0], [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE], true),
        ));
        $callsValidate = false;
        foreach ($tokens as $i => $t) {
            if (is_array($t) && $t[0] === T_STRING && $t[1] === 'validate' && isset($tokens[$i - 1]) && is_array($tokens[$i - 1]) && $tokens[$i - 1][0] === T_OBJECT_OPERATOR) {
                $callsValidate = true;
            }
        }

        if ($callsValidate && ! in_array($route->getName(), JsonErrorRoutes::NAMES, true)) {
            $offenders[] = $route->getName().' → '.$method->class.'::'.$method->name;
        }
    }

    // The scan must see something, or a green result says nothing (the guest lookup, the team chat, the booking engine, the passkeys …).
    expect($seen)->toBeGreaterThan(10)
        ->and($offenders)->toBe([]);
});
