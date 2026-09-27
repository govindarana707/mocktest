<?php

use App\ExaminationStatus;
use App\Models\Examination;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can create a scheduled draft examination', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)->post(route('admin.examinations.store'), [
        'subject_id' => $subject->id, 'title' => 'Algebra readiness', 'description' => 'Practice algebra.',
        'duration_minutes' => 45, 'passing_percentage' => 60, 'status' => 'draft',
        'starts_at' => '2026-10-01 09:00:00', 'ends_at' => '2026-10-01 10:00:00',
    ])->assertRedirect();

    $this->assertDatabaseHas('examinations', ['title' => 'Algebra readiness', 'status' => ExaminationStatus::Draft->value]);
});

test('administrators assign matching subject questions through the JSON endpoint', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $examination = Examination::factory()->create(['subject_id' => $subject->id]);
    $questions = Question::factory()->count(2)->create(['subject_id' => $subject->id]);

    $this->actingAs($admin)->putJson(route('admin.examinations.questions.update', $examination), [
        'question_ids' => $questions->pluck('id')->all(),
    ])->assertOk()->assertJsonPath('question_count', 2);

    $this->assertDatabaseHas('examination_question', ['examination_id' => $examination->id, 'question_id' => $questions->first()->id, 'position' => 1]);
});

test('question assignment rejects questions from another subject', function () {
    $admin = User::factory()->admin()->create();
    $examination = Examination::factory()->create();
    $otherQuestion = Question::factory()->create();

    $this->actingAs($admin)->putJson(route('admin.examinations.questions.update', $examination), [
        'question_ids' => [$otherQuestion->id],
    ])->assertUnprocessable()->assertJsonValidationErrors('question_ids');
});

test('an examination needs questions before it can be published', function () {
    $admin = User::factory()->admin()->create();
    $examination = Examination::factory()->create();

    $this->actingAs($admin)->put(route('admin.examinations.update', $examination), [
        'subject_id' => $examination->subject_id, 'title' => $examination->title, 'duration_minutes' => 30,
        'passing_percentage' => 60, 'status' => 'published', 'starts_at' => null, 'ends_at' => null,
    ])->assertSessionHasErrors('status');
});

test('deleting an examination removes assignments but preserves questions', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create();
    $examination = Examination::factory()->create(['subject_id' => $question->subject_id]);
    $examination->questions()->attach($question, ['position' => 1]);

    $this->actingAs($admin)->delete(route('admin.examinations.destroy', $examination))
        ->assertRedirect(route('admin.examinations.index'));
    $this->assertModelMissing($examination);
    $this->assertModelExists($question);
    $this->assertDatabaseMissing('examination_question', ['examination_id' => $examination->id]);
});
