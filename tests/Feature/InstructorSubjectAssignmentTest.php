<?php

use App\Models\Subject;
use App\Models\User;
use App\UserRole;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an instructor can have multiple subjects', function () {
    $instructor = User::factory()->instructor()->create();
    $subjects = Subject::factory()->count(2)->create();

    $instructor->subjects()->attach($subjects->modelKeys());

    expect($instructor->subjects()->pluck('subjects.id')->all())->toEqualCanonicalizing($subjects->modelKeys());
});

test('a subject can belong to multiple instructors', function () {
    $subject = Subject::factory()->create();
    $instructors = User::factory()->instructor()->count(2)->create();

    $subject->instructors()->attach($instructors->modelKeys());

    expect($subject->instructors()->pluck('users.id')->all())->toEqualCanonicalizing($instructors->modelKeys());
});

test('duplicate instructor subject assignments are prevented by the database', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);

    expect(fn () => $instructor->subjects()->attach($subject))->toThrow(QueryException::class);
    $this->assertDatabaseCount('instructor_subject', 1);
});

test('deleting an instructor removes assignments without deleting subjects', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);

    $instructor->delete();

    $this->assertDatabaseEmpty('instructor_subject');
    $this->assertModelExists($subject);
});

test('deleting an unreferenced subject removes assignments without deleting instructors', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);

    $subject->delete();

    $this->assertDatabaseEmpty('instructor_subject');
    $this->assertModelExists($instructor);
});

test('an admin can create an instructor with zero subjects', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.instructors.store'), instructorPayload())
        ->assertRedirect(route('admin.instructors.index'));

    $instructor = User::where('email', 'assigned@example.com')->firstOrFail();
    expect($instructor->role)->toBe(UserRole::Instructor)
        ->and($instructor->subjects)->toHaveCount(0);
});

test('an admin can create an instructor with one subject', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)->post(route('admin.instructors.store'), instructorPayload([
        'subject_ids' => [$subject->id],
    ]))->assertRedirect(route('admin.instructors.index'));

    $instructor = User::where('email', 'assigned@example.com')->firstOrFail();
    $this->assertDatabaseHas('instructor_subject', ['instructor_id' => $instructor->id, 'subject_id' => $subject->id]);
});

test('an admin can create an instructor with multiple subjects', function () {
    $admin = User::factory()->admin()->create();
    $subjects = Subject::factory()->count(3)->create();

    $this->actingAs($admin)->post(route('admin.instructors.store'), instructorPayload([
        'subject_ids' => $subjects->modelKeys(),
    ]))->assertRedirect(route('admin.instructors.index'));

    $instructor = User::where('email', 'assigned@example.com')->firstOrFail();
    expect($instructor->subjects()->pluck('subjects.id')->all())->toEqualCanonicalizing($subjects->modelKeys());
});

test('an admin can synchronize added and removed subject assignments', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();
    [$removed, $retained, $added] = Subject::factory()->count(3)->create();
    $instructor->subjects()->attach([$removed->id, $retained->id]);

    $this->actingAs($admin)->put(route('admin.instructors.update', $instructor), [
        'name' => $instructor->name,
        'email' => $instructor->email,
        'password' => '',
        'password_confirmation' => '',
        'subject_ids' => [$retained->id, $added->id],
    ])->assertRedirect(route('admin.instructors.index'));

    expect($instructor->subjects()->pluck('subjects.id')->all())->toEqualCanonicalizing([$retained->id, $added->id]);
});

test('duplicate and nonexistent subject identifiers are rejected', function (array $subjectIds) {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $subjectIds = array_map(fn (int|string $id) => $id === 'existing' ? $subject->id : $id, $subjectIds);

    $this->actingAs($admin)->post(route('admin.instructors.store'), instructorPayload([
        'subject_ids' => $subjectIds,
    ]))->assertSessionHasErrors('subject_ids.0');

    $this->assertDatabaseMissing('users', ['email' => 'assigned@example.com']);
})->with([
    'duplicate identifiers' => [['existing', 'existing']],
    'nonexistent identifier' => [[999999]],
]);

test('validation failure preserves attempted subject selections', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)->from(route('admin.instructors.create'))->post(route('admin.instructors.store'), instructorPayload([
        'email' => 'not-an-email',
        'subject_ids' => [$subject->id],
    ]))->assertRedirect(route('admin.instructors.create'))
        ->assertSessionHasInput('subject_ids', [$subject->id]);
});

test('admin assignment endpoints reject student and admin targets', function (string $role) {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->{$role}()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)->put(route('admin.instructors.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'password' => '',
        'password_confirmation' => '',
        'subject_ids' => [$subject->id],
    ])->assertNotFound();

    $this->assertDatabaseMissing('instructor_subject', ['instructor_id' => $target->id]);
})->with(['student target' => 'student', 'admin target' => 'admin']);

test('only admins can submit instructor subject assignments', function (string $role) {
    $actor = User::factory()->{$role}()->create();
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($actor)->put(route('admin.instructors.update', $instructor), [
        'name' => $instructor->name,
        'email' => $instructor->email,
        'subject_ids' => [$subject->id],
    ])->assertForbidden();

    $this->assertDatabaseEmpty('instructor_subject');
})->with(['student' => 'student', 'instructor' => 'instructor']);

test('guests cannot submit instructor subject assignments', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();

    $this->put(route('admin.instructors.update', $instructor), [
        'name' => $instructor->name,
        'email' => $instructor->email,
        'subject_ids' => [$subject->id],
    ])->assertRedirect(route('admin.login'));

    $this->assertDatabaseEmpty('instructor_subject');
});

test('demo content assigns a deterministic idempotent subject set to the demo instructor', function () {
    $this->seed(DemoAccountSeeder::class);
    $this->seed(DemoContentSeeder::class);
    $this->seed(DemoContentSeeder::class);

    $instructor = User::where('email', 'instructor@mocktest.test')->firstOrFail();

    expect($instructor->subjects()->orderBy('code')->pluck('code')->all())->toBe(['MATH-101', 'SCI-101']);
    $this->assertDatabaseCount('instructor_subject', 2);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function instructorPayload(array $overrides = []): array
{
    return [...[
        'name' => 'Assigned Instructor',
        'email' => 'assigned@example.com',
        'password' => 'Secure123',
        'password_confirmation' => 'Secure123',
    ], ...$overrides];
}
