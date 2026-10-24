<?php

use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\Auth\AdminInviteController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\Auth\AdminTwoFactorChallengeController;
use App\Http\Controllers\Admin\Auth\AdminTwoFactorController;
use App\Http\Controllers\Admin\BreakGlassController as AdminBreakGlassController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FunnelController as AdminFunnelController;
use App\Http\Controllers\Admin\HotelStatsController as AdminHotelStatsController;
use App\Http\Controllers\Admin\IntegrationController as AdminIntegrationController;
use App\Http\Controllers\Admin\OverviewController as AdminOverviewController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\PrivacyController as AdminPrivacyController;
use App\Http\Controllers\Admin\RevenueController as AdminRevenueController;
use App\Http\Controllers\Admin\StatusIncidentController as AdminStatusIncidentController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\SwitchController as AdminSwitchController;
use App\Http\Controllers\AnnouncementDismissController;
use App\Http\Controllers\Auth\AcceptInviteController;
use App\Http\Controllers\Auth\AcceptOwnerTeamInviteController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginApprovalController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\PasskeyController;
use App\Http\Controllers\Auth\PasskeyLoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SecurityAlertController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BookingEngineController;
use App\Http\Controllers\ChannexWebhookController;
use App\Http\Controllers\CookieChoiceController;
use App\Http\Controllers\FrontDeskActionController;
use App\Http\Controllers\Guest\GuestAccountController;
use App\Http\Controllers\Guest\GuestArrivalController;
use App\Http\Controllers\Guest\GuestCheckinController;
use App\Http\Controllers\Guest\GuestClaimController;
use App\Http\Controllers\Guest\GuestDocumentController;
use App\Http\Controllers\Guest\GuestExtrasController;
use App\Http\Controllers\Guest\GuestFolioPaymentController;
use App\Http\Controllers\Guest\GuestHelpController;
use App\Http\Controllers\Guest\GuestHomeController;
use App\Http\Controllers\Guest\GuestLoginController;
use App\Http\Controllers\Guest\GuestManageController;
use App\Http\Controllers\Guest\GuestMessageAttachmentController;
use App\Http\Controllers\Guest\GuestMessagesController;
use App\Http\Controllers\Guest\GuestNotificationsController;
use App\Http\Controllers\Guest\GuestPasswordResetController;
use App\Http\Controllers\Guest\GuestPaymentsController;
use App\Http\Controllers\Guest\GuestReceiptController;
use App\Http\Controllers\Guest\GuestReservationController;
use App\Http\Controllers\Guest\GuestReviewController;
use App\Http\Controllers\Guest\GuestSavedController;
use App\Http\Controllers\Guest\GuestTripsController;
use App\Http\Controllers\Guest\GuestWalletController;
use App\Http\Controllers\Guest\MarketingUnsubscribeController;
use App\Http\Controllers\HousekeepingActionController;
use App\Http\Controllers\MarketingContactController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MessageAttachmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\Operator\BillingActionController;
use App\Http\Controllers\Operator\EmailPreviewController;
use App\Http\Controllers\Operator\EmailTemplateActionController;
use App\Http\Controllers\Operator\EmailTemplateController;
use App\Http\Controllers\Operator\FolioPdfController;
use App\Http\Controllers\Operator\GroupBookingController;
use App\Http\Controllers\Operator\GuestActionController;
use App\Http\Controllers\Operator\GuestBulkActionController;
use App\Http\Controllers\Operator\GuestRequestAdminController;
use App\Http\Controllers\Operator\InboxActionController;
use App\Http\Controllers\Operator\InboxLiveController;
use App\Http\Controllers\Operator\InventoryActionController;
use App\Http\Controllers\Operator\InviteOwnerController;
use App\Http\Controllers\Operator\InviteOwnerPreviewController;
use App\Http\Controllers\Operator\MaintenanceActionController;
use App\Http\Controllers\Operator\MaintenanceCostExportController;
use App\Http\Controllers\Operator\MaintenanceSettingsController;
use App\Http\Controllers\Operator\OperatorConnectController;
use App\Http\Controllers\Operator\OperatorProfileController;
use App\Http\Controllers\Operator\OperatorRateProposalController;
use App\Http\Controllers\Operator\OperatorReportsExportController;
use App\Http\Controllers\Operator\OperatorReviewsController;
use App\Http\Controllers\Operator\OperatorSecurityController;
use App\Http\Controllers\Operator\OperatorTaskStateController;
use App\Http\Controllers\Operator\OwnerExportController;
use App\Http\Controllers\Operator\OwnerInviteActionController;
use App\Http\Controllers\Operator\OwnerManageController;
use App\Http\Controllers\Operator\PayoutRunActionController;
use App\Http\Controllers\Operator\PropertyController;
use App\Http\Controllers\Operator\PropertySwitchController;
use App\Http\Controllers\Operator\PulseController;
use App\Http\Controllers\Operator\RateActionController;
use App\Http\Controllers\Operator\ReservationCreateController;
use App\Http\Controllers\Operator\ReservationLifecycleController;
use App\Http\Controllers\Operator\RoleAssignmentController;
use App\Http\Controllers\Operator\RoomTypePhotoController;
use App\Http\Controllers\Operator\ScheduleController;
use App\Http\Controllers\Operator\SeasonActionController;
use App\Http\Controllers\Operator\SettingsActionController;
use App\Http\Controllers\Operator\TeamChatController;
use App\Http\Controllers\Operator\TeamChatFileController;
use App\Http\Controllers\Operator\UnitExportController;
use App\Http\Controllers\Operator\UnitRateActionController;
use App\Http\Controllers\Operator\UnitRequestActionController;
use App\Http\Controllers\Operator\WorkspaceSwitchController;
use App\Http\Controllers\Owner\OwnerAccountController;
use App\Http\Controllers\Owner\OwnerAgreementController;
use App\Http\Controllers\Owner\OwnerAgreementPdfController;
use App\Http\Controllers\Owner\OwnerArchiveZipController;
use App\Http\Controllers\Owner\OwnerAttentionController;
use App\Http\Controllers\Owner\OwnerBalanceExportController;
use App\Http\Controllers\Owner\OwnerBlockController;
use App\Http\Controllers\Owner\OwnerBookingConfirmationPdfController;
use App\Http\Controllers\Owner\OwnerBookingsExportController;
use App\Http\Controllers\Owner\OwnerBookingsTargetController;
use App\Http\Controllers\Owner\OwnerBookingSummaryPdfController;
use App\Http\Controllers\Owner\OwnerConnectController;
use App\Http\Controllers\Owner\OwnerConnectedAppController;
use App\Http\Controllers\Owner\OwnerExpenseApprovalController;
use App\Http\Controllers\Owner\OwnerExpensesExportController;
use App\Http\Controllers\Owner\OwnerGuardrailController;
use App\Http\Controllers\Owner\OwnerHelpAskController;
use App\Http\Controllers\Owner\OwnerMessageActionController;
use App\Http\Controllers\Owner\OwnerPayoutMethodController;
use App\Http\Controllers\Owner\OwnerPayoutPreferenceController;
use App\Http\Controllers\Owner\OwnerPayoutRetryController;
use App\Http\Controllers\Owner\OwnerPayoutsExportController;
use App\Http\Controllers\Owner\OwnerProfileController;
use App\Http\Controllers\Owner\OwnerRateController;
use App\Http\Controllers\Owner\OwnerRateProposalController;
use App\Http\Controllers\Owner\OwnerRemittancePdfController;
use App\Http\Controllers\Owner\OwnerReportEmailController;
use App\Http\Controllers\Owner\OwnerSecurityController;
use App\Http\Controllers\Owner\OwnerStatementHistoryExportController;
use App\Http\Controllers\Owner\OwnerStatementPdfController;
use App\Http\Controllers\Owner\OwnerTaxController;
use App\Http\Controllers\Owner\OwnerTeamController;
use App\Http\Controllers\Owner\OwnerUnitRequestController;
use App\Http\Controllers\Owner\OwnerUnitsExportController;
use App\Http\Controllers\Owner\OwnerUnitStatusController;
use App\Http\Controllers\ReservationFolioActionController;
use App\Http\Controllers\StatusPageController;
use App\Http\Controllers\StorefrontLandingController;
use App\Http\Controllers\StorefrontSitemapController;
use App\Http\Controllers\StripeConnectWebhookController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\EnforceIpAllowlist;
use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Http\Middleware\EnsureOwnerCapability;
use App\Http\Middleware\EnsureOwnerPortalAccess;
use App\Http\Middleware\EnsurePlanFeature;
use App\Http\Middleware\EnsurePlatformAdminTwoFactor;
use App\Http\Middleware\EnsurePlatformCapability;
use App\Http\Middleware\EnsurePrimaryOwner;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\EnterBreakGlassSession;
use App\Http\Middleware\NoIndex;
use App\Http\Middleware\RefuseSuspendedOrganization;
use App\Http\Middleware\RequirePlatformAdminTwoFactorPending;
use App\Http\Middleware\RequireTwoFactorPending;
use App\Http\Middleware\ResolvePublicOrganization;
use App\Http\Middleware\ResolvePublicProperty;
use App\Http\Middleware\SetCurrentPropertyForWebRequest;
use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Http\Middleware\SetTenantForGuestRequest;
use App\Http\Middleware\TrackPlatformAdminSession;
use App\Livewire\FrontDesk;
use App\Livewire\HousekeepingBoard;
use App\Livewire\MaintenanceList;
use App\Livewire\MaintenanceTicketDetail;
use App\Livewire\Operator\GroupBooking;
use App\Livewire\Operator\GuestProfile;
use App\Livewire\Operator\GuestsList;
use App\Livewire\Operator\NewReservation;
use App\Livewire\Operator\OperatorBilling;
use App\Livewire\Operator\OperatorBillingCheckout;
use App\Livewire\Operator\OperatorChannels;
use App\Livewire\Operator\OperatorDashboard;
use App\Livewire\Operator\OperatorEmailEditor;
use App\Livewire\Operator\OperatorInbox;
use App\Livewire\Operator\OperatorInsights;
use App\Livewire\Operator\OperatorMaintenanceCosts;
use App\Livewire\Operator\OperatorMaintenanceSettings;
use App\Livewire\Operator\OperatorNotificationPreferences;
use App\Livewire\Operator\OperatorNotifications;
use App\Livewire\Operator\OperatorOwnerDetail;
use App\Livewire\Operator\OperatorOwners;
use App\Livewire\Operator\OperatorPortfolio;
use App\Livewire\Operator\OperatorProfile;
use App\Livewire\Operator\OperatorReports;
use App\Livewire\Operator\OperatorRoles;
use App\Livewire\Operator\OperatorSchedule;
use App\Livewire\Operator\OperatorSearch;
use App\Livewire\Operator\OperatorSecurity;
use App\Livewire\Operator\OperatorSettings;
use App\Livewire\Operator\OperatorTeamChat;
use App\Livewire\Operator\OperatorUnitRequests;
use App\Livewire\Operator\PayoutRun;
use App\Livewire\Operator\RatesGrid;
use App\Livewire\Operator\ReservationRefund;
use App\Livewire\Operator\ReservationsList;
use App\Livewire\Owner\OwnerAddUnit;
use App\Livewire\Owner\OwnerAgreement;
use App\Livewire\Owner\OwnerArchive;
use App\Livewire\Owner\OwnerBalance;
use App\Livewire\Owner\OwnerBlockCalendar;
use App\Livewire\Owner\OwnerBookingDetail;
use App\Livewire\Owner\OwnerBookings;
use App\Livewire\Owner\OwnerDashboard;
use App\Livewire\Owner\OwnerDocuments;
use App\Livewire\Owner\OwnerExpenses;
use App\Livewire\Owner\OwnerHelp;
use App\Livewire\Owner\OwnerInsights;
use App\Livewire\Owner\OwnerMessages;
use App\Livewire\Owner\OwnerMyUnits;
use App\Livewire\Owner\OwnerNotificationPreferences;
use App\Livewire\Owner\OwnerNotifications;
use App\Livewire\Owner\OwnerPayoutMethod;
use App\Livewire\Owner\OwnerPayouts;
use App\Livewire\Owner\OwnerRates;
use App\Livewire\Owner\OwnerReports;
use App\Livewire\Owner\OwnerReviews;
use App\Livewire\Owner\OwnerSecurity;
use App\Livewire\Owner\OwnerSettings;
use App\Livewire\Owner\OwnerStatementView;
use App\Livewire\Owner\OwnerTaxCenter;
use App\Livewire\Owner\OwnerTeam;
use App\Livewire\Owner\OwnerUnitDetail;
use App\Livewire\Owner\OwnerWelcome;
use App\Livewire\ReservationDetail;
use App\Livewire\ReservationRack;
use App\Livewire\UnitsList;
use App\Models\MessageThread;
use App\Support\Ops\Heartbeat;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Platform\PlatformAdminRole;
use Illuminate\Support\Facades\Route;

/*
 * ⚠️ `/` IS THE MARKETING HOME (front-website ruling D2, 2026-08-21). It previously returned the stock
 * Laravel `welcome` splash — grepped before replacing: `routes/web.php:212` was the ONLY reference to that
 * view anywhere, and the only test touching `/` was the Gate-1 foundation guard. (`/owner/welcome` is an
 * unrelated Livewire onboarding route.) The view file is LEFT IN PLACE, untouched: it is Laravel's own
 * scaffold, harmless, and deleting it is not this gate's business.
 *
 * ⚠️ The marketing routes live in ONE group registered BELOW the storefront (see "THE MARKETING SITE"),
 * so they are read after `/book/*` and `/stay/*`. `/` cannot collide with either — different prefixes —
 * but the ordering is explicit so a reviewer can see it rather than infer it.
 */
Route::get('/', [MarketingController::class, 'home'])->name('marketing.page.home');

/*
 * Shared Vyrobel login (operator + owner) — real, session-based auth (Auth Phase A). The login POST is
 * CSRF-protected (web group; NOT in the CSRF-except list) and rate-limited per (email, IP) in
 * LoginRequest. Logout is a CSRF-protected POST behind `auth`. `/app/*` + `/owner/*` require `auth`
 * (redirect here when unauthenticated); `/book/*` stays public.
 */
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');

/*
 * Vyrobel self-signup / ONBOARDING (Phase 7a) — PUBLIC, pre-auth. Provisions a brand-new operator org
 * atomically (org + roles + founder + Property Owner + first property) via TenantProvisioningService, then
 * logs the founder in. Rate-limited against abuse; enumeration handled neutrally (see the controller). The
 * founding user's auth is hardened like the rest of the chapter (hashed password, session regenerated).
 */
Route::get('/signup', [OnboardingController::class, 'create'])->name('signup');
Route::post('/signup', [OnboardingController::class, 'store'])->middleware('throttle:5,1');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
 * ⚠️⚠️ SOCIAL SIGN-IN (Google, operator realm) — PUBLIC, and OFF ENTIRELY UNTIL THE FOUNDER CONFIGURES THE
 * CONSOLE CREDENTIALS. Both endpoints 404 while `services.google.client_id`/`client_secret` are empty, which
 * is the state that ships today: no button, no route, no error page. The check lives in the controller
 * rather than here, so a cached route table cannot outlive the credentials that justified it.
 *
 * ⚠️ The matching rule is NOT in this file and NOT in the controller — see App\Support\Auth\SocialLinking.
 * A returning user is matched on (provider, provider_user_id) and NEVER on their email address.
 */
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToProvider'])
    ->defaults('provider', 'google')
    ->middleware('throttle:10,1')->name('social.google.redirect');
Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])
    ->defaults('provider', 'google')
    ->middleware('throttle:10,1')->name('social.google.callback');

// ⚠️ X / Twitter — the same shape, its own hardcoded provider default. No wildcard segment anywhere, so
//    there is no provider name for a caller to supply and nothing to validate at runtime.
Route::get('/auth/twitter/redirect', [SocialAuthController::class, 'redirectToProvider'])
    ->defaults('provider', 'twitter')
    ->middleware('throttle:10,1')->name('social.twitter.redirect');
Route::get('/auth/twitter/callback', [SocialAuthController::class, 'callback'])
    ->defaults('provider', 'twitter')
    ->middleware('throttle:10,1')->name('social.twitter.callback');

/*
 * Notification mark-read writes (Phase 3) — shared by the operator + owner feeds. Behind `auth` only (both
 * portals). SELF-ONLY by construction: the controller writes through $request->user()->notifications, so a
 * cross-user id 404s — a user can only ever mark their OWN notifications read.
 */
Route::middleware('auth')->group(function () {
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    // D29-5 + DO6-4 — DISMISS, mounted beside mark-read in the SAME shared group so both realms reach ONE
    // implementation. ⚠️ Not to be confused with `owner.attention.dismiss` further down: that dismisses an
    // owner ATTENTION SIGNAL (owner_attention_states), a different entity entirely.
    Route::post('/notifications/{notification}/dismiss', [NotificationController::class, 'dismiss'])->name('notifications.dismiss');
});

/*
 * 2FA login CHALLENGE (Auth · Step 2) — reachable ONLY in the "2FA pending" state (correct password given,
 * not yet logged in) via RequireTwoFactorPending. That pending marker is a plain session key, NOT the auth
 * guard, so it grants no authenticated access — /app + /owner reject a pending user by construction. The POST
 * verifies a TOTP code OR a single-use recovery code and is rate-limited in the controller (per pending user
 * + IP); CSRF-protected (web group). Session regenerated on success (fixation protection).
 */
Route::middleware(RequireTwoFactorPending::class)->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->middleware('throttle:login')->name('two-factor.login');
});

/*
 * LOGIN APPROVAL for new devices (Wave B · GATE 4) — the pending "approve this new device by email" flow. The
 * pending browser holds a plain session key (NOT the auth guard, so /app + /owner reject it); `approve` is the
 * emailed SIGNED link (verified by the `signed` middleware, may be opened on any device); `complete` finishes the
 * sign-in only once the request is approved (single-use). CSRF-protected (web group), rate-limited.
 */
Route::middleware('throttle:12,1')->group(function () {
    Route::get('/login-approval', [LoginApprovalController::class, 'show'])->name('login-approval.show');
    Route::post('/login-approval/continue', [LoginApprovalController::class, 'complete'])->name('login-approval.complete');
    Route::get('/login-approval/approve/{approval}', [LoginApprovalController::class, 'approve'])->middleware('signed')->name('login-approval.approve');
});

/*
 * PASSKEY SIGN-IN (Wave B · GATE 4) — passwordless WebAuthn login. The browser drives the ceremony; the assertion is
 * verified server-side by the real web-auth/webauthn-lib validator, and only a genuine cryptographic success logs the
 * matched user in. Guest-accessible (there is no session user yet), CSRF-protected (web group), rate-limited.
 */
Route::middleware('throttle:20,1')->group(function () {
    Route::post('/passkeys/login/options', [PasskeyLoginController::class, 'options'])->name('passkeys.login.options');
    Route::post('/passkeys/login', [PasskeyLoginController::class, 'login'])->name('passkeys.login');
});

/*
 * PUBLIC forgot / reset password (Auth Phase B · finishing sweep). Laravel Password broker — tokened,
 * single-use, expiring tokens. NO enumeration (the request-link endpoint always responds generically;
 * ACTIVE users only), rate-limited against abuse, CSRF-protected (web group). On reset: hashed password,
 * remember-token rotated, session regenerated, signed in + routed by role.
 */
Route::middleware('throttle:6,1')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

/*
 * MARKETING UNSUBSCRIBE — public, no login, no realm (wireframe-parity sweep · HIGH-5).
 *
 * Marketing email had NO opt-out route: the only toggle lived inside a CLAIMED guest account, so a guest who
 * never claimed one could not unsubscribe at all. Authorised purely by the native HMAC `signed` middleware — the
 * link must work from an old email, on any device, with no session — so it sits OUTSIDE the guest realm rather
 * than pretending to be part of it. Deliberately unauthenticated: requiring a login here is the very defect.
 *
 * GET only renders (a prefetching scanner must not opt anyone out); POST performs it and is also the RFC 8058
 * one-click target, so it is CSRF-exempt — an inbox provider posts from outside any session and the signature,
 * not a token, is the authorisation. Throttled per-IP against signature-guessing.
 */
Route::middleware('throttle:20,1')->group(function () {
    Route::get('/unsubscribe/{guest}', [MarketingUnsubscribeController::class, 'show'])
        ->middleware('signed')->name('marketing.unsubscribe');
    Route::post('/unsubscribe/{guest}', [MarketingUnsubscribeController::class, 'update'])
        ->middleware('signed')->name('marketing.unsubscribe.confirm');
});

/*
 * GUEST PORTAL — the separate 'guest' realm (Phase 4a). PUBLIC claim/verify pages (a guest has no account yet) +
 * a minimal authed guest home. BOOKING-TIED: the claim only ever sends a link to an email that has a real booking
 * (no enumeration — the response is neutral either way). The verify/complete links are native HMAC-`signed`,
 * expiring URLs. NEVER point an /app or /owner route at the guest guard — the realms are separate by construction.
 */
