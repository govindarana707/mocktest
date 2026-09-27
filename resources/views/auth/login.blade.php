<x-layouts.guest title="Student sign in">
    <div class="mb-8"><p class="text-sm font-semibold text-brand-600">Student portal</p><h2 class="mt-2 text-3xl font-bold text-slate-950 dark:text-white">Welcome back</h2><p class="mt-2 text-slate-500 dark:text-slate-400">Sign in to continue your learning journey.</p></div>
    <form method="POST" action="{{ route('login.store') }}" class="grid gap-5">@csrf
        <x-form-field label="Email address" name="email" type="email" required autocomplete="email" />
        <x-form-field label="Password" name="password" type="password" required autocomplete="current-password" />
        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-600"> Keep me signed in</label>
        <x-primary-button><i data-lucide="log-in" class="size-4"></i>Sign in</x-primary-button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-500">New here? <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700">Create a student account</a></p>
    <p class="mt-3 text-center text-xs"><a href="{{ route('admin.login') }}" class="text-slate-400 hover:text-slate-600">Administrator access</a></p>
</x-layouts.guest>
