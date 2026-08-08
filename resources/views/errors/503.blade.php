{{-- 503 — maintenance mode (`php artisan down --render="errors::503"`, which is exactly what the deploy
     sequence in docs/DEPLOYMENT.md runs). --}}
@include('errors.layout', [
    'code' => 503,
    'title' => 'Back in a moment',
    'message' => 'Vyrobel is being updated. This usually takes less than a minute — please try again shortly.',
])
