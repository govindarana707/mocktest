<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $instructor = $request->user();
        $search = $request->string('search')->trim()->toString();

        $results = $this->ownedResults($instructor->id)
            ->with([
                'examinationAttempt:id,examination_id,student_id,started_at,submitted_at,submission_reason',
                'examinationAttempt.student:id,name',
                'examinationAttempt.examination:id,subject_id,title,created_by',
                'examinationAttempt.examination.subject:id,name',
            ])
            ->when($search, fn (Builder $query) => $query->whereHas('examinationAttempt.student', fn (Builder $student) => $student->where('name', 'like', "%{$search}%")))
            ->when($request->integer('examination_id'), fn (Builder $query, int $id) => $query->whereHas('examinationAttempt', fn (Builder $attempt) => $attempt->where('examination_id', $id)))
            ->when($request->integer('subject_id'), fn (Builder $query, int $id) => $query->whereHas('examinationAttempt.examination', fn (Builder $examination) => $examination->where('subject_id', $id)))
            ->when(in_array($request->input('passed'), ['0', '1'], true), fn (Builder $query) => $query->where('passed', $request->boolean('passed')))
            ->latest('graded_at')
            ->paginate(15)
            ->withQueryString();

        return view('instructor.results.index', [
            'results' => $results,
            'examinations' => Examination::query()
                ->where('created_by', $instructor->id)
                ->whereHas('attempts.result')
                ->orderBy('title')
                ->get(['id', 'title']),
            'subjects' => Subject::query()
                ->whereHas('examinations', fn (Builder $examination) => $examination
                    ->where('created_by', $instructor->id)
                    ->whereHas('attempts.result'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function show(ExaminationResult $result, Request $request): View
    {
        $result = $this->ownedResults($request->user()->id)
            ->with([
                'examinationAttempt:id,examination_id,student_id,started_at,submitted_at,submission_reason',
                'examinationAttempt.student:id,name',
                'examinationAttempt.examination:id,subject_id,title,created_by,duration_minutes',
                'examinationAttempt.examination.subject:id,name',
            ])
            ->findOrFail($result->id);

        return view('instructor.results.show', compact('result'));
    }

    /** @return Builder<ExaminationResult> */
    private function ownedResults(int $instructorId): Builder
    {
        return ExaminationResult::query()
            ->whereHas('examinationAttempt.examination', fn (Builder $examination) => $examination->where('created_by', $instructorId));
    }
}
