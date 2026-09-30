<?php

namespace App\Models;

use Database\Factories\ExaminationCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_active'])]
class ExaminationCategory extends Model
{
    /** @use HasFactory<ExaminationCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(Examination::class, 'category_id');
    }
}
