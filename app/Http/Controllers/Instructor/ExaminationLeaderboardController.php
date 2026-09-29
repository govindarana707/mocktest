<?php

namespace App\Http\Controllers\Instructor;

use App\ExaminationLeaderboard;
use App\Http\Controllers\Controller;
use App\Models\Examination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExaminationLeaderboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('instructor.leaderboards.index', [
            'examinations' => $request->user()
                ->examinations()
                ->with('subject:id,code,name')
                ->withCount([
                    'attempts as finalized_result_count' => fn ($query) => $query
                        ->whereNotNull('started_at')
                        ->whereNotNull('submitted_at')
                        ->has('result'),
                ])
                ->latest()
                ->paginate(12),
        ]);
    }

    public function show(Examination $examination, Request $request, ExaminationLeaderboard $leaderboard): View
    {
        abort_unless($examination->created_by === $request->user()->id, 404);

        $ranked = $leaderboard->resultsFor($examination);

        return view('instructor.examinations.leaderboard', [
            'examination' => $examination->load('subject:id,name,code'),
            'entries' => $ranked->take(10),
            'podium' => $ranked->take(3),
            'participants' => $ranked->count(),
        ]);
    }
}
