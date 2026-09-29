<?php

namespace App\Models;

use App\UserRole;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['subject_id', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option', 'marks', 'explanation'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['marks' => 'decimal:2'];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function examinations(): BelongsToMany
    {
        return $this->belongsToMany(Examination::class, 'examination_question');
    }

    public function examinationAttempts(): BelongsToMany
    {
        return $this->belongsToMany(ExaminationAttempt::class, 'examination_attempt_question')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function scopeEligibleForInstructor(Builder $query, User $instructor, Examination $examination): Builder
    {
        return $query
            ->where('subject_id', $examination->subject_id)
            ->where(function (Builder $query) use ($instructor): void {
                $query->where('created_by', $instructor->id)
                    ->orWhereNull('created_by')
                    ->orWhereHas('creator', fn (Builder $query) => $query->where('role', UserRole::Admin));
            });
    }
}
