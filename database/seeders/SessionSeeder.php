<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SessionModel;

class SessionSeeder extends Seeder
{
    public function run(): void
    {
        // Create academic sessions if none exist
        if (SessionModel::count() === 0) {
            SessionModel::insert([
              
                [
                    'name' => '2025/2026',
                    'year_start' => '2025-09-01',
                    'year_end' => '2026-08-31',
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }

        $this->command->info('✅ SessionSeeder completed successfully.');
    }
}