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

        return view('student.dashboard', [
            'student' => $student,
            'profileCompletion' => $completedFields * 20,
            'resultStatistics' => ['completed' => ExaminationResult::whereHas('examinationAttempt', fn ($query) => $query->where('student_id', $student->id))->count(), 'passed' => ExaminationResult::whereHas('examinationAttempt', fn ($query) => $query->where('student_id', $student->id))->where('passed', true)->count(), 'failed' => ExaminationResult::whereHas('examinationAttempt', fn ($query) => $query->where('student_id', $student->id))->where('passed', false)->count(), 'average' => ExaminationResult::whereHas('examinationAttempt', fn ($query) => $query->where('student_id', $student->id))->avg('percentage') ?? 0],
            'recentResults' => ExaminationResult::whereHas('examinationAttempt', fn ($query) => $query->where('student_id', $student->id))->with('examinationAttempt.examination')->latest('graded_at')->limit(5)->get(),
        ]);
    }
}
