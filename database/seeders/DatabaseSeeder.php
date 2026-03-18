<?php

namespace Database\Seeders;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        TravelOrder::factory(5)->for($testUser)->create();

        User::factory(3)
            ->has(TravelOrder::factory(3))
            ->create();
    }
}
