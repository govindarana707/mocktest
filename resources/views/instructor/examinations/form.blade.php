<x-layouts.app :title="$examination->exists ? 'Edit examination' : 'New examination'">
    <div class="mx-auto max-w-5xl">
        <div class="mb-7">
            <a href="{{ route('instructor.examinations.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-600"><i data-lucide="arrow-left" class="size-4"></i>My Examinations</a>
            <h2 class="mt-3 text-2xl font-bold">{{ $examination->exists ? $examination->title : 'Create a draft examination' }}</h2>
            <p class="mt-1 text-slate-500">Configure examination details, scheduling, and eligible same-subject questions.</p>
        </div>

        @if($subjects->isEmpty())
            <section class="surface p-12 text-center sm:p-16">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-500/10"><i data-lucide="book-x" class="size-7"></i></span>
                <h3 class="mt-5 text-lg font-bold">No assigned subjects</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Ask an administrator to assign a subject before creating examinations.</p>
                <a href="{{ route('instructor.examinations.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-slate-700">Return to My Examinations</a>
            </section>
        @else
            <form method="POST" action="{{ $examination->exists ? route('instructor.examinations.update', $examination) : route('instructor.examinations.store') }}" class="surface grid gap-6 p-6 sm:p-8">
                @csrf
                @if($examination->exists) @method('PUT') @endif

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form-field label="Examination title" name="title" :value="$examination->title" required />
                    <label class="grid gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                        <span>Subject</span>
                        <select name="subject_id" required class="focus-ring rounded-xl border border-slate-300 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-950">
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected((int) old('subject_id', $examination->subject_id) === $subject->id)>{{ $subject->code }} · {{ $subject->name }}</option>
                            @endforeach
                        </select>
                        @error('subject_id')<span class="text-xs font-medium text-rose-600">{{ $message }}</span>@enderror
                    </label>
                    <x-form-field label="Duration in minutes" name="duration_minutes" type="number" min="1" max="600" :value="$examination->duration_minutes" required />
                    <x-form-field label="Passing percentage" name="passing_percentage" type="number" min="1" max="100" :value="$examination->passing_percentage" required />
                </div>

                <label class="grid gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                    <span>Description <span class="font-normal text-slate-400">optional</span></span>
                    <textarea name="description" rows="4" maxlength="3000" class="focus-ring rounded-xl border border-slate-300 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-950">{{ old('description', $examination->description) }}</textarea>
                    @error('description')<span class="text-xs font-medium text-rose-600">{{ $message }}</span>@enderror
                </label>

                <div class="grid gap-5 md:grid-cols-3">
                    <label class="grid gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                        <span>Status</span>
                        <select name="status" class="focus-ring rounded-xl border border-slate-300 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-950">
                            @if(!$examination->exists)
                                <option value="draft">Draft</option>
                            @else
                                <option value="draft" @selected(old('status', $examination->status->value) === 'draft')>Draft</option>
                                <option value="published" @selected(old('status', $examination->status->value) === 'published')>Published</option>
                            @endif
                        </select>
                        @error('status')<span class="text-xs font-medium text-rose-600">{{ $message }}</span>@enderror
                    </label>
                    <x-form-field label="Starts at" name="starts_at" type="datetime-local" :value="$examination->starts_at?->format('Y-m-d\TH:i')" />
                    <x-form-field label="Ends at" name="ends_at" type="datetime-local" :value="$examination->ends_at?->format('Y-m-d\TH:i')" />
                </div>

                <fieldset class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                    <legend class="px-1 text-sm font-semibold text-slate-900 dark:text-white">Result visibility</legend>
                    <input type="hidden" name="allow_answer_review" value="0">
                    <label class="mt-3 flex cursor-pointer items-start gap-3">
                        <input id="allow_answer_review" type="checkbox" name="allow_answer_review" value="1" @checked((bool) old('allow_answer_review', $examination->allow_answer_review)) class="focus-ring mt-0.5 size-4 rounded border-slate-300 text-brand-600 dark:border-slate-700">
                        <span><span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">Allow students to review answers after submission</span><span class="mt-1 block text-sm leading-6 text-slate-500">When enabled, students can review their submitted answers after the examination has been finalized.</span></span>
                    </label>
                </fieldset>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('instructor.examinations.index') }}" class="rounded-xl px-5 py-3 text-sm font-semibold text-slate-500">Cancel</a>
                    <x-primary-button><i data-lucide="save" class="size-4"></i>{{ $examination->exists ? 'Save details' : 'Create draft' }}</x-primary-button>
                </div>
            </form>
        @endif

        @if($examination->exists)
            <section class="surface mt-6 overflow-hidden" x-data="questionAssignment({ endpoint: @js(route('instructor.examinations.questions.update', $examination)), token: @js(csrf_token()) })">
                <div class="border-b border-slate-200 p-6 dark:border-slate-800">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-bold">Question assignment</h3>
                            <p class="mt-1 text-sm text-slate-500">Use your questions and Admin/shared questions from {{ $examination->subject->name }}.</p>
                        </div>
                        @unless($assignmentLocked)
                            <label class="relative"><i data-lucide="search" class="absolute left-3 top-3 size-4 text-slate-400"></i><input x-model="search" placeholder="Filter eligible questions" class="focus-ring rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950"></label>
                        @endunless
                    </div>
                </div>

                <form @submit.prevent="save($el)" class="grid gap-5 p-6">
                    @if($assignmentLocked)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">Question assignments are locked because a student attempt has already started. Existing attempt snapshots and results remain unchanged.</div>
                    @elseif(count($assignedQuestionIds) === 0)
                        <div class="rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-700 dark:border-brand-900/60 dark:bg-brand-950/30 dark:text-brand-200">Assign at least one question before publishing this examination.</div>
                    @endif

                    <div class="grid gap-3">
                        @forelse($questions as $question)
                            @php
                                $sourceLabel = $question->created_by === $examination->created_by
                                    ? 'My Question'
                                    : ($question->creator?->isAdmin() ? 'Admin / Shared' : 'Legacy / Shared');
                            @endphp
                            <label x-show="!search || @js(strtolower($question->question_text)).includes(search.toLowerCase())" @class(['flex items-start gap-3 rounded-xl border border-slate-200 p-4 transition dark:border-slate-800', 'cursor-pointer hover:border-brand-300' => ! $assignmentLocked, 'cursor-not-allowed opacity-75' => $assignmentLocked])>
                                <input type="checkbox" name="question_ids[]" value="{{ $question->id }}" @checked(in_array($question->id, $assignedQuestionIds)) @disabled($assignmentLocked) class="mt-1 rounded border-slate-300 text-brand-600">
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-start justify-between gap-2">
                                        <span class="font-semibold">{{ $question->question_text }}</span>
                                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $sourceLabel }}</span>
                                    </span>
                                    <span class="mt-1 block text-sm text-slate-500">Correct answer details remain secured in the Question Bank.</span>
                                </span>
                            </label>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
                                <p class="text-sm text-slate-500">No eligible questions are available for this subject.</p>
                                <a href="{{ route('instructor.questions.index', ['subject_id' => $examination->subject_id]) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-brand-600"><i data-lucide="circle-help" class="size-4"></i>Open Instructor Question Bank</a>
                            </div>
                        @endforelse
                    </div>

                    @if($questions->isNotEmpty())
                        <div class="flex justify-end">
                            <button :disabled="saving || @js($assignmentLocked)" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-60"><i data-lucide="list-checks" class="size-4"></i><span x-text="saving ? 'Saving…' : 'Save question assignment'"></span></button>
                        </div>
                    @endif
                </form>
            </section>
        @endif
    </div>
</x-layouts.app>
