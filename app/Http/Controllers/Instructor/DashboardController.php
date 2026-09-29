<?php

namespace App\Http\Controllers\Instructor;

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
        $resultStatistics = ExaminationResult::query()
            ->whereHas('examinationAttempt.examination', fn ($query) => $query->where('created_by', $request->user()->id))
            ->selectRaw('count(*) as total, coalesce(sum(passed = 1), 0) as passed, coalesce(sum(passed = 0), 0) as failed')
            ->first();

        return view('instructor.dashboard', [
            'instructor' => $request->user(),
            'ownedExaminationCount' => $request->user()->examinations()->count(),
            'resultStatistics' => [
                'total' => $resultStatistics->total,
                'passed' => $resultStatistics->passed,
                'failed' => $resultStatistics->failed,
            ],
        ]);
    }
}
