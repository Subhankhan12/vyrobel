<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** A team chat action refused for a reason the person can act on — its message is shown to them as written. */
final class TeamChatRefused extends RuntimeException {}
