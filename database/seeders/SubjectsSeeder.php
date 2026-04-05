<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectsSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name'=>'MATHEMATICS','code'=>'MATH'],
            ['name'=>'ENGLISH LANGUAGE','code'=>'ENG'],
            ['name'=>'SOCIAL STUDIES','code'=>'SOC'],
            ['name'=>'CHRISTIAN RELIGIOUS STUDIES','code'=>'CRS'],
            ['name'=>'CULTURAL AND CREATIVE ART','code'=>'CCA'],
            ['name'=>'AGRICULTURAL SCIENCE ','code'=>'AGS'],
            ['name'=>'COMPUTER STUDIES','code'=>'CSC'],
            ['name'=>'HANDWRITING','code'=>'HWR'],
            ['name'=>'LIT-IN-ENGLISH','code'=>'LIT'],
            ['name'=>'SCIENCE AND TECHNOLOGY','code'=>'SCT'],
            ['name'=>'VERBAL REASONING','code'=>'VRN'],
            ['name'=>'QUANTITATIVE REASONING','code'=>'QRN'],
            ['name'=>'PHYSICAL AND HEALTH EDUCATION','code'=>'PHE'],
            ['name'=>'CIVIC EDUCATION','code'=>'CVE'],
            ['name'=>'VOCATION APTITUDE','code'=>'VOA'],
            ['name'=>'YORUBA LANGUAGE','code'=>'YOR'],
            ['name'=>'HOME ECONOMIC','code'=>'HME'],
            ['name'=>'HISTORY','code'=>'HIS'],
        ];

        foreach ($subjects as $s) {
            Subject::firstOrCreate(['code'=>$s['code']], $s);
        }
    }
}
