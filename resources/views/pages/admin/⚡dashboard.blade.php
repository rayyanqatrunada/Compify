<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.shop')]
#[Title('Login Admin - Compify')]
class extends Component {
    public string $email = '';
    public string $password = '';

    public function login()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->addError('email', 'Email atau password salah.');
            return;
        }

        request()->session()->regenerate();

        if (auth()->user()->role !== 'admin') {
            Auth::logout();
            $this->addError('email', 'Akun ini bukan admin.');
            return;
        }

        return redirect()->route('admin.dashboard');
    }
};
?>

<section class="section" style="max-width: 520px; margin: auto;">
    <div class="section-title">
        <h2>Login Admin</h2>
    </div>

    <form wire:submit="login" class="admin-card admin-form">
        <label>
            Email
            <input type="email" wire:model="email">
            @error('email') <span class="error-text">{{ $message }}</span> @enderror
        </label>

        <br>

        <label>
            Password
            <input type="password" wire:model="password">
            @error('password') <span class="error-text">{{ $message }}</span> @enderror
        </label>

        <div class="admin-actions">
            <button class="admin-btn" type="submit">Masuk</button>
        </div>
    </form>
</section>