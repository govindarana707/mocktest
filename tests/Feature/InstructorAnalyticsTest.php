<?php

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\ExaminationResult;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function instructorAnalyticsResult(User $student, Examination $examination, float $percentage, bool $passed, string $gradedAt = '2026-09-01 10:00:00'): ExaminationResult
{
    $attempt = ExaminationAttempt::factory()->submitted()->create([
        'student_id' => $student->id,
        'examination_id' => $examination->id,
    ]);

    return ExaminationResult::create([
        'examination_attempt_id' => $attempt->id,
        'total_questions' => 10,
        'answered_questions' => 10,
        'correct_answers' => $passed ? 8 : 2,
        'incorrect_answers' => $passed ? 2 : 8,
        'unanswered_questions' => 0,
        'maximum_marks' => 100,
        'obtained_marks' => $percentage,
        'percentage' => $percentage,
        'passing_percentage' => 60,
        'passed' => $passed,
        'graded_at' => $gradedAt,
    ]);
}

test('instructor analytics enforces authentication and strict role boundaries', function () {
    $this->get(route('instructor.analytics.index'))->assertRedirect(route('instructor.login'));

    $this->actingAs(User::factory()->student()->create())->get(route('instructor.analytics.index'))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('instructor.analytics.index'))->assertForbidden();
    $this->actingAs(User::factory()->instructor()->create())->get(route('instructor.analytics.index'))->assertOk();
});

test('instructor analytics includes only the signed-in instructors persisted results', function () {
    [$instructorA, $instructorB] = User::factory()->instructor()->count(2)->create();
    $subject = Subject::factory()->create(['name' => 'Shared DBMS']);
    $examinationA = Examination::factory()->create(['created_by' => $instructorA->id, 'subject_id' => $subject->id, 'title' => 'A DBMS Examination']);
    $examinationB = Examination::factory()->create(['created_by' => $instructorB->id, 'subject_id' => $subject->id, 'title' => 'B DBMS Examination']);
    $firstStudent = User::factory()->student()->create();

    instructorAnalyticsResult($firstStudent, $examinationA, 80, true, '2026-09-01 10:00:00');
    instructorAnalyticsResult(User::factory()->student()->create(), $examinationA, 40, false, '2026-09-01 10:00:00');
    instructorAnalyticsResult(User::factory()->student()->create(), $examinationB, 100, true, '2026-10-01 10:00:00');

    $response = $this->actingAs($instructorA)->get(route('instructor.analytics.index'));

    $response->assertOk()
        ->assertViewHas('summary.totalResults', 2)
        ->assertViewHas('summary.uniqueStudents', 2)
        ->assertViewHas('summary.examinationsWithResults', 1)
        ->assertViewHas('summary.passedResults', 1)
        ->assertViewHas('summary.failedResults', 1)
        ->assertViewHas('summary.passRate', 50.0)
        ->assertViewHas('summary.averagePercentage', 60.0)
        ->assertSee('A DBMS Examination')
        ->assertDontSee('B DBMS Examination');

    expect($response->viewData('scoreDistribution')->pluck('count', 'label')->all())
        ->toMatchArray(['40–49%' => 1, '80–89%' => 1, '90–100%' => 0])
        ->and($response->viewData('subjectPerformance')->first())->toMatchArray([
            'name' => 'Shared DBMS',
            'resultCount' => 2,
            'uniqueStudents' => 2,
            'averagePercentage' => 60.0,
            'passRate' => 50.0,
        ])
        ->and($response->viewData('trend')->all())->toBe([['label' => '2026-09', 'averagePercentage' => 60.0]]);
});

test('instructor analytics excludes admin-owned and legacy examination results', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $owned = Examination::factory()->create(['created_by' => $instructor->id, 'subject_id' => $subject->id]);
    $adminOwned = Examination::factory()->create(['created_by' => User::factory()->admin()->create()->id, 'subject_id' => $subject->id]);
    $legacy = Examination::factory()->create(['created_by' => null, 'subject_id' => $subject->id]);

    instructorAnalyticsResult(User::factory()->student()->create(), $owned, 80, true);
    instructorAnalyticsResult(User::factory()->student()->create(), $adminOwned, 10, false);
    instructorAnalyticsResult(User::factory()->student()->create(), $legacy, 10, false);

    $this->actingAs($instructor)->get(route('instructor.analytics.index'))
        ->assertViewHas('summary.totalResults', 1)
        ->assertViewHas('summary.passRate', 100.0);
});

test('historical owned reporting and filter options survive subject unassignment', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create(['name' => 'Historical Subject']);
    $instructor->subjects()->attach($subject);
    $examination = Examination::factory()->create(['created_by' => $instructor->id, 'subject_id' => $subject->id, 'title' => 'Historical Examination']);
    instructorAnalyticsResult(User::factory()->student()->create(), $examination, 75, true);
    $instructor->subjects()->detach($subject);

    $response = $this->actingAs($instructor)->get(route('instructor.analytics.index', [
        'examination_id' => $examination->id,
        'subject_id' => $subject->id,
    ]));

    $response->assertOk()
        ->assertViewHas('summary.totalResults', 1)
        ->assertSee('Historical Examination')
        ->assertSee('Historical Subject');
});

test('tampered examination and subject filters cannot leak another instructors analytics', function () {
    [$instructorA, $instructorB] = User::factory()->instructor()->count(2)->create();
    $subjectA = Subject::factory()->create();
    $subjectB = Subject::factory()->create();
    $owned = Examination::factory()->create(['created_by' => $instructorA->id, 'subject_id' => $subjectA->id]);
    $other = Examination::factory()->create(['created_by' => $instructorB->id, 'subject_id' => $subjectB->id]);
    instructorAnalyticsResult(User::factory()->student()->create(), $owned, 80, true);
    instructorAnalyticsResult(User::factory()->student()->create(), $other, 100, true);

    $this->actingAs($instructorA)->get(route('instructor.analytics.index', ['examination_id' => $other->id]))
        ->assertViewHas('summary.totalResults', 0);
    $this->actingAs($instructorA)->get(route('instructor.analytics.index', ['subject_id' => $subjectB->id]))
        ->assertViewHas('summary.totalResults', 0);
});

test('instructor analytics has a safe empty state and does not expose student data', function () {
    $instructor = User::factory()->instructor()->create();
    $response = $this->actingAs($instructor)->get(route('instructor.analytics.index'));

    $response->assertOk()
        ->assertSee('No results match these filters')
        ->assertViewHas('summary.totalResults', 0)
        ->assertViewHas('summary.passRate', 0.0);

    $examination = Examination::factory()->create(['created_by' => $instructor->id]);
    $student = User::factory()->student()->create(['email' => 'private.instructor.analytics@example.test']);
    instructorAnalyticsResult($student, $examination, 80, true);
    $examination->update(['passing_percentage' => 99]);

    $this->actingAs($instructor)->get(route('instructor.analytics.index'))
        ->assertViewHas('summary.passRate', 100.0)
        ->assertSee('80.0%')
        ->assertDontSee('private.instructor.analytics@example.test');
});
