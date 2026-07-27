<?php

use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\Auth\AdminTwoFactorChallengeController;
use App\Http\Controllers\Admin\Auth\AdminTwoFactorController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\IntegrationController as AdminIntegrationController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Auth\AcceptInviteController;
use App\Http\Controllers\Auth\AcceptOwnerTeamInviteController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BookingEngineController;
use App\Http\Controllers\ChannexWebhookController;
use App\Http\Controllers\FrontDeskActionController;
use App\Http\Controllers\Guest\GuestAccountController;
use App\Http\Controllers\Guest\GuestArrivalController;
use App\Http\Controllers\Guest\GuestCheckinController;
use App\Http\Controllers\Guest\GuestClaimController;
use App\Http\Controllers\Guest\GuestDocumentController;
use App\Http\Controllers\Guest\GuestFolioPaymentController;
use App\Http\Controllers\Guest\GuestHomeController;
use App\Http\Controllers\Guest\GuestLoginController;
use App\Http\Controllers\Guest\GuestManageController;
use App\Http\Controllers\Guest\GuestMessagesController;
use App\Http\Controllers\Guest\GuestPasswordResetController;
use App\Http\Controllers\Guest\GuestPaymentsController;
use App\Http\Controllers\Guest\GuestReceiptController;
use App\Http\Controllers\Guest\GuestReservationController;
use App\Http\Controllers\Guest\GuestReviewController;
use App\Http\Controllers\Guest\GuestSavedController;
use App\Http\Controllers\Guest\GuestTripsController;
use App\Http\Controllers\Guest\GuestWalletController;
use App\Http\Controllers\HousekeepingActionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\Operator\BillingActionController;
use App\Http\Controllers\Operator\EmailPreviewController;
use App\Http\Controllers\Operator\EmailTemplateController;
use App\Http\Controllers\Operator\FolioPdfController;
use App\Http\Controllers\Operator\GroupBookingController;
use App\Http\Controllers\Operator\GuestActionController;
use App\Http\Controllers\Operator\GuestRequestAdminController;
use App\Http\Controllers\Operator\InboxActionController;
use App\Http\Controllers\Operator\InviteOwnerController;
use App\Http\Controllers\Operator\MaintenanceActionController;
use App\Http\Controllers\Operator\MaintenanceSettingsController;
use App\Http\Controllers\Operator\OperatorProfileController;
use App\Http\Controllers\Operator\OperatorReviewsController;
use App\Http\Controllers\Operator\PayoutRunActionController;
use App\Http\Controllers\Operator\PropertyController;
use App\Http\Controllers\Operator\PropertySwitchController;
use App\Http\Controllers\Operator\RateActionController;
use App\Http\Controllers\Operator\ReservationCreateController;
use App\Http\Controllers\Operator\ReservationLifecycleController;
use App\Http\Controllers\Operator\RoleAssignmentController;
use App\Http\Controllers\Operator\RoomTypePhotoController;
use App\Http\Controllers\Operator\ScheduleController;
use App\Http\Controllers\Operator\SettingsActionController;
use App\Http\Controllers\Owner\OwnerAccountController;
use App\Http\Controllers\Owner\OwnerBlockController;
use App\Http\Controllers\Owner\OwnerBookingSummaryPdfController;
use App\Http\Controllers\Owner\OwnerConnectController;
use App\Http\Controllers\Owner\OwnerMessageActionController;
use App\Http\Controllers\Owner\OwnerPayoutMethodController;
use App\Http\Controllers\Owner\OwnerProfileController;
use App\Http\Controllers\Owner\OwnerRateController;
use App\Http\Controllers\Owner\OwnerRemittancePdfController;
use App\Http\Controllers\Owner\OwnerSecurityController;
use App\Http\Controllers\Owner\OwnerStatementPdfController;
use App\Http\Controllers\Owner\OwnerTaxController;
use App\Http\Controllers\Owner\OwnerTeamController;
use App\Http\Controllers\Owner\OwnerUnitRequestController;
use App\Http\Controllers\Owner\OwnerUnitsExportController;
use App\Http\Controllers\ReservationFolioActionController;
use App\Http\Controllers\StripeConnectWebhookController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Http\Middleware\EnsureOwnerCapability;
use App\Http\Middleware\EnsurePlatformAdminTwoFactor;
use App\Http\Middleware\EnsurePrimaryOwner;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\NoIndex;
use App\Http\Middleware\RequirePlatformAdminTwoFactorPending;
use App\Http\Middleware\RequireTwoFactorPending;
use App\Http\Middleware\ResolvePublicProperty;
use App\Http\Middleware\SetCurrentPropertyForWebRequest;
use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Http\Middleware\SetTenantForGuestRequest;
use App\Livewire\FrontDesk;
use App\Livewire\HousekeepingBoard;
use App\Livewire\InboxList;
use App\Livewire\InboxThread;
use App\Livewire\MaintenanceList;
use App\Livewire\MaintenanceTicketDetail;
use App\Livewire\Operator\GroupBooking;
use App\Livewire\Operator\GuestProfile;
use App\Livewire\Operator\GuestsList;
use App\Livewire\Operator\NewReservation;
use App\Livewire\Operator\OperatorBilling;
use App\Livewire\Operator\OperatorBillingCheckout;
use App\Livewire\Operator\OperatorDashboard;
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
use App\Livewire\Owner\OwnerMessageThread;
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
use App\Livewire\ReservationDetail;
use App\Livewire\ReservationRack;
use App\Livewire\UnitsList;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Shared Vyrobel login (operator + owner) — real, session-based auth (Auth Phase A). The login POST is
 * CSRF-protected (web group; NOT in the CSRF-except list) and rate-limited per (email, IP) in
 * LoginRequest. Logout is a CSRF-protected POST behind `auth`. `/app/*` + `/owner/*` require `auth`
 * (redirect here when unauthenticated); `/book/*` stays public.
 */
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);

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
 * Notification mark-read writes (Phase 3) — shared by the operator + owner feeds. Behind `auth` only (both
 * portals). SELF-ONLY by construction: the controller writes through $request->user()->notifications, so a
 * cross-user id 404s — a user can only ever mark their OWN notifications read.
 */
