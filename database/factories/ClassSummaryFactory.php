<?php

namespace Database\Factories;

use App\Models\ClassSummary;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\Session;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassSummaryFactory extends Factory
{
    protected $model = ClassSummary::class;

    public function definition()
    {
        return [
            'school_class_id' => SchoolClass::factory(),
            'term_id' => Term::factory(),
            'session_id' => Session::factory(),
            'subject_id' => Subject::factory(),
            'average' => $this->faker->randomFloat(2, 40, 90),
            'highest' => $this->faker->randomFloat(2, 50, 100),
            'lowest' => $this->faker->randomFloat(2, 10, 50),
            'computed_at' => now(),
        ];
    }
}
