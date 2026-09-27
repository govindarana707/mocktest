<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the correct login portal', function () {
    $this->get(route('student.dashboard'))->assertRedirect(route('login'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});

test('students cannot access administration pages', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($student)->get(route('admin.students.index'))->assertForbidden();
});

test('administrators cannot access student pages or edit a student profile', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('student.dashboard'))->assertForbidden();
    $this->actingAs($admin)->put(route('student.profile.update'), ['name' => 'Changed'])->assertForbidden();
});

test('student directory is protected from guests', function () {
    User::factory()->student()->create(['email' => 'private@example.com']);

    $this->get(route('admin.students.index'))->assertRedirect(route('admin.login'))->assertDontSee('private@example.com');
});
