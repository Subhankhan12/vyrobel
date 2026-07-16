<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTags;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Guest profile (PII, convention #5). `email`/`phone` are encrypted at rest. GDPR erase is
 * {@see anonymize()} — it nulls PII and sets `anonymized_at` WITHOUT deleting the row, so
 * reservation/financial history (which references this id) stays intact. We never
 * soft-delete-and-retain PII.
 */
class Guest extends Model
{
    use BelongsToOrganization, HasDocuments, HasFactory, HasNotes, HasTags, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'nationality',
        'dob',
        'language',
        'is_vip',
        'risk_flag',
        'marketing_consent',
        'external_refs',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'email',
        'phone',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'dob' => 'date',
            'is_vip' => 'boolean',
            'risk_flag' => 'boolean',
            'marketing_consent' => 'boolean',
            'external_refs' => 'array',
            'anonymized_at' => 'datetime',
        ];
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
                'nationality' => null,
                'dob' => null,
                'language' => null,
                'external_refs' => null,
                'marketing_consent' => false,
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
            }

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
