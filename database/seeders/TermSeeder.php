<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Term;

class TermSeeder extends Seeder{
    public function run(): void
    {
        // Create terms if none exist
        if (Term::count() === 0) {
            Term::insert([
                [
                    'name' => 'First Term',
                    'session_id' => 1, // Assuming the session created in SessionSeeder has ID 1
                    'starts_at' => '2025-09-15',
                    'ends_at' => '2025-12-12',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Second Term',
                    'session_id' => 1, // Assuming the session created in SessionSeeder has ID 1
                    'starts_at' => '2025-05-01',
                    'ends_at' => '2025-04-02',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
               
            ]);
        }

        $this->command->info('✅ TermSeeder completed successfully.');
    }
}