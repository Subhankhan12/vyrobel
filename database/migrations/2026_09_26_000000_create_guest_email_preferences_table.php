<?php

declare(strict_types=1);

use App\Services\NotificationPreferences;
use App\Support\Email\EmailTemplateResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `guest_email_preferences` — a GUEST's opt-out from an OPTIONAL transactional email category.
 *
 * ⚠️ THIS IS NOT `notification_preferences`, AND IT IS NOT A DUPLICATE OF IT. That table is per-USER,
 * per-event, per-channel and is read by {@see NotificationPreferences::allows()} to gate the
 * IN-APP feed. The guest transactional emails never consult `allows()` at all — they call
 * `Mail::to()->send()` directly, gated only by {@see EmailTemplateResolver::enabled()}.
 * So a guest email opt-out is a gate at the SEND SITE, and it needs its own store. Extending
 * `notification_preferences` polymorphically would have taken a schema risk on the table BOTH sealed realms
 * depend on and STILL required this send-site gate.
 *
 * ⚠️ SPARSE — DEVIATIONS ONLY, exactly the `notification_preferences` convention: a row exists **only when a
 * guest has opted OUT**. No row means send. That makes the default a property of the ABSENCE of data rather
 * than of a written value, so a guest who never visits the screen cannot be silently un-subscribed by a bug
 * in a backfill that never ran.
 *
 * ⚠️ NOT THE MARKETING CONSENT. `guests.marketing_consent` (+ the `marketing.unsubscribe` flow) governs
 * MARKETING on a different legal basis and is untouched by this table. The two are deliberately separate
 * controls; collapsing them would let a transactional opt-out revoke marketing consent, or worse the reverse.
 *
 * ⚠️ Only categories with a LIVE optional sender may appear here: `payment` (PaymentReminderMail) and
 * `reviews` (ReviewRequestMail). `txn` and `pre-arrival` are always-on by ruling (a transactional record; and
 * the door-code nudge a guest must not be able to miss), so they are never written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_email_preferences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->string('category');            // constrained to the catalog by the service, as event_key is
            $table->timestamps();

            // One opt-out per (guest, category) — re-opting out is idempotent, not a second row.
            $table->unique(['guest_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_email_preferences');
    }
};
