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
        // Built where it is thrown (inside the request, after its language is set), so the operator reads it in theirs.
        parent::__construct(__('php/services/preview_changed_exception.message'));
    }
}
