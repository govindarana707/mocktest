<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a student can view and update only their profile', function () {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student)->put(route('student.profile.update'), [
        'name' => 'Updated Student', 'email' => 'updated@example.com', 'phone' => '+977 9812345678',
        'date_of_birth' => '2000-01-01', 'bio' => 'Preparing for the next examination.',
    ]);

    $response->assertRedirect()->assertSessionHas('success');
    $this->assertDatabaseHas('users', ['id' => $student->id, 'name' => 'Updated Student', 'email' => 'updated@example.com']);
});

test('profile updates enforce server side validation', function () {
    $student = User::factory()->student()->create();
    $other = User::factory()->student()->create(['email' => 'taken@example.com']);

    $this->actingAs($student)->put(route('student.profile.update'), [
        'name' => '', 'email' => $other->email, 'date_of_birth' => now()->addDay()->format('Y-m-d'),
        'bio' => str_repeat('a', 1001),
    ])->assertSessionHasErrors(['name', 'email', 'date_of_birth', 'bio']);
});
