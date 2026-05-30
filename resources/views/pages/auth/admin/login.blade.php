<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.guest')]
#[Title('Admin Sign In - Compify')]
class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $showPassword = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('admin')->attempt([
            'email' => $this->email,
            'password' => $this->password,
        ])) {
            $this->addError('email', 'Email atau password admin salah.');
            return;
        }

        session()->regenerate();

        $this->redirectRoute('admin.dashboard', navigate: true);
    }

    public function togglePassword(): void
    {
        $this->showPassword = ! $this->showPassword;
    }
};
?>

<div class="admin-login-page">
    <section class="admin-login-illustration">
        <img
            src="{{ asset('assets/admin/auth/admin-login-illustration.svg') }}"
            alt="Admin Login Illustration"
        >
    </section>

    <section class="admin-login-panel">
        <div class="admin-login-card">
            <img
                src="{{ asset('assets/brand/compify-icon.svg') }}"
                alt="Compify"
                class="admin-login-logo"
            >

            <h1>Admin Sign In</h1>
            <p>Masuk ke dashboard Compify</p>

            <form wire:submit="login" class="admin-login-form">
                <label class="admin-login-field">
                    <span class="admin-login-field-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M6 10V8a6 6 0 0 1 12 0v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <rect x="4" y="10" width="16" height="11" rx="2" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </span>

                    <input
                        type="email"
                        wire:model="email"
                        placeholder="Email"
                        autocomplete="email"
                    >
                </label>

                @error('email')
                    <small class="admin-login-error">{{ $message }}</small>
                @enderror

                <label class="admin-login-field">
                    <span class="admin-login-field-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 7.5h16v9H4v-9Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M4.5 8l7.5 5 7.5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>

                    <input
                        type="{{ $showPassword ? 'text' : 'password' }}"
                        wire:model="password"
                        placeholder="Password"
                        autocomplete="current-password"
                    >

                    <button
                        type="button"
                        class="admin-login-eye"
                        wire:click="togglePassword"
                        aria-label="Tampilkan password"
                    >
                        @if($showPassword)
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M3 3l18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M10.7 10.7a2 2 0 0 0 2.6 2.6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M7.6 7.8C5.6 8.9 4 10.4 3 12c2 3.2 5.3 5 9 5 1.4 0 2.7-.3 3.8-.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M17.8 14.1c1.3-.8 2.4-1.9 3.2-3.1-2-3.2-5.3-5-9-5-.8 0-1.6.1-2.4.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M3 12s3.2-6 9-6 9 6 9 6-3.2 6-9 6-9-6-9-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        @endif
                    </button>
                </label>

                @error('password')
                    <small class="admin-login-error">{{ $message }}</small>
                @enderror

                <button type="submit" class="admin-login-button">
                    Login
                </button>
            </form>
        </div>
    </section>
</div>