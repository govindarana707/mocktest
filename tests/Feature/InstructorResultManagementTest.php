<?php

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\ExaminationResult;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function instructorResultFor(User $student, Examination $examination, bool $passed = true): ExaminationResult
{
    $attempt = ExaminationAttempt::factory()->submitted()->create([
        'student_id' => $student->id,
        'examination_id' => $examination->id,
        'started_at' => now()->subMinutes(30),
        'passing_percentage_snapshot' => 60,
    ]);

    return ExaminationResult::create([
        'examination_attempt_id' => $attempt->id,
        'total_questions' => 2,
        'answered_questions' => 2,
        'correct_answers' => $passed ? 2 : 0,
        'incorrect_answers' => $passed ? 0 : 2,
        'unanswered_questions' => 0,
        'maximum_marks' => 10,
        'obtained_marks' => $passed ? 8 : 2,
        'percentage' => $passed ? 80 : 20,
        'passing_percentage' => 60,
        'passed' => $passed,
        'graded_at' => now(),
    ]);
}

test('an instructor sees only results for examinations they own and scoped filter options', function () {
    [$instructor, $otherInstructor] = User::factory()->instructor()->count(2)->create();
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create(['name' => 'DBMS']);
    $ownedExamination = Examination::factory()->create(['created_by' => $instructor->id, 'subject_id' => $subject->id, 'title' => 'Instructor A DBMS']);
    $otherExamination = Examination::factory()->create(['created_by' => $otherInstructor->id, 'subject_id' => $subject->id, 'title' => 'Instructor B DBMS']);
    $adminExamination = Examination::factory()->create(['created_by' => $admin->id, 'subject_id' => $subject->id, 'title' => 'Admin DBMS']);
    $legacyExamination = Examination::factory()->create(['created_by' => null, 'subject_id' => $subject->id, 'title' => 'Legacy DBMS']);
    $ownedStudent = User::factory()->student()->create(['name' => 'Owned Learner']);

    instructorResultFor($ownedStudent, $ownedExamination);
    instructorResultFor(User::factory()->student()->create(['name' => 'Other Instructor Learner']), $otherExamination);
    instructorResultFor(User::factory()->student()->create(['name' => 'Admin Learner']), $adminExamination);
    instructorResultFor(User::factory()->student()->create(['name' => 'Legacy Learner']), $legacyExamination);

    $this->actingAs($instructor)->get(route('instructor.results.index'))
        ->assertOk()
        ->assertSee('Owned Learner')
        ->assertSee('Instructor A DBMS')
        ->assertDontSee('Other Instructor Learner')
        ->assertDontSee('Admin Learner')
        ->assertDontSee('Legacy Learner')
        ->assertDontSee('Instructor B DBMS')
        ->assertDontSee('Admin DBMS')
        ->assertDontSee('Legacy DBMS');
});

test('instructor result filters and pagination remain within examination ownership', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    [$ownedSubject, $otherSubject] = Subject::factory()->count(2)->create();
    $ownedExamination = Examination::factory()->create(['created_by' => $instructor->id, 'subject_id' => $ownedSubject->id, 'title' => 'Owned Filter Examination']);
    $otherExamination = Examination::factory()->create(['created_by' => $otherInstructor->id, 'subject_id' => $otherSubject->id, 'title' => 'Other Filter Examination']);
    $matched = User::factory()->student()->create(['name' => 'Matched Result Student']);

    instructorResultFor($matched, $ownedExamination, true);
    instructorResultFor(User::factory()->student()->create(['name' => 'Owned Failed Student']), $ownedExamination, false);
    instructorResultFor(User::factory()->student()->create(['name' => 'Other Result Student']), $otherExamination, true);

    $this->actingAs($instructor)->get(route('instructor.results.index', [
        'search' => 'Matched Result',
        'examination_id' => $ownedExamination->id,
        'subject_id' => $ownedSubject->id,
        'passed' => '1',
    ]))->assertOk()
        ->assertSee('Matched Result Student')
        ->assertDontSee('Owned Failed Student')
        ->assertDontSee('Other Result Student');

    $this->actingAs($instructor)->get(route('instructor.results.index', [
        'examination_id' => $otherExamination->id,
        'subject_id' => $otherSubject->id,
    ]))->assertOk()
        ->assertSee('No finalized results yet')
        ->assertDontSee('Other Result Student');

    User::factory()->student()->count(16)->create(['name' => 'Paginated Owned Student'])->each(fn (User $student) => instructorResultFor($student, $ownedExamination));

    $this->actingAs($instructor)->get(route('instructor.results.index', [
        'search' => 'Paginated Owned Student',
        'examination_id' => $ownedExamination->id,
        'subject_id' => $ownedSubject->id,
        'passed' => '1',
    ]))->assertOk()
        ->assertSee('page=2', false)
        ->assertSee('search=Paginated', false)
        ->assertSee('examination_id='.$ownedExamination->id, false)
        ->assertSee('subject_id='.$ownedSubject->id, false)
        ->assertSee('passed=1', false);
});

