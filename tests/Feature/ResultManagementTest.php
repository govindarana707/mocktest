<?php

use App\GradeExaminationAttempt;
use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function attemptForResult(object $tester, User $student, array $answers = [], int $passing = 50): ExaminationAttempt
{
    $exam = Examination::factory()->published()->create(['passing_percentage' => $passing, 'allow_answer_review' => true]);
    $questions = Question::factory()->count(2)->create(['subject_id' => $exam->subject_id, 'marks' => 2]);
    $questions[0]->update(['correct_option' => 'a', 'marks' => 3, 'explanation' => 'First explanation']);
    $questions[1]->update(['correct_option' => 'b', 'marks' => 1]);
    $exam->questions()->attach($questions->mapWithKeys(fn ($q, $i) => [$q->id => ['position' => $i + 1]])->all());
    $tester->actingAs($student)->post(route('student.attempts.start', $exam));
    $attempt = ExaminationAttempt::firstOrFail();
    foreach ($answers as $questionIndex => $option) {
        $tester->actingAs($student)->putJson(route('student.attempts.answers.update', [$attempt, $questions[$questionIndex]]), ['selected_option' => $option, 'version' => 0]);
    }
    $tester->actingAs($student)->postJson(route('student.attempts.submit', $attempt));

    return $attempt->fresh();
}

test('grades weighted correct mixed and unanswered answers from immutable snapshots', function () {
    $student = User::factory()->student()->create();
    $attempt = attemptForResult($this, $student, [0 => 'a']);
    $result = $attempt->result;
    expect($result->total_questions)->toBe(2)->and($result->correct_answers)->toBe(1)->and($result->unanswered_questions)->toBe(1)->and($result->obtained_marks)->toBe('3.00')->and($result->maximum_marks)->toBe('4.00')->and($result->percentage)->toBe('75.00')->and($result->passed)->toBeTrue();
});

test('passing boundary passes and grading is idempotent', function () {
    $student = User::factory()->student()->create();
    $attempt = attemptForResult($this, $student, [1 => 'b'], 25);
    $first = $attempt->result;
    $second = app(GradeExaminationAttempt::class)->handle($attempt);
    expect($first->id)->toBe($second->id)->and($first->percentage)->toBe('25.00')->and($first->passed)->toBeTrue();
});

test('a failing result and student result isolation are enforced', function () {
    $owner = User::factory()->student()->create();
    $intruder = User::factory()->student()->create();
    $attempt = attemptForResult($this, $owner, [0 => 'b']);
    $result = $attempt->result;
    expect($result->passed)->toBeFalse()->and($result->incorrect_answers)->toBe(1);
    $this->actingAs($intruder)->get(route('student.results.show', $result))->assertNotFound();
    $this->actingAs($intruder)->get(route('student.results.review', $result))->assertNotFound();
});

test('review uses snapshots and is unavailable when disabled', function () {
    $student = User::factory()->student()->create();
    $attempt = attemptForResult($this, $student, [0 => 'a']);
    $result = $attempt->result;
    $question = $attempt->questions()->first();
    $question->update(['question_text' => 'Changed question', 'correct_option' => 'd']);
    $this->actingAs($student)->get(route('student.results.review', $result))->assertOk()->assertSee('First explanation')->assertDontSee('Changed question');
    $attempt->examination->update(['allow_answer_review' => false]);
    $this->actingAs($student)->get(route('student.results.review', $result))->assertNotFound();
});

test('admins can view results and active attempts never have a result', function () {
    $student = User::factory()->student()->create();
    $admin = User::factory()->admin()->create();
    $attempt = attemptForResult($this, $student, [0 => 'a']);
    $this->actingAs($admin)->get(route('admin.results.index'))->assertOk()->assertSee($student->name);
    $this->actingAs($admin)->get(route('admin.results.show', $attempt->result))->assertOk();
});
