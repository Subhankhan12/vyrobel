<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Inbox\MessageAttachments;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opens a message attachment for someone in that conversation, signed in (DEC-m, 2026-09-30). ONE controller behind TWO
 * routes — `owner.messages.attachment` in the owner portal and `app.inbox.attachment` in the back office: the route's own
 * middleware decides who may knock (the owner portal, or the inbox's `guests.view`), and the conversation's scope decides
 * which files they find ({@see MessageAttachments::find()}).
 */
final class MessageAttachmentController
{
    public function __invoke(string $media, MessageAttachments $attachments): Response
    {
        return $attachments->response($attachments->find($media));
    }
}
