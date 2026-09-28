<?php

namespace App\Models;

use Database\Factories\ExaminationAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExaminationAttempt extends Model
{
    /** @use HasFactory<ExaminationAttemptFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['examination_id', 'student_id', 'started_at', 'expires_at', 'submitted_at', 'submission_reason'];

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
        return $this->belongsToMany(Question::class, 'examination_attempt_question')->withPivot('position')->orderByPivot('position');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
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
