<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use RuntimeException;

/**
 * ⚠️ An override was supplied by someone who may not override.
 *
 * ⚠️ Thrown from the point the override is CONSUMED, so it covers every booking path at once — including a
 * crafted public request that carries an override reason the storefront never offers.
 */
class RestrictionOverrideForbiddenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You do not have permission to override a booking restriction.');
    }
}
