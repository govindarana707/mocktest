<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('student-login', fn (Request $request) => $this->loginLimit($request, 'student'));
        RateLimiter::for('admin-login', fn (Request $request) => $this->loginLimit($request, 'admin'));
    }

    private function loginLimit(Request $request, string $portal): Limit
    {
        $email = Str::lower($request->string('email')->toString());

        return Limit::perMinute(5)->by(Str::transliterate("{$portal}|{$email}|{$request->ip()}"));
    }
}
