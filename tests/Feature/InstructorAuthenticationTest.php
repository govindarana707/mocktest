<?php

use App\Models\User;
use App\UserRole;
use Database\Seeders\DemoAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

test('the instructor login and dashboard shell render', function () {
    $instructor = User::factory()->instructor()->create(['name' => 'Portal Instructor']);

    $this->get(route('instructor.login'))
        ->assertOk()
        ->assertSee('Instructor sign in')
        ->assertSee('name="_token"', false);

    $this->actingAs($instructor)->get(route('instructor.dashboard'))
        ->assertOk()
        ->assertSee('Portal Instructor')
        ->assertSee('Open Question Bank')
        ->assertSee(route('instructor.questions.index'))
        ->assertSee('Coming Soon');

    $this->actingAs($instructor)->get(route('home'))->assertRedirect(route('instructor.dashboard'));
    $this->actingAs($instructor)->get(route('dashboard'))->assertRedirect(route('instructor.dashboard'));
});

test('an instructor signs in through the instructor portal', function () {
    $instructor = User::factory()->instructor()->create(['password' => 'Password123']);

    $this->post(route('instructor.login.store'), ['email' => $instructor->email, 'password' => 'Password123'])
        ->assertRedirect(route('instructor.dashboard'));

    $this->assertAuthenticatedAs($instructor);
});

test('the demo instructor seeder is idempotent and its credentials authenticate', function () {
    $this->seed(DemoAccountSeeder::class);
    $this->seed(DemoAccountSeeder::class);

    expect(User::where('email', 'instructor@mocktest.test')->count())->toBe(1);

    $this->post(route('instructor.login.store'), [
        'email' => 'instructor@mocktest.test',
        'password' => 'Instructor123!',
    ])->assertRedirect(route('instructor.dashboard'));
});

test('student and admin credentials are rejected by the instructor portal', function (string $role) {
    $user = User::factory()->{$role}()->create(['password' => 'Password123']);

    $this->post(route('instructor.login.store'), ['email' => $user->email, 'password' => 'Password123'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
})->with(['student' => 'student', 'administrator' => 'admin']);

test('instructor credentials are rejected by student and admin portals', function (string $routeName) {
    $instructor = User::factory()->instructor()->create(['password' => 'Password123']);

    $this->post(route($routeName), ['email' => $instructor->email, 'password' => 'Password123'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
})->with(['student portal' => 'login.store', 'admin portal' => 'admin.login.store']);

test('an instructor can log out securely', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->post(route('instructor.logout'))
        ->assertRedirect(route('instructor.login'));

    $this->assertGuest();
});

test('instructor routes require authentication and the instructor role', function () {
    $student = User::factory()->student()->create();
    $admin = User::factory()->admin()->create();

    $this->get(route('instructor.dashboard'))->assertRedirect(route('instructor.login'));
    $this->actingAs($student)->get(route('instructor.dashboard'))->assertForbidden();
    $this->actingAs($admin)->get(route('instructor.dashboard'))->assertForbidden();
});

test('an instructor cannot access admin or student routes', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($instructor)->get(route('student.dashboard'))->assertForbidden();
});

test('the instructor login is throttled after repeated failures', function () {
    RateLimiter::clear('instructor|nobody@example.com|127.0.0.1');

    foreach (range(1, 5) as $attempt) {
        $this->post(route('instructor.login.store'), ['email' => 'nobody@example.com', 'password' => 'WrongPassword'])
            ->assertSessionHasErrors('email');
    }

    $this->post(route('instructor.login.store'), ['email' => 'nobody@example.com', 'password' => 'WrongPassword'])
        ->assertTooManyRequests();
});

test('public registration always creates a student despite a submitted role', function (string $submittedRole) {
    $this->post(route('register.store'), [
        'name' => 'Secure Registration',
        'email' => "{$submittedRole}@example.com",
        'password' => 'Secure123',
        'password_confirmation' => 'Secure123',
        'role' => $submittedRole,
    ])->assertRedirect(route('student.dashboard'));

    $this->assertDatabaseHas('users', [
        'email' => "{$submittedRole}@example.com",
        'role' => UserRole::Student->value,
    ]);
})->with(['malicious instructor role' => 'instructor', 'malicious admin role' => 'admin']);
