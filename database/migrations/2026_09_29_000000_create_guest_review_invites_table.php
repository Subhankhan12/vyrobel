<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * R3 (founder, 2026-09-19) — THE RECORD THAT MAKES AN HONEST UNDO POSSIBLE.
 *
 * ⚠️⚠️ WHY THIS TABLE EXISTS. Reversing a mis-clicked checkout must tell the operator what the guest has
 * ALREADY received, and the review-request email cannot be recalled. Before this table, `Mail::to(...)->send()`
 * persisted NOTHING, so the product could not distinguish "we invited this guest to review" from "we did not"
 * — and an undo that guessed would be the exact lie the ruling exists to prevent.
 *
 * ⚠️⚠️ THE ROW'S EXISTENCE IS THE BOUNDARY MARKER, AND THAT IS THE WHOLE DESIGN. From this migration onward
 * EVERY checkout writes exactly one row per stay, whatever happened — sent, suppressed by the operator's
 * template switch, suppressed by the guest's own preference, or no contact address. So:
 *
 *   • a row with `sent_at`      → A REQUEST WAS SENT
 *   • a row without `sent_at`   → NO REQUEST WAS SENT, and `outcome` says why
 *   • NO ROW AT ALL             → UNKNOWN: the stay checked out BEFORE this record existed
 *
 * ⚠️ A NULLABLE COLUMN ON `reservations` WOULD HAVE COLLAPSED THE LAST TWO into one empty value, and that
 * collapse IS the defect. Three states need three representations.
 *
 * ⚠️⚠️ THERE IS NO BACKFILL, DELIBERATELY. Past sends left no trace of any kind; deriving one from
 * `checked_out` plus the two feature switches would be a GUESS, and an invented row cannot be told apart from
 * a real one afterwards. The record starts here and the undo reports UNKNOWN for everything behind it.
 *
 * ⚠️ NO GUEST PII. The row names the RESERVATION and the TIME. It never stores an address, a name or a body —
 * "was this guest invited" is answerable without any of them, and an encrypted column is undone the moment its
 * value is copied somewhere plaintext.
 *
 * ⚠️ NO SOFT DELETES, DELIBERATELY. A deletable record re-creates the ambiguity this table was built to end:
 * a removed row is indistinguishable from a stay that predates the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_review_invites', function (Blueprint $table): void {
            $table->char('id', 26)->primary();
            // ⚠️ TENANCY IS NOT OPTIONAL — every business table carries it (BelongsToOrganization).
            $table->char('organization_id', 26);
            // ⚠️ ONE ROW PER STAY. A second checkout of the same stay updates this row rather than appending a
            //    second opinion about the same question.
            $table->char('reservation_id', 26)->unique();
            $table->string('outcome', 32);
            // ⚠️ STICKY: set the first time a send SUCCEEDS and never cleared. "Was this guest ever invited?"
            //    must not be un-answered by a later suppressed attempt.
            $table->timestamp('sent_at')->nullable();
            $table->string('channel', 16)->default('email');
            $table->timestamps();

            $table->index(['organization_id', 'reservation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_review_invites');
    }
};
