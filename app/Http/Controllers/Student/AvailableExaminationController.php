<?php

namespace App\Http\Controllers\Student;

use App\ExaminationStatus;
use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Models\ExaminationCategory;
use App\Models\Subject;
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
            ->select(['id', 'subject_id', 'category_id', 'title', 'description', 'duration_minutes', 'passing_percentage', 'starts_at', 'ends_at'])
            ->with(['subject:id,code,name', 'category:id,name,slug,is_active'])
            ->where('status', ExaminationStatus::Published)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->when($request->integer('subject_id'), fn ($query, int $subjectId) => $query->where('subject_id', $subjectId))
            ->when($request->filled('category'), fn ($query) => $query->whereHas('category', fn ($query) => $query->where('is_active', true)->where('slug', $request->string('category')->toString())))
            ->orderBy('starts_at')
            ->paginate(12)
            ->withQueryString();

        $categories = ExaminationCategory::query()
            ->where('is_active', true)
            ->whereHas('examinations', fn ($query) => $query
                ->where('status', ExaminationStatus::Published)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->when($request->integer('subject_id'), fn ($query, int $subjectId) => $query->where('subject_id', $subjectId)))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $subjects = Subject::query()
            ->whereHas('examinations', fn ($query) => $query
                ->where('status', ExaminationStatus::Published)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now())))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('student.examinations.index', compact('examinations', 'categories', 'subjects'));
    }
}
