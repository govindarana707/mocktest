<!DOCTYPE html>
<html lang="en" class="h-full" x-data="{ dark: localStorage.getItem('theme') === 'dark' }" :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'MockTest' }}</title>
    <script>if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark')</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-950 text-slate-900">
    <main class="relative grid min-h-screen overflow-hidden lg:grid-cols-2">
        <section class="relative hidden overflow-hidden bg-gradient-to-br from-brand-700 via-indigo-700 to-violet-900 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-24 -top-24 size-96 rounded-full bg-white/10 blur-3xl"></div>
            <a href="{{ route('home') }}" class="relative flex items-center gap-3 text-xl font-bold">
                <span class="grid size-11 place-items-center rounded-2xl bg-white/15 backdrop-blur"><i data-lucide="graduation-cap" class="size-6"></i></span>
                MockTest
            </a>
            <div class="relative max-w-lg">
                <p class="mb-4 text-sm font-semibold uppercase tracking-[0.25em] text-indigo-200">Learn. Measure. Improve.</p>
                <h1 class="text-5xl font-bold leading-tight">A focused place to build exam confidence.</h1>
                <p class="mt-6 text-lg leading-8 text-indigo-100">Secure practice, clear progress, and fair rankings—designed for students who want every attempt to count.</p>
            </div>
            <p class="relative text-sm text-indigo-200">© {{ date('Y') }} MockTest</p>
        </section>
        <section class="relative flex min-h-screen items-center justify-center bg-slate-50 px-5 py-12 dark:bg-slate-950">
            <button type="button" @click="dark = !dark; localStorage.setItem('theme', dark ? 'dark' : 'light')" class="absolute right-5 top-5 grid size-11 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300" aria-label="Toggle theme">
                <i data-lucide="moon" class="size-5 dark:hidden"></i><i data-lucide="sun" class="hidden size-5 dark:block"></i>
            </button>
            <div class="w-full max-w-md">{{ $slot }}</div>
        </section>
    </main>
    @if (session('success'))
        <script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon: 'success', title: @js(session('success')), timer: 2200, showConfirmButton: false }))</script>
    @endif
</body>
</html>
