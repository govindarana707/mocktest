<?php

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function publishedExamForAttemptTests(int $questionCount = 2): Examination
{
    $examination = Examination::factory()->published()->create(['duration_minutes' => 30]);
    $questions = Question::factory()->count($questionCount)->create(['subject_id' => $examination->subject_id]);
    $examination->questions()->attach($questions->mapWithKeys(fn ($question, int $index): array => [$question->id => ['position' => $index + 1]])->all());

    return $examination;
}

test('a student starts exactly one snapshot attempt without correct-answer content', function () {
    $student = User::factory()->student()->create();
    $examination = publishedExamForAttemptTests();

    $this->actingAs($student)->post(route('student.attempts.start', $examination))->assertRedirect();
    $this->actingAs($student)->post(route('student.attempts.start', $examination))->assertRedirect();

    $attempt = ExaminationAttempt::firstOrFail();
    expect(ExaminationAttempt::count())->toBe(1);
    $this->assertDatabaseHas('examination_attempt_question', ['examination_attempt_id' => $attempt->id, 'position' => 1]);
    $this->actingAs($student)->get(route('student.attempts.show', $attempt))->assertOk()->assertDontSee('correct_option')->assertDontSee('explanation');
});

test('answers are saved with stale-request protection and restored after refresh', function () {
    $student = User::factory()->student()->create();
    $examination = publishedExamForAttemptTests();
    $this->actingAs($student)->post(route('student.attempts.start', $examination));
    $attempt = ExaminationAttempt::firstOrFail();
    $question = $examination->questions()->firstOrFail();

    $this->actingAs($student)->putJson(route('student.attempts.answers.update', [$attempt, $question]), ['selected_option' => 'b', 'version' => 0])->assertOk()->assertJsonPath('version', 1);
    $this->actingAs($student)->putJson(route('student.attempts.answers.update', [$attempt, $question]), ['selected_option' => 'c', 'version' => 0])->assertConflict()->assertJsonPath('selected_option', 'b');
    $this->assertDatabaseHas('attempt_answers', ['examination_attempt_id' => $attempt->id, 'question_id' => $question->id, 'selected_option' => 'b', 'version' => 1]);
    $this->actingAs($student)->get(route('student.attempts.show', $attempt))->assertOk()->assertSee('value="b"', false);
});

test('submission is idempotent and prevents further edits', function () {
    $student = User::factory()->student()->create();
    $examination = publishedExamForAttemptTests();
    $this->actingAs($student)->post(route('student.attempts.start', $examination));
    $attempt = ExaminationAttempt::firstOrFail();
    $question = $examination->questions()->firstOrFail();

    $this->actingAs($student)->postJson(route('student.attempts.submit', $attempt))->assertOk()->assertJsonPath('reason', 'manual');
    $submittedAt = $attempt->fresh()->submitted_at;
    $this->actingAs($student)->postJson(route('student.attempts.submit', $attempt))->assertOk();
    expect($attempt->fresh()->submitted_at->equalTo($submittedAt))->toBeTrue();
    $this->actingAs($student)->putJson(route('student.attempts.answers.update', [$attempt, $question]), ['selected_option' => 'a', 'version' => 0])->assertConflict();
});

test('expired attempts submit on the server and students cannot access another attempt', function () {
    $owner = User::factory()->student()->create();
    $intruder = User::factory()->student()->create();
    $examination = publishedExamForAttemptTests();
    $attempt = ExaminationAttempt::factory()->create(['examination_id' => $examination->id, 'student_id' => $owner->id, 'expires_at' => now()->subSecond()]);
    $attempt->questions()->attach($examination->questions()->pluck('questions.id')->mapWithKeys(fn ($id, int $index): array => [$id => ['position' => $index + 1]])->all());

    $this->actingAs($intruder)->get(route('student.attempts.show', $attempt))->assertNotFound();
    $this->actingAs($owner)->get(route('student.attempts.show', $attempt))->assertRedirect(route('student.my-exams.index'));
    $this->assertDatabaseHas('examination_attempts', ['id' => $attempt->id, 'submission_reason' => 'timeout']);
});

test('draft and empty examinations never start and admin access remains forbidden', function () {
    $student = User::factory()->student()->create();
    $admin = User::factory()->admin()->create();
    $draft = Examination::factory()->create();
    $emptyPublished = Examination::factory()->published()->create();

    $this->actingAs($student)->post(route('student.attempts.start', $draft))->assertSessionHasErrors('examination');
    $this->actingAs($student)->post(route('student.attempts.start', $emptyPublished))->assertSessionHasErrors('examination');
    $this->actingAs($admin)->get(route('student.exams.index'))->assertForbidden();
});
