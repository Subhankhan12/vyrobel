<?php

declare(strict_types=1);

namespace App\Services\Channex;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real REST client against Channex. Config-gated (`services.channex.fake = false`) and never
 * exercised by the test suite — bound only when explicitly opted into via config.
 *
 * ⚠️ UNVERIFIED AGAINST LIVE CHANNEX: this endpoint shape (`POST /properties/{id}/ari`) and
 * payload are a reasonable, honest guess at Channex's ARI API, not confirmed against their
 * docs or a staging account. Validating (and correcting) this against real Channex staging
 * credentials is the live-integration checkpoint called out in docs/DECISIONS.md — do not
 * treat this class as proven until that checkpoint runs.
 */
class ChannexClient implements ChannelManagerClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {}

    public function pushAri(Channel $channel, AriSnapshot $snapshot): void
    {
        if ($this->apiKey === '') {
            throw new RuntimeException(
                'ChannexClient has no API key configured (services.channex.api_key) — '
                .'refusing to push to a live channel. Set CHANNEX_API_KEY or keep CHANNEX_FAKE=true.'
            );
        }

        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->timeout(15)
            ->post("/properties/{$snapshot->externalPropertyId}/ari", $snapshot->toArray());

        // Any 4xx/5xx becomes a RequestException — bubbles up to AriPushJob, which retries via
        // the Step 1 backbone (backoff, then dead-letter on exhaustion).
        $response->throw();
    }
}
