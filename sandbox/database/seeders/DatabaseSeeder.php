<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed Lunar demo data (languages, currencies, taxes, suppliers, etc.)
        $this->call(DemoDataSeeder::class);

        // Sync and import supplier products (Probo, etc.)
        $this->call(SupplierProductsSeeder::class);
    }
}
