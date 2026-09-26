<?php

declare(strict_types=1);

namespace App\Services\Guest;

use App\Console\Commands\PurgeExpiredGuestDocuments;
use App\Models\Document;
use App\Models\Guest;
use App\Models\GuestDocument;
use App\Models\Media;
use App\Models\Reservation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * The SINGLE writer of a guest ID document (Guest Portal batch 2 · GATE 2 · founder decision D2). Captures the
 * guest's own passport / EU ID-card photo at online check-in for the legal guest register — and it is REALLY
 * encrypted, register-only, and short-lived:
 *
 *  🔒 ENCRYPTED AT REST — the raw image bytes are wrapped with {@see Crypt} (the app key, AES-256) BEFORE they
 *     touch disk, and written to the PRIVATE `local` disk (storage/app/private — never `public`, no URL). So
 *     "Encrypted" on the screen is literally true, not a claim; the stored blob is ciphertext.
 *  🔒 OWNER NEVER SEES IT (#28) — a GuestDocument is guest-scoped; NO owner/operator surface renders it. It is
 *     captured, not verified: `verification_status` stays `pending` (an operator/KYC provider sets it later) —
 *     we NEVER write a fake "verified"/"details matched" (#39, and it would be legally consequential).
 *  🔒 SHORT-LIVED — {@see PurgeExpiredGuestDocuments} hard-deletes the file + rows 30 days
 *     after checkout; a GDPR erase ({@see Guest::anonymize()}) purges it immediately.
 *
 * The stored chain mirrors the shared secure-document structure: Media (the encrypted blob) → Document (secure,
 * signed-url-required) → GuestDocument (the typed register record). Tenant + guest scoped via the models.
 */
class GuestIdDocumentService
{
    private const DISK = 'local';                 // the PRIVATE disk (storage/app/private) — never `public`

    private const COLLECTION = 'guest-id';

    /** Accepted register document types (the wireframe's Passport / EU ID-card picker). */
    public const TYPES = ['passport', 'id_card'];

    /**
     * Encrypt + store one ID photo for a guest's reservation, returning the register record. Replaces any
     * prior pending ID for the SAME reservation+guest (a re-upload supersedes — never stacks duplicates).
     */
    public function store(Guest $guest, Reservation $reservation, UploadedFile $file, string $type): GuestDocument
    {
        $type = in_array($type, self::TYPES, true) ? $type : 'passport';

        // ⚠️ ADMIN 05 — the encrypted bytes are written BEFORE their rows; when the rows do not land (a failure, or the
        //    organisation deleted while the upload was in flight), the bytes must not outlive them with nothing to find
        //    them by. Rolled back → removed.
        $path = null;
        try {
            return $this->storeRows($guest, $reservation, $file, $type, $path);
        } catch (Throwable $e) {
            if ($path !== null) {
                try {
                    Storage::disk(self::DISK)->delete($path);
                } catch (Throwable $cleanup) {
                    report($cleanup);
                }
            }

            throw $e;
        }
    }

    private function storeRows(Guest $guest, Reservation $reservation, UploadedFile $file, string $type, ?string &$path): GuestDocument
    {
        return DB::transaction(function () use ($guest, $reservation, $file, $type, &$path): GuestDocument {
            // Supersede an existing pending ID for this booking (purge its bytes + rows first).
            foreach (GuestDocument::query()
                ->where('guest_id', $guest->getKey())
                ->where('reservation_id', $reservation->getKey())
                ->get() as $existing) {
                $this->purge($existing);
            }

            // Encrypt the raw bytes BEFORE writing — the blob on disk is ciphertext, keyed by the app key.
            $ciphertext = Crypt::encryptString((string) $file->get());
            $path = self::COLLECTION.'/'.$reservation->getKey().'/'.Str::ulid().'.enc';
            Storage::disk(self::DISK)->put($path, $ciphertext);

            $media = Media::create([
                'organization_id' => $reservation->organization_id,
                'mediable_type' => $guest->getMorphClass(),
                'mediable_id' => $guest->getKey(),
                'collection' => self::COLLECTION,
                'disk' => self::DISK,
                'path' => $path,
                'mime' => (string) $file->getMimeType(),   // the ORIGINAL image type (the blob is .enc ciphertext)
                'size' => (int) $file->getSize(),
                'meta' => ['encrypted' => true, 'cipher' => 'laravel-crypt'],
            ]);

            $document = Document::create([
                'organization_id' => $reservation->organization_id,
                'documentable_type' => $guest->getMorphClass(),
                'documentable_id' => $guest->getKey(),
                'media_id' => $media->getKey(),
                'category' => 'guest_id',
                'signed_url_required' => true,
            ]);

            return GuestDocument::create([
                'organization_id' => $reservation->organization_id,
                'guest_id' => $guest->getKey(),
                'reservation_id' => $reservation->getKey(),
                'type' => $type,
                'document_id' => $document->getKey(),
                'verification_status' => 'pending',   // captured, NEVER a fabricated "verified" (#39)
            ]);
        });
    }

    /**
     * Hard-delete an ID document: the encrypted file bytes + the Media/Document/GuestDocument rows. The single
     * teardown, reused by the 30-day retention command and available to the GDPR erase path.
     */
    public function purge(GuestDocument $guestDocument): void
    {
        $document = $guestDocument->document;
        $media = $document?->media;

        if ($media !== null) {
            Storage::disk((string) $media->disk)->delete((string) $media->path);
            $media->forceDelete();
        }

        $document?->forceDelete();
        $guestDocument->forceDelete();
    }
}
