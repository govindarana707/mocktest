<?php

namespace App\Models;

use App\ExaminationStatus;
use Database\Factories\ExaminationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subject_id', 'title', 'description', 'duration_minutes', 'passing_percentage', 'status', 'starts_at', 'ends_at'])]
class Examination extends Model
{
    /** @use HasFactory<ExaminationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ExaminationStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'examination_question')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExaminationAttempt::class);
    }

    public function isAvailableNow(): bool
    {
        return $this->status === ExaminationStatus::Published
            && (! $this->starts_at || $this->starts_at->isPast())
            && (! $this->ends_at || $this->ends_at->isFuture());
    }
}
