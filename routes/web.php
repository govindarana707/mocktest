<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Auth\AdminSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\StudentSessionController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ProfileController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/login', [StudentSessionController::class, 'create'])->name('login');
    Route::post('/login', [StudentSessionController::class, 'store'])->middleware('throttle:student-login')->name('login.store');
    Route::get('/admin/login', [AdminSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminSessionController::class, 'store'])->middleware('throttle:admin-login')->name('admin.login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', function (Request $request): RedirectResponse {
        return redirect()->route($request->user()->isAdmin() ? 'admin.dashboard' : 'student.dashboard');
    })->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::view('/subjects', 'shared.coming-soon', ['title' => 'Subjects'])->name('subjects.index');
        Route::view('/questions', 'shared.coming-soon', ['title' => 'Question Bank'])->name('questions.index');
        Route::view('/examinations', 'shared.coming-soon', ['title' => 'Examinations'])->name('examinations.index');
        Route::view('/results', 'shared.coming-soon', ['title' => 'Results'])->name('results.index');
        Route::view('/leaderboard', 'shared.coming-soon', ['title' => 'Leaderboard'])->name('leaderboard.index');
        Route::view('/settings', 'shared.coming-soon', ['title' => 'Settings'])->name('settings.index');
        Route::post('/logout', [AdminSessionController::class, 'destroy'])->name('logout');
    });

    Route::prefix('student')->name('student.')->middleware('role:student')->group(function (): void {
        Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');
        Route::view('/exams', 'shared.coming-soon', ['title' => 'Available Exams'])->name('exams.index');
        Route::view('/my-exams', 'shared.coming-soon', ['title' => 'My Exams'])->name('my-exams.index');
        Route::view('/results', 'shared.coming-soon', ['title' => 'Results'])->name('results.index');
        Route::view('/leaderboard', 'shared.coming-soon', ['title' => 'Leaderboard'])->name('leaderboard.index');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/logout', [StudentSessionController::class, 'destroy'])->name('logout');
    });
});
