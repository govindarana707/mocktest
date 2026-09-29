<?php

use App\ExaminationLeaderboard;
use App\ExaminationStatus;
use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\ExaminationResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function rankedResult(Examination $exam, User $student, int $marks, int $seconds, string $gradedAt = '2026-01-01 10:00:00'): ExaminationResult
{
    $started = now()->subSeconds($seconds);
    $attempt = ExaminationAttempt::factory()->submitted()->create(['examination_id' => $exam->id, 'student_id' => $student->id, 'started_at' => $started, 'submitted_at' => now()]);

    return ExaminationResult::create(['examination_attempt_id' => $attempt->id, 'total_questions' => 1, 'answered_questions' => 1, 'correct_answers' => 1, 'incorrect_answers' => 0, 'unanswered_questions' => 0, 'maximum_marks' => 100, 'obtained_marks' => $marks, 'percentage' => $marks, 'passing_percentage' => 50, 'passed' => true, 'graded_at' => $gradedAt]);
}

test('orders immutable results by marks duration finalization and identifier using positional ranks', function () {
    $exam = Examination::factory()->published()->create();
    $slow = rankedResult($exam, User::factory()->student()->create(), 90, 600, '2026-01-01 10:02:00');
    $fast = rankedResult($exam, User::factory()->student()->create(), 90, 500, '2026-01-01 10:03:00');
    $lower = rankedResult($exam, User::factory()->student()->create(), 80, 400, '2026-01-01 10:01:00');

    $ranked = app(ExaminationLeaderboard::class)->resultsFor($exam);
    expect($ranked->pluck('id')->all())->toBe([$fast->id, $slow->id, $lower->id])->and($ranked->pluck('rank')->all())->toBe([1, 2, 3]);
});

test('keeps examinations independent and excludes incomplete attempts', function () {
    $exam = Examination::factory()->published()->create();
    $other = Examination::factory()->published()->create();
    $student = User::factory()->student()->create();
    $included = rankedResult($exam, $student, 70, 300);
    rankedResult($other, User::factory()->student()->create(), 100, 200);
    ExaminationAttempt::factory()->create(['examination_id' => $exam->id, 'student_id' => User::factory()->student()->create()->id]);

    expect(app(ExaminationLeaderboard::class)->resultsFor($exam)->pluck('id')->all())->toBe([$included->id]);
});

test('student and admin leaderboard routes enforce roles and protect private data', function () {
    $exam = Examination::factory()->published()->create();
    $student = User::factory()->student()->create(['email' => 'private@example.test', 'phone' => '999']);
    $admin = User::factory()->admin()->create();
    rankedResult($exam, $student, 75, 200);

    $this->get(route('student.exams.leaderboard', $exam))->assertRedirect(route('login'));
    $this->actingAs($student)->get(route('student.exams.leaderboard', $exam))->assertOk()->assertSee($student->name)->assertDontSee($student->email)->assertDontSee('correct_option');
    $this->actingAs($student)->get(route('admin.examinations.leaderboard', $exam))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.examinations.leaderboard', $exam))->assertOk()->assertSee($student->name);
});

test('shows own rank outside the top ten without fabricating ranks', function () {
    $exam = Examination::factory()->published()->create();
    $student = User::factory()->student()->create(['name' => 'Eleventh Student']);
    foreach (range(1, 10) as $rank) {
        rankedResult($exam, User::factory()->student()->create(), 100 - $rank, 100);
    }
    rankedResult($exam, $student, 1, 100);

    $this->actingAs($student)->get(route('student.exams.leaderboard', $exam))->assertOk()->assertSee('#11 of 11')->assertDontSee('Eleventh Student</td>');
    $nonParticipant = User::factory()->student()->create();
    $this->actingAs($nonParticipant)->get(route('student.exams.leaderboard', $exam))->assertOk()->assertDontSee('Your Rank');
});

test('student leaderboard visibility follows publication and start policy while admins can view all states', function () {
    $student = User::factory()->student()->create();
    $admin = User::factory()->admin()->create();
    $draft = Examination::factory()->create(['status' => ExaminationStatus::Draft]);
    $future = Examination::factory()->published()->create(['starts_at' => now()->addHour()]);
    $active = Examination::factory()->published()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
    $expired = Examination::factory()->published()->create(['starts_at' => now()->subDays(2), 'ends_at' => now()->subHour()]);

    $this->actingAs($student)->get(route('student.exams.leaderboard', $draft))->assertNotFound();
    $this->actingAs($student)->get(route('student.exams.leaderboard', $future))->assertNotFound();
    $this->actingAs($student)->get(route('student.exams.leaderboard', $active))->assertOk()->assertSee('No finalized results are available yet.');
    $this->actingAs($student)->get(route('student.exams.leaderboard', $expired))->assertOk();
    $this->actingAs($admin)->get(route('admin.examinations.leaderboard', $draft))->assertOk();
    $this->actingAs($admin)->get(route('admin.examinations.leaderboard', $future))->assertOk();
    $this->actingAs($admin)->get(route('admin.examinations.leaderboard', $active))->assertOk();
    $this->actingAs($admin)->get(route('admin.examinations.leaderboard', $expired))->assertOk();
});

test('leaderboard remains stable when mutable examination configuration changes', function () {
    $exam = Examination::factory()->published()->create(['passing_percentage' => 50]);
    $first = rankedResult($exam, User::factory()->student()->create(), 90, 300);
    $second = rankedResult($exam, User::factory()->student()->create(), 80, 200);
    $before = app(ExaminationLeaderboard::class)->resultsFor($exam)->pluck('id')->all();

    $exam->update(['passing_percentage' => 90, 'title' => 'Changed title']);
    $after = app(ExaminationLeaderboard::class)->resultsFor($exam)->pluck('id')->all();
    expect($after)->toBe($before)->and($first->fresh()->obtained_marks)->toBe('90.00')->and($second->fresh()->obtained_marks)->toBe('80.00');
});

