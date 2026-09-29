<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstructorRequest;
use App\Http\Requests\Admin\UpdateInstructorRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return view('admin.instructors.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInstructorRequest $request): RedirectResponse
    {
        User::create([
            ...$request->safe()->only(['name', 'email', 'password']),
            'role' => UserRole::Instructor,
        ]);

        return redirect()->route('admin.instructors.index')->with('success', 'Instructor account created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $instructor): View
    {
        $this->ensureInstructor($instructor);

        return view('admin.instructors.edit', ['instructor' => $instructor]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInstructorRequest $request, User $instructor): RedirectResponse
    {
        $this->ensureInstructor($instructor);
        $attributes = $request->safe()->only(['name', 'email', 'password']);

        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        }

        $instructor->update($attributes);

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
