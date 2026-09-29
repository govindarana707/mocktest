@php
    $isAdmin = auth()->user()->isAdmin();
    $isInstructor = auth()->user()->isInstructor();
    $navigation = $isAdmin
        ? [
            ['Overview', 'admin.dashboard', 'layout-dashboard'], ['Students', 'admin.students.index', 'users'],
            ['Instructors', 'admin.instructors.index', 'presentation'],
            ['Subjects', 'admin.subjects.index', 'book-open'], ['Question Bank', 'admin.questions.index', 'circle-help'],
            ['Examinations', 'admin.examinations.index', 'clipboard-check'], ['Results', 'admin.results.index', 'chart-no-axes-column'],
            ['Analytics', 'admin.analytics.index', 'chart-pie'],
            ['Leaderboard', 'admin.leaderboard.index', 'trophy'], ['Settings', 'admin.settings.index', 'settings'],
        ]
        : ($isInstructor ? [
            ['Dashboard', 'instructor.dashboard', 'layout-dashboard'], ['My Subjects', 'instructor.subjects.index', 'book-open'],
            ['Question Bank', 'instructor.questions.index', 'circle-help'],
            ['My Examinations', 'instructor.examinations.index', 'clipboard-check'],
            ['Results', 'instructor.results.index', 'chart-no-axes-column'],
            ['Leaderboard', 'instructor.leaderboards.index', 'trophy'],
            ['Analytics', 'instructor.analytics.index', 'chart-pie'],
            ['Profile', 'instructor.profile.edit', 'user-round'],
        ] : [
            ['Overview', 'student.dashboard', 'layout-dashboard'], ['Available Exams', 'student.exams.index', 'notebook-tabs'],
            ['My Exams', 'student.my-exams.index', 'file-clock'], ['Results', 'student.results.index', 'chart-no-axes-column'],
            ['Leaderboard', 'student.leaderboard.index', 'trophy'], ['Profile', 'student.profile.edit', 'user-round'],
        ]);
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} · MockTest</title>
    <script>if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark')</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100" x-data="shell" @keydown.escape.window="sidebarOpen = false; profileOpen = false">
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>
    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-800 bg-slate-950 text-slate-300 transition-all duration-300 lg:translate-x-0" :class="{ 'translate-x-0': sidebarOpen, 'lg:w-24': sidebarCollapsed }">
        <div class="flex h-20 items-center gap-3 px-6">
            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-brand-600 text-white"><i data-lucide="graduation-cap" class="size-6"></i></span>
            <span x-show="!sidebarCollapsed" class="text-xl font-bold text-white">MockTest</span>
            <button class="ml-auto lg:hidden" @click="sidebarOpen=false" aria-label="Close sidebar"><i data-lucide="x"></i></button>
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-4">
            @foreach ($navigation as [$label, $route, $icon])
                <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' => request()->routeIs($route), 'hover:bg-slate-900 hover:text-white' => !request()->routeIs($route)]) title="{{ $label }}">
                    <i data-lucide="{{ $icon }}" class="size-5 shrink-0"></i><span x-show="!sidebarCollapsed">{{ $label }}</span>
                </a>
            @endforeach
        </nav>
        <button type="button" @click="toggleSidebar()" class="m-4 hidden items-center justify-center gap-2 rounded-xl border border-slate-800 px-4 py-3 text-sm hover:bg-slate-900 lg:flex">
            <i data-lucide="panel-left-close" class="size-5" :class="{ 'rotate-180': sidebarCollapsed }"></i><span x-show="!sidebarCollapsed">Collapse</span>
        </button>
    </aside>
    <div class="min-h-screen transition-all duration-300 lg:pl-72" :class="{ 'lg:pl-24': sidebarCollapsed }">
        <header class="sticky top-0 z-30 flex h-20 items-center border-b border-slate-200/80 bg-white/85 px-5 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/85 sm:px-8">
            <button @click="sidebarOpen=true" class="mr-4 lg:hidden" aria-label="Open sidebar"><i data-lucide="menu"></i></button>
            <div><p class="text-xs font-semibold uppercase tracking-widest text-brand-600">{{ $isAdmin ? 'Administration' : ($isInstructor ? 'Instructor portal' : 'Student portal') }}</p><h1 class="text-lg font-bold">{{ $title ?? 'Dashboard' }}</h1></div>
            <div class="ml-auto flex items-center gap-2">
                <button @click="toggleTheme()" class="grid size-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300" aria-label="Toggle theme"><i data-lucide="moon" class="size-5 dark:hidden"></i><i data-lucide="sun" class="hidden size-5 dark:block"></i></button>
                <div class="relative" @click.outside="profileOpen=false">
                    <button @click="profileOpen=!profileOpen" class="flex items-center gap-3 rounded-xl p-1.5 pr-3 hover:bg-slate-100 dark:hover:bg-slate-900">
                        <span class="grid size-9 place-items-center rounded-xl bg-brand-100 font-bold text-brand-700">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                        <span class="hidden text-left sm:block"><span class="block text-sm font-semibold">{{ auth()->user()->name }}</span><span class="block text-xs text-slate-500">{{ ucfirst(auth()->user()->role->value) }}</span></span>
                        <i data-lucide="chevron-down" class="size-4 text-slate-400"></i>
                    </button>
                    <div x-show="profileOpen" x-cloak x-transition class="surface absolute right-0 mt-2 w-56 p-2">
                        @unless($isAdmin)<a href="{{ route($isInstructor ? 'instructor.profile.edit' : 'student.profile.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-800"><i data-lucide="user-round" class="size-4"></i>Profile</a>@endunless
                        <form method="POST" action="{{ route($isAdmin ? 'admin.logout' : ($isInstructor ? 'instructor.logout' : 'student.logout')) }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30"><i data-lucide="log-out" class="size-4"></i>Sign out</button></form>
                    </div>
                </div>
            </div>
        </header>
        <main class="p-5 sm:p-8">{{ $slot }}</main>
    </div>
    @if (session('success'))<script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon: 'success', title: @js(session('success')), timer: 2200, showConfirmButton: false }))</script>@endif
</body>
</html>
