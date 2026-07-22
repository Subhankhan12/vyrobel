<?php

declare(strict_types=1);

namespace App\Exceptions\Schedule;

use RuntimeException;

/**
 * A recoverable Team-Schedule rule violation (Phase 7d) — a manager approving their OWN time-off (the
 * self-approval separation), or assigning a shift on a day the staff member is on approved leave (a real
 * conflict). Thrown by the schedule services; the controllers catch it and redirect back with the message.
 */
class ScheduleException extends RuntimeException {}
