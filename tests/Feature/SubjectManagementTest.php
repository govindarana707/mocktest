<?php

use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can create and update subjects', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'code' => 'math-101', 'name' => 'Mathematics', 'description' => 'Core numerical skills.',
    ])->assertRedirect(route('admin.subjects.index'));
    $this->assertDatabaseHas('subjects', ['code' => 'MATH-101', 'name' => 'Mathematics']);

    $subject = Subject::firstOrFail();
    $this->actingAs($admin)->put(route('admin.subjects.update', $subject), [
        'code' => 'MATH-201', 'name' => 'Advanced Mathematics', 'description' => 'Updated description.',
    ])->assertRedirect(route('admin.subjects.index'));
    $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'code' => 'MATH-201']);
});

test('subject creation validates unique identity fields', function () {
    $admin = User::factory()->admin()->create();
    Subject::factory()->create(['code' => 'SCI-101', 'name' => 'Science']);

    $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'code' => 'SCI-101', 'name' => 'Science', 'description' => str_repeat('a', 2001),
    ])->assertSessionHasErrors(['code', 'name', 'description']);
});

test('subjects referenced by questions cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $question = Question::factory()->create();

    $this->actingAs($admin)->delete(route('admin.subjects.destroy', $question->subject))
        ->assertSessionHas('error');
    $this->assertModelExists($question->subject);
});

test('students cannot manage subjects', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('admin.subjects.index'))->assertForbidden();
});
