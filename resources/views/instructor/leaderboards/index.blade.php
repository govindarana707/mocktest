<x-layouts.app title="Leaderboard">
    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h2 class="text-2xl font-bold">Leaderboard</h2><p class="mt-1 text-slate-500">Read-only finalized rankings for examinations you own.</p></div>
        <a href="{{ route('instructor.examinations.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-600"><i data-lucide="clipboard-check" class="size-4"></i>My Examinations</a>
    </div>

    <div class="grid gap-4">
        @forelse ($examinations as $examination)
            <article class="surface flex flex-col gap-5 p-6 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1"><div class="flex flex-wrap gap-2"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $examination->subject->code }}</span><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">{{ $examination->finalized_result_count }} finalized</span></div><h3 class="mt-3 text-lg font-bold">{{ $examination->title }}</h3><p class="mt-1 text-sm text-slate-500">{{ $examination->subject->name }} · Historical reporting remains available for examinations you own.</p></div>
                <a href="{{ route('instructor.examinations.leaderboard', $examination) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white"><i data-lucide="trophy" class="size-4"></i>View Leaderboard</a>
            </article>
        @empty
            <section class="surface p-14 text-center"><i data-lucide="trophy" class="mx-auto size-9 text-slate-300"></i><h3 class="mt-4 font-bold">No examinations yet</h3><p class="mt-2 text-sm text-slate-500">Your examination leaderboards will appear here.</p><a href="{{ route('instructor.examinations.index') }}" class="mt-4 inline-flex font-semibold text-brand-600">Open My Examinations</a></section>
        @endforelse
    </div>
    @if ($examinations->hasPages())<div class="mt-6">{{ $examinations->links() }}</div>@endif
</x-layouts.app>
