<x-layouts.app title="Overview">
    <section class="mb-8 overflow-hidden rounded-3xl bg-gradient-to-r from-brand-700 via-indigo-700 to-violet-700 p-8 text-white shadow-xl shadow-indigo-900/20">
        <p class="text-sm font-semibold text-indigo-200">{{ now()->format('l, F j') }}</p><h2 class="mt-2 text-3xl font-bold">Good to see you, {{ str(auth()->user()->name)->before(' ') }}.</h2><p class="mt-2 max-w-2xl text-indigo-100">Your administration hub is ready. Student access is live; content modules arrive in Phase 3.</p>
    </section>
    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Students', $statistics['students'], 'users'], ['New this month', $statistics['newStudents'], 'user-plus'], ['Administrators', $statistics['administrators'], 'shield-check'], ['Active sessions', $statistics['activeSessions'], 'activity']] as [$label, $value, $icon])
            <article class="surface p-6"><div class="flex items-center justify-between"><span class="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10"><i data-lucide="{{ $icon }}" class="size-5"></i></span><span class="text-3xl font-bold">{{ number_format($value) }}</span></div><p class="mt-5 text-sm font-medium text-slate-500">{{ $label }}</p></article>
        @endforeach
    </section>
    <section class="surface mt-8 overflow-hidden"><div class="flex items-center justify-between border-b border-slate-200 p-6 dark:border-slate-800"><div><h3 class="font-bold">Recent students</h3><p class="text-sm text-slate-500">Newest registered learners</p></div><a href="{{ route('admin.students.index') }}" class="text-sm font-semibold text-brand-600">View all</a></div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">@forelse($recentStudents as $student)<div class="flex items-center gap-4 p-5"><span class="grid size-10 place-items-center rounded-xl bg-slate-100 font-bold text-slate-600 dark:bg-slate-800">{{ str($student->name)->substr(0, 1)->upper() }}</span><div class="min-w-0 flex-1"><p class="truncate font-semibold">{{ $student->name }}</p><p class="truncate text-sm text-slate-500">{{ $student->email }}</p></div><time class="text-xs text-slate-400">{{ $student->created_at->diffForHumans() }}</time></div>@empty<div class="p-10 text-center text-sm text-slate-500">No students have registered yet.</div>@endforelse</div>
    </section>
</x-layouts.app>
