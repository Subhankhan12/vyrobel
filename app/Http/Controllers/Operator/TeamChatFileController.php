<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Platform\BreakGlass;
use App\Support\TeamChat\TeamChatFiles;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A team chat file — a picture, a document or a voice note — for the members of its conversation only (team chat,
 * 2026-10-06; {@see TeamChatFiles}). Anyone else, and a Vyrobel support session, gets a 404.
 */
final class TeamChatFileController extends Controller
{
    public function __invoke(Request $request, TeamChatFiles $files, string $media): Response
    {
        abort_if(BreakGlass::hasStash($request), 404);
        $user = $request->user();
        abort_unless($user instanceof User, 404);

        return $files->response($files->find($media, $user));
    }
}
