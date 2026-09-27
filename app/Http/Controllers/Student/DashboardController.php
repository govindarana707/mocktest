<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
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
        ]);
    }
}
