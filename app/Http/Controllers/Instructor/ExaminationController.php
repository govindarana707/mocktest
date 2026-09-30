<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreExaminationRequest;
use App\Http\Requests\Instructor\UpdateExaminationRequest;
use App\Models\Examination;
use App\Models\ExaminationCategory;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExaminationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $instructor = $request->user();
        $assignedSubjectIds = $instructor->subjects()->pluck('subjects.id');
        $relevantSubjectIds = $instructor->examinations()->distinct()->pluck('subject_id')->merge($assignedSubjectIds)->unique();
        $search = $request->string('search')->trim()->toString();

        return view('instructor.examinations.index', [
            'examinations' => $instructor->examinations()
                ->with(['subject:id,code,name', 'category:id,name,is_active'])
                ->withCount('questions')
                ->when($request->integer('subject_id'), fn ($query, int $subjectId) => $query->where('subject_id', $subjectId))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
                ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'subjects' => Subject::query()->whereIn('id', $relevantSubjectIds)->orderBy('name')->get(['id', 'code', 'name']),
            'assignedSubjectIds' => $assignedSubjectIds,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        return view('instructor.examinations.form', $this->formData($request->user(), new Examination));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExaminationRequest $request): RedirectResponse
    {
        $request->user()->examinations()->create($request->validated());

        return redirect()->route('instructor.examinations.index')->with('success', 'Draft examination created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Examination $examination): View
    {
        $this->ensureManageable($request->user(), $examination);

        return view('instructor.examinations.form', $this->formData($request->user(), $examination));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExaminationRequest $request, Examination $examination): RedirectResponse
    {
        $examination->update($request->validated());

        return redirect()->route('instructor.examinations.edit', $examination)->with('success', 'Examination details updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Examination $examination): RedirectResponse
    {
        $this->ensureManageable($request->user(), $examination);

        if ($examination->attempts()->exists()) {
            return back()->with('error', 'This examination has student attempts and cannot be deleted.');
        }

        $examination->delete();

        return redirect()->route('instructor.examinations.index')->with('success', 'Examination deleted successfully.');
    }

    private function ensureManageable(User $instructor, Examination $examination): void
    {
        abort_unless($examination->created_by === $instructor->id, 403);
        abort_unless($instructor->subjects()->where('subjects.id', $examination->subject_id)->exists(), 403);
    }

    /** @return array{examination: Examination, subjects: Collection<int, Subject>, categories: Collection<int, ExaminationCategory>, questions: Collection<int, Question>, assignedQuestionIds: array<int, int>, assignmentLocked: bool} */
    private function formData(User $instructor, Examination $examination): array
    {
        if ($examination->exists) {
            $examination->load(['subject:id,name', 'category:id,name,is_active']);
        }

        return [
            'examination' => $examination,
            'subjects' => $instructor->subjects()->orderBy('name')->get(['subjects.id', 'subjects.code', 'subjects.name']),
            'categories' => ExaminationCategory::query()
                ->where(fn ($query) => $query->where('is_active', true)->orWhereKey($examination->category_id))
                ->orderBy('name')
                ->get(['id', 'name', 'is_active']),
            'questions' => $examination->exists
                ? Question::query()
                    ->eligibleForInstructor($instructor, $examination)
                    ->with('creator:id,name,role')
                    ->orderBy('id')
                    ->get(['id', 'subject_id', 'created_by', 'question_text'])
                : collect(),
            'assignedQuestionIds' => $examination->exists
                ? $examination->questions()->pluck('questions.id')->all()
                : [],
            'assignmentLocked' => $examination->exists && $examination->attempts()->exists(),
        ];
    }
}
