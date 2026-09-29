<x-layouts.app title="Profile">
    <div class="mx-auto max-w-3xl"><div class="mb-6"><h2 class="text-2xl font-bold">Instructor profile</h2><p class="mt-1 text-slate-500">Update the name and email associated with your instructor account.</p></div>
        <form method="POST" action="{{ route('instructor.profile.update') }}" class="surface p-6 sm:p-8">@csrf @method('PUT')<div class="grid gap-5 sm:grid-cols-2"><x-form-field label="Full name" name="name" :value="$instructor->name" required /><x-form-field label="Email address" name="email" type="email" :value="$instructor->email" required /></div><p class="mt-5 text-sm text-slate-500">Your Instructor role is managed by an administrator and cannot be changed here.</p><div class="mt-7 flex justify-end"><x-primary-button><i data-lucide="save" class="size-4"></i>Save changes</x-primary-button></div></form>
    </div>
</x-layouts.app>
