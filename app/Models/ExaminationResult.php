<?php

namespace App\Models;

use Database\Factories\ExaminationResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExaminationResult extends Model
{
    /** @use HasFactory<ExaminationResultFactory> */
    use HasFactory;

    protected $fillable = ['examination_attempt_id', 'total_questions', 'answered_questions', 'correct_answers', 'incorrect_answers', 'unanswered_questions', 'maximum_marks', 'obtained_marks', 'percentage', 'passing_percentage', 'passed', 'graded_at'];

    protected function casts(): array
    {
        return ['maximum_marks' => 'decimal:2', 'obtained_marks' => 'decimal:2', 'percentage' => 'decimal:2', 'passed' => 'boolean', 'graded_at' => 'datetime'];
    }

    public function examinationAttempt(): BelongsTo
    {
        return $this->belongsTo(ExaminationAttempt::class);
    }
}
