<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * A property removal the service will not schedule — the message is written for the person who asked, in plain words.
 * Thrown by {@see PropertyRemovalService::schedule()}; every caller turns it into a notice, never a 500.
 */
final class PropertyRemovalRefused extends RuntimeException {}
