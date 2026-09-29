<?php

namespace App\Http\Controllers\Admin;

use App\ExaminationStatus;
use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        $resultStatistics = ExaminationResult::query()
            ->selectRaw('count(*) as total, coalesce(sum(passed = 1), 0) as passed, coalesce(sum(passed = 0), 0) as failed')
            ->first();

        return view('admin.dashboard', [
            'statistics' => [
                'students' => User::where('role', UserRole::Student)->count(),
                'newStudents' => User::where('role', UserRole::Student)->where('created_at', '>=', now()->startOfMonth())->count(),
                'administrators' => User::where('role', UserRole::Admin)->count(),
                'instructors' => User::where('role', UserRole::Instructor)->count(),
                'activeSessions' => DB::table('sessions')->whereNotNull('user_id')->count(),
                'subjects' => Subject::count(),
                'questions' => Question::count(),
                'examinations' => Examination::count(),
                'publishedExaminations' => Examination::where('status', ExaminationStatus::Published)->count(),
                'results' => $resultStatistics->total,
                'passedResults' => $resultStatistics->passed,
                'failedResults' => $resultStatistics->failed,
                'passRate' => $resultStatistics->total ? round($resultStatistics->passed / $resultStatistics->total * 100, 1) : 0,
            ],
            'recentStudents' => User::where('role', UserRole::Student)->latest()->limit(5)->get(),
            'recentResults' => ExaminationResult::with('examinationAttempt.student', 'examinationAttempt.examination')->latest('graded_at')->limit(5)->get(),
        ]);
    }
}
