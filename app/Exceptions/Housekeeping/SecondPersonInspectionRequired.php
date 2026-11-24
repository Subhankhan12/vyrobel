<?php

declare(strict_types=1);

namespace App\Exceptions\Housekeeping;

use RuntimeException;

/**
 * VY-27 — the property asks for inspection by a second person, and the person inspecting is the one who marked the room clean.
 * A {@see RuntimeException} like every other refusal of the status ladder, so a caller that does not catch it fails loudly; the board
 * catches it first and says why in its own words.
 */
final class SecondPersonInspectionRequired extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This property asks for a second person to inspect a room; the person who cleaned it cannot.');
    }
}
