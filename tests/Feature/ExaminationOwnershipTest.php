<?php

use App\Models\AttemptAnswer;
use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\ExaminationResult;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin examination creation assigns the authenticated admin and ignores submitted ownership', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)->post(route('admin.examinations.store'), ownershipExaminationPayload($subject, [
        'created_by' => $other->id,
        'creator_id' => $other->id,
        'owner_id' => $other->id,
        'user_id' => $other->id,
    ]))->assertRedirect();

    $examination = Examination::where('title', 'Ownership examination')->firstOrFail();

    expect($examination->created_by)->toBe($admin->id)
        ->and($examination->creator->is($admin))->toBeTrue();
});

test('admin metadata updates preserve instructor and legacy examination ownership', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $owned = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $legacy = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => null]);

    foreach ([$owned, $legacy] as $examination) {
        $this->actingAs($admin)->put(route('admin.examinations.update', $examination), ownershipExaminationPayload($subject, [
            'title' => 'Admin updated '.$examination->id,
            'created_by' => $admin->id,
        ]))->assertRedirect();
    }

    expect($owned->fresh()->created_by)->toBe($instructor->id)
        ->and($legacy->fresh()->created_by)->toBeNull();
});

test('admin examination listing renders creator roles and legacy ownership safely', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create(['name' => 'Visible Examination Author']);
    Examination::factory()->create(['created_by' => $instructor->id, 'title' => 'Instructor-owned examination']);
    Examination::factory()->create(['created_by' => null, 'title' => 'Historical legacy examination']);

    $this->actingAs($admin)->get(route('admin.examinations.index'))
        ->assertOk()
        ->assertSee('Instructor-owned examination')
        ->assertSee('Visible Examination Author')
        ->assertSee('Instructor')
        ->assertSee('Historical legacy examination')
        ->assertSee('Legacy / Former owner');
});

test('admin deletion of an instructor nulls examination ownership and preserves historical data', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    $question = Question::factory()->create();
    $examination = Examination::factory()->create([
        'subject_id' => $question->subject_id,
        'created_by' => $instructor->id,
    ]);
    $examination->questions()->attach($question, ['position' => 1]);
    $attempt = ExaminationAttempt::factory()->submitted()->create([
        'examination_id' => $examination->id,
        'student_id' => $student->id,
        'passing_percentage_snapshot' => 60,
    ]);
    $attempt->questions()->attach($question, [
        'position' => 1,
        'question_text_snapshot' => $question->question_text,
        'option_a_snapshot' => $question->option_a,
        'option_b_snapshot' => $question->option_b,
        'option_c_snapshot' => $question->option_c,
        'option_d_snapshot' => $question->option_d,
        'correct_option_snapshot' => $question->correct_option,
        'explanation_snapshot' => $question->explanation,
        'marks_snapshot' => $question->marks,
    ]);
    $answer = AttemptAnswer::factory()->create([
        'examination_attempt_id' => $attempt->id,
        'question_id' => $question->id,
    ]);
    $result = ExaminationResult::create([
        'examination_attempt_id' => $attempt->id,
        'total_questions' => 1,
        'answered_questions' => 1,
        'correct_answers' => 1,
        'incorrect_answers' => 0,
        'unanswered_questions' => 0,
        'maximum_marks' => 1,
        'obtained_marks' => 1,
        'percentage' => 100,
        'passing_percentage' => 60,
        'passed' => true,
        'graded_at' => now(),
    ]);

    $this->actingAs($admin)->delete(route('admin.instructors.destroy', $instructor))
        ->assertRedirect(route('admin.instructors.index'));

    $this->assertModelExists($examination);
    $this->assertDatabaseHas('examinations', ['id' => $examination->id, 'created_by' => null]);
    $this->assertDatabaseHas('examination_question', ['examination_id' => $examination->id, 'question_id' => $question->id]);
    $this->assertModelExists($attempt);
    $this->assertModelExists($answer);
    $this->assertModelExists($result);
});

test('demo content creates one deterministic instructor-owned examination without changing the legacy demo examination', function () {
    $this->seed(DemoAccountSeeder::class);
    $this->seed(DemoContentSeeder::class);
    $this->seed(DemoContentSeeder::class);

    $instructor = User::where('email', 'instructor@mocktest.test')->firstOrFail();

    expect(Examination::where('created_by', $instructor->id)->count())->toBe(1)
        ->and(Examination::whereNull('created_by')->count())->toBe(1);
    $this->assertDatabaseHas('examinations', [
        'created_by' => $instructor->id,
        'title' => 'Instructor Science Practice',
        'status' => 'draft',
    ]);
    $this->assertDatabaseHas('examinations', [
        'created_by' => null,
        'title' => 'Mathematics Foundations',
    ]);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ownershipExaminationPayload(Subject $subject, array $overrides = []): array
{
    return [...[
        'subject_id' => $subject->id,
        'title' => 'Ownership examination',
        'description' => 'Ownership is assigned by the server.',
        'duration_minutes' => 45,
        'passing_percentage' => 60,
        'status' => 'draft',
        'starts_at' => null,
        'ends_at' => null,
        'allow_answer_review' => false,
    ], ...$overrides];
}
