{{-- 503 — maintenance mode (`php artisan down --render="errors::503"`, which is exactly what the deploy
     sequence in docs/DEPLOYMENT.md runs). --}}
@include('errors.layout', [
    'code' => 503,
    'title' => __('errors/503.title'),
    'message' => __('errors/503.message'),
])