Route::prefix('guest')->name('guest.')->group(function () {
    // Booking-tied claim (SG2).
    Route::get('/claim', [GuestClaimController::class, 'create'])->name('claim');
    Route::post('/claim', [GuestClaimController::class, 'store'])->middleware('throttle:6,1')->name('claim.store');
    Route::get('/claim/sent', [GuestClaimController::class, 'sent'])->name('claim.sent');
    Route::get('/claim/verify/{guest}/{hash}', [GuestClaimController::class, 'verify'])->middleware('signed')->name('claim.verify');
    Route::post('/claim/verify/{guest}/{hash}', [GuestClaimController::class, 'claim'])->middleware('signed', 'throttle:6,1')->name('claim.complete');

    // Login + password reset (SG3) — on the guest guard/broker. Rate-limited; reset is no-enumeration + hashed tokens.
    Route::get('/login', [GuestLoginController::class, 'create'])->name('login');
    Route::post('/login', [GuestLoginController::class, 'store'])->middleware('throttle:login');
    Route::post('/logout', [GuestLoginController::class, 'destroy'])->name('logout');
    Route::middleware('throttle:6,1')->group(function () {
        Route::get('/forgot-password', [GuestPasswordResetController::class, 'showForgot'])->name('password.request');
        Route::post('/forgot-password', [GuestPasswordResetController::class, 'sendLink'])->name('password.email');
        Route::get('/reset-password/{token}', [GuestPasswordResetController::class, 'showReset'])->name('password.reset');
        Route::post('/reset-password', [GuestPasswordResetController::class, 'reset'])->name('password.update');
    });

    // Authenticated guest area — behind the GUEST guard (an unauthenticated visitor bounces to the GUEST login,
    // never the operator login) + SetTenantForGuestRequest (portal reads resolve within the guest's OWN org; the
    // per-booking isolation is that every controller resolves THROUGH the authed guest's own relations).
    Route::middleware(['auth:guest', SetTenantForGuestRequest::class])->group(function () {
        Route::get('/trips', [GuestTripsController::class, 'index'])->name('trips');   // Phase 4b · the landing
        Route::get('/reservation/{reservation}', [GuestReservationController::class, 'show'])->name('reservation');
        // Manage booking (SG3) — date-change/add-on/cancellation as operator-confirmed REQUESTS (never guest writes).
        Route::get('/reservation/{reservation}/manage', [GuestManageController::class, 'show'])->name('manage');
        Route::post('/reservation/{reservation}/manage/date-change', [GuestManageController::class, 'requestDateChange'])->middleware('throttle:12,1')->name('manage.date-change');
        Route::post('/reservation/{reservation}/manage/add-ons', [GuestManageController::class, 'requestAddOns'])->middleware('throttle:12,1')->name('manage.add-ons');
        // Add-ons — "Enhance your stay": the real AddOn catalogue; the form posts to manage.add-ons (D4).
        Route::get('/reservation/{reservation}/extras', [GuestExtrasController::class, 'show'])->name('extras');
        Route::post('/reservation/{reservation}/manage/cancel', [GuestManageController::class, 'requestCancellation'])->middleware('throttle:12,1')->name('manage.cancel');
        // Online check-in (SG4) — the GUEST's pre-check-in intent (stamps guest_checked_in_at); NOT the operator's
        // authoritative check-in. Arrival info (SG4) — directions/times + the timing-gated door code & wifi.
        Route::get('/reservation/{reservation}/check-in', [GuestCheckinController::class, 'show'])->name('checkin');
        Route::post('/reservation/{reservation}/check-in', [GuestCheckinController::class, 'submit'])->middleware('throttle:12,1')->name('checkin.submit');
        Route::get('/reservation/{reservation}/arrival', [GuestArrivalController::class, 'show'])->name('arrival');
        Route::get('/reservation/{reservation}/arrival/pdf', [GuestArrivalController::class, 'pdf'])->name('arrival.pdf');
        // Review (Phase 5) — a guest reviews their OWN completed stay (foreign/future/cancelled → 404); one per stay.
        Route::get('/reservation/{reservation}/review', [GuestReviewController::class, 'show'])->name('review');
        Route::post('/reservation/{reservation}/review', [GuestReviewController::class, 'submit'])->middleware('throttle:12,1')->name('review.submit');
        // Payments (SG1) — balance + history across owned bookings (card charge = deferred Stripe rail) + the
        // per-booking Folio Receipt + the money PDFs (each owned-booking-only; a cross-guest id → 404).
        Route::get('/payments', [GuestPaymentsController::class, 'index'])->name('payments');
        // Phase 8 · SG2 — the guest pays their OWN booking's balance by card (hosted Stripe Checkout; the card
        // never touches the server). The folio settles on the payment_intent.succeeded webhook, never the
        // redirect. Rate-limited; the booking is resolved through the guest's own reservations (#28).
        Route::post('/payments/{reservation}/pay', [GuestFolioPaymentController::class, 'pay'])->middleware('throttle:10,1')->name('payments.pay');
        Route::get('/reservation/{reservation}/receipt', [GuestReceiptController::class, 'show'])->name('receipt');
        Route::get('/reservation/{reservation}/receipt.pdf', [GuestDocumentController::class, 'folioReceipt'])->name('doc.receipt');
        Route::get('/reservation/{reservation}/invoice.pdf', [GuestDocumentController::class, 'vatInvoice'])->name('doc.invoice');
        Route::get('/reservation/{reservation}/statement.pdf', [GuestDocumentController::class, 'statement'])->name('doc.statement');
        Route::get('/reservation/{reservation}/refund.pdf', [GuestDocumentController::class, 'refundReceipt'])->name('doc.refund');
        Route::get('/reservation/{reservation}/credit-note.pdf', [GuestDocumentController::class, 'creditNote'])->name('doc.credit-note');
        // Wallet (SG2) — the guest's credit balance (derived from the append-only ledger), redeem-a-code, apply
        // credit to a booking. Guest-scoped; no overdraw; a foreign booking for an apply → 404.
        Route::get('/wallet', [GuestWalletController::class, 'show'])->name('wallet');
        Route::post('/wallet/redeem', [GuestWalletController::class, 'redeem'])->middleware('throttle:12,1')->name('wallet.redeem');
        Route::post('/wallet/apply/{reservation}', [GuestWalletController::class, 'apply'])->middleware('throttle:12,1')->name('wallet.apply');
        // Messages (SG3) — the guest side of the Unified Inbox: the guest's own thread(s), sending a real message
        // via InboxService. Guest-scoped by guest_id (a foreign / owner thread → 404); no reimplementation.
        // Help centre — a READ-ONLY FAQ over the ONE curated HelpKnowledgeBase (the same engine the owner
        // Help page and the grounded assistant read). Search + category are GET params (§N.4k: no write on
        // this page). Guest-guarded because it renders in the guest shell and every answer links a guest
        // screen — it holds no guest data, so there is nothing to scope.
        Route::get('/help', [GuestHelpController::class, 'show'])->name('help');
        // GUEST NOTIFICATIONS · GATE 1 — mark-read / dismiss, on the SHARED NotificationController.
        // ⚠️ REUSED, NOT FORKED (§N.8f). A separate guest controller was written first, on the belief that
        // `$request->user()` is null for a guest — that belief was WRONG and a probe corrected it: Laravel's
        // `Authenticate` middleware calls `shouldUse($guard)` for the guard that succeeded, so INSIDE an
        // `auth:guest` route the default guard IS `guest` and `$request->user()` returns the Guest. The
        // controller's self-scoping (`$request->user()->notifications()`) therefore holds for this realm too.
        //
        // ⚠️⚠️ THAT MAKES `auth:guest` LOAD-BEARING FOR ISOLATION, not just for access. These three routes
        // MUST stay inside this group: outside it, `$request->user()` would resolve on the `web` guard and a
        // signed-in OPERATOR could reach a guest mark-read route. `GuestNotificationsRouteGuardTest` pins that.
        //
        // ⚠️ No GET feed page yet: the guest notifications SCREEN + bell placement are a follow-on gate (there
        // is no guest notifications wireframe to build against).
        // ⚠️ GATE 3 — the FEED SCREEN the bell's link opens. Read-only + self-scoped; the writes below are
        // the shared controller, reused.
        Route::get('/notifications', [GuestNotificationsController::class, 'index'])->name('notifications');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/{notification}/dismiss', [NotificationController::class, 'dismiss'])->name('notifications.dismiss');

        Route::get('/messages', [GuestMessagesController::class, 'index'])->name('messages');
        Route::post('/messages', [GuestMessagesController::class, 'start'])->middleware('throttle:12,1')->name('messages.start');
        // a file the property sent in the guest's own conversation (2026-10-06) — that guest only, signed in (DEC-m)
        Route::get('/messages/attachments/{media}', GuestMessageAttachmentController::class)->name('messages.attachment');
        Route::get('/messages/{thread}', [GuestMessagesController::class, 'show'])->name('messages.show');
        Route::post('/messages/{thread}', [GuestMessagesController::class, 'send'])->middleware('throttle:20,1')->name('messages.send');
        // Saved (SG4) — wishlist (saved rooms) + waitlist (join for sold-out dates). Real, self-only stores;
        // every item/entry resolves through the authed guest's guest_id (a foreign id → 404).
        Route::get('/saved', [GuestSavedController::class, 'show'])->name('saved');
        Route::post('/saved/wishlist', [GuestSavedController::class, 'addWishlist'])->middleware('throttle:30,1')->name('saved.wishlist.add');
        Route::post('/saved/wishlist/{item}/remove', [GuestSavedController::class, 'removeWishlist'])->name('saved.wishlist.remove');
        Route::post('/saved/waitlist', [GuestSavedController::class, 'joinWaitlist'])->middleware('throttle:30,1')->name('saved.waitlist.join');
        Route::post('/saved/waitlist/{entry}/leave', [GuestSavedController::class, 'leaveWaitlist'])->name('saved.waitlist.leave');
        Route::post('/saved/waitlist/{entry}/reconfirm', [GuestSavedController::class, 'reconfirmWaitlist'])->name('saved.waitlist.reconfirm');
        Route::get('/home', [GuestHomeController::class, 'index'])->name('home');       // legacy → redirects to trips
        Route::get('/account', [GuestAccountController::class, 'show'])->name('account');
        Route::post('/account', [GuestAccountController::class, 'updateProfile'])->name('account.update');
        Route::post('/account/password', [GuestAccountController::class, 'changePassword'])->middleware('throttle:6,1')->name('account.password');
        Route::post('/account/preferences', [GuestAccountController::class, 'updatePreferences'])->name('account.preferences');
        // Delete = a reversible 14-day SCHEDULED erase (D2); undo restores the account before the sweep runs.
        Route::post('/account/delete', [GuestAccountController::class, 'destroy'])->name('account.delete');
        Route::post('/account/delete/undo', [GuestAccountController::class, 'undoDeletion'])->name('account.delete.undo');
    });
});

/*
 * PUBLIC accept-invite / set-password (Auth Phase B · Step 2). No auth — the invitee has no usable
 * password yet; the sha256-hashed single-use token in the URL is the credential. Rate-limited (per IP)
 * against token guessing; CSRF-protected POST (web group). Invalid/expired/spent → a generic page.
 */
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/invite/{token}', [AcceptInviteController::class, 'show'])->name('invite.accept');
    Route::post('/invite/{token}', [AcceptInviteController::class, 'store']);
    // Phase 7c · SG3 — the OWNER-TEAM claim (owner-realm parallel): same public, hashed, single-use,
    // rate-limited machinery; the invitee sets a password → joins their owner's team at the assigned role.
    Route::get('/team-invite/{token}', [AcceptOwnerTeamInviteController::class, 'show'])->name('owner.team.invite.accept');
    Route::post('/team-invite/{token}', [AcceptOwnerTeamInviteController::class, 'store']);
});

/*
 * Public, unauthenticated INBOUND Channex webhook (Step 4). Signature-authenticated, CSRF-exempt
 * (see bootstrap/app.php). {channel} is our channel ULID; the controller resolves it under a
 * super-admin context then sets the tenant context — no route-model binding (that would trip the
 * tenant scope before context is set).
 */
Route::post('/webhooks/channex/{channel}', ChannexWebhookController::class)->name('webhooks.channex');

/*
 * Public, unauthenticated INBOUND Stripe webhook (Step 6). Signature-authenticated (Stripe's real
 * scheme), CSRF-exempt. Resolves the payment (hence org) under super-admin then sets context —
 * no route params, the event body carries the payment_intent id.
 */
Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');
// Phase 8 · SG3 — the SEPARATE Stripe CONNECT webhook endpoint (its own signing secret): owner-account KYC
// (account.updated) + payout transfer confirmation (transfer.created). Signature-verified, idempotent.
Route::post('/webhooks/stripe/connect', StripeConnectWebhookController::class)->name('webhooks.stripe.connect');

/*
 * Public, unauthenticated BOOKING ENGINE (Step 7) — backend + unstyled contract stub. Tenant/
 * property context is set by ResolvePublicProperty (real context, never a guard bypass) from the
 * {property} slug; every action re-loads it tenant-scoped. Rate-limited (throttle:booking) against
 * scraping/abuse. POST endpoints are CSRF-exempt (JSON API; see bootstrap/app.php). Styled UI is
 * the deferred design pass.
 */
Route::prefix('book/{property}')
    ->middleware([ResolvePublicProperty::class, 'throttle:booking'])
    ->name('booking.')
    ->group(function () {
        Route::get('/', [BookingEngineController::class, 'browse'])->name('browse');
        // Design pass · Booking Screen 2 — public room detail (read-only; property-scoped → cross-property 404).
        Route::get('/rooms/{roomType}', [BookingEngineController::class, 'room'])->name('room');
        // Design pass · Booking Screen 3 — styled checkout. GET review (read-only) + CSRF-protected POST
        // WRITE that books THROUGH AssignmentService + FolioService (never a parallel path); PRG to the
        // styled confirmation. `book/{property}/checkout` is NOT in the CSRF-except list (see bootstrap).
        Route::get('/checkout', [BookingEngineController::class, 'checkout'])->name('checkout');
        Route::post('/checkout', [BookingEngineController::class, 'reserve'])->name('reserve');
        Route::get('/reservation/{quote}', [BookingEngineController::class, 'reservation'])->name('reservation');
        // JSON API (machine clients) — the real final backend contract, CSRF-exempt by path (bootstrap).
        Route::get('/search', [BookingEngineController::class, 'search'])->name('search');
        Route::post('/quote', [BookingEngineController::class, 'quote'])->name('quote');
        Route::post('/book', [BookingEngineController::class, 'book'])->name('book');
        Route::get('/confirmation/{quote}', [BookingEngineController::class, 'confirmation'])->name('confirmation');
    });

/*
 * Public, unauthenticated multi-property storefront LANDING (Guest Browse · founder decision D1). Org
 * context is set by ResolvePublicOrganization (real tenant context, never a guard bypass) from the
 * {organization} slug; every aggregate re-loads tenant-scoped. Rate-limited (throttle:booking). The
 * search action is a GET redirect into the per-property booking.browse (no state written here).
 */
Route::prefix('stay/{organization}')
    ->middleware([ResolvePublicOrganization::class, 'throttle:booking'])
    ->name('storefront.')
    ->group(function () {
        Route::get('/', [StorefrontLandingController::class, 'show'])->name('landing');
        Route::get('/search', [StorefrontLandingController::class, 'search'])->name('search');
    });

/*
 * STOREFRONT SEO (D10) — the generated storefront sitemap.
 *
 * A LITERAL path with no parameters, so it cannot shadow `/book/{property}` or `/stay/{organization}`, and
 * it is registered here (above marketing) purely to keep the whole public storefront surface in one place.
 *
 * It lists ONLY sellable properties: the inclusion test is the booking engine's own search, borrowed via
 * StorefrontOffer rather than re-derived, so the sitemap and the property pages can never disagree about
 * what is actually on sale.
 *
 * It is NOT `sitemap.xml` — that name belongs to the marketing sitemap, which is SEO finalisation and out
 * of scope until the marketing page set stops moving.
 */
Route::get('/sitemap-storefront.xml', StorefrontSitemapController::class)->name('storefront.sitemap');

/*
 * ══ THE MARKETING SITE (front-website Gate 2) ══════════════════════════════════════════════════════
 *
 * ⚠️ REGISTERED AFTER THE STOREFRONT, DELIBERATELY. Everything above this line is the SEALED,
 * MONEY-CARRYING guest path — `/book/{property}` (…/checkout is `booking.reserve`, the money route) and
 * `/stay/{organization}`. Laravel matches in registration order, so putting marketing below makes it
 * structurally impossible for a marketing route to be reached first. `MarketingRouteIsolationTest`
 * proves it, and was itself proven capable of failing (a shadowing route was introduced, observed RED,
 * and removed).
 *
 * ⚠️ EVERY SLUG IS WHITELISTED — read from the design's OWN `sitemap.xml`, not derived from filenames
 * (they differ: "Vyrobel Guide Setup" is `/docs/first-property`). ⚠️ There is NO free `/{slug}` wildcard,
 * NO `Route::fallback`, and NO `/{locale}` prefix — the locale route is deferred with D7 precisely
 * because an unconstrained `/{locale}/...` is the one pattern that could swallow `/book/...` (risk E1).
 *
 * ⚠️ Route names are `marketing.page.{key}`, which is the contract `MarketingUrls::pending()` reads: a
 * page not registered here renders across the whole site as a MARKED non-link, never a silent 404 — so
 * this list is also the switch that lights the nav up. ⚠️ The `page.` segment is NOT decoration: the
 * name `marketing.*` was ALREADY taken by the guest EMAIL-marketing unsubscribe routes, and sharing the
 * namespace made an unrelated route answer `Route::has('marketing.unsubscribe')`. Caught by a
 * count-pinned test, which is exactly what count-pinning is for.
 *
 * ⚠️ NOT registered yet, and each absence is a RULING, not a backlog item — see the group below for the
 * current list (`alternatives` / `compare`, the 27 docs, and the four Gate 7 pages refused on 2026-08-22).
 *
 * ⚠️ THIS COMMENT WAS STALE AND SAID THE OPPOSITE OF THE CODE. It read "NOT registered yet, on purpose:
 * `/try-booking` … and every page from Gates 3–6" while `/try-booking` (Gate 4) and the four Gate 3b
 * scenario pages were registered thirty lines below it. Found by the Gate 7 audit reading the file rather
 * than the comment. ⚠️ A comment that outlives its subject is a claim like any other: nothing fails when it
 * goes stale, and the next reader believes it.
 */
/*
 * ══ THE COOKIE NOTICE (front-website Gate 6, ruling D11) ═══════════════════════════════════════════
 *
 * ⚠️ DELIBERATELY OUTSIDE the `marketing.page.*` group: it is not a page, and the group's name prefix is a
 * contract that {@see App\Support\Marketing\MarketingUrls::pending()} reads and a count-pinned test guards.
 *
 * ⚠️ A POST, and the ONLY writer of the consent cookie. Accept and decline are the same route with a
 * different value, so declining can never be made to cost more than accepting. Throttled because it is an
 * unauthenticated write; generous, because a visitor changing their mind twice is not abuse.
 */
Route::post('/cookie-choice', [CookieChoiceController::class, 'store'])
    ->middleware('throttle:20,1')->name('cookies.choice');

