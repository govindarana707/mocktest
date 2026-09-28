<?php

namespace App;

use App\Models\ExaminationAttempt;
use Illuminate\Support\Facades\DB;

class SubmitExaminationAttempt
{
    public function handle(ExaminationAttempt $attempt, string $reason): ExaminationAttempt
    {
        return DB::transaction(function () use ($attempt, $reason): ExaminationAttempt {
            $lockedAttempt = ExaminationAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if (! $lockedAttempt->isSubmitted()) {
                $lockedAttempt->update(['submitted_at' => now(), 'submission_reason' => $lockedAttempt->hasExpired() ? 'timeout' : $reason]);
            }

            if ($lockedAttempt->passing_percentage_snapshot !== null) {
                app(GradeExaminationAttempt::class)->handle($lockedAttempt);
            }

            return $lockedAttempt;
        }, 3);
    }
}
