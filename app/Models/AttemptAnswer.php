<?php

namespace App\Models;

use Database\Factories\AttemptAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    /** @use HasFactory<AttemptAnswerFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['examination_attempt_id', 'question_id', 'selected_option', 'version'];

    public function examinationAttempt(): BelongsTo
    {
        return $this->belongsTo(ExaminationAttempt::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
