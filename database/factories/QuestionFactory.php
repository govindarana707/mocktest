<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'question_text' => fake()->sentence(12).'?',
            'option_a' => fake()->sentence(3),
            'option_b' => fake()->sentence(3),
            'option_c' => fake()->sentence(3),
            'option_d' => fake()->sentence(3),
            'correct_option' => fake()->randomElement(['a', 'b', 'c', 'd']),
            'explanation' => fake()->optional()->sentence(),
        ];
    }
}
