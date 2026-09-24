<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADMIN 01 · PHASE 4 — THE PRIVACY REQUEST REGISTER. ⚠️ A NEW TABLE — additive.
 *
 * A request that reaches Vyrobel some other way than a self-service button (an email, a letter, a hotel relaying
 * its guest's request) is RECORDED here with a status and a due date, and every change is on the audit ledger with a
 * reason. ⚠️ Fulfilment is the EXISTING mechanism, never a new one — this table tracks, it does not export or erase.
 * ⚠️⚠️ NO FREE TEXT AND NO GUEST IDENTITY: an account holder by user id; a relayed guest request by ORGANISATION only.
 * A register of requests must not become a new store of the personal data it is about.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('kind', 16);            // export | delete
            $table->string('subject_kind', 24);    // account_holder | relayed_guest
            $table->ulid('subject_user_id')->nullable()->index();
            $table->ulid('related_organization_id')->nullable()->index();
            $table->string('channel', 24);         // email | letter | phone | hotel
            $table->string('status', 16)->index(); // received | in_progress | completed | refused
            $table->timestamp('received_at');
            $table->timestamp('due_at');
            $table->timestamp('closed_at')->nullable();
            $table->ulid('recorded_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
    }
};
