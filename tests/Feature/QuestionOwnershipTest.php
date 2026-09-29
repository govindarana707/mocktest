<?php

use App\Models\Examination;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin question creation assigns the authenticated admin and ignores submitted ownership', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)->post(route('admin.questions.store'), ownershipQuestionPayload($subject, [
        'created_by' => $other->id,
        'owner_id' => $other->id,
        'user_id' => $other->id,
    ]))->assertRedirect(route('admin.questions.index'));

    $question = Question::where('question_text', 'Who owns this question?')->firstOrFail();

    expect($question->created_by)->toBe($admin->id)
        ->and($question->creator->is($admin))->toBeTrue();
});

test('admin content updates preserve an instructor question creator', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $question = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);

    $this->actingAs($admin)->put(route('admin.questions.update', $question), ownershipQuestionPayload($subject, [
        'question_text' => 'Admin-corrected content?',
        'created_by' => $admin->id,
    ]))->assertRedirect(route('admin.questions.index'));

    expect($question->fresh()->created_by)->toBe($instructor->id)
        ->and($question->fresh()->question_text)->toBe('Admin-corrected content?');
});

test('legacy questions with no creator remain valid and render safely for admins', function () {
    $admin = User::factory()->admin()->create();
    $legacy = Question::factory()->create(['created_by' => null, 'question_text' => 'Historical shared question?']);

    expect($legacy->creator)->toBeNull();

    $this->actingAs($admin)->get(route('admin.questions.index'))
        ->assertOk()
        ->assertSee('Historical shared question?')
        ->assertSee('Legacy / Former owner');
});

test('admin question bank globally displays instructor questions and creator role', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create(['name' => 'Visible Question Author']);
    Question::factory()->create(['created_by' => $instructor->id, 'question_text' => 'Instructor-authored question?']);

    $this->actingAs($admin)->get(route('admin.questions.index'))
        ->assertOk()
        ->assertSee('Instructor-authored question?')
        ->assertSee('Visible Question Author')
        ->assertSee('Instructor');
});

test('admin deletion of an instructor nulls ownership without deleting the question or examination assignment', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $question = Question::factory()->create(['created_by' => $instructor->id]);
    $examination = Examination::factory()->create(['subject_id' => $question->subject_id]);
    $examination->questions()->attach($question, ['position' => 1]);

    $this->actingAs($admin)->delete(route('admin.instructors.destroy', $instructor))
        ->assertRedirect(route('admin.instructors.index'));

    $this->assertModelExists($question);
    $this->assertDatabaseHas('questions', ['id' => $question->id, 'created_by' => null]);
    $this->assertDatabaseHas('examination_question', ['examination_id' => $examination->id, 'question_id' => $question->id]);
});

test('demo content creates one deterministic instructor-owned question without disturbing shared questions', function () {
    $this->seed(DemoAccountSeeder::class);
    $this->seed(DemoContentSeeder::class);
    $this->seed(DemoContentSeeder::class);

    $instructor = User::where('email', 'instructor@mocktest.test')->firstOrFail();

    expect(Question::where('created_by', $instructor->id)->count())->toBe(1)
        ->and(Question::whereNull('created_by')->count())->toBe(4);
    $this->assertDatabaseHas('questions', [
        'created_by' => $instructor->id,
        'question_text' => 'Which planet is known as the Red Planet?',
    ]);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ownershipQuestionPayload(Subject $subject, array $overrides = []): array
{
    return [...[
        'subject_id' => $subject->id,
        'question_text' => 'Who owns this question?',
        'option_a' => 'Admin',
        'option_b' => 'Instructor',
        'option_c' => 'Student',
        'option_d' => 'Nobody',
        'correct_option' => 'a',
        'explanation' => 'Ownership is assigned by the server.',
    ], ...$overrides];
}
