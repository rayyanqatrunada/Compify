<?php

use App\Models\ContactMessage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Pesan Masuk - Admin Compify')]
class extends Component {
    use WithPagination;

    #[Url(as: 'status')]
    public string $filterStatus = 'all';

    #[Url(as: 'search')]
    public string $search = '';

    public int $perPage = 20;

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        ContactMessage::findOrFail($id)->delete();
        session()->flash('success', 'Pesan berhasil dihapus.');
    }

    #[Computed]
    public function messages()
    {
        return ContactMessage::query()
            ->when($this->filterStatus !== 'all', fn ($q) =>
                $q->where('status', $this->filterStatus)
            )
            ->when($this->search, fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('name',    'like', "%{$this->search}%")
                       ->orWhere('email',   'like', "%{$this->search}%")
                       ->orWhere('subject', 'like', "%{$this->search}%")
                )
            )
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'all'      => ContactMessage::count(),
            'unread'   => ContactMessage::where('status', 'unread')->count(),
            'read'     => ContactMessage::where('status', 'read')->count(),
            'replied'  => ContactMessage::where('status', 'replied')->count(),
            'archived' => ContactMessage::where('status', 'archived')->count(),
        ];
    }
}

?>

<div class="admin-page-v2">

    {{-- ── PAGE HEADER ──────────────────────────────────────── --}}
    <div class="admin-section-title-v2">
        <div>
            <h2>Pesan Masuk</h2>
            <p>Daftar pesan dari halaman Contact Us</p>
        </div>
        <div class="admin-page-actions">
            <a href="{{ route('admin.contact.settings') }}" wire:navigate>
                Pengaturan Halaman
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">{{ session('success') }}</div>
    @endif

    {{-- ── TABS ──────────────────────────────────────────────── --}}
    @php
        $counts = $this->counts;
        $tabs = [
            'all'      => ['label' => 'Semua',        'count' => $counts['all']],
            'unread'   => ['label' => 'Belum Dibaca', 'count' => $counts['unread']],
            'read'     => ['label' => 'Dibaca',       'count' => $counts['read']],
            'replied'  => ['label' => 'Dibalas',      'count' => $counts['replied']],
            'archived' => ['label' => 'Arsip',        'count' => $counts['archived']],
        ];
    @endphp

    <div class="admin-tabs-v2">
        @foreach($tabs as $key => $tab)
            <button
                type="button"
                wire:click="$set('filterStatus', '{{ $key }}')"
                class="admin-tab-v2 {{ $filterStatus === $key ? 'active' : '' }}"
            >
                {{ $tab['label'] }}
                @if($tab['count'] > 0)
                    <span class="tab-badge">{{ $tab['count'] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- ── TABLE + SEARCH ───────────────────────────────────── --}}
    <div class="admin-panel-v2">
        <div class="admin-table-head">
            <h2>
                @if($filterStatus === 'all') Semua Pesan
                @elseif($filterStatus === 'unread') Belum Dibaca
                @elseif($filterStatus === 'read') Sudah Dibaca
                @elseif($filterStatus === 'replied') Dibalas
                @elseif($filterStatus === 'archived') Arsip
                @endif
            </h2>
            <div class="admin-table-tools">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama, email, atau subject…"
                >
                <select wire:model.live="perPage">
                    <option value="20">20 data</option>
                    <option value="50">50 data</option>
                    <option value="100">100 data</option>
                </select>
            </div>
        </div>

        <div class="admin-table-wrap-v2">
            <table class="admin-table-v2">
                <thead>
                    <tr>
                        <th>Pengirim</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Diterima</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php $messages = $this->messages; @endphp
                    @forelse($messages as $msg)
                        <tr class="{{ $msg->status === 'unread' ? 'row-unread' : '' }}">
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="admin-avatar-v2" style="width:34px; height:34px; border-radius:var(--admin-radius); font-size:13px; font-weight:950; flex-shrink:0; display:grid; place-items:center;">
                                        {{ strtoupper(substr($msg->name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <strong style="display:block; font-size:13px; font-weight:{{ $msg->status === 'unread' ? '900' : '700' }}; color:var(--admin-text);">
                                            {{ $msg->name }}
                                        </strong>
                                        <span style="color:var(--admin-muted); font-size:12px;">{{ $msg->email }}</span>
                                        @if($msg->phone)
                                            <span style="display:block; color:var(--admin-muted); font-size:12px;">{{ $msg->phone }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong style="display:block; font-size:13px; font-weight:{{ $msg->status === 'unread' ? '900' : '700' }};">
                                    {{ $msg->subject ?: '(Tanpa subject)' }}
                                </strong>
                                <span style="color:var(--admin-muted); font-size:12px;">
                                    {{ Str::limit($msg->message, 60) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $statusClass = match($msg->status) {
                                        'unread'   => 'admin-status-v2--warning',
                                        'read'     => 'admin-status-v2--info',
                                        'replied'  => 'admin-status-v2--success',
                                        'archived' => 'admin-status-v2--neutral',
                                        default    => 'admin-status-v2--neutral',
                                    };
                                    $statusLabel = match($msg->status) {
                                        'unread'   => 'Belum Dibaca',
                                        'read'     => 'Dibaca',
                                        'replied'  => 'Dibalas',
                                        'archived' => 'Diarsipkan',
                                        default    => ucfirst($msg->status),
                                    };
                                @endphp
                                <span class="admin-status-v2 {{ $statusClass }}"
                                      style="min-height:22px; padding:3px 8px; border-radius:999px; display:inline-flex; align-items:center; font-size:11px; font-weight:900;">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td>
                                <span style="display:block; font-size:13px;">
                                    {{ $msg->created_at->format('d M Y') }}
                                </span>
                                <span style="color:var(--admin-muted); font-size:12px;">
                                    {{ $msg->created_at->format('H:i') }}
                                </span>
                            </td>
                            <td>
                                <div class="admin-table-actions-v2">
                                    <a href="{{ route('admin.contact.show', $msg) }}"
                                       class="admin-btn-v2 admin-btn-v2--sm"
                                       wire:navigate>
                                        Lihat
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="delete({{ $msg->id }})"
                                        onclick="return confirm('Hapus pesan ini?')"
                                        class="admin-btn-v2 admin-btn-v2--sm admin-btn-v2--danger">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="admin-empty-v2">Belum ada pesan masuk.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->messages->hasPages())
            <div class="admin-pagination">
                {{ $this->messages->links() }}
            </div>
        @endif
    </div>

</div>