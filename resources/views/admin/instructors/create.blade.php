<x-layouts.app title="New Instructor">
    <div class="mx-auto max-w-4xl"><div class="mb-6"><h2 class="text-2xl font-bold">Create instructor</h2><p class="mt-1 text-slate-500">Provision a secure Instructor account.</p></div>@include('admin.instructors.form', ['action' => route('admin.instructors.store'), 'method' => 'POST', 'instructor' => null, 'submitLabel' => 'Create instructor'])</div>
</x-layouts.app>
