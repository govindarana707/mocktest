<?php

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an instructor creates examinations in assigned subjects with server-owned attribution', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();
    $subjects = Subject::factory()->count(2)->create();
    $instructor->subjects()->attach($subjects);

    foreach ($subjects as $index => $subject) {
        $this->actingAs($instructor)->post(route('instructor.examinations.store'), instructorExaminationPayload($subject, [
            'title' => "Assigned examination {$index}",
            'created_by' => $other->id,
            'creator_id' => $other->id,
            'owner_id' => $other->id,
            'user_id' => $other->id,
        ]))->assertRedirect(route('instructor.examinations.index'));

        $this->assertDatabaseHas('examinations', [
            'subject_id' => $subject->id,
            'created_by' => $instructor->id,
            'title' => "Assigned examination {$index}",
            'allow_answer_review' => true,
        ]);
    }
});

test('examination creation rejects unassigned nonexistent and absent subject authority', function (string $scenario) {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();

    if ($scenario === 'nonexistent') {
        $subject->id = 999999;
    }

    $this->actingAs($instructor)->post(route('instructor.examinations.store'), instructorExaminationPayload($subject))
        ->assertSessionHasErrors('subject_id');

    $this->assertDatabaseEmpty('examinations');
})->with(['unassigned', 'nonexistent']);

test('an instructor with no subjects sees a safe empty examination create state', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->get(route('instructor.examinations.create'))
        ->assertOk()
        ->assertSee('No assigned subjects')
        ->assertDontSee('name="subject_id"', false);
});

test('instructor examination routes enforce authentication and strict role boundaries', function (string $role) {
    if ($role === 'guest') {
        $this->get(route('instructor.examinations.index'))->assertRedirect(route('instructor.login'));
        $this->post(route('instructor.examinations.store'), [])->assertRedirect(route('instructor.login'));

        return;
    }

    $user = User::factory()->{$role}()->create();
    $this->actingAs($user)->get(route('instructor.examinations.index'))->assertForbidden();
    $this->actingAs($user)->post(route('instructor.examinations.store'), [])->assertForbidden();
})->with(['guest', 'student', 'admin']);

test('instructors sharing a subject see and manage only their own examinations', function () {
    [$instructorA, $instructorB] = User::factory()->instructor()->count(2)->create();
    $subject = Subject::factory()->create();
    $instructorA->subjects()->attach($subject);
    $instructorB->subjects()->attach($subject);
    $examinationA = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructorA->id, 'title' => 'Private A examination']);
    $examinationB = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructorB->id, 'title' => 'Private B examination']);
    $adminExamination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => User::factory()->admin()->create()->id, 'title' => 'Admin examination']);
    $legacyExamination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => null, 'title' => 'Legacy examination']);

    $this->actingAs($instructorA)->get(route('instructor.examinations.index'))
        ->assertOk()
        ->assertSee('Private A examination')
        ->assertDontSee('Private B examination')
        ->assertDontSee('Admin examination')
        ->assertDontSee('Legacy examination');

    $this->actingAs($instructorA)->get(route('instructor.examinations.edit', $examinationB))->assertForbidden();
    $this->actingAs($instructorA)->put(route('instructor.examinations.update', $examinationB), instructorExaminationPayload($subject))->assertForbidden();
    $this->actingAs($instructorA)->delete(route('instructor.examinations.destroy', $examinationB))->assertForbidden();
    $this->actingAs($instructorA)->delete(route('instructor.examinations.destroy', $adminExamination))->assertForbidden();
    $this->actingAs($instructorA)->delete(route('instructor.examinations.destroy', $legacyExamination))->assertForbidden();

    $this->assertModelExists($examinationA);
    $this->assertModelExists($examinationB);
    $this->assertModelExists($adminExamination);
    $this->assertModelExists($legacyExamination);
});

