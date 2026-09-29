<x-layouts.guest title="Instructor sign in">
    <div class="mb-8"><span class="inline-flex items-center gap-2 rounded-full bg-brand-100 px-3 py-1 text-xs font-bold text-brand-800 dark:bg-brand-500/10 dark:text-brand-300"><i data-lucide="presentation" class="size-4"></i>Instructor portal</span><h2 class="mt-4 text-3xl font-bold text-slate-950 dark:text-white">Instructor sign in</h2><p class="mt-2 text-slate-500 dark:text-slate-400">Access your secure teaching workspace.</p></div>
    <form method="POST" action="{{ route('instructor.login.store') }}" class="grid gap-5">@csrf
        <x-form-field label="Instructor email" name="email" type="email" required autocomplete="email" />
        <x-form-field label="Password" name="password" type="password" required autocomplete="current-password" />
        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-600"> Keep me signed in</label>
        <x-primary-button><i data-lucide="log-in" class="size-4"></i>Enter instructor portal</x-primary-button>
    </form>
    <div class="mt-6 flex items-center justify-center gap-4 text-sm"><a href="{{ route('login') }}" class="font-semibold text-brand-600">Student sign in</a><span class="text-slate-300 dark:text-slate-700">·</span><a href="{{ route('admin.login') }}" class="text-slate-500 hover:text-slate-700 dark:text-slate-400">Administrator access</a></div>
</x-layouts.guest>