test('leaderboard exposes positional top ten and participant totals for eleven results', function () {
    $exam = Examination::factory()->published()->create();
    $students = User::factory()->student()->count(11)->create();
    $students->each(function (User $student, int $index) use ($exam): void {
        rankedResult($exam, $student, 100 - $index, 300);
    });

    $ranked = app(ExaminationLeaderboard::class)->resultsFor($exam);
    expect($ranked->count())->toBe(11)->and($ranked->take(10)->pluck('rank')->all())->toBe(range(1, 10))->and($ranked->last()->rank)->toBe(11);
});

test('leaderboard handles zero through three participant shapes without placeholders', function () {
    $service = app(ExaminationLeaderboard::class);
    $emptyExam = Examination::factory()->published()->create();
    $oneExam = Examination::factory()->published()->create();
    $twoExam = Examination::factory()->published()->create();
    $threeExam = Examination::factory()->published()->create();

    rankedResult($oneExam, User::factory()->student()->create(), 90, 300);
    rankedResult($twoExam, User::factory()->student()->create(), 90, 300);
    rankedResult($twoExam, User::factory()->student()->create(), 80, 300);
    rankedResult($threeExam, User::factory()->student()->create(), 90, 300);
    rankedResult($threeExam, User::factory()->student()->create(), 80, 300);
    rankedResult($threeExam, User::factory()->student()->create(), 70, 300);

    expect($service->resultsFor($emptyExam))->toHaveCount(0)
        ->and($service->resultsFor($oneExam)->pluck('rank')->all())->toBe([1])
        ->and($service->resultsFor($twoExam)->pluck('rank')->all())->toBe([1, 2])
        ->and($service->resultsFor($threeExam)->take(3)->pluck('rank')->all())->toBe([1, 2, 3]);
});

test('admin examination list exposes an examination-specific leaderboard action', function () {
    $admin = User::factory()->admin()->create();
    $exam = Examination::factory()->create();

    $this->actingAs($admin)->get(route('admin.examinations.index'))->assertOk()->assertSee(route('admin.examinations.leaderboard', $exam), false)->assertSee('Leaderboard');
});

test('score ordering is explicit across three results', function () {
    $exam = Examination::factory()->published()->create();
    rankedResult($exam, User::factory()->student()->create(), 90, 300);
    rankedResult($exam, User::factory()->student()->create(), 80, 300);
    rankedResult($exam, User::factory()->student()->create(), 70, 300);

    expect(app(ExaminationLeaderboard::class)->resultsFor($exam)->pluck('obtained_marks')->map(fn ($marks) => (int) $marks)->all())->toBe([90, 80, 70]);
});

test('earlier completion ranks first when obtained marks match', function () {
    $exam = Examination::factory()->published()->create();
    $slow = rankedResult($exam, User::factory()->student()->create(), 90, 600);
    $fast = rankedResult($exam, User::factory()->student()->create(), 90, 500);

    expect(app(ExaminationLeaderboard::class)->resultsFor($exam)->pluck('id')->all())->toBe([$fast->id, $slow->id]);
});

test('earlier graded result wins equal score and completion ties', function () {
    $exam = Examination::factory()->published()->create();
    $later = rankedResult($exam, User::factory()->student()->create(), 90, 500, '2026-01-02 10:00:00');
    $earlier = rankedResult($exam, User::factory()->student()->create(), 90, 500, '2026-01-01 10:00:00');

    expect(app(ExaminationLeaderboard::class)->resultsFor($exam)->pluck('id')->all())->toBe([$earlier->id, $later->id]);
});

test('result id provides a stable final tie-break and positional ranks', function () {
    $exam = Examination::factory()->published()->create();
    $first = rankedResult($exam, User::factory()->student()->create(), 90, 500, '2026-01-01 10:00:00');
    $second = rankedResult($exam, User::factory()->student()->create(), 90, 500, '2026-01-01 10:00:00');
    $ranked = app(ExaminationLeaderboard::class)->resultsFor($exam);

    expect($ranked->pluck('id')->all())->toBe([$first->id, $second->id])->and($ranked->pluck('rank')->all())->toBe([1, 2]);
});

test('leaderboards isolate examinations', function () {
    $firstExam = Examination::factory()->published()->create();
    $secondExam = Examination::factory()->published()->create();
    $first = rankedResult($firstExam, User::factory()->student()->create(), 70, 300);
    rankedResult($secondExam, User::factory()->student()->create(), 100, 100);

    expect(app(ExaminationLeaderboard::class)->resultsFor($firstExam)->pluck('id')->all())->toBe([$first->id])->and(app(ExaminationLeaderboard::class)->resultsFor($secondExam)->count())->toBe(1);
});

test('only finalized attempts with valid timestamps and persisted results are eligible', function () {
    $exam = Examination::factory()->published()->create();
    $valid = rankedResult($exam, User::factory()->student()->create(), 80, 300);
    ExaminationAttempt::factory()->create(['examination_id' => $exam->id, 'student_id' => User::factory()->student()->create()->id, 'submitted_at' => null]);
    ExaminationAttempt::factory()->submitted()->create(['examination_id' => $exam->id, 'student_id' => User::factory()->student()->create()->id]);

    expect(app(ExaminationLeaderboard::class)->resultsFor($exam)->pluck('id')->all())->toBe([$valid->id]);
});
