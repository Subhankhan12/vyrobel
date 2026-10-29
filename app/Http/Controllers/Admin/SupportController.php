<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\User;
use App\Models\UserIdentity;
use App\Support\Auth\SignInCredentials;
use App\Support\Authorization\RoleLabel;
use App\Support\Mail\UndeliverableRecipients;
use App\Support\Platform\AdminReason;
use App\Support\Platform\BreakGlass;
use App\Support\Platform\MailDelivery;
use App\Support\Platform\PlatformQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

/**
 * ⚠️ ADMIN 01 · PHASE 3 — SUPPORT TOOLS for a Vyrobel ACCOUNT HOLDER (staff and owners who sign in) — never a guest.
 *
 *   · LOOKUP by email (globally unique) — one query through the one door; the ledger records WHICH user was found,
 *     never the address that was typed.
 *   · UNLINK a social identity — ⚠️ the PRODUCT'S OWN RULE ({@see SignInCredentials::mayUnlink()}), not a copy of
 *     it: refused when it would leave the person with no way in (no password and no other provider).
 *   · MARK AN EMAIL VERIFIED — the DEPLOYMENT.md "verify when mail is down" step as an audited button. ⚠️ The
 *     column is READ BACK after the write: it has silently refused a write before.
 *   · RESEND VERIFICATION / SEND A PASSWORD RESET — ⚠️ built, and while mail is `log` the result says plainly that
 *     nothing reached anyone ({@see MailDelivery}). Never a silent success.
 *
 * Every action needs a written reason and runs through {@see PlatformQuery::mutate()}.
 */
class SupportController extends Controller
{
    public function __construct(private readonly PlatformQuery $platform) {}

    public function index(Request $request): View
    {
        $email = trim($request->string('email')->toString());
        $userId = $request->string('user')->toString();
        $result = null;

        if ($email !== '' || $userId !== '') {
            $result = $this->platform->read('platform.support.lookup', function () use ($email, $userId): ?array {
                $user = $userId !== ''
                    ? User::query()->find($userId)
                    : User::query()->where('email', $email)->first();

                return $user === null ? null : $this->describe($user);
            }, ['by' => $userId !== '' ? 'id' : 'email']);
        }

        return view('admin.support.index', [
            'email' => $email,
            'searched' => $email !== '' || $userId !== '',
            'result' => $result,
            'mailDiscards' => MailDelivery::discards(),
            'mailer' => MailDelivery::mailer(),
            'breakGlassEnabled' => BreakGlass::enabled(),
        ]);
    }

    /** UNLINK a provider identity — the product's lockout floor applies, unchanged. */
    public function unlink(Request $request, string $user, string $identity): RedirectResponse
    {
        $reason = AdminReason::from($request);
        $account = User::query()->findOrFail($user);
        $row = UserIdentity::query()->where('user_id', $account->id)->whereKey($identity)->first();

        if ($row === null) {
            return $this->back($account)->withErrors(['identity' => __('php/http/controllers/admin/support_controller.connection_gone')]);
        }
        if (! SignInCredentials::mayUnlink($account, (string) $row->getKey())) {
            return $this->back($account)->withErrors(['identity' => __('php/http/controllers/admin/support_controller.unlink_refused_only_way_in')]);
        }

        $this->platform->mutate(
            'platform.support.unlink_identity',
            $reason,
            fn () => $row->delete(),
            ['provider' => $row->provider],
            'user',
            (string) $account->id,
        );

        $gone = ! UserIdentity::query()->whereKey($identity)->exists();

        return $gone
            ? $this->back($account)->with('admin_notice', __('php/http/controllers/admin/support_controller.unlinked', ['provider' => ucfirst((string) $row->provider)]))
            : $this->back($account)->withErrors(['identity' => __('php/http/controllers/admin/support_controller.unlink_not_landed')]);
    }

    /** MARK VERIFIED — the "mail is down" procedure, audited; the column is read back, never assumed. */
    public function verifyEmail(Request $request, string $user): RedirectResponse
    {
        $reason = AdminReason::from($request);
        $account = User::query()->findOrFail($user);

        if ($account->email_verified_at !== null) {
            return $this->back($account)->withErrors(['verify' => __('php/http/controllers/admin/support_controller.already_verified')]);
        }

        // ⚠️ forceFill — `email_verified_at` is NOT fillable, and a mass-assign of it is dropped SILENTLY.
        $this->platform->mutate(
            'platform.support.verify_email',
            $reason,
            fn () => $account->forceFill(['email_verified_at' => now()])->save(),
            [],
            'user',
            (string) $account->id,
        );

        $stored = DB::table('users')->where('id', $account->id)->value('email_verified_at');

        return $stored !== null
            ? $this->back($account)->with('admin_notice', __('php/http/controllers/admin/support_controller.marked_verified', ['stored' => $stored]))
            : $this->back($account)->withErrors(['verify' => __('php/http/controllers/admin/support_controller.verify_not_landed')]);
    }

