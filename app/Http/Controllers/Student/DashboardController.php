<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExaminationResult;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $student = $request->user();
        $completedFields = collect(['name', 'email', 'phone', 'date_of_birth', 'bio'])
            ->filter(fn (string $field) => filled($student->{$field}))
            ->count();

        $resultStatistics = ExaminationResult::query()
            ->whereHas('examinationAttempt', fn ($query) => $query->where('student_id', $student->id))
            ->selectRaw('count(*) as completed, coalesce(sum(passed = 1), 0) as passed, coalesce(sum(passed = 0), 0) as failed, coalesce(avg(percentage), 0) as average')
            ->first();

        return view('student.dashboard', [
            'student' => $student,
            'profileCompletion' => $completedFields * 20,
            'resultStatistics' => ['completed' => $resultStatistics->completed, 'passed' => $resultStatistics->passed, 'failed' => $resultStatistics->failed, 'average' => $resultStatistics->average],
            'recentResults' => ExaminationResult::whereHas('examinationAttempt', fn ($query) => $query->where('student_id', $student->id))->with('examinationAttempt.examination')->latest('graded_at')->limit(5)->get(),
        ]);
    }
}
