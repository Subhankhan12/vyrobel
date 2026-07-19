<?php

declare(strict_types=1);

namespace App\Exceptions\Authorization;

use App\Services\RoleAssignmentService;
use RuntimeException;

/**
 * A role assignment was refused by a DATA-level bound in {@see RoleAssignmentService} — an
 * attempted privilege escalation, a self-change, a cross-org target, or a non-catalog role. Distinct from the
 * ROUTE gate (`can:users.manage`): that decides whether the actor may manage roles AT ALL; this decides whether
 * a SPECIFIC assignment is within their privilege. The controller catches it and re-flashes, never a 500.
 */
class RoleAssignmentException extends RuntimeException {}
