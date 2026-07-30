<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner Agreement exact-match (#82) — the REAL amendment-acknowledgment flow that replaces the wireframe's
 * fake "Review & sign" e-signature.
 *
 * `agreement_amendments` — an operator-published change to an owner's terms: a version, a plain-language
 * title/summary, an effective_date, and `new_terms` json (the changed ManagementAgreement fields). Status
 * draft → published (visible to the owner, awaiting acknowledgment) → applied (the owner acknowledged; a
 * superseding effective-dated agreement now carries the new terms).
 *
 * `agreement_amendment_acknowledgments` — the owner's REAL record of consent: WHO (user) acknowledged
 * WHICH version, WHEN. One per amendment (unique). This is an in-portal ACKNOWLEDGMENT, NOT a legally-
 * binding e-signature (Vyrobel provides no e-sig infra — that is honest-deferred); the record is real and
 * audited, the wording never claims legal signature validity.
 *
 * Neither table carries OwnerScope (management_agreements doesn't either) — the owner boundary is applied
 * EXPLICITLY at every read/write via OwnerContext::ownerIds(), fail-closed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_amendments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->string('version', 40);                 // e.g. "2026 rate-card"
            $table->string('title');
            $table->text('summary')->nullable();
            $table->date('effective_date');
            $table->json('new_terms')->nullable();         // changed ManagementAgreement fields; null = no term change
            $table->enum('status', ['draft', 'published', 'applied'])->default('published');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
            $table->index('effective_date');
        });

        Schema::create('agreement_amendment_acknowledgments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('amendment_id')->constrained('agreement_amendments')->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignUlid('acknowledged_by')->constrained('users')->cascadeOnDelete();
            $table->string('acknowledged_version', 40);    // snapshot of the version acknowledged
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            // One acknowledgment per amendment — a re-submit is a no-op, never a second consent row.
            $table->unique('amendment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_amendment_acknowledgments');
        Schema::dropIfExists('agreement_amendments');
    }
};
