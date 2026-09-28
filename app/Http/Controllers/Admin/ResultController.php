<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $results = ExaminationResult::query()->with('examinationAttempt.student', 'examinationAttempt.examination.subject')->when($request->filled('search'), fn ($query) => $query->whereHas('examinationAttempt.student', fn ($student) => $student->where('name', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%')))->when($request->integer('examination_id'), fn ($query, $id) => $query->whereHas('examinationAttempt', fn ($attempt) => $attempt->where('examination_id', $id)))->when($request->integer('subject_id'), fn ($query, $id) => $query->whereHas('examinationAttempt.examination', fn ($exam) => $exam->where('subject_id', $id)))->when(in_array($request->input('passed'), ['0', '1'], true), fn ($query) => $query->where('passed', $request->boolean('passed')))->latest('graded_at')->paginate(15)->withQueryString();

        return view('admin.results.index', ['results' => $results, 'examinations' => Examination::orderBy('title')->get(['id', 'title']), 'subjects' => Subject::orderBy('name')->get(['id', 'name'])]);
    }

    public function show(ExaminationResult $result): View
    {
        $result->load('examinationAttempt.student', 'examinationAttempt.examination.subject');

        return view('admin.results.show', compact('result'));
    }
}
