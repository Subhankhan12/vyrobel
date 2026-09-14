<?php

declare(strict_types=1);

namespace App\Support\Auth;

/**
 * One authenticated assertion from an external identity provider — what the callback learned, before any
 * decision is taken about it.
 *
 * ⚠️ `$emailVerified` IS NOT AN IDENTITY CLAIM. It asserts the provider verified control of that mailbox.
 *    {@see SocialLinking} states where that distinction is decided and why it can never authorise a link.
 */
final readonly class SocialIdentity
{
    public function __construct(
        public string $provider,
        public string $providerUserId,
        public string $email,
        public bool $emailVerified,
        public string $name,
    ) {}
}