Route::controller(MarketingController::class)->name('marketing.page.')->group(function () {
    // `/` is registered at the top of this file (it must read before everything) — see ruling D2 there.
    Route::get('/product', 'product')->name('product');
    Route::get('/features', 'features')->name('features');
    Route::get('/modules', 'modules')->name('modules');
    Route::get('/how-it-works', 'howItWorks')->name('how-it-works');
    Route::get('/pricing', 'pricing')->name('pricing');

    // ⚠️ Gate 4 · the try-booking SANDBOX (ruling D3 = (b)). It mirrors the real `seaside-demo` property,
    // prices from the engine's own read-only `priceOver()`, and renders the REAL BookingConfirmationMail
    // with every link defused (#33). It creates NO reservation, no quote row, and sends no mail.
    //
    // ⚠️ GATE 4b — IT NOW TAKES DATES (`?from=&to=&m=`), and that is the whole interactive picker: a click
    // is a GET, and the server re-prices through `priceOver()`. ⚠️ THERE IS NO PROPERTY PARAMETER, by
    // construction — `TryBookingSandbox::DEMO_SLUG` is a constant, so this URL cannot be pointed at another
    // organisation's rates. An endpoint that took a property id would be a pricing oracle over every
    // customer on the platform; this one is a fixed demo property with two dates.
    // ⚠️ THROTTLED ON THE PARAMETERISED PATH ONLY — a dated request prices ~60 nights, the plain page
    // prices none and must stay crawlable. See the `try-booking` limiter in AppServiceProvider.
    Route::get('/try-booking', 'tryBooking')->middleware('throttle:try-booking')->name('try-booking');

    // ── Gate 3 · the Sales pages that make no third-party claims ────────────────────────────────────
    // ⚠️ `alternatives` and `compare` are deliberately ABSENT. Both pages' subject IS a set of unsourced
    // claims about named competitors (Mews / Cloudbeds / Guesty / Hostaway), which sit entirely outside
    // the content-honesty gate's reach — nothing in CI can see them. Audited and surfaced instead of
    // shipped: docs/audits/FRONT-GATE3-HONESTY-AUDITS-2026-08-21.md.
    // ⚠️ The three case studies were absent for the same class of reason — their SUBJECT was D1's three
    // invented, unconsented customers — until Gate 3b REBUILT them as explicitly-labelled illustrative
    // scenarios and registered them below under `/example-scenarios`. (This paragraph still claimed they
    // were absent; corrected by the Gate 7 audit, which read the routes rather than the prose.)
    Route::get('/switching', 'switching')->name('switching');
    Route::get('/for-hotels', 'forHotels')->name('for-hotels');
    Route::get('/for-apartments', 'forApartments')->name('for-apartments');
    Route::get('/for-villas', 'forVillas')->name('for-villas');

    // ── Gate 3b · the illustrative scenarios (ruling A) ─────────────────────────────────────────────
    // ⚠️ NOT `/case-studies`. These were drawn as customer stories about three invented, unconsented
    // businesses; they are rebuilt as explicitly labelled ILLUSTRATIVE SCENARIOS, and a URL that says
    // "case study" would re-assert the very claim the rewrite removed. The slug diverges from the design's
    // sitemap deliberately — the page's identity changed. Changed now, while nothing is published and it
    // is free; after launch it costs SEO and leaves dead URLs.
    // ⚠️ Guarded by MarketingScenarioTest: the label, no identities, no quotes, no achieved outcomes.
    Route::get('/example-scenarios', 'exampleScenarios')->name('case-studies');
    Route::get('/example-scenarios/villa-portfolio', 'scenarioVillas')->name('story-cyclades');
    Route::get('/example-scenarios/city-hotel', 'scenarioHotel')->name('story-lisbon');
    Route::get('/example-scenarios/serviced-apartments', 'scenarioApartments')->name('story-rotterdam');

    // ── Gate 7 · the COMPANY pages ─────────────────────────────────────────────────────────────────
    // ⚠️ FOUR OF TEN. The doc-truth audit rated 147 claims across the ten and found 75 FALSE
    // (docs/audits/FRONT-GATE7-DOC-TRUTH-AUDIT-2026-08-22.md). These four survive because their false
    // claims could be CORRECTED against the code; the other six could not, and each absence is a ruling:
    //
    //  • `careers`  — its SUBJECT is a company that does not exist. Four "open roles" at a business with one
    //    person, a studio in Rotterdam, published salary bands, a four-day fortnight that "survived two
    //    release crunches" (there have been zero releases), twice-yearly on-sites "at a property that runs on
    //    Vyrobel" (there are no customers), and "the best hires we've made". Removing the false claims
    //    removes the page. ⚠️ NOTHING IS DISCARDED BY ITS ABSENCE: the page has no form and no file input —
    //    all five of its CTAs point at /contact, which now works.
    //  • `changelog` — a changelog asserts a RELEASE HISTORY. There are zero git tags across 863 commits and
    //    the product has never been deployed. ⚠️ THREE of its eight dated entries PREDATE the repository's
    //    first commit (2026-07-02) and TWO are dated in the FUTURE — the newest, presented as most recently
    //    shipped, is 45 days ahead of today. No labelling cures a date that cannot have happened.
    //  • `demo-confirmed` — the post-submit state of the demo booker, refused with D5.
    //
    // ⚠️⚠️ `privacy` / `terms` WERE REFUSED HERE, AND THE REFUSAL IS KEPT ON THE RECORD RATHER THAN DELETED.
    //    It read: "LEGAL DOCUMENTS. Their structure and a cited data inventory are prepared for counsel in
    //    docs/legal/; the text is not this pass's to draft, and a plausible-looking privacy policy is worse
    //    than none because the founder and their customers' auditors would rely on it."
    // ⚠️ OVERRIDDEN BY THE FOUNDER on 2026-09-23, twice and knowingly, because Google will not publish an
    //    External OAuth consent screen without a privacy-policy URL — so the real choice was not "policy or
    //    no policy", it was "policy or no Google sign-in at all".
    // ⚠️⚠️ THE ORIGINAL OBJECTION IS ANSWERED BY CONSTRUCTION, NOT IGNORED. The pages state only what
    //    docs/legal/DATA-INVENTORY.md verified against the code; they PRINT every limitation rather than
    //    smoothing it (no residency promise, no "encrypted at rest", no sub-processor list that does not
    //    exist, no retention period the software does not enforce); the cookie table renders from the LIVE
    //    CookieConsent catalogue so it cannot drift; and both carry a pending-legal-review banner.
    //    ⚠️ They are a floor, not a finished policy. Counsel replaces the text; it does not merely bless it.
    Route::get('/privacy', 'privacy')->name('privacy');
    Route::get('/terms', 'terms')->name('terms');
    Route::get('/integrations', 'integrations')->name('integrations');
    Route::get('/security', 'security')->name('security');
    Route::get('/about', 'about')->name('about');
    Route::get('/contact', 'contact')->name('contact');
});

/*
 * ══ THE CONTACT FORM'S WRITE (front-website Gate 7) ════════════════════════════════════════════════
 *
 * ⚠️ DELIBERATELY OUTSIDE the `marketing.page.*` group, for the same reason the cookie POST is: that name
 * prefix is a contract {@see App\Support\Marketing\MarketingUrls::pending()} reads to decide whether a page
 * exists, and a count-pinned test guards it. This is a write, not a page.
 *
 * ⚠️ THE ONLY PUBLIC ENDPOINT THAT PERSISTS A STRANGER'S PROSE AND CAN CAUSE MAIL TO BE SENT, so it carries
 * its own limiter rather than borrowing one. No `can:` — there is no actor to authorize; a throttle, a
 * honeypot and hard length caps stand in its place.
 */
Route::post('/contact', [MarketingContactController::class, 'store'])
    ->middleware('throttle:marketing-contact')->name('marketing.contact.store');

/*
 * THE OPERATOR APP (/app) — `auth` + the BACK-OFFICE GATE.
 *
 * `auth` gives us a user and lets SetTenantForWebRequest resolve the active organization (tenant
 * isolation intact). {@see EnsureBackOfficeAccess} then enforces the operator/owner boundary: only a
 * user holding a `config('authorization.back_office_roles')` role IN THE ACTIVE ORG may enter. This
 * closes the critical hole where `auth` alone let an owner — who necessarily holds an active Membership,
 * because that is the only way their org resolves — walk into the whole back office, and (since
 * OwnerScope is inactive off-portal) read EVERY owner's financials at /app/owners/{owner}.
 * See docs/DECISIONS.md 2026-07-16. The gate is on the GROUP, so it covers every route below —
 * including any added later.
 */
/*
|--------------------------------------------------------------------------
| EMAIL VERIFICATION (operator realm) — GATE 01, 2026-09-23
|--------------------------------------------------------------------------
|
| ⚠️⚠️ FOUNDER RULING: VERIFY BEFORE `/app`. Signup stays fully open and public; an unverified account
| simply does nothing until the link is clicked.
|
| ⚠️⚠️ THESE THREE ROUTES SIT **OUTSIDE** THE GATE THEY LIFT, AND THAT IS NOT AN OVERSIGHT. Gating the page
| that lifts the gate is the classic self-inflicted wound — `verified` redirects to `verification.notice`,
| so if that route were itself gated the user would be redirected to it forever. They carry `auth` only.
|
| ⚠️ `verification.verify` additionally carries `signed` (the HMAC link) and a throttle; the `{id}`/`{hash}`
| are validated against the AUTHENTICATED user by the framework's own `EmailVerificationRequest`, so one
| user's link can never verify another's account.
*/
Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')->name('verification.send');
});

