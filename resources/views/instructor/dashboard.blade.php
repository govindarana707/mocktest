<x-layouts.app title="Dashboard">
    <section class="overflow-hidden rounded-3xl bg-gradient-to-r from-brand-700 via-indigo-700 to-violet-700 p-7 text-white shadow-xl shadow-indigo-900/20 sm:p-9">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wider"><i data-lucide="presentation" class="size-4"></i>Instructor</span>
        <h2 class="mt-5 text-3xl font-bold">Welcome, {{ str($instructor->name)->before(' ') }}.</h2>
        <p class="mt-2 max-w-2xl text-indigo-100">Your instructor workspace is ready. Subject, question, examination, and result tools will arrive in the next Phase 7 sub-phases.</p>
        <a href="{{ route('instructor.profile.edit') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-brand-700"><i data-lucide="user-round" class="size-4"></i>Manage profile</a>
    </section>
    <section class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['My Subjects', 'book-open'], ['Question Bank', 'circle-help'], ['My Examinations', 'clipboard-check'], ['Results', 'chart-no-axes-column']] as [$label, $icon])
            <article class="surface p-6"><div class="flex items-center justify-between"><span class="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10"><i data-lucide="{{ $icon }}" class="size-5"></i></span><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 dark:bg-slate-800 dark:text-slate-300">Coming Soon</span></div><h3 class="mt-5 font-bold">{{ $label }}</h3><p class="mt-1 text-sm text-slate-500">Available in a later instructor sub-phase.</p></article>
        @endforeach
    </section>
</x-layouts.app>
