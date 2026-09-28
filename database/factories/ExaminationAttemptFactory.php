<?php

namespace Database\Factories;

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExaminationAttempt>
 */
class ExaminationAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now();

        return ['examination_id' => Examination::factory(), 'student_id' => User::factory()->student(), 'started_at' => $startedAt, 'expires_at' => $startedAt->copy()->addHour(), 'submitted_at' => null, 'submission_reason' => null];
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['submitted_at' => now(), 'submission_reason' => 'manual']);
    }
}
