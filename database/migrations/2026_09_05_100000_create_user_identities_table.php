<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️⚠️ THE LINKING TABLE — AND THE SECURITY CORE OF SOCIAL SIGN-IN IS THE UNIQUE INDEX BELOW.
 *
 * A returning social user is matched on `(provider, provider_user_id)` and NEVER on `email`. Matching by
 * address means anyone who controls a Google account at that address owns the Vyrobel account — including
 * any address the user once held at a domain they no longer control.
 *
 * ⚠️ `email` HERE IS A DISPLAY VALUE ONLY. It is stored so the account page can say which Google account is
 *    linked; nothing resolves an identity through it, and it is deliberately NOT unique — the provider's
 *    address may change, and when it does the link is unaffected because the link is on the identifier.
 *
 * ⚠️⚠️ THE UNIQUENESS IS A DATABASE CONSTRAINT, NOT A VALIDATOR. A validator covers one controller; it does
 *    not cover a seeder, a console command, a queued job or a direct write. Two rows for one Google identity
 *    would mean one Google account signing in as two different Vyrobel users, so the guarantee has to hold
 *    at the layer that no code path can go around.
 *
 * ⚠️ NOT A TENANT TABLE. `users` carries no `organization_id` (a user reaches an org through `memberships`),
 *    so an identity is a property of the LOGIN, not of a tenant, and `BelongsToOrganization` does not apply.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_identities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();

            // The provider key (`google`) and THAT PROVIDER'S stable identifier for the person. For Google
            // this is the OpenID `sub` claim — stable across email changes, which is precisely why it, and
            // not the address, is the identity.
            $table->string('provider', 32);
            $table->string('provider_user_id');

            // Display only. See the note above: never used to resolve an identity.
            $table->string('provider_email')->nullable();

            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // ⚠️⚠️ THE GUARANTEE: one provider identity resolves to at most ONE Vyrobel user, enforced by the
            //    database rather than by whichever code path happens to be writing.
            $table->unique(['provider', 'provider_user_id']);

            // A person may link several providers; each provider at most once per account.
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_identities');
    }
};
