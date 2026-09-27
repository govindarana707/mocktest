<?php

namespace App\Http\Controllers\Admin;

use App\ExaminationStatus;
use App\Http\Controllers\Controller;
use App\Models\Examination;
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
        return view('admin.dashboard', [
            'statistics' => [
                'students' => User::where('role', UserRole::Student)->count(),
                'newStudents' => User::where('role', UserRole::Student)->where('created_at', '>=', now()->startOfMonth())->count(),
                'administrators' => User::where('role', UserRole::Admin)->count(),
                'activeSessions' => DB::table('sessions')->whereNotNull('user_id')->count(),
                'subjects' => Subject::count(),
                'questions' => Question::count(),
                'examinations' => Examination::count(),
                'publishedExaminations' => Examination::where('status', ExaminationStatus::Published)->count(),
            ],
            'recentStudents' => User::where('role', UserRole::Student)->latest()->limit(5)->get(),
        ]);
    }
}
