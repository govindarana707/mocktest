<?php

use App\ExaminationStatus;
use App\Models\Examination;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('students only see currently available published examinations without answer content', function () {
    $student = User::factory()->student()->create();
    $available = Examination::factory()->published()->create(['title' => 'Visible exam', 'starts_at' => now()->subHour()]);
    $question = Question::factory()->create(['subject_id' => $available->subject_id, 'option_a' => 'Secret answer text']);
    $available->questions()->attach($question, ['position' => 1]);
    Examination::factory()->create(['title' => 'Draft exam', 'status' => ExaminationStatus::Draft]);
    Examination::factory()->published()->create(['title' => 'Future exam', 'starts_at' => now()->addHour()]);
    Examination::factory()->published()->create(['title' => 'Expired exam', 'ends_at' => now()->subHour()]);

    $this->actingAs($student)->get(route('student.exams.index'))
        ->assertOk()->assertSee('Visible exam')->assertDontSee('Draft exam')->assertDontSee('Future exam')
        ->assertDontSee('Expired exam')->assertDontSee('Secret answer text');
});

test('guests are redirected from the available examination catalog', function () {
    $this->get(route('student.exams.index'))->assertRedirect(route('login'));
});
