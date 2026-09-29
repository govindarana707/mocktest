<?php

use App\Models\Examination;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an instructor creates private questions in each assigned subject with server-owned attribution', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();
    $subjects = Subject::factory()->count(2)->create();
    $instructor->subjects()->attach($subjects);

    foreach ($subjects as $index => $subject) {
        $this->actingAs($instructor)->post(route('instructor.questions.store'), instructorQuestionPayload($subject, [
            'question_text' => "Assigned question {$index}?",
            'created_by' => $other->id,
            'owner_id' => $other->id,
            'user_id' => $other->id,
        ]))->assertRedirect(route('instructor.questions.index'));

        $this->assertDatabaseHas('questions', [
            'subject_id' => $subject->id,
            'created_by' => $instructor->id,
            'question_text' => "Assigned question {$index}?",
        ]);
    }
});

test('question creation rejects unassigned nonexistent and absent subject authority', function (string $scenario) {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();

    if ($scenario === 'nonexistent') {
        $subject->id = 999999;
    }

    $this->actingAs($instructor)->post(route('instructor.questions.store'), instructorQuestionPayload($subject))
        ->assertSessionHasErrors('subject_id');

    $this->assertDatabaseEmpty('questions');
})->with(['unassigned', 'nonexistent']);

test('an instructor with no subjects sees a safe empty create state', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->get(route('instructor.questions.create'))
        ->assertOk()
        ->assertSee('No assigned subjects')
        ->assertDontSee('name="subject_id"', false);
});

test('instructor question routes enforce authentication and strict role boundaries', function (string $role) {
    if ($role === 'guest') {
        $this->get(route('instructor.questions.index'))->assertRedirect(route('instructor.login'));
        $this->post(route('instructor.questions.store'), [])->assertRedirect(route('instructor.login'));

        return;
    }

    $user = User::factory()->{$role}()->create();
    $this->actingAs($user)->get(route('instructor.questions.index'))->assertForbidden();
    $this->actingAs($user)->post(route('instructor.questions.store'), [])->assertForbidden();
})->with(['guest', 'student', 'admin']);

test('instructors sharing a subject see and manage only their own questions', function () {
    [$instructorA, $instructorB] = User::factory()->instructor()->count(2)->create();
    $subject = Subject::factory()->create();
    $instructorA->subjects()->attach($subject);
    $instructorB->subjects()->attach($subject);
    $questionA = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructorA->id, 'question_text' => 'Private A question?']);
    $questionB = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructorB->id, 'question_text' => 'Private B question?']);
    $adminQuestion = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => User::factory()->admin()->create()->id, 'question_text' => 'Admin shared question?']);
    $legacyQuestion = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => null, 'question_text' => 'Legacy shared question?']);

    $this->actingAs($instructorA)->get(route('instructor.questions.index'))
        ->assertOk()
        ->assertSee('Private A question?')
        ->assertDontSee('Private B question?')
        ->assertDontSee('Admin shared question?')
        ->assertDontSee('Legacy shared question?');

    $this->actingAs($instructorB)->get(route('instructor.questions.index'))
        ->assertOk()
        ->assertSee('Private B question?')
        ->assertDontSee('Private A question?');

    $this->actingAs($instructorA)->get(route('instructor.questions.edit', $questionB))->assertForbidden();
    $this->actingAs($instructorA)->put(route('instructor.questions.update', $questionB), instructorQuestionPayload($subject))->assertForbidden();
    $this->actingAs($instructorA)->delete(route('instructor.questions.destroy', $questionB))->assertForbidden();
    $this->actingAs($instructorB)->get(route('instructor.questions.edit', $questionA))->assertForbidden();

    $this->assertModelExists($questionA);
    $this->assertModelExists($questionB);
    $this->assertModelExists($adminQuestion);
    $this->assertModelExists($legacyQuestion);
});

test('an owner can edit and move a question between assigned subjects without changing ownership', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();
    [$originalSubject, $destinationSubject] = Subject::factory()->count(2)->create();
    $instructor->subjects()->attach([$originalSubject->id, $destinationSubject->id]);
    $question = Question::factory()->create(['subject_id' => $originalSubject->id, 'created_by' => $instructor->id]);

    $this->actingAs($instructor)->get(route('instructor.questions.edit', $question))
        ->assertOk()
        ->assertSee($originalSubject->name)
        ->assertSee($destinationSubject->name);

    $this->actingAs($instructor)->put(route('instructor.questions.update', $question), instructorQuestionPayload($destinationSubject, [
        'question_text' => 'Moved safely?',
        'created_by' => $other->id,
    ]))->assertRedirect(route('instructor.questions.index'));

    expect($question->fresh()->subject_id)->toBe($destinationSubject->id)
        ->and($question->fresh()->created_by)->toBe($instructor->id)
        ->and($question->fresh()->question_text)->toBe('Moved safely?');
});

