<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExaminationResult;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $results = ExaminationResult::query()->whereHas('examinationAttempt', fn ($query) => $query->where('student_id', $request->user()->id))->with('examinationAttempt.examination.subject')->latest('graded_at')->paginate(12);

        return view('student.results.index', compact('results'));
    }

    public function show(ExaminationResult $result, Request $request): View
    {
        $result->load('examinationAttempt.examination.subject');
        abort_unless($result->examinationAttempt->student_id === $request->user()->id, 404);

        return view('student.results.show', compact('result'));
    }

    public function review(ExaminationResult $result, Request $request): View
    {
        $result->load(['examinationAttempt.examination', 'examinationAttempt.answers', 'examinationAttempt.questions']);
        abort_unless($result->examinationAttempt->student_id === $request->user()->id && $result->examinationAttempt->isSubmitted() && $result->examinationAttempt->examination->allow_answer_review, 404);

        return view('student.results.review', compact('result'));
    }
}
