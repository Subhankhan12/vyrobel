<?php

declare(strict_types=1);

namespace App\Services\Channex;

use App\Models\Channel;
use RuntimeException;

/**
 * In-memory, deterministic, inspectable {@see ChannelManagerClient} — what every test uses
 * (bound whenever `services.channex.fake` is true, the default). Never touches the network.
 *
 * Bound as a container singleton, so the same instance receives pushes made from inside a
 * queued job (tests run the queue in-process) and stays inspectable afterward via
 * `app(ChannelManagerClient::class)`.
 */
class FakeChannexClient implements ChannelManagerClient
{
    /** @var list<array{channel: Channel, snapshot: AriSnapshot}> */
    private array $pushes = [];

    private int $callCount = 0;

    /**
     * When > 0, pushAri() throws for calls 1..failUntilCall (inclusive), then succeeds —
     * lets tests exercise retry/backoff/dead-letter against a real failure mode.
     */
    public int $failUntilCall = 0;

    public function pushAri(Channel $channel, AriSnapshot $snapshot): void
    {
        $this->callCount++;

        if ($this->callCount <= $this->failUntilCall) {
            throw new RuntimeException("FakeChannexClient: simulated failure on call {$this->callCount}.");
        }

        $this->pushes[] = ['channel' => $channel, 'snapshot' => $snapshot];
    }

    /**
     * @return list<array{channel: Channel, snapshot: AriSnapshot}>
     */
    public function pushes(): array
    {
        return $this->pushes;
    }

    /**
     * @return list<array{channel: Channel, snapshot: AriSnapshot}>
     */
    public function pushesFor(string $roomTypeId): array
    {
        return array_values(array_filter(
            $this->pushes,
            fn (array $push): bool => $push['snapshot']->roomTypeId === $roomTypeId,
        ));
    }

    public function pushCount(): int
    {
        return count($this->pushes);
    }

    public function callCount(): int
    {
        return $this->callCount;
    }

    /**
     * @return array{channel: Channel, snapshot: AriSnapshot}|null
     */
    public function lastPush(): ?array
    {
        if ($this->pushes === []) {
            return null;
        }

        return $this->pushes[array_key_last($this->pushes)];
    }

    public function reset(): void
    {
        $this->pushes = [];
        $this->callCount = 0;
        $this->failUntilCall = 0;
    }
}
