<?php

namespace App;

use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartExaminationAttempt
{
    public function handle(Examination $examination, User $student): ExaminationAttempt
    {
        return DB::transaction(function () use ($examination, $student): ExaminationAttempt {
            $lockedExamination = Examination::query()->lockForUpdate()->findOrFail($examination->id);
            $attempt = ExaminationAttempt::query()->where('examination_id', $lockedExamination->id)->where('student_id', $student->id)->lockForUpdate()->first();

            if ($attempt) {
                return $attempt;
            }

            if (! $lockedExamination->isAvailableNow()) {
                throw ValidationException::withMessages(['examination' => 'This examination is not currently available.']);
            }

            $questions = $lockedExamination->questions()->select('questions.id')->get();

            if ($questions->isEmpty()) {
                throw ValidationException::withMessages(['examination' => 'This examination has no assigned questions.']);
            }

            $startedAt = now();
            $expiresAt = $startedAt->copy()->addMinutes($lockedExamination->duration_minutes);

            if ($lockedExamination->ends_at && $lockedExamination->ends_at->lessThan($expiresAt)) {
                $expiresAt = $lockedExamination->ends_at->copy();
            }

            $attempt = ExaminationAttempt::create(['examination_id' => $lockedExamination->id, 'student_id' => $student->id, 'started_at' => $startedAt, 'expires_at' => $expiresAt]);
            $attempt->questions()->attach($questions->mapWithKeys(fn ($question, int $index): array => [$question->id => ['position' => $index + 1]])->all());

            return $attempt;
        }, 3);
    }
}
