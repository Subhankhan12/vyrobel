<?php

declare(strict_types=1);

namespace App\Support\Auth;

/** Every decision {@see SocialLinking} can reach. There is no default branch anywhere that consumes it. */
enum SocialLinkOutcome
{
    /** The identity is already linked. ⚠️ THE ONLY PATH THAT IS EVER SILENT. */
    case SignIn;

    /** Nothing matched and no account holds that address — send them to the org form to finish signing up. */
    case SignUp;

    /** ⚠️ An account already exists at that address. Sign in with the password first, then link. NEVER link here. */
    case RefuseEmailTaken;

    /** ⚠️ The provider says the address is unverified. Refuse to link AND refuse to create. */
    case RefuseUnverified;

    /** Linking from an authenticated session, and nothing else holds this identity. */
    case Link;

    /** Already linked to the signed-in account — a no-op, reported as success. */
    case AlreadyLinked;

    /** ⚠️ This Google account is linked to a DIFFERENT Vyrobel user. Refuse; never move a link silently. */
    case RefuseIdentityTaken;
}
