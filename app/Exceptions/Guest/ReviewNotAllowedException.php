<?php

declare(strict_types=1);

namespace App\Exceptions\Guest;

use App\Services\ReviewService;
use RuntimeException;

/**
 * A review cannot be left (Phase 5) — the stay isn't the guest's own completed booking, or it has already been
 * reviewed (one review per stay). {@see ReviewService::submit()} throws this BEFORE any write.
 */
class ReviewNotAllowedException extends RuntimeException {}
