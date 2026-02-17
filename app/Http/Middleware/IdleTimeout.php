<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class IdleTimeout
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $lifetimeSeconds = (int) config('session.lifetime') * 60;
            $lastActivity = $request->session()->get('last_activity');
            $now = time();

            if ($lastActivity && ($now - $lastActivity) > $lifetimeSeconds) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Session expired due to inactivity.'], 401);
                }

                return Redirect::route('login')
                    ->with('status', 'Session expired due to inactivity. Please log in again.');
            }

            $request->session()->put('last_activity', $now);
        }

        return $next($request);
    }
}
