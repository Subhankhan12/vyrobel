<?php

declare(strict_types=1);

namespace App\Exceptions\Owner;

use RuntimeException;

/**
 * An owner statement for this period is already finalized/locked, so it cannot be regenerated or finalized again.
 * A RuntimeException still (every existing catch keeps working), but identifiable — so a controller maps it to a
 * TRANSLATED reason instead of printing the exception's English message to a person (D-I18N-STATEMENT-REFUSAL).
 */
class StatementSealedException extends RuntimeException
{
    public static function cannotRegenerate(): self
    {
        return new self('Cannot regenerate a finalized/locked statement.');
    }

    public static function alreadyFinalized(): self
    {
        return new self('Statement is already finalized/locked.');
    }
}
