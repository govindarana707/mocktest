<?php

namespace App\Models;

use Database\Factories\ExaminationAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExaminationAttempt extends Model
{
    /** @use HasFactory<ExaminationAttemptFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['examination_id', 'student_id', 'started_at', 'expires_at', 'submitted_at', 'submission_reason', 'passing_percentage_snapshot'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'expires_at' => 'datetime', 'submitted_at' => 'datetime'];
    }

    public function examination(): BelongsTo
    {
        return $this->belongsTo(Examination::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'examination_attempt_question')->withPivot(['position', 'question_text_snapshot', 'option_a_snapshot', 'option_b_snapshot', 'option_c_snapshot', 'option_d_snapshot', 'correct_option_snapshot', 'explanation_snapshot', 'marks_snapshot'])->orderByPivot('position');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(ExaminationResult::class);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function hasExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->expires_at);
    }
}
