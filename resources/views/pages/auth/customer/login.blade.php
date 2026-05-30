<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.customer-auth')]
#[Title('Login - Compify')]
class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $showPassword = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::guard('customer')->attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], true)) {
            $this->addError('email', 'Email atau password salah.');
            return;
        }

        request()->session()->regenerate();

        if (Auth::guard('customer')->user()->role === 'admin') {
            Auth::guard('customer')->logout();

            $this->addError('email', 'Akun admin tidak digunakan untuk login customer.');
            return;
        }

        $intended = session()->pull('url.intended', route('home'));

        $this->redirect($intended, navigate: true);
    }
};
?>

<section class="auth-figma-page">
    <div class="auth-figma-illustration">
        <a href="{{ route('home') }}" class="auth-back-home" wire:navigate>
            ← Back to website
        </a>

        <div class="auth-illustration-card">
            <img
                src="{{ asset('assets/auth/login-illustration.svg') }}"
                alt="Login Illustration"
                onerror="this.style.display='none'"
            >

            </div>
        </div>
    </div>

    <div class="auth-figma-panel">
        <div class="auth-figma-box">
            <div class="auth-mini-logo">
                <img src="{{ asset('assets/brand/compify-icon.svg') }}" alt="Compify">
            </div>

            <h1>Log in with email</h1>

            <p class="auth-switch-text">
                Belum punya akun?
                <a href="{{ route('customer.register') }}" wire:navigate>Registrasi disini</a>
            </p>

            @if($errors->any())
                <div class="auth-figma-alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form wire:submit="login" class="auth-figma-form">
                <label class="auth-input-wrap">
                    <span class="auth-input-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M7 8V6a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h1Zm2 0h6V6a3 3 0 0 0-6 0v2Zm-3 2v9h12v-9H6Z"/>
                        </svg>
                    </span>

                    <input type="email" wire:model.defer="email" placeholder="Email">
                </label>

                <label class="auth-input-wrap">
                    <span class="auth-input-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h13A2.5 2.5 0 0 1 21 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5v-11Zm2.3-.5 6.7 5.1L18.7 6H5.3ZM19 8.1l-6.1 4.6a1.5 1.5 0 0 1-1.8 0L5 8.1v9.4c0 .3.2.5.5.5h13c.3 0 .5-.2.5-.5V8.1Z"/>
                        </svg>
                    </span>

                    <input
                        type="{{ $showPassword ? 'text' : 'password' }}"
                        wire:model.defer="password"
                        placeholder="Password"
                    >

                    <button type="button" class="auth-password-eye" wire:click="$toggle('showPassword')">
                        <svg viewBox="0 0 24 24">
                            <path d="M2.3 12s3.4-6 9.7-6 9.7 6 9.7 6-3.4 6-9.7 6-9.7-6-9.7-6Zm9.7 4a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0-2.2a1.8 1.8 0 1 1 0-3.6 1.8 1.8 0 0 1 0 3.6Z"/>
                        </svg>
                    </button>
                </label>

                <a href="#" class="auth-forgot-link">Forgot password?</a>

                <button type="submit" class="auth-submit-btn">
                    Login
                </button>
            </form>

            <div class="auth-social-title">or log in with</div>

            <div class="auth-social-row">
                <a href="{{ route('customer.google.redirect') }}" class="auth-social-icon" aria-label="Login with Google">
                    <svg viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.4-.2-2H12v3.8h5.4c-.2 1.2-.9 2.2-1.9 2.9v2.4h3.1c1.8-1.7 3-4.1 3-7.1Z"/>
                        <path fill="#34A853" d="M12 22c2.7 0 5-.9 6.6-2.5l-3.1-2.4c-.9.6-2 1-3.5 1-2.7 0-4.9-1.8-5.7-4.2H3.1v2.5C4.7 19.7 8.1 22 12 22Z"/>
                        <path fill="#FBBC05" d="M6.3 13.9c-.2-.6-.3-1.2-.3-1.9s.1-1.3.3-1.9V7.6H3.1C2.4 8.9 2 10.4 2 12s.4 3.1 1.1 4.4l3.2-2.5Z"/>
                        <path fill="#EA4335" d="M12 5.9c1.5 0 2.8.5 3.8 1.5l2.9-2.9C16.9 2.9 14.7 2 12 2 8.1 2 4.7 4.3 3.1 7.6l3.2 2.5C7.1 7.7 9.3 5.9 12 5.9Z"/>
                    </svg>
                </a>

                <button type="button" class="auth-social-icon" title="Facebook login belum dikonfigurasi">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" fill="#18ACFE"/>
                        <path fill="#fff" d="M13.5 12.7h2.4l.4-2.8h-2.8V8.6c0-.8.2-1.4 1.4-1.4h1.5V4.7c-.7-.1-1.5-.2-2.2-.2-2.2 0-3.7 1.4-3.7 3.9v1.5H8v2.8h2.5V20h3v-7.3Z"/>
                    </svg>
                </button>

                <button type="button" class="auth-social-icon apple" title="Apple login belum dikonfigurasi">
                    <svg viewBox="0 0 24 24">
                        <path d="M16.4 13.1c0-2.2 1.8-3.3 1.9-3.4-1-1.5-2.6-1.7-3.1-1.7-1.3-.1-2.6.8-3.3.8-.7 0-1.7-.8-2.8-.8-1.5 0-2.9.9-3.7 2.2-1.6 2.8-.4 6.9 1.1 9.2.8 1.1 1.7 2.3 2.9 2.3 1.1 0 1.6-.7 3-.7s1.8.7 3 .7c1.2 0 2-1.1 2.8-2.2.9-1.3 1.2-2.5 1.2-2.6-.1 0-2.9-1.1-3-3.8ZM14.3 6.6c.6-.8 1-1.8.9-2.9-.9 0-1.9.6-2.6 1.3-.6.7-1.1 1.8-1 2.8 1 .1 2-.5 2.7-1.2Z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</section>