    /** RESEND the verification email — and say plainly when mail discards it. */
    public function resendVerification(Request $request, string $user): RedirectResponse
    {
        $reason = AdminReason::from($request);
        $account = User::query()->findOrFail($user);

        if ($account->email_verified_at !== null) {
            return $this->back($account)->withErrors(['verify' => __('php/http/controllers/admin/support_controller.already_verified_nothing_to_resend')]);
        }

        $discards = MailDelivery::discards();
        if (! $discards && ($refused = $this->refusedUndeliverable($account, 'verification')) !== null) {
            return $refused;
        }
        $this->platform->mutate(
            'platform.support.resend_verification',
            $reason,
            fn () => $account->sendEmailVerificationNotification(),
            ['mailer' => MailDelivery::mailer(), 'delivered' => ! $discards],
            'user',
            (string) $account->id,
        );

        return $discards
            ? $this->back($account)->withErrors(['mail' => __('php/http/controllers/admin/support_controller.verification_discarded', ['mailer' => MailDelivery::mailer()])])
            : $this->back($account)->with('admin_notice', __('php/http/controllers/admin/support_controller.verification_handed', ['mailer' => MailDelivery::mailer()]));
    }

    /** SEND A PASSWORD RESET — through the product's own broker — and say plainly when mail discards it. */
    public function passwordReset(Request $request, string $user): RedirectResponse
    {
        $reason = AdminReason::from($request);
        $account = User::query()->findOrFail($user);
        $discards = MailDelivery::discards();
        if (! $discards && ($refused = $this->refusedUndeliverable($account, 'reset')) !== null) {
            return $refused;
        }

        $status = $this->platform->mutate(
            'platform.support.password_reset',
            $reason,
            fn () => Password::broker()->sendResetLink(['email' => $account->email]),
            ['mailer' => MailDelivery::mailer(), 'delivered' => ! $discards],
            'user',
            (string) $account->id,
        );

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->back($account)->withErrors(['mail' => __('php/http/controllers/admin/support_controller.no_reset_link', ['status' => __((string) $status)])]);
        }

        return $discards
            ? $this->back($account)->withErrors(['mail' => __('php/http/controllers/admin/support_controller.reset_discarded', ['mailer' => MailDelivery::mailer()])])
            : $this->back($account)->with('admin_notice', __('php/http/controllers/admin/support_controller.reset_handed', ['mailer' => MailDelivery::mailer()]));
    }

    /**
     * ⚠️⚠️ TONIGHT 01 (2026-09-25) — an address that can NEVER receive mail (a reserved test domain, a probe account,
     * a listed no-mailbox address) is refused HERE, before anything is handed to the mailer. Since MAIL 02 the skip list
     * suppresses such a send at the last moment — and this console then said "handed to the mailer" while nobody
     * received anything: the silent success this class promises never to be. The reason is named; the address never.
     */
    private function refusedUndeliverable(User $account, string $what): ?RedirectResponse
    {
        // The stored reason stays English (it is what the mail listener logs); the screen shows it in the reader's language.
        $why = UndeliverableRecipients::displayReason((string) $account->email);

        return $why === null ? null : $this->back($account)->withErrors(['mail' => $what === 'reset'
            ? __('php/http/controllers/admin/support_controller.reset_not_sent_undeliverable', ['reason' => $why])
            : __('php/http/controllers/admin/support_controller.verification_not_sent_undeliverable', ['reason' => $why])]);
    }

    /**
     * The account holder's platform metadata — identity, credentials, memberships. Never a guest; never a password.
     *
     * @return array<string, mixed>
     */
    private function describe(User $user): array
    {
        $memberships = Membership::query()->where('user_id', $user->id)->get(['organization_id', 'status']);
        $orgs = DB::table('organizations')->whereIn('id', $memberships->pluck('organization_id'))->get(['id', 'name', 'deleted_at'])->keyBy('id');
        $roles = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)->where('model_has_roles.model_type', $user->getMorphClass())
            ->get(['model_has_roles.organization_id', 'roles.name'])->groupBy('organization_id');

        return [
            'user' => $user,
            'has_password' => $user->hasPassword(),
            'two_factor' => $user->hasTwoFactorEnabled(),
            'identities' => UserIdentity::query()->where('user_id', $user->id)->orderBy('provider')->get()
                ->map(fn (UserIdentity $i): array => [
                    'id' => (string) $i->id,
                    'provider' => (string) $i->provider,
                    'provider_email' => $i->provider_email,
                    'last_used_at' => $i->last_used_at,
                    'may_unlink' => SignInCredentials::mayUnlink($user, (string) $i->id),
                ])->all(),
            'memberships' => $memberships->map(fn (Membership $m): array => [
                'organization_id' => (string) $m->organization_id,
                'organization' => (string) ($orgs[$m->organization_id]->name ?? '—'),
                'archived' => ($orgs[$m->organization_id]->deleted_at ?? null) !== null,
                'status' => (string) $m->status,
                'roles' => collect($roles[$m->organization_id] ?? [])->pluck('name')->map(fn ($r): string => RoleLabel::for((string) $r))->sort()->values()->all(),
            ])->all(),
        ];
    }

    private function back(User $user): RedirectResponse
    {
        return redirect()->route('admin.support.index', ['user' => $user->id]);
    }
}
