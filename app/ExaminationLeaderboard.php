<?php

namespace App;

use App\Models\Examination;
use App\Models\ExaminationResult;
use Illuminate\Support\Collection;

class ExaminationLeaderboard
{
    public function resultsFor(Examination $examination): Collection
    {
        return ExaminationResult::query()->whereHas('examinationAttempt', fn ($query) => $query->where('examination_id', $examination->id)->whereNotNull('submitted_at')->whereNotNull('started_at'))->with('examinationAttempt.student:id,name')->get()->sort(function ($left, $right): int {
            $leftDuration = $left->examinationAttempt->submitted_at->diffInSeconds($left->examinationAttempt->started_at, true);
            $rightDuration = $right->examinationAttempt->submitted_at->diffInSeconds($right->examinationAttempt->started_at, true);

            return [$right->obtained_marks, $leftDuration, $left->graded_at->timestamp, $left->id] <=> [$left->obtained_marks, $rightDuration, $right->graded_at->timestamp, $right->id];
        })->values()->each(function ($result, $index): void {
            $result->setAttribute('rank', $index + 1);
            $result->setAttribute('completion_seconds', $result->examinationAttempt->submitted_at->diffInSeconds($result->examinationAttempt->started_at, true));
        });
    }
}
