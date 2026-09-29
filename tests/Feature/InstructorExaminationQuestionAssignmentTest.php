<?php

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('eligible question list contains own admin and legacy same-subject questions only', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $admin = User::factory()->admin()->create();
    [$subject, $otherSubject] = Subject::factory()->count(2)->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id, 'question_text' => 'My eligible question?', 'option_a' => 'Private correct-answer detail']);
    Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $admin->id, 'question_text' => 'Admin eligible question?']);
    Question::factory()->create(['subject_id' => $subject->id, 'created_by' => null, 'question_text' => 'Legacy eligible question?']);
    Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $otherInstructor->id, 'question_text' => 'Private instructor question?']);
    Question::factory()->create(['subject_id' => $otherSubject->id, 'created_by' => $instructor->id, 'question_text' => 'Different subject question?']);

    $this->actingAs($instructor)->get(route('instructor.examinations.edit', $examination))
        ->assertOk()
        ->assertSee('My eligible question?')
        ->assertSee('My Question')
        ->assertSee('Admin eligible question?')
        ->assertSee('Admin / Shared')
        ->assertSee('Legacy eligible question?')
        ->assertSee('Legacy / Shared')
        ->assertDontSee('Private instructor question?')
        ->assertDontSee('Different subject question?')
        ->assertDontSee('Private correct-answer detail');
});

test('an examination with no eligible questions shows safe empty and publication guidance', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $otherInstructor->id]);

    $this->actingAs($instructor)->get(route('instructor.examinations.edit', $examination))
        ->assertOk()
        ->assertSee('No eligible questions are available')
        ->assertSee('Assign at least one question before publishing')
        ->assertSee('Open Instructor Question Bank')
        ->assertSee(route('instructor.questions.index', ['subject_id' => $subject->id]));
});

test('an instructor assigns each eligible question source to an owned examination', function () {
    $instructor = User::factory()->instructor()->create();
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $questions = collect([
        Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]),
        Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $admin->id]),
        Question::factory()->create(['subject_id' => $subject->id, 'created_by' => null]),
    ]);

    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), [
        'question_ids' => $questions->pluck('id')->all(),
    ])->assertOk()
        ->assertJsonPath('question_count', 3)
        ->assertJsonPath('message', 'Question assignment saved successfully.');

    foreach ($questions as $index => $question) {
        $this->assertDatabaseHas('examination_question', [
            'examination_id' => $examination->id,
            'question_id' => $question->id,
            'position' => $index + 1,
        ]);
    }
});

test('assignment rejects another instructors private question and every different-subject question', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $admin = User::factory()->admin()->create();
    [$subject, $otherSubject] = Subject::factory()->count(2)->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $privateQuestion = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $otherInstructor->id]);
    $differentSubjectQuestion = Question::factory()->create(['subject_id' => $otherSubject->id, 'created_by' => $admin->id]);

    foreach ([$privateQuestion, $differentSubjectQuestion] as $question) {
        $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), [
            'question_ids' => [$question->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('question_ids');
    }

    $this->assertDatabaseMissing('examination_question', ['examination_id' => $examination->id]);
});

test('assignment endpoints deny examinations not owned by the instructor', function (string $ownerType) {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $ownerId = match ($ownerType) {
        'other instructor' => User::factory()->instructor()->create()->id,
        'admin' => User::factory()->admin()->create()->id,
        default => null,
    };
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $ownerId]);
    $question = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);

    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), [
        'question_ids' => [$question->id],
    ])->assertForbidden();

    $this->assertDatabaseMissing('examination_question', ['examination_id' => $examination->id]);
    $examination->questions()->attach($question, ['position' => 1]);

    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), [])
        ->assertForbidden();

    $this->assertDatabaseHas('examination_question', ['examination_id' => $examination->id, 'question_id' => $question->id]);
})->with(['other instructor', 'admin', 'legacy']);

test('an instructor removes own admin and legacy assignments without mutating questions', function () {
    $instructor = User::factory()->instructor()->create();
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $questions = collect([
        Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]),
        Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $admin->id]),
        Question::factory()->create(['subject_id' => $subject->id, 'created_by' => null]),
    ]);
    $originalOwners = $questions->pluck('created_by', 'id');
    $examination->questions()->attach($questions->mapWithKeys(fn (Question $question, int $index): array => [$question->id => ['position' => $index + 1]])->all());

    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), [])
        ->assertOk()
        ->assertJsonPath('question_count', 0);

    $this->assertDatabaseMissing('examination_question', ['examination_id' => $examination->id]);
    foreach ($questions as $question) {
        $this->assertModelExists($question);
        expect($question->fresh()->created_by)->toBe($originalOwners[$question->id]);
    }
});

