<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Contracts\Auth\SocialIdentityProvider;
use App\Services\Auth\GoogleIdentityProvider;
use App\Services\Auth\TwitterIdentityProvider;

/**
 * ⚠️⚠️ THE ONE PLACE A PROVIDER KEY BECOMES A PROVIDER. Before this class the container held exactly one
 * binding — `SocialIdentityProvider` → Google — and the controller had no notion of WHICH provider it was
 * serving. That was correct while Google was the only door; it cannot survive a second one.
 *
 * ⚠️ A KEY THAT IS NOT IN {@see self::REGISTRY} RESOLVES TO NULL, AND THE CALLER 404s. There is deliberately
 *    no wildcard and no string interpolation into a class name: the set of providers is a closed list in
 *    source, so an unexpected key cannot reach the container at all.
 *
 * ⚠️⚠️ WHY GOOGLE RESOLVES THROUGH THE **CONTRACT** AND EVERYTHING ELSE THROUGH ITS **CLASS**, which looks
 *    asymmetric and is deliberate: the contract binding is the seam the 30 existing Google tests swap
 *    (`app()->instance(SocialIdentityProvider::class, $fake)`). Resolving Google through its concrete class
 *    would silently stop those swaps working — the tests would still pass, but they would be exercising the
 *    real provider instead of the fake, which is the worst kind of green. ⚠️ The asymmetry preserves what
 *    those tests prove; it is not an oversight.
 */
final class SocialProviders
{
    /** @var array<string, class-string<SocialIdentityProvider>> */
    private const REGISTRY = [
        GoogleIdentityProvider::KEY => GoogleIdentityProvider::class,
        TwitterIdentityProvider::KEY => TwitterIdentityProvider::class,
    ];

    /**
     * Providers whose address may CREATE an account even though the provider asserts no verification.
     *
     * ⚠️⚠️ THIS IS A FOUNDER RULING, NOT A PROPERTY OF THE PROVIDER, AND IT IS NAMED SO IT CAN BE ARGUED
     *    WITH. Made 2026-09-24, after the live API was measured: X returns a populated `confirmed_email`
     *    and NO verification signal of any kind. The field's NAME implies X confirmed it; an implication
     *    in a field name is not an assertion, so {@see TwitterIdentityProvider} still
     *    reports `emailVerified: false` honestly. This list — not a forged flag — is what permits signup.
     *
     * ⚠️⚠️ WHAT IT COSTS, RECORDED RATHER THAN GLOSSED: `users.email` is UNIQUE GLOBALLY, so one address
     *    is one account forever. Someone signing up through X with an address they do not own takes it
     *    PERMANENTLY, and its real owner can never register. Takeover of an EXISTING account is still
     *    closed — `RefuseEmailTaken` refuses a provider address that already matches one — so the live
     *    risk is squatting on an address nobody has claimed yet, not stealing one that is.
     *
     * ⚠️ GOOGLE IS DELIBERATELY ABSENT. It asserts verification itself, so it never needs this exception,
     *    and an UNVERIFIED Google identity is still refused exactly as it always was. Adding a provider
     *    here is a ruling; a test pins the list so it cannot grow by accident.
     *
     * @var list<string>
     */
    private const TRUSTED_FOR_SIGNUP = [
        TwitterIdentityProvider::KEY,
    ];

    /** ⚠️ Ruled, never inferred — see {@see self::TRUSTED_FOR_SIGNUP}. */
    public static function trustsAddressForSignup(string $key): bool
    {
        return in_array($key, self::TRUSTED_FOR_SIGNUP, true);
    }

    /** The provider's name as a person would read it, for UI copy that must not say "Google" for X. */
    public static function label(string $key): string
    {
        return match ($key) {
            GoogleIdentityProvider::KEY => 'Google',
            TwitterIdentityProvider::KEY => 'X',
            default => ucfirst($key),
        };
    }

    /** Every provider key this application knows about, configured or not. */
    public static function keys(): array
    {
        return array_keys(self::REGISTRY);
    }

    /** ⚠️ NULL for an unknown key — never a guess, never a dynamic class name. */
    public static function resolve(string $key): ?SocialIdentityProvider
    {
        $class = self::REGISTRY[$key] ?? null;

        if ($class === null) {
            return null;
        }

        return $key === GoogleIdentityProvider::KEY
            ? app(SocialIdentityProvider::class)   // ⚠️ the seam the existing tests swap — see the class note
            : app($class);
    }

    /**
     * The providers that are actually usable — configured credentials and nothing else.
     *
     * ⚠️ This is what a UI iterates. A provider with no credentials must not render a button, because a
     *    button that leads to an error page is worse than no button (the rule Google already ships under).
     *
     * @return array<string, SocialIdentityProvider>
     */
    public static function enabled(): array
    {
        $out = [];

        foreach (self::keys() as $key) {
            $provider = self::resolve($key);

            if ($provider !== null && $provider->enabled()) {
                $out[$key] = $provider;
            }
        }

        return $out;
    }
}
