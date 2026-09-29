<?php

namespace App;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExaminationResultAnalytics
{
    public function summary(Builder $results): array
    {
        $statistics = (clone $results)
            ->selectRaw('count(*) as total_results')
            ->selectRaw('count(distinct attempts.student_id) as unique_students')
            ->selectRaw('count(distinct attempts.examination_id) as examinations_with_results')
            ->selectRaw('coalesce(sum(case when results.passed = 1 then 1 else 0 end), 0) as passed_results')
            ->selectRaw('coalesce(sum(case when results.passed = 0 then 1 else 0 end), 0) as failed_results')
            ->selectRaw('coalesce(avg(results.percentage), 0) as average_percentage')
            ->first();

        $totalResults = (int) $statistics->total_results;
        $passedResults = (int) $statistics->passed_results;

        return [
            'totalResults' => $totalResults,
            'uniqueStudents' => (int) $statistics->unique_students,
            'examinationsWithResults' => (int) $statistics->examinations_with_results,
            'passedResults' => $passedResults,
            'failedResults' => (int) $statistics->failed_results,
            'passRate' => $totalResults === 0 ? 0.0 : round($passedResults / $totalResults * 100, 1),
            'averagePercentage' => round((float) $statistics->average_percentage, 1),
        ];
    }

    public function scoreDistribution(Builder $results): Collection
    {
        $buckets = [
            'Below 0%' => 0,
            '0–39%' => 0,
            '40–49%' => 0,
            '50–59%' => 0,
            '60–69%' => 0,
            '70–79%' => 0,
            '80–89%' => 0,
            '90–100%' => 0,
            'Above 100%' => 0,
        ];

        $distribution = (clone $results)
            ->selectRaw("case when results.percentage < 0 then 'Below 0%' when results.percentage <= 39 then '0–39%' when results.percentage <= 49 then '40–49%' when results.percentage <= 59 then '50–59%' when results.percentage <= 69 then '60–69%' when results.percentage <= 79 then '70–79%' when results.percentage <= 89 then '80–89%' when results.percentage <= 100 then '90–100%' else 'Above 100%' end as bucket")
            ->selectRaw('count(*) as result_count')
            ->groupBy('bucket')
            ->get()
            ->mapWithKeys(fn (object $row) => [$row->bucket => (int) $row->result_count]);

        return collect($buckets)->map(fn (int $count, string $bucket) => [
            'label' => $bucket,
            'count' => $distribution->get($bucket, $count),
        ])->values();
    }

    public function examinationPerformance(Builder $results): Collection
    {
        return (clone $results)
            ->select('examinations.id', 'examinations.title as examination_title', 'subjects.name as subject_name')
            ->selectRaw('count(*) as result_count')
            ->selectRaw('coalesce(avg(results.percentage), 0) as average_percentage')
            ->selectRaw('coalesce(sum(case when results.passed = 1 then 1 else 0 end), 0) as passed_results')
            ->selectRaw('coalesce(sum(case when results.passed = 0 then 1 else 0 end), 0) as failed_results')
            ->groupBy('examinations.id', 'examinations.title', 'subjects.name')
            ->orderBy('examinations.title')
            ->get()
            ->map(fn (object $row) => [
                'title' => $row->examination_title,
                'subject' => $row->subject_name,
                'resultCount' => (int) $row->result_count,
                'averagePercentage' => round((float) $row->average_percentage, 1),
                'passedResults' => (int) $row->passed_results,
                'failedResults' => (int) $row->failed_results,
                'passRate' => round((int) $row->passed_results / (int) $row->result_count * 100, 1),
            ]);
    }

    public function subjectPerformance(Builder $results): Collection
    {
        return (clone $results)
            ->select('subjects.id', 'subjects.name as subject_name')
            ->selectRaw('count(*) as result_count')
            ->selectRaw('count(distinct attempts.student_id) as unique_students')
            ->selectRaw('coalesce(avg(results.percentage), 0) as average_percentage')
            ->selectRaw('coalesce(sum(case when results.passed = 1 then 1 else 0 end), 0) as passed_results')
            ->groupBy('subjects.id', 'subjects.name')
            ->orderBy('subjects.name')
            ->get()
            ->map(fn (object $row) => [
                'name' => $row->subject_name,
                'resultCount' => (int) $row->result_count,
                'uniqueStudents' => (int) $row->unique_students,
                'averagePercentage' => round((float) $row->average_percentage, 1),
                'passRate' => round((int) $row->passed_results / (int) $row->result_count * 100, 1),
            ]);
    }

    public function trend(Builder $results): Collection
    {
        $period = match (DB::getDriverName()) {
            'sqlite' => "strftime('%Y-%m', results.graded_at)",
            'pgsql' => "to_char(results.graded_at, 'YYYY-MM')",
            default => "date_format(results.graded_at, '%Y-%m')",
        };

        return (clone $results)
            ->selectRaw("{$period} as period")
            ->selectRaw('coalesce(avg(results.percentage), 0) as average_percentage')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn (object $row) => [
                'label' => $row->period,
                'averagePercentage' => round((float) $row->average_percentage, 1),
            ]);
    }
}
