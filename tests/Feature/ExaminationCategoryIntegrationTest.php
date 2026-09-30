<?php

use App\Models\Examination;
use App\Models\ExaminationCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can set, clear, and retain an inactive examination category', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $activeCategory = ExaminationCategory::factory()->create(['name' => 'Academic', 'slug' => 'academic']);
    $inactiveCategory = ExaminationCategory::factory()->create(['name' => 'Archived', 'slug' => 'archived', 'is_active' => false]);

    $this->actingAs($admin)->post(route('admin.examinations.store'), examinationCategoryPayload($subject, ['category_id' => $activeCategory->id]))->assertRedirect();
    $examination = Examination::firstOrFail();
    $this->assertDatabaseHas('examinations', ['id' => $examination->id, 'category_id' => $activeCategory->id]);

    $this->actingAs($admin)->put(route('admin.examinations.update', $examination), examinationCategoryPayload($subject, ['category_id' => null]))->assertRedirect();
    $this->assertDatabaseHas('examinations', ['id' => $examination->id, 'category_id' => null]);

    $examination->update(['category_id' => $inactiveCategory->id]);
    $this->actingAs($admin)->get(route('admin.examinations.edit', $examination))->assertOk()->assertSee('Archived (inactive)');
    $this->actingAs($admin)->put(route('admin.examinations.update', $examination), examinationCategoryPayload($subject, ['category_id' => $inactiveCategory->id]))->assertRedirect();
});

test('instructors can choose active categories but cannot select inactive ones', function () {
    $instructor = User::factory()->instructor()->create();
    $subject = Subject::factory()->create();
    $instructor->subjects()->attach($subject);
    $activeCategory = ExaminationCategory::factory()->create(['is_active' => true]);
    $inactiveCategory = ExaminationCategory::factory()->create(['is_active' => false]);

    $this->actingAs($instructor)->post(route('instructor.examinations.store'), examinationCategoryPayload($subject, ['category_id' => $activeCategory->id]))->assertRedirect();
    $this->assertDatabaseHas('examinations', ['created_by' => $instructor->id, 'category_id' => $activeCategory->id]);

    $this->actingAs($instructor)->post(route('instructor.examinations.store'), examinationCategoryPayload($subject, ['category_id' => $inactiveCategory->id]))->assertSessionHasErrors('category_id');
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function examinationCategoryPayload(Subject $subject, array $overrides = []): array
{
    return [...[
        'subject_id' => $subject->id,
        'title' => 'Category-aware examination',
        'description' => 'An examination used for category integration tests.',
        'duration_minutes' => 45,
        'passing_percentage' => 60,
        'status' => 'draft',
        'starts_at' => null,
        'ends_at' => null,
        'allow_answer_review' => false,
    ], ...$overrides];
}
