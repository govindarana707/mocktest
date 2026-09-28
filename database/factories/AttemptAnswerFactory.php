<?php

namespace Database\Factories;

use App\Models\AttemptAnswer;
use App\Models\ExaminationAttempt;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptAnswer>
 */
class AttemptAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['examination_attempt_id' => ExaminationAttempt::factory(), 'question_id' => Question::factory(), 'selected_option' => fake()->randomElement(['a', 'b', 'c', 'd']), 'version' => 1];
    }
}
