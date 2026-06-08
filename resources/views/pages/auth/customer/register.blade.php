<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.customer-auth')]
#[Title('Register - Compify')]
class extends Component {
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    private function safeCustomerRedirectUrl(): string
    {
        $intended = session()->pull('url.intended', route('home'));

        $path = trim((string) parse_url($intended, PHP_URL_PATH), '/');

        $adminPanelPath = trim((string) config('compify.admin_panel_path'), '/');
        $adminLoginPath = trim((string) config('compify.admin_login_path'), '/');

        if (
            ($adminPanelPath !== '' && str_starts_with($path, $adminPanelPath)) ||
            ($adminLoginPath !== '' && str_starts_with($path, $adminLoginPath))
        ) {
            return route('home');
        }

        return $intended ?: route('home');
    }

    public function register(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'customer',
        ]);

        Auth::guard('customer')->login($user, false);

        request()->session()->regenerate();

        Auth::shouldUse('customer');

        $this->redirect($this->safeCustomerRedirectUrl(), navigate: true);
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
                alt="Register Illustration"
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

            <h1>Create account</h1>

            <p class="auth-switch-text">
                Sudah punya akun?
                <a href="{{ route('customer.login') }}" wire:navigate>Login disini</a>
            </p>

            @if($errors->any())
                <div class="auth-figma-alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form wire:submit="register" class="auth-figma-form">
                <label class="auth-input-wrap">
                    <span class="auth-input-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5Z"/>
                        </svg>
                    </span>

                    <input type="text" wire:model.defer="name" placeholder="Nama lengkap">
                </label>

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
                            <path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.8 21 3 13.2 3 3.7c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.3 1.1l-2.2 2.2Z"/>
                        </svg>
                    </span>

                    <input type="text" wire:model.defer="phone" placeholder="Nomor HP">
                </label>

                <label class="auth-input-wrap">
                    <span class="auth-input-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M7 8V6a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h1Zm2 0h6V6a3 3 0 0 0-6 0v2Zm-3 2v9h12v-9H6Z"/>
                        </svg>
                    </span>

                    <input type="password" wire:model.defer="password" placeholder="Password">
                </label>

                <label class="auth-input-wrap">
                    <span class="auth-input-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M7 8V6a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h1Zm2 0h6V6a3 3 0 0 0-6 0v2Zm-3 2v9h12v-9H6Z"/>
                        </svg>
                    </span>

                    <input type="password" wire:model.defer="password_confirmation" placeholder="Konfirmasi password">
                </label>

                <button type="submit" class="auth-submit-btn">
                    Register
                </button>
            </form>

            <div class="auth-social-title">or register with</div>

            <div class="auth-social-row">
                <a href="{{ route('customer.google.redirect') }}" class="auth-social-icon" aria-label="Register with Google">
                    <svg viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.4-.2-2H12v3.8h5.4c-.2 1.2-.9 2.2-1.9 2.9v2.4h3.1c1.8-1.7 3-4.1 3-7.1Z"/>
                        <path fill="#34A853" d="M12 22c2.7 0 5-.9 6.6-2.5l-3.1-2.4c-.9.6-2 1-3.5 1-2.7 0-4.9-1.8-5.7-4.2H3.1v2.5C4.7 19.7 8.1 22 12 22Z"/>
                        <path fill="#FBBC05" d="M6.3 13.9c-.2-.6-.3-1.2-.3-1.9s.1-1.3.3-1.9V7.6H3.1C2.4 8.9 2 10.4 2 12s.4 3.1 1.1 4.4l3.2-2.5Z"/>
                        <path fill="#EA4335" d="M12 5.9c1.5 0 2.8.5 3.8 1.5l2.9-2.9C16.9 2.9 14.7 2 12 2 8.1 2 4.7 4.3 3.1 7.6l3.2 2.5C7.1 7.7 9.3 5.9 12 5.9Z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>