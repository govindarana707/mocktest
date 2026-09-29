<?php

namespace App\Http\Controllers\Instructor;

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
        return view('instructor.dashboard', [
            'instructor' => $request->user(),
            'ownedExaminationCount' => $request->user()->examinations()->count(),
        ]);
    }
}
