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

    /**
     * @param  int  $staysCancelled  how many of the guest's bookings the erase will cancel — passed in by
     *                               {@see GuestDeletionService} from the SAME set the erase uses, so the email
     *                               and the account screen cannot quote different numbers.
     */
    public function __construct(public readonly Guest $guest, public readonly int $staysCancelled = 0) {}

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
            // ⚠️ The erase cancels bookings and can refund — the email says so, because a deadline with no
            // stated consequence is half a warning. Never promises a refund: it is policy-dependent.
            'intro' => 'We’ve scheduled your Vyrobel guest account for deletion. You have '
                .GuestDeletionService::GRACE_DAYS.' days to change your mind — nothing is removed until then.'
                .($this->staysCancelled > 0
                    ? ' When it runs we will also cancel the '.$this->staysCancelled.' booking'
                        .($this->staysCancelled === 1 ? '' : 's').' you still have with us, including a stay already'
                        .' under way. Whether any money comes back follows each booking’s cancellation policy, so'
                        .' some or all of it may not be refunded.'
                    : ''),
            'guestName' => GuestName::display($this->guest),
            'graceDays' => GuestDeletionService::GRACE_DAYS,
            'effective' => $effective !== null ? $effective->format('j M Y') : null,
        ]);
    }
}
