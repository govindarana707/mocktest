<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateExaminationQuestionsRequest;
use App\Models\Examination;
use Illuminate\Http\JsonResponse;

class ExaminationQuestionController extends Controller
{
    public function update(UpdateExaminationQuestionsRequest $request, Examination $examination): JsonResponse
    {
        $assignments = collect($request->validated('question_ids'))
            ->values()
            ->mapWithKeys(fn (int $questionId, int $index) => [$questionId => ['position' => $index + 1]])
            ->all();

        $examination->questions()->sync($assignments);

        return response()->json([
            'message' => 'Question assignment saved successfully.',
            'question_count' => count($assignments),
        ]);
    }
}
