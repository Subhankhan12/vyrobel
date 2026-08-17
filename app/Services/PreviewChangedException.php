<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * D18-5 — the bulk per-unit change set moved between the operator seeing it and approving it.
 *
 * ⚠️ THIS IS A SUCCESS, NOT A FAULT. It means {@see UnitRateOverrideService} refused to write figures the
 * operator never approved. The caller re-previews and asks again; nothing has been written.
 */
final class PreviewChangedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The rates changed while you were reviewing — nothing was applied. Please preview again.');
    }
}
