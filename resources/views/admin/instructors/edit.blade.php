<x-layouts.app title="Edit Instructor">
    <div class="mx-auto max-w-4xl"><div class="mb-6"><h2 class="text-2xl font-bold">Edit instructor</h2><p class="mt-1 text-slate-500">Update account details without changing the Instructor role.</p></div>@include('admin.instructors.form', ['action' => route('admin.instructors.update', $instructor), 'method' => 'PUT', 'instructor' => $instructor, 'submitLabel' => 'Save changes'])</div>
</x-layouts.app>
