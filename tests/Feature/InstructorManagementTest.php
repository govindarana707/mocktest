<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('an admin can list and search instructors', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->instructor()->create(['name' => 'Visible Instructor', 'email' => 'visible@example.com']);
    User::factory()->instructor()->create(['name' => 'Hidden Instructor', 'email' => 'hidden@example.com']);

    $this->actingAs($admin)->get(route('admin.instructors.index', ['search' => 'visible@example.com']))
        ->assertOk()
        ->assertSee('Visible Instructor')
        ->assertDontSee('Hidden Instructor');
});

test('an admin can create an instructor with a securely hashed password', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.instructors.store'), [
        'name' => 'New Instructor',
        'email' => 'new-instructor@example.com',
        'password' => 'Secure123',
        'password_confirmation' => 'Secure123',
        'role' => UserRole::Admin->value,
    ])->assertRedirect(route('admin.instructors.index'));

    $instructor = User::where('email', 'new-instructor@example.com')->firstOrFail();
    expect($instructor->role)->toBe(UserRole::Instructor)
        ->and(Hash::check('Secure123', $instructor->password))->toBeTrue()
        ->and($instructor->password)->not->toBe('Secure123');
});

test('instructor creation validates duplicate email and password confirmation', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($admin)->post(route('admin.instructors.store'), [
        'name' => 'Duplicate Instructor',
        'email' => 'taken@example.com',
        'password' => 'Secure123',
        'password_confirmation' => 'Different123',
    ])->assertSessionHasErrors(['email', 'password']);
});

test('an admin can edit an instructor without changing a blank password', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create(['password' => 'Original123']);
    $originalPassword = $instructor->password;

    $this->actingAs($admin)->put(route('admin.instructors.update', $instructor), [
        'name' => 'Updated Instructor',
        'email' => 'updated-instructor@example.com',
        'password' => '',
        'password_confirmation' => '',
    ])->assertRedirect(route('admin.instructors.index'));

    expect($instructor->fresh()->name)->toBe('Updated Instructor')
        ->and($instructor->fresh()->password)->toBe($originalPassword)
        ->and($instructor->fresh()->role)->toBe(UserRole::Instructor);
});

test('an admin can change an instructor password securely', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create(['password' => 'Original123']);

    $this->actingAs($admin)->put(route('admin.instructors.update', $instructor), [
        'name' => $instructor->name,
        'email' => $instructor->email,
        'password' => 'Changed123',
        'password_confirmation' => 'Changed123',
    ])->assertRedirect(route('admin.instructors.index'));

    expect(Hash::check('Changed123', $instructor->fresh()->password))->toBeTrue();
});

test('an admin can delete an unreferenced instructor', function () {
    $admin = User::factory()->admin()->create();
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($admin)->delete(route('admin.instructors.destroy', $instructor))
        ->assertRedirect(route('admin.instructors.index'));

    $this->assertModelMissing($instructor);
});

test('non-admin users cannot access instructor management', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->get(route('admin.instructors.index'))->assertForbidden();
})->with(['student' => 'student', 'instructor' => 'instructor']);

test('guests are redirected from instructor management without seeing private data', function () {
    User::factory()->instructor()->create(['email' => 'private-instructor@example.com']);

    $this->get(route('admin.instructors.index'))
        ->assertRedirect(route('admin.login'))
        ->assertDontSee('private-instructor@example.com');
});

test('admin instructor routes reject users with another role', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($admin)->get(route('admin.instructors.edit', $student))->assertNotFound();
});

test('the admin dashboard displays the real instructor count', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->instructor()->count(2)->create();

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Instructors')
        ->assertSee('2');
});