test('an owner can edit and safely move an examination between assigned subjects without changing ownership', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();
    [$originalSubject, $destinationSubject] = Subject::factory()->count(2)->create();
    $instructor->subjects()->attach([$originalSubject->id, $destinationSubject->id]);
    $examination = Examination::factory()->create(['subject_id' => $originalSubject->id, 'created_by' => $instructor->id]);

    $this->actingAs($instructor)->get(route('instructor.examinations.edit', $examination))
        ->assertOk()
        ->assertSee($originalSubject->name)
        ->assertSee($destinationSubject->name)
        ->assertSee('allow_answer_review', false)
        ->assertSee('Question assignment');

    $this->actingAs($instructor)->put(route('instructor.examinations.update', $examination), instructorExaminationPayload($destinationSubject, [
        'title' => 'Moved safely',
        'created_by' => $other->id,
    ]))->assertRedirect(route('instructor.examinations.edit', $examination));

    expect($examination->fresh()->subject_id)->toBe($destinationSubject->id)
        ->and($examination->fresh()->created_by)->toBe($instructor->id)
        ->and($examination->fresh()->title)->toBe('Moved safely');
});

test('an owner cannot move an examination to an unassigned subject or change subject while questions are assigned', function () {
    $instructor = User::factory()->instructor()->create();
    [$originalSubject, $assignedDestination, $unassignedDestination] = Subject::factory()->count(3)->create();
    $instructor->subjects()->attach([$originalSubject->id, $assignedDestination->id]);
    $examination = Examination::factory()->create(['subject_id' => $originalSubject->id, 'created_by' => $instructor->id]);

    $this->actingAs($instructor)->put(route('instructor.examinations.update', $examination), instructorExaminationPayload($unassignedDestination))
        ->assertSessionHasErrors('subject_id');
    expect($examination->fresh()->subject_id)->toBe($originalSubject->id);

    $question = Question::factory()->create(['subject_id' => $originalSubject->id]);
    $examination->questions()->attach($question, ['position' => 1]);

    $this->actingAs($instructor)->put(route('instructor.examinations.update', $examination), instructorExaminationPayload($assignedDestination))
        ->assertSessionHasErrors('subject_id');

    expect($examination->fresh()->subject_id)->toBe($originalSubject->id);
    $this->assertDatabaseHas('examination_question', ['examination_id' => $examination->id, 'question_id' => $question->id]);
});

test('an instructor can publish only an owned examination that has assigned questions', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $withoutQuestions = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);

    $this->actingAs($instructor)->put(route('instructor.examinations.update', $withoutQuestions), instructorExaminationPayload($subject, ['status' => 'published']))
        ->assertSessionHasErrors('status');

    $withQuestions = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $question = Question::factory()->create(['subject_id' => $subject->id]);
    $withQuestions->questions()->attach($question, ['position' => 1]);

    $this->actingAs($instructor)->put(route('instructor.examinations.update', $withQuestions), instructorExaminationPayload($subject, ['status' => 'published']))
        ->assertRedirect(route('instructor.examinations.edit', $withQuestions));

    expect($withQuestions->fresh()->status->value)->toBe('published');
});

test('an unassigned historical examination stays visible but becomes read only while admin control remains', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);

    $this->actingAs($instructor)->post(route('instructor.examinations.store'), instructorExaminationPayload($subject, [
        'title' => 'Historical owned examination',
    ]))->assertRedirect();
    $examination = Examination::where('title', 'Historical owned examination')->firstOrFail();

    $instructor->subjects()->detach($subject);

    $this->actingAs($instructor)->get(route('instructor.examinations.index'))
        ->assertOk()
        ->assertSee('Historical owned examination')
        ->assertSee('Subject no longer assigned')
        ->assertSee('Read only')
        ->assertDontSee(route('instructor.examinations.edit', $examination));
    $this->actingAs($instructor)->get(route('instructor.examinations.edit', $examination))->assertForbidden();
    $this->actingAs($instructor)->put(route('instructor.examinations.update', $examination), instructorExaminationPayload($subject))->assertForbidden();
    $this->actingAs($instructor)->delete(route('instructor.examinations.destroy', $examination))->assertForbidden();
    $this->actingAs($instructor)->post(route('instructor.examinations.store'), instructorExaminationPayload($subject))->assertSessionHasErrors('subject_id');

    $this->actingAs($admin)->put(route('admin.examinations.update', $examination), instructorExaminationPayload($subject, [
        'title' => 'Admin managed historical examination',
    ]))->assertRedirect(route('admin.examinations.edit', $examination));

    expect($examination->fresh()->title)->toBe('Admin managed historical examination')
        ->and($examination->fresh()->created_by)->toBe($instructor->id);
});

