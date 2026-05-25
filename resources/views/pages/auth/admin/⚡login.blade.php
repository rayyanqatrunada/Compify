<?php

use App\Models\ShopSetting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.auth')]
#[Title('Admin Login - Compify')]
class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public string $site_name = 'Compify';
    public ?string $support_email = null;
    public ?string $support_phone = null;
    public string $login_heading = 'Admin Sign In';
    public string $login_subheading = 'Masuk ke dashboard Compify';
    public string $login_showcase_title = 'Manage your store beautifully';
    public string $login_showcase_text = 'Kelola produk, banner, kategori, dan seluruh tampilan toko dari satu dashboard.';
    public ?string $login_image = null;

    public function mount(): void
    {
        $setting = ShopSetting::first();

        if ($setting) {
            $this->site_name = $setting->site_name ?: 'Compify';
            $this->support_email = $setting->support_email;
            $this->support_phone = $setting->support_phone;
            $this->login_heading = $setting->login_heading ?: 'Admin Sign In';
            $this->login_subheading = $setting->login_subheading ?: 'Masuk ke dashboard Compify';
            $this->login_showcase_title = $setting->login_showcase_title ?: 'Manage your store beautifully';
            $this->login_showcase_text = $setting->login_showcase_text ?: 'Kelola produk, banner, kategori, dan seluruh tampilan toko dari satu dashboard.';
            $this->login_image = $setting->login_image;
        }
    }

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ]);

        if (! Auth::guard('admin')->attempt($credentials, false)) {
            $this->addError('email', 'Email atau password salah.');
            return;
        }

        request()->session()->regenerate();

        if (Auth::guard('admin')->user()->role !== 'admin') {
            Auth::guard('admin')->logout();

            $this->addError('email', 'Akun ini tidak memiliki akses admin.');
            return;
        }

        $this->redirect(route('admin.dashboard'), navigate: true);
    }
};
?>

{{-- design theme --}}
<section class="auth-page-v2">
    @php
        $loginImageUrl = $login_image
            ? asset('storage/' . $login_image)
            : null;
    @endphp

    <div
        class="auth-bg-v2"
        @if($loginImageUrl)
            style="background-image: linear-gradient(90deg, rgba(17, 19, 34, .96), rgba(17, 19, 34, .82), rgba(17, 19, 34, .45)), url('{{ $loginImageUrl }}')"
        @endif
    >
        <a href="{{ route('home') }}" class="auth-home-btn" wire:navigate>
            ← Back to website
        </a>

        <div class="auth-login-box-v2">
            <p class="auth-start-text">Private admin access</p>

            <h1>{{ $login_heading }}<span>.</span></h1>

            <p class="auth-login-subtitle">
                {{ $login_subheading }}
            </p>

            @if ($errors->any())
                <div class="auth-alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form wire:submit="login" class="auth-form-v2">
                <label class="auth-input-v2">
                    <span>Email</span>
                    <input type="email" wire:model.defer="email" placeholder="admin@compify.test">
                    @error('email') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <label class="auth-input-v2">
                    <span>Password</span>
                    <input type="password" wire:model.defer="password" placeholder="••••••••">
                    @error('password') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <button type="submit" class="auth-primary-v2">
                    Login
                </button>
            </form>
        </div>

        <div class="auth-login-caption-v2">
            <h2>{{ $login_showcase_title }}</h2>
            <p>{{ $login_showcase_text }}</p>
        </div>

        <div class="auth-corner-logo">
            <img src="{{ asset('assets/brand/compify-logo.svg') }}" alt="Compify Logo">
        </div>
    </div>
</section>

{{-- design theme 2 --}}
{{-- <section class="auth-page">
    <div class="auth-card">
        <div class="auth-form-panel">
            <div class="auth-topbar">
                <div class="auth-brand">
                    <div class="auth-brand-mark">C</div>
                    <div>
                        <strong>{{ $site_name }}</strong>
                        <span>Admin Access</span>
                    </div>
                </div>

                <a href="{{ route('home') }}" class="auth-back-link" wire:navigate>
                    Back to website →
                </a>
            </div>

            <div class="auth-form-wrap">
                <p class="auth-eyebrow">Private admin portal</p>
                <h1>{{ $login_heading }}</h1>
                <p class="auth-subtitle">{{ $login_subheading }}</p>

                @if ($errors->any())
                    <div class="auth-alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form wire:submit="login" class="auth-form">
                    <div class="auth-field">
                        <label>Email</label>
                        <input type="email" wire:model.defer="email" placeholder="Masukkan email admin">
                        @error('email') <small class="error-text">{{ $message }}</small> @enderror
                    </div>

                    <div class="auth-field">
                        <label>Password</label>
                        <input type="password" wire:model.defer="password" placeholder="Masukkan password">
                        @error('password') <small class="error-text">{{ $message }}</small> @enderror
                    </div>

                    <div class="auth-row">
                        <label class="auth-check">
                            <input type="checkbox" wire:model="remember">
                            <span>Remember me</span>
                        </label>
                    </div>

                    <button type="submit" class="auth-submit">
                        Login to dashboard
                    </button>
                </form>

                <div class="auth-help">
                    @if($support_email)
                        <p>Email: {{ $support_email }}</p>
                    @endif

                    @if($support_phone)
                        <p>Phone: {{ $support_phone }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="auth-showcase-panel">
            <div class="auth-showcase-overlay"></div>

            @if($login_image)
                <div class="auth-showcase-image">
                    <img src="{{ asset('storage/' . $login_image) }}" alt="Login artwork">
                </div>
            @else
                <div class="auth-showcase-placeholder">
                    <div>
                        <strong>Custom Anime Artwork</strong>
                        <span>Upload dari Admin Settings untuk menampilkan artwork login.</span>
                    </div>
                </div>
            @endif

            <div class="auth-showcase-content">
                <p class="auth-showcase-badge">Compify Dashboard</p>
                <h2>{{ $login_showcase_title }}</h2>
                <p>{{ $login_showcase_text }}</p>
            </div>
        </div>
    </div>
</section> --}}