test('repeating the same assignment is idempotent and does not duplicate the pivot', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $question = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $payload = ['question_ids' => [$question->id]];

    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), $payload)->assertOk();
    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), $payload)->assertOk();

    expect($examination->questions()->count())->toBe(1);
    $this->assertDatabaseHas('examination_question', ['examination_id' => $examination->id, 'question_id' => $question->id, 'position' => 1]);
});

test('a published owned examination remains assignable until a student attempt starts', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->published()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $question = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);

    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), [
        'question_ids' => [$question->id],
    ])->assertOk()->assertJsonPath('question_count', 1);

    $this->assertDatabaseHas('examination_question', ['examination_id' => $examination->id, 'question_id' => $question->id]);
});

test('demo content assigns its instructor question once without disturbing the legacy examination', function () {
    $this->seed(DemoAccountSeeder::class);
    $this->seed(DemoContentSeeder::class);
    $this->seed(DemoContentSeeder::class);

    $demoExamination = Examination::where('title', 'Instructor Science Practice')->firstOrFail();
    $demoQuestion = Question::where('question_text', 'Which planet is known as the Red Planet?')->firstOrFail();
    $legacyExamination = Examination::where('title', 'Mathematics Foundations')->firstOrFail();

    expect($demoExamination->questions()->count())->toBe(1)
        ->and($demoExamination->questions()->firstOrFail()->is($demoQuestion))->toBeTrue()
        ->and($demoExamination->status->value)->toBe('draft')
        ->and($legacyExamination->questions()->count())->toBe(2);
});

test('historical ineligible assignments stay attached without exposing another instructors question', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $ownQuestion = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id, 'question_text' => 'Visible owned question?']);
    $historicalQuestion = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $otherInstructor->id, 'question_text' => 'Hidden historical question?']);
    $examination->questions()->attach($historicalQuestion, ['position' => 1]);

    $this->actingAs($instructor)->get(route('instructor.examinations.edit', $examination))
        ->assertOk()
        ->assertSee('Visible owned question?')
        ->assertDontSee('Hidden historical question?');
    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), [
        'question_ids' => [$ownQuestion->id],
    ])->assertOk()->assertJsonPath('question_count', 2);

    $this->assertDatabaseHas('examination_question', ['examination_id' => $examination->id, 'question_id' => $historicalQuestion->id]);
    $this->assertDatabaseHas('examination_question', ['examination_id' => $examination->id, 'question_id' => $ownQuestion->id]);
});

test('subject unassignment preserves questions and history while disabling every assignment mutation', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $question = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $newQuestion = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $examination->questions()->attach($question, ['position' => 1]);
    $attempt = ExaminationAttempt::factory()->create(['examination_id' => $examination->id]);

    $instructor->subjects()->detach($subject);

    $this->actingAs($instructor)->get(route('instructor.examinations.index'))
        ->assertOk()
        ->assertSee($examination->title)
        ->assertSee('Read only');
    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), [
        'question_ids' => [$question->id, $newQuestion->id],
    ])->assertForbidden();
    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), [])->assertForbidden();

    $this->assertDatabaseHas('examination_question', ['examination_id' => $examination->id, 'question_id' => $question->id]);
    $this->assertDatabaseMissing('examination_question', ['examination_id' => $examination->id, 'question_id' => $newQuestion->id]);
    $this->assertModelExists($question);
    $this->assertModelExists($attempt);
});

test('question assignment locks after the first student attempt without changing snapshots or relationships', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $assignedQuestion = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $newQuestion = Question::factory()->create(['subject_id' => $subject->id, 'created_by' => $instructor->id]);
    $examination->questions()->attach($assignedQuestion, ['position' => 1]);
    $attempt = ExaminationAttempt::factory()->create(['examination_id' => $examination->id]);
    $attempt->questions()->attach($assignedQuestion, ['position' => 1]);

    $this->actingAs($instructor)->get(route('instructor.examinations.edit', $examination))
        ->assertOk()
        ->assertSee('Question assignments are locked');
    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), [
        'question_ids' => [$newQuestion->id],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('question_ids');

    $this->assertDatabaseHas('examination_question', ['examination_id' => $examination->id, 'question_id' => $assignedQuestion->id]);
    $this->assertDatabaseMissing('examination_question', ['examination_id' => $examination->id, 'question_id' => $newQuestion->id]);
    $this->assertDatabaseHas('examination_attempt_question', ['examination_attempt_id' => $attempt->id, 'question_id' => $assignedQuestion->id]);
});

