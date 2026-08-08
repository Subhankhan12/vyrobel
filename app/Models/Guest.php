<?php

namespace App\Models;

use App\Mail\GuestPasswordResetMail;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTags;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Guest\GuestName;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guest profile (PII, convention #5). `email`/`phone` are encrypted at rest. GDPR erase is
 * {@see anonymize()} — it nulls PII and sets `anonymized_at` WITHOUT deleting the row, so
 * reservation/financial history (which references this id) stays intact. We never
 * soft-delete-and-retain PII.
 *
 * ⚠️ PORTAL AUTH (Phase 4a) — a Guest is Authenticatable on a SEPARATE 'guest' guard/realm (NOT the operator/owner
 * `web` guard; a guest can never reach `/app` or `/owner`). The account is UNCLAIMED until a `password` is set AND
 * the email is verified ({@see hasPortalAccount()}); a booking's Guest row exists first, the guest claims it later.
 * `email` is encrypted (non-deterministic → not queryable), so login/claim look a guest up by {@see emailHash()}
 * (a keyed blind index), never by the plaintext.
 *
 * @property string|null $password
 * @property Carbon|null $email_verified_at
 * @property string|null $email_hash
 * @property string|null $remember_token
 * @property string|null $city
 * @property bool $prefers_email
 * @property array<int, string>|null $preferences
 * @property Carbon|null $scheduled_deletion_at
 */
class Guest extends Model implements AuthenticatableContract, CanResetPasswordContract, MustVerifyEmailContract
{
    use AuthenticatableTrait, BelongsToOrganization, CanResetPasswordTrait, HasDocuments, HasFactory, HasNotes, HasTags, HasUlids, MustVerifyEmailTrait, Notifiable, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'city',
        'nationality',
        'dob',
        'language',
        'prefers_email',
        'is_vip',
        'risk_flag',
        'marketing_consent',
        'preferences',
        'external_refs',
    ];

    /**
     * The fixed catalog of stay PREFERENCES (#87) — key => label. Shown to the front desk + housekeeping so a
     * cleaner/receptionist prepares the right room. A `guests.preferences` JSON array holds the ACTIVE keys;
     * an unknown key is dropped on write, so the set can never drift from this catalog.
     *
     * @var array<string, string>
     */
    public const PREFERENCE_CATALOG = [
        'sea_view' => 'Sea view',
        'high_floor' => 'High floor',
        'late_checkout' => 'Late checkout',
        'quiet_room' => 'Quiet room',
        'ground_floor' => 'Ground floor',
        'early_checkin' => 'Early check-in',
        'extra_towels' => 'Extra towels',
        'crib' => 'Crib',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'email',
        'phone',
        'password',
        'remember_token',
        'email_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'dob' => 'date',
            'prefers_email' => 'boolean',
            'is_vip' => 'boolean',
            'risk_flag' => 'boolean',
            'marketing_consent' => 'boolean',
            'preferences' => 'array',
            'external_refs' => 'array',
            'anonymized_at' => 'datetime',
            'scheduled_deletion_at' => 'datetime',
        ];
    }

    /**
     * Keep the blind index in lock-step with the (encrypted) email on every write — including anonymize(), which
     * nulls the email → nulls the hash, so an erased guest can no longer be found by email.
     */
    protected static function booted(): void
    {
        static::saving(function (Guest $guest): void {
            $email = $guest->email;   // plaintext via the encrypted cast
            $guest->email_hash = ($email !== null && trim($email) !== '') ? self::emailHash($email) : null;
        });
    }

    /**
     * The BLIND INDEX for an email — a keyed HMAC of the normalised (lower-cased, trimmed) address. Deterministic
     * (so login/claim can look a guest up despite the encrypted column) but keyed with the app key, so the low
     * entropy of an email address can't be reversed from the hash alone. NEVER store or compare the plaintext.
     */
    public static function emailHash(string $email): string
    {
        return hash_hmac('sha256', mb_strtolower(trim($email)), (string) config('app.key'));
    }

    /** The account is CLAIMED (can log in) only once a password is set AND the email is verified. */
    public function hasPortalAccount(): bool
    {
        return $this->password !== null && $this->email_verified_at !== null;
    }

    /**
     * ⚠️ The reset broker keys its token table on THIS — the BLIND INDEX (`email_hash`), never the plaintext
     * email — so no guest PII is written to `guest_password_reset_tokens` (invariant #28). The user still enters
     * their plaintext email on the forms; the provider resolves it to a guest, and the token is matched by hash.
     */
    public function getEmailForPasswordReset(): string
    {
        return (string) $this->email_hash;
    }

    /** Deliver the guest reset link through our own branded, email-safe Mailable (Mailpit in dev; prod: a provider). */
    public function sendPasswordResetNotification($token): void
    {
        Mail::to((string) $this->email)->send(new GuestPasswordResetMail((string) $token, GuestName::display($this)));
    }

    /**
     * The ONE hash + remember-token-rotate path for a guest — mirrors {@see User::changePasswordTo()}, so the
     * guest reset and any future guest change-password can't drift. Rotating the remember token logs OTHER
     * remembered guest sessions out on a password change.
     */
    public function changePasswordTo(string $plainPassword): void
    {
        $this->forceFill([
            'password' => $plainPassword,
            'remember_token' => Str::random(60),
        ])->save();
    }

    /**
     * @return HasMany<GuestDocument, $this>
     */
    public function guestDocuments(): HasMany
    {
        return $this->hasMany(GuestDocument::class);
    }

    /**
     * The stays this guest LEADS (Wave 2 · Guests CRM).
     *
     * Keyed on `reservations.lead_guest_id` — the same FK {@see Reservation::leadGuest()} points back
     * through, and the one every built operator surface already treats as "the guest of this booking"
     * (FrontDesk, ReservationDetail, InboxThread::guestCard). A booking can also carry additional
     * occupants via the real `reservation_guests` pivot; they are deliberately NOT included here, because
     * "reservations of this guest" would then mean two different things on two screens. If a
     * fellow-traveller lens is ever wanted it belongs in its own, separately-named relation.
     *
     * Read-only convenience: it adds no behaviour and changes no guarantee — it is the relation the Wave 2
     * inventory flagged as the one shallow gap behind every guest derivation.
     *
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'lead_guest_id');
    }

    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }

    /**
     * This guest's active stay preferences as human labels (#87), from {@see PREFERENCE_CATALOG} — a foreign
     * key is dropped, so the labels can never drift from the catalog. The ONE definition, read by the profile,
     * the front desk and housekeeping, so those surfaces cannot disagree.
     *
     * @return list<string>
     */
    public function preferenceLabels(): array
    {
        $labels = [];
        foreach (is_array($this->preferences) ? $this->preferences : [] as $key) {
            if (isset(self::PREFERENCE_CATALOG[$key])) {
                $labels[] = self::PREFERENCE_CATALOG[$key];
            }
        }

        return $labels;
    }

    /**
     * GDPR erase: null every PII field, purge sensitive ID documents (hard delete — never
     * retain PII, not even soft-deleted), and stamp `anonymized_at`. The row and its id are
     * preserved so downstream reservation/financial records remain referentially intact.
     */
    public function anonymize(): void
    {
        DB::transaction(function (): void {
            $this->guestDocuments()->get()->each(function (GuestDocument $guestDocument): void {
                $document = $guestDocument->document;
                $media = $document?->media;

                if ($media !== null) {
                    // Purge the actual stored file bytes, not just the DB row — a real GDPR erase.
                    Storage::disk((string) $media->disk)->delete((string) $media->path);
                    $media->forceDelete();
                }

                $document?->forceDelete();
                $guestDocument->forceDelete();
            });

            $anonymizedAt = now();

            $this->forceFill([
                'first_name' => null,
                'last_name' => null,
                'email' => null,
                'phone' => null,
                // #87 — a residence + stay preferences are personal data ABOUT the guest; erasure clears them too.
                'city' => null,
                'preferences' => null,
                'nationality' => null,
                'dob' => null,
                'language' => null,
                'prefers_email' => true,
                'external_refs' => null,
                'marketing_consent' => false,
                // GDPR erase ALSO destroys any portal account — an anonymized guest can never log in again
                // (email_hash is nulled by the saving() hook when email → null). Portal auth is Phase 4a.
                'password' => null,
                'email_verified_at' => null,
                'remember_token' => null,
                'anonymized_at' => $anonymizedAt,
            ])->save();

            // GDPR: scrub this guest's inbox conversations too — a guest's messages ARE their PII. Redact
            // the content (message bodies + sender labels) and the thread's encrypted participant identity /
            // subject, keeping the thread/message skeleton for audit. NEVER soft-delete-and-keep-PII.
            // Tenant-scoped (this org) + guest-scoped; a cross-org thread can't be touched.
            $threadIds = MessageThread::query()->where('guest_id', $this->getKey())->pluck('id');

            if ($threadIds->isNotEmpty()) {
                Message::query()->whereIn('thread_id', $threadIds)->update(['body' => '', 'sender_label' => null]);
                MessageThread::query()->whereKey($threadIds)->update(['participant_identity' => null, 'subject' => null]);

                // Staff INTERNAL notes on the guest's threads are free text ABOUT the guest ("handing to HK re:
                // late checkout for X") — their PII too, so redact the body on erase (keep the row skeleton for
                // audit). Added WITH the inbox assign-note writer that first populates them — never a store the
                // erase silently leaves behind. `message_thread` is the enforced morph alias for MessageThread.
                Note::query()->where('notable_type', 'message_thread')->whereIn('notable_id', $threadIds)->update(['body' => '']);
            }

            // GDPR: scrub the operator's NOTES about this guest for the same reason as the messages above —
            // `notes.body` is free text and staff write identifying detail into it ("travelling with X",
            // "allergic to Y"). A note ABOUT an identified person is their personal data, so leaving it would
            // keep PII the erase promises to destroy. Redact the body, keep the row + author + timestamp so
            // the audit skeleton survives — never soft-delete-and-keep-PII.
            //
            // This landed WITH the guest profile (Wave 2 · Sub-gate 5), the first thing in the app that ever
            // WROTE a note: until then the table was empty everywhere, so the gap was unreachable rather than
            // benign. Shipping the writer without this would have opened a real hole in a store the app
            // already promises to clear. Tenant-scoped + guest-scoped via the morph relation.
            $this->notes()->update(['body' => '']);

            // GDPR: the guest's OWN free-text on their reservations is their PII too. `notes_guest` holds the
            // arrival-preparation note the guest types at online check-in ("allergies, a cot, parking…"); clear
            // it (and the coarse arrival window) on erase. Added WITH the online-check-in writer that first
            // populates it (Guest Portal batch 2 · GATE 2) — never a store the erase silently leaves behind.
            //
            // ⚠️ `notes_internal` + `expected_arrival` ADDED 2026-08-08 (audit H5 / F-2b-03). They were the
            // STAFF-authored half of the same row and survived erasure verbatim — full name, phone number,
            // allergies, travel companions. `guests.phone` is an encrypted column precisely because a phone
            // number is sensitive; copying it into a plaintext `notes_internal` and never clearing it defeated
            // that control, which is exactly what this file's own rule forbids ("an encrypted column is undone
            // the moment its value is copied somewhere plaintext").
            $this->reservations()->update([
                'notes_guest' => null,
                'arrival_window' => null,
                'notes_internal' => null,
                'expected_arrival' => null,
            ]);

            // GDPR: the operator's PUBLIC reply to this guest's review (audit H4 / F-2b-02). The byline already
            // renders "A guest" after erasure, but the reply underneath kept the guest's first name — on a
            // public, indexable, unauthenticated storefront page tied to a specific stay, unit and date. An
            // erasure that leaves "Thank you Sofia" visible is not an erasure. Keep the rating + row skeleton so
            // the derived #76 aggregates are unchanged; only the identifying free text goes.
            //
            // ⚠️ `reviews.body` is DELIBERATELY RETAINED and flagged for a founder decision, not silently kept:
            // it is the guest's own opinion OF THE PROPERTY, it is what other travellers rely on, and destroying
            // it changes published content. It is not known to contain identifiers (the author is already
            // anonymised). If policy says an Art.17 erasure must take the review text too, add `'body' => null`
            // here — one line, and GuestErasureTest already plants a value in it.
            Review::query()->where('guest_id', $this->getKey())
                ->update(['operator_response' => null, 'responded_at' => null]);

            // GDPR: a change request is free text the GUEST wrote about their own stay (dates, occupancy, a
            // reason). Cleared with the same reasoning as notes_guest; the row skeleton + status survive so the
            // operator's decision history stays auditable.
            // (`payload` is NOT NULL at the schema level, so it is EMPTIED rather than nulled — the same
            // redact-don't-delete shape as `notes.body => ''` above.)
            GuestChangeRequest::query()->where('guest_id', $this->getKey())->update(['payload' => '[]']);

            // GDPR: an abandoned-booking QUOTE carries the guest's encrypted email/phone and a guest_snapshot.
            // Only the quotes that converted into THIS guest's reservations are reachable by id — an abandoned
            // quote has no guest link at all (audit F-2b-07), so those are cleared by the scheduled retention
            // purge, not from here. Clearing what IS reachable is strictly better than clearing none of it.
            $reservationIds = $this->reservations()->pluck('id');
            if ($reservationIds->isNotEmpty()) {
                BookingQuote::query()->whereIn('converted_reservation_id', $reservationIds)
                    ->update(['email' => null, 'phone' => null, 'guest_snapshot' => null]);
            }

            // GDPR: the block-organiser NAME copied into `groups.contact` (plaintext json, for display) is this
            // guest's PII too. Redact just the name (company is a business, not PII) via the organiser FK — added
            // WITH that FK so this store can never survive erase. An adversarial review caught it: without the FK
            // + this clause the organiser name lingered in plaintext after anonymize.
            Group::query()->where('organiser_guest_id', $this->getKey())->update(['contact->name' => null]);

            // NOTE: never put the erased PII into the audit before/after — that would re-retain it.
            app(AuditEventRecorder::class)->record(
                action: 'guest.anonymized',
                subject: $this,
                after: ['anonymized_at' => $anonymizedAt->toIso8601String()],
            );
            app(DomainEventRecorder::class)->record(
                eventType: 'guest.anonymized',
                subject: $this,
                payload: ['guest_id' => $this->getKey()],
            );
        });
    }
}
