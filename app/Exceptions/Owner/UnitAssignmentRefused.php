<?php

declare(strict_types=1);

namespace App\Exceptions\Owner;

use App\Services\UnitOwnerAssignmentService;
use RuntimeException;

/**
 * DEC-p — giving an owner a unit was refused. The message is a plain sentence written for the operator (it is shown on the
 * owner's page as it stands), and nothing was written: every check runs again inside the transaction, under the unit's lock.
 *
 * @see UnitOwnerAssignmentService
 */
class UnitAssignmentRefused extends RuntimeException {}
