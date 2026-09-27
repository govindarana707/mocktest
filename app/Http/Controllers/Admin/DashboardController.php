<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            ],
            'recentStudents' => User::where('role', UserRole::Student)->latest()->limit(5)->get(),
        ]);
    }
}
