<?php

use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\MidtransPaymentChannel;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Payment Methods - Admin Compify')]
class extends Component {
    use WithFileUploads;
    use WithPagination;

    public int $perPage = 10;

    public ?int $editingId = null;

    public string $name = '';
    public string $type = 'manual';
    public string $payment_url = '';

    public string $whatsapp_number = '';
    public string $whatsapp_template = '';
    public string $auto_redirect = '0';

    public string $api_provider = '';
    public string $api_endpoint = '';
    public string $midtrans_enabled_payment = '';
    public string $description = '';
    public string $instructions = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public $logoFile = null;
    public $qrFile = null;

    public ?string $currentLogo = null;
    public ?string $currentQr = null;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function methods()
    {
        return PaymentMethod::query()
            ->where('type', '!=', 'whatsapp')
            ->orderBy('sort_order')
            ->latest()
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:manual,url,qr,api'],

            'payment_url' => ['nullable', 'string', 'max:255'],

            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'whatsapp_template' => ['nullable', 'string'],
            'auto_redirect' => ['boolean'],

            'api_provider' => ['nullable', 'string', 'max:100'],
            'api_endpoint' => ['nullable', 'string', 'max:255'],
            'midtrans_enabled_payment' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'instructions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],

            'logoFile' => ['nullable', 'image', 'max:2048'],
            'qrFile' => ['nullable', 'image', 'max:4096'],
        ]);

        $slug = Str::slug($this->name);

        $payload = [
            'name' => $this->name,
            'slug' => $slug,
            'type' => $this->type,

            'payment_url' => $this->type === 'url'
                ? ($this->payment_url ?: null)
                : null,

            'whatsapp_number' => null,
            'whatsapp_template' => null,
            'auto_redirect' => false,

            'api_provider' => $this->type === 'api'
                ? ($this->api_provider ?: null)
                : null,

            'midtrans_enabled_payments' => $this->type === 'api' && strtolower($this->api_provider) === 'midtrans' && $this->midtrans_enabled_payment !== ''
                ? [$this->midtrans_enabled_payment]
                : null,

            'api_endpoint' => $this->type === 'api'
                ? ($this->api_endpoint ?: null)
                : null,

            'description' => $this->description ?: null,
            'instructions' => $this->instructions ?: null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->logoFile) {
            if ($this->currentLogo && Storage::disk('public')->exists($this->currentLogo)) {
                Storage::disk('public')->delete($this->currentLogo);
            }

            $payload['logo'] = $this->logoFile->store('payment-methods/logos', 'public');
        }

        if ($this->qrFile) {
            if ($this->currentQr && Storage::disk('public')->exists($this->currentQr)) {
                Storage::disk('public')->delete($this->currentQr);
            }

            $payload['qr_image'] = $this->qrFile->store('payment-methods/qr', 'public');
        }

        if ($this->editingId) {
            PaymentMethod::findOrFail($this->editingId)->update($payload);
        } else {
            PaymentMethod::create($payload);
        }

        session()->flash('success', 'Payment method berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $method = PaymentMethod::findOrFail($id);

        $this->editingId = $method->id;
        $this->name = $method->name;
        $this->type = $method->type === 'whatsapp' ? 'manual' : $method->type;

        $this->payment_url = $method->payment_url ?? '';

        $this->whatsapp_number = $method->whatsapp_number ?? '';
        $this->whatsapp_template = $method->whatsapp_template ?? '';
        $this->auto_redirect = $method->auto_redirect ? '1' : '0';

        $this->api_provider = $method->api_provider ?? '';
        $this->api_endpoint = $method->api_endpoint ?? '';
        $this->midtrans_enabled_payment = $method->midtrans_channel_code ?? '';
        $this->description = $method->description ?? '';
        $this->instructions = $method->instructions ?? '';
        $this->is_active = (bool) $method->is_active;
        $this->sort_order = $method->sort_order ?? 0;

        $this->currentLogo = $method->logo;
        $this->currentQr = $method->qr_image;

        $this->logoFile = null;
        $this->qrFile = null;
    }

    public function delete(int $id): void
    {
        $method = PaymentMethod::findOrFail($id);

        foreach ([$method->logo, $method->qr_image] as $image) {
            if ($image && Storage::disk('public')->exists($image)) {
                Storage::disk('public')->delete($image);
            }
        }

        $method->delete();

        session()->flash('success', 'Payment method berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;

        $this->name = '';
        $this->type = 'manual';
        $this->payment_url = '';

        $this->whatsapp_number = '';
        $this->whatsapp_template = '';
        $this->auto_redirect = '0';

        $this->api_provider = '';
        $this->api_endpoint = '';
        $this->midtrans_enabled_payment = '';
        $this->description = '';
        $this->instructions = '';
        $this->is_active = true;
        $this->sort_order = 0;

        $this->logoFile = null;
        $this->qrFile = null;

        $this->currentLogo = null;
        $this->currentQr = null;

        $this->resetValidation();
    }

    public function typeLabel(?string $type): string
    {
        return match ($type) {
            'manual' => 'MANUAL',
            'qr' => 'QR',
            'url' => 'URL',
            'api' => 'API',
            default => strtoupper((string) $type),
        };
    }

    public function midtransOptions(): array
    {
        return MidtransPaymentChannel::options();
    }

    public function isMidtransApiForm(): bool
    {
        return $this->type === 'api' && strtolower(trim($this->api_provider)) === 'midtrans';
    }

    public function methodDetail(PaymentMethod $method): string
    {
        if ($method->is_midtrans) {
            return $method->midtrans_channel_label . ' via Midtrans';
        }

        if ($method->type === 'url') {
            return $method->payment_url ?: '-';
        }

        if ($method->type === 'api') {
            return $method->api_provider ?: '-';
        }

        return $method->description ?: '-';
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>Payment Methods</h2>
        <p>Atur metode pembayaran seperti QRIS, transfer bank, e-wallet, URL payment, atau API gateway seperti Midtrans.</p>
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form">
        <h2>{{ $editingId ? 'Edit Payment Method' : 'Tambah Payment Method' }}</h2>

        <div class="admin-grid">
            <label>
                Nama Metode
                <input type="text" wire:model="name" placeholder="Contoh: Midtrans, QRIS, BCA, DANA">
            </label>

            <label>
                Tipe
                <select wire:model.live="type">
                    <option value="manual">Manual / Transfer</option>
                    <option value="qr">QR Image</option>
                    <option value="url">Payment URL</option>
                    <option value="api">API Gateway</option>
                </select>
            </label>

            <label>
                Urutan
                <input type="number" wire:model="sort_order" min="0">
            </label>

            <label>
                Status
                <select wire:model="is_active">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>

            <label>
                Logo
                <input type="file" wire:model="logoFile" accept="image/*">
            </label>

            @if($type === 'qr')
                <label>
                    Gambar QR
                    <input type="file" wire:model="qrFile" accept="image/*">
                </label>
            @endif

            @if($type === 'url')
                <label>
                    Payment URL
                    <input type="text" wire:model="payment_url" placeholder="https://payment.example.com/...">
                </label>
            @endif

            @if($type === 'api')
                <label>
                    API Provider
                    <input type="text" wire:model="api_provider" placeholder="Contoh: xendit / midtrans">
                </label>

                <label>
                    API Endpoint
                    <input type="text" wire:model="api_endpoint" placeholder="Endpoint API pembayaran">
                </label>

                @if($this->isMidtransApiForm())
                    <label>
                        Channel Midtrans
                        <select wire:model="midtrans_enabled_payment">
                            <option value="">Semua channel aktif Midtrans</option>
                            @foreach($this->midtransOptions() as $code => $meta)
                                <option value="{{ $code }}">{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            @endif
        </div>

        <label>
            Deskripsi singkat
            <input type="text" wire:model="description" placeholder="Contoh: Scan QRIS melalui aplikasi bank/e-wallet">
        </label>

        <br>

        <label>
            Instruksi Pembayaran
            <textarea wire:model="instructions" rows="5" placeholder="Contoh: Transfer ke rekening 123456 a.n. Compify, lalu admin akan verifikasi pembayaran."></textarea>
        </label>

        <div class="home-section-preview-grid">
            <div>
                <strong>Logo</strong>

                @if($logoFile)
                    <img src="{{ $logoFile->temporaryUrl() }}" alt="Logo Preview">
                @elseif($currentLogo)
                    <img src="{{ Storage::url($currentLogo) }}" alt="Logo">
                @else
                    <span>Belum ada logo</span>
                @endif
            </div>

            <div>
                <strong>QR Image</strong>

                @if($qrFile)
                    <img src="{{ $qrFile->temporaryUrl() }}" alt="QR Preview">
                @elseif($currentQr)
                    <img src="{{ Storage::url($currentQr) }}" alt="QR">
                @else
                    <span>Belum ada QR</span>
                @endif
            </div>
        </div>

        <div class="admin-actions">
            <button class="admin-btn" type="submit">
                {{ $editingId ? 'Update Payment Method' : 'Simpan Payment Method' }}
            </button>

            <button class="admin-btn secondary" type="button" wire:click="resetForm">
                Reset
            </button>
        </div>
    </form>

    <div class="admin-panel-v2">
        <div class="admin-table-head">
            <h2>Data Payment Methods</h2>

            <select wire:model.live="perPage">
                <option value="10">10 data</option>
                <option value="20">20 data</option>
                <option value="50">50 data</option>
            </select>
        </div>

        <table class="admin-table-v2">
            <thead>
                <tr>
                    <th>Logo</th>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Detail</th>
                    <th>Status</th>
                    <th>Urutan</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->methods as $method)
                    <tr>
                        <td>
                            @if($method->logo)
                                <img src="{{ Storage::url($method->logo) }}" class="admin-table-thumb" alt="{{ $method->name }}">
                            @else
                                -
                            @endif
                        </td>

                        <td>{{ $method->name }}</td>

                        <td>{{ $this->typeLabel($method->type) }}</td>

                        <td>
                            <small>{{ $this->methodDetail($method) }}</small>
                        </td>

                        <td>{{ $method->is_active ? 'Aktif' : 'Nonaktif' }}</td>

                        <td>{{ $method->sort_order }}</td>

                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $method->id }})">
                                Edit
                            </button>

                            <button
                                class="admin-btn danger"
                                type="button"
                                wire:click="delete({{ $method->id }})"
                                wire:confirm="Yakin hapus payment method ini?"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Belum ada metode pembayaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->methods->links() }}
        </div>
    </div>
</div>