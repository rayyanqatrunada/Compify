@extends('layouts.app')

@section('title', 'Register - Compify')

@section('content')
    <section class="auth-shell">
        <div class="auth-panel">
            <p class="section-kicker">Create account</p>
            <h1 class="mt-4 text-3xl font-black text-white">Mulai jelajah Compify.</h1>
            <p class="mt-3 text-sm leading-6 text-slate-400">Akun baru otomatis mendapat role user. Role admin dikelola dari database dan Filament.</p>

            <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
                @csrf
                <label class="block">
                    <span class="form-label">Nama</span>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input" required autofocus>
                    @error('name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </label>

                <label class="block">
                    <span class="form-label">Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input" required>
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

                <label class="block">
                    <span class="form-label">Konfirmasi password</span>
                    <input type="password" name="password_confirmation" class="form-input" required>
                </label>

                <button type="submit" class="neon-button w-full justify-center">Register</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-400">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-semibold text-sky-200">Login</a>
            </p>
        </div>
    </section>
@endsection
