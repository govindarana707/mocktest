<?php

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an instructor sees assigned subjects but not unassigned subjects', function () {
    $instructor = User::factory()->instructor()->create();
    $assigned = Subject::factory()->create(['code' => 'ASSIGNED-101', 'name' => 'Assigned Subject']);
    $unassigned = Subject::factory()->create(['code' => 'HIDDEN-101', 'name' => 'Unassigned Subject']);
    $instructor->subjects()->attach($assigned);

    $this->actingAs($instructor)->get(route('instructor.subjects.index'))
        ->assertOk()
        ->assertSee('My Subjects')
        ->assertSee($assigned->name)
        ->assertSee($assigned->code)
        ->assertDontSee($unassigned->name)
        ->assertSee('Read only');
});

test('an instructor with no assignments sees the empty state', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->get(route('instructor.subjects.index'))
        ->assertOk()
        ->assertSee('No subjects have been assigned to you yet.');
});

test('the instructor subjects page enforces strict role boundaries', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->get(route('instructor.subjects.index'))->assertForbidden();
})->with(['student' => 'student', 'admin' => 'admin']);

test('guests are redirected to instructor login from My Subjects', function () {
    $this->get(route('instructor.subjects.index'))->assertRedirect(route('instructor.login'));
});

test('My Subjects exposes no subject or assignment write controls', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);

    $this->actingAs($instructor)->get(route('instructor.subjects.index'))
        ->assertOk()
        ->assertDontSee('Create Subject')
        ->assertDontSee('Edit Subject')
        ->assertDontSee('Delete Subject');

    $this->actingAs($instructor)->put(route('admin.subjects.update', $subject), [
        'code' => 'CHANGED',
        'name' => 'Changed Subject',
    ])->assertForbidden();

    $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'code' => $subject->code, 'name' => $subject->name]);
});
