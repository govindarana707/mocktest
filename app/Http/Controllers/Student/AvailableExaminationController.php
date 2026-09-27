<?php

namespace App\Http\Controllers\Student;

use App\ExaminationStatus;
use App\Http\Controllers\Controller;
use App\Models\Examination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvailableExaminationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $examinations = Examination::query()
            ->select(['id', 'subject_id', 'title', 'description', 'duration_minutes', 'passing_percentage', 'starts_at', 'ends_at'])
            ->with('subject:id,code,name')
            ->where('status', ExaminationStatus::Published)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('starts_at')
            ->paginate(12);

        return view('student.examinations.index', compact('examinations'));
    }
}
