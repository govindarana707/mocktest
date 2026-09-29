<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an instructor can view their own profile', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->get(route('instructor.profile.edit'))
        ->assertOk()
        ->assertSee($instructor->name)
        ->assertSee($instructor->email);
});

test('an instructor can update their own name and email', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->put(route('instructor.profile.update'), [
        'name' => 'Updated Instructor',
        'email' => 'updated-profile@example.com',
    ])->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'id' => $instructor->id,
        'name' => 'Updated Instructor',
        'email' => 'updated-profile@example.com',
        'role' => UserRole::Instructor->value,
    ]);
});

test('instructor profile updates validate required and unique fields', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->create(['email' => 'taken-profile@example.com']);

    $this->actingAs($instructor)->put(route('instructor.profile.update'), [
        'name' => '',
        'email' => $other->email,
    ])->assertSessionHasErrors(['name', 'email']);
});

test('an instructor cannot change role or another user through profile input', function () {
    $instructor = User::factory()->instructor()->create();
    $other = User::factory()->student()->create(['name' => 'Other Student']);

    $this->actingAs($instructor)->put(route('instructor.profile.update'), [
        'name' => 'Safe Instructor',
        'email' => 'safe-instructor@example.com',
        'role' => UserRole::Admin->value,
        'user_id' => $other->id,
    ])->assertRedirect();

    expect($instructor->fresh()->role)->toBe(UserRole::Instructor)
        ->and($other->fresh()->name)->toBe('Other Student');
});
