<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

test('a visitor can register only as a student', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Asha Rai', 'email' => 'asha@example.com', 'password' => 'Secure123',
        'password_confirmation' => 'Secure123', 'role' => 'admin',
    ]);

    $response->assertRedirect(route('student.dashboard'));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'asha@example.com', 'role' => UserRole::Student->value]);
});

test('registration validates required unique and strong credentials', function () {
    User::factory()->create(['email' => 'used@example.com']);

    $this->post(route('register.store'), [
        'name' => '', 'email' => 'used@example.com', 'password' => 'weak', 'password_confirmation' => 'different',
    ])->assertSessionHasErrors(['name', 'email', 'password']);

    $this->assertGuest();
});

test('students and administrators sign in through their own portals', function () {
    $student = User::factory()->student()->create(['email' => 'student@example.com', 'password' => 'Password123']);
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com', 'password' => 'Password123']);

    $this->post(route('login.store'), ['email' => $student->email, 'password' => 'Password123'])
        ->assertRedirect(route('student.dashboard'));
    $this->assertAuthenticatedAs($student);
    $this->post(route('student.logout'))->assertRedirect(route('login'));
    $this->assertGuest();

    $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'Password123'])
        ->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin);
});

test('cross role credentials are rejected', function () {
    $admin = User::factory()->admin()->create(['password' => 'Password123']);

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'Password123'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('student login is throttled after repeated attempts', function () {
    RateLimiter::clear('student|nobody@example.com|127.0.0.1');

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'WrongPassword'])->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'WrongPassword'])->assertTooManyRequests();
});
