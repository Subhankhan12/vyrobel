<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * ⚠️ ADMIN 06 (#5) — an invite link that was redeemable when its page opened, and is not by the time it is submitted
 * (a founder's account gained a password in between). The accept page answers it as an invalid link — never a 500.
 */
final class InviteNoLongerRedeemable extends RuntimeException {}
