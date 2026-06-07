<x-layouts.admin>
    <x-slot name="title">Pengaturan Contact - Admin Compify</x-slot>

    <style>
        .contact-settings-wrap {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: var(--admin-grid-gap);
            align-items: start;
        }

        .settings-card {
            background: var(--admin-panel);
            border: 1px solid var(--admin-border);
            border-radius: calc(var(--admin-radius) + 2px);
            overflow: hidden;
        }

        .settings-card-header {
            padding: 18px 22px 14px;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-card-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--admin-radius);
            background: var(--admin-accent, #3b82f6);
            display: grid;
            place-items: center;
            flex-shrink: 0;
            opacity: .9;
        }

        .settings-card-icon svg {
            width: 16px;
            height: 16px;
            stroke: #fff;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .settings-card-header h3 {
            font-size: 13px;
            font-weight: 850;
            color: var(--admin-text);
            margin: 0;
            letter-spacing: -.01em;
        }

        .settings-card-header p {
            font-size: 12px;
            color: var(--admin-muted);
            margin: 2px 0 0;
        }

        .settings-card-body {
            padding: 20px 22px;
            display: grid;
            gap: 16px;
        }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .field-group label {
            font-size: 12px;
            font-weight: 800;
            color: var(--admin-text);
            letter-spacing: .01em;
            text-transform: uppercase;
        }

        .field-group .field-hint {
            font-size: 11.5px;
            color: var(--admin-muted);
            margin-top: 4px;
            line-height: 1.5;
        }

        .field-group input,
        .field-group textarea {
            min-height: var(--admin-control-height, 38px);
            border: 1px solid var(--admin-border-strong, #d1d5db);
            border-radius: var(--admin-radius);
            padding: 0 11px;
            background: var(--admin-bg, #f9fafb);
            color: var(--admin-text);
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            transition: border-color .15s, box-shadow .15s;
            width: 100%;
            box-sizing: border-box;
        }

        .field-group textarea {
            padding: 9px 11px;
            resize: vertical;
            line-height: 1.5;
        }

        .field-group input:focus,
        .field-group textarea:focus {
            outline: none;
            border-color: var(--admin-accent, #3b82f6);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--admin-accent, #3b82f6) 12%, transparent);
        }

        .field-group .field-error {
            font-size: 11.5px;
            color: #dc2626;
            font-weight: 600;
            margin-top: 2px;
        }

        .field-required {
            color: #dc2626;
            margin-left: 2px;
        }

        /* Sidebar sticky */
        .settings-sidebar {
            display: grid;
            gap: var(--admin-grid-gap);
            position: sticky;
            top: 16px;
        }

        /* Save card */
        .save-card {
            background: var(--admin-panel);
            border: 1px solid var(--admin-border);
            border-radius: calc(var(--admin-radius) + 2px);
            overflow: hidden;
        }

        .save-card-body {
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-save-primary {
            width: 100%;
            min-height: 40px;
            border-radius: var(--admin-radius);
            background: var(--admin-accent, #3b82f6);
            color: #fff;
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            letter-spacing: -.01em;
            transition: opacity .15s, transform .1s;
        }

        .btn-save-primary:hover { opacity: .88; }
        .btn-save-primary:active { transform: scale(.98); }

        .btn-save-primary svg {
            width: 15px;
            height: 15px;
            stroke: #fff;
            fill: none;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .btn-preview {
            width: 100%;
            min-height: 36px;
            border-radius: var(--admin-radius);
            background: transparent;
            border: 1px solid var(--admin-border-strong);
            color: var(--admin-text);
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
            transition: background .15s, border-color .15s;
        }

        .btn-preview:hover {
            background: var(--admin-hover, rgba(0,0,0,.04));
            border-color: var(--admin-text);
        }

        /* Preview card */
        .preview-card {
            background: var(--admin-panel);
            border: 1px solid var(--admin-border);
            border-radius: calc(var(--admin-radius) + 2px);
            overflow: hidden;
        }

        .preview-card-header {
            padding: 14px 18px;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .preview-card-header h3 {
            font-size: 12.5px;
            font-weight: 850;
            margin: 0;
            color: var(--admin-text);
        }

        .preview-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: .4; }
        }

        .preview-info-list {
            padding: 14px 18px;
            display: grid;
            gap: 12px;
        }

        .preview-info-item {
            display: grid;
            gap: 2px;
        }

        .preview-info-item .pil-label {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--admin-muted);
        }

        .preview-info-item .pil-value {
            font-size: 13px;
            font-weight: 700;
            color: var(--admin-text);
        }

        .preview-info-item .pil-empty {
            font-size: 13px;
            color: var(--admin-muted);
            font-style: italic;
        }

        .preview-divider {
            height: 1px;
            background: var(--admin-border);
            margin: 0 18px;
        }

        /* Notification toggle area */
        .notif-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: color-mix(in srgb, #f59e0b 10%, transparent);
            border: 1px solid color-mix(in srgb, #f59e0b 30%, transparent);
            color: #b45309;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 9px;
        }

        .notif-badge svg {
            width: 11px;
            height: 11px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
        }

        @media (max-width: 900px) {
            .contact-settings-wrap {
                grid-template-columns: 1fr;
            }
            .settings-sidebar {
                position: static;
            }
        }
    </style>

    <div class="admin-page-v2">

        {{-- ── PAGE HEADER ──────────────────────────────────────── --}}
        <div class="admin-section-title-v2">
            <div>
                <a href="{{ route('admin.contact.index') }}" class="admin-back-link-v2" wire:navigate>
                    ← Kembali ke Pesan Masuk
                </a>
                <h2>Pengaturan Contact</h2>
                <p>Teks halaman, info kontak, dan notifikasi pesan masuk.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-alert-v2 admin-alert-v2--success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.contact.settings.update') }}">
            @csrf

            <div class="contact-settings-wrap">

                {{-- ── KIRI ── --}}
                <div style="display:grid; gap:var(--admin-grid-gap);">

                    {{-- Teks Halaman --}}
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon">
                                <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h10M4 18h7"/></svg>
                            </div>
                            <div>
                                <h3>Teks Halaman</h3>
                                <p>Heading dan deskripsi di bagian atas halaman Contact Us</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="field-row">
                                <div class="field-group">
                                    <label>Heading<span class="field-required">*</span></label>
                                    <input
                                        type="text"
                                        name="heading"
                                        value="{{ old('heading', $setting->heading) }}"
                                        placeholder="Contact Us"
                                        required
                                    >
                                    @error('heading')
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="field-group">
                                    <label>Subheading</label>
                                    <input
                                        type="text"
                                        name="subheading"
                                        value="{{ old('subheading', $setting->subheading) }}"
                                        placeholder="Hubungi Kami"
                                    >
                                    @error('subheading')
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="field-group">
                                <label>Deskripsi Singkat</label>
                                <textarea
                                    name="description"
                                    rows="3"
                                    placeholder="Ada pertanyaan? Kami siap membantu."
                                >{{ old('description', $setting->description) }}</textarea>
                                @error('description')
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Info Kontak --}}
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon" style="background: #6366f1;">
                                <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.07 12 19.79 19.79 0 0 1 1 3.18a2 2 0 0 1 2-2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/></svg>
                            </div>
                            <div>
                                <h3>Info Kontak</h3>
                                <p>Ditampilkan di sisi kiri halaman Contact Us</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="field-row">
                                <div class="field-group">
                                    <label>Nomor Telepon / WA</label>
                                    <input
                                        type="text"
                                        name="phone"
                                        value="{{ old('phone', $setting->phone) }}"
                                        placeholder="+62 812 XXXX XXXX"
                                    >
                                    @error('phone')
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="field-group">
                                    <label>Email Kontak</label>
                                    <input
                                        type="email"
                                        name="email"
                                        value="{{ old('email', $setting->email) }}"
                                        placeholder="hello@compify.id"
                                    >
                                    @error('email')
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="field-group">
                                <label>Alamat</label>
                                <input
                                    type="text"
                                    name="address"
                                    value="{{ old('address', $setting->address) }}"
                                    placeholder="Jl. Contoh No. 123"
                                >
                                @error('address')
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="field-row">
                                <div class="field-group">
                                    <label>Kota</label>
                                    <input
                                        type="text"
                                        name="address_city"
                                        value="{{ old('address_city', $setting->address_city) }}"
                                        placeholder="Jakarta"
                                    >
                                    @error('address_city')
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="field-group">
                                    <label>Negara</label>
                                    <input
                                        type="text"
                                        name="address_country"
                                        value="{{ old('address_country', $setting->address_country) }}"
                                        placeholder="Indonesia"
                                    >
                                    @error('address_country')
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="field-group">
                                <label>Jam Operasional</label>
                                <input
                                    type="text"
                                    name="open_hours"
                                    value="{{ old('open_hours', $setting->open_hours) }}"
                                    placeholder="Senin – Sabtu, 09.00 – 18.00 WIB"
                                >
                                @error('open_hours')
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Notifikasi --}}
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon" style="background: #f59e0b;">
                                <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            </div>
                            <div>
                                <h3>Notifikasi Pesan Masuk</h3>
                                <p>Pemberitahuan otomatis saat ada pesan baru</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="field-group">
                                <label>Email Penerima Notifikasi</label>
                                <input
                                    type="email"
                                    name="notify_email"
                                    value="{{ old('notify_email', $setting->notify_email) }}"
                                    placeholder="admin@compify.id"
                                >
                                <span class="field-hint">Pesan masuk akan diteruskan ke email ini. Fitur email harus dikonfigurasi di <code>.env</code>. Kosongkan jika tidak diperlukan.</span>
                                @error('notify_email')
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="field-group">
                                <label>Nomor WA Penerima Notifikasi</label>
                                <input
                                    type="text"
                                    name="notify_phone"
                                    value="{{ old('notify_phone', $setting->notify_phone) }}"
                                    placeholder="628xxxxxxxxxx"
                                >
                                <span class="field-hint">Digunakan jika integrasi Fonnte aktif. Format internasional tanpa tanda <code>+</code>.</span>
                                @error('notify_phone')
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                </div>

                {{-- ── KANAN (sidebar) ── --}}
                <div class="settings-sidebar">

                    {{-- Tombol Simpan --}}
                    <div class="save-card">
                        <div class="save-card-body">
                            <button type="submit" class="btn-save-primary">
                                <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                Simpan Pengaturan
                            </button>
                            <a href="{{ route('contact') }}"
                               target="_blank"
                               rel="noopener"
                               class="btn-preview">
                                <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/></svg>
                                Lihat Halaman Contact
                            </a>
                        </div>
                    </div>

                    {{-- Preview Info --}}
                    <div class="preview-card">
                        <div class="preview-card-header">
                            <h3>Preview Info Kontak</h3>
                            <div class="preview-dot" title="Live preview"></div>
                        </div>
                        <div class="preview-info-list">
                            <div class="preview-info-item">
                                <span class="pil-label">Telepon / WA</span>
                                @if($setting->phone)
                                    <span class="pil-value">{{ $setting->phone }}</span>
                                @else
                                    <span class="pil-empty">Belum diisi</span>
                                @endif
                            </div>
                            <div class="preview-divider"></div>
                            <div class="preview-info-item">
                                <span class="pil-label">Email</span>
                                @if($setting->email)
                                    <span class="pil-value">{{ $setting->email }}</span>
                                @else
                                    <span class="pil-empty">Belum diisi</span>
                                @endif
                            </div>
                            <div class="preview-divider"></div>
                            <div class="preview-info-item">
                                <span class="pil-label">Alamat</span>
                                @php
                                    $fullAddress = collect([$setting->address, $setting->address_city, $setting->address_country])->filter()->join(', ');
                                @endphp
                                @if($fullAddress)
                                    <span class="pil-value">{{ $fullAddress }}</span>
                                @else
                                    <span class="pil-empty">Belum diisi</span>
                                @endif
                            </div>
                            @if($setting->open_hours)
                                <div class="preview-divider"></div>
                                <div class="preview-info-item">
                                    <span class="pil-label">Jam Operasional</span>
                                    <span class="pil-value">{{ $setting->open_hours }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Notif status badge --}}
                    @if($setting->notify_email || $setting->notify_phone)
                        <div style="padding:12px 14px; background:var(--admin-panel); border:1px solid var(--admin-border); border-radius:calc(var(--admin-radius) + 2px); display:flex; align-items:center; justify-content:space-between;">
                            <span style="font-size:12px; font-weight:700; color:var(--admin-text);">Notifikasi</span>
                            <span class="notif-badge">
                                <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                Aktif
                            </span>
                        </div>
                    @else
                        <div style="padding:12px 14px; background:var(--admin-panel); border:1px solid var(--admin-border); border-radius:calc(var(--admin-radius) + 2px); display:flex; align-items:center; justify-content:space-between;">
                            <span style="font-size:12px; font-weight:700; color:var(--admin-text);">Notifikasi</span>
                            <span style="font-size:11px; color:var(--admin-muted); font-weight:700; background:var(--admin-bg); border:1px solid var(--admin-border); border-radius:999px; padding:3px 9px;">Nonaktif</span>
                        </div>
                    @endif

                </div>
            </div>

        </form>

    </div>
</x-layouts.admin>