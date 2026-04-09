<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RemarkTemplate;

class RemarkTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Excellent Performance (90-100%)
            ['type'=>'teacher','min_avg'=>90,'max_avg'=>100,'remark'=>'Outstanding academic excellence and consistent dedication to studies.'],
            ['type'=>'teacher','min_avg'=>90,'max_avg'=>100,'remark'=>'Exceptional performance across all subjects. A role model for peers.'],
            ['type'=>'teacher','min_avg'=>90,'max_avg'=>100,'remark'=>'Demonstrates superior understanding and mastery of concepts.'],
            ['type'=>'teacher','min_avg'=>90,'max_avg'=>100,'remark'=>'Remarkable achievement and commitment to academic success.'],
            ['type'=>'head','min_avg'=>90,'max_avg'=>100,'remark'=>'A shining example of academic excellence. Keep up the magnificent work!'],
            ['type'=>'head','min_avg'=>90,'max_avg'=>100,'remark'=>'Exceptional results that reflect great dedication and intelligence.'],
            ['type'=>'head','min_avg'=>90,'max_avg'=>100,'remark'=>'Outstanding performance worthy of recognition and admiration.'],
            ['type'=>'head','min_avg'=>90,'max_avg'=>100,'remark'=>'Consistently excellent work that sets high standards for others.'],

            // Very Good Performance (80-89%)
            ['type'=>'teacher','min_avg'=>80,'max_avg'=>89,'remark'=>'Very good performance with strong understanding of subjects.'],
            ['type'=>'teacher','min_avg'=>80,'max_avg'=>89,'remark'=>'Shows great potential and consistent effort in studies.'],
            ['type'=>'teacher','min_avg'=>80,'max_avg'=>89,'remark'=>'Solid academic foundation with room for further improvement.'],
            ['type'=>'teacher','min_avg'=>80,'max_avg'=>89,'remark'=>'Reliable and diligent work across all areas.'],
            ['type'=>'head','min_avg'=>80,'max_avg'=>89,'remark'=>'Very commendable performance and dedication to learning.'],
            ['type'=>'head','min_avg'=>80,'max_avg'=>89,'remark'=>'Strong academic standing with excellent work ethic.'],
            ['type'=>'head','min_avg'=>80,'max_avg'=>89,'remark'=>'Consistent good results that show commitment to excellence.'],
            ['type'=>'head','min_avg'=>80,'max_avg'=>89,'remark'=>'Impressive progress and focus on academic goals.'],

            // Good Performance (70-79%)
            ['type'=>'teacher','min_avg'=>70,'max_avg'=>79,'remark'=>'Good overall performance with satisfactory understanding.'],
            ['type'=>'teacher','min_avg'=>70,'max_avg'=>79,'remark'=>'Meets expectations with consistent effort and application.'],
            ['type'=>'teacher','min_avg'=>70,'max_avg'=>79,'remark'=>'Shows adequate grasp of concepts and willingness to learn.'],
            ['type'=>'teacher','min_avg'=>70,'max_avg'=>79,'remark'=>'Steady progress and participation in class activities.'],
            ['type'=>'head','min_avg'=>70,'max_avg'=>79,'remark'=>'Satisfactory academic performance with good effort.'],
            ['type'=>'head','min_avg'=>70,'max_avg'=>79,'remark'=>'Meets the required standards with dedication.'],
            ['type'=>'head','min_avg'=>70,'max_avg'=>79,'remark'=>'Good foundation that can be built upon further.'],
            ['type'=>'head','min_avg'=>70,'max_avg'=>79,'remark'=>'Consistent work that demonstrates commitment to studies.'],

            // Satisfactory Performance (60-69%)
            ['type'=>'teacher','min_avg'=>60,'max_avg'=>69,'remark'=>'Satisfactory performance with some areas needing attention.'],
            ['type'=>'teacher','min_avg'=>60,'max_avg'=>69,'remark'=>'Meets basic requirements but has potential for improvement.'],
            ['type'=>'teacher','min_avg'=>60,'max_avg'=>69,'remark'=>'Shows effort in most subjects with room for growth.'],
            ['type'=>'teacher','min_avg'=>60,'max_avg'=>69,'remark'=>'Adequate understanding with need for more focus.'],
            ['type'=>'head','min_avg'=>60,'max_avg'=>69,'remark'=>'Acceptable performance that can be enhanced with effort.'],
            ['type'=>'head','min_avg'=>60,'max_avg'=>69,'remark'=>'Meets minimum standards with opportunity for better results.'],
            ['type'=>'head','min_avg'=>60,'max_avg'=>69,'remark'=>'Satisfactory work with potential for significant improvement.'],
            ['type'=>'head','min_avg'=>60,'max_avg'=>69,'remark'=>'Good effort shown, but more dedication needed.'],

            // Below Average Performance (50-59%)
            ['type'=>'teacher','min_avg'=>50,'max_avg'=>59,'remark'=>'Below average performance requiring additional support.'],
            ['type'=>'teacher','min_avg'=>50,'max_avg'=>59,'remark'=>'Struggles with some concepts and needs extra help.'],
            ['type'=>'teacher','min_avg'=>50,'max_avg'=>59,'remark'=>'Shows effort but lacks consistent understanding.'],
            ['type'=>'teacher','min_avg'=>50,'max_avg'=>59,'remark'=>'Needs to work harder to meet academic expectations.'],
            ['type'=>'head','min_avg'=>50,'max_avg'=>59,'remark'=>'Performance is below par and requires improvement.'],
            ['type'=>'head','min_avg'=>50,'max_avg'=>59,'remark'=>'Additional effort and focus needed to succeed.'],
            ['type'=>'head','min_avg'=>50,'max_avg'=>59,'remark'=>'Struggling academically, please seek assistance.'],
            ['type'=>'head','min_avg'=>50,'max_avg'=>59,'remark'=>'Must improve significantly to meet standards.'],

            // Poor Performance (0-49%)
            ['type'=>'teacher','min_avg'=>0,'max_avg'=>49,'remark'=>'Poor performance indicating serious academic difficulties.'],
            ['type'=>'teacher','min_avg'=>0,'max_avg'=>49,'remark'=>'Significant improvement needed in all subjects.'],
            ['type'=>'teacher','min_avg'=>0,'max_avg'=>49,'remark'=>'Lacks basic understanding and requires intensive support.'],
            ['type'=>'teacher','min_avg'=>0,'max_avg'=>49,'remark'=>'Failing to meet minimum requirements, urgent action needed.'],
            ['type'=>'head','min_avg'=>0,'max_avg'=>49,'remark'=>'Extremely poor results requiring immediate intervention.'],
            ['type'=>'head','min_avg'=>0,'max_avg'=>49,'remark'=>'Critical academic situation needing urgent attention.'],
            ['type'=>'head','min_avg'=>0,'max_avg'=>49,'remark'=>'Severe underperformance, comprehensive support required.'],
            ['type'=>'head','min_avg'=>0,'max_avg'=>49,'remark'=>'Failing across subjects, drastic improvement essential.'],

            // Position-based remarks (Top 3 positions)
            ['type'=>'teacher','min_avg'=>0,'max_avg'=>100,'min_position'=>1,'max_position'=>1,'remark'=>'First position in class - Excellent leadership and academic prowess!'],
            ['type'=>'teacher','min_avg'=>0,'max_avg'=>100,'min_position'=>2,'max_position'=>2,'remark'=>'Second position - Outstanding performance and close to perfection!'],
            ['type'=>'teacher','min_avg'=>0,'max_avg'=>100,'min_position'=>3,'max_position'=>3,'remark'=>'Third position - Remarkable achievement and strong academic standing!'],
            ['type'=>'head','min_avg'=>0,'max_avg'=>100,'min_position'=>1,'max_position'=>1,'remark'=>'Class champion - A true academic leader and inspiration to others!'],
            ['type'=>'head','min_avg'=>0,'max_avg'=>100,'min_position'=>2,'max_position'=>2,'remark'=>'Runner-up position - Exceptional talent and dedication shown!'],
            ['type'=>'head','min_avg'=>0,'max_avg'=>100,'min_position'=>3,'max_position'=>3,'remark'=>'Third place - Impressive results and potential for greatness!'],

            // Additional varied remarks
            ['type'=>'teacher','min_avg'=>70,'max_avg'=>100,'remark'=>'Shows excellent analytical skills and problem-solving abilities.'],
            ['type'=>'teacher','min_avg'=>70,'max_avg'=>100,'remark'=>'Demonstrates creativity and innovative thinking in assignments.'],
            ['type'=>'teacher','min_avg'=>50,'max_avg'=>69,'remark'=>'Participates actively in class discussions and group work.'],
            ['type'=>'teacher','min_avg'=>50,'max_avg'=>69,'remark'=>'Shows improvement in areas of difficulty with extra effort.'],
            ['type'=>'head','min_avg'=>70,'max_avg'=>100,'remark'=>'Exhibits leadership qualities and helps fellow students.'],
            ['type'=>'head','min_avg'=>70,'max_avg'=>100,'remark'=>'Maintains excellent behavior and discipline alongside academics.'],
            ['type'=>'head','min_avg'=>50,'max_avg'=>69,'remark'=>'Shows determination and perseverance in challenging subjects.'],
            ['type'=>'head','min_avg'=>50,'max_avg'=>69,'remark'=>'Makes good progress with consistent attendance and focus.'],
        ];

        foreach ($templates as $t) {
            RemarkTemplate::firstOrCreate(
                ['type'=>$t['type'],'min_avg'=>$t['min_avg'],'max_avg'=>$t['max_avg'],'min_position'=>$t['min_position'] ?? null,'max_position'=>$t['max_position'] ?? null],
                $t
            );
        }
    }
}