// ⚠️ ADMIN 01 · PHASE 2 — RefuseSuspendedOrganization: a suspended or archived organisation's staff are refused here,
//    with a plain explanation. It sits before every other gate so no /app route — billing included — is reachable.
// ⚠️ ADMIN 01 · PHASE 3 — EnterBreakGlassSession FIRST: for a browser holding an open break-glass session it borrows the
//    chosen back-office user's view for this request only, so `auth` and `verified` are satisfied, never skipped.
Route::middleware([EnterBreakGlassSession::class, 'auth', 'verified', RefuseSuspendedOrganization::class, EnsureBackOfficeAccess::class, EnforceIpAllowlist::class, SetCurrentPropertyForWebRequest::class, EnsureSubscriptionActive::class])->prefix('app')->name('app.')->group(function () {
    // Design pass · Operator Screen 5 — operator dashboard (new tenant-scoped overview page).
    Route::get('/dashboard', OperatorDashboard::class)->name('dashboard');
    // D1-1 — tick/un-tick one of the dashboard's DERIVED task rows. ⚠️ A PERSONAL note, not a completion:
    // it changes no count and moves no state (see OperatorTaskStateController). A controller route (#20) so
    // the property context + tenant scope are resolved the same way the dashboard resolved them; self-only
    // by construction, so it needs no `can:` beyond back-office access — a user can only tick their own list.
    Route::post('/dashboard/tasks/toggle', [OperatorTaskStateController::class, 'toggle'])->name('dashboard.tasks.toggle');
    // D1-2 — how THIS person groups their task list (priority | category). A preference, not a filter: it
    // cannot remove a row or change a count. Self-only, same controller, same discipline.
    Route::post('/dashboard/tasks/grouping', [OperatorTaskStateController::class, 'grouping'])->name('dashboard.tasks.grouping');

    // Phase 6a · multi-property — the topbar property switcher. Sets the per-user working property
    // (or "all") in the session, then PRG back. Any back-office user may switch among the properties
    // they can access; the controller re-validates the id against that set (defence in depth — the
    // middleware validates again on the next request), so a foreign/stale id can never take effect.
    Route::post('/property/switch', [PropertySwitchController::class, 'update'])->name('property.switch');

    // Auth/isolation pass · item 2 — the WORKSPACE (organization) switcher. `active_organization_id` was
    // READ by SetTenantForWebRequest and NEVER WRITTEN anywhere, so a user in two orgs was permanently
    // pinned to whichever membership came back first. Same shape as the property switcher above: the
    // controller validates the target against the user's OWN active memberships (a crafted org id is
    // rejected, never stored), and the middleware re-validates on the next request (#38).
    Route::post('/workspace/switch', [WorkspaceSwitchController::class, 'update'])->name('workspace.switch');

    // Phase 6a · Step 2 — the PORTFOLIO overview + property CRUD. The overview is gated `properties.view`
    // (every back-office role sees it); creating/editing a property is gated `properties.manage` (Property
    // Owner / Group Admin — NOT Front Desk / Hotel Manager, #30). A created property is org-scoped by
    // BelongsToOrganization; an edit resolves its target by a tenant-scoped id (cross-org 404s).
    Route::get('/portfolio', OperatorPortfolio::class)->middleware('can:properties.view')->name('portfolio');
    Route::post('/portfolio', [PropertyController::class, 'store'])->middleware('can:properties.manage')->name('portfolio.store');
    Route::post('/portfolio/update', [PropertyController::class, 'update'])->middleware('can:properties.manage')->name('portfolio.update');
    // ⚠️ 2026-09-29 — remove a property from its Portfolio card (archive-not-destroy), and keep one whose removal is scheduled
    Route::post('/portfolio/{property}/remove', [PropertyController::class, 'remove'])->middleware('can:properties.manage')->whereUlid('property')->name('portfolio.remove');
    Route::post('/portfolio/{property}/keep', [PropertyController::class, 'keep'])->middleware('can:properties.manage')->whereUlid('property')->name('portfolio.keep');

    // Phase 6b · Analytics — operator Insights + Reports over the SG1 metric layer, scoped by the Phase-6a
    // property context (per-property or the all-properties roll-up). Read-only, gated `can:reports.view` (a
    // management/analytics baseline — NOT Front Desk / Housekeeping / Reservation Agent, #30). Every figure
    // is a REAL aggregate reconciling to FolioTotals; no fabricated history/narrative.
    Route::get('/insights', OperatorInsights::class)->middleware('can:reports.view')->name('insights');
    Route::get('/reports', OperatorReports::class)->middleware('can:reports.view')->name('reports');
    // #104 GATE 2 — the real Reports CSV export (was a dead "Soon" pill); same `reports.view` gate as the screen,
    // scope via PropertyContext, figures through the shared OperatorRevenueBreakdown (can't drift from the page).
    Route::get('/reports/export.csv', [OperatorReportsExportController::class, 'csv'])->middleware('can:reports.view')->name('reports.export');
    // GATE 10.2 — the Reports PDF export (the wireframe's "PDF report"). Same reports.view gate + PropertyContext
    // scope; rendered from the SAME OperatorReportData the screen builds, so the printed report can't drift.
    Route::get('/reports/export.pdf', [OperatorReportsExportController::class, 'pdf'])->middleware('can:reports.view')->name('reports.export.pdf');

    // D4-1 — the Excel workbook: the SAME scope + period + breakdown as the CSV and the screen, typed
    // (money numeric, months as date cells). Same `reports.view` gate as the page it exports.
    Route::get('/reports/export.xlsx', [OperatorReportsExportController::class, 'xlsx'])->middleware('can:reports.view')->name('reports.export.xlsx');

    // Phase 7b · Subscription billing — the Paywall + Checkout, gated `can:billing.manage` (the founder /
    // Property Owner, NOT Front Desk). Read-only until the account-gated Stripe charge: the tiers + the
    // honest derived trial status render; the subscribe action is the deferred Stripe-Billing seam (no
    // charge, no "active" off a fake payment, no raw card). Company/VAT captured on the org for invoicing.
    Route::get('/billing', OperatorBilling::class)->middleware('can:billing.manage')->name('billing');
    Route::get('/billing/checkout', OperatorBillingCheckout::class)->middleware('can:billing.manage')->name('billing.checkout');
    Route::post('/billing/company', [BillingActionController::class, 'saveCompany'])->middleware('can:billing.manage')->name('billing.company');
    Route::post('/billing/subscribe', [BillingActionController::class, 'subscribe'])->middleware('can:billing.manage')->name('billing.subscribe');
    // Operator Billing exact-match (#85) — the REAL pause/cancel/resume state machine. Genuinely gates the
    // workspace read-only (the shared gate the Dashboard/Calendar consume); no charge; audited.
    Route::post('/billing/pause', [BillingActionController::class, 'pause'])->middleware('can:billing.manage')->name('billing.pause');
    Route::post('/billing/cancel', [BillingActionController::class, 'cancel'])->middleware('can:billing.manage')->name('billing.cancel');
    Route::post('/billing/resume', [BillingActionController::class, 'resume'])->middleware('can:billing.manage')->name('billing.resume');

    // ⚠️ CONNECT PHASE 1 — start Stripe Connect onboarding for THIS organisation (a Standard connected
    // account). ⚠️⚠️ PURELY ADDITIVE: nothing charges on the resulting account, the guest checkout is
    // untouched, and an org that never starts this behaves exactly as it does today. Gated
    // `billing.manage` (Super Admin + Property Owner only — the same gate as the rest of billing, and the
    // tightest one the matrix offers for a money-account act); audited in OperatorConnectService.
    Route::post('/billing/connect/start', [OperatorConnectController::class, 'start'])
        ->middleware('can:billing.manage')->name('billing.connect.start');
    // Dev/test-only affordance to demo the access gate (404s outside local/testing) — never a real payment.
    Route::post('/billing/dev-expire', [BillingActionController::class, 'devExpireTrial'])->middleware('can:billing.manage')->name('billing.dev-expire');

    // GLOBAL SEARCH (Phase 1 · Sub-gate 2) — cross-entity search (reservations/guests/units/owners), the query
    // on a GET (scope-safe reads). Gated on `reservations.view` (the operations read baseline); per-entity
    // results follow the viewer's abilities inside the component. Tenant-scoped; NOT availability search.
    Route::get('/search', OperatorSearch::class)->middleware('can:reservations.view')->name('search');
    // Auth Phase B · Step 4 — operator Owners screen + the invite-owner write (POST → controller → PRG).
    // ⚠️ OP-FIX-2 (audit Finding 1) — owner FINANCIALS + an owner's email address. Gated on the finance
    // read permission, which keeps Property Owner / Group Admin / Hotel Manager / Accountant (and the
    // analytics roles that hold it) and excludes every operational role. NOT `users.manage`: that is the
    // owners surface's WRITE gate, and the detail page's rates.manage proposals panel is built for Hotel
    // Manager, who does not hold users.manage — gating the page on it would strand a control by design.
    Route::get('/owners', OperatorOwners::class)->middleware('can:reports.view')->name('owners');
    Route::post('/owners/invite', [InviteOwnerController::class, 'store'])
        ->middleware(['throttle:10,1', 'can:users.manage'])->name('owners.invite');
    // "Preview the invite email" (the invite modal) — renders the REAL OwnerInviteMail template (transient
    // invite, placeholder token; never sends/writes). Operator-admin gated, exactly like the invite write.
    Route::get('/owners/invite/preview', [InviteOwnerPreviewController::class, 'show'])
        ->middleware('can:users.manage')->name('owners.invite.preview');
    // 2026-10-01 — a waiting invite can be sent again (a fresh link; your password, as sending was) or cancelled, and the
    // owners in view can be taken away as a CSV. ⚠️ Registered BEFORE `/owners/{owner}`, which would otherwise read
    // "export" as an owner's id.
    Route::post('/owners/invites/{invite}/resend', [OwnerInviteActionController::class, 'resend'])
        ->middleware(['throttle:10,1', 'can:users.manage'])->name('owners.invites.resend');
    Route::post('/owners/invites/{invite}/cancel', [OwnerInviteActionController::class, 'cancel'])
        ->middleware('can:users.manage')->name('owners.invites.cancel');
    Route::get('/owners/export', [OwnerExportController::class, 'download'])->middleware('can:reports.view')->name('owners.export');
    // Wave 1 · Sub-gate 3 — the operator's TENANT-scoped lens on ONE owner (units/agreement/
    // statements/payouts/balance). NOT owner-scoped: OwnerScope is inactive off-portal, so this
    // deliberately reads the whole owner relationship the operator manages, within their own org
    // (Owner is BelongsToOrganization → a cross-org id 404s).
    Route::get('/owners/{owner}', OperatorOwnerDetail::class)->middleware('can:reports.view')->name('owners.show');
    // Owner management writes (#93) — edit / pause-payouts / add-note / remove (archive). Gated users.manage,
    // read-only-respecting, audited; remove is a careful archive-not-destroy (financials/bookings retained).
    Route::post('/owners/{owner}/update', [OwnerManageController::class, 'update'])->middleware('can:users.manage')->name('owners.update');
    // ⚠️ ②+accrual — the FIRST real caller finalisation has ever had (it was seeder-and-test-only, and the
    // payout engine pays ONLY finalized statements, so a real deployment had nothing to pay). Gated on
    // `payouts.manage` like the payout run: closing a period is the act that makes money payable.
    // ⚠️ Safe only because the collection gate landed with it — see OwnerCollectionGateTest.
    Route::post('/owners/{owner}/close-period', [OwnerManageController::class, 'closePeriod'])
        ->middleware('can:payouts.manage')->name('owners.close-period');
    Route::post('/owners/{owner}/pause-payouts', [OwnerManageController::class, 'pausePayouts'])->middleware('can:users.manage')->name('owners.pause-payouts');
    Route::post('/owners/{owner}/note', [OwnerManageController::class, 'addNote'])->middleware('can:users.manage')->name('owners.note');
    Route::post('/owners/{owner}/remove', [OwnerManageController::class, 'remove'])->middleware('can:users.manage')->name('owners.remove');
    // Phase 2 · Sub-gate 6 — the operator sees owners' add-unit REQUESTS here (tenant-scoped; owner scope is
    // inactive off-portal). Read-only: creating the real unit stays the operator's own action. Gated units.view.
    Route::get('/unit-requests', OperatorUnitRequests::class)->middleware('can:units.view')->name('unit-requests');
    // #104 GATE 1 — the review is now OPERATIONAL: approve (→ create the real unit) / decline (→ notify the owner)
    // via POST → UnitRequestActionController → OwnerUnitRequestService → PRG. Gated `can:units.manage` (the write
    // permission Group Admin/Hotel Manager hold; the read screen stays units.view). Controller carries no
    // authorize(), so these gates are the actor gate (#20/#30).
    Route::post('/unit-requests/{unitRequest}/approve', [UnitRequestActionController::class, 'approve'])->middleware('can:units.manage')->name('unit-requests.approve');
    Route::post('/unit-requests/{unitRequest}/decline', [UnitRequestActionController::class, 'decline'])->middleware('can:units.manage')->name('unit-requests.decline');

    // ROLES & ACCESS (Phase 1 · Sub-gate 3) — VIEW the fixed catalog (roles + read-only permissions + members)
    // and ASSIGN a role to a member. Gated on `users.manage` (only an admin manages roles — the same gate that
    // closed the owner-invite escalation, #30). The role→permission catalog is CODE-DEFINED + read-only; only
    // the user↔role assignment is written, and RoleAssignmentService bounds it by the privilege-subset rule so
    // no one can grant a role with more access than their own.
    Route::get('/roles', OperatorRoles::class)->middleware('can:users.manage')->name('roles');
    Route::post('/roles/assign', [RoleAssignmentController::class, 'assign'])->middleware('can:users.manage')->name('roles.assign');
    Route::post('/roles/revoke', [RoleAssignmentController::class, 'revoke'])->middleware('can:users.manage')->name('roles.revoke');
    // #102 — edit a role's PERMISSION MATRIX (the toggles genuinely grant/revoke real capabilities, spatie DB).
    // Gated `can:roles.manage` (org-admin: Property Owner + Super Admin), NOT the broader users.manage — a
    // sensitive surface; RolePermissionService enforces editable-role/subset/last-admin + audits.
    Route::post('/roles/permissions', [RoleAssignmentController::class, 'savePermissions'])->middleware('can:roles.manage')->name('roles.permissions');
    // Wave 1 · Sub-gate 4 — the operator PAYOUT RUN (tenant-scoped): finalized statements ready to pay
    // → REAL payouts through the certified OwnerPayoutService (ManualPayoutRail is the working rail;
    // the Stripe-Connect rail stays a config-gated skeleton that THROWS — never faked). The writes use
    // the operator-mutation pattern: POST → controller → service → PRG (CSRF + tenant re-scoped).
    // ⚠️ OP-FIX-2 (audit Finding 1) — payout figures. Matches its own POST sibling exactly (§N.8f).
    Route::get('/payouts/run', PayoutRun::class)->middleware('can:payouts.manage')->name('payouts.run');
    Route::post('/payouts/create', [PayoutRunActionController::class, 'create'])
        ->middleware('can:payouts.manage')->name('payouts.create');
    Route::post('/payouts/pay', [PayoutRunActionController::class, 'pay'])
        ->middleware('can:payouts.manage')->name('payouts.pay');
    // #93 — run the CERTIFIED payout engine (test-mode): a real persisted PayoutRun that reconciles + locks.
    Route::post('/payouts/run', [PayoutRunActionController::class, 'runNow'])
        ->middleware('can:payouts.manage')->name('payouts.run-now');
    Route::get('/rack', ReservationRack::class)->name('rack');
    // Operator Calendar exact-match — the canonical "Calendar" URL (the nav points here); same certified
    // rack component. `/rack` is kept as an alias for existing deep-links (front desk / maintenance / groups).
    Route::get('/calendar', ReservationRack::class)->name('calendar');
    Route::get('/units', UnitsList::class)->name('units');
    // ── P-6 · Gate 1 — INVENTORY CREATION. Before this, RoomType and RatePlan had NO creation path in
    //    app/ at all and Unit had one only behind an OWNER's request, so a /signup tenant reached a
    //    property they could never make sellable (BookingEngineService::search() iterates RoomType rows).
    //    ⚠️ Same shape as the Property writes above: POST → controller → PRG, `can:` on the ROUTE (#30).
    //    ⚠️ TenantCanBecomeSellableTest pins these route names — it is the guard against the whole class
    //    silently returning, so a rename must move the test with it, not around it.
    // ⚠️ CLUSTER E — a unit's ARRIVAL INFO (door code · wifi · notes): the first writer these columns have
    // ever had outside the demo seeder. `units.manage`, and the REVEAL is a separate, audited action
    // because the editor never renders a stored credential back into the page.
    Route::post('/units/{unit}/arrival-info', [InventoryActionController::class, 'updateArrivalInfo'])->middleware('can:units.manage')->name('inventory.units.arrival-info');
    Route::post('/units/{unit}/arrival-info/reveal', [InventoryActionController::class, 'revealArrivalInfo'])->middleware('can:units.manage')->name('inventory.units.arrival-info.reveal');
    Route::post('/units/room-types', [InventoryActionController::class, 'storeRoomType'])
        ->middleware('can:units.manage')->name('inventory.room-types.store');
    Route::post('/units', [InventoryActionController::class, 'storeUnit'])
        ->middleware('can:units.manage')->name('inventory.units.store');
    // ⚠️ F-6 — a unit could be created and never changed or taken out of service. Both carry the SAME gate
    // as creating one (`units.manage`, held by Super Admin / Property Owner / Group Admin / Hotel Manager),
    // because changing inventory and adding it are the same authority.
    // ⚠️⚠️ DELETE (2026-10-05, the founder: "there must be an option to edit and delete the unit") — a unit, and a room type,
    // but ONLY one nothing has recorded: a hard delete would CASCADE `unit_status_logs`, cleaning and repair history and SET
    // NULL on `owner_ledger` and `reservation_units` (a room type's delete CASCADEs sold stays themselves), so the
    // controller refuses any of those, in words, and offers Pause for a unit with history. The rule is ONE class,
    // App\Support\Unit\InventoryDeletion, asked again inside the delete's transaction with the row locked.
    Route::put('/units/{unit}', [InventoryActionController::class, 'updateUnit'])
        ->middleware('can:units.manage')->name('inventory.units.update');
    Route::delete('/units/{unit}', [InventoryActionController::class, 'destroyUnit'])
        ->middleware('can:units.manage')->name('inventory.units.destroy');
    Route::put('/units/room-types/{roomType}', [InventoryActionController::class, 'updateRoomType'])
        ->middleware('can:units.manage')->name('inventory.room-types.update');
    Route::delete('/units/room-types/{roomType}', [InventoryActionController::class, 'destroyRoomType'])
        ->middleware('can:units.manage')->name('inventory.room-types.destroy');
    Route::post('/units/{unit}/active', [InventoryActionController::class, 'setUnitActive'])
        ->middleware('can:units.manage')->name('inventory.units.active');
    // 2026-09-30 — the Units page rebuilt: pause or resume several units at once (the same audited service, one unit at a
    // time, the same gate as the single action), and the export that replaced "Export · Soon" (a READ, `units.view`,
    // offered only to a role holding it). ⚠️ Paths outside `/units/{unit}/…`, so no id is ever captured as a word.
    Route::post('/units-bulk/active', [InventoryActionController::class, 'bulkSetActive'])
        ->middleware('can:units.manage')->name('inventory.units.bulk-active');
    Route::get('/units-export', [UnitExportController::class, 'view'])->middleware('can:units.view')->name('units.export');
    Route::post('/units-export', [UnitExportController::class, 'selected'])->middleware('can:units.view')->name('units.export-selected');
    // Operator Channels exact-match — the OTA channel-manager surface. Pro-gated (`plan.feature:channels` →
    // an Essential org gets the honest "not in your plan" page). HONEST-GATED: real channel STATE, but live
    // OTA two-way sync/import is account-gated (no real Channex integration) — never a fabricated sync.
    Route::get('/channels', OperatorChannels::class)->middleware(EnsurePlanFeature::class.':channels')->name('channels');

    // Rates & availability (Wave 4) — the READ grid + the guarded rate/restriction write, which is the
    // FIRST production caller of RateAndRestrictionService (setRate/setRestriction). PER-ROOM-TYPE (the
    // design's per-unit grid can't be backed by the per-rate-plan tables — docs/WAVE4-INVENTORY STRUCT-1),
    // tenant-scoped, integer money parsed server-side. GET gated on `rates.view` (Housekeeping/Maintenance/
    // Marketing/Guest Support hold none → the nav shows them "Soon", never a 403); POST gated on
    // `rates.manage` — deliberately NOT Front Desk or Reservation Agent (invariant #30, separation of duties;
    // RatesGrid gates the editor off the SAME permission so the UI never offers a write the route refuses).
    Route::get('/rates', RatesGrid::class)->middleware('can:rates.view')->name('rates');
    Route::post('/rates', [RateActionController::class, 'apply'])->middleware('can:rates.manage')->name('rates.update');
    // P-6 · Gate 1 — a rate plan is what rate_calendar rows hang off; without one a room type has nowhere
    // to store a price, which RateActionController could report but not remedy.
    Route::post('/rates/plans', [InventoryActionController::class, 'storeRatePlan'])
        ->middleware('can:rates.manage')->name('inventory.rate-plans.store');

    // ⚠️ F-6's SIBLING — a rate plan could be created and never edited or taken off sale. Same gate as
    // creating one (`rates.manage`). ⚠️ THERE IS DELIBERATELY NO DELETE ROUTE, and the reason is measured:
    // `rate_calendar` CASCADES on a plan delete — 929 nightly prices destroyed — and NOTHING RECORDS WHAT A
    // RATE ROW REPLACED (no prior-value column, no snapshot table, `before` null at all eleven rate-path
    // audit sites), so it could never be undone. It would also SET NULL on 42 `reservation_units`, strip 3
    // room types of their default plan, and leave derived plans holding an adjustment with no basis —
    // a percentage of nothing. See docs/RATE-PLAN-LIFECYCLE.md.
    Route::put('/rates/plans/{ratePlan}', [InventoryActionController::class, 'updateRatePlan'])
        ->middleware('can:rates.manage')->name('inventory.rate-plans.update');
    Route::post('/rates/plans/{ratePlan}/active', [InventoryActionController::class, 'setRatePlanActive'])
        ->middleware('can:rates.manage')->name('inventory.rate-plans.active');    // #100 — bulk edit (increase%/decrease%/set-exact across units/nights, skip booked, update derived plans).
    Route::post('/rates/bulk', [RateActionController::class, 'bulkApply'])->middleware('can:rates.manage')->name('rates.bulk');

    // D18-5 — BULK PER-UNIT overrides. ⚠️ TWO POSTS ON PURPOSE: `preview` writes NOTHING and returns the exact
    // per-unit change set + a digest; `apply` refuses without that digest and re-verifies it inside the
    // transaction, so what commits is what the operator approved. Both `rates.manage` (#30), both write only
    // through the ONE UnitRateOverrideService (D4-1 — never a direct rate write).
    Route::post('/rates/units/preview', [UnitRateActionController::class, 'preview'])->middleware('can:rates.manage')->name('rates.units.preview');
    Route::post('/rates/units/apply', [UnitRateActionController::class, 'apply'])->middleware('can:rates.manage')->name('rates.units.apply');

    // SEASONAL ("peak") RATES — OWNER FIX 4b. ⚠️ TWO POSTS: `store` records the season and writes NO price;
    // `apply` MATERIALIZES it into `rate_calendar` through the certified RateAndRestrictionService (never a
    // second write path — D4-1). Both `rates.manage` (#30): applying overwrites real nightly prices for a
    // whole range, which is a revenue action, not a front-desk one.
    Route::post('/seasons', [SeasonActionController::class, 'store'])->middleware('can:rates.manage')->name('seasons.store');
    Route::post('/seasons/{season}/apply', [SeasonActionController::class, 'apply'])->middleware('can:rates.manage')->name('seasons.apply');
    // ⚠️ EDIT AND DELETE — same gate as create and apply (`rates.manage`), because both change what a guest
    //    would be quoted. ⚠️ Editing is refused once a season is APPLIED: applying overwrote the nightly
    //    rates and nothing recorded what they were, so an "edit" could not put them back. See
    //    docs/SEASON-EDIT-DELETE.md — an applied season is superseded, never edited.
    Route::put('/seasons/{season}', [SeasonActionController::class, 'update'])->middleware('can:rates.manage')->name('seasons.update');
    Route::delete('/seasons/{season}', [SeasonActionController::class, 'destroy'])->middleware('can:rates.manage')->name('seasons.destroy');

    // RATE PROPOSALS (Wave C · GATE 1) — the operator proposes a rate change to an owner (who approves/declines in
    // their portal) + may withdraw a pending one. `rates.manage` (#30, NOT Front Desk); integer money server-side.
    Route::post('/rate-proposals', [OperatorRateProposalController::class, 'store'])->middleware(['throttle:20,1', 'can:rates.manage'])->name('rate-proposals.store');
    Route::post('/rate-proposals/{proposal}/withdraw', [OperatorRateProposalController::class, 'withdraw'])->middleware('can:rates.manage')->name('rate-proposals.withdraw');
    // Owner Rates exact-match (#81) — the operator ACCEPTS the owner's counter → the owner's rate is applied.
    Route::post('/rate-proposals/{proposal}/accept-counter', [OperatorRateProposalController::class, 'acceptCounter'])->middleware('can:rates.manage')->name('rate-proposals.accept-counter');

    // Settings (Wave 4) — ONE coherent settings surface (Operator + Property settings de-conflicted). Only
    // the BACKED columns are written (properties / property_policies / organizations); unbacked design fields
    // are stubbed "Soon", never a fake save. The page + property/policy writers are gated `properties.manage`
    // (Property Owner / Group Admin); the ORG writer is additionally gated `settings.manage` (Property Owner)
    // — per-section separation of duties, NOT Front Desk. Both permissions already exist in the catalog.
    Route::get('/settings', OperatorSettings::class)->middleware('can:properties.manage')->name('settings');
    Route::post('/settings/property', [SettingsActionController::class, 'updateProperty'])->middleware('can:properties.manage')->name('settings.property');
    Route::post('/settings/policies', [SettingsActionController::class, 'updatePolicies'])->middleware('can:properties.manage')->name('settings.policies');
    Route::post('/settings/organization', [SettingsActionController::class, 'updateOrganization'])->middleware('can:settings.manage')->name('settings.organization');
    Route::post('/settings/invoice', [SettingsActionController::class, 'updateInvoiceDetails'])->middleware('can:settings.manage')->name('settings.invoice');
    // Batch C · GATE 4 — edit taxes & fees (bounded rate/amount) via the certified SettingsService.
    // ⚠️ P-7 · CLUSTER J — per-property staff scoping, the pivot's first real writer. `users.manage`, not
    // `properties.manage`: deciding which properties OTHER people may see is access administration (#30).
    Route::post('/settings/team-scope', [SettingsActionController::class, 'updateTeamScope'])->middleware('can:users.manage')->name('settings.team-scope');
    Route::post('/settings/tax-fees', [SettingsActionController::class, 'updateTaxesAndFees'])->middleware('can:properties.manage')->name('settings.tax-fees');
    // #98 — guest-facing brand (accent/logo/from-name/reply-to/powered-by → real emails + portal) + the REAL
    // property-level booking pause (fans out to units via the certified AvailabilityService). properties.manage.
    Route::post('/settings/brand', [SettingsActionController::class, 'updateBrand'])->middleware('can:properties.manage')->name('settings.brand');
    // DO-G13-1 — the guest SUPPORT contact (phone / soft reply norm / real hours). Same gate as the rest of the
    // per-property settings; each field is nullable and an empty one withdraws the affordance from the guest page.
    Route::post('/settings/support', [SettingsActionController::class, 'updateSupport'])->middleware('can:properties.manage')->name('settings.support');
    Route::post('/settings/pause', [SettingsActionController::class, 'pauseBookings'])->middleware('can:properties.manage')->name('settings.pause');
    Route::post('/settings/resume', [SettingsActionController::class, 'resumeBookings'])->middleware('can:properties.manage')->name('settings.resume');
    // #98 GATE 2 — custom Add-a-fee (posts to folios via the shared StayEstimator rule) + the careful
    // archive-not-destroy property removal (scheduled, cancellable, records retained). properties.manage.
    Route::post('/settings/fees/add', [SettingsActionController::class, 'addFee'])->middleware('can:properties.manage')->name('settings.fees.add');
    Route::post('/settings/remove/schedule', [SettingsActionController::class, 'scheduleRemoval'])->middleware('can:properties.manage')->name('settings.remove.schedule');
    Route::post('/settings/remove/cancel', [SettingsActionController::class, 'cancelRemoval'])->middleware('can:properties.manage')->name('settings.remove.cancel');

    // Change password (Auth) — the operator Security page + its guarded write. Reachable by EVERY back-office
    // user (auth + #26, NO fine-grained gate — unlike /app/settings, which is manager-gated) so anyone can
    // change their OWN password. The write resolves the user from auth() (never a request id), VERIFIES the
    // current password, and reuses User::changePasswordTo (the shared reset hash + remember-token-rotate
    // path). Rate-limited like the rest of the auth chapter.
    // Phase 3 · Sub-gate 1 — the operator in-app notification feed (the AUTHENTICATED user's OWN notifications;
    // per-user Notifiable → isolation by construction). Behind #26 only (every back-office user has a feed).
    Route::get('/notifications', OperatorNotifications::class)->name('notifications');
    // Phase 3 · Sub-gate 2 — per-user notification preferences (self-only; the audience is the route default, not
    // the request). These GENUINELY gate delivery (via NotificationPreferences::allows); always-on locked.
    Route::get('/notification-preferences', OperatorNotificationPreferences::class)->name('notification-preferences');
    Route::post('/notification-preferences', [NotificationPreferenceController::class, 'update'])->defaults('audience', 'operator')->name('notification-preferences.update');

    Route::get('/security', OperatorSecurity::class)->name('security');
    Route::post('/security/password', [ChangePasswordController::class, 'operator'])->middleware('throttle:6,1')->name('security.password');

    // D24-2 — PASSKEYS FOR THE OPERATOR REALM. ⚠️ THE SAME `PasskeyController` the owner realm mounts
    // (`owner.security.passkeys.*`), not a copy: the WebAuthn subsystem is realm-agnostic — `PasskeyService`
    // has zero owner references, `Passkey belongsTo(User)`, and every action works on `$request->user()`, so
    // self-only scoping comes free and a second implementation would only drift (§N.8f).
    // ⚠️ LOGIN NEEDED NO WIRING AT ALL: the usernameless sign-in already runs on the shared `web` guard and
    // redirects through the shared `RedirectsByRole`, so an operator's passkey already lands on /app.
    Route::post('/security/passkeys/options', [PasskeyController::class, 'options'])->middleware('throttle:20,1')->name('security.passkeys.options');
    Route::post('/security/passkeys', [PasskeyController::class, 'store'])->middleware('throttle:20,1')->name('security.passkeys.store');
    Route::post('/security/passkeys/{passkey}/remove', [PasskeyController::class, 'destroy'])->middleware('throttle:10,1')->name('security.passkeys.remove');

    // #86 — Email Templates: the REAL customization MANAGER for the 5 guest emails (#83). The list + editor let an
    // operator edit subject/heading/message/button + toggle content blocks + pause an email; the saved content is
    // what the real Mailables render at send (mutation-proven). Reads behind #26; every WRITE carries
    // `can:properties.manage` (#30) → EmailTemplateActionController → EmailTemplateService (audited). Sub-gate 4 ·
    // Email Previews render each email with real sample data (no send).
    // ⚠️⚠️ FOUNDER RULING 2026-09-22 — the email templates are OWNER/ORG-ADMIN only, and the reason is not
    //    tidiness: outbound guest communication is a FRAUD SURFACE. Whoever can edit the confirmation email
    //    can change the bank details a guest is asked to pay into.
    // ⚠️ Gated with `properties.manage` — the SAME permission already on the save/toggle/test writes below,
    //    so the read and the write now agree. 🔬 Holders: Super Admin, Property Owner, Group Admin.
    // ⚠️⚠️ WHY A NEW PERMISSION RATHER THAN `properties.manage`: that one EXCLUDES `Hotel Manager`, and the
    //    ruling is "owner AND manager". 🔬 The product's own suite asserted a Hotel Manager could open this
    //    screen, so reusing `properties.manage` would have shipped a gate that contradicts both the ruling
    //    and the built behaviour. 🔬 Holders: Super Admin, Property Owner, Group Admin, Hotel Manager.
    Route::get('/email-templates', [EmailTemplateController::class, 'index'])->middleware('can:email_templates.manage')->name('email-templates');
    Route::get('/email-templates/{key}/edit', OperatorEmailEditor::class)->middleware('can:email_templates.manage')->name('email-templates.edit');
    Route::post('/email-templates/{key}', [EmailTemplateActionController::class, 'save'])->middleware('can:email_templates.manage')->name('email-templates.save');
    Route::post('/email-templates/{key}/toggle', [EmailTemplateActionController::class, 'toggle'])->middleware('can:email_templates.manage')->name('email-templates.toggle');
    Route::post('/email-templates/{key}/test', [EmailTemplateActionController::class, 'testSend'])->middleware('can:email_templates.manage')->name('email-templates.test');
    // ⚠️ OP-FIX-2b (audit Finding 1) — gated on `folios.view`, the SAME permission `folio.pdf` carries.
    // The previews render REAL booking money (run 36: €540.00 · €124.20 · €664.20), but that money is the
    // POINT of the surface, not a leak: PaymentReminderBrandTest exists to prove an operator previewing a
    // payment reminder sees correct arithmetic, and EmailScreensAccessTest treats the previews as a general
    // back-office operator screen. Both render as Hotel Manager, who HOLDS folios.view — confirmed before
    // this gate was added, because a gate that reds a legitimate test is the wrong gate (the owners /
    // users.manage catch, reprised).
    // ⚠️ NOT `properties.manage`: that is Property Owner + Group Admin only, so it would 403 Hotel Manager
    // and break the documented workflow. `folios.view` keeps every operational role that has folio business
    // (front-desk-and-up) and excludes Housekeeping + Maintenance, who have none. (Revenue Manager and
    // Marketing Manager are excluded too — they hold no folio permission and have no preview business.)
    Route::get('/email-previews', [EmailPreviewController::class, 'index'])->middleware('can:folios.view')->name('email-previews');
    Route::get('/email-previews/{key}', [EmailPreviewController::class, 'show'])->middleware('can:folios.view')->name('email-previews.show');
    // #109 — a REAL test send from the previews gallery, to the operator's OWN email only (MailSafetyGuard).
    Route::post('/email-previews/{key}/test', [EmailPreviewController::class, 'testSend'])->middleware('can:properties.manage')->name('email-previews.test');

    // Operator PROFILE (Phase 1 · Sub-gate 5) — the logged-in operator's OWN name + work email. Like /security,
    // behind auth + #26 with NO fine-grained gate (every back-office user has a profile). The write is SELF-ONLY:
    // the controller resolves $request->user() (never a request id), so there is no id to tamper with to reach
    // another account. Only the genuinely-backed fields persist; unbacked design fields are omitted, not faked.
    Route::get('/profile', OperatorProfile::class)->name('profile');
    Route::post('/profile', [OperatorProfileController::class, 'update'])->name('profile.update');
    // #96 — UI preferences (stored + applied) + REAL sign-out-of-all-other-devices (#66 session revocation).
    Route::post('/profile/preferences', [OperatorProfileController::class, 'preferences'])->name('profile.preferences');
    Route::post('/profile/sign-out-others', [OperatorProfileController::class, 'signOutOtherSessions'])->middleware('throttle:6,1')->name('profile.sign-out-others');

    // Two-factor ENROLLMENT (self-service; the login challenge is separate). Real TOTP (google2fa) — enable →
    // confirm-code (anti-lockout) → one-time recovery codes; regenerate; disable (re-auth). Self-only
    // (auth()-resolved). Confirm/disable rate-limited. Behind #26 like the rest of /app.
    Route::post('/security/2fa/enable', [TwoFactorController::class, 'enable'])->name('security.2fa.enable');
    Route::post('/security/2fa/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:6,1')->name('security.2fa.confirm');
    Route::post('/security/2fa/recovery-codes', [TwoFactorController::class, 'regenerate'])->name('security.2fa.regenerate');
    Route::post('/security/2fa/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:6,1')->name('security.2fa.disable');
    // #104b GATE 1 — Security: the REAL IP-allowlist (org-wide admin-panel access control; `settings.manage` +
    // audited), the trusted-device revoke (shared #66 service; self-only re-auth), and the self-scoped account
    // export (throttled). None read-only-gated — account security stays usable on a paused workspace.
    Route::post('/security/ip-rules', [OperatorSecurityController::class, 'addIpRule'])->middleware('can:settings.manage')->name('security.ip-rules.add');
    Route::delete('/security/ip-rules/{rule}', [OperatorSecurityController::class, 'removeIpRule'])->middleware('can:settings.manage')->name('security.ip-rules.remove');
    Route::post('/security/trusted-devices/revoke', [OperatorSecurityController::class, 'revokeTrustedDevice'])->middleware('throttle:6,1')->name('security.trusted.revoke');
    // 2026-10-05 — sign ONE other device out, from its row (self-only; found by a one-way key, never a printed session id)
    Route::post('/security/sessions/sign-out', [OperatorSecurityController::class, 'signOutSession'])->middleware('throttle:10,1')->name('security.sessions.sign-out');

    // ⚠️ CONNECTED SIGN-IN ACCOUNTS — self-only, and linking is reachable ONLY from an authenticated session:
    //    being signed in is the proof that a matching email address can never be. The unlink floor (a user
    //    must always keep one usable credential) is enforced in the controller, not in the screen.
    Route::post('/security/connections/google', [SocialAuthController::class, 'redirectToLink'])
        ->defaults('provider', 'google')
        ->middleware('throttle:10,1')->name('security.connections.link');
    Route::post('/security/connections/twitter', [SocialAuthController::class, 'redirectToLink'])
        ->defaults('provider', 'twitter')
        ->middleware('throttle:10,1')->name('security.connections.twitter');
    Route::post('/security/connections/{identity}/unlink', [SocialAuthController::class, 'unlink'])
        ->middleware('throttle:10,1')->name('security.connections.unlink');
    Route::get('/security/export', [OperatorSecurityController::class, 'exportData'])->middleware('throttle:6,1')->name('security.export');
    // "This was me" on the sign-in problems card (2026-10-24) — self-only; the SAME controller serves the owner page.
    Route::post('/security/alerts/seen', [SecurityAlertController::class, 'seen'])->middleware('throttle:10,1')->name('security.alerts.seen');
    // Unified Inbox — the 3-pane operator MESSAGES page (#90 exact-match, OperatorInbox). Tenant-scoped;
    // the active thread rides ?c={id} (scoped find → cross-org null). The legacy /inbox/{thread} deep-link
    // redirects into the 3-pane so existing links keep working.
    // ⚠️ GATED 2026-08-08 (audit H3 / F-2a-02). The inbox is GUEST CORRESPONDENCE and its writes send
    // OUTBOUND guest-facing messages, yet the page and all 8 writes were ungated — a Housekeeping account
    // could read every thread and send as the property.
    // Gate is `guests.view`, NOT `guests.manage`: a Guest Support Agent holds guests.view only, and replying
    // IS their job — gating writes on `guests.manage` would lock the role out of its core function while
    // adding nothing (both gates already exclude Housekeeping/Maintenance/Accountant/Revenue Manager).
    Route::get('/inbox', OperatorInbox::class)->middleware('can:guests.view')->name('inbox');
    // ⚠️⚠️ DEC-m (2026-09-30) — an owner's message attachment, for the inbox staff the owner sent it to: the inbox's own
    //    permission, then the organisation's threads ({@see MessageAttachments::find()}). The owner portal's door is
    //    `owner.messages.attachment`.
    Route::get('/inbox/attachments/{media}', MessageAttachmentController::class)->middleware('can:guests.view')->name('inbox.attachment');
    // ⚠️⚠️ TEAM CHAT (2026-10-06, `docs/TEAM-CHAT-DESIGN.md`) — conversations between the people who work here: EVERY
    //    back-office user (the group's own gate — Housekeeping, Maintenance, Accountant and Revenue Manager hold no
    //    `guests.view`, and they are the employees he named), so NO `can:` here; who may read a conversation is its
    //    MEMBERSHIP, decided in TeamChatService / TeamChatReader / TeamChatFiles (a 404 for anyone else). ⚠️ Registered
    //    BEFORE `/inbox/{thread}`, which would otherwise take "team" for a thread id. ⚠️ Named limiters, never an
    //    unnamed `throttle:N,1` — that one counter per user is shared with the password change and the sign-in actions.
    Route::get('/inbox/team', OperatorTeamChat::class)->name('inbox.team');
    Route::get('/inbox/team/poll', [TeamChatController::class, 'poll'])->middleware('throttle:team-chat-poll')->name('inbox.team.poll');
    Route::post('/inbox/team/conversations', [TeamChatController::class, 'start'])->middleware('throttle:team-chat-write')->name('inbox.team.start');
    Route::post('/inbox/team/{conversation}/messages', [TeamChatController::class, 'send'])->middleware('throttle:team-chat-write')->name('inbox.team.send');
    Route::post('/inbox/team/{conversation}/read', [TeamChatController::class, 'read'])->middleware('throttle:team-chat-poll')->name('inbox.team.read');
    Route::get('/inbox/team/files/{media}', TeamChatFileController::class)->name('inbox.team.file');
    // THE GUESTS SIDE, LIVE — the open Inbox's small stamp (changes when anything it shows changes). Its own permission.
    Route::get('/inbox/live', InboxLiveController::class)->middleware(['can:guests.view', 'throttle:inbox-live'])->name('inbox.live');
    // THE PULSE — every back-office page's live counts (the bell, the Messages badge, the chime). Counts only.
    Route::get('/pulse', PulseController::class)->middleware('throttle:pulse')->name('pulse');
    Route::get('/inbox/{thread}', fn (string $thread) => redirect()->route('app.inbox', ['c' => $thread]))->name('inbox.show');
    // NEW MESSAGE — start a thread with a real guest (current/upcoming stay), optionally seeded from a #83 template.
    Route::post('/inbox/start', [InboxActionController::class, 'start'])->middleware('can:guests.view')->name('inbox.start');
    // Inbox writes — REAL reply + lifecycle via POST → controller → InboxService → redirect (PRG).
    // Cross-org {thread} 404s; a deferred channel leaves an honest failed record + clean flash (never 500).
    Route::post('/inbox/{thread}/reply', [InboxActionController::class, 'reply'])->middleware('can:guests.view')->name('inbox.reply');
    // (2026-10-06, the independent review) the open Inbox refreshes itself WITHOUT reading anything — reading is this POST,
    // sent when the person is looking at the conversation; the live stamp's own limiter (never the sign-in's counter)
    Route::post('/inbox/{thread}/read', [InboxActionController::class, 'read'])->middleware(['can:guests.view', 'throttle:inbox-live'])->name('inbox.read');
    Route::post('/inbox/{thread}/assign', [InboxActionController::class, 'assign'])->middleware('can:guests.view')->name('inbox.assign');
    Route::post('/inbox/{thread}/close', [InboxActionController::class, 'close'])->middleware('can:guests.view')->name('inbox.close');
    Route::post('/inbox/{thread}/reopen', [InboxActionController::class, 'reopen'])->middleware('can:guests.view')->name('inbox.reopen');
    Route::post('/inbox/{thread}/snooze', [InboxActionController::class, 'snooze'])->middleware('can:guests.view')->name('inbox.snooze');
    Route::post('/inbox/{thread}/link-reservation', [InboxActionController::class, 'linkReservation'])->middleware('can:guests.view')->name('inbox.link-reservation');
    // Retry a FAILED outbound — a REAL re-send over the channel (delivers on manual; a deferred channel fails
    // again, honestly — never the prototype's fake flip-to-delivered).
    Route::post('/inbox/{thread}/messages/{message}/retry', [InboxActionController::class, 'retryMessage'])->middleware('can:guests.view')->name('inbox.retry');
    // Maintenance module — styled work-order board + detail (design pass · Maintenance UI). Tenant-scoped;
    // the detail resolves {ticket} via a scoped findOrFail in render() → cross-org 404.
    Route::get('/maintenance', MaintenanceList::class)->name('maintenance');
    // Maintenance COSTS (analytics) + SETTINGS (Phase 1 · Sub-gate 4) — declared BEFORE `{ticket}` so "costs"/
    // "settings" are not captured as a ticket id. Gated on `maintenance.manage`. Costs is read-only real
    // aggregates; Settings persists typed columns (SLA / category-assignee / toggles) — never a toast-only save.
    Route::get('/maintenance/costs', OperatorMaintenanceCosts::class)->middleware('can:maintenance.manage')->name('maintenance.costs');
    // #89 — Costs report EXPORTS (PDF report + CSV billable line-items + email-to-self). Gated maintenance.manage
    // (the operator owns this cost data); every export is audited.
    Route::get('/maintenance/costs/export.csv', [MaintenanceCostExportController::class, 'csv'])->middleware('can:maintenance.manage')->name('maintenance.costs.csv');
    Route::get('/maintenance/costs/export.pdf', [MaintenanceCostExportController::class, 'pdf'])->middleware('can:maintenance.manage')->name('maintenance.costs.pdf');
    // D12b-1 — the workbook: the SAME scope + period as the CSV/PDF above, same maintenance.manage gate.
    Route::get('/maintenance/costs/export.xlsx', [MaintenanceCostExportController::class, 'xlsx'])->middleware('can:maintenance.manage')->name('maintenance.costs.xlsx');
    Route::post('/maintenance/costs/email', [MaintenanceCostExportController::class, 'email'])->middleware('can:maintenance.manage')->name('maintenance.costs.email');
    Route::get('/maintenance/settings', OperatorMaintenanceSettings::class)->middleware('can:maintenance.manage')->name('maintenance.settings');
    Route::post('/maintenance/settings', [MaintenanceSettingsController::class, 'update'])->middleware('can:maintenance.manage')->name('maintenance.settings.update');
    Route::get('/maintenance/{ticket}', MaintenanceTicketDetail::class)->name('maintenance.show');
    // Maintenance writes — REAL open + assign via POST → controller → MaintenanceService → redirect (PRG).
    // A foreign unit_id / cross-org {ticket} 404s; a guarded illegal transition flashes cleanly (never 500).
    // #104 GATE 0 — every maintenance WRITE is gated `can:maintenance.manage` (the dedicated permission the
    // Maintenance/Manager roles hold; NOT Front Desk/Reservation Agent/Accountant), matching the already-gated
    // costs/settings siblings. The controller carries no authorize(), so this route middleware is the actor gate
    // (#20/#30) — closing the privilege-within-org hole where `resolve` posts a REAL deductible owner expense.
    Route::middleware('can:maintenance.manage')->group(function () {
        Route::post('/maintenance', [MaintenanceActionController::class, 'store'])->name('maintenance.store');
        // GATE 6.2 — BULK actions from the board's select mode. DECLARED BEFORE the /{ticket}/ routes so
        // `/maintenance/bulk/assign` is never captured as {ticket}='bulk'. Each loops the certified
        // MaintenanceService per ticket (tenant-scoped whereIn → foreign ids fail-safe-excluded); same actor
        // gate (maintenance.manage).
        Route::post('/maintenance/bulk/assign', [MaintenanceActionController::class, 'bulkAssign'])->name('maintenance.bulk.assign');
        Route::post('/maintenance/bulk/resolve', [MaintenanceActionController::class, 'bulkResolve'])->name('maintenance.bulk.resolve');
        Route::post('/maintenance/bulk/export', [MaintenanceActionController::class, 'bulkExport'])->name('maintenance.bulk.export');
        Route::post('/maintenance/{ticket}/assign', [MaintenanceActionController::class, 'assign'])->name('maintenance.assign');
        // 2026-09-29 — the board's Edit: title, description, category, priority, while the work is open. The ticket is
        // resolved through the property allow-list too (#38), and the service refuses a closed one.
        Route::post('/maintenance/{ticket}/edit', [MaintenanceActionController::class, 'update'])->name('maintenance.update');
        // Lifecycle writes from the styled ticket detail — each offered only when the real state allows it;
        // the service is still the authority (illegal transition → clean flash, never 500). Cross-org → 404.
        Route::post('/maintenance/{ticket}/start', [MaintenanceActionController::class, 'start'])->name('maintenance.start');
        Route::post('/maintenance/{ticket}/resolve', [MaintenanceActionController::class, 'resolve'])->name('maintenance.resolve');
        Route::post('/maintenance/{ticket}/cancel', [MaintenanceActionController::class, 'cancel'])->name('maintenance.cancel');
        // #89 — awaiting-parts state + proof photos. Tenant + ticket scoped; read-only respected.
        Route::post('/maintenance/{ticket}/parts', [MaintenanceActionController::class, 'setParts'])->name('maintenance.parts');
        // #105 — a real Activity update (Note / Progress / Part-ordered) posted to the ticket timeline.
        Route::post('/maintenance/{ticket}/notes', [MaintenanceActionController::class, 'addNote'])->name('maintenance.notes.store');
        Route::post('/maintenance/{ticket}/photos', [MaintenanceActionController::class, 'uploadPhoto'])->name('maintenance.photos.store');
        Route::delete('/maintenance/{ticket}/photos/{media}', [MaintenanceActionController::class, 'deletePhoto'])->name('maintenance.photos.destroy');
    });
    // Demo-readiness · Part A — operator room-type PHOTO management (secure upload; the operator-mutation
    // pattern POST→controller→PRG). {roomType}/{photo} are string ids resolved tenant-scoped → cross-org 404.
    // ⚠️ GATED 2026-08-08 (audit H3 / F-2a-05). These mutate the imagery shown on the PUBLIC storefront and
    // were ungated — any back-office account could upload, reorder or delete a property's public photos.
    // `units.manage` is the existing gate for room/unit content.
    Route::post('/room-types/{roomType}/photos', [RoomTypePhotoController::class, 'store'])->middleware('can:units.manage')->name('room-types.photos.store');
    Route::post('/room-types/{roomType}/photos/{photo}/cover', [RoomTypePhotoController::class, 'setCover'])->middleware('can:units.manage')->name('room-types.photos.cover');
    Route::post('/room-types/{roomType}/photos/{photo}/move', [RoomTypePhotoController::class, 'move'])->middleware('can:units.manage')->name('room-types.photos.move');
    Route::delete('/room-types/{roomType}/photos/{photo}', [RoomTypePhotoController::class, 'destroy'])->middleware('can:units.manage')->name('room-types.photos.destroy');
    // Wave 2 · Sub-gate 1 — the operator RESERVATIONS LIST (tenant-scoped, read-only). Declared BEFORE
    // the {reservation} detail so the literal path wins. Stage filters come from the shared
    // App\Support\Reservations\ReservationStage — the same resolver the badge uses, so a chip can never
    // disagree with what it filters on.
    Route::get('/reservations', ReservationsList::class)->name('reservations');

    // New reservation (Wave 4) — the READ screen + the guarded booking write. MUST precede the
    // `{reservation}` show route so "create" is not captured as an id. The write is the certified
    // AssignmentService::createBooking (single path, no double-booking); gated on `reservations.create`
    // (Front Desk / Reservation Agent / Hotel Manager / Group Admin / Property Owner). An optional MANUAL
    // payment inside the controller additionally checks `payments.record`. Never a raw PAN (GUARD 3).
    Route::get('/reservations/create', NewReservation::class)->middleware('can:reservations.create')->name('reservations.create');
    Route::post('/reservations/create', [ReservationCreateController::class, 'store'])->middleware('can:reservations.create')->name('reservations.store');

    // GROUP BOOKING (Phase 1 · Sub-gate 1) — a block of rooms created ATOMICALLY through the single write path
    // (AssignmentService::createGroupBooking — all-or-nothing, no phantom events). Same `reservations.create`
    // gate as a single booking; the dates ride the GET (scope-safe availability), the block rides the POST.
    Route::get('/group-booking', GroupBooking::class)->middleware('can:reservations.create')->name('group-booking');
    Route::post('/group-booking', [GroupBookingController::class, 'store'])->middleware('can:reservations.create')->name('group-booking.store');

    Route::get('/reservations/{reservation}', ReservationDetail::class)->name('reservations.show');
    // Demo-readiness · Part B — operator folio PDF. TENANT-scoped (string id → findOrFail → cross-org 404).
    // Real append-only ledger; reconciles to the cent; streams application/pdf.
    // ⚠️ OP-FIX-2 (audit Finding 1) — the downloadable folio DOCUMENT. Gated on the folio read permission,
    // which keeps Front Desk / Reservation Agent (a guest's folio is operational, per the ruling) and
    // excludes Housekeeping / Maintenance, who have no folio business.
    Route::get('/reservations/{reservation}/folio.pdf', [FolioPdfController::class, 'download'])->middleware('can:folios.view')->name('reservations.folio-pdf');
    // Wave 2 · Sub-gate 3 — the dedicated REFUND screen. Read-only; the write reuses the existing
    // `reservations.refund` POST below (now amount-aware). The screen exists to RESPECT the #27 cumulative
    // guard at the UI: it shows the real remaining-refundable per payment, caps the input at it, and offers
    // nothing once remaining = 0 — so the button cannot invite a rejection. The service stays the authority.
    // Gated on the same permission as the POST it exists to perform (2026-07-17). It is a GET, but its ONLY
    // purpose is refunding: leaving it open would let an unpermitted role open a refund form that 403s on
    // submit — precisely the "UI must not invite a rejection" failure #27 warns about, just one click later.
    Route::get('/reservations/{reservation}/refund', ReservationRefund::class)
        ->middleware('can:payments.refund')->name('reservations.refund-screen');
    // Wave 2 · Sub-gate 4 — the operator GUESTS LIST (the CRM). Tenant-scoped, read-only.
    // ⚠️ This is the PII-visible surface (locked decision 4): guest email/phone are decrypted for display
    // HERE and nowhere else — the front desk, the reservations list and the inbox stay name-only.
    // "Total billed" comes from the shared App\Support\Guest\GuestSpend and the status badge from the shared
    // App\Support\Reservations\ReservationStage, so this page and the guest profile cannot disagree.
    // ⚠️ GATED 2026-08-08 (audit H3 / F-2a-01). This page renders FULL guest contact PII — the #28 rule says
    // it renders HERE and nowhere else — yet it carried no permission at all, so a Housekeeping account (which
    // holds only units.view/housekeeping.*/schedule.view) loaded the entire guest database and could bulk-export
    // it. `guests.view` already existed and is already withheld from Housekeeping/Maintenance/Accountant/Revenue
    // Manager: the intent was modelled, only the enforcement was missing.
    Route::get('/guests', GuestsList::class)->middleware('can:guests.view')->name('guests');
    // Wave 2 · Sub-gate 5 — the guest PROFILE + its REAL writes. The design's "Save changes" fired a toast
    // and persisted nothing; these POSTs go through GuestService (the single path that records the audit +
    // domain events). {guest} is a plain string resolved tenant-scoped in the component/controller (never
    // RMB) → cross-org 404. Contact fields are encrypted casts — the cast does the encrypting.
    // #87 — Add guest (real create) + the list BULK actions (Message · Delete[erasure] · Export). The bulk
    // DELETE is `guests.erase` (#30), like the single erase.
    // ⚠️ CORRECTED 2026-08-08 (audit H3 / F-2a-01). This block used to read "Message/Export carry no extra gate
    // (any back-office user ON THE CRM)". That reasoning was sound but its premise was false: reaching the CRM
    // was itself ungated, so "any back-office user on the CRM" meant EVERY back-office user, including
    // Housekeeping. With the list now behind `guests.view`, the premise holds — and each write additionally
    // carries the gate its action deserves. Export stays at `guests.view`: it exports exactly what the reader
    // can already see (Marketing Manager / Guest Support Agent legitimately export their own CRM).
    Route::post('/guests', [GuestActionController::class, 'store'])->middleware('can:guests.manage')->name('guests.store');
    Route::post('/guests-bulk/message', [GuestBulkActionController::class, 'message'])->middleware('can:guests.manage')->name('guests.bulk.message');
    Route::post('/guests-bulk/tag', [GuestBulkActionController::class, 'tag'])->middleware('can:guests.manage')->name('guests.bulk.tag');
    Route::post('/guests-bulk/export', [GuestBulkActionController::class, 'export'])->middleware('can:guests.view')->name('guests.bulk.export');
    Route::post('/guests-bulk/anonymize', [GuestBulkActionController::class, 'anonymize'])
        ->middleware('can:guests.erase')->name('guests.bulk.anonymize');
    Route::get('/guests/{guest}', GuestProfile::class)->middleware('can:guests.view')->name('guests.show');
    Route::post('/guests/{guest}', [GuestActionController::class, 'updateProfile'])->middleware('can:guests.manage')->name('guests.update');
    Route::post('/guests/{guest}/message', [GuestActionController::class, 'startThread'])->middleware('can:guests.manage')->name('guests.message');
    Route::post('/guests/{guest}/notes', [GuestActionController::class, 'addNote'])->middleware('can:guests.manage')->name('guests.notes.store');
    Route::post('/guests/{guest}/tags', [GuestActionController::class, 'addTag'])->middleware('can:guests.manage')->name('guests.tags.store');
    Route::delete('/guests/{guest}/tags', [GuestActionController::class, 'removeTag'])->middleware('can:guests.manage')->name('guests.tags.destroy');
    // ⚠️ GDPR ERASURE (2026-07-17) — the trigger `Guest::anonymize()` never had. It was called from NOWHERE:
    // the erase was correct and tested, and no operator could run it, so a data-subject request could not
    // actually be served. Gated on `can:guests.erase` (Property Owner / Group Admin only — invariant #30):
    // running the house does not include erasing a person. IRREVERSIBLE, so the controller verifies a TYPED
    // confirmation server-side — a client dialog is bypassable by anyone who can POST.
    Route::post('/guests/{guest}/anonymize', [GuestActionController::class, 'anonymize'])
        ->middleware('can:guests.erase')->name('guests.anonymize');
    // Design pass · Operator Screen 4 — REAL append-only folio actions via POST → controller →
    // FolioService → redirect (PRG). {reservation} is a string param resolved tenant-scoped in the
    // controller (never RMB) → cross-org 404. A refund is a NEW offsetting entry (never an edit).
    // Wave 2 · Sub-gate 2 — the LIFECYCLE transitions behind the terminal-state panels (cancel / mark
    // no-show). POST → controller → AssignmentService (the single guarded inventory write path, which
    // frees `sold`, writes reservation_status_history and emits the domain event) → PRG.
    //
    // ⚠️ THE STATUS BOUND EXISTS IN BOTH LAYERS, and the controller's copy is the SURFACE half.
    // `AssignmentService::terminate()` DOES guard its source state: it throws
    // ReservationAlreadyTerminalException when the reservation is already in TERMINAL_STATUSES
    // (cancelled / no_show / checked_out), BEFORE the transaction opens, so a rejected call touches no
    // inventory. That is shipped invariant #29 in CLAUDE.md, and the method carries its own @throws.
    // ⚠️ This comment previously stated the OPPOSITE — that terminate() had no guard and that adding one
    // was "outstanding" — which was false and sat exactly where an engineer reads before touching the
    // reservation lifecycle (audit F-19). Corrected 2026-09-13 against the code, not against memory.
    // ReservationLifecycleController::CANCELLABLE / ::NO_SHOWABLE remain load-bearing: they bound WHICH
    // source states the UI offers, which is a narrower question than "is this already terminal", and the
    // component gates its buttons off those same constants so the UI cannot offer what the write refuses.
    // The controller's `catch (RuntimeException)` is still real: releaseLine() → lockAvailability() can
    // throw inside the transaction, and the rollback is total.
    Route::post('/reservations/{reservation}/cancel', [ReservationLifecycleController::class, 'cancel'])
        ->middleware('can:reservations.cancel')->name('reservations.cancel');
    Route::post('/reservations/{reservation}/no-show', [ReservationLifecycleController::class, 'markNoShow'])
        ->middleware('can:reservations.cancel')->name('reservations.no-show');

    // ⚠️ R5 (F-60) — a TENTATIVE group hold becomes a real stay. Gated on `reservations.manage`,
    //    which already gates the guest-request confirm/decline pair and is held by Group Admin,
    //    Hotel Manager, Front Desk and Reservation Agent. ⚠️ NO permission was minted for this.
    Route::post('/reservations/{reservation}/confirm', [ReservationLifecycleController::class, 'confirm'])
        ->middleware('can:reservations.manage')->name('reservations.confirm');

    // Phase 4b · SG3 — guest change REQUESTS: the operator confirm/decline side (status only; the real change is
    // applied with the operator's certified tools). A guest can never reach here (web guard, back-office gate).
    Route::get('/guest-requests', [GuestRequestAdminController::class, 'index'])->middleware('can:reservations.view')->name('guest-requests');
    Route::post('/guest-requests/{guestRequest}/confirm', [GuestRequestAdminController::class, 'confirm'])->middleware('can:reservations.manage')->name('guest-requests.confirm');
    Route::post('/guest-requests/{guestRequest}/decline', [GuestRequestAdminController::class, 'decline'])->middleware('can:reservations.manage')->name('guest-requests.decline');
    // Reviews (Phase 5) — see reviews (reviews.view), respond + moderate (reviews.manage). A reputation task,
    // NOT Front Desk. Actions carry a STRING {review}, resolved tenant-scoped in-action (cross-org → 404).
    Route::get('/reviews', [OperatorReviewsController::class, 'index'])->middleware('can:reviews.view')->name('reviews');
    Route::post('/reviews/{review}/respond', [OperatorReviewsController::class, 'respond'])->middleware('can:reviews.manage')->name('reviews.respond');
    Route::post('/reviews/{review}/publish', [OperatorReviewsController::class, 'publish'])->middleware('can:reviews.manage')->name('reviews.publish');
    Route::post('/reviews/{review}/reject', [OperatorReviewsController::class, 'reject'])->middleware('can:reviews.manage')->name('reviews.reject');
    Route::post('/reservations/{reservation}/payments', [ReservationFolioActionController::class, 'recordPayment'])
        ->middleware('can:payments.record')->name('reservations.payment');
    Route::post('/reservations/{reservation}/refunds', [ReservationFolioActionController::class, 'refund'])
        ->middleware('can:payments.refund')->name('reservations.refund');
    // Batch C · GATE 2 — ADD CHARGE: a new folio line via the append-only FolioService::postCharge (#17).
    Route::post('/reservations/{reservation}/charge', [ReservationFolioActionController::class, 'addCharge'])
        ->middleware('can:folios.manage')->name('reservations.charge');

    // ⚠️ COMPANY ACCOUNTS — bill an outstanding folio balance to an account, or return it to the guest.
    //    Gated like every other folio settlement decision; the service refuses cross-org and settled folios
    //    regardless, so the gate is defence in depth rather than the only guard.
    Route::post('/reservations/{reservation}/bill-to-account', [ReservationFolioActionController::class, 'billToAccount'])
        ->middleware('can:folios.manage')->name('reservations.bill-to-account');
    Route::post('/reservations/{reservation}/unbill-from-account', [ReservationFolioActionController::class, 'unbillFromAccount'])
        ->middleware('can:folios.manage')->name('reservations.unbill-from-account');
    // Batch C · GATE 2 — MOVE ROOM: the SINGLE guarded inventory write (AssignmentService::assignUnit —
    // availability lock, no-overlap, same-room-type). Same-room-type only (Q5); a cross-type/occupied target
    // is refused. #104 GATE 0 — gated `can:reservations.assign` (the dedicated permission Group Admin/Hotel
    // Manager/Front Desk hold; NOT Reservation Agent/Accountant/Housekeeping). The controller carries no
    // authorize(), so this is the actor gate for an inventory write (#20/#30).
    Route::post('/reservations/{reservation}/move', [FrontDeskActionController::class, 'move'])->middleware('can:reservations.assign')->name('reservations.move');
    // GATE 5.3 — DRAG-TO-RESCHEDULE: the Calendar drag routes to the SAME guarded inventory write path
    // (AssignmentService::reschedule — availability lock, release-old/claim-new, Level A/B). Same actor gate
    // as move (`can:reservations.assign`); the stay length is preserved server-side (no client date math).
    Route::post('/reservations/{reservation}/reschedule', [FrontDeskActionController::class, 'reschedule'])->middleware('can:reservations.assign')->name('reservations.reschedule');
    Route::get('/housekeeping', HousekeepingBoard::class)->name('housekeeping');
    // Design pass · Operator Screen 3 — REAL housekeeping status advance via POST → action → redirect
    // (PRG). {task} is a string param resolved tenant-scoped in the controller (never RMB) → cross-org 404.
    // #104 GATE 0 — gated `can:housekeeping.manage` (matching the assign/photos siblings; NOT Front Desk's
    // housekeeping.view-only). The controller carries no authorize(), so this is the actor gate (#20/#30).
    Route::post('/housekeeping/{task}/status', [HousekeepingActionController::class, 'updateStatus'])->middleware('can:housekeeping.manage')->name('housekeeping.status');
    // Batch C · GATE 1 — assign a REAL housekeeper (Housekeeping dept via StaffRoster) to a task. POST →
    // HousekeepingService::assign → PRG; `housekeeping.manage`-gated; a non-housekeeper/cross-org id refused.
    Route::post('/housekeeping/{task}/assign', [HousekeepingActionController::class, 'assign'])->middleware('can:housekeeping.manage')->name('housekeeping.assign');
    // GATE 8.4 — the "Undo" behind the assign / assign-all toast: clear the assignment. Both `housekeeping.manage`
    // -gated, read-only-guarded, tenant-scoped. `bulk-unassign` is 2-segment so it is never captured as {task}.
    Route::post('/housekeeping/bulk-unassign', [HousekeepingActionController::class, 'bulkUnassign'])->middleware('can:housekeeping.manage')->name('housekeeping.bulk-unassign');
    Route::post('/housekeeping/{task}/unassign', [HousekeepingActionController::class, 'unassign'])->middleware('can:housekeeping.manage')->name('housekeeping.unassign');
    // #88 — Assign-all (auto-balance across the real housekeepers) + task PROOF PHOTOS (the media-upload
    // security pattern). All `housekeeping.manage`-gated; tenant + property scoped; read-only respected.
    Route::post('/housekeeping/assign-all', [HousekeepingActionController::class, 'assignAll'])->middleware('can:housekeeping.manage')->name('housekeeping.assign-all');
    Route::post('/housekeeping/{task}/photos', [HousekeepingActionController::class, 'uploadPhoto'])->middleware('can:housekeeping.manage')->name('housekeeping.photos.store');
    Route::delete('/housekeeping/{task}/photos/{media}', [HousekeepingActionController::class, 'deletePhoto'])->middleware('can:housekeeping.manage')->name('housekeeping.photos.destroy');
    // Phase 7d — TEAM SCHEDULE. VIEW is broad (any staff sees the team roster, `schedule.view`); ASSIGN is a
    // manager-level act (`schedule.manage`, NOT Front Desk). The write is a real POST (NOT a Livewire action)
    // so the /app property middleware runs and the shift lands on the SELECTED property (PropertyContext).
    Route::get('/schedule', OperatorSchedule::class)->middleware('can:schedule.view')->name('schedule');
    Route::post('/schedule/shift', [ScheduleController::class, 'assign'])->middleware('can:schedule.manage')->name('schedule.assign');
    // GATE 9.1 — DRAG-TO-MOVE / SWAP a shift. Same manager gate + real-POST discipline as assign; dates are the
    // grid's server-rendered cell strings (never a client toISOString). Atomic + leave-guarded in the service.
    Route::post('/schedule/shift/move', [ScheduleController::class, 'moveShift'])->middleware('can:schedule.manage')->name('schedule.move');
    // The week's roster as a PDF (real shifts/staff/leave, selected property — reconciles to the grid). Broad view.
    Route::get('/schedule/pdf', [ScheduleController::class, 'pdf'])->middleware('can:schedule.view')->name('schedule.pdf');
    // TIME OFF — requesting OWN is broad + self-only; grant/approve/deny are manager-level. 🔒 The self-approval
    // separation (a manager can't approve/grant their OWN leave) is enforced in LeaveService, mutation-proven.
    Route::post('/schedule/time-off', [ScheduleController::class, 'requestTimeOff'])->middleware('can:schedule.view')->name('schedule.timeoff');
    Route::post('/schedule/leave', [ScheduleController::class, 'grantLeave'])->middleware('can:schedule.manage')->name('schedule.leave.grant');
    Route::post('/schedule/leave/{leave}/approve', [ScheduleController::class, 'approveLeave'])->middleware('can:schedule.manage')->name('schedule.leave.approve');
    Route::post('/schedule/leave/{leave}/deny', [ScheduleController::class, 'denyLeave'])->middleware('can:schedule.manage')->name('schedule.leave.deny');
    // #104b GATE 3 — publish the week (finalize + notify; edits stay live) + open-shift slots (create / fill via
    // the certified assign / remove). schedule.manage-gated, read-only-guarded, audited.
    Route::post('/schedule/publish', [ScheduleController::class, 'publishWeek'])->middleware('can:schedule.manage')->name('schedule.publish');
    Route::post('/schedule/open-shifts', [ScheduleController::class, 'createOpenShift'])->middleware('can:schedule.manage')->name('schedule.open.create');
    Route::post('/schedule/open-shifts/{openShift}/fill', [ScheduleController::class, 'fillOpenShift'])->middleware('can:schedule.manage')->name('schedule.open.fill');
    Route::delete('/schedule/open-shifts/{openShift}', [ScheduleController::class, 'removeOpenShift'])->middleware('can:schedule.manage')->name('schedule.open.remove');
    // Phase 2 Step 8 — front-desk staff surface (arrivals/departures/in-house + action contracts).
    Route::get('/front-desk', FrontDesk::class)->name('front-desk');
    // Design pass · Operator Screen 2 — REAL check-in/out via POST → action → redirect (PRG). The
    // {reservation} is a string param resolved tenant-scoped in the controller (never RMB), so the
    // mutation re-runs the tenant middleware and cross-org ids 404. #104 GATE 0 — gated by the dedicated
    // `reservations.checkin`/`reservations.checkout` permissions (held by Group Admin/Hotel Manager/Front Desk —
    // the roles that actually work the desk; NOT Reservation Agent/Accountant/Housekeeping/Maintenance). The
    // controller carries no authorize(), so these route gates are the actor gate (#20/#30).
    Route::post('/front-desk/{reservation}/check-in', [FrontDeskActionController::class, 'checkIn'])->middleware('can:reservations.checkin')->name('front-desk.check-in');
    Route::post('/front-desk/{reservation}/check-out', [FrontDeskActionController::class, 'checkOut'])->middleware('can:reservations.checkout')->name('front-desk.check-out');

    // ⚠️ R3 (founder, 2026-09-19) — reversing a mis-clicked checkout. TWO routes on purpose: the GET is the
    //    confirmation surface (it writes nothing and shows what CANNOT be undone), the POST performs it.
    //    Gated on `reservations.checkout` — the same desk that made the mistake corrects it.
    Route::get('/front-desk/{reservation}/reverse-checkout', [FrontDeskActionController::class, 'reverseCheckoutConfirm'])->middleware('can:reservations.checkout')->name('front-desk.reverse-checkout');
    Route::post('/front-desk/{reservation}/reverse-checkout', [FrontDeskActionController::class, 'reverseCheckout'])->middleware('can:reservations.checkout')->name('front-desk.reverse-checkout.store');
    // ⚠️ R8 (2026-09-22) — the mirror of the two lines above. ⚠️ Gated on `reservations.checkin`, NOT on a new
    // permission: 🔬 measured, the holders of checkin and checkout are the SAME five roles, so reversing an
    // arrival is bounded by the same authority that created it. A new permission would ship a role catalogue
    // change nobody ruled on (#30), and inventing one to look careful is how catalogues rot.
    Route::get('/front-desk/{reservation}/reverse-checkin', [FrontDeskActionController::class, 'reverseCheckinConfirm'])->middleware('can:reservations.checkin')->name('front-desk.reverse-checkin');
    Route::post('/front-desk/{reservation}/reverse-checkin', [FrontDeskActionController::class, 'reverseCheckin'])->middleware('can:reservations.checkin')->name('front-desk.reverse-checkin.store');
});

