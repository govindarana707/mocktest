<x-layouts.app title="My Subjects">
    <div class="mb-7"><h2 class="text-2xl font-bold">My Subjects</h2><p class="mt-1 text-slate-500">Subjects assigned to you by an administrator.</p></div>
    @if($subjects->isEmpty())
        <section class="surface p-12 text-center sm:p-16"><span class="mx-auto grid size-14 place-items-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800"><i data-lucide="book-open" class="size-7"></i></span><h3 class="mt-5 text-lg font-bold">No subjects have been assigned to you yet.</h3><p class="mx-auto mt-2 max-w-md text-sm text-slate-500">An administrator will assign subjects when your teaching workspace is ready.</p></section>
    @else
        <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">@foreach($subjects as $subject)<article class="surface p-6"><div class="flex items-start justify-between gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10"><i data-lucide="book-open" class="size-5"></i></span><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $subject->code }}</span></div><h3 class="mt-5 text-lg font-bold">{{ $subject->name }}</h3><p class="mt-2 text-sm leading-6 text-slate-500">{{ $subject->description ?: 'No description is available for this subject.' }}</p><div class="mt-5 border-t border-slate-100 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:border-slate-800">Read only</div></article>@endforeach</section>
    @endif
</x-layouts.app>
