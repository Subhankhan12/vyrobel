<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a tenant-scoped model is queried or created without an active organization
 * context and outside an explicit super-admin context. This is the hard guarantee that we
 * never silently read or write another tenant's data (convention #1).
 */
class TenantContextMissingException extends RuntimeException
{
    public static function for(string $modelClass): self
    {
        return new self(
            "No organization context is set for tenant-scoped model [{$modelClass}]. "
            .'Set the tenant via TenantContext::set() (or wrap the call in '
            .'TenantContext::runAsSuperAdmin()) before querying or creating tenant data.'
        );
    }
}
