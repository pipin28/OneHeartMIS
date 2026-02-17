<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return Redirect::back()
                ->withErrors([
                    'username' => 'Too many login attempts. Try again in ' . $seconds . ' seconds.',
                ])
                ->onlyInput('username');
        }

        $credentials = $request->validated();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            AuditLogger::log('auth.login_failed', 'user', null, [
                'username' => (string) ($credentials['username'] ?? ''),
            ]);

            return Redirect::back()
                ->withErrors(['username' => 'Invalid credentials.'])
                ->onlyInput('username');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        AuditLogger::log('auth.login_success', 'user', (int) auth()->id());

        return Redirect::route('dashboard')->with('status', 'Login successful. Welcome back!');
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return Redirect::route('register')->with('status', 'Account created successfully.');
    }

    public function logout(Request $request)
    {
        $userId = (int) auth()->id();
        AuditLogger::log('auth.logout', 'user', $userId);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::route('login')->with('status', 'Logged out successfully.');
    }

    private function throttleKey(Request $request): string
    {
        return Str::lower((string) $request->input('username')) . '|' . $request->ip();
    }
}
