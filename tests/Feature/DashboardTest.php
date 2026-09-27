<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin dashboard displays real account statistics', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->student()->count(3)->create();

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()->assertSee('Students')->assertSee('3')->assertSee('Administrators')->assertSee('1');
});

test('student dashboard shows profile completion and phase boundary', function () {
    $student = User::factory()->student()->create(['phone' => null, 'date_of_birth' => null, 'bio' => null]);

    $this->actingAs($student)->get(route('student.dashboard'))
        ->assertOk()->assertSee('40%')->assertSee('Coming Soon');
});

test('future module pages are available only as coming soon', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('student.exams.index'))
        ->assertOk()->assertSee('planned for Phase 3')->assertSee('Coming Soon');
});
