<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        $questions = Question::query()
            ->with(['subject', 'creator:id,name,role'])
            ->when($request->integer('subject_id'), fn ($query, int $subjectId) => $query->where('subject_id', $subjectId))
            ->when($request->filled('search'), fn ($query) => $query->where('question_text', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.questions.index', [
            'questions' => $questions,
            'subjects' => Subject::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.questions.form', $this->formData(new Question));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $request->user()->questions()->create($request->validated());

        return redirect()->route('admin.questions.index')->with('success', 'Question added to the bank.');
    }

    /**
     * Display the specified resource.
     */
    public function edit(Question $question): View
    {
        return view('admin.questions.form', $this->formData($question));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $question->update($request->validated());

        return redirect()->route('admin.questions.index')->with('success', 'Question updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question): RedirectResponse
    {
        if ($question->examinations()->exists()) {
            return back()->with('error', 'This question is assigned to an examination and cannot be deleted.');
        }

        $question->delete();

        return back()->with('success', 'Question deleted successfully.');
    }

    /** @return array{question: Question, subjects: Collection<int, Subject>} */
    private function formData(Question $question): array
    {
        return ['question' => $question, 'subjects' => Subject::orderBy('name')->get(['id', 'code', 'name'])];
    }
}
