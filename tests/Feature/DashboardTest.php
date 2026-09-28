<?php

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\ExaminationResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin dashboard displays real account statistics', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->student()->count(3)->create();

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()->assertSee('Students')->assertSee('3')->assertSee('Administrators')->assertSee('1');
});

test('student dashboard shows profile completion and result metrics', function () {
    $student = User::factory()->student()->create(['phone' => null, 'date_of_birth' => null, 'bio' => null]);

    $this->actingAs($student)->get(route('student.dashboard'))
        ->assertOk()->assertSee('40%')->assertSee('Exams Completed')->assertSee('Average Percentage');
});

test('student results page remains available with an empty state', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('student.results.index'))
        ->assertOk()->assertSee('No finalized results yet.');
});

function dashboardResult(User $student, bool $passed, float $percentage): ExaminationResult
{
    $attempt = ExaminationAttempt::factory()->submitted()->create(['student_id' => $student->id, 'examination_id' => Examination::factory()->create(['title' => $passed ? 'Passed Examination' : 'Failed Examination'])->id]);

    return ExaminationResult::create(['examination_attempt_id' => $attempt->id, 'total_questions' => 2, 'answered_questions' => 2, 'correct_answers' => $passed ? 2 : 0, 'incorrect_answers' => $passed ? 0 : 2, 'unanswered_questions' => 0, 'maximum_marks' => 10, 'obtained_marks' => $passed ? 8 : 4, 'percentage' => $percentage, 'passing_percentage' => 60, 'passed' => $passed, 'graded_at' => now()]);
}

test('student dashboard renders result metrics and recent results', function () {
    $student = User::factory()->student()->create();
    dashboardResult($student, true, 80);
    dashboardResult($student, false, 40);

    $this->actingAs($student)->get(route('student.dashboard'))->assertOk()->assertSee('Exams Completed')->assertSee('Average Percentage')->assertSee('60.0%')->assertSee('Passed Examination')->assertSee('Failed Examination');
});

test('dashboards render result zero states and admin result metrics', function () {
    $student = User::factory()->student()->create();
    $admin = User::factory()->admin()->create();
    dashboardResult($student, true, 80);
    dashboardResult($student, false, 40);

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Total Results')->assertSee('Pass Rate')->assertSee('50.0%')->assertSee('Passed Examination');
    $emptyStudent = User::factory()->student()->create();
    $this->actingAs($emptyStudent)->get(route('student.dashboard'))->assertOk()->assertSee('No results yet');
});
