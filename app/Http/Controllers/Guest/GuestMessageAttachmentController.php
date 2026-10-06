<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Support\Inbox\MessageAttachments;
use Symfony\Component\HttpFoundation\Response;

/**
 * A file the property sent in this guest's own conversation — a picture, a document or a voice note (2026-10-06) — opened
 * by that guest only, signed in ({@see MessageAttachments::findForGuest()}). Another guest's file, an owner's, anything
 * else: a 404. The DEC-m rule for message files: "let only people in that conversation open them, after signing in".
 */
final class GuestMessageAttachmentController extends Controller
{
    use ResolvesGuest;

    public function __invoke(MessageAttachments $attachments, string $media): Response
    {
        return $attachments->response($attachments->findForGuest($media, $this->guest()));
    }
}
