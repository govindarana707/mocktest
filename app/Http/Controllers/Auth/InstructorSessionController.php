<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InstructorSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.instructor-login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt([
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'role' => UserRole::Instructor->value,
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'The provided instructor credentials are incorrect.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('instructor.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('instructor.login')->with('success', 'Your instructor session has ended.');
    }
}
