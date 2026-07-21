<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * NOTE: WithoutModelEvents is intentionally NOT used here — ULID primary keys are
     * generated via the model `creating` event (HasUlids), which that trait would suppress.
     */
    public function run(): void
    {
        $this->call([
            RoleCatalogSeeder::class,
            BillingPlanSeeder::class,   // the placeholder plan catalog (Phase 7b) — before the demo org's trial
            DemoSeeder::class,
        ]);

        // Dev convenience only: a global platform user (not tenant data).
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
