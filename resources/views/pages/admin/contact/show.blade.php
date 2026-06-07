<x-layouts.admin>
    <x-slot name="title">Detail Pesan - Admin Compify</x-slot>

    <div class="admin-page-v2">

        {{-- ── PAGE HEADER ──────────────────────────────────────── --}}
        <div class="admin-section-title-v2">
            <div>
                <a href="{{ route('admin.contact.index') }}" class="admin-back-link-v2" wire:navigate>
                    ← Kembali ke Pesan Masuk
                </a>
                <h2>Detail Pesan</h2>
            </div>

            <div class="admin-order-title-actions-v2">
                {{-- Quick status update --}}
                <form method="POST" action="{{ route('admin.contact.status', $message) }}">
                    @csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()"
                            style="min-height:var(--admin-control-height); border:1px solid var(--admin-border-strong); border-radius:var(--admin-radius); padding:0 11px; background:var(--admin-panel); color:var(--admin-text); font-family:inherit; font-size:13px; font-weight:750; cursor:pointer;">
                        @foreach(\App\Models\ContactMessage::STATUSES as $s)
                            <option value="{{ $s }}" {{ $message->status === $s ? 'selected' : '' }}>
                                @switch($s)
                                    @case('unread')   Belum Dibaca @break
                                    @case('read')     Dibaca @break
                                    @case('replied')  Dibalas @break
                                    @case('archived') Diarsipkan @break
                                    @default {{ ucfirst($s) }}
                                @endswitch
                            </option>
                        @endforeach
                    </select>
                </form>

                <form method="POST"
                      action="{{ route('admin.contact.destroy', $message) }}"
                      onsubmit="return confirm('Hapus pesan ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="admin-btn danger">
                        Hapus Pesan
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-alert-v2 admin-alert-v2--success">{{ session('success') }}</div>
        @endif

        <div style="display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:var(--admin-grid-gap); align-items:start;">

            {{-- ── LEFT: Pesan ── --}}
            <div class="admin-panel-v2">

                {{-- Sender header --}}
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:16px; padding-bottom:16px; border-bottom:1px solid var(--admin-border);">
                    <span class="admin-avatar-v2"
                          style="width:44px; height:44px; border-radius:var(--admin-radius); font-size:18px; font-weight:950; flex-shrink:0; display:grid; place-items:center;">
                        {{ strtoupper(substr($message->name, 0, 1)) }}
                    </span>
                    <div style="min-width:0; flex:1;">
                        <strong style="display:block; font-size:15px; font-weight:950;">{{ $message->name }}</strong>
                        <span style="color:var(--admin-muted); font-size:13px;">{{ $message->email }}</span>
                    </div>
                    <span class="admin-status-v2 {{ $message->statusBadgeClass() }}"
                          style="min-height:22px; padding:3px 10px; border-radius:999px; display:inline-flex; align-items:center; font-size:11px; font-weight:900; flex-shrink:0;">
                        {{ $message->statusLabel() }}
                    </span>
                </div>

                @if($message->subject)
                    <p style="margin:0 0 14px; font-size:13px; color:var(--admin-muted);">
                        <strong style="color:var(--admin-text);">Subject:</strong> {{ $message->subject }}
                    </p>
                @endif

                <div style="font-size:14px; line-height:1.7; color:var(--admin-text); white-space:pre-wrap; word-break:break-word;">
                    {!! nl2br(e($message->message)) !!}
                </div>
            </div>

            {{-- ── RIGHT: Sidebar ── --}}
            <div style="display:grid; gap:var(--admin-grid-gap);">

                {{-- Info Pengirim --}}
                <div class="admin-panel-v2">
                    <h2>Info Pengirim</h2>

                    <div class="admin-info-list-v2" style="margin-top:10px;">
                        <div>
                            <span>Nama</span>
                            <strong>{{ $message->name }}</strong>
                        </div>
                        <div>
                            <span>Email</span>
                            <strong>
                                <a href="mailto:{{ $message->email }}"
                                   style="color:var(--admin-text); text-decoration:underline;">
                                    {{ $message->email }}
                                </a>
                            </strong>
                        </div>
                        @if($message->phone)
                            <div>
                                <span>Telepon</span>
                                <strong>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $message->phone) }}"
                                       target="_blank" rel="noopener"
                                       style="color:var(--admin-text); text-decoration:underline;">
                                        {{ $message->phone }}
                                    </a>
                                </strong>
                            </div>
                        @endif
                        <div>
                            <span>Dikirim</span>
                            <strong>{{ $message->created_at->translatedFormat('d F Y, H:i') }}</strong>
                        </div>
                        <div>
                            <span>IP Address</span>
                            <strong>{{ $message->ip_address ?? '-' }}</strong>
                        </div>
                    </div>
                </div>

                {{-- Balas Cepat --}}
                <div class="admin-panel-v2">
                    <h2>Balas Cepat</h2>
                    <p>Klik tombol di bawah untuk membuka email client dan membalas langsung.</p>

                    <div class="admin-actions" style="margin:10px 0 0; flex-direction:column;">
                        <a href="mailto:{{ $message->email }}?subject=Re: {{ urlencode($message->subject ?? 'Pesan dari ' . $message->name) }}"
                           class="admin-btn"
                           style="width:100%; justify-content:center;">
                            Balas via Email
                        </a>

                        @if($message->phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $message->phone) }}"
                               target="_blank" rel="noopener"
                               class="admin-btn secondary"
                               style="width:100%; justify-content:center;">
                                WhatsApp
                            </a>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
</x-layouts.admin>