test('an instructor can delete only a safe owned examination while currently assigned', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $question = Question::factory()->create(['subject_id' => $subject->id]);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $examination->questions()->attach($question, ['position' => 1]);

    $this->actingAs($instructor)->delete(route('instructor.examinations.destroy', $examination))
        ->assertRedirect(route('instructor.examinations.index'));

    $this->assertModelMissing($examination);
    $this->assertModelExists($question);
    $this->assertDatabaseMissing('examination_question', ['examination_id' => $examination->id]);
});

test('student attempts prevent instructor and admin examination deletion without deleting history', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $attempt = ExaminationAttempt::factory()->create(['examination_id' => $examination->id]);

    $this->actingAs($instructor)->delete(route('instructor.examinations.destroy', $examination))->assertSessionHas('error');
    $this->actingAs($admin)->delete(route('admin.examinations.destroy', $examination))->assertSessionHas('error');

    $this->assertModelExists($examination);
    $this->assertModelExists($attempt);
});

test('instructor examination search subject status filters and pagination stay scoped to ownership', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->instructor()->create();
    [$targetSubject, $otherSubject] = Subject::factory()->count(2)->create();
    $instructor->subjects()->attach([$targetSubject->id, $otherSubject->id]);
    Examination::factory()->count(13)->create(['subject_id' => $targetSubject->id, 'created_by' => $instructor->id, 'title' => 'Owned searchable examination']);
    Examination::factory()->create(['subject_id' => $otherSubject->id, 'created_by' => $instructor->id, 'title' => 'Wrong subject examination']);
    Examination::factory()->published()->create(['subject_id' => $targetSubject->id, 'created_by' => $instructor->id, 'title' => 'Wrong status examination']);
    Examination::factory()->create(['subject_id' => $targetSubject->id, 'created_by' => $other->id, 'title' => 'Private outsider examination']);

    $this->actingAs($instructor)->get(route('instructor.examinations.index', [
        'search' => 'Owned searchable',
        'subject_id' => $targetSubject->id,
        'status' => 'draft',
    ]))->assertOk()
        ->assertSee('Owned searchable examination')
        ->assertDontSee('Wrong subject examination')
        ->assertDontSee('Wrong status examination')
        ->assertDontSee('Private outsider examination')
        ->assertSee('search=Owned%20searchable', false)
        ->assertSee('subject_id='.$targetSubject->id, false)
        ->assertSee('status=draft', false);
});

test('validation failure preserves instructor examination metadata subject and checkbox input', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);

    $this->actingAs($instructor)
        ->from(route('instructor.examinations.create'))
        ->post(route('instructor.examinations.store'), instructorExaminationPayload($subject, [
            'title' => '',
            'description' => 'Keep this description',
            'allow_answer_review' => '1',
        ]))
        ->assertRedirect(route('instructor.examinations.create'))
        ->assertSessionHasErrors('title')
        ->assertSessionHasInput('subject_id', $subject->id)
        ->assertSessionHasInput('description', 'Keep this description')
        ->assertSessionHasInput('allow_answer_review', '1');
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function instructorExaminationPayload(Subject $subject, array $overrides = []): array
{
    return [...[
        'subject_id' => $subject->id,
        'title' => 'Instructor examination',
        'description' => 'Instructor-managed examination metadata.',
        'duration_minutes' => 45,
        'passing_percentage' => 60,
        'status' => 'draft',
        'starts_at' => '2026-10-01 09:00:00',
        'ends_at' => '2026-10-01 10:00:00',
        'allow_answer_review' => '1',
    ], ...$overrides];
}
