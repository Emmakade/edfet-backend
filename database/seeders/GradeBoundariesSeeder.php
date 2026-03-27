<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GradeBoundary;

class GradeBoundariesSeeder extends Seeder
{
    public function run(): void
    {
        $bounds = [
            ['min_score'=>70,'max_score'=>100,'grade'=>'A','remark'=>'Excellent','priority'=>100],
            ['min_score'=>60,'max_score'=>69,'grade'=>'B','remark'=>'Very Good','priority'=>90],
            ['min_score'=>50,'max_score'=>59,'grade'=>'C','remark'=>'Good','priority'=>80],
            ['min_score'=>45,'max_score'=>49,'grade'=>'D','remark'=>'Fair','priority'=>70],
            ['min_score'=>40,'max_score'=>44,'grade'=>'E','remark'=>'Pass','priority'=>60],
            ['min_score'=>0,'max_score'=>39,'grade'=>'F','remark'=>'Fail','priority'=>50],
        ];

        foreach ($bounds as $b) {
            GradeBoundary::firstOrCreate(
                ['min_score'=>$b['min_score'],'max_score'=>$b['max_score'],'grade'=>$b['grade']],
                $b
            );
        }
    }
}
