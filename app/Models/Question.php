<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
}
