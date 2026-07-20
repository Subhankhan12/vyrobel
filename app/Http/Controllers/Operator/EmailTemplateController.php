<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * Operator Email Templates (Phase 3 · Sub-gate 5) — the management view for every transactional email: its
 * subject, its trigger status (live now vs template-ready-trigger-deferred), and the merge variables it renders.
 * Each links to the live preview ({@see EmailPreviewController}).
 *
 * HONEST SCOPE: this is the VIEW, real. EDITING the content (a per-tenant template store + variable substitution
 * + an editor that writes to it + Mailables rendering from the store) is a genuine subsystem and is deferred —
 * templates are authored in code today. The screen says so plainly; there is NO fake "save" that persists
 * nothing. Behind the back-office gate (#26); the catalog is the same one the previews render.
 */
class EmailTemplateController extends Controller
{
    /**
     * key => [title, subject, trigger, variables]. `key` matches the preview route so "Preview" opens the live render.
     *
     * @var array<string, array{title: string, subject: string, trigger: string, variables: list<string>}>
     */
    private const TEMPLATES = [
        'booking-confirmation' => ['title' => 'Booking confirmation', 'subject' => 'Your booking is confirmed · {reference}', 'trigger' => 'Live · on booking confirmed', 'variables' => ['guest name', 'reference', 'property', 'check-in', 'check-out', 'nights', 'total']],
        'cancellation' => ['title' => 'Cancellation', 'subject' => 'Your booking has been cancelled · {reference}', 'trigger' => 'Live · on booking cancelled', 'variables' => ['guest name', 'reference', 'property', 'reason', 'refund (when issued)']],
        'refund-confirmation' => ['title' => 'Refund confirmation', 'subject' => 'Your refund has been processed · {reference}', 'trigger' => 'Live · on refund processed', 'variables' => ['guest name', 'reference', 'amount']],
        'payment-reminder' => ['title' => 'Payment reminder', 'subject' => 'A balance is due before your stay · {reference}', 'trigger' => 'Live · daily emails:send-payment-reminders', 'variables' => ['guest name', 'reference', 'property', 'check-in', 'balance']],
        'pre-arrival' => ['title' => 'Pre-arrival', 'subject' => 'Getting ready for your stay · {reference}', 'trigger' => 'Template ready · trigger deferred (scheduler)', 'variables' => ['guest name', 'property', 'check-in', 'directions', 'access code (when set)']],
        'review-request' => ['title' => 'Review request', 'subject' => 'How was your stay?', 'trigger' => 'Template ready · trigger deferred (Phase 5 · Reviews)', 'variables' => ['guest name', 'property', 'review link (Phase 5)']],
        'waitlist-notification' => ['title' => 'Waitlist notification', 'subject' => 'A room just opened up at {property}', 'trigger' => 'Template ready · trigger deferred (Phase 4 · Waitlist)', 'variables' => ['guest name', 'property', 'dates', 'book link (Phase 4)']],
        'winback' => ['title' => 'Winback', 'subject' => 'We’d love to welcome you back', 'trigger' => 'Template ready · trigger deferred (scheduler)', 'variables' => ['guest name', 'property (optional)']],
        'verify-email' => ['title' => 'Verify email', 'subject' => 'Confirm your email address', 'trigger' => 'Template ready · trigger deferred (Phase 4 · Guest accounts)', 'variables' => ['guest name', 'verify link (Phase 4)']],
    ];

    public function index(): Response
    {
        return response()->view('operator.email-templates', ['templates' => self::TEMPLATES]);
    }
}
