<?php

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\ExaminationResult;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function instructorRankedResult(Examination $examination, User $student, int $marks, int $seconds, bool $passed = true): ExaminationResult
{
    $attempt = ExaminationAttempt::factory()->submitted()->create([
        'examination_id' => $examination->id,
        'student_id' => $student->id,
        'started_at' => now()->subSeconds($seconds),
        'submitted_at' => now(),
        'passing_percentage_snapshot' => 60,
    ]);

    return ExaminationResult::create([
        'examination_attempt_id' => $attempt->id,
        'total_questions' => 1,
        'answered_questions' => 1,
        'correct_answers' => $passed ? 1 : 0,
        'incorrect_answers' => $passed ? 0 : 1,
        'unanswered_questions' => 0,
        'maximum_marks' => 100,
        'obtained_marks' => $marks,
        'percentage' => $marks,
        'passing_percentage' => 60,
        'passed' => $passed,
        'graded_at' => now(),
    ]);
}

test('an instructor leaderboard index lists only owned examinations and links to each leaderboard', function () {
    [$instructor, $otherInstructor] = User::factory()->instructor()->count(2)->create();
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create(['code' => 'DBMS']);
    $owned = Examination::factory()->create(['created_by' => $instructor->id, 'subject_id' => $subject->id, 'title' => 'Owned DBMS Examination']);
    $other = Examination::factory()->create(['created_by' => $otherInstructor->id, 'subject_id' => $subject->id, 'title' => 'Other DBMS Examination']);
    Examination::factory()->create(['created_by' => $admin->id, 'subject_id' => $subject->id, 'title' => 'Admin DBMS Examination']);
    Examination::factory()->create(['created_by' => null, 'subject_id' => $subject->id, 'title' => 'Legacy DBMS Examination']);

    instructorRankedResult($owned, User::factory()->student()->create(), 80, 300);

    $this->actingAs($instructor)->get(route('instructor.leaderboards.index'))
        ->assertOk()
        ->assertSee('Owned DBMS Examination')
        ->assertSee(route('instructor.examinations.leaderboard', $owned), false)
        ->assertSee('1 finalized')
        ->assertDontSee('Other DBMS Examination')
        ->assertDontSee('Admin DBMS Examination')
        ->assertDontSee('Legacy DBMS Examination');

    $this->actingAs($instructor)->get(route('instructor.examinations.leaderboard', $other))->assertNotFound();
});

test('an instructor sees service-provided positional rankings top three and completion time', function () {
    $instructor = User::factory()->instructor()->create();
    $examination = Examination::factory()->create(['created_by' => $instructor->id, 'title' => 'Ranked Instructor Examination']);
    $first = User::factory()->student()->create(['name' => 'First Performer']);
    $second = User::factory()->student()->create(['name' => 'Second Performer']);
    $third = User::factory()->student()->create(['name' => 'Third Performer']);

    instructorRankedResult($examination, $second, 90, 600, false);
    instructorRankedResult($examination, $first, 90, 300);
    instructorRankedResult($examination, $third, 80, 120);

    $this->actingAs($instructor)->get(route('instructor.examinations.leaderboard', $examination))
        ->assertOk()
        ->assertSee('3 participants')
        ->assertSee('First Performer')
        ->assertSee('Second Performer')
        ->assertSee('Third Performer')
        ->assertSee('#1')
        ->assertSee('#2')
        ->assertSee('#3')
        ->assertSee('5m 00s')
        ->assertSee('Pass')
        ->assertSee('Fail');
});

test('instructor leaderboard preserves historical access after subject unassignment', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['created_by' => $instructor->id, 'subject_id' => $subject->id]);
    instructorRankedResult($examination, User::factory()->student()->create(['name' => 'Historical Rank']), 80, 300);

    $instructor->subjects()->detach($subject);

    $this->actingAs($instructor)->get(route('instructor.examinations.leaderboard', $examination))
        ->assertOk()
        ->assertSee('Historical Rank')
        ->assertSee('#1');
});

test('instructor leaderboard denies admin legacy and other instructor examinations', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $other = Examination::factory()->create(['created_by' => $otherInstructor->id, 'subject_id' => $subject->id]);
    $adminExamination = Examination::factory()->create(['created_by' => $admin->id, 'subject_id' => $subject->id]);
    $legacy = Examination::factory()->create(['created_by' => null, 'subject_id' => $subject->id]);

    foreach ([$other, $adminExamination, $legacy] as $examination) {
        $this->actingAs($instructor)->get(route('instructor.examinations.leaderboard', $examination))->assertNotFound();
    }
});

test('instructor leaderboard routes require an authenticated instructor', function (string $role) {
    $examination = Examination::factory()->create();

    if ($role === 'guest') {
        $this->get(route('instructor.leaderboards.index'))->assertRedirect(route('instructor.login'));
        $this->get(route('instructor.examinations.leaderboard', $examination))->assertRedirect(route('instructor.login'));

        return;
    }

    $user = User::factory()->{$role}()->create();
    $this->actingAs($user)->get(route('instructor.leaderboards.index'))->assertForbidden();
    $this->actingAs($user)->get(route('instructor.examinations.leaderboard', $examination))->assertForbidden();
})->with(['guest', 'student', 'admin']);

test('an owned examination with no rankable results renders the empty leaderboard state', function () {
    $instructor = User::factory()->instructor()->create();
    $examination = Examination::factory()->create(['created_by' => $instructor->id]);

    $this->actingAs($instructor)->get(route('instructor.examinations.leaderboard', $examination))
        ->assertOk()
        ->assertSee('0 participants')
        ->assertSee('No finalized results are available yet.')
        ->assertSee(route('instructor.examinations.index'));
});

test('instructor leaderboard is stable and excludes private student and answer data', function () {
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create(['name' => 'Private Ranking Student', 'email' => 'private@example.test', 'phone' => '9999999999']);
    $examination = Examination::factory()->create(['created_by' => $instructor->id, 'passing_percentage' => 60]);
    $result = instructorRankedResult($examination, $student, 80, 300);

    $examination->update(['passing_percentage' => 95, 'title' => 'Changed Examination Configuration']);

    $this->actingAs($instructor)->get(route('instructor.examinations.leaderboard', $examination))
        ->assertOk()
        ->assertSee('Private Ranking Student')
        ->assertSee('80.00 / 100.00')
        ->assertSee('80.00%')
        ->assertDontSee('private@example.test')
        ->assertDontSee('9999999999')
        ->assertDontSee('selected_option')
        ->assertDontSee('correct_option');

    expect($result->fresh()->obtained_marks)->toBe('80.00')
        ->and($result->fresh()->percentage)->toBe('80.00')
        ->and($result->fresh()->passed)->toBeTrue();
});

test('leaderboard navigation is available from instructor examinations and result detail', function () {
    $instructor = User::factory()->instructor()->create();
    $examination = Examination::factory()->create(['created_by' => $instructor->id]);
    $result = instructorRankedResult($examination, User::factory()->student()->create(), 80, 300);

    $this->actingAs($instructor)->get(route('instructor.examinations.index'))
        ->assertOk()
        ->assertSee(route('instructor.examinations.leaderboard', $examination), false)
        ->assertSee('Leaderboard');
    $this->actingAs($instructor)->get(route('instructor.results.show', $result))
        ->assertOk()
        ->assertSee(route('instructor.examinations.leaderboard', $examination), false)
        ->assertSee('View Leaderboard');
});
