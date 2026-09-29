<?php

namespace App\Http\Controllers\Instructor;

use App\ExaminationResultAnalytics;
use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, ExaminationResultAnalytics $analytics): View
    {
        $filters = $request->validate([
            'examination_id' => ['nullable', 'integer', 'exists:examinations,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'passed' => ['nullable', 'in:0,1'],
        ]);

        $instructorId = $request->user()->id;
        $results = $this->filteredResults($instructorId, $filters);
        $summary = $analytics->summary($results);

        return view('instructor.analytics.index', [
            'filters' => $filters,
            'summary' => $summary,
            'scoreDistribution' => $analytics->scoreDistribution($results),
            'examinationPerformance' => $analytics->examinationPerformance($results),
            'subjectPerformance' => $analytics->subjectPerformance($results),
            'trend' => $analytics->trend($results),
            'examinations' => Examination::query()
                ->where('created_by', $instructorId)
                ->whereHas('attempts.result')
                ->orderBy('title')
                ->get(['id', 'title']),
            'subjects' => Subject::query()
                ->whereHas('examinations', fn (EloquentBuilder $query) => $query
                    ->where('created_by', $instructorId)
                    ->whereHas('attempts.result'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function filteredResults(int $instructorId, array $filters): Builder
    {
        return DB::table('examination_results as results')
            ->join('examination_attempts as attempts', 'attempts.id', '=', 'results.examination_attempt_id')
            ->join('examinations', 'examinations.id', '=', 'attempts.examination_id')
            ->join('subjects', 'subjects.id', '=', 'examinations.subject_id')
            ->where('examinations.created_by', $instructorId)
            ->whereNotNull('attempts.submitted_at')
            ->when($filters['examination_id'] ?? null, fn (Builder $query, int $id) => $query->where('attempts.examination_id', $id))
            ->when($filters['subject_id'] ?? null, fn (Builder $query, int $id) => $query->where('examinations.subject_id', $id))
            ->when(array_key_exists('passed', $filters) && $filters['passed'] !== null, fn (Builder $query) => $query->where('results.passed', $filters['passed']));
    }
}
