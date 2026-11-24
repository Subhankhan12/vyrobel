<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\GuestDocument;
use App\Models\User;
use App\Services\Guest\GuestIdDocumentService;
use App\Support\Property\PropertyScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * VY-18 (audit of 2026-11-24; his ruling the same day: "Build the viewer") — staff open a guest's ID image from the reservation's
 * page and record what they decided. Until now the images were collected and nothing could open one.
 *
 * 🔒 `can:guest_documents.manage` on both routes (#30 — the route, after the tenant middleware has set the team).
 * 🔒 The booking is resolved inside the person's organisation AND their property list ({@see PropertyScope::reservation()}, VY-05);
 *    the document must belong to THAT booking — the right document under another booking's address is a 404.
 * 🔒 EVERY OPENING IS RECORDED before a byte is sent ({@see GuestIdDocumentService::recordViewed()}).
 * 🔒 The image is decrypted for this one response and marked `no-store, private`: no browser, proxy or CDN keeps a copy.
 */
class GuestIdDocumentController extends Controller
{
    public function show(GuestIdDocumentService $ids, string $reservation, string $document): Response
    {
        $guestDocument = $this->documentOf($reservation, $document);

        $file = $ids->read($guestDocument);
        abort_if($file === null, 404);

        $ids->recordViewed($guestDocument);

        return response($file['bytes'], 200, [
            'Content-Type' => $file['mime'],
            // an image opens in the tab; anything that is not one of the accepted image types is only ever offered as a file
            'Content-Disposition' => ($file['mime'] === 'application/octet-stream' ? 'attachment' : 'inline').'; filename="id-document"',
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function review(Request $request, GuestIdDocumentService $ids, string $reservation, string $document): RedirectResponse
    {
        $guestDocument = $this->documentOf($reservation, $document);

        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:'.implode(',', GuestIdDocumentService::DECISIONS)],
        ]);

        /** @var User $user */
        $user = $request->user();
        $ids->review($guestDocument, $user, (string) $validated['decision']);

        return redirect()->route('app.reservations.show', $reservation)
            ->with('folio_notice', $validated['decision'] === 'verified'
                ? __('php/http/controllers/operator/guest_id_document_controller.marked_checked')
                : __('php/http/controllers/operator/guest_id_document_controller.marked_rejected'));
    }

    /** The document of THIS booking, inside the person's organisation and properties — anything else is a 404. */
    private function documentOf(string $reservation, string $document): GuestDocument
    {
        $model = app(PropertyScope::class)->reservation($reservation);

        return GuestDocument::query()
            ->where('reservation_id', $model->getKey())
            ->with('document.media')
            ->findOrFail($document);
    }
}
