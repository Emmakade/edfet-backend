<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        School::firstOrCreate([
            'name' => 'Gomal Baptist',
        ], [
            'name2' => 'Nursery and Primary School',
            'address' => 'P.O. Box 1981, Old Osogbo road, Ogbomoso.',
            'mailbox' => 'Box 1981',
            'phone' => '08000000000',
            'motto' => 'Arise and Shine',
            'next_term_begins' => now()->addMonths(2)->toDateString(),
            'extra' => json_encode([
                'school_logo' => 'https://gomalbaptist.edu.ng/logo.png',
                'school_website' => 'https://gomalbaptist.edu.ng',
                'school_email' => 'info@gomalbaptist.edu.ng',
            ]),
        ]);
    }
}
