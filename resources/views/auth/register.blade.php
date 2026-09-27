<x-layouts.guest title="Create student account">
    <div class="mb-8"><p class="text-sm font-semibold text-brand-600">Student registration</p><h2 class="mt-2 text-3xl font-bold text-slate-950 dark:text-white">Start learning today</h2><p class="mt-2 text-slate-500 dark:text-slate-400">Create your secure student account in a minute.</p></div>
    <form method="POST" action="{{ route('register.store') }}" class="grid gap-4">@csrf
        <x-form-field label="Full name" name="name" required autocomplete="name" />
        <x-form-field label="Email address" name="email" type="email" required autocomplete="email" />
        <x-form-field label="Password" name="password" type="password" required autocomplete="new-password" />
        <x-form-field label="Confirm password" name="password_confirmation" type="password" required autocomplete="new-password" />
        <x-primary-button><i data-lucide="user-plus" class="size-4"></i>Create student account</x-primary-button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-500">Already registered? <a href="{{ route('login') }}" class="font-semibold text-brand-600">Sign in</a></p>
</x-layouts.guest>
