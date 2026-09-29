<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExaminationRequest;
use App\Http\Requests\Admin\UpdateExaminationRequest;
use App\Models\Examination;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExaminationController extends Controller
{
    public function index(Request $request): View
    {
        $examinations = Examination::query()
            ->with(['subject', 'creator:id,name,role'])
            ->withCount('questions')
            ->when($request->integer('subject_id'), fn ($query, int $subjectId) => $query->where('subject_id', $subjectId))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.examinations.index', [
            'examinations' => $examinations,
            'subjects' => Subject::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.examinations.form', $this->formData(new Examination));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExaminationRequest $request): RedirectResponse
    {
        $examination = $request->user()->examinations()->create($request->validated());

        return redirect()->route('admin.examinations.edit', $examination)->with('success', 'Draft examination created. Assign questions before publishing it.');
    }

    /**
     * Display the specified resource.
     */
    public function edit(Examination $examination): View
    {
        return view('admin.examinations.form', $this->formData($examination));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExaminationRequest $request, Examination $examination): RedirectResponse
    {
        $examination->update($request->validated());

        return redirect()->route('admin.examinations.edit', $examination)->with('success', 'Examination details updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Examination $examination): RedirectResponse
    {
        if ($examination->attempts()->exists()) {
            return back()->with('error', 'This examination has student attempts and cannot be deleted.');
        }

        $examination->delete();

        return redirect()->route('admin.examinations.index')->with('success', 'Examination and its assignments were deleted.');
    }

    /** @return array{examination: Examination, subjects: Collection<int, Subject>, questions: Collection<int, Question>, assignedQuestionIds: array<int, int>} */
    private function formData(Examination $examination): array
    {
        if ($examination->exists) {
            $examination->load('subject');
        }

        $questions = $examination->exists
            ? Question::query()->where('subject_id', $examination->subject_id)->orderBy('id')->get()
            : collect();

        return [
            'examination' => $examination,
            'subjects' => Subject::orderBy('name')->get(['id', 'code', 'name']),
            'questions' => $questions,
            'assignedQuestionIds' => $examination->exists
                ? $examination->questions()->pluck('questions.id')->all()
                : [],
        ];
    }
}
