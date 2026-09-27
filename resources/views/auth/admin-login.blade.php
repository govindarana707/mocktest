<x-layouts.guest title="Administrator sign in">
    <div class="mb-8"><span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800"><i data-lucide="shield-check" class="size-4"></i>Restricted access</span><h2 class="mt-4 text-3xl font-bold text-slate-950 dark:text-white">Administrator sign in</h2><p class="mt-2 text-slate-500 dark:text-slate-400">Use your authorized administration account.</p></div>
    <form method="POST" action="{{ route('admin.login.store') }}" class="grid gap-5">@csrf
        <x-form-field label="Administrator email" name="email" type="email" required autocomplete="email" />
        <x-form-field label="Password" name="password" type="password" required autocomplete="current-password" />
        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-600"> Keep me signed in</label>
        <x-primary-button><i data-lucide="shield" class="size-4"></i>Enter admin portal</x-primary-button>
    </form>
    <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-brand-600">Return to student sign in</a></p>
</x-layouts.guest>
