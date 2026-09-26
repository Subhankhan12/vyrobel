<?php

declare(strict_types=1);

namespace App\Services\Platform;

use RuntimeException;

/**
 * ⚠️ ADMIN 05 — the deletion REFUSED, and nothing was deleted: something makes it unsafe (money moving at a third
 * party, another organisation's rows pointing in, the schema unreadable, it is already gone), or the post-condition
 * found a surviving row and rolled everything back. The message is plain words written here — never a database or
 * driver message — so the console may show it as it is.
 */
final class OrganizationDeletionRefused extends RuntimeException {}
