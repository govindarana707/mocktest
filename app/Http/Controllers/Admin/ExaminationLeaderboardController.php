<?php

namespace App\Http\Controllers\Admin;

use App\ExaminationLeaderboard;
use App\Http\Controllers\Controller;
use App\Models\Examination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExaminationLeaderboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Examination $examination, ExaminationLeaderboard $leaderboard): View
    {
        $ranked = $leaderboard->resultsFor($examination);

        return view('admin.examinations.leaderboard', ['examination' => $examination->load('subject:id,name,code'), 'entries' => $ranked->take(10), 'podium' => $ranked->take(3), 'participants' => $ranked->count()]);
    }
}