test('assignment route requires an authenticated instructor role', function (string $role) {
    $examination = Examination::factory()->create();

    if ($role === 'guest') {
        $this->putJson(route('instructor.examinations.questions.update', $examination), [])->assertUnauthorized();

        return;
    }

    $user = User::factory()->{$role}()->create();
    $this->actingAs($user)->putJson(route('instructor.examinations.questions.update', $examination), [])->assertForbidden();
})->with(['guest', 'student', 'admin']);

test('complete instructor authoring flow publishes an exam that a student completes and receives a result for', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    $student = User::factory()->student()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)->put(route('admin.instructors.update', $instructor), assignmentInstructorPayload($instructor, [$subject->id]))
        ->assertRedirect(route('admin.instructors.index'));
    $this->actingAs($instructor)->post(route('instructor.questions.store'), assignmentQuestionPayload($subject))
        ->assertRedirect(route('instructor.questions.index'));
    $question = Question::where('question_text', 'Which option should pass?')->firstOrFail();
    $this->actingAs($instructor)->post(route('instructor.examinations.store'), assignmentExaminationPayload($subject))
        ->assertRedirect(route('instructor.examinations.index'));
    $examination = Examination::where('title', 'Instructor end-to-end examination')->firstOrFail();

    $this->actingAs($instructor)->put(route('instructor.examinations.update', $examination), assignmentExaminationPayload($subject, ['status' => 'published']))
        ->assertSessionHasErrors('status');
    $this->actingAs($instructor)->putJson(route('instructor.examinations.questions.update', $examination), [
        'question_ids' => [$question->id],
    ])->assertOk();
    $this->actingAs($instructor)->put(route('instructor.examinations.update', $examination), assignmentExaminationPayload($subject, ['status' => 'published']))
        ->assertRedirect(route('instructor.examinations.edit', $examination));

    expect($examination->fresh()->created_by)->toBe($instructor->id)
        ->and($examination->fresh()->status->value)->toBe('published');
    $this->actingAs($student)->get(route('student.exams.index'))->assertOk()->assertSee('Instructor end-to-end examination');
    $this->actingAs($student)->post(route('student.attempts.start', $examination))->assertRedirect();
    $attempt = ExaminationAttempt::where('examination_id', $examination->id)->where('student_id', $student->id)->firstOrFail();
    $this->actingAs($student)->putJson(route('student.attempts.answers.update', [$attempt, $question]), [
        'selected_option' => 'b',
        'version' => 0,
    ])->assertOk();
    $this->actingAs($student)->postJson(route('student.attempts.submit', $attempt))
        ->assertOk()
        ->assertJsonPath('reason', 'manual');

    $this->assertDatabaseHas('examination_results', [
        'examination_attempt_id' => $attempt->id,
        'correct_answers' => 1,
        'passed' => true,
    ]);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function assignmentExaminationPayload(Subject $subject, array $overrides = []): array
{
    return [...[
        'subject_id' => $subject->id,
        'title' => 'Instructor end-to-end examination',
        'description' => 'A complete Instructor authoring workflow.',
        'duration_minutes' => 30,
        'passing_percentage' => 60,
        'status' => 'draft',
        'starts_at' => null,
        'ends_at' => null,
        'allow_answer_review' => false,
    ], ...$overrides];
}

/** @return array<string, mixed> */
function assignmentQuestionPayload(Subject $subject): array
{
    return [
        'subject_id' => $subject->id,
        'question_text' => 'Which option should pass?',
        'option_a' => 'Incorrect',
        'option_b' => 'Correct',
        'option_c' => 'Incorrect again',
        'option_d' => 'Also incorrect',
        'correct_option' => 'b',
        'marks' => 1,
        'explanation' => 'Option B is correct.',
    ];
}

/**
 * @param  array<int, int>  $subjectIds
 * @return array<string, mixed>
 */
function assignmentInstructorPayload(User $instructor, array $subjectIds): array
{
    return [
        'name' => $instructor->name,
        'email' => $instructor->email,
        'password' => '',
        'password_confirmation' => '',
        'subject_ids' => $subjectIds,
    ];
}
