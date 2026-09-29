<x-layouts.app :title="$examination->title">
    @php($formatDuration = static function (int $seconds): string { $hours = intdiv($seconds, 3600); $minutes = intdiv($seconds % 3600, 60); $remainingSeconds = $seconds % 60; return $hours > 0 ? sprintf('%dh %02dm %02ds', $hours, $minutes, $remainingSeconds) : ($minutes > 0 ? sprintf('%dm %02ds', $minutes, $remainingSeconds) : $remainingSeconds.'s'); })
    <div class="mx-auto max-w-5xl">
        <p class="text-sm font-semibold text-brand-600">{{ $examination->subject->name }} · {{ $participants }} participants</p><h2 class="mt-2 text-3xl font-bold">Leaderboard</h2>
        @if ($podium->isNotEmpty())
            <section class="mt-6 grid gap-4 sm:grid-cols-3" aria-label="Top three performers">
                @foreach ($podium as $entry)
                    <article @class(['surface p-5 text-center', 'border-2 border-amber-400 bg-amber-50/60 dark:bg-amber-950/30 sm:-translate-y-2' => $entry->rank === 1, 'border border-slate-300 dark:border-slate-700' => $entry->rank !== 1])><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-100 text-lg font-black text-brand-700 dark:bg-brand-500/20 dark:text-brand-200">#{{ $entry->rank }}</span><h3 class="mt-4 truncate font-bold">{{ $entry->examinationAttempt->student->name }}</h3><p class="mt-2 text-sm text-slate-500">{{ $entry->obtained_marks }} / {{ $entry->maximum_marks }} · {{ $entry->percentage }}%</p><p class="mt-1 text-xs text-slate-400">{{ $formatDuration($entry->completion_seconds) }}</p></article>
                @endforeach
            </section>
        @endif
        @if ($ownResult)<section class="surface mt-6 p-6"><p class="text-sm text-slate-500">Your Rank</p><p class="mt-1 text-3xl font-bold">#{{ $ownResult->rank }} of {{ $participants }}</p><p class="mt-2 text-sm">{{ $ownResult->obtained_marks }} / {{ $ownResult->maximum_marks }} · {{ $ownResult->percentage }}% · {{ $formatDuration($ownResult->completion_seconds) }}</p></section>@endif
        <section class="surface mt-6 overflow-hidden"><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead><tr><th class="p-4 text-left">Rank</th><th class="p-4 text-left">Student</th><th class="p-4 text-left">Score</th><th class="p-4 text-left">Percentage</th><th class="p-4 text-left">Completion</th></tr></thead><tbody>@forelse($entries as $entry)<tr @class(['border-t dark:border-slate-800', 'bg-brand-50 dark:bg-brand-950/20' => $ownResult?->id === $entry->id])><td class="p-4 font-bold">#{{ $entry->rank }}</td><td class="p-4">{{ $entry->examinationAttempt->student->name }}</td><td class="p-4">{{ $entry->obtained_marks }} / {{ $entry->maximum_marks }}</td><td class="p-4">{{ $entry->percentage }}%</td><td class="p-4">{{ $formatDuration($entry->completion_seconds) }}</td></tr>@empty<tr><td colspan="5" class="p-10 text-center text-slate-500">No finalized results are available yet.</td></tr>@endforelse</tbody></table></div></section>
    </div>
</x-layouts.app>
