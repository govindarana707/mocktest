<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SaveAttemptAnswerRequest;
use App\Models\AttemptAnswer;
use App\Models\Examination;
use App\Models\ExaminationAttempt;
use App\Models\Question;
use App\StartExaminationAttempt;
use App\SubmitExaminationAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExaminationAttemptController extends Controller
{
    public function instructions(Examination $examination, Request $request): View|RedirectResponse
    {
        $attempt = ExaminationAttempt::query()->where('examination_id', $examination->id)->where('student_id', $request->user()->id)->first();

        if ($attempt) {
            return $attempt->isSubmitted() ? redirect()->route('student.my-exams.index')->with('success', 'You have already submitted this examination.') : redirect()->route('student.attempts.show', $attempt);
        }

        abort_unless($examination->isAvailableNow() && $examination->questions()->exists(), 404);

        return view('student.examinations.instructions', compact('examination'));
    }

    public function start(Examination $examination, Request $request, StartExaminationAttempt $starter): RedirectResponse
    {
        $attempt = $starter->handle($examination, $request->user());

        return $attempt->isSubmitted() ? redirect()->route('student.my-exams.index')->with('success', 'You have already submitted this examination.') : redirect()->route('student.attempts.show', $attempt);
    }

    public function index(Request $request): View
    {
        $attempts = ExaminationAttempt::query()->where('student_id', $request->user()->id)->with('examination.subject')->latest('started_at')->paginate(12);

        return view('student.attempts.index', compact('attempts'));
    }

    public function show(ExaminationAttempt $attempt, Request $request, SubmitExaminationAttempt $submitter): View|RedirectResponse
    {
        $attempt = $this->studentAttempt($attempt, $request);

        if ($attempt->isSubmitted()) {
            return redirect()->route('student.my-exams.index')->with('success', 'This examination has already been submitted.');
        }

        if ($attempt->hasExpired()) {
            $submitter->handle($attempt, 'timeout');

            return redirect()->route('student.my-exams.index')->with('success', 'Time expired and your examination was submitted.');
        }

        $attempt->load('examination:id,title,duration_minutes', 'answers:id,examination_attempt_id,question_id,selected_option,version');
        $questions = $attempt->questions()->select(['questions.id', 'questions.question_text', 'questions.option_a', 'questions.option_b', 'questions.option_c', 'questions.option_d'])->get();

        return view('student.attempts.show', compact('attempt', 'questions'));
    }

    public function saveAnswer(SaveAttemptAnswerRequest $request, ExaminationAttempt $attempt, Question $question, SubmitExaminationAttempt $submitter): JsonResponse
    {
        $attempt = $this->studentAttempt($attempt, $request);

        if ($attempt->isSubmitted() || $attempt->hasExpired()) {
            if (! $attempt->isSubmitted()) {
                $submitter->handle($attempt, 'timeout');
            }

            return response()->json(['message' => 'This examination is no longer accepting answers.'], 409);
        }

        abort_unless($attempt->questions()->whereKey($question->id)->exists(), 404);

        return DB::transaction(function () use ($request, $attempt, $question): JsonResponse {
            $lockedAttempt = ExaminationAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($lockedAttempt->isSubmitted() || $lockedAttempt->hasExpired()) {
                return response()->json(['message' => 'This examination is no longer accepting answers.'], 409);
            }

            $answer = AttemptAnswer::query()->where('examination_attempt_id', $lockedAttempt->id)->where('question_id', $question->id)->lockForUpdate()->first();
            $version = $answer?->version ?? 0;

            if ($request->integer('version') !== $version) {
                return response()->json(['message' => 'A newer answer was already saved.', 'selected_option' => $answer?->selected_option, 'version' => $version], 409);
            }

            $answer ??= new AttemptAnswer(['examination_attempt_id' => $lockedAttempt->id, 'question_id' => $question->id]);
            $answer->fill(['selected_option' => $request->validated('selected_option'), 'version' => $version + 1])->save();

            return response()->json(['message' => 'Answer saved.', 'selected_option' => $answer->selected_option, 'version' => $answer->version]);
        }, 3);
    }

    public function submit(ExaminationAttempt $attempt, Request $request, SubmitExaminationAttempt $submitter): JsonResponse|RedirectResponse
    {
        $attempt = $submitter->handle($this->studentAttempt($attempt, $request), 'manual');

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Examination submitted.', 'submitted_at' => $attempt->submitted_at?->toIso8601String(), 'reason' => $attempt->submission_reason]);
        }

        return redirect()->route('student.my-exams.index')->with('success', 'Examination submitted. Results will be available in a later phase.');
    }

    private function studentAttempt(ExaminationAttempt $attempt, Request $request): ExaminationAttempt
    {
        abort_unless($attempt->student_id === $request->user()->id, 404);

        return $attempt;
    }
}
