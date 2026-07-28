<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webauthn\PublicKeyCredentialSource;

/**
 * Owner completeness sweep · Wave B · GATE 4 — PASSKEYS (WebAuthn).
 *
 * One registered platform passkey per row. `credential_id` is the base64url authenticator credential id (unique —
 * a credential authenticates exactly one user). `public_key` is the serialized {@see PublicKeyCredentialSource}
 * (the public key + signature counter + user handle) written by the web-auth/webauthn-lib serializer; the signature
 * counter inside it advances on each use (replay protection). No secret — a passkey's PRIVATE key never leaves the
 * user's device (that is the whole point). Self-only, resolved by user_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passkeys', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('credential_id', 512)->unique();   // base64url credential id
            $table->text('public_key');                       // serialized PublicKeyCredentialSource (JSON)
            $table->string('label')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passkeys');
    }
};
