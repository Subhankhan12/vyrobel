<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\BrandsGuestMail;
use App\Models\Guest;
use App\Services\GuestDeletionService;
use App\Support\Branding\PropertyBrand;
use App\Support\Guest\GuestName;
use App\Support\I18n\LocalDate;
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
        return $this->brandedEnvelope(__('php/mail/account_deletion_scheduled_mail.subject'), PropertyBrand::default());
    }

    public function content(): Content
    {
        $effective = $this->guest->scheduled_deletion_at;

        return new Content(view: 'emails.account-deletion', with: [
            'brand' => PropertyBrand::default(),
            'heading' => __('php/mail/account_deletion_scheduled_mail.heading'),
            // ⚠️ The erase cancels bookings and can refund — the email says so, because a deadline with no
            // stated consequence is half a warning. Never promises a refund: it is policy-dependent.
            'intro' => trans_choice('php/mail/account_deletion_scheduled_mail.intro', GuestDeletionService::GRACE_DAYS, ['count' => GuestDeletionService::GRACE_DAYS])
                .($this->staysCancelled > 0
                    ? ' '.trans_choice('php/mail/account_deletion_scheduled_mail.cancel_note', $this->staysCancelled, ['count' => $this->staysCancelled])
                    : ''),
            'guestName' => GuestName::display($this->guest),
            'graceDays' => GuestDeletionService::GRACE_DAYS,
            'effective' => $effective !== null ? $effective->translatedFormat(LocalDate::pattern('j M Y')) : null,
        ]);
    }
}
