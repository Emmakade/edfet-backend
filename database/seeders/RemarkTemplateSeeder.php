<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RemarkTemplate;

class RemarkTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // High Achievers (70-100%)
            ['type'=>'teacher','min_avg'=>70,'max_avg'=>100,'remark'=>'Excellent performance, keep it up!'],
            ['type'=>'teacher','min_avg'=>70,'max_avg'=>100,'remark'=>'Outstanding work and dedication.'],
            ['type'=>'head','min_avg'=>70,'max_avg'=>100,'remark'=>'A top-performing student. Keep shining!'],
            ['type'=>'head','min_avg'=>70,'max_avg'=>100,'remark'=>'Exceptional results, very proud!'],

            // Average Performers (50-69%)
            ['type'=>'teacher','min_avg'=>50,'max_avg'=>69,'remark'=>'Good effort, but there is room for improvement.'],
            ['type'=>'teacher','min_avg'=>50,'max_avg'=>69,'remark'=>'Fair performance, aim higher next term.'],
            ['type'=>'head','min_avg'=>50,'max_avg'=>69,'remark'=>'Decent work, but strive for excellence.'],
            ['type'=>'head','min_avg'=>50,'max_avg'=>69,'remark'=>'Keep working hard, you can do better!'],

            // Low Performers (0-49%)
            ['type'=>'teacher','min_avg'=>0,'max_avg'=>49,'remark'=>'Needs serious improvement and focus.'],
            ['type'=>'teacher','min_avg'=>0,'max_avg'=>49,'remark'=>'Struggling with the material, extra help needed.'],
            ['type'=>'head','min_avg'=>0,'max_avg'=>49,'remark'=>'Performance is below expectation, must improve.'],
            ['type'=>'head','min_avg'=>0,'max_avg'=>49,'remark'=>'Significant improvement needed, please seek help.'],
        ];

        foreach ($templates as $t) {
            RemarkTemplate::firstOrCreate(
                ['type'=>$t['type'],'min_avg'=>$t['min_avg'],'max_avg'=>$t['max_avg']],
                $t
            );
        }
    }
}