Route::middleware('auth')->group(function () {
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
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
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('two-factor.login');
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
    Route::post('/login', [GuestLoginController::class, 'store']);
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
        Route::post('/reservation/{reservation}/manage/cancel', [GuestManageController::class, 'requestCancellation'])->middleware('throttle:12,1')->name('manage.cancel');
        // Online check-in (SG4) — the GUEST's pre-check-in intent (stamps guest_checked_in_at); NOT the operator's
        // authoritative check-in. Arrival info (SG4) — directions/times + the timing-gated door code & wifi.
        Route::get('/reservation/{reservation}/check-in', [GuestCheckinController::class, 'show'])->name('checkin');
        Route::post('/reservation/{reservation}/check-in', [GuestCheckinController::class, 'submit'])->middleware('throttle:12,1')->name('checkin.submit');
        Route::get('/reservation/{reservation}/arrival', [GuestArrivalController::class, 'show'])->name('arrival');
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
        Route::get('/messages', [GuestMessagesController::class, 'index'])->name('messages');
        Route::post('/messages', [GuestMessagesController::class, 'start'])->middleware('throttle:12,1')->name('messages.start');
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
        Route::post('/account/delete', [GuestAccountController::class, 'destroy'])->name('account.delete');
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
Route::middleware(['auth', EnsureBackOfficeAccess::class, SetCurrentPropertyForWebRequest::class, EnsureSubscriptionActive::class])->prefix('app')->name('app.')->group(function () {
    // Design pass · Operator Screen 5 — operator dashboard (new tenant-scoped overview page).
    Route::get('/dashboard', OperatorDashboard::class)->name('dashboard');

    // Phase 6a · multi-property — the topbar property switcher. Sets the per-user working property
    // (or "all") in the session, then PRG back. Any back-office user may switch among the properties
    // they can access; the controller re-validates the id against that set (defence in depth — the
    // middleware validates again on the next request), so a foreign/stale id can never take effect.
    Route::post('/property/switch', [PropertySwitchController::class, 'update'])->name('property.switch');

    // Phase 6a · Step 2 — the PORTFOLIO overview + property CRUD. The overview is gated `properties.view`
    // (every back-office role sees it); creating/editing a property is gated `properties.manage` (Property
    // Owner / Group Admin — NOT Front Desk / Hotel Manager, #30). A created property is org-scoped by
    // BelongsToOrganization; an edit resolves its target by a tenant-scoped id (cross-org 404s).
    Route::get('/portfolio', OperatorPortfolio::class)->middleware('can:properties.view')->name('portfolio');
    Route::post('/portfolio', [PropertyController::class, 'store'])->middleware('can:properties.manage')->name('portfolio.store');
    Route::post('/portfolio/update', [PropertyController::class, 'update'])->middleware('can:properties.manage')->name('portfolio.update');

    // Phase 6b · Analytics — operator Insights + Reports over the SG1 metric layer, scoped by the Phase-6a
    // property context (per-property or the all-properties roll-up). Read-only, gated `can:reports.view` (a
    // management/analytics baseline — NOT Front Desk / Housekeeping / Reservation Agent, #30). Every figure
    // is a REAL aggregate reconciling to FolioTotals; no fabricated history/narrative.
    Route::get('/insights', OperatorInsights::class)->middleware('can:reports.view')->name('insights');
    Route::get('/reports', OperatorReports::class)->middleware('can:reports.view')->name('reports');

    // Phase 7b · Subscription billing — the Paywall + Checkout, gated `can:billing.manage` (the founder /
    // Property Owner, NOT Front Desk). Read-only until the account-gated Stripe charge: the tiers + the
    // honest derived trial status render; the subscribe action is the deferred Stripe-Billing seam (no
    // charge, no "active" off a fake payment, no raw card). Company/VAT captured on the org for invoicing.
    Route::get('/billing', OperatorBilling::class)->middleware('can:billing.manage')->name('billing');
    Route::get('/billing/checkout', OperatorBillingCheckout::class)->middleware('can:billing.manage')->name('billing.checkout');
    Route::post('/billing/company', [BillingActionController::class, 'saveCompany'])->middleware('can:billing.manage')->name('billing.company');
    Route::post('/billing/subscribe', [BillingActionController::class, 'subscribe'])->middleware('can:billing.manage')->name('billing.subscribe');
    // Dev/test-only affordance to demo the access gate (404s outside local/testing) — never a real payment.
    Route::post('/billing/dev-expire', [BillingActionController::class, 'devExpireTrial'])->middleware('can:billing.manage')->name('billing.dev-expire');

    // GLOBAL SEARCH (Phase 1 · Sub-gate 2) — cross-entity search (reservations/guests/units/owners), the query
    // on a GET (scope-safe reads). Gated on `reservations.view` (the operations read baseline); per-entity
    // results follow the viewer's abilities inside the component. Tenant-scoped; NOT availability search.
    Route::get('/search', OperatorSearch::class)->middleware('can:reservations.view')->name('search');
    // Auth Phase B · Step 4 — operator Owners screen + the invite-owner write (POST → controller → PRG).
    Route::get('/owners', OperatorOwners::class)->name('owners');
    Route::post('/owners/invite', [InviteOwnerController::class, 'store'])
        ->middleware(['throttle:10,1', 'can:users.manage'])->name('owners.invite');
    // Wave 1 · Sub-gate 3 — the operator's TENANT-scoped lens on ONE owner (units/agreement/
    // statements/payouts/balance). NOT owner-scoped: OwnerScope is inactive off-portal, so this
    // deliberately reads the whole owner relationship the operator manages, within their own org
    // (Owner is BelongsToOrganization → a cross-org id 404s).
    Route::get('/owners/{owner}', OperatorOwnerDetail::class)->name('owners.show');
    // Phase 2 · Sub-gate 6 — the operator sees owners' add-unit REQUESTS here (tenant-scoped; owner scope is
    // inactive off-portal). Read-only: creating the real unit stays the operator's own action. Gated units.view.
    Route::get('/unit-requests', OperatorUnitRequests::class)->middleware('can:units.view')->name('unit-requests');

    // ROLES & ACCESS (Phase 1 · Sub-gate 3) — VIEW the fixed catalog (roles + read-only permissions + members)
    // and ASSIGN a role to a member. Gated on `users.manage` (only an admin manages roles — the same gate that
    // closed the owner-invite escalation, #30). The role→permission catalog is CODE-DEFINED + read-only; only
    // the user↔role assignment is written, and RoleAssignmentService bounds it by the privilege-subset rule so
    // no one can grant a role with more access than their own.
    Route::get('/roles', OperatorRoles::class)->middleware('can:users.manage')->name('roles');
    Route::post('/roles/assign', [RoleAssignmentController::class, 'assign'])->middleware('can:users.manage')->name('roles.assign');
    Route::post('/roles/revoke', [RoleAssignmentController::class, 'revoke'])->middleware('can:users.manage')->name('roles.revoke');
    // Wave 1 · Sub-gate 4 — the operator PAYOUT RUN (tenant-scoped): finalized statements ready to pay
    // → REAL payouts through the certified OwnerPayoutService (ManualPayoutRail is the working rail;
    // the Stripe-Connect rail stays a config-gated skeleton that THROWS — never faked). The writes use
    // the operator-mutation pattern: POST → controller → service → PRG (CSRF + tenant re-scoped).
    Route::get('/payouts/run', PayoutRun::class)->name('payouts.run');
    Route::post('/payouts/create', [PayoutRunActionController::class, 'create'])
        ->middleware('can:payouts.manage')->name('payouts.create');
    Route::post('/payouts/pay', [PayoutRunActionController::class, 'pay'])
        ->middleware('can:payouts.manage')->name('payouts.pay');
    Route::get('/rack', ReservationRack::class)->name('rack');
    Route::get('/units', UnitsList::class)->name('units');

    // Rates & availability (Wave 4) — the READ grid + the guarded rate/restriction write, which is the
    // FIRST production caller of RateAndRestrictionService (setRate/setRestriction). PER-ROOM-TYPE (the
    // design's per-unit grid can't be backed by the per-rate-plan tables — docs/WAVE4-INVENTORY STRUCT-1),
    // tenant-scoped, integer money parsed server-side. GET gated on `rates.view` (Housekeeping/Maintenance/
    // Marketing/Guest Support hold none → the nav shows them "Soon", never a 403); POST gated on
    // `rates.manage` — deliberately NOT Front Desk or Reservation Agent (invariant #30, separation of duties;
    // RatesGrid gates the editor off the SAME permission so the UI never offers a write the route refuses).
    Route::get('/rates', RatesGrid::class)->middleware('can:rates.view')->name('rates');
    Route::post('/rates', [RateActionController::class, 'apply'])->middleware('can:rates.manage')->name('rates.update');

    // Settings (Wave 4) — ONE coherent settings surface (Operator + Property settings de-conflicted). Only
    // the BACKED columns are written (properties / property_policies / organizations); unbacked design fields
    // are stubbed "Soon", never a fake save. The page + property/policy writers are gated `properties.manage`
    // (Property Owner / Group Admin); the ORG writer is additionally gated `settings.manage` (Property Owner)
    // — per-section separation of duties, NOT Front Desk. Both permissions already exist in the catalog.
    Route::get('/settings', OperatorSettings::class)->middleware('can:properties.manage')->name('settings');
    Route::post('/settings/property', [SettingsActionController::class, 'updateProperty'])->middleware('can:properties.manage')->name('settings.property');
    Route::post('/settings/policies', [SettingsActionController::class, 'updatePolicies'])->middleware('can:properties.manage')->name('settings.policies');
    Route::post('/settings/organization', [SettingsActionController::class, 'updateOrganization'])->middleware('can:settings.manage')->name('settings.organization');
    // Batch C · GATE 4 — edit taxes & fees (bounded rate/amount) via the certified SettingsService.
    Route::post('/settings/tax-fees', [SettingsActionController::class, 'updateTaxesAndFees'])->middleware('can:properties.manage')->name('settings.tax-fees');

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

    // Phase 3 · Sub-gate 5 — Email Templates: the documented management view (subject / trigger / variables) for
    // every transactional email; editing is honestly deferred. Sub-gate 4 · Email Previews render each email with
    // real sample data (no send). Both behind the back-office gate (#26), like /security.
    Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates');
    Route::get('/email-previews', [EmailPreviewController::class, 'index'])->name('email-previews');
    Route::get('/email-previews/{key}', [EmailPreviewController::class, 'show'])->name('email-previews.show');

    // Operator PROFILE (Phase 1 · Sub-gate 5) — the logged-in operator's OWN name + work email. Like /security,
    // behind auth + #26 with NO fine-grained gate (every back-office user has a profile). The write is SELF-ONLY:
    // the controller resolves $request->user() (never a request id), so there is no id to tamper with to reach
    // another account. Only the genuinely-backed fields persist; unbacked design fields are omitted, not faked.
    Route::get('/profile', OperatorProfile::class)->name('profile');
    Route::post('/profile', [OperatorProfileController::class, 'update'])->name('profile.update');

    // Two-factor ENROLLMENT (self-service; the login challenge is separate). Real TOTP (google2fa) — enable →
    // confirm-code (anti-lockout) → one-time recovery codes; regenerate; disable (re-auth). Self-only
    // (auth()-resolved). Confirm/disable rate-limited. Behind #26 like the rest of /app.
    Route::post('/security/2fa/enable', [TwoFactorController::class, 'enable'])->name('security.2fa.enable');
    Route::post('/security/2fa/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:6,1')->name('security.2fa.confirm');
    Route::post('/security/2fa/recovery-codes', [TwoFactorController::class, 'regenerate'])->name('security.2fa.regenerate');
    Route::post('/security/2fa/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:6,1')->name('security.2fa.disable');
    // Unified Inbox — UNSTYLED operator contract stubs (thread list + thread view; styled screen is a
    // later design pass). Tenant-scoped; the view resolves {thread} via a scoped findOrFail → cross-org 404.
    Route::get('/inbox', InboxList::class)->name('inbox');
    Route::get('/inbox/{thread}', InboxThread::class)->name('inbox.show');
    // Inbox writes — REAL reply + lifecycle via POST → controller → InboxService → redirect (PRG).
    // Cross-org {thread} 404s; a deferred channel leaves an honest failed record + clean flash (never 500).
    Route::post('/inbox/{thread}/reply', [InboxActionController::class, 'reply'])->name('inbox.reply');
    Route::post('/inbox/{thread}/assign', [InboxActionController::class, 'assign'])->name('inbox.assign');
    Route::post('/inbox/{thread}/close', [InboxActionController::class, 'close'])->name('inbox.close');
    Route::post('/inbox/{thread}/reopen', [InboxActionController::class, 'reopen'])->name('inbox.reopen');
    Route::post('/inbox/{thread}/snooze', [InboxActionController::class, 'snooze'])->name('inbox.snooze');
    Route::post('/inbox/{thread}/link-reservation', [InboxActionController::class, 'linkReservation'])->name('inbox.link-reservation');
    // Maintenance module — styled work-order board + detail (design pass · Maintenance UI). Tenant-scoped;
    // the detail resolves {ticket} via a scoped findOrFail in render() → cross-org 404.
    Route::get('/maintenance', MaintenanceList::class)->name('maintenance');
    // Maintenance COSTS (analytics) + SETTINGS (Phase 1 · Sub-gate 4) — declared BEFORE `{ticket}` so "costs"/
    // "settings" are not captured as a ticket id. Gated on `maintenance.manage`. Costs is read-only real
    // aggregates; Settings persists typed columns (SLA / category-assignee / toggles) — never a toast-only save.
    Route::get('/maintenance/costs', OperatorMaintenanceCosts::class)->middleware('can:maintenance.manage')->name('maintenance.costs');
    Route::get('/maintenance/settings', OperatorMaintenanceSettings::class)->middleware('can:maintenance.manage')->name('maintenance.settings');
    Route::post('/maintenance/settings', [MaintenanceSettingsController::class, 'update'])->middleware('can:maintenance.manage')->name('maintenance.settings.update');
    Route::get('/maintenance/{ticket}', MaintenanceTicketDetail::class)->name('maintenance.show');
    // Maintenance writes — REAL open + assign via POST → controller → MaintenanceService → redirect (PRG).
    // A foreign unit_id / cross-org {ticket} 404s; a guarded illegal transition flashes cleanly (never 500).
    Route::post('/maintenance', [MaintenanceActionController::class, 'store'])->name('maintenance.store');
    Route::post('/maintenance/{ticket}/assign', [MaintenanceActionController::class, 'assign'])->name('maintenance.assign');
    // Lifecycle writes from the styled ticket detail — each offered only when the real state allows it;
    // the service is still the authority (illegal transition → clean flash, never 500). Cross-org → 404.
    Route::post('/maintenance/{ticket}/start', [MaintenanceActionController::class, 'start'])->name('maintenance.start');
    Route::post('/maintenance/{ticket}/resolve', [MaintenanceActionController::class, 'resolve'])->name('maintenance.resolve');
    Route::post('/maintenance/{ticket}/cancel', [MaintenanceActionController::class, 'cancel'])->name('maintenance.cancel');
    // Demo-readiness · Part A — operator room-type PHOTO management (secure upload; the operator-mutation
    // pattern POST→controller→PRG). {roomType}/{photo} are string ids resolved tenant-scoped → cross-org 404.
    Route::post('/room-types/{roomType}/photos', [RoomTypePhotoController::class, 'store'])->name('room-types.photos.store');
    Route::post('/room-types/{roomType}/photos/{photo}/cover', [RoomTypePhotoController::class, 'setCover'])->name('room-types.photos.cover');
    Route::post('/room-types/{roomType}/photos/{photo}/move', [RoomTypePhotoController::class, 'move'])->name('room-types.photos.move');
    Route::delete('/room-types/{roomType}/photos/{photo}', [RoomTypePhotoController::class, 'destroy'])->name('room-types.photos.destroy');
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
    Route::get('/reservations/{reservation}/folio.pdf', [FolioPdfController::class, 'download'])->name('reservations.folio-pdf');
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
    Route::get('/guests', GuestsList::class)->name('guests');
    // Wave 2 · Sub-gate 5 — the guest PROFILE + its REAL writes. The design's "Save changes" fired a toast
    // and persisted nothing; these POSTs go through GuestService (the single path that records the audit +
    // domain events). {guest} is a plain string resolved tenant-scoped in the component/controller (never
    // RMB) → cross-org 404. Contact fields are encrypted casts — the cast does the encrypting.
    Route::get('/guests/{guest}', GuestProfile::class)->name('guests.show');
    Route::post('/guests/{guest}', [GuestActionController::class, 'updateProfile'])->name('guests.update');
    Route::post('/guests/{guest}/notes', [GuestActionController::class, 'addNote'])->name('guests.notes.store');
    Route::post('/guests/{guest}/tags', [GuestActionController::class, 'addTag'])->name('guests.tags.store');
    Route::delete('/guests/{guest}/tags', [GuestActionController::class, 'removeTag'])->name('guests.tags.destroy');
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
    // ⚠️ THE STATUS GUARD LIVES IN THE CONTROLLER, and that is NOT the usual pattern — read before moving
    // it. `AssignmentService::terminate()` has NO status guard: re-cancelling an already-cancelled
    // reservation does not throw, it silently OVERWRITES `cancelled_at`/`cancellation_reason` (destroying
    // the provenance the terminal panel renders) and appends a junk `cancelled → cancelled` row to the
    // append-only audit trail. Proven empirically — see docs/DECISIONS.md 2026-07-16. So
    // ReservationLifecycleController::CANCELLABLE / ::NO_SHOWABLE are load-bearing, not cosmetic, and the
    // component gates its buttons off those same constants so the UI cannot offer what the write refuses.
    // The proper fix is a guard inside terminate() itself (a service change — recorded as outstanding).
    // The controller's `catch (RuntimeException)` is still real: releaseLine() → lockAvailability() can
    // throw inside the transaction, and the rollback is total.
    Route::post('/reservations/{reservation}/cancel', [ReservationLifecycleController::class, 'cancel'])
        ->middleware('can:reservations.cancel')->name('reservations.cancel');
    Route::post('/reservations/{reservation}/no-show', [ReservationLifecycleController::class, 'markNoShow'])
        ->middleware('can:reservations.cancel')->name('reservations.no-show');

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
    // Batch C · GATE 2 — MOVE ROOM: the SINGLE guarded inventory write (AssignmentService::assignUnit —
    // availability lock, no-overlap, same-room-type). Gated like check-in/out (any back-office; the POST
    // re-runs the tenant middleware). Same-room-type only (Q5); a cross-type/occupied target is refused.
    Route::post('/reservations/{reservation}/move', [FrontDeskActionController::class, 'move'])->name('reservations.move');
    Route::get('/housekeeping', HousekeepingBoard::class)->name('housekeeping');
    // Design pass · Operator Screen 3 — REAL housekeeping status advance via POST → action → redirect
    // (PRG). {task} is a string param resolved tenant-scoped in the controller (never RMB) → cross-org 404.
    Route::post('/housekeeping/{task}/status', [HousekeepingActionController::class, 'updateStatus'])->name('housekeeping.status');
    // Batch C · GATE 1 — assign a REAL housekeeper (Housekeeping dept via StaffRoster) to a task. POST →
    // HousekeepingService::assign → PRG; `housekeeping.manage`-gated; a non-housekeeper/cross-org id refused.
    Route::post('/housekeeping/{task}/assign', [HousekeepingActionController::class, 'assign'])->middleware('can:housekeeping.manage')->name('housekeeping.assign');
    // Phase 7d — TEAM SCHEDULE. VIEW is broad (any staff sees the team roster, `schedule.view`); ASSIGN is a
    // manager-level act (`schedule.manage`, NOT Front Desk). The write is a real POST (NOT a Livewire action)
    // so the /app property middleware runs and the shift lands on the SELECTED property (PropertyContext).
    Route::get('/schedule', OperatorSchedule::class)->middleware('can:schedule.view')->name('schedule');
    Route::post('/schedule/shift', [ScheduleController::class, 'assign'])->middleware('can:schedule.manage')->name('schedule.assign');
    // The week's roster as a PDF (real shifts/staff/leave, selected property — reconciles to the grid). Broad view.
    Route::get('/schedule/pdf', [ScheduleController::class, 'pdf'])->middleware('can:schedule.view')->name('schedule.pdf');
    // TIME OFF — requesting OWN is broad + self-only; grant/approve/deny are manager-level. 🔒 The self-approval
    // separation (a manager can't approve/grant their OWN leave) is enforced in LeaveService, mutation-proven.
    Route::post('/schedule/time-off', [ScheduleController::class, 'requestTimeOff'])->middleware('can:schedule.view')->name('schedule.timeoff');
    Route::post('/schedule/leave', [ScheduleController::class, 'grantLeave'])->middleware('can:schedule.manage')->name('schedule.leave.grant');
    Route::post('/schedule/leave/{leave}/approve', [ScheduleController::class, 'approveLeave'])->middleware('can:schedule.manage')->name('schedule.leave.approve');
    Route::post('/schedule/leave/{leave}/deny', [ScheduleController::class, 'denyLeave'])->middleware('can:schedule.manage')->name('schedule.leave.deny');
    // Phase 2 Step 8 — front-desk staff surface (arrivals/departures/in-house + action contracts).
    Route::get('/front-desk', FrontDesk::class)->name('front-desk');
    // Design pass · Operator Screen 2 — REAL check-in/out via POST → action → redirect (PRG). The
    // {reservation} is a string param resolved tenant-scoped in the controller (never RMB), so the
    // mutation re-runs the tenant middleware and cross-org ids 404.
    Route::post('/front-desk/{reservation}/check-in', [FrontDeskActionController::class, 'checkIn'])->name('front-desk.check-in');
    Route::post('/front-desk/{reservation}/check-out', [FrontDeskActionController::class, 'checkOut'])->name('front-desk.check-out');
});

/*
 * Phase 3 · Module 1 Step 6 — OWNER PORTAL (unstyled contract stubs). `auth` + SetTenantForWebRequest
 * (web group) set the user + org; SetOwnerScopeForPortal then activates the owner scope (row-level,
 * within the tenant) so an owner sees ONLY their own units/statements/payouts and a non-linked user
 * fails closed. Typed models are resolved owner+tenant-scoped in render() (never RMB props). Styled
 * owner PWA is the design pass.
 */
Route::middleware(['auth', SetOwnerScopeForPortal::class])->prefix('owner')->name('owner.')->group(function () {
    // Phase 7c · SG2 — owner-team capability gates ({@see EnsureOwnerCapability}). Named locals keep the
    // per-route middleware legible; the primary owner acts as co_owner (all capabilities), so every existing
    // owner flow is unaffected — these only restrict invited sub-users to their role's grid.
    $capCalendar = EnsureOwnerCapability::class.':'.OwnerTeamRole::CAP_CALENDAR;   // co-owner + view-only
    $capDocuments = EnsureOwnerCapability::class.':'.OwnerTeamRole::CAP_DOCUMENTS; // co-owner + accountant
    $capExports = EnsureOwnerCapability::class.':'.OwnerTeamRole::CAP_EXPORTS;     // co-owner + accountant
    $capActions = EnsureOwnerCapability::class.':'.OwnerTeamRole::CAP_ACTIONS;     // co-owner only
    $capPayout = EnsureOwnerCapability::class.':'.OwnerTeamRole::CAP_PAYOUT;       // co-owner only

    Route::get('/', OwnerDashboard::class)->name('dashboard');
    // WAVE 3 — the OWNER INBOX (owner ↔ the operator's staff). OWNER-scoped by OwnerScope: MessageThread
    // gained `owner_id` + BelongsToOwner, so a cross-owner thread AND every operator↔guest thread
    // (owner_id NULL) 404 here — the two-inbox boundary falls out of the scope, not out of a predicate.
    // The reply is a REAL message via InboxService::postOwnerMessage() (inbound = into the org), never a
    // fake send. {thread} is a plain string resolved in the component/controller (never RMB).
    Route::get('/messages', OwnerMessages::class)->name('messages');
    Route::get('/messages/{thread}', OwnerMessageThread::class)->name('messages.show');
    Route::post('/messages', [OwnerMessageActionController::class, 'start'])->name('messages.start');
    Route::post('/messages/{thread}', [OwnerMessageActionController::class, 'reply'])->name('messages.reply');
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
    });
    // Phase 2 · Sub-gate 6 — Add a unit is an owner REQUEST (self-only, owner-scoped), NOT direct creation: a new
    // unit changes the operator's billable inventory, so the operator reviews the request and creates the real
    // unit as their own action. Plus a STATIC Help surface (FAQ + the real "Message your manager" inbox route).
    // 🔒 SG2 — requesting a new unit is an "Approval" action: co-owner only (page and submit).
    Route::get('/add-unit', OwnerAddUnit::class)->middleware($capActions)->name('add-unit');
    Route::post('/add-unit', [OwnerUnitRequestController::class, 'store'])->middleware($capActions)->name('add-unit.submit');
    Route::get('/help', OwnerHelp::class)->name('help');
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
    Route::get('/archive', OwnerArchive::class)->name('archive');
    // Wave 1 · Sub-gate 4 — the owner's document library: an owner-scoped aggregation over the REAL
    // generatable PDFs (statement · remittance · booking summary). No document is invented.
    // 🔒 SG2 — "Documents & tax": co-owner + accountant (a view-only sub-user is refused).
    Route::get('/documents', OwnerDocuments::class)->middleware($capDocuments)->name('documents');

    // Wave 4 — owner SELF-PROFILE. The owner edits their OWN contact details (name + encrypted
    // email/phone). ⚠️ Owner is BelongsToOrganization, NOT BelongsToOwner, so OwnerScope does not scope it:
    // the write resolves the owner by an EXPLICIT `where('user_id', auth id)` (no owner id in the request),
    // so an owner can only ever edit their own row. The payout account/IBAN, notification prefs, team and 2FA
    // are net-new subsystems, stubbed "Soon".
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
    // Real owner-scoped data export ("Request my data").
    Route::get('/security/export', [OwnerSecurityController::class, 'exportData'])->middleware('throttle:6,1')->name('security.export');
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
    Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');

    // The 2FA login CHALLENGE — reachable ONLY while a pending state exists (RequirePlatformAdminTwoFactorPending).
    Route::middleware(RequirePlatformAdminTwoFactorPending::class)->group(function () {
        Route::get('two-factor', [AdminTwoFactorChallengeController::class, 'create'])->name('2fa.challenge');
        Route::post('two-factor', [AdminTwoFactorChallengeController::class, 'store'])->name('2fa.challenge.store');
    });

    // Authenticated on the `admin` guard.
    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');

        // MANDATORY 2FA ENROLLMENT — deliberately EXEMPT from the mandatory-2FA gate (this is where you enrol),
        // so an un-enrolled admin can complete setup without a redirect loop; nothing else is reachable first.
        Route::get('two-factor/setup', [AdminTwoFactorController::class, 'create'])->name('2fa.setup');
        Route::post('two-factor/setup', [AdminTwoFactorController::class, 'store'])->name('2fa.setup.store');

        // The console — behind MANDATORY 2FA.
        Route::middleware(EnsurePlatformAdminTwoFactor::class)->group(function () {
            Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

            // Build 1 · Gate 2 — Customers (all orgs). Reads via PlatformQuery::read; the lifecycle actions via
            // PlatformQuery::mutate (audited). Rate-limited status actions. No impersonation (deferred).
            Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{organization}', [AdminCustomerController::class, 'show'])->name('customers.show');
            Route::post('customers/{organization}/suspend', [AdminCustomerController::class, 'suspend'])->middleware('throttle:20,1')->name('customers.suspend');
            Route::post('customers/{organization}/reactivate', [AdminCustomerController::class, 'reactivate'])->middleware('throttle:20,1')->name('customers.reactivate');
            Route::post('customers/{organization}/plan', [AdminCustomerController::class, 'assignPlan'])->middleware('throttle:20,1')->name('customers.assign-plan');

            // Build 1 · Gate 3 — Plans (DB-backed catalog; feature toggles drive real gating). Global tables (no
            // tenant bypass needed); every write audited. Stripe billing sync DEFERRED (the stripe_price_id seam).
            Route::get('plans', [AdminPlanController::class, 'index'])->name('plans.index');
            Route::get('plans/{plan}/edit', [AdminPlanController::class, 'edit'])->name('plans.edit');
            Route::put('plans/{plan}', [AdminPlanController::class, 'update'])->middleware('throttle:30,1')->name('plans.update');
            Route::post('plans/{plan}/toggle', [AdminPlanController::class, 'toggle'])->middleware('throttle:30,1')->name('plans.toggle');

            // Build 2 · Gate 1 — Security & activity. Login-activity (platform table) read directly; the
            // cross-tenant audit slice via PlatformQuery::read (audited). Privacy: 90-day retention.
            Route::get('activity', [AdminActivityController::class, 'index'])->name('activity.index');

            // Build 2 · Gate 2 — Integrations. Master channel credentials, ENCRYPTED at rest, write-only/masked;
            // managing is audited. The test-connection is a stub (real sync account-gated).
            Route::get('integrations', [AdminIntegrationController::class, 'index'])->name('integrations.index');
            Route::get('integrations/{channel}', [AdminIntegrationController::class, 'edit'])->name('integrations.edit');
            Route::put('integrations/{channel}', [AdminIntegrationController::class, 'update'])->middleware('throttle:30,1')->name('integrations.update');
            Route::post('integrations/{channel}/test', [AdminIntegrationController::class, 'test'])->middleware('throttle:30,1')->name('integrations.test');

            // SUPER ADMIN · ACCOUNT — the admin manages their OWN identity. Self-only; sensitive changes re-auth
            // (current_password:admin); email change is re-verified; all audited. Mirrors the owner account discipline.
            Route::get('account', [AdminAccountController::class, 'edit'])->name('account.edit');
            Route::put('account/profile', [AdminAccountController::class, 'updateProfile'])->middleware('throttle:20,1')->name('account.profile');
            Route::put('account/password', [AdminAccountController::class, 'changePassword'])->middleware('throttle:6,1')->name('account.password');
            Route::post('account/recovery-codes', [AdminAccountController::class, 'regenerateRecoveryCodes'])->middleware('throttle:6,1')->name('account.recovery-codes');
            Route::post('account/email', [AdminAccountController::class, 'requestEmailChange'])->middleware('throttle:6,1')->name('account.email.request');
            Route::delete('account/email', [AdminAccountController::class, 'cancelEmailChange'])->middleware('throttle:20,1')->name('account.email.cancel');
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