/*
 * Phase 3 · Module 1 Step 6 — OWNER PORTAL (unstyled contract stubs). `auth` + SetTenantForWebRequest
 * (web group) set the user + org; SetOwnerScopeForPortal then activates the owner scope (row-level,
 * within the tenant) so an owner sees ONLY their own units/statements/payouts and a non-linked user
 * fails closed. Typed models are resolved owner+tenant-scoped in render() (never RMB props). Styled
 * owner PWA is the design pass.
 */
// ⚠️⚠️ R-GATE (founder, 2026-09-19) — WIRED, AND ORDERED BEFORE THE SCOPE. `EnsureOwnerPortalAccess` was
// built and mutation-proven in `702efe1f` and left deliberately unregistered pending this ruling, because
// wiring it reddens ~35 certified assertions that encode "a non-owner sees an EMPTY portal" as intended.
// The founder ruled the refusal, so those assertions move.
//
// ⚠️ THE ORDER IS THE RULING: the gate runs BEFORE SetOwnerScopeForPortal, so a non-owner is refused before
// any owner resolution happens at all. 🔬 It can decide that early because
// `OwnerContext::actorIsOwnerLinked()` reads the two link sources DIRECTLY from the actor and needs only the
// tenant context (set by the `web` group), never the owner scope.
//
// ⚠️ THE TWO ROUTES THAT MUST SERVE NON-OWNERS ARE EXEMPT BY CONSTRUCTION, not by an exclusion list: both
// `owner.team.invite.accept` (a pending invitee is not yet owner-linked) and `owner.account.email.verify`
// (a signed link opened from a new inbox, possibly with no session) are registered OUTSIDE this group.
// ⚠️ ADMIN 01 · PHASE 2 — and a suspended or archived organisation's OWNERS are refused here, the same way.
Route::middleware(['auth', RefuseSuspendedOrganization::class, EnsureOwnerPortalAccess::class, SetOwnerScopeForPortal::class])->prefix('owner')->name('owner.')->group(function () {
    // Phase 7c · SG2 — owner-team capability gates ({@see EnsureOwnerCapability}). Named locals keep the
    // per-route middleware legible; the primary owner acts as co_owner (all capabilities), so every existing
    // owner flow is unaffected — these only restrict invited sub-users to their role's grid.
    $capCalendar = EnsureOwnerCapability::class.':'.OwnerTeamRole::CAP_CALENDAR;   // co-owner + view-only
    $capDocuments = EnsureOwnerCapability::class.':'.OwnerTeamRole::CAP_DOCUMENTS; // co-owner + accountant
    $capExports = EnsureOwnerCapability::class.':'.OwnerTeamRole::CAP_EXPORTS;     // co-owner + accountant
    $capActions = EnsureOwnerCapability::class.':'.OwnerTeamRole::CAP_ACTIONS;     // co-owner only
    $capPayout = EnsureOwnerCapability::class.':'.OwnerTeamRole::CAP_PAYOUT;       // co-owner only

    Route::get('/', OwnerDashboard::class)->name('dashboard');
    // Welcome / onboarding (Wave B · GATE 6) — the honest thin flow (real units + rate + payout hand-off, NO IBAN).
    Route::get('/welcome', OwnerWelcome::class)->name('welcome');
    // WAVE 3 — the OWNER INBOX (owner ↔ the operator's staff). OWNER-scoped by OwnerScope: MessageThread
    // gained `owner_id` + BelongsToOwner, so a cross-owner thread AND every operator↔guest thread
    // (owner_id NULL) 404 here — the two-inbox boundary falls out of the scope, not out of a predicate.
    // The reply is a REAL message via InboxService::postOwnerMessage() (inbound = into the org), never a
    // fake send. {thread} is a plain string resolved in the component/controller (never RMB).
    Route::get('/messages', OwnerMessages::class)->name('messages');
    // ⚠️⚠️ DEC-m (2026-09-30, his ruling) — a message attachment opens ONLY for someone in that conversation, signed in:
    //    this group's owner-portal gates, then the owner scope on the thread ({@see MessageAttachments::find()}) — another
    //    owner's file is a 404. The back office's door is `app.inbox.attachment`.
    Route::get('/messages/attachments/{media}', MessageAttachmentController::class)->name('messages.attachment');
    // ⚠️⚠️ THIS ROUTE WAS REMOVED ON 2026-09-22 AND RESTORED THE SAME DAY. THE REMOVAL WAS WRONG AND THE
    // REASON IT LOOKED RIGHT IS WORTH MORE THAN THE ROUTE.
    //
    // It was removed as an unreferenced orphan on a census that found no view, component, Mailable,
    // notification, test or JS naming `owner.messages.show`, and 710 persisted notification rows carrying no
    // `/owner/messages/<id>` URL. ⚠️⚠️ **EVERY ONE OF THOSE MEASUREMENTS WAS TRUE, AND THE CONCLUSION WAS
    // STILL FALSE: three tests in `OwnerInboxIsolationTest` reach this route BY URL, never by name** —
    // `->get('/owner/messages/'.$thread->id)` — so a search for the route NAME could not see them. ⚠️ SWEEP
    // THE ROUTE, NOT THE TEXT: a name-search is blind to the callers that build the path themselves, which is
    // the same blind spot that hides a JS-built URI.
    //
    // ⚠️⚠️ AND WHAT THOSE TESTS PIN IS NOT A DEEP LINK — IT IS AN ISOLATION BOUNDARY. They assert a
    // cross-owner or guest thread is *"genuinely a 404 through the portal, not merely absent from a list"*
    // and *"not a 403 that leaks its existence"*. The owner-scoped `findOrFail` below is the mechanism that
    // produces that 404. 🔬 With the route gone the same URL returned **405**, because the POST reply route
    // occupies the identical URI — so removing it did not delete a dead end, it replaced an owner-scoped
    // refusal with an unscoped one, on the two-inbox boundary.
    //
    // ⚠️ So the GET half exists for the same reason the POST half does, and the redirect to `?c=` is the
    // convenience, not the purpose. The comment that was here before — "a deep-link redirects to it" — was
    // the part that was genuinely wrong: nothing emits such a link, and that is fine, because this route's
    // job is to refuse correctly when someone types or guesses one.
    Route::get('/messages/{thread}', function (string $thread) {
        $model = MessageThread::query()->findOrFail($thread);

        return redirect()->route('owner.messages', ['c' => $model->getKey()]);
    })->name('messages.show');
    Route::post('/messages', [OwnerMessageActionController::class, 'start'])->name('messages.start');
    Route::post('/messages/{thread}', [OwnerMessageActionController::class, 'reply'])->name('messages.reply');
    // GATE 2 — rich message actions, all OWNER-scoped (the {thread} findOrFail 404s a cross-owner/guest thread)
    // + the message_id re-validated to belong to that thread. Real writes through InboxService (audited #20).
    Route::post('/messages/{thread}/react', [OwnerMessageActionController::class, 'react'])->name('messages.react');
    Route::post('/messages/{thread}/star', [OwnerMessageActionController::class, 'star'])->name('messages.star');
    Route::post('/messages/{thread}/pin', [OwnerMessageActionController::class, 'pin'])->name('messages.pin');
    Route::get('/statements/{statement}', OwnerStatementView::class)->name('statements.show');
    // Demo-readiness · Part B — branded owner-statement PDF. OWNER-scoped (string id → findOrFail under
    // OwnerScope → cross-owner 404, fail-closed for non-linked). Real owner_ledger figures; reconciles to
    // the cent; no invented lines. Streams application/pdf.
    // 🔒 Phase 7c · SG2 — EXPORTS (PDF downloads) require the exports capability: co-owner + accountant only.
    // A view-only sub-user is "no exports" (design) — refused here even on their own owner's data. The
    // owner-scope 404 still applies inside; this gate is the role layer on top.
    Route::middleware($capExports)->group(function () {
        Route::get('/statements/{statement}/pdf', [OwnerStatementPdfController::class, 'download'])->name('statements.pdf');
        // Wave 1 · Sub-gate 2 — two more owner PDFs on the SAME owner-scoped pattern (string id →
        // findOrFail under OwnerScope → cross-owner 404; fail-closed for a non-linked user). Both reuse
        // PdfService + the shared MoneyFormatter, so they reconcile to the screens to the cent.
        Route::get('/statements/{statement}/bookings', [OwnerBookingSummaryPdfController::class, 'download'])->name('statements.bookings');
        Route::get('/payouts/{payout}/remittance', [OwnerRemittancePdfController::class, 'download'])->name('payouts.remittance');
        // Owner Statement exact-match — the statement-HISTORY exports (CSV + a per-year summary PDF), owner-scoped
        // over the finalized statements (OwnerScope → cross-owner rows never appear; fail-closed for non-linked).
        // A DISTINCT path prefix (not /statements/…) so the exports can't be shadowed by /statements/{statement}/pdf
        // (which would resolve {statement}="history" → a 404). Optional ?year= clips the set (never widens it).
        Route::get('/statement-history/csv', [OwnerStatementHistoryExportController::class, 'csv'])->name('statements.history.csv');
        Route::get('/statement-history/pdf', [OwnerStatementHistoryExportController::class, 'pdf'])->name('statements.history.pdf');
        // DO24-1 — the FULL ARCHIVE as one ZIP (every statement PDF the Archive page lists). ⚠️ Bulk egress:
        // it sits in this same CAP_EXPORTS group as the individual statement PDFs, so bundling can never be
        // a way around the gate on downloading one; the rows are owner+org scoped by the model's global
        // scopes, and the masked IBAN inside each PDF still obeys CAP_PAYOUT (#28). Throttled — it renders
        // one PDF per statement.
        Route::get('/archive/zip', [OwnerArchiveZipController::class, 'download'])
            ->middleware('throttle:6,1')->name('archive.zip');
        // Owner Booking Detail exact-match (#78) — the per-booking CONFIRMATION PDF. OWNER-scoped inside the
        // controller (the SAME guard as the page: 404 unless the reservation touches one of THIS owner's units);
        // guest NAME only (#28); reconciles to the on-screen earnings via the shared OwnerLedgerAttribution. The
        // 3-segment path can't collide with /bookings/{booking} (2 segments) or /bookings/export|target.
        Route::get('/bookings/{booking}/confirmation', [OwnerBookingConfirmationPdfController::class, 'download'])->name('bookings.confirmation');
        // Owner Payouts exact-match — the payout-history CSV (owner-scoped; no guest data; real payout states).
        Route::get('/payouts/export', [OwnerPayoutsExportController::class, 'download'])->name('payouts.export');
        // Owner Balance exact-match — the balance-activity CSV (owner-scoped; the real ledger + running balance).
        Route::get('/balance/export', [OwnerBalanceExportController::class, 'download'])->name('balance.export');
        // Owner Expenses exact-match — the expense-history CSV (owner-scoped; the deductible tri-state + real vendor).
        Route::get('/expenses/export', [OwnerExpensesExportController::class, 'download'])->name('expenses.export');
        // Owner Agreement exact-match (#82) — the agreement + amendment PDFs. OWNER-scoped EXPLICITLY inside
        // the controller (management_agreements / agreement_amendments carry no OwnerScope) — a cross-owner
        // amendment id 404s; fail-closed for a non-linked user. The amendment path is 4 segments so it can't
        // collide with the 2-segment acknowledge POST.
        Route::get('/agreement/pdf', [OwnerAgreementPdfController::class, 'agreement'])->name('agreement.pdf');
        Route::get('/agreement/amendments/{amendment}/pdf', [OwnerAgreementPdfController::class, 'amendment'])->name('agreement.amendments.pdf');
        // "Email to me" (completeness sweep · Wave A · GATE 4) — queues the owner's latest finalized statement PDF
        // to their OWN inbox (self-only; no recipient in the request). Throttled — a mail action, not a page load.
        Route::post('/reports/email', [OwnerReportEmailController::class, 'send'])->middleware('throttle:6,1')->name('reports.email');
    });
    // Design pass · Screen 3 — owner payouts (owner-scoped; new page for the certified Payout backend).
    Route::get('/payouts', OwnerPayouts::class)->name('payouts');
    // Design pass · Screen 4 — owner units + owner-scoped occupancy calendar (new owner-scoped page).
    // 🔒 SG2 — "Calendar & bookings" capability: co-owner + view-only (an accountant is refused).
    Route::get('/units', OwnerMyUnits::class)->middleware($capCalendar)->name('units');
    Route::get('/units/export', [OwnerUnitsExportController::class, 'download'])->middleware($capExports)->name('units.export');
    // Phase 5 — Owner Reviews (read-only): published reviews for the owner's OWN units (owner-scoped).
    Route::get('/reviews', OwnerReviews::class)->name('reviews');
    // Phase 6b · Analytics — owner Insights + Reports over the SG1 metric layer, scoped to the owner's OWN
    // units (OwnerPortalService::units() + owner-scoped OwnerLedgerEntry). Earnings reconcile to the ledger
    // (the same figures the statements show). Read-only; owner isolation fails closed for a non-linked user.
    Route::get('/insights', OwnerInsights::class)->name('insights');
    Route::get('/reports', OwnerReports::class)->name('reports');
    // Wave 1 · Sub-gate 3 — ONE of the owner's units. Owner-scoped: a non-owned/cross-owner unit id
    // 404s under OwnerScope. Occupancy is THIS-unit/THIS-owner (never room-type-wide).
    Route::get('/units/{unit}', OwnerUnitDetail::class)->middleware($capCalendar)->name('units.show');
    // Phase 2 · Sub-gate 1 — the owner's bookings across their OWN units + one booking's detail. Owner-scoped
    // THROUGH the units: reservations carry no owner_id, so the set is derived from the owner-scoped units and
    // {booking} is REJECTED (404) unless it touches one this owner owns (never trusts the request id). Read-only;
    // guest NAME only (no contact — invariant #28). Filters ride GET so the owner middleware re-runs.
    Route::get('/bookings', OwnerBookings::class)->middleware($capCalendar)->name('bookings');
    // GATE 1 (Owner Bookings exact-match) — the CSV export + the monthly-goal target write. Both are static
    // segments registered BEFORE `/bookings/{booking}` so "export"/"target" are never captured as a booking id.
    // EXPORT: read-only CSV of the SAME owner-scoped rows the page shows (via the shared OwnerBookingsReader),
    // gated `exports` (co-owner + accountant) like the other owner downloads; name-only, no contact PII (#28).
    Route::get('/bookings/export', [OwnerBookingsExportController::class, 'download'])->middleware($capExports)->name('bookings.export');
    // TARGET: sets the owner's real monthly NET goal behind the "Monthly goal" ring (#39 — no fabricated goal).
    // A WRITE on a controller ROUTE (#20/#30), gated `actions` (co-owner only) — a view-only sub-user sees the
    // ring but can't change the shared target; owner-scoped, fail-closed for a non-linked user.
    Route::post('/bookings/target', [OwnerBookingsTargetController::class, 'store'])->middleware($capActions)->name('bookings.target');
    Route::get('/bookings/{booking}', OwnerBookingDetail::class)->middleware($capCalendar)->name('bookings.show');
    // Phase 2 · Sub-gate 2 — the owner Block Calendar. Reads are owner-scoped through the unit; the WRITES go
    // through OwnerBlockService → the certified AssignmentService (single inventory write path). The unit is
    // resolved from the owner's OWN units (never the request id) — a foreign unit id is 403 — and blockInventory
    // enforces non-overlap (a booked/blocked date is rejected by the real assertUnitFree rule).
    // 🔒 SG2 — the calendar READ is "Calendar & bookings" (co-owner + view-only); the block/release WRITES are
    // "Approvals" (co-owner only), so a view-only sub-user can see the calendar but cannot block/release.
    Route::get('/calendar', OwnerBlockCalendar::class)->middleware($capCalendar)->name('calendar');
    Route::post('/calendar/block', [OwnerBlockController::class, 'store'])->middleware($capActions)->name('calendar.block');
    Route::delete('/calendar/blocks/{block}', [OwnerBlockController::class, 'destroy'])->middleware($capActions)->name('calendar.release');
    Route::delete('/calendar/series/{series}', [OwnerBlockController::class, 'destroySeries'])->middleware($capActions)->name('calendar.release-series');
    // Completeness sweep · Wave A · GATE 1 — the shared attention-state writes (snooze/dismiss/read/undo). The
    // posted key is resolved back to a REAL signal (OwnerAttention::signalFor), owner-scoped; CAP_ACTIONS (a
    // view-only sub-user SEES the feed but cannot act on it); every write audited.
    Route::post('/attention/snooze', [OwnerAttentionController::class, 'snooze'])->middleware($capActions)->name('attention.snooze');
    Route::post('/attention/dismiss', [OwnerAttentionController::class, 'dismiss'])->middleware($capActions)->name('attention.dismiss');
    Route::post('/attention/read', [OwnerAttentionController::class, 'read'])->middleware($capActions)->name('attention.read');
    Route::post('/attention/restore', [OwnerAttentionController::class, 'restore'])->middleware($capActions)->name('attention.restore');
    // Wave B · 💡 — BULK actions over the currently-raised signals (mark-all-read / snooze-all-for-a-week).
    Route::post('/attention/read-all', [OwnerAttentionController::class, 'readAll'])->middleware($capActions)->name('attention.read-all');
    Route::post('/attention/snooze-all', [OwnerAttentionController::class, 'snoozeAll'])->middleware($capActions)->name('attention.snooze-all');

    // RATE PROPOSALS (Wave C · GATE 1 · ⚠️ REAL MONEY) — the owner APPROVES (applied via the certified rate path,
    // the rate really changes) or DECLINES (unchanged) a pending operator proposal. CAP_ACTIONS (co-owner only);
    // owner-scoped (RateProposal is BelongsToOwner → cross-owner 404); every decision audited.
    Route::post('/rate-proposals/{proposal}/approve', [OwnerRateProposalController::class, 'approve'])->middleware($capActions)->name('rate-proposals.approve');
    Route::post('/rate-proposals/{proposal}/decline', [OwnerRateProposalController::class, 'decline'])->middleware($capActions)->name('rate-proposals.decline');
    // Owner Rates exact-match (#81) — the owner COUNTERS with their own rate (pending → countered; the operator
    // then accepts or withdraws) + saves the pricing GUARDRAILS that constrain the manager's proposals. Both
    // owner-scoped + CAP_ACTIONS (co-owner only, money-class), audited.
    Route::post('/rate-proposals/{proposal}/counter', [OwnerRateProposalController::class, 'counter'])->middleware($capActions)->name('rate-proposals.counter');
    Route::post('/rates/guardrails', [OwnerGuardrailController::class, 'save'])->middleware($capActions)->name('rates.guardrails');

    // EXPENSE APPROVAL (Wave C · GATE 2 · ⚠️ deduction model) — the owner APPROVES a pending expense (charged to
    // the payout/ledger, reducing net) or QUERIES it (held out of net + opens the relay). CAP_ACTIONS; owner-scoped.
    Route::post('/expenses/{expense}/approve', [OwnerExpenseApprovalController::class, 'approve'])->middleware($capActions)->name('expenses.approve');
    Route::post('/expenses/{expense}/query', [OwnerExpenseApprovalController::class, 'query'])->middleware($capActions)->name('expenses.query');

    // UNIT STATUS (Wave C · GATE 3 · ⚠️ availability) — the owner PAUSES / RESUMES one of their OWN units (a paused
    // unit's capacity is removed via the certified availability recompute → no new bookings). CAP_ACTIONS; owner-scoped.
    Route::post('/units/{unit}/toggle-status', [OwnerUnitStatusController::class, 'toggle'])->middleware($capActions)->name('units.toggle-status');
    // Phase 2 · Sub-gate 3 — Owner Rates (the highest-risk gate: an owner-scoped MONEY write). The owner sets
    // nightly rates DIRECTLY on their OWN room types via the certified RateAndRestrictionService. The write is
    // guarded in OwnerRateService: a room type is editable ONLY if the owner owns it EXCLUSIVELY (every unit is
    // theirs) — resolved through ownership, never a request id — so a shared/foreign room type is 403.
    // 🔒 SG2 — Rates is an "Approval" (setting money): co-owner only, page and write.
    Route::get('/rates', OwnerRates::class)->middleware($capActions)->name('rates');
    Route::post('/rates/apply', [OwnerRateController::class, 'apply'])->middleware($capActions)->name('rates.apply');
    // Phase 2 · Sub-gate 4 — Owner Tax Center. SELF-ONLY: the owner is resolved by user_id (never a request
    // id), so tax details + summaries are always the authenticated owner's own. Saving a tax ID RELEASES held
    // payouts (the genuine hold is in OwnerPayoutService::pay()). The annual PDF is self-only (no owner id in
    // the URL — a non-linked user 404s) and reconciles to the owner's real committed statements.
    // 🔒 SG2 — Tax center is "Documents & tax" (co-owner + accountant, read); SAVING tax details is an
    // "Approval" (co-owner only); the annual summary is an EXPORT (co-owner + accountant).
    Route::get('/tax', OwnerTaxCenter::class)->middleware($capDocuments)->name('tax');
    Route::post('/tax', [OwnerTaxController::class, 'save'])->middleware($capActions)->name('tax.save');
    Route::get('/tax/{year}/summary.pdf', [OwnerTaxController::class, 'downloadAnnual'])->middleware($capExports)->name('tax.annual');
    // VAT return · CSV (Wave D · tax engine) — the REAL computed VAT from configured rates; CAP_EXPORTS, self-only.
    Route::get('/tax/{year}/vat-return.csv', [OwnerTaxController::class, 'vatReturn'])->middleware($capExports)->name('tax.vat-return');
    // Phase 2 · Sub-gate 5 — Owner Payout Method. SELF-ONLY + owner-scoped: new methods attach to the owner
    // resolved by user_id (never a request id); set-default/remove resolve the method through the owner scope
    // (a cross-owner id 404s). The full IBAN is ENCRYPTED + never rendered (masked ···· last4). DATA CAPTURE
    // only — the live rail (Stripe Connect) is deferred, so nothing here moves money.
    // 🔒 Phase 7c · SG2 — the SENSITIVE payout account (encrypted IBAN / where the money goes) is CO-OWNER
    // ONLY. The capability gate refuses an accountant or view-only sub-user on BOTH the read page and every
    // write — a non-co-owner reaching the IBAN is a money-security hole (invariant #28 holds on the column).
    Route::middleware($capPayout)->group(function () {
        Route::get('/payout-method', OwnerPayoutMethod::class)->name('payout-method');
        // Phase 8 · SG3 — connect a Stripe account for the LIVE payout rail (self-only, co-owner via $capPayout).
        Route::post('/payout-method/connect', [OwnerConnectController::class, 'start'])->middleware('throttle:6,1')->name('payout-method.connect');
        Route::post('/payout-method', [OwnerPayoutMethodController::class, 'store'])->name('payout-method.add');
        Route::post('/payout-method/{method}/default', [OwnerPayoutMethodController::class, 'setDefault'])->name('payout-method.default');
        Route::delete('/payout-method/{method}', [OwnerPayoutMethodController::class, 'destroy'])->name('payout-method.remove');
        // Owner Payouts exact-match — the STATED payout preferences (frequency · day · minimum · pause). CO-OWNER
        // ONLY ($capPayout, #28/#42), a controller route (#20). A PREFERENCE, never a schedule/money-movement (#39).
        Route::post('/payouts/preferences', [OwnerPayoutPreferenceController::class, 'save'])->name('payouts.preferences');
        // DO3-3 — ask for another attempt at a FAILED payout. ⚠️ RE-QUEUES ONLY: OwnerPayoutService::retry()
        // writes a new `pending` row; sending stays operator-permissioned (`payouts.manage`) over the
        // config-selected rail, so the owner never touches the money rail. CO-OWNER only ($capPayout) and
        // owner-scoped inside the controller (a cross-owner id 404s). Throttled — a money action, not a read.
        Route::post('/payouts/{payout}/retry', [OwnerPayoutRetryController::class, 'retry'])
            ->middleware('throttle:6,1')->name('payouts.retry');
    });
    // Phase 2 · Sub-gate 6 — Add a unit is an owner REQUEST (self-only, owner-scoped), NOT direct creation: a new
    // unit changes the operator's billable inventory, so the operator reviews the request and creates the real
    // unit as their own action. Plus a STATIC Help surface (FAQ + the real "Message your manager" inbox route).
    // 🔒 SG2 — requesting a new unit is an "Approval" action: co-owner only (page and submit).
    Route::get('/add-unit', OwnerAddUnit::class)->middleware($capActions)->name('add-unit');
    Route::post('/add-unit', [OwnerUnitRequestController::class, 'store'])->middleware($capActions)->name('add-unit.submit');
    // DO9-2 — save the wizard as an owner-private DRAFT (relaxed validation; nothing filed). Same capability
    // gate as the submit ($capActions, co-owner only) and the same controller-route discipline (#20): a draft
    // is still an owner write. Resuming rides the GET above as ?draft={id}, resolved owner-scoped + draft-only.
    Route::post('/add-unit/draft', [OwnerUnitRequestController::class, 'draft'])->middleware($capActions)->name('add-unit.draft');
    // DO9-3 — the owner WITHDRAWS a request they already filed (pending → withdrawn; a soft transition, never a
    // delete). Same capability gate as filing one; owner-scoped inside the controller (a cross-owner id 404s);
    // the pending-only decision is re-made under a row lock in the service, because the page is a snapshot.
    Route::post('/add-unit/{unitRequest}/withdraw', [OwnerUnitRequestController::class, 'withdraw'])
        ->middleware($capActions)->name('add-unit.withdraw');
    Route::get('/help', OwnerHelp::class)->name('help');
    // "Ask Vyrobel" — the grounded support assistant (Wave D · support AI). A controller POST (a plain <form>),
    // rate-limited; a READ over generic help content (no owner-data write). Grounding + guardrails + cost/circuit
    // live in AskVyrobelService; an out-of-scope/account question escalates to the real relay, never fabricates.
    Route::post('/help/ask', [OwnerHelpAskController::class, 'ask'])->middleware('throttle:15,1')->name('help.ask');
    // Phase 3 · Sub-gate 1 — the owner in-app notification feed (the owner's OWN notifications; per-user
    // Notifiable → never another user's, never another owner's).
    Route::get('/notifications', OwnerNotifications::class)->name('notifications');
    // Phase 3 · Sub-gate 2 — owner notification preferences (self-only; audience from the route default).
    Route::get('/notification-preferences', OwnerNotificationPreferences::class)->name('notification-preferences');
    Route::post('/notification-preferences', [NotificationPreferenceController::class, 'update'])->defaults('audience', 'owner')->name('notification-preferences.update');
    // Wave 1 · Sub-gate 1 — the owner-accounting long-tail (all read-only, owner-scoped, over the
    // CERTIFIED owner-accounting backend; presentation only, no service/schema change).
    Route::get('/balance', OwnerBalance::class)->name('balance');
    Route::get('/expenses', OwnerExpenses::class)->name('expenses');
    Route::get('/agreement', OwnerAgreement::class)->name('agreement');
    // Owner Agreement exact-match (#82) — acknowledge a published amendment. The REAL replacement for the
    // wireframe's fake "Review & sign": a real, audited record of consent (#20) that applies the new terms
    // via the certified effective-dating. $capActions (co-owner only — a money-class control); OWNER-scoped
    // inside the controller (a cross-owner amendment 404s). An in-portal ACKNOWLEDGMENT, not an e-signature.
    Route::post('/agreement/amendments/{amendment}/acknowledge', [OwnerAgreementController::class, 'acknowledge'])->middleware($capActions)->name('agreement.amendments.acknowledge');
    Route::get('/archive', OwnerArchive::class)->name('archive');
    // Wave 1 · Sub-gate 4 — the owner's document library: an owner-scoped aggregation over the REAL
    // generatable PDFs (statement · remittance · booking summary). No document is invented.
    // 🔒 SG2 — "Documents & tax": co-owner + accountant (a view-only sub-user is refused).
    Route::get('/documents', OwnerDocuments::class)->middleware($capDocuments)->name('documents');

    // Wave 4 — owner SELF-PROFILE, since rebuilt as the settings HUB (#62). The owner edits their OWN contact
    // details (name + encrypted email/phone). ⚠️ Owner is BelongsToOrganization, NOT BelongsToOwner, so
    // OwnerScope does not scope it: the write resolves the owner by an EXPLICIT `where('user_id', auth id)`
    // (no owner id in the request), so an owner can only ever edit their own row.
    // ⚠️ The note that used to sit here — "the payout account/IBAN, notification prefs, team and 2FA are
    // net-new subsystems, stubbed 'Soon'" — is STALE: all four shipped and the hub REUSES them (the masked
    // IBAN is CAP_PAYOUT-only, prefs are a typed pivot, the team is real, 2FA lives on /owner/security).
    Route::get('/settings', OwnerSettings::class)->name('settings');

    // Per-USER self-profile (name / language / avatar / login-email) — 🔒 self-only via auth(), NO capability
    // gate: EVERY owner-realm user, incl. accountant / view-only SUB-USERS, edits their OWN login (this is the
    // fix for the old sub-user 404, where the page resolved an Owner row by a user_id a sub-user doesn't have).
    // Writes are controller POSTs (never Livewire actions) so route middleware always runs.
    Route::post('/account', [OwnerAccountController::class, 'update'])->name('account.update');
    Route::post('/account/avatar', [OwnerAccountController::class, 'uploadAvatar'])->middleware('throttle:20,1')->name('account.avatar');
    Route::delete('/account/avatar', [OwnerAccountController::class, 'removeAvatar'])->name('account.avatar.remove');
    Route::post('/account/email', [OwnerAccountController::class, 'requestEmailChange'])->middleware('throttle:6,1')->name('account.email');
    Route::post('/account/email/cancel', [OwnerAccountController::class, 'cancelEmailChange'])->name('account.email.cancel');

    // OWNER-PARTY / business details (the entity the management team pays out to) — 🔒 PRIMARY-OWNER-ONLY (#42),
    // gated at the route by EnsurePrimaryOwner. Resolved by an explicit where('user_id', auth id); no owner id
    // in the request, so an owner can only ever edit their own party row.
    Route::post('/settings', [OwnerProfileController::class, 'update'])->middleware(EnsurePrimaryOwner::class)->name('settings.update');

    // ── SECURITY page (owner account area · Gate 2) — per-USER (self-only via auth), controller POSTs only.
    Route::get('/security', OwnerSecurity::class)->name('security');
    // Change password (Auth) — self-only, current password verified, shared reset hash+rotate path.
    Route::post('/security/password', [ChangePasswordController::class, 'owner'])->middleware('throttle:6,1')->name('security.password');
    // Two-factor ENROLLMENT — same shared TwoFactorController, self-only, back() to /owner/security.
    Route::post('/security/2fa/enable', [TwoFactorController::class, 'enable'])->name('security.2fa.enable');
    Route::post('/security/2fa/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:6,1')->name('security.2fa.confirm');
    Route::post('/security/2fa/recovery-codes', [TwoFactorController::class, 'regenerate'])->name('security.2fa.regenerate');
    Route::post('/security/2fa/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:6,1')->name('security.2fa.disable');
    // Real active-session management — "sign out all other sessions" re-auths with the password.
    Route::post('/security/sessions/sign-out-others', [OwnerSecurityController::class, 'signOutOtherSessions'])->middleware('throttle:6,1')->name('security.sessions.signout');
    // Trusted devices (Wave B · GATE 4) — remove one (self-only, re-auth). A trust lets a browser skip 2FA.
    Route::post('/security/trusted-devices/revoke', [OwnerSecurityController::class, 'revokeTrustedDevice'])->middleware('throttle:10,1')->name('security.trusted-devices.revoke');
    // Login approval (Wave B · GATE 4) — turn the new-device email-approval requirement on/off (self-only, re-auth).
    Route::post('/security/login-approval', [OwnerSecurityController::class, 'setLoginApproval'])->middleware('throttle:10,1')->name('security.login-approval');
    // Connected apps (Wave D · OAuth) — revoke a third-party OAuth app (self-only, re-auth, audited; immediate).
    Route::post('/security/connected-apps/revoke', [OwnerConnectedAppController::class, 'revoke'])->middleware('throttle:10,1')->name('security.connected-apps.revoke');
    // Passkeys (Wave B · GATE 4) — register (2-step ceremony) + remove (re-auth), self-only via $request->user().
    Route::post('/security/passkeys/options', [PasskeyController::class, 'options'])->middleware('throttle:20,1')->name('security.passkeys.options');
    Route::post('/security/passkeys', [PasskeyController::class, 'store'])->middleware('throttle:20,1')->name('security.passkeys.store');
    Route::post('/security/passkeys/{passkey}/remove', [PasskeyController::class, 'destroy'])->middleware('throttle:10,1')->name('security.passkeys.remove');
    // Real owner-scoped data export ("Request my data").
    Route::get('/security/export', [OwnerSecurityController::class, 'exportData'])->middleware('throttle:6,1')->name('security.export');
    // "This was me" on the sign-in problems card (2026-10-24) — self-only; the SAME controller serves the back office.
    Route::post('/security/alerts/seen', [SecurityAlertController::class, 'seen'])->middleware('throttle:10,1')->name('security.alerts.seen');
    // DEACTIVATE (pause) — 🔒 PRIMARY-OWNER-ONLY (#42), re-auth with the password, reversible + non-destructive.
    Route::post('/security/deactivate', [OwnerSecurityController::class, 'deactivate'])->middleware([EnsurePrimaryOwner::class, 'throttle:6,1'])->name('security.deactivate');

    // Phase 7c · SG3 — the OWNER TEAM (owner sub-tenancy). 🔒 PRIMARY OWNER ONLY: EnsurePrimaryOwner gates
    // the read screen AND every mutation at the ROUTE (team management is held by no role — not even a
    // co-owner — so self-escalation is structurally impossible). The mutations are real controller routes
    // (NOT Livewire actions) so the owner middleware — which does NOT run on /livewire/update — enforces
    // the gate on every write. {member}/{invite} are string ids resolved owner-scoped in the controller,
    // never trusted from the request.
    Route::middleware(EnsurePrimaryOwner::class)->group(function () {
        Route::get('/team', OwnerTeam::class)->name('team');
        Route::post('/team/invite', [OwnerTeamController::class, 'invite'])->middleware('throttle:10,1')->name('team.invite');
        Route::patch('/team/members/{member}', [OwnerTeamController::class, 'updateRole'])->name('team.members.role');
        Route::delete('/team/members/{member}', [OwnerTeamController::class, 'remove'])->name('team.members.remove');
        Route::post('/team/invites/{invite}/revoke', [OwnerTeamController::class, 'revokeInvite'])->name('team.invites.revoke');
        Route::post('/team/invites/{invite}/resend', [OwnerTeamController::class, 'resendInvite'])->middleware('throttle:10,1')->name('team.invites.resend');
    });
});

