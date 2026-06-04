<?php

use App\Models\FonnteMessageLog;
use App\Models\FonnteSetting;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Fonnte Settings - Admin Compify')]
class extends Component
{
    use WithPagination;

    public bool $is_active = false;
    public string $api_url = 'https://api.fonnte.com/send';
    public string $token = '';
    public string $admin_phone = '';

    public bool $send_customer_order_created = true;
    public bool $send_admin_order_created = true;

    public string $customer_order_created_template = '';
    public string $admin_order_created_template = '';

    public int $perPage = 10;

    public function mount(): void
    {
        $setting = FonnteSetting::current();

        $this->is_active = (bool) $setting->is_active;
        $this->api_url = $setting->api_url ?: 'https://api.fonnte.com/send';
        $this->token = $setting->token ?? '';
        $this->admin_phone = $setting->admin_phone ?? '';

        $this->send_customer_order_created = (bool) $setting->send_customer_order_created;
        $this->send_admin_order_created = (bool) $setting->send_admin_order_created;

        $this->customer_order_created_template = $setting->customer_order_created_template
            ?: FonnteSetting::defaultCustomerOrderTemplate();

        $this->admin_order_created_template = $setting->admin_order_created_template
            ?: FonnteSetting::defaultAdminOrderTemplate();
    }

    #[Computed]
    public function logs()
    {
        return FonnteMessageLog::query()
            ->with('order')
            ->latest()
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        $this->validate([
            'is_active' => ['boolean'],
            'api_url' => ['required', 'string', 'max:255'],
            'token' => ['nullable', 'string'],
            'admin_phone' => ['nullable', 'string', 'max:30'],

            'send_customer_order_created' => ['boolean'],
            'send_admin_order_created' => ['boolean'],

            'customer_order_created_template' => ['required', 'string'],
            'admin_order_created_template' => ['required', 'string'],
        ]);

        FonnteSetting::current()->update([
            'is_active' => $this->is_active,
            'api_url' => $this->api_url,
            'token' => $this->token ?: null,
            'admin_phone' => $this->admin_phone ?: null,

            'send_customer_order_created' => $this->send_customer_order_created,
            'send_admin_order_created' => $this->send_admin_order_created,

            'customer_order_created_template' => $this->customer_order_created_template,
            'admin_order_created_template' => $this->admin_order_created_template,
        ]);

        session()->flash('success', 'Fonnte settings berhasil disimpan.');
    }

    public function resetTemplates(): void
    {
        $this->customer_order_created_template = FonnteSetting::defaultCustomerOrderTemplate();
        $this->admin_order_created_template = FonnteSetting::defaultAdminOrderTemplate();

        session()->flash('success', 'Template berhasil dikembalikan ke default. Klik simpan untuk menyimpan perubahan.');
    }

    public function statusLabel(?string $status): string
    {
        return match ($status) {
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            default => 'Pending',
        };
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>Fonnte Settings</h2>
        <p>Atur pengiriman pesan WhatsApp otomatis menggunakan Fonnte.</p>
    </div>

    @if(session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form">
        <h2>Konfigurasi Fonnte</h2>

        <div class="admin-grid">
            <label>
                Status
                <select wire:model="is_active">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>

            <label>
                API URL
                <input type="text" wire:model="api_url" placeholder="https://api.fonnte.com/send">
            </label>

            <label>
                Token Fonnte
                <input type="password" wire:model="token" placeholder="Masukkan token Fonnte">
                <small class="admin-form-help-v2">
                    Simpan token dengan aman. Jangan dibagikan ke user atau frontend.
                </small>
            </label>

            <label>
                Nomor WhatsApp Admin
                <input type="text" wire:model="admin_phone" placeholder="Contoh: 6281234567890">
                <small class="admin-form-help-v2">
                    Nomor tujuan notifikasi admin. Boleh format 08xxx atau 62xxx.
                </small>
            </label>

            <label>
                Kirim ke Customer saat Order Dibuat
                <select wire:model="send_customer_order_created">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>

            <label>
                Kirim ke Admin saat Order Dibuat
                <select wire:model="send_admin_order_created">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>
        </div>

        <br>

        <label>
            Template Pesan Customer - Order Dibuat
            <textarea wire:model="customer_order_created_template" rows="10"></textarea>
        </label>

        <br>

        <label>
            Template Pesan Admin - Order Baru
            <textarea wire:model="admin_order_created_template" rows="10"></textarea>
        </label>

        <small class="admin-form-help-v2">
            Placeholder yang bisa dipakai:
            {order_number}, {customer_name}, {customer_phone}, {customer_email},
            {items}, {subtotal}, {shipping_cost}, {total_amount},
            {payment_method}, {shipping_method}, {shipping_address}, {payment_url}
        </small>

        <div class="admin-actions">
            <button type="submit" class="admin-btn">
                Simpan Fonnte Settings
            </button>

            <button type="button" wire:click="resetTemplates" class="admin-btn secondary">
                Reset Template
            </button>
        </div>
    </form>

    <div class="admin-panel-v2">
        <div class="admin-table-head">
            <h2>Log Pesan Fonnte</h2>

            <select wire:model.live="perPage">
                <option value="10">10 data</option>
                <option value="20">20 data</option>
                <option value="50">50 data</option>
            </select>
        </div>

        <table class="admin-table-v2">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Order</th>
                    <th>Event</th>
                    <th>Target</th>
                    <th>Status</th>
                    <th>Error</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->logs as $log)
                    <tr>
                        <td>
                            <small>{{ $log->created_at?->format('d M Y H:i') }}</small>
                        </td>

                        <td>
                            @if($log->order)
                                <strong>{{ $log->order->order_number }}</strong>
                            @else
                                -
                            @endif
                        </td>

                        <td>{{ $log->event_type ?: '-' }}</td>

                        <td>{{ $log->target ?: '-' }}</td>

                        <td>{{ $this->statusLabel($log->status) }}</td>

                        <td>
                            <small>{{ $log->error_message ?: '-' }}</small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada log pesan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->logs->links() }}
        </div>
    </div>
</div>