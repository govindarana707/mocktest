<?php

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\ExaminationResult;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function filteredResult(User $student, Examination $examination, bool $passed): ExaminationResult
{
    $attempt = ExaminationAttempt::factory()->submitted()->create(['student_id' => $student->id, 'examination_id' => $examination->id]);

    return ExaminationResult::create(['examination_attempt_id' => $attempt->id, 'total_questions' => 1, 'answered_questions' => 1, 'correct_answers' => $passed ? 1 : 0, 'incorrect_answers' => $passed ? 0 : 1, 'unanswered_questions' => 0, 'maximum_marks' => 10, 'obtained_marks' => $passed ? 8 : 2, 'percentage' => $passed ? 80 : 20, 'passing_percentage' => 60, 'passed' => $passed, 'graded_at' => now()]);
}

test('administrators filter results by student name and email', function () {
    $admin = User::factory()->admin()->create();
    $exam = Examination::factory()->create(['title' => 'Search Examination']);
    $nameStudent = User::factory()->student()->create(['name' => 'Matched Learner', 'email' => 'matched@example.test']);
    $emailStudent = User::factory()->student()->create(['name' => 'Other Learner', 'email' => 'email-match@example.test']);
    $other = User::factory()->student()->create(['name' => 'Unrelated Learner']);
    filteredResult($nameStudent, $exam, true);
    filteredResult($emailStudent, $exam, true);
    filteredResult($other, $exam, true);

    $this->actingAs($admin)->get(route('admin.results.index', ['search' => 'Matched Learner']))->assertOk()->assertSee('Matched Learner')->assertDontSee('Unrelated Learner');
    $this->actingAs($admin)->get(route('admin.results.index', ['search' => 'email-match@example.test']))->assertOk()->assertSee('Other Learner')->assertDontSee('Unrelated Learner');
});

test('administrators filter results by examination subject status and combined criteria', function () {
    $admin = User::factory()->admin()->create();
    $mathematics = Subject::factory()->create(['name' => 'Mathematics']);
    $science = Subject::factory()->create(['name' => 'Science']);
    $mathExam = Examination::factory()->create(['subject_id' => $mathematics->id, 'title' => 'Algebra Examination']);
    $scienceExam = Examination::factory()->create(['subject_id' => $science->id, 'title' => 'Physics Examination']);
    $matched = User::factory()->student()->create(['name' => 'Combined Student']);
    $other = User::factory()->student()->create(['name' => 'Other Student']);
    $scienceStudent = User::factory()->student()->create(['name' => 'Science Student']);
    filteredResult($matched, $mathExam, true);
    filteredResult($other, $mathExam, false);
    filteredResult($scienceStudent, $scienceExam, true);

    $this->actingAs($admin)->get(route('admin.results.index', ['examination_id' => $mathExam->id]))->assertOk()->assertSee('Combined Student')->assertDontSee('Science Student');
    $this->actingAs($admin)->get(route('admin.results.index', ['subject_id' => $science->id]))->assertOk()->assertSee('Science Student')->assertDontSee('Combined Student');
    $this->actingAs($admin)->get(route('admin.results.index', ['passed' => '0']))->assertOk()->assertSee('Other Student')->assertDontSee('Combined Student');
    $this->actingAs($admin)->get(route('admin.results.index', ['search' => 'Combined', 'examination_id' => $mathExam->id, 'subject_id' => $mathematics->id, 'passed' => '1']))->assertOk()->assertSee('Combined Student')->assertDontSee('Other Student')->assertSee('Apply Filters')->assertSee('Clear');
});

test('result pagination preserves active filter query parameters', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create(['name' => 'Pagination Subject']);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'title' => 'Pagination Examination']);

    User::factory()->student()->count(16)->create(['name' => 'Pagination Student'])->each(fn (User $student) => filteredResult($student, $examination, true));

    $response = $this->actingAs($admin)->get(route('admin.results.index', [
        'search' => 'Pagination Student',
        'examination_id' => $examination->id,
        'subject_id' => $subject->id,
        'passed' => '1',
    ]));

    $response->assertOk()
        ->assertSee('page=2', false)
        ->assertSee('search=Pagination', false)
        ->assertSee('examination_id='.$examination->id, false)
        ->assertSee('subject_id='.$subject->id, false)
        ->assertSee('passed=1', false);
});
