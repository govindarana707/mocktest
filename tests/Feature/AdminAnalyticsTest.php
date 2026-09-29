<?php

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\ExaminationResult;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function analyticsResult(User $student, Examination $examination, float $percentage, bool $passed, string $gradedAt = '2026-09-01 10:00:00'): ExaminationResult
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

test('analytics is accessible only to administrators', function () {
    $this->get(route('admin.analytics.index'))->assertRedirect(route('admin.login'));

    $this->actingAs(User::factory()->student()->create())
        ->get(route('admin.analytics.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->instructor()->create())
        ->get(route('admin.analytics.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.analytics.index'))
        ->assertOk();
});

test('analytics renders exact persisted summary metrics', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create(['name' => 'Databases']);
    $firstExam = Examination::factory()->create(['subject_id' => $subject->id, 'title' => 'SQL Fundamentals']);
    $secondExam = Examination::factory()->create(['subject_id' => $subject->id, 'title' => 'Index Design']);
    $firstStudent = User::factory()->student()->create();
    $secondStudent = User::factory()->student()->create();

    analyticsResult($firstStudent, $firstExam, 80, true);
    analyticsResult($firstStudent, $secondExam, 40, false);
    analyticsResult($secondStudent, $firstExam, 60, true);

    $this->actingAs($admin)->get(route('admin.analytics.index'))
        ->assertOk()
        ->assertViewHas('summary.totalResults', 3)
        ->assertViewHas('summary.uniqueStudents', 2)
        ->assertViewHas('summary.examinationsWithResults', 2)
        ->assertViewHas('summary.passedResults', 2)
        ->assertViewHas('summary.failedResults', 1)
        ->assertViewHas('summary.passRate', 66.7)
        ->assertViewHas('summary.averagePercentage', 60.0);
});

test('analytics filters use subject examination and result status together', function () {
    $admin = User::factory()->admin()->create();
    $database = Subject::factory()->create(['name' => 'Databases']);
    $networking = Subject::factory()->create(['name' => 'Networking']);
    $databaseExam = Examination::factory()->create(['subject_id' => $database->id, 'title' => 'Database Examination']);
    $networkingExam = Examination::factory()->create(['subject_id' => $networking->id, 'title' => 'Network Examination']);

    analyticsResult(User::factory()->student()->create(), $databaseExam, 80, true);
    analyticsResult(User::factory()->student()->create(), $databaseExam, 20, false);
    analyticsResult(User::factory()->student()->create(), $networkingExam, 90, true);

    $this->actingAs($admin)->get(route('admin.analytics.index', ['subject_id' => $database->id]))
        ->assertViewHas('summary.totalResults', 2);
    $this->actingAs($admin)->get(route('admin.analytics.index', ['examination_id' => $networkingExam->id]))
        ->assertViewHas('summary.totalResults', 1);
    $this->actingAs($admin)->get(route('admin.analytics.index', ['passed' => '0']))
        ->assertViewHas('summary.totalResults', 1);
    $this->actingAs($admin)->get(route('admin.analytics.index', ['subject_id' => $database->id, 'examination_id' => $networkingExam->id, 'passed' => '1']))
        ->assertViewHas('summary.totalResults', 0);
});

test('analytics groups examination and subject performance without cross-contamination', function () {
    $admin = User::factory()->admin()->create();
    $database = Subject::factory()->create(['name' => 'Databases']);
    $networking = Subject::factory()->create(['name' => 'Networking']);
    $databaseExam = Examination::factory()->create(['subject_id' => $database->id, 'title' => 'Database Examination']);
    $networkingExam = Examination::factory()->create(['subject_id' => $networking->id, 'title' => 'Network Examination']);

    analyticsResult(User::factory()->student()->create(), $databaseExam, 80, true);
    analyticsResult(User::factory()->student()->create(), $databaseExam, 40, false);
    analyticsResult(User::factory()->student()->create(), $networkingExam, 90, true);

    $response = $this->actingAs($admin)->get(route('admin.analytics.index'));
    $databaseExamPerformance = $response->viewData('examinationPerformance')->firstWhere('title', 'Database Examination');
    $databaseSubjectPerformance = $response->viewData('subjectPerformance')->firstWhere('name', 'Databases');

    expect($databaseExamPerformance)->toMatchArray([
        'resultCount' => 2,
        'averagePercentage' => 60.0,
        'passedResults' => 1,
        'failedResults' => 1,
        'passRate' => 50.0,
    ])->and($databaseSubjectPerformance)->toMatchArray([
        'resultCount' => 2,
        'uniqueStudents' => 2,
        'averagePercentage' => 60.0,
        'passRate' => 50.0,
    ]);
});

test('analytics places every percentage boundary in exactly one score bucket', function () {
    $admin = User::factory()->admin()->create();
    $examination = Examination::factory()->create();

    foreach ([0, 39, 40, 49, 50, 59, 60, 69, 70, 79, 80, 89, 90, 100] as $percentage) {
        analyticsResult(User::factory()->student()->create(), $examination, $percentage, $percentage >= 60);
    }

    $distribution = $this->actingAs($admin)->get(route('admin.analytics.index'))
        ->viewData('scoreDistribution')
        ->pluck('count', 'label')
        ->all();

    expect($distribution)->toMatchArray([
        '0–39%' => 2,
        '40–49%' => 2,
        '50–59%' => 2,
        '60–69%' => 2,
        '70–79%' => 2,
        '80–89%' => 2,
        '90–100%' => 2,
    ])->and(array_sum($distribution))->toBe(14);
});

test('analytics has a safe empty state', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.analytics.index'));

    $response->assertOk()
        ->assertSee('No results match these filters')
        ->assertViewHas('summary.totalResults', 0)
        ->assertViewHas('summary.passRate', 0.0)
        ->assertViewHas('summary.averagePercentage', 0.0);
});

test('analytics remains based on persisted results and omits student personal data', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create(['email' => 'private.student@example.test']);
    $examination = Examination::factory()->create(['passing_percentage' => 60]);
    analyticsResult($student, $examination, 80, true);
    $examination->update(['passing_percentage' => 99]);

    $this->actingAs($admin)->get(route('admin.analytics.index'))
        ->assertSee('80.0%')
        ->assertViewHas('summary.passRate', 100.0)
        ->assertDontSee('private.student@example.test');
});
