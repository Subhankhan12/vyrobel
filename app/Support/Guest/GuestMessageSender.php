<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Mail\Concerns\BrandsGuestMail;
use App\Models\Property;
use App\Support\Property\PropertyContext;

/**
 * The property a Guests-list "Message" is sent AS (2026-10-05) — the working property: its name on the email and its reply-to
 * address ({@see BrandsGuestMail}). ONE definition, read by the page (which names it and says where replies
 * go) and by the controller (which sends), so the dialog can never describe a different sender from the one the email carries.
 *
 * ⚠️ Under "All properties" this is the first accessible one ({@see PropertyContext::id()}) whichever property the selected guests
 * stayed at — which is why the dialog names it.
 */
final class GuestMessageSender
{
    public static function property(): ?Property
    {
        $id = app(PropertyContext::class)->id();

        return $id !== null ? Property::query()->find($id) : null;
    }
}
