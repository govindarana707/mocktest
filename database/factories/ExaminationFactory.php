<?php

namespace Database\Factories;

use App\ExaminationStatus;
use App\Models\Examination;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Examination>
 */
class ExaminationFactory extends Factory
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
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'duration_minutes' => fake()->numberBetween(30, 180),
            'passing_percentage' => fake()->numberBetween(40, 80),
            'status' => ExaminationStatus::Draft,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExaminationStatus::Published,
        ]);
    }
}
