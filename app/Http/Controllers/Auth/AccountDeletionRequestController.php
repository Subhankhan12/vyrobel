<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Privacy\AccountDeletionRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * DEC-6 — "Request deletion" on the staff and the owner Security pages. See {@see AccountDeletionRequests}: it files a
 * request for a person to answer within a month; it erases nothing.
 *
 * 🔒 SELF ONLY — the subject is always `$request->user()`, never a request-supplied id. The password is asked for
 * whenever there is one to ask for (a person who signs in with Google or X has none — the same rule
 * {@see ChangePasswordController} applies, and not a weakening: the request erases nothing and a person answers it).
 */
class AccountDeletionRequestController extends Controller
{
    public function operator(Request $request, AccountDeletionRequests $requests): RedirectResponse
    {
        return $this->file($request, $requests, route('app.security'));
    }

    public function owner(Request $request, AccountDeletionRequests $requests): RedirectResponse
    {
        return $this->file($request, $requests, route('owner.security'));
    }

    private function file(Request $request, AccountDeletionRequests $requests, string $back): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $request->validate([
            'current_password' => $user->hasPassword() ? ['required', 'current_password'] : ['prohibited'],
        ]);

        $requests->file($user);

        return redirect()->to($back);
    }
}
