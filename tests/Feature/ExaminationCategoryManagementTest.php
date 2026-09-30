<?php

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\ExaminationCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can manage examination categories and receive stable slugs', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.examination-categories.index'))
        ->assertOk()
        ->assertSee('Exam category library');

    $this->actingAs($admin)->post(route('admin.examination-categories.store'), [
        'name' => 'Entrance Preparation',
        'description' => 'Competitive exam practice.',
        'is_active' => true,
    ])->assertRedirect(route('admin.examination-categories.index'));

    $category = ExaminationCategory::firstOrFail();
    $this->assertDatabaseHas('examination_categories', [
        'id' => $category->id,
        'slug' => 'entrance-preparation',
        'is_active' => true,
    ]);

    $this->actingAs($admin)->put(route('admin.examination-categories.update', $category), [
        'name' => 'Entrance Readiness',
        'description' => 'Updated category description.',
        'is_active' => false,
    ])->assertRedirect(route('admin.examination-categories.index'));

    $this->assertDatabaseHas('examination_categories', [
        'id' => $category->id,
        'slug' => 'entrance-readiness',
        'is_active' => false,
    ]);
});

test('category creation validates a unique name', function () {
    $admin = User::factory()->admin()->create();
    ExaminationCategory::factory()->create(['name' => 'Academic']);

    $this->actingAs($admin)->post(route('admin.examination-categories.store'), [
        'name' => 'Academic',
        'is_active' => true,
    ])->assertSessionHasErrors('name');
});

test('deleting a category uncategorizes examinations while preserving their history', function () {
    $admin = User::factory()->admin()->create();
    $category = ExaminationCategory::factory()->create();
    $examination = Examination::factory()->create(['category_id' => $category->id]);
    $attempt = ExaminationAttempt::factory()->create(['examination_id' => $examination->id]);

    $this->actingAs($admin)->delete(route('admin.examination-categories.destroy', $category))
        ->assertSessionHas('success');

    $this->assertModelMissing($category);
    $this->assertModelExists($examination);
    $this->assertModelExists($attempt);
    $this->assertDatabaseHas('examinations', ['id' => $examination->id, 'category_id' => null]);
});

test('only administrators can access examination category management', function (string $role) {
    if ($role === 'guest') {
        $this->get(route('admin.examination-categories.index'))->assertRedirect(route('admin.login'));

        return;
    }

    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->get(route('admin.examination-categories.index'))->assertForbidden();
})->with(['guest', 'student', 'instructor']);
