<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('customer')->check()) {
            return redirect()->guest(route('customer.login'));
        }

        $customer = Auth::guard('customer')->user();

        if (! $customer || $customer->role !== 'customer') {
            Auth::guard('customer')->logout();

            $request->session()->regenerateToken();

            return redirect()
                ->route('customer.login')
                ->withErrors([
                    'email' => 'Silakan login menggunakan akun customer.',
                ]);
        }

        Auth::shouldUse('customer');

        return $next($request);
    }
}