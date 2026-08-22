<?php

declare(strict_types=1);

use App\Models\PayoutRun;
use App\Services\OwnerInviteService;
use App\Support\Mail\MailSafetyGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * THE PUBLIC CONTACT MESSAGE (front-website Gate 7) — the store behind `/contact`.
 *
 * ⚠️ WHY A TABLE AND NOT JUST A MAILABLE. A contact form whose only output is an email loses every message
 * the moment mail fails, and mail fails quietly: outside `production` {@see MailSafetyGuard}
 * deliberately downgrades every real-sending transport to `log`, so a message sent today would go to a log
 * file and nowhere else. ⚠️ THE ROW IS THE SYSTEM OF RECORD AND THE EMAIL IS A NOTIFICATION — written first,
 * inside the transaction, exactly as {@see OwnerInviteService} persists an invite before it
 * mails one. That ordering is what lets the page honestly say the message was received.
 *
 * ⚠️ PLATFORM-LEVEL — NO `organization_id`, deliberately. A visitor filling in this form has no tenant: they
 * are a stranger asking about the product, and there is no organisation to scope them to. The
 * `BelongsToOrganization` trait would THROW on insert for exactly that reason (its guard fires when a tenant
 * write runs with no tenant context), so using it here would make the form fail closed on every submission.
 * {@see PayoutRun} is the existing platform-level precedent.
 *
 * ⚠️⚠️ THIS TABLE IS PERSONAL DATA, AND IT HAS NO RETENTION RULE. Name, email and free text about their
 * business, from a visitor with no account — the FIRST personal data the marketing site has ever collected.
 * It is listed in docs/legal/DATA-INVENTORY.md as requiring a retention decision, and that decision is the
 * founder's: this migration deliberately does NOT invent a period, because a number chosen here would end up
 * in a privacy policy as though someone had thought about it.
 *
 * ⚠️ THE FIRST VERSION OF THIS COMMENT SAID THE TABLE WAS "recorded in the data inventory WITH A STATED
 * RETENTION PERIOD". It was not — the inventory had been generated before this table existed. That is
 * precisely the defect the Gate 7 audit found in `Guest::anonymize()`, which defers abandoned booking quotes
 * to "the scheduled retention purge" that does not exist. ⚠️ A comment asserting a mechanism is a claim like
 * any other, and it is the most dangerous kind, because the next reader ticks the box and moves on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_contact_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            // ⚠️ ENCRYPTED AT THE APPLICATION LAYER (see the model's casts) — same treatment as
            // `guests.email` / `guests.phone`. A prospective customer's contact details are not less
            // sensitive than a booked guest's just because they have not bought anything yet.
            $table->text('name');
            $table->text('email');
            $table->text('company')->nullable();
            $table->text('message');

            // The two pickers the design draws. Free-form-but-validated: constrained by the controller to a
            // fixed list, so an unexpected value cannot be stored and later rendered.
            $table->string('units_band', 20)->nullable();
            $table->string('topic', 40)->nullable();

            // ⚠️ MINIMAL REQUEST CONTEXT, and no more. The IP is kept only to make abuse investigable and is
            // itself personal data — it carries the same retention as the row. ⚠️ NO user-agent, no referrer,
            // no fingerprint: none of them would be used, and data you do not use is data you should not keep.
            $table->string('ip', 45)->nullable();

            // Whether the notification email actually left. ⚠️ A FACT, not a promise: `false` here with a row
            // present is exactly the situation this table exists to survive.
            $table->boolean('notified')->default(false);
            $table->timestamp('handled_at')->nullable();

            $table->timestamps();

            // The retention sweep reads `created_at`; the founder's inbox reads it newest-first.
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_contact_messages');
    }
};
