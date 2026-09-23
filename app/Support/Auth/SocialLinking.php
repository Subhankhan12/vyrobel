<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\User;
use App\Models\UserIdentity;

/**
 * ⚠️⚠️ THE SECURITY CORE OF SOCIAL SIGN-IN. Not the button — this.
 *
 * ⚠️⚠️ THE RULE: a returning user is matched on `(provider, provider_user_id)`. NEVER on email.
 *    An email address is a HINT THAT REQUIRES PROOF; it is not an identity. If a Google identity were
 *    matched to an existing account by address alone, then anyone who controls a Google account at that
 *    address would own the Vyrobel account — including any address the user once held at a domain they no
 *    longer control, and any address a Workspace administrator can create inside their own domain.
 *
 * ⚠️⚠️ WHERE `email_verified` IS DECIDED, AND WHY IT CANNOT AUTHORISE A LINK. `email_verified: true` asserts
 *    that the provider verified control of that MAILBOX. It says nothing about whether this human is the
 *    person who holds the Vyrobel account at that address. A Google Workspace administrator can create an
 *    account at any address in their domain, so the claim proves MAILBOX CONTROL, NEVER CONTINUITY OF
 *    PERSON. Therefore:
 *      • it MAY be trusted to skip a verification email when CREATING a new account (nothing exists to take
 *        over, and the provider has done the same work our own verification email would have done);
 *      • it MUST NEVER be sufficient to attach an identity to an ACCOUNT THAT ALREADY EXISTS.
 *    Both halves of that are implemented below, in {@see self::forSignIn()}.
 *
 * ⚠️ THIS CLASS DECIDES AND DOES NOT ACT. It performs no writes, signs nobody in, and has no side effects,
 *    so the decision can be tested directly and cannot differ from the one the controller then carries out.
 */
final class SocialLinking
{
    /**
     * The unauthenticated path: someone pressed "Continue with Google" on the login or signup page.
     *
     * @return array{outcome: SocialLinkOutcome, user: ?User}
     */
    public static function forSignIn(SocialIdentity $identity): array
    {
        // ① ALREADY LINKED → sign in. ⚠️ Matched on the provider's identifier, never the address, and this
        //    is the only outcome that is allowed to be silent.
        $link = self::find($identity);

        if ($link !== null) {
            return ['outcome' => SocialLinkOutcome::SignIn, 'user' => $link->user];
        }

        // ② ⚠️⚠️ UNVERIFIED → REFUSE TO CREATE. An unverified address from any provider is worthless for
        //    minting an account: users.email is globally unique, so someone claiming an address they never
        //    held would lock its real owner out permanently.
        // ⚠️ IT NO LONGER REFUSES TO *LINK* — that half moved to forLink() on 2026-09-24 by a founder
        //    ruling, where the note explains why an authenticated session makes the flag redundant. This
        //    half is the one that must never move again, and a mutation removing it reddens three tests.
        if (! $identity->emailVerified) {
            return ['outcome' => SocialLinkOutcome::RefuseUnverified, 'user' => null];
        }

        // ③ ⚠️⚠️ THE TAKEOVER CASE — THE ONE THAT MUST NOT BE "HELPFUL". An account already exists at this
        //    address and this Google identity is not linked to it. DO NOT LINK. The user signs in with their
        //    password first and links from their account page, where being signed in IS the proof that the
        //    address alone cannot supply. See the class note on why `email_verified` does not change this.
        if (self::accountExistsAt($identity->email)) {
            return ['outcome' => SocialLinkOutcome::RefuseEmailTaken, 'user' => null];
        }

        // ④ Nothing matched, nothing to take over. Verified address, so no separate verification email is
        //    needed — but this is a SIGNUP, not an account: see the controller for what still has to happen.
        return ['outcome' => SocialLinkOutcome::SignUp, 'user' => null];
    }

    /**
     * The authenticated path: a signed-in user is attaching a provider from their account page.
     *
     * ⚠️ The provider's address is NOT required to match the account's address here, and that is deliberate —
     *    a person may hold a Google account under a different address, and an authenticated session is
     *    already stronger proof than any address comparison could be.
     */
    public static function forLink(User $actor, SocialIdentity $identity): SocialLinkOutcome
    {
        // The emailVerified check was MOVED out of this method on 2026-09-24 by a founder ruling, not
        // deleted: it still stands unweakened in forSignIn(), which is the only place it has a job —
        // deciding whether an address may CREATE an account.
        //
        // Why it had no job here: the flag answers "does this person own this mailbox?", and on this path
        // that is already answered without the provider. The actor arrived inside an authenticated
        // session (proving they own the Vyrobel account) and completed the provider flow (proving they
        // own the provider account). The address resolves nothing here; it is stored for display only.
        //
        // What it was actually blocking: X returns an address but asserts nothing about it, so every X
        // identity is honestly emailVerified=false. With the check here X could not be linked, could not
        // create, and could only sign in if already linked - which nothing could make it. A rule written
        // for account creation was silently switching off an entire provider.
        //
        // What is NOT relaxed: an identity already attached to another user is still refused below, and
        // RefuseEmailTaken in forSignIn() still refuses a provider address matching an existing account.
        $link = self::find($identity);

        if ($link !== null) {
            // ⚠️ Linked to someone else → REFUSE. Silently re-pointing an existing link would hand one
            //    person's Google account the ability to sign in as another.
            return (string) $link->user_id === (string) $actor->getKey()
                ? SocialLinkOutcome::AlreadyLinked
                : SocialLinkOutcome::RefuseIdentityTaken;
        }

        return SocialLinkOutcome::Link;
    }

    /** ⚠️ THE ONE LOOKUP. Both entry points resolve an identity through here, so neither can drift to email. */
    private static function find(SocialIdentity $identity): ?UserIdentity
    {
        return UserIdentity::query()
            ->with('user')
            ->where('provider', $identity->provider)
            ->where('provider_user_id', $identity->providerUserId)
            ->first();
    }

    /**
     * ⚠️ USED ONLY TO REFUSE, NEVER TO RESOLVE. This is the sole place an email is compared to an account,
     *    and its answer can only ever stop the flow — it can never select a user to sign in as.
     */
    private static function accountExistsAt(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }
}
