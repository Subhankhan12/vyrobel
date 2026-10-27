<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\BrandsGuestMail;
use App\Models\Folio;
use App\Models\Reservation;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Email\ResolvedEmailTemplate;
use App\Support\Folio\FolioTotals;
use App\Support\Guest\ArrivalPack;
use App\Support\Guest\GuestName;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Guest: pre-arrival info — self check-in with the guest's OWN door code + wifi (transactional-emails exact-match).
 * Sent 48h before check-in by the `emails:send-pre-arrival` command (the real scheduled trigger).
 *
 * 🔒 The door code + wifi are the guest's OWN access secrets for their OWN stay — CORRECT to include here (#33
 * forbids exposing arrival secrets to the OWNER, not to the guest). They come from the REAL assigned unit via the
 * shared {@see ArrivalPack} (the SAME resolver the Arrival Info screen uses — no drift) and this email is delivered
 * to the lead guest's OWN email only. The pack is TIMING-GATED: it releases the secrets only inside the arrival
 * window (`LEAD_DAYS`=2 = 48h), which is exactly when this email is sent — so the code is real at send time and
 * withheld (never fabricated) for a cancelled/out-of-window stay.
 *
 * `$asOf` is the moment the email represents; it DEFAULTS to the send window (check_in − LEAD_DAYS), so the
 * operator preview renders the same released state the guest will receive — never a fake code.
 */
class PreArrivalMail extends Mailable
{
    use BrandsGuestMail, Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation, public readonly ?CarbonInterface $asOf = null) {}

    public function envelope(): Envelope
    {
        return $this->brandedEnvelope($this->template()->subject(), $this->brandFor($this->reservation->property));
    }

    public function content(): Content
    {
        $r = $this->reservation->loadMissing(['leadGuest', 'property', 'reservationUnits.unit']);

        $checkIn = Carbon::parse($r->check_in);
        // The email represents the 48h-before send moment, so the arrival pack always reflects the released state.
        $asOf = $this->asOf ?? $checkIn->copy()->subDays(ArrivalPack::LEAD_DAYS);
        $pack = ArrivalPack::for($r, $asOf);

        $unit = $r->reservationUnits->first()?->unit;
        $unitName = $unit !== null ? trim((string) ($unit->name ?: $unit->code)) : '';
        $unitName = $unitName !== '' ? $unitName : (string) ($r->property->name ?? __('php/mail/pre_arrival_mail.your_unit'));

        // The REAL outstanding balance (shared FolioTotals) — the "pay now to skip the desk" line shows ONLY when
        // money is genuinely owed; never a fabricated figure.
        $folio = Folio::query()->where('reservation_id', $r->getKey())->first();
        $balanceMinor = $folio !== null ? FolioTotals::for($folio)['balance_minor'] : 0;
        $balance = $balanceMinor > 0 ? MoneyFormatter::display($balanceMinor, (string) $r->currency) : null;

        $directions = $pack['directions'];
        $nights = max(1, (int) $checkIn->diffInDays(Carbon::parse($r->check_out)));
        $tpl = $this->template($unitName, $nights, $balance);

        return new Content(
            view: 'emails.pre-arrival',
            with: [
                'guestName' => GuestName::display($r->leadGuest),
                'propertyName' => (string) ($r->property->name ?? __('php/mail/pre_arrival_mail.your_stay')),
                'brand' => $this->brandFor($r->property),
                'unitName' => $unitName,
                'reference' => (string) $r->reference,
                'checkInLabel' => $checkIn->translatedFormat('D, j M'),
                'checkOutLabel' => Carbon::parse($r->check_out)->translatedFormat('D, j M'),
                'checkInTime' => $pack['check_in_time'],
                'checkOutTime' => $pack['check_out_time'],
                // The guest's OWN access secrets (released in-window; null before it opens → the template says when).
                'doorCode' => $pack['door_code'],
                'releasedFrom' => $pack['released_from'],
                'wifiSsid' => $pack['wifi_ssid'],
                'wifiPassword' => $pack['wifi_password'],
                'directions' => $directions,
                'mapsUrl' => $directions !== null ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($directions) : null,
                'balance' => $balance,
                'payUrl' => route('guest.payments'),
                'arrivalUrl' => route('guest.arrival', $r->getKey()),
                'checkinUrl' => route('guest.checkin', $r->getKey()),
                'viewUrl' => route('guest.manage', $r->getKey()),
                'messagesUrl' => route('guest.messages'),
                // Operator-customizable template (#86)
                'heading' => $tpl->heading(),
                'intro' => $tpl->message(),
                'button' => $tpl->button(),
                'blocks' => [
                    'door_code' => $tpl->block('door_code'),
                    'directions' => $tpl->block('directions'),
                    'wifi' => $tpl->block('wifi'),
                    'checkin' => $tpl->block('checkin'),
                    'balance' => $tpl->block('balance'),
                ],
            ],
        );
    }

    private function template(?string $unitName = null, ?int $nights = null, ?string $balance = null): ResolvedEmailTemplate
    {
        $r = $this->reservation;
        $guest = $r->leadGuest;
        $first = $guest !== null ? trim((string) $guest->first_name) : '';

        return EmailTemplateResolver::for((string) $r->organization_id, 'pre-arrival', [
            'first_name' => $first !== '' ? $first : __('php/mail/pre_arrival_mail.there'),
            'unit' => $unitName ?? (string) ($r->property->name ?? __('php/mail/pre_arrival_mail.your_unit')),
            'nights' => $nights ?? '',
            'reference' => (string) $r->reference,
            'balance' => $balance ?? '',
        ]);
    }
}
