<?php

use App\Models\ContactMessage;
use App\Models\ContactSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.shop')]
#[Title('Contact Us - Compify')]
class extends Component {

    public string $name    = '';
    public string $email   = '';
    public string $phone   = '';
    public string $subject = '';
    public string $message = '';

    public bool $sent = false;

    public function submit(): void
    {
        $this->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'name.required'    => 'Nama wajib diisi.',
            'email.required'   => 'Email wajib diisi.',
            'email.email'      => 'Format email tidak valid.',
            'message.required' => 'Pesan wajib diisi.',
        ]);

        ContactMessage::create([
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone ?: null,
            'subject'    => $this->subject ?: null,
            'message'    => $this->message,
            'status'     => ContactMessage::STATUS_UNREAD,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->reset(['name', 'email', 'phone', 'subject', 'message']);
        $this->sent = true;
    }

    public function with(): array
    {
        return [
            'setting' => ContactSetting::current(),
        ];
    }
}

?>
<div class="cp-root">

    {{-- ── MAIN ───────────────────────────────────────────────── --}}
    <main class="cp-main">
        <div class="cp-main__inner">

            {{-- ── LEFT: Info Kontak ──────────────────────────── --}}
            <aside class="cp-info">

                <div class="cp-info__page-header">
                    <h1 class="cp-info__h1">{{ $setting->heading }}</h1>
                    @if($setting->subheading)
                        <p class="cp-info__sub">{{ $setting->subheading }}</p>
                    @endif
                    @if($setting->description)
                        <p class="cp-info__desc">{{ $setting->description }}</p>
                    @endif
                </div>

                <div class="cp-info__items">

                    @if($setting->phone)
                        <div class="cp-info__item">
                            <div class="cp-info__item-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.73 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.64 1.27h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.91A16 16 0 0 0 15.09 16.09l.92-.92a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92Z"/>
                                </svg>
                            </div>
                            <div class="cp-info__item-body">
                                <strong>Phone Number</strong>
                                <span>{{ $setting->phone }}</span>
                            </div>
                        </div>
                    @endif

                    @if($setting->email)
                        <div class="cp-info__item">
                            <div class="cp-info__item-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                                </svg>
                            </div>
                            <div class="cp-info__item-body">
                                <strong>Email</strong>
                                <span>{{ $setting->email }}</span>
                            </div>
                        </div>
                    @endif

                    @if($setting->address)
                        <div class="cp-info__item">
                            <div class="cp-info__item-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </div>
                            <div class="cp-info__item-body">
                                <strong>Address</strong>
                                <span>
                                    {{ $setting->address }}@if($setting->address_city), {{ $setting->address_city }}@endif@if($setting->address_country), {{ $setting->address_country }}@endif
                                </span>
                            </div>
                        </div>
                    @endif

                    @if($setting->open_hours)
                        <div class="cp-info__item">
                            <div class="cp-info__item-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 6v6l4 2"/>
                                </svg>
                            </div>
                            <div class="cp-info__item-body">
                                <strong>Jam Operasional</strong>
                                <span>{{ $setting->open_hours }}</span>
                            </div>
                        </div>
                    @endif

                </div>
            </aside>

            {{-- ── RIGHT: Form ─────────────────────────────────── --}}
            <section class="cp-form-card" aria-label="Form Pesan">

                <div class="cp-form-card__header">
                    <div>
                        <h2 class="cp-form-card__title">Send a Message</h2>
                        <p class="cp-form-card__sub">Isi form di bawah dan kami akan segera menghubungi kamu.</p>
                    </div>
                    <div class="cp-form-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4 20-7Z"/>
                        </svg>
                    </div>
                </div>

                @if($sent)
                    <div class="cp-success" role="alert">
                        <div class="cp-success__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m5 12 5 5L20 7"/>
                            </svg>
                        </div>
                        <div>
                            <strong>Pesan Terkirim!</strong>
                            <p>Kami akan segera menghubungi kamu melalui email atau nomor telepon yang diberikan.</p>
                        </div>
                    </div>
                @endif

                <form wire:submit="submit" class="cp-form" novalidate>

                    <div class="cp-form__row">
                        <div class="cp-form__field">
                            <label class="cp-form__label" for="cp-name">
                                Nama <span class="cp-form__required" aria-hidden="true">*</span>
                            </label>
                            <input
                                id="cp-name"
                                type="text"
                                wire:model="name"
                                placeholder="Nama lengkap kamu"
                                class="cp-form__input {{ $errors->has('name') ? 'cp-form__input--error' : '' }}"
                                autocomplete="name"
                            >
                            @error('name')
                                <span class="cp-form__error" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="cp-form__field">
                            <label class="cp-form__label" for="cp-phone">
                                Nomor HP <span class="cp-form__optional">(opsional)</span>
                            </label>
                            <input
                                id="cp-phone"
                                type="tel"
                                wire:model="phone"
                                placeholder="+62 812 XXXX XXXX"
                                class="cp-form__input {{ $errors->has('phone') ? 'cp-form__input--error' : '' }}"
                                autocomplete="tel"
                            >
                            @error('phone')
                                <span class="cp-form__error" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="cp-form__field">
                        <label class="cp-form__label" for="cp-email">
                            Email <span class="cp-form__required" aria-hidden="true">*</span>
                        </label>
                        <input
                            id="cp-email"
                            type="email"
                            wire:model="email"
                            placeholder="email@kamu.com"
                            class="cp-form__input {{ $errors->has('email') ? 'cp-form__input--error' : '' }}"
                            autocomplete="email"
                        >
                        @error('email')
                            <span class="cp-form__error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="cp-form__field">
                        <label class="cp-form__label" for="cp-subject">
                            Subject <span class="cp-form__optional">(opsional)</span>
                        </label>
                        <input
                            id="cp-subject"
                            type="text"
                            wire:model="subject"
                            placeholder="Tentang apa pesan ini?"
                            class="cp-form__input {{ $errors->has('subject') ? 'cp-form__input--error' : '' }}"
                        >
                        @error('subject')
                            <span class="cp-form__error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="cp-form__field">
                        <label class="cp-form__label" for="cp-message">
                            Pesan <span class="cp-form__required" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            id="cp-message"
                            wire:model="message"
                            placeholder="Tulis pesanmu di sini…"
                            rows="5"
                            class="cp-form__input cp-form__textarea {{ $errors->has('message') ? 'cp-form__input--error' : '' }}"
                        ></textarea>
                        @error('message')
                            <span class="cp-form__error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="cp-form__submit" wire:loading.attr="disabled">
                        <span wire:loading.remove class="cp-form__submit-inner">
                            Kirim Pesan
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4 20-7Z"/>
                            </svg>
                        </span>
                        <span wire:loading class="cp-form__submit-loading">
                            <svg class="cp-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                            </svg>
                            Mengirim…
                        </span>
                    </button>

                </form>
            </section>

        </div>
    </main>

</div>