<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->route('login');
        }

        $admin = Auth::guard('admin')->user();

        if (! $admin || $admin->role !== 'admin') {
            Auth::guard('admin')->logout();

            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        Auth::shouldUse('admin');

        return $next($request);
    }
}