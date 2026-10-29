<?php

declare(strict_types=1);

namespace App\Support\Reviews;

/**
 * The words for a review tag (I18N-01). A review stores its tags as their English CODES ("Comfy beds") — the guest form's
 * values, validated against GuestReviewController's lists — so every surface that shows a tag (the guest's own review, the
 * operator and owner review lists) says it through this ONE map, in the reader's language. An unknown tag shows as stored.
 */
final class ReviewTag
{
    public static function label(string $code): string
    {
        return match ($code) {
            'Spotless villa' => __('php/http/controllers/guest/guest_review_controller.tag_spotless_villa'),
            'Easy check-in' => __('php/http/controllers/guest/guest_review_controller.tag_easy_check_in'),
            'Great location' => __('php/http/controllers/guest/guest_review_controller.tag_great_location'),
            'Fast replies' => __('php/http/controllers/guest/guest_review_controller.tag_fast_replies'),
            'Comfy beds' => __('php/http/controllers/guest/guest_review_controller.tag_comfy_beds'),
            'Cleanliness' => __('php/http/controllers/guest/guest_review_controller.tag_cleanliness'),
            'Check-in hassle' => __('php/http/controllers/guest/guest_review_controller.tag_check_in_hassle'),
            'Noise' => __('php/http/controllers/guest/guest_review_controller.tag_noise'),
            'Slow replies' => __('php/http/controllers/guest/guest_review_controller.tag_slow_replies'),
            default => $code,
        };
    }

    /**
     * @param  array<int, mixed>  $codes
     * @return list<string>
     */
    public static function labels(array $codes): array
    {
        return array_values(array_map(fn ($code): string => self::label((string) $code), $codes));
    }
}
