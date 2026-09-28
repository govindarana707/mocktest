<?php

namespace App;

use App\Models\ExaminationAttempt;
use App\Models\ExaminationResult;
use Illuminate\Support\Facades\DB;
use LogicException;

class GradeExaminationAttempt
{
    public function handle(ExaminationAttempt $attempt): ExaminationResult
    {
        return DB::transaction(function () use ($attempt): ExaminationResult {
            $attempt = ExaminationAttempt::query()->lockForUpdate()->with(['answers', 'result', 'questions'])->findOrFail($attempt->id);
            if (! $attempt->isSubmitted()) {
                throw new LogicException('Only finalized attempts may be graded.');
            }
            if ($attempt->result) {
                return $attempt->result;
            }
            if (! $attempt->passing_percentage_snapshot || $attempt->questions->contains(fn ($question): bool => ! $question->pivot->correct_option_snapshot || $question->pivot->marks_snapshot === null)) {
                throw new LogicException('This legacy attempt has no immutable grading snapshot.');
            }
            $answers = $attempt->answers->keyBy('question_id');
            $total = $attempt->questions->count();
            $answered = 0;
            $correct = 0;
            $marks = 0.0;
            $maximum = 0.0;
            foreach ($attempt->questions as $question) {
                $snapshot = $question->pivot;
                $maximum += (float) $snapshot->marks_snapshot;
                $selected = $answers->get($question->id)?->selected_option;
                if ($selected !== null) {
                    $answered++;
                } if ($selected === $snapshot->correct_option_snapshot) {
                    $correct++;
                    $marks += (float) $snapshot->marks_snapshot;
                }
            }
            $percentage = $maximum > 0 ? round(($marks / $maximum) * 100, 2) : 0;

            return ExaminationResult::create(['examination_attempt_id' => $attempt->id, 'total_questions' => $total, 'answered_questions' => $answered, 'correct_answers' => $correct, 'incorrect_answers' => $answered - $correct, 'unanswered_questions' => $total - $answered, 'maximum_marks' => $maximum, 'obtained_marks' => $marks, 'percentage' => $percentage, 'passing_percentage' => $attempt->passing_percentage_snapshot, 'passed' => $percentage >= $attempt->passing_percentage_snapshot, 'graded_at' => now()]);
        }, 3);
    }
}
