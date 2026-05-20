@extends('layouts.app')

@section('title', 'Login - Compify')

@section('content')
    <section class="auth-shell">
        <div class="auth-panel">
            <p class="section-kicker">Welcome back</p>
            <h1 class="mt-4 text-3xl font-black text-white">Login ke Compify.</h1>
            <p class="mt-3 text-sm leading-6 text-slate-400">Gunakan akun user biasa atau akun admin demo untuk masuk ke panel Filament.</p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                @csrf
                <label class="block">
                    <span class="form-label">Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input" required autofocus>
                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </label>

                <label class="block">
                    <span class="form-label">Password</span>
                    <input type="password" name="password" class="form-input" required>
                    @error('password')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </label>

                <label class="flex items-center gap-3 text-sm text-slate-300">
                    <input type="checkbox" name="remember" class="size-4 rounded border-white/20 bg-white/10 text-sky-400">
                    Remember me
                </label>

                <button type="submit" class="neon-button w-full justify-center">Login</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-400">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-semibold text-sky-200">Register</a>
            </p>
        </div>
    </section>
@endsection
