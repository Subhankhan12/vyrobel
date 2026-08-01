<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\BrandsGuestMail;
use App\Models\Guest;
use App\Services\GuestDeletionService;
use App\Support\Branding\PropertyBrand;
use App\Support\Guest\GuestName;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Your account deletion is scheduled" — sent to the guest's OWN address when they schedule the reversible
 * 14-day erase (Guest Portal · GATE 3, founder decision D2). Account-level, so it carries the default Vyrobel
 * brand (no specific property). Tells them exactly when it runs + that they can undo any time before then.
 */
class AccountDeletionScheduledMail extends Mailable
{
    use BrandsGuestMail, Queueable, SerializesModels;

    public function __construct(public readonly Guest $guest) {}

    public function envelope(): Envelope
    {
        return $this->brandedEnvelope('Your account deletion is scheduled', PropertyBrand::default());
    }

    public function content(): Content
    {
        $effective = $this->guest->scheduled_deletion_at;

        return new Content(view: 'emails.account-deletion', with: [
            'brand' => PropertyBrand::default(),
            'heading' => 'Your account deletion is scheduled',
            'intro' => 'We’ve scheduled your Vyrobel guest account for deletion. You have '.GuestDeletionService::GRACE_DAYS.' days to change your mind — nothing is removed until then.',
            'guestName' => GuestName::display($this->guest),
            'graceDays' => GuestDeletionService::GRACE_DAYS,
            'effective' => $effective !== null ? $effective->format('j M Y') : null,
        ]);
    }
}
