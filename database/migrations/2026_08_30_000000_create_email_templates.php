<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `email_templates` — the per-org, per-template CUSTOMIZATION store (Operator Email Templates exact-match #86).
 * A row overrides the code DEFAULTS for one guest email (subject/heading/message/button + which content blocks
 * show + whether the email is enabled at all). The real #83 Mailables READ this at send, so editing genuinely
 * changes the email. A missing row = the code default (an un-customized template renders byte-identically).
 *
 * Every column is nullable except `enabled` — a null subject/heading/message/button falls back to the catalog
 * default; `blocks` json maps block-key → bool (missing key = the block's default-on). Additive; disturbs no
 * existing send.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('template_key', 64);            // pre-arrival | payment-reminder | refund-confirmation | review-request | verify-email
            $table->boolean('enabled')->default(true);     // off genuinely stops that email
            $table->text('subject')->nullable();
            $table->text('heading')->nullable();
            $table->text('message')->nullable();
            $table->string('button_label')->nullable();
            $table->json('blocks')->nullable();            // block-key => bool (show/hide a content block)
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'template_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