// Owner login-email change · VERIFY (owner account area · Gate 1). PUBLIC + `signed`: the link is opened from
// the NEW inbox — possibly on a device where the owner isn't signed in — so it can't sit behind `auth`. Security
// is the HMAC signature (unforgeable) + the `sha1(pending_email)` hash binding (a later change to the pending
// address invalidates any older link), mirroring the guest claim-verify mechanism. Rate-limited.
Route::get('/owner/account/email/verify/{user}/{hash}', [OwnerAccountController::class, 'verifyEmailChange'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('owner.account.email.verify');

/*
|--------------------------------------------------------------------------
| SUPER ADMIN · GATE 0 — the PLATFORM-ADMIN realm (/admin)
|--------------------------------------------------------------------------
| A SEPARATE, hardened realm on the `admin` guard (platform_admins table) — NOT the operator/owner/guest
| realms. It sits ABOVE the tenancy: it uses NEITHER SetTenantForWebRequest's outcome (a platform admin is not
| a `web` User, so no tenant context is ever set here — tenant-scoped queries fail closed unless deliberately
| wrapped in the audited PlatformQuery/runAsSuperAdmin) NOR EnsureBackOfficeAccess (a tenant-scoped gate). Every
| /admin response is noindexed. 2FA is MANDATORY behind EnsurePlatformAdminTwoFactor. There is NO public
| sign-up — the first admin is seeded via `php artisan admin:create`.
*/
Route::prefix('admin')->name('admin.')->middleware(NoIndex::class)->group(function () {
    // Unauthenticated — the login form + the hardened, rate-limited attempt (throttle lives in AdminLoginRequest).
    Route::get('login', [AdminLoginController::class, 'create'])->name('login');
    Route::post('login', [AdminLoginController::class, 'store'])->middleware('throttle:login')->name('login.store');

    // The 2FA login CHALLENGE — reachable ONLY while a pending state exists (RequirePlatformAdminTwoFactorPending).
    Route::middleware(RequirePlatformAdminTwoFactorPending::class)->group(function () {
        Route::get('two-factor', [AdminTwoFactorChallengeController::class, 'create'])->name('2fa.challenge');
        Route::post('two-factor', [AdminTwoFactorChallengeController::class, 'store'])->middleware('throttle:login')->name('2fa.challenge.store');
    });

    // ADMIN 01 · Phase 1 — accepting an invitation. PUBLIC (the invitee has no session), rate-limited, useless
    // without a live 64-character one-time token. It sets the person's OWN password; 2FA is enrolled at sign-in.
    Route::get('invite/{token}', [AdminInviteController::class, 'show'])->middleware('throttle:10,1')->name('invite.show');
    Route::post('invite/{token}', [AdminInviteController::class, 'store'])->middleware('throttle:10,1')->name('invite.store');

    // Authenticated on the `admin` guard. ⚠️ TrackPlatformAdminSession ends a session whose admin was revoked or
    // whose session was force-ended — on every request, on any session driver (production's is `file`).
    Route::middleware(['auth:admin', TrackPlatformAdminSession::class])->group(function () {
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');

        // MANDATORY 2FA ENROLLMENT — deliberately EXEMPT from the mandatory-2FA gate (this is where you enrol),
        // so an un-enrolled admin can complete setup without a redirect loop; nothing else is reachable first.
        Route::get('two-factor/setup', [AdminTwoFactorController::class, 'create'])->name('2fa.setup');
        Route::post('two-factor/setup', [AdminTwoFactorController::class, 'store'])->name('2fa.setup.store');

        // The console — behind MANDATORY 2FA, and ⚠️⚠️ EVERY route below carries a ROLE gate
        // (`EnsurePlatformCapability:<capability>`, reading the ONE matrix in PlatformAdminRole). A route with no
        // capability fails PlatformRoleGateTest, and each gate is mutation-proved there.
        Route::middleware(EnsurePlatformAdminTwoFactor::class)->group(function () {
            $can = static fn (string $capability): string => EnsurePlatformCapability::class.':'.$capability;

            // ── Everyone with a role: the health board, the business overview, the read-only screens.
            Route::middleware($can(PlatformAdminRole::CONSOLE_VIEW))->group(function () {
                Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
                Route::get('overview', [AdminOverviewController::class, 'index'])->name('overview');
                Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
                // ADMIN 04 — ⚠️ registered BEFORE `customers/{organization}`, or "create" would be read as an organisation id.
                Route::get('customers/create', [AdminCustomerController::class, 'create'])->middleware(EnsurePlatformCapability::class.':'.PlatformAdminRole::TENANTS_MANAGE)->name('customers.create');
                Route::get('customers/{organization}', [AdminCustomerController::class, 'show'])->name('customers.show');
                Route::get('triage', [AdminCustomerController::class, 'triage'])->name('customers.triage');
                // ⚠️ ADMIN 01 · PHASE 4 — the signup funnel (recorded facts only) and the privacy desk (read side).
                Route::get('funnel', [AdminFunnelController::class, 'index'])->name('funnel');
                Route::get('privacy', [AdminPrivacyController::class, 'index'])->name('privacy.index');
                // ⚠️ ADMIN 01 · PHASE 5 — hotel statistics (aggregates), announcements and the status page (read side).
                Route::get('hotels', [AdminHotelStatsController::class, 'index'])->name('hotels');
                Route::get('announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
                Route::get('status', [AdminStatusIncidentController::class, 'index'])->name('status.index');
                Route::get('plans', [AdminPlanController::class, 'index'])->name('plans.index');
                Route::get('activity', [AdminActivityController::class, 'index'])->name('activity.index');
                Route::get('audit', [AdminAuditController::class, 'index'])->name('audit.index');
                Route::get('switches', [AdminSwitchController::class, 'index'])->name('switches.index');

                // SELF-SERVICE ACCOUNT — the admin's OWN identity. Every role may manage its own account.
                Route::get('account', [AdminAccountController::class, 'edit'])->name('account.edit');
                Route::put('account/profile', [AdminAccountController::class, 'updateProfile'])->middleware('throttle:20,1')->name('account.profile');
                Route::put('account/password', [AdminAccountController::class, 'changePassword'])->middleware('throttle:6,1')->name('account.password');
                Route::post('account/recovery-codes', [AdminAccountController::class, 'regenerateRecoveryCodes'])->middleware('throttle:6,1')->name('account.recovery-codes');
                Route::post('account/email', [AdminAccountController::class, 'requestEmailChange'])->middleware('throttle:6,1')->name('account.email.request');
                Route::delete('account/email', [AdminAccountController::class, 'cancelEmailChange'])->middleware('throttle:20,1')->name('account.email.cancel');
            });

            // ── Tenant lifecycle (Owner, Support). Every write via PlatformQuery::mutate, with a reason.
            Route::middleware([$can(PlatformAdminRole::TENANTS_MANAGE), 'throttle:20,1'])->group(function () {
                // ⚠️ ADMIN 01 · PHASE 2 — a REAL suspension: staff and owners refused, storefront closed, no money
                //    touched. Archive is a soft delete of a suspended org. The ONE hard delete is ADMIN 05's (below):
                //    Owner-only (tenants.delete on top of this group), by the founder's ruling of 2026-09-26.
                Route::post('customers/{organization}/suspend', [AdminCustomerController::class, 'suspend'])->name('customers.suspend');
                Route::post('customers/{organization}/reactivate', [AdminCustomerController::class, 'reactivate'])->name('customers.reactivate');
                Route::post('customers/{organization}/archive', [AdminCustomerController::class, 'archive'])->name('customers.archive');
                Route::post('customers/{organization}/restore', [AdminCustomerController::class, 'restore'])->name('customers.restore');
                // ⚠️ ADMIN 01 · PHASE 5 — speaking to tenants and to the public: announcements and status incidents.
                Route::post('announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');
                Route::post('announcements/{announcement}/withdraw', [AdminAnnouncementController::class, 'withdraw'])->name('announcements.withdraw');
                Route::post('status', [AdminStatusIncidentController::class, 'store'])->name('status.store');
                Route::post('status/{incident}', [AdminStatusIncidentController::class, 'update'])->name('status.update');
                Route::post('customers/{organization}/internal', [AdminCustomerController::class, 'setInternal'])->name('customers.internal');
                // ADMIN 04 — update an organisation's details (name, country, language, billing; never currency or slug).
                Route::post('customers/{organization}/details', [AdminCustomerController::class, 'updateDetails'])->name('customers.update-details');
                // ADMIN 04 — create an organisation (through TenantProvisioningService; the founder gets a set-password link).
                Route::post('customers', [AdminCustomerController::class, 'store'])->name('customers.store');
                // ⚠️ ADMIN 05 — PERMANENT deletion (the founder's ruling, 2026-09-26). OWNER ONLY, on top of tenants.manage.
                Route::post('customers/{organization}/delete', [AdminCustomerController::class, 'destroy'])
                    ->middleware(EnsurePlatformCapability::class.':'.PlatformAdminRole::TENANTS_DELETE)->name('customers.delete');
            });

            // ── ⚠️ ADMIN 01 · PHASE 4 — REVENUE (Owner, Billing, Analyst): traced to the charge, read-only with respect
            //    to money. No route in this console charges, refunds or transfers.
            Route::middleware($can(PlatformAdminRole::BILLING_VIEW))->group(function () {
                Route::get('revenue', [AdminRevenueController::class, 'index'])->name('revenue');
            });

            // ── ⚠️ ADMIN 01 · PHASE 3 — SUPPORT TOOLS (Owner, Support): account holders, never guests. Every write
            //    needs a reason. BREAK-GLASS lives here too — and answers 404 while it is switched off (dormant).
            Route::middleware($can(PlatformAdminRole::SUPPORT_USE))->group(function () {
                Route::get('support', [AdminSupportController::class, 'index'])->name('support.index');
                Route::post('support/users/{user}/verify-email', [AdminSupportController::class, 'verifyEmail'])->middleware('throttle:20,1')->name('support.verify-email');
                Route::post('support/users/{user}/resend-verification', [AdminSupportController::class, 'resendVerification'])->middleware('throttle:10,1')->name('support.resend-verification');
                Route::post('support/users/{user}/password-reset', [AdminSupportController::class, 'passwordReset'])->middleware('throttle:10,1')->name('support.password-reset');
                Route::post('support/users/{user}/identities/{identity}/unlink', [AdminSupportController::class, 'unlink'])->middleware('throttle:20,1')->name('support.unlink');

                // ⚠️ ADMIN 01 · PHASE 4 — the privacy request REGISTER (record + move along). Tracks; never erases.
                Route::post('privacy/requests', [AdminPrivacyController::class, 'store'])->middleware('throttle:20,1')->name('privacy.store');
                Route::post('privacy/requests/{privacyRequest}', [AdminPrivacyController::class, 'update'])->middleware('throttle:30,1')->name('privacy.update');

                Route::get('break-glass', [AdminBreakGlassController::class, 'index'])->name('break-glass.index');
                Route::post('break-glass', [AdminBreakGlassController::class, 'start'])->middleware('throttle:10,1')->name('break-glass.start');
                Route::post('break-glass/end', [AdminBreakGlassController::class, 'end'])->middleware('throttle:30,1')->name('break-glass.end');
            });

            // ── Billing (Owner, Billing): what a customer pays for. Stripe sync stays deferred.
            Route::middleware($can(PlatformAdminRole::BILLING_MANAGE))->group(function () {
                Route::post('customers/{organization}/plan', [AdminCustomerController::class, 'assignPlan'])->middleware('throttle:20,1')->name('customers.assign-plan');
                Route::get('plans/create', [AdminPlanController::class, 'create'])->name('plans.create');
                Route::post('plans', [AdminPlanController::class, 'store'])->middleware('throttle:30,1')->name('plans.store');
                Route::get('plans/{plan}/edit', [AdminPlanController::class, 'edit'])->name('plans.edit');
                Route::put('plans/{plan}', [AdminPlanController::class, 'update'])->middleware('throttle:30,1')->name('plans.update');
                Route::post('plans/{plan}/toggle', [AdminPlanController::class, 'toggle'])->middleware('throttle:30,1')->name('plans.toggle');
            });

            // ── Kill switches (Owner). Database-backed; a flip takes effect on the next request.
            Route::post('switches/{switch}', [AdminSwitchController::class, 'update'])
                ->middleware([$can(PlatformAdminRole::SWITCHES_MANAGE), 'throttle:20,1'])->name('switches.update');

            // ── Integrations: master channel credentials, ENCRYPTED, write-only (Owner; Analyst may look).
            Route::middleware($can(PlatformAdminRole::INTEGRATIONS_VIEW))->group(function () {
                Route::get('integrations', [AdminIntegrationController::class, 'index'])->name('integrations.index');
                Route::get('integrations/{channel}', [AdminIntegrationController::class, 'edit'])->name('integrations.edit');
            });
            Route::middleware([$can(PlatformAdminRole::INTEGRATIONS_MANAGE), 'throttle:30,1'])->group(function () {
                Route::put('integrations/{channel}', [AdminIntegrationController::class, 'update'])->name('integrations.update');
                Route::post('integrations/{channel}/test', [AdminIntegrationController::class, 'test'])->name('integrations.test');
            });

            // ── The platform admins themselves (Owner; Analyst may look).
            Route::get('admins', [AdminUserController::class, 'index'])->middleware($can(PlatformAdminRole::ADMINS_VIEW))->name('admins.index');
            Route::middleware([$can(PlatformAdminRole::ADMINS_MANAGE), 'throttle:20,1'])->group(function () {
                Route::post('admins', [AdminUserController::class, 'invite'])->name('admins.invite');
                Route::post('admins/{admin}/invite', [AdminUserController::class, 'reissueInvite'])->name('admins.reissue');
                Route::post('admins/{admin}/role', [AdminUserController::class, 'changeRole'])->name('admins.role');
                Route::post('admins/{admin}/revoke', [AdminUserController::class, 'revoke'])->name('admins.revoke');
                Route::post('admins/{admin}/restore', [AdminUserController::class, 'restore'])->name('admins.restore');
                Route::post('admins/{admin}/sign-out', [AdminUserController::class, 'signOut'])->name('admins.sign-out');
            });
        });
    });

    // SUPER ADMIN · ACCOUNT — email-change VERIFY. PUBLIC + `signed` (the link may be opened on a device where
    // the admin isn't signed in). Security = the HMAC signature + the `sha1(pending_email)` binding (a later
    // change invalidates an older link) + the current-password re-auth already required at request time. The
    // OLD email stays the login until this is opened. Rate-limited.
    Route::get('account/email/verify/{admin}/{hash}', [AdminAccountController::class, 'verifyEmailChange'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('account.email.verify');
});

// ─────────────────────────────────────────────────────────────────────────────────────────────────
// DEPLOY §7.2 — THE LIVENESS ENDPOINT AN EXTERNAL MONITOR POLLS.
//
// ⚠️ THIS HAS TO BE OUTSIDE THE THING IT WATCHES. A check that ran inside the scheduler could never
// report the scheduler being dead — it would simply not run, which looks exactly like healthy silence.
// So the app only PUBLISHES the evidence here; the alarm belongs to an external uptime monitor that
// polls this URL, and to the dead-man's-switch ping in routes/console.php.
//
// ⚠️ 503 IS DELIBERATE, NOT COSMETIC. Every uptime monitor in existence alerts on a non-2xx; almost
// none parses a JSON body. Returning 200 with {"healthy":false} would be a check that technically
// reports the outage while never actually waking anybody.
//
// Deliberately unauthenticated, like Laravel's own /up: a monitor cannot hold a session, and the body
// carries no tenant data — only two timestamps and their ages.
// ⛔ FOUNDER STEP: point an external monitor at this URL. Unpolled, it is evidence nobody is reading.
// ─────────────────────────────────────────────────────────────────────────────────────────────────
Route::get('/up/liveness', function () {
    $report = Heartbeat::report();

    return response()->json($report, $report['healthy'] ? 200 : 503);
})->name('ops.liveness');

// ⚠️⚠️ ADMIN 01 · PHASE 5 — THE PUBLIC STATUS PAGE: manual incidents only, never an internal. Throttled like any public page.
Route::get('/status', StatusPageController::class)->middleware('throttle:60,1')->name('status');

// ADMIN 01 · PHASE 5 — a signed-in tenant user dismisses a Vyrobel announcement for themselves (self-only; 404 for a
// notice that does not reach their organisation). Outside /app and /owner so both realms share one route.
Route::post('/announcements/{announcement}/dismiss', AnnouncementDismissController::class)->middleware(['auth', 'throttle:30,1'])->name('announcements.dismiss');
