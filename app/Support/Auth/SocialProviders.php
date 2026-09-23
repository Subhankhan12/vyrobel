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
