<?php

declare(strict_types=1);

namespace App\Services\Channex;

use Illuminate\Support\Facades\Config;

/**
 * Validates INBOUND Channex webhook signatures (Step 4). HMAC-SHA256 over the raw request body,
 * keyed by the shared `services.channex.webhook_secret`, compared in constant time.
 *
 * ⚠️ DOCUMENTED GUESS, unverified against live Channex: the exact header name and HMAC scheme
 * are a reasonable assumption, exercised only against simulated payloads in tests — the same
 * live-integration checkpoint that covers the ARI request shape (see docs/DECISIONS.md). Fails
 * CLOSED: no configured secret ⇒ every webhook is rejected (never accept unauthenticated writes).
 */
class ChannexWebhookSignature
{
    public const HEADER = 'X-Channex-Signature';

    public function verify(string $rawBody, ?string $providedSignature): bool
    {
        $secret = Config::get('services.channex.webhook_secret');

        if (! is_string($secret) || $secret === '' || $providedSignature === null || $providedSignature === '') {
            return false;
        }

        return hash_equals($this->sign($rawBody, $secret), $providedSignature);
    }

    /**
     * Compute the signature for a body (used by the verifier and by tests to sign simulated
     * payloads — there is no other production caller).
     */
    public function sign(string $rawBody, string $secret): string
    {
        return hash_hmac('sha256', $rawBody, $secret);
    }
}
