<?php

namespace App\Http\Controllers\Student;

use App\ExaminationLeaderboard;
use App\ExaminationStatus;
use App\Http\Controllers\Controller;
use App\Models\Examination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExaminationLeaderboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Examination $examination, Request $request, ExaminationLeaderboard $leaderboard): View
    {
        abort_unless($examination->status === ExaminationStatus::Published && (! $examination->starts_at || $examination->starts_at->isPast()), 404);
        $ranked = $leaderboard->resultsFor($examination);

        return view('student.examinations.leaderboard', ['examination' => $examination->load('subject:id,name,code'), 'entries' => $ranked->take(10), 'podium' => $ranked->take(3), 'ownResult' => $ranked->first(fn ($result) => $result->examinationAttempt->student_id === $request->user()->id), 'participants' => $ranked->count()]);
    }
}
