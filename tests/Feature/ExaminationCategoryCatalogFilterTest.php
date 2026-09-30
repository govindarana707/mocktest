<?php

use App\Models\Examination;
use App\Models\ExaminationCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('students can combine active category and subject filters for available examinations', function () {
    $student = User::factory()->student()->create();
    $academic = ExaminationCategory::factory()->create(['name' => 'Academic', 'slug' => 'academic']);
    $practice = ExaminationCategory::factory()->create(['name' => 'Practice Test', 'slug' => 'practice-test']);
    [$mathematics, $science] = Subject::factory()->count(2)->create();

    Examination::factory()->published()->create([
        'title' => 'Academic Mathematics',
        'subject_id' => $mathematics->id,
        'category_id' => $academic->id,
        'starts_at' => now()->subHour(),
    ]);
    Examination::factory()->published()->create([
        'title' => 'Academic Science',
        'subject_id' => $science->id,
        'category_id' => $academic->id,
        'starts_at' => now()->subHour(),
    ]);
    Examination::factory()->published()->create([
        'title' => 'Practice Science',
        'subject_id' => $science->id,
        'category_id' => $practice->id,
        'starts_at' => now()->subHour(),
    ]);

    $this->actingAs($student)->get(route('student.exams.index', [
        'subject_id' => $science->id,
        'category' => $academic->slug,
    ]))->assertOk()
        ->assertSee('Academic Science')
        ->assertDontSee('Academic Mathematics')
        ->assertDontSee('Practice Science');
});
