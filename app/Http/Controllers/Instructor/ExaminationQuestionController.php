<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateExaminationQuestionsRequest;
use App\Models\Examination;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExaminationQuestionController extends Controller
{
    public function update(UpdateExaminationQuestionsRequest $request, Examination $examination): JsonResponse
    {
        $questionCount = DB::transaction(function () use ($request, $examination): int {
            $lockedExamination = Examination::query()->lockForUpdate()->findOrFail($examination->id);
            $instructor = $request->user();

            abort_unless($lockedExamination->created_by === $instructor->id, 403);
            abort_unless($instructor->subjects()->where('subjects.id', $lockedExamination->subject_id)->exists(), 403);

            if ($lockedExamination->attempts()->exists()) {
                throw ValidationException::withMessages([
                    'question_ids' => 'Question assignments cannot be changed after a student attempt has started.',
                ]);
            }

            $preservedHistoricalQuestionIds = $lockedExamination->questions()
                ->whereNotIn('questions.id', Question::query()
                    ->eligibleForInstructor($instructor, $lockedExamination)
                    ->select('id'))
                ->pluck('questions.id');
            $questionIds = collect($request->validated('question_ids', []))
                ->map(fn (mixed $questionId): int => (int) $questionId)
                ->merge($preservedHistoricalQuestionIds)
                ->unique()
                ->values();
            $assignments = $questionIds
                ->mapWithKeys(fn (int $questionId, int $index) => [$questionId => ['position' => $index + 1]])
                ->all();

            DB::table('examination_question')
                ->where('examination_id', $lockedExamination->id)
                ->increment('position', 1_000_000);
            $lockedExamination->questions()->sync($assignments);

            return count($assignments);
        }, 3);

        return response()->json([
            'message' => 'Question assignment saved successfully.',
            'question_count' => $questionCount,
        ]);
    }
}
