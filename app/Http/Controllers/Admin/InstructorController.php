<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstructorRequest;
use App\Http\Requests\Admin\UpdateInstructorRequest;
use App\Models\Subject;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InstructorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        return view('admin.instructors.index', [
            'instructors' => User::query()
                ->where('role', UserRole::Instructor)
                ->when($search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.instructors.create', [
            'subjects' => Subject::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInstructorRequest $request): RedirectResponse
    {
        $attributes = $request->safe()->only(['name', 'email', 'password']);
        $subjectIds = $request->validated('subject_ids', []);

        DB::transaction(function () use ($attributes, $subjectIds): void {
            $instructor = User::create([
                ...$attributes,
                'role' => UserRole::Instructor,
            ]);

            $instructor->subjects()->sync($subjectIds);
        });

        return redirect()->route('admin.instructors.index')->with('success', 'Instructor account created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $instructor): View
    {
        $this->ensureInstructor($instructor);

        return view('admin.instructors.edit', [
            'instructor' => $instructor->load('subjects:id'),
            'subjects' => Subject::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInstructorRequest $request, User $instructor): RedirectResponse
    {
        $this->ensureInstructor($instructor);
        $attributes = $request->safe()->only(['name', 'email', 'password']);
        $subjectIds = $request->validated('subject_ids', []);

        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        }

        DB::transaction(function () use ($attributes, $instructor, $subjectIds): void {
            $instructor->update($attributes);
            $instructor->subjects()->sync($subjectIds);
        });

        return redirect()->route('admin.instructors.index')->with('success', 'Instructor account updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $instructor): RedirectResponse
    {
        $this->ensureInstructor($instructor);
        $instructor->delete();

        return redirect()->route('admin.instructors.index')->with('success', 'Instructor account deleted.');
    }

    private function ensureInstructor(User $instructor): void
    {
        abort_unless($instructor->isInstructor(), 404);
    }
}
