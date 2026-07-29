<?php

use App\Support\Reviews\OwnerReviewSummary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-category sub-ratings on a review (Owner Reviews exact-match · GATE 2) — the guest optionally rates
 * Cleanliness / Communication / Location / Value 1–5, alongside the single overall `rating`. Nullable: a review
 * without sub-ratings simply doesn't contribute to the owner "By category" averages (never a fabricated score,
 * #39). Averaged (published+public only) by {@see OwnerReviewSummary}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->unsignedTinyInteger('rating_cleanliness')->nullable()->after('rating');
            $table->unsignedTinyInteger('rating_communication')->nullable()->after('rating_cleanliness');
            $table->unsignedTinyInteger('rating_location')->nullable()->after('rating_communication');
            $table->unsignedTinyInteger('rating_value')->nullable()->after('rating_location');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn(['rating_cleanliness', 'rating_communication', 'rating_location', 'rating_value']);
        });
    }
};
