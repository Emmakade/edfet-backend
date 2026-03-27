<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        School::firstOrCreate([
            'name' => 'Town Central School',
        ], [
            'address' => 'P.O. Box 123, Yaku Town',
            'mailbox' => 'Box 123',
            'phone' => '08000000000',
            'motto' => 'Knowledge and Virtue',
            'next_term_begins' => now()->addMonths(2)->toDateString()
        ]);
    }
}
