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

    $this->assertDatabaseHas('questions', ['subject_id' => $subject->id, 'correct_option' => 'b', 'option_d' => '6']);
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
