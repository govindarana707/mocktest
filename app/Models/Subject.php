<?php

namespace App\Models;

use App\UserRole;
use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description'])]
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(Examination::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'instructor_subject', 'subject_id', 'instructor_id')
            ->where('users.role', UserRole::Instructor);
    }
}
