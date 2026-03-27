<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectsSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name'=>'Mathematics','code'=>'MATH'],
            ['name'=>'English Language','code'=>'ENG'],
            ['name'=>'Basic Science','code'=>'SCI'],
            ['name'=>'Social Studies','code'=>'SOC'],
            ['name'=>'Computer Studies','code'=>'CSC'],
            ['name'=>'Physical Education','code'=>'PE']
        ];

        foreach ($subjects as $s) {
            Subject::firstOrCreate(['code'=>$s['code']], $s);
        }
    }
}