test('an owner cannot move a question into an unassigned subject', function () {
    $instructor = User::factory()->instructor()->create();
    $assigned = Subject::factory()->create();
    $unassigned = Subject::factory()->create();
    $instructor->subjects()->attach($assigned);
    $question = Question::factory()->create(['subject_id' => $assigned->id, 'created_by' => $instructor->id]);

    $this->actingAs($instructor)->put(route('instructor.questions.update', $question), instructorQuestionPayload($unassigned))
        ->assertSessionHasErrors('subject_id');

    expect($question->fresh()->subject_id)->toBe($assigned->id);
});

test('an unassigned historical question stays visible but becomes read only while admin control remains', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);

    $this->actingAs($instructor)->post(route('instructor.questions.store'), instructorQuestionPayload($subject, [
        'question_text' => 'Historical owned question?',
    ]))->assertRedirect();
    $question = Question::where('question_text', 'Historical owned question?')->firstOrFail();

    $instructor->subjects()->detach($subject);

    $this->actingAs($instructor)->get(route('instructor.questions.index'))
        ->assertOk()
        ->assertSee('Historical owned question?')
        ->assertSee('Subject no longer assigned')
        ->assertSee('Read only')
        ->assertDontSee(route('instructor.questions.edit', $question));
    $this->actingAs($instructor)->get(route('instructor.questions.edit', $question))->assertForbidden();
    $this->actingAs($instructor)->put(route('instructor.questions.update', $question), instructorQuestionPayload($subject))->assertForbidden();
    $this->actingAs($instructor)->delete(route('instructor.questions.destroy', $question))->assertForbidden();
    $this->actingAs($instructor)->post(route('instructor.questions.store'), instructorQuestionPayload($subject))->assertSessionHasErrors('subject_id');

    $this->assertModelExists($question);
    expect($question->fresh()->created_by)->toBe($instructor->id);

    $this->actingAs($admin)->put(route('admin.questions.update', $question), instructorQuestionPayload($subject, [
        'question_text' => 'Admin managed historical question?',
    ]))->assertRedirect(route('admin.questions.index'));
    expect($question->fresh()->question_text)->toBe('Admin managed historical question?');
});

test('an owner can delete an unreferenced question while currently assigned', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $question = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);

    $this->actingAs($instructor)->delete(route('instructor.questions.destroy', $question))
        ->assertSessionHas('success');

    $this->assertModelMissing($question);
});

test('an instructor cannot delete another instructors admin or legacy question', function (string $ownerType) {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $ownerId = match ($ownerType) {
        'other instructor' => User::factory()->instructor()->create()->id,
        'admin' => User::factory()->admin()->create()->id,
        default => null,
    };
    $question = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $ownerId]);

    $this->actingAs($instructor)->delete(route('instructor.questions.destroy', $question))->assertForbidden();
    $this->assertModelExists($question);
})->with(['other instructor', 'admin', 'legacy']);

test('an examination-assigned question keeps existing deletion protection for its owner', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $question = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $examination = Examination::factory()->create(['subject_id' => $subject->id]);
    $examination->questions()->attach($question, ['position' => 1]);

    $this->actingAs($instructor)->delete(route('instructor.questions.destroy', $question))
        ->assertSessionHas('error');

    $this->assertModelExists($question);
});

test('instructor question search subject filter and pagination stay scoped to ownership', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();
    [$targetSubject, $otherSubject] = Subject::factory()->count(2)->create();
    $instructor->subjects()->attach([$targetSubject->id, $otherSubject->id]);
    Question::factory()->count(13)->create(['subject_id' => $targetSubject->id, 'created_by' => $instructor->id, 'question_text' => 'Owned searchable item']);
    Question::factory()->create(['subject_id' => $otherSubject->id, 'created_by' => $instructor->id, 'question_text' => 'Wrong subject item']);
    Question::factory()->create(['subject_id' => $targetSubject->id, 'created_by' => $other->id, 'question_text' => 'Private outsider item']);

    $this->actingAs($instructor)->get(route('instructor.questions.index', [
        'search' => 'Owned searchable',
        'subject_id' => $targetSubject->id,
    ]))->assertOk()
        ->assertSee('Owned searchable item')
        ->assertDontSee('Wrong subject item')
        ->assertDontSee('Private outsider item')
        ->assertSee('search=Owned%20searchable', false)
        ->assertSee('subject_id='.$targetSubject->id, false);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function instructorQuestionPayload(Subject $subject, array $overrides = []): array
{
    return [...[
        'subject_id' => $subject->id,
        'question_text' => 'Instructor question?',
        'option_a' => 'First',
        'option_b' => 'Second',
        'option_c' => 'Third',
        'option_d' => 'Fourth',
        'correct_option' => 'b',
        'explanation' => 'Second is correct.',
    ], ...$overrides];
}
