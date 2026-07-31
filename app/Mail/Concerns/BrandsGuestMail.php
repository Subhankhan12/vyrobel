<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use App\Models\Property;
use App\Support\Branding\PropertyBrand;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Applies a property's guest-facing BRAND to a Mailable (task #98) — the from-NAME and REPLY-TO, plus the
 * resolved {@see PropertyBrand} the shared `emails.layout` reads. Anti-drift: ONE place builds the branded
 * envelope, so the ~guest Mailables can't disagree on the sender rules.
 *
 * ⚠️ Postmark: the from ADDRESS stays the fixed verified Vyrobel sender (`config('mail.from.address')`) — a
 * per-property address would be refused without domain verification. Only the display NAME + REPLY-TO vary.
 */
trait BrandsGuestMail
{
    protected function brandFor(?Property $property): PropertyBrand
    {
        return PropertyBrand::for($property);
    }

    protected function brandedEnvelope(string $subject, PropertyBrand $brand): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), $brand->fromName),
            replyTo: $brand->replyTo !== null ? [new Address($brand->replyTo)] : [],
            subject: $subject,
        );
    }
}
