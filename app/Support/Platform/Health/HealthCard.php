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

    /** ⚠️ ADMIN 01 · PHASE 5 — OFF BY DESIGN: not a problem, not unmeasured (e.g. an integration not connected yet). */
    public const OFF = 'off';

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
        public readonly ?string $label = null,
    ) {}

    /** A card whose measurement itself failed — grey, with the reason, never green. */
    public static function couldNotMeasure(string $key, string $title, string $why): self
    {
        return new self($key, $title, self::GREY, __('php/support/platform/health/health_card.could_not_measure'), [$why]);
    }

    public static function fromFailure(string $key, string $title, Throwable $e): self
    {
        return self::couldNotMeasure($key, $title, __('php/support/platform/health/health_card.check_failed', ['exception' => class_basename($e)]));
    }

    public function statusLabel(): string
    {
        return $this->label ?? match ($this->status) {
            self::OFF => __('php/support/platform/health/health_card.status_off'),
            self::GREEN => __('php/support/platform/health/health_card.status_ok'),
            self::AMBER => __('php/support/platform/health/health_card.status_attention'),
            self::RED => __('php/support/platform/health/health_card.status_problem'),
            default => __('php/support/platform/health/health_card.status_could_not_measure'),
        };
    }
}