test('an instructor can view an owned historical result after subject unassignment', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['created_by' => $instructor->id, 'subject_id' => $subject->id]);
    $result = instructorResultFor(User::factory()->student()->create(['name' => 'Historical Learner']), $examination);

    $instructor->subjects()->detach($subject);

    $this->actingAs($instructor)->get(route('instructor.results.index'))
        ->assertOk()
        ->assertSee('Historical Learner');
    $this->actingAs($instructor)->get(route('instructor.results.show', $result))
        ->assertOk()
        ->assertSee('Historical Learner')
        ->assertSee('read-only');
});

test('instructor result detail denies other instructor admin and legacy examination results', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $otherResult = instructorResultFor(User::factory()->student()->create(), Examination::factory()->create(['created_by' => $otherInstructor->id, 'subject_id' => $subject->id]));
    $adminResult = instructorResultFor(User::factory()->student()->create(), Examination::factory()->create(['created_by' => $admin->id, 'subject_id' => $subject->id]));
    $legacyResult = instructorResultFor(User::factory()->student()->create(), Examination::factory()->create(['created_by' => null, 'subject_id' => $subject->id]));

    foreach ([$otherResult, $adminResult, $legacyResult] as $result) {
        $this->actingAs($instructor)->get(route('instructor.results.show', $result))->assertNotFound();
    }
});

test('instructor result routes enforce authentication and strict role separation', function (string $role) {
    $result = instructorResultFor(User::factory()->student()->create(), Examination::factory()->create());

    if ($role === 'guest') {
        $this->get(route('instructor.results.index'))->assertRedirect(route('instructor.login'));
        $this->get(route('instructor.results.show', $result))->assertRedirect(route('instructor.login'));

        return;
    }

    $user = User::factory()->{$role}()->create();
    $this->actingAs($user)->get(route('instructor.results.index'))->assertForbidden();
    $this->actingAs($user)->get(route('instructor.results.show', $result))->assertForbidden();
})->with(['guest', 'student', 'admin']);

test('instructor result detail uses persisted results without exposing personal or question data', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create(['name' => 'Result Learner', 'email' => 'private@example.test', 'phone' => '9999999999']);
    $examination = Examination::factory()->create(['created_by' => $instructor->id, 'allow_answer_review' => false, 'passing_percentage' => 60]);
    $question = Question::factory()->create(['subject_id' => $examination->subject_id, 'question_text' => 'Mutable source question']);
    $result = instructorResultFor($student, $examination, true);

    $question->update(['question_text' => 'Changed mutable source question']);
    $examination->update(['passing_percentage' => 95, 'allow_answer_review' => true]);

    $this->actingAs($instructor)->get(route('instructor.results.show', $result))
        ->assertOk()
        ->assertSee('Result Learner')
        ->assertSee('8.00 / 10.00')
        ->assertSee('80.00%')
        ->assertSee('Pass')
        ->assertDontSee('private@example.test')
        ->assertDontSee('9999999999')
        ->assertDontSee('Mutable source question')
        ->assertDontSee('Changed mutable source question');

    expect($result->fresh()->obtained_marks)->toBe('8.00')
        ->and($result->fresh()->maximum_marks)->toBe('10.00')
        ->and($result->fresh()->percentage)->toBe('80.00')
        ->and($result->fresh()->passed)->toBeTrue();
});

test('instructor results are read only and the zero result state is useful', function () {
    $instructor = User::factory()->instructor()->create();
    $examination = Examination::factory()->create(['created_by' => $instructor->id]);
    $result = instructorResultFor(User::factory()->student()->create(), $examination);

    $this->actingAs($instructor)->post(route('instructor.results.show', $result))->assertMethodNotAllowed();
    $this->actingAs($instructor)->put(route('instructor.results.show', $result))->assertMethodNotAllowed();
    $this->actingAs($instructor)->delete(route('instructor.results.show', $result))->assertMethodNotAllowed();

    $emptyInstructor = User::factory()->instructor()->create();
    Examination::factory()->create(['created_by' => $emptyInstructor->id]);

    $this->actingAs($emptyInstructor)->get(route('instructor.results.index'))
        ->assertOk()
        ->assertSee('No finalized results yet')
        ->assertSee(route('instructor.examinations.index'));
});

test('instructor dashboard reports persisted result totals for owned examinations only', function () {
    $instructor = User::factory()->instructor()->create(['name' => 'Results Instructor']);
    $otherInstructor = User::factory()->instructor()->create();
    $ownedExamination = Examination::factory()->create(['created_by' => $instructor->id]);
    $otherExamination = Examination::factory()->create(['created_by' => $otherInstructor->id]);

    instructorResultFor(User::factory()->student()->create(), $ownedExamination, true);
    instructorResultFor(User::factory()->student()->create(), $ownedExamination, false);
    instructorResultFor(User::factory()->student()->create(), $otherExamination, true);

    $this->actingAs($instructor)->get(route('instructor.dashboard'))
        ->assertOk()
        ->assertSee('2 total')
        ->assertSee('1 passed · 1 failed')
        ->assertSee(route('instructor.results.index'));
});
