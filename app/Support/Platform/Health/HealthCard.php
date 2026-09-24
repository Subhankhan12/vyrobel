<?php

declare(strict_types=1);

namespace App\Support\Platform\Health;

use Throwable;

/**
 * One card on the "nothing is silent" health board.
 *
 * ⚠️⚠️ FOUR STATES, NEVER THREE. GREY — "could not measure" — is its own verdict and is NEVER folded into green.
 * The exposure battery once scored itself perfect twice by treating "no answer" as "fine"; a card that could not
 * look says so, in grey, with the reason.
 */
final class HealthCard
{
    public const GREEN = 'green';

    public const AMBER = 'amber';

    public const RED = 'red';

    public const GREY = 'grey';

    /**
     * @param  list<string>  $lines  supporting facts — ⚠️ never a secret or config VALUE
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $status,
        public readonly string $headline,
        public readonly array $lines = [],
        public readonly ?string $note = null,
    ) {}

    /** A card whose measurement itself failed — grey, with the reason, never green. */
    public static function couldNotMeasure(string $key, string $title, string $why): self
    {
        return new self($key, $title, self::GREY, 'Could not measure', [$why]);
    }

    public static function fromFailure(string $key, string $title, Throwable $e): self
    {
        return self::couldNotMeasure($key, $title, 'The check itself failed: '.class_basename($e).'.');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::GREEN => 'OK',
            self::AMBER => 'Attention',
            self::RED => 'Problem',
            default => 'Could not measure',
        };
    }
}
