<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use RuntimeException;

/**
 * Base for expected, clean booking rejections thrown by AssignmentService (as opposed to
 * unexpected errors). These are the rejections the no-double-booking guarantee produces.
 */
class BookingException extends RuntimeException {}
