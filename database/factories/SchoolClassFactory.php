<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition()
    {
        return [
            'name' => $this->faker->randomElement(['Primary 1', 'Primary 2', 'JSS1A', 'SS2B']),
            'level' => $this->faker->randomElement(['Primary', 'JSS', 'SSS']),
            'section' => $this->faker->randomElement(['A', 'B', 'C']),
            'school_id' => 1, // adjust or use School::factory() if available
        ];
    }
}
