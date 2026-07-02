<?php

declare(strict_types=1);

namespace App\Services\Channex;

use App\Models\Channel;

/**
 * The channel-manager seam (Channex at launch, per the buy-vs-build decision). Bound to
 * {@see FakeChannexClient} (in-memory, no network — every test) or {@see ChannexClient} (real
 * REST, config-gated) in `AppServiceProvider::register()` via `services.channex.fake`.
 */
interface ChannelManagerClient
{
    /**
     * Push the current full ARI (availability/rate/restriction) state for one room_type + date
     * range to the channel. Last-write-wins — implementations replace, never diff.
     *
     * @throws \Throwable on any failure — the caller (AriPushJob, via the Step 1 reliability
     *                    backbone) is responsible for retry/backoff/dead-letter.
     */
    public function pushAri(Channel $channel, AriSnapshot $snapshot): void;
}
