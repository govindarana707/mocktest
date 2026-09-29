<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreQuestionRequest;
use App\Http\Requests\Instructor\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $instructor = $request->user();
        $assignedSubjectIds = $instructor->subjects()->pluck('subjects.id');
        $relevantSubjectIds = $instructor->questions()->distinct()->pluck('subject_id')->merge($assignedSubjectIds)->unique();
        $search = $request->string('search')->trim()->toString();

        return view('instructor.questions.index', [
            'questions' => $instructor->questions()
                ->with('subject:id,code,name')
                ->when($request->integer('subject_id'), fn ($query, int $subjectId) => $query->where('subject_id', $subjectId))
                ->when($search, fn ($query) => $query->where('question_text', 'like', "%{$search}%"))
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
        return view('instructor.questions.form', $this->formData($request->user(), new Question));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $request->user()->questions()->create($request->validated());

        return redirect()->route('instructor.questions.index')->with('success', 'Question added to your bank.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Question $question): View
    {
        $this->ensureManageable($request->user(), $question);

        return view('instructor.questions.form', $this->formData($request->user(), $question));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $question->update($request->validated());

        return redirect()->route('instructor.questions.index')->with('success', 'Question updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Question $question): RedirectResponse
    {
        $this->ensureManageable($request->user(), $question);

        if ($question->examinations()->exists()) {
            return back()->with('error', 'This question is assigned to an examination and cannot be deleted.');
        }

        $question->delete();

        return back()->with('success', 'Question deleted successfully.');
    }

    private function ensureManageable(User $instructor, Question $question): void
    {
        abort_unless($question->created_by === $instructor->id, 403);
        abort_unless($instructor->subjects()->where('subjects.id', $question->subject_id)->exists(), 403);
    }

    /** @return array{question: Question, subjects: Collection<int, Subject>} */
    private function formData(User $instructor, Question $question): array
    {
        return [
            'question' => $question,
            'subjects' => $instructor->subjects()->orderBy('name')->get(['subjects.id', 'subjects.code', 'subjects.name']),
        ];
    }
}
