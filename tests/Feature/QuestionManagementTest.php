<?php

use App\Models\Examination;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can create a four-option MCQ with one correct answer', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)->post(route('admin.questions.store'), [
        'subject_id' => $subject->id, 'question_text' => 'What is 2 + 2?',
        'option_a' => '3', 'option_b' => '4', 'option_c' => '5', 'option_d' => '6',
        'correct_option' => 'b', 'explanation' => 'Two plus two equals four.',
    ])->assertRedirect(route('admin.questions.index'));

    $this->assertDatabaseHas('questions', ['subject_id' => $subject->id, 'created_by' => $admin->id, 'correct_option' => 'b', 'option_d' => '6']);
});

test('question creation rejects an invalid correct option', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)->post(route('admin.questions.store'), [
        'subject_id' => $subject->id, 'question_text' => '',
        'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D', 'correct_option' => 'e',
    ])->assertSessionHasErrors(['question_text', 'correct_option']);
});

test('questions assigned to examinations cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create();
    $examination = Examination::factory()->create(['subject_id' => $question->subject_id]);
    $examination->questions()->attach($question, ['position' => 1]);

    $this->actingAs($admin)->delete(route('admin.questions.destroy', $question))->assertSessionHas('error');
    $this->assertModelExists($question);
});

test('administrators can delete an unreferenced question', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create();

    $this->actingAs($admin)->delete(route('admin.questions.destroy', $question))
        ->assertSessionHas('success');

    $this->assertModelMissing($question);
});

test('admin question search subject filtering and pagination preserve active filters', function () {
    $admin = User::factory()->admin()->create();
    [$targetSubject, $otherSubject] = Subject::factory()->count(2)->create();
    Question::factory()->count(13)->create([
        'subject_id' => $targetSubject->id,
        'question_text' => 'Searchable admin question',
    ]);
    Question::factory()->create([
        'subject_id' => $otherSubject->id,
        'question_text' => 'Unrelated admin question',
    ]);

    $this->actingAs($admin)->get(route('admin.questions.index', [
        'search' => 'Searchable admin',
        'subject_id' => $targetSubject->id,
    ]))->assertOk()
        ->assertSee('Searchable admin question')
        ->assertDontSee('Unrelated admin question')
        ->assertSee('search=Searchable%20admin', false)
        ->assertSee('subject_id='.$targetSubject->id, false);
});
