<?php

use App\Models\ShippingMethod;
use App\Models\ShippingSetting;
use Illuminate\Support\Str;
use App\Services\Shipping\RajaOngkirShippingService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Shipping Methods - Admin Compify')]
class extends Component {
    public ?int $editingId = null;

    public string $name = '';
    public string $description = '';
    public string $base_cost = '0';
    public string $same_district_cost = '';
    public string $same_city_cost = '';
    public string $same_province_cost = '';
    public string $outside_province_cost = '';
    public string $free_shipping_min = '';
    public string $estimate = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public string $country = 'Indonesia';
    public string $province = 'Jawa Tengah';
    public string $city = 'Jepara';
    public string $district = 'Bangsri';
    public string $postal_code = '';

    public string $shipping_api_provider = 'manual';
    public bool $shipping_api_enabled = false;
    public string $shipping_api_key = '';
    public string $shipping_api_origin_area_id = '';
    public string $shipping_api_origin_label = '';
    public string $shipping_api_couriers = 'jne,jnt,sicepat,anteraja,pos';
    public int $shipping_api_default_weight_gram = 1000;
    public int $shipping_api_cache_minutes = 30;
    public bool $shipping_api_fallback_manual = true;

    public string $origin_search = '';
    public array $origin_results = [];
    public ?string $origin_search_error = null;

    public function mount(): void
    {
        $setting = ShippingSetting::firstOrCreate(
            ['id' => 1],
            [
                'country' => 'Indonesia',
                'province' => 'Jawa Tengah',
                'city' => 'Jepara',
                'district' => 'Bangsri',
                'shipping_api_provider' => config('shipping_api.default_provider', 'manual'),
                'shipping_api_enabled' => (bool) config('shipping_api.enabled', false),
                'shipping_api_fallback_manual' => (bool) config('shipping_api.fallback_manual', true),
                'shipping_api_default_weight_gram' => 1000,
                'shipping_api_cache_minutes' => (int) config('shipping_api.cache_minutes', 30),
            ]
        );

        $this->country = $setting->country;
        $this->province = $setting->province;
        $this->city = $setting->city;
        $this->district = $setting->district;
        $this->postal_code = $setting->postal_code ?? '';

        $this->shipping_api_provider = $setting->shipping_api_provider ?: 'manual';
        $this->shipping_api_enabled = (bool) $setting->shipping_api_enabled;
        $this->shipping_api_key = '';
        $this->shipping_api_origin_area_id = $setting->shipping_api_origin_area_id ?? '';
        $this->shipping_api_origin_label = $setting->shipping_api_origin_label ?? '';
        $this->shipping_api_couriers = $setting->shipping_api_couriers ?: 'jne,jnt,sicepat,anteraja,pos';
        $this->shipping_api_default_weight_gram = $setting->shipping_api_default_weight_gram ?: 1000;
        $this->shipping_api_cache_minutes = $setting->shipping_api_cache_minutes ?? 30;
        $this->shipping_api_fallback_manual = (bool) $setting->shipping_api_fallback_manual;
    }

    #[Computed]
    public function shippingMethods()
    {
        return ShippingMethod::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function providerOptions(): array
    {
        return [
            'manual' => 'Manual dulu',
            'rajaongkir' => 'RajaOngkir',
            'biteship' => 'Biteship',
        ];
    }

    public function apiKeyStatus(): string
    {
        $setting = ShippingSetting::find(1);
        $provider = $this->shipping_api_provider;

        if ($setting?->shipping_api_key) {
            return 'API key tersimpan di database.';
        }

        if ($provider !== 'manual' && filled(config("shipping_api.providers.{$provider}.api_key"))) {
            return 'API key terdeteksi dari .env.';
        }

        return 'API key belum diisi.';
    }

    public function apiReadinessLabel(): string
    {
        if ($this->shipping_api_provider === 'manual' || ! $this->shipping_api_enabled) {
            return 'Mode manual aktif.';
        }

        if (blank($this->shipping_api_origin_area_id)) {
            return 'Perlu Origin Area ID.';
        }

        if ($this->apiKeyStatus() === 'API key belum diisi.' && blank($this->shipping_api_key)) {
            return 'Perlu API key.';
        }

        return 'Siap untuk tahap integrasi API.';
    }

    public function searchOriginArea(): void
    {
        $this->origin_search_error = null;
        $this->origin_results = [];

        if ($this->shipping_api_provider !== 'rajaongkir') {
            $this->origin_search_error = 'Search origin tahap ini baru tersedia untuk RajaOngkir.';
            return;
        }

        if (mb_strlen(trim($this->origin_search)) < 3) {
            $this->origin_search_error = 'Ketik minimal 3 karakter lokasi toko.';
            return;
        }

        try {
            $this->origin_results = app(RajaOngkirShippingService::class)
                ->searchDomesticDestination($this->origin_search, 8, 0);

            if ($this->origin_results === []) {
                $this->origin_search_error = 'Lokasi tidak ditemukan. Coba kata kunci lain, misalnya kota/kecamatan/kode pos.';
            }
        } catch (\Throwable $e) {
            report($e);

            $this->origin_search_error = config('app.debug')
                ? $e->getMessage()
                : 'Gagal menghubungi RajaOngkir. Cek API key dan koneksi.';
        }
    }

    public function selectOriginArea(string $id, string $label): void
    {
        $this->shipping_api_origin_area_id = $id;
        $this->shipping_api_origin_label = $label;
        $this->origin_search = $label;
        $this->origin_results = [];
        $this->origin_search_error = null;
    }

    public function clearOriginSearch(): void
    {
        $this->origin_search = '';
        $this->origin_results = [];
        $this->origin_search_error = null;
    }

    public function saveSetting(): void
    {
        $this->validate([
            'country' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],

            'shipping_api_provider' => ['required', 'in:manual,rajaongkir,biteship'],
            'shipping_api_enabled' => ['boolean'],
            'shipping_api_key' => ['nullable', 'string', 'max:500'],
            'shipping_api_origin_area_id' => ['nullable', 'string', 'max:100'],
            'shipping_api_origin_label' => ['nullable', 'string', 'max:500'],
            'shipping_api_couriers' => ['nullable', 'string', 'max:255'],
            'shipping_api_default_weight_gram' => ['required', 'integer', 'min:1', 'max:200000'],
            'shipping_api_cache_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'shipping_api_fallback_manual' => ['boolean'],
        ]);

        $payload = [
            'country' => $this->country,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'postal_code' => $this->postal_code ?: null,

            'shipping_api_provider' => $this->shipping_api_provider,
            'shipping_api_enabled' => (bool) $this->shipping_api_enabled,
            'shipping_api_origin_area_id' => $this->shipping_api_origin_area_id ?: null,
            'shipping_api_origin_label' => $this->shipping_api_origin_label ?: null,
            'shipping_api_couriers' => $this->shipping_api_couriers ?: null,
            'shipping_api_default_weight_gram' => $this->shipping_api_default_weight_gram,
            'shipping_api_cache_minutes' => $this->shipping_api_cache_minutes,
            'shipping_api_fallback_manual' => (bool) $this->shipping_api_fallback_manual,
        ];

        if (filled($this->shipping_api_key)) {
            $payload['shipping_api_key'] = $this->shipping_api_key;
        }

        ShippingSetting::updateOrCreate(['id' => 1], $payload);

        $this->shipping_api_key = '';

        session()->flash('success', 'Setting pengiriman berhasil disimpan.');
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_cost' => ['required', 'numeric', 'min:0'],
            'same_district_cost' => ['nullable', 'numeric', 'min:0'],
            'same_city_cost' => ['nullable', 'numeric', 'min:0'],
            'same_province_cost' => ['nullable', 'numeric', 'min:0'],
            'outside_province_cost' => ['nullable', 'numeric', 'min:0'],
            'free_shipping_min' => ['nullable', 'numeric', 'min:0'],
            'estimate' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        ShippingMethod::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'code' => Str::slug($this->name),
                'description' => $this->description ?: null,
                'base_cost' => (int) $this->base_cost,
                'same_district_cost' => $this->same_district_cost !== '' ? (int) $this->same_district_cost : null,
                'same_city_cost' => $this->same_city_cost !== '' ? (int) $this->same_city_cost : null,
                'same_province_cost' => $this->same_province_cost !== '' ? (int) $this->same_province_cost : null,
                'outside_province_cost' => $this->outside_province_cost !== '' ? (int) $this->outside_province_cost : null,
                'free_shipping_min' => $this->free_shipping_min !== '' ? (int) $this->free_shipping_min : null,
                'estimate' => $this->estimate ?: null,
                'is_active' => $this->is_active,
                'sort_order' => $this->sort_order,
            ]
        );

        $this->resetForm();

        session()->flash('success', 'Shipping method berhasil disimpan.');
    }

    public function edit(int $id): void
    {
        $method = ShippingMethod::findOrFail($id);

        $this->editingId = $method->id;
        $this->name = $method->name;
        $this->description = $method->description ?? '';
        $this->base_cost = (string) $method->base_cost;
        $this->same_district_cost = $method->same_district_cost !== null ? (string) $method->same_district_cost : '';
        $this->same_city_cost = $method->same_city_cost !== null ? (string) $method->same_city_cost : '';
        $this->same_province_cost = $method->same_province_cost !== null ? (string) $method->same_province_cost : '';
        $this->outside_province_cost = $method->outside_province_cost !== null ? (string) $method->outside_province_cost : '';
        $this->free_shipping_min = $method->free_shipping_min !== null ? (string) $method->free_shipping_min : '';
        $this->estimate = $method->estimate ?? '';
        $this->is_active = $method->is_active;
        $this->sort_order = $method->sort_order;
    }

    public function delete(int $id): void
    {
        ShippingMethod::findOrFail($id)->delete();

        session()->flash('success', 'Shipping method berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->base_cost = '0';
        $this->same_district_cost = '';
        $this->same_city_cost = '';
        $this->same_province_cost = '';
        $this->outside_province_cost = '';
        $this->free_shipping_min = '';
        $this->estimate = '';
        $this->is_active = true;
        $this->sort_order = 0;

        $this->resetValidation();
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <div>
            <h2>Shipping Methods</h2>
            <p>Atur metode pengiriman, base lokasi toko, estimasi biaya, dan gratis ongkir.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <section class="admin-panel-v2 admin-form admin-shipping-setting-v3">
        <div class="admin-shipping-setting-head-v3">
            <div>
                <h2>Base Lokasi & API Ongkir</h2>
                <p>Setting lokasi toko tetap dipakai untuk ongkir manual. Setting API disiapkan untuk tahap ongkir otomatis berikutnya.</p>
            </div>

            <span>{{ $this->apiReadinessLabel() }}</span>
        </div>

        <div class="admin-shipping-setting-block-v3">
            <div class="admin-shipping-block-title-v3">
                <strong>Base Lokasi Toko</strong>
                <small>Dipakai sebagai origin toko dan fallback ongkir manual.</small>
            </div>

            <div class="admin-grid">
                <label>
                    Negara
                    <input type="text" wire:model="country">
                </label>

                <label>
                    Provinsi
                    <input type="text" wire:model="province">
                </label>

                <label>
                    Kota / Kabupaten
                    <input type="text" wire:model="city">
                </label>

                <label>
                    Kecamatan
                    <input type="text" wire:model="district">
                </label>

                <label>
                    Kode Pos
                    <input type="text" wire:model="postal_code">
                </label>
            </div>
        </div>

        <div class="admin-shipping-setting-block-v3">
            <div class="admin-shipping-block-title-v3">
                <strong>Persiapan API Ongkir</strong>
            </div>

            <div class="admin-grid admin-shipping-api-grid-v3">
                <label>
                    Provider API
                    <select wire:model.live="shipping_api_provider">
                        @foreach($this->providerOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Status API
                    <select wire:model="shipping_api_enabled">
                        <option value="0">Nonaktif dulu</option>
                        <option value="1">Aktif</option>
                    </select>
                </label>

                <label>
                    API Key
                    <input
                        type="password"
                        wire:model="shipping_api_key"
                        placeholder="{{ $this->apiKeyStatus() }}"
                        autocomplete="off"
                    >
                </label>

                <label>
                    Origin Area ID
                    <input type="text" wire:model="shipping_api_origin_area_id" placeholder="Contoh: area/destination id dari provider">
                </label>

                <div class="admin-shipping-origin-search-v3 admin-shipping-api-wide-v3">
                    <div class="admin-shipping-origin-search-row-v3">
                        <label>
                            Cari Origin Toko
                            <input
                                type="text"
                                wire:model.live.debounce.500ms="origin_search"
                                placeholder="Contoh: Bangsri, Jepara, 59453"
                            >
                        </label>

                        <div class="admin-shipping-origin-actions-v3">
                            <button type="button" class="admin-btn secondary" wire:click="searchOriginArea">
                                Cari Origin
                            </button>

                            <button type="button" class="admin-btn secondary" wire:click="clearOriginSearch">
                                Bersihkan
                            </button>
                        </div>
                    </div>

                    @if($origin_search_error)
                        <div class="admin-shipping-api-error-v3">
                            {{ $origin_search_error }}
                        </div>
                    @endif

                    @if(! empty($origin_results))
                        <div class="admin-shipping-origin-results-v3">
                            @foreach($origin_results as $area)
                                <button
                                    type="button"
                                    wire:click="selectOriginArea('{{ $area['id'] }}', @js($area['label']))"
                                >
                                    <strong>{{ $area['label'] }}</strong>
                                    <small>
                                        ID: {{ $area['id'] }}
                                        @if($area['zip_code'])
                                            · {{ $area['zip_code'] }}
                                        @endif
                                    </small>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <label class="admin-shipping-api-wide-v3">
                    Label Origin
                    <input type="text" wire:model="shipping_api_origin_label" placeholder="Contoh: Bangsri, Jepara, Jawa Tengah">
                </label>

                <label>
                    Kode Kurir
                    <input type="text" wire:model="shipping_api_couriers" placeholder="jne,jnt,sicepat,anteraja,pos">
                </label>

                <label>
                    Berat Default
                    <input type="number" wire:model="shipping_api_default_weight_gram" min="1">
                </label>

                <label>
                    Cache Ongkir
                    <input type="number" wire:model="shipping_api_cache_minutes" min="0">
                </label>

                <label>
                    Fallback Manual
                    <select wire:model="shipping_api_fallback_manual">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="admin-actions">
            <button type="button" wire:click="saveSetting" class="admin-btn">
                Simpan Setting Pengiriman
            </button>
        </div>
    </section>

    <section class="admin-panel-v2 admin-form">
        <h2>{{ $editingId ? 'Edit Shipping Method' : 'Tambah Shipping Method' }}</h2>

        <div class="admin-grid">
            <label>
                Nama Metode
                <input type="text" wire:model="name" placeholder="Contoh: Reguler, Instant, COD Area Jepara">
            </label>

            <label>
                Estimasi
                <input type="text" wire:model="estimate" placeholder="Contoh: 1-3 hari">
            </label>

            <label>
                Biaya Dasar
                <input type="number" wire:model="base_cost" min="0">
            </label>

            <label>
                Biaya Satu Kecamatan
                <input type="number" wire:model="same_district_cost" min="0" placeholder="Kosongkan jika ikut biaya dasar">
            </label>

            <label>
                Biaya Satu Kota/Kabupaten
                <input type="number" wire:model="same_city_cost" min="0">
            </label>

            <label>
                Biaya Satu Provinsi
                <input type="number" wire:model="same_province_cost" min="0">
            </label>

            <label>
                Biaya Luar Provinsi
                <input type="number" wire:model="outside_province_cost" min="0">
            </label>

            <label>
                Gratis Ongkir Minimal Belanja
                <input type="number" wire:model="free_shipping_min" min="0" placeholder="Contoh: 3000000">
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
        </div>

        <label>
            Deskripsi
            <textarea wire:model="description" rows="3" placeholder="Contoh: Pengiriman standar untuk wilayah Jawa Tengah dan sekitarnya."></textarea>
        </label>

        <div class="admin-actions">
            <button type="submit" wire:click="save" class="admin-btn">
                Simpan Shipping
            </button>

            <button type="button" wire:click="resetForm" class="admin-btn secondary">
                Reset
            </button>
        </div>
    </section>

    <section class="admin-panel-v2">
        <div class="admin-table-head">
            <div>
                <h2>Daftar Shipping Method</h2>
                <p>Metode aktif akan tampil di checkout.</p>
            </div>
        </div>

        <table class="admin-table-v2">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Base</th>
                    <th>Wilayah Dekat</th>
                    <th>Luar Provinsi</th>
                    <th>Gratis Ongkir</th>
                    <th>Estimasi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->shippingMethods as $method)
                    <tr>
                        <td>
                            <strong>{{ $method->name }}</strong>
                            <br>
                            <small>{{ $method->description }}</small>
                        </td>

                        <td>Rp {{ number_format($method->base_cost, 0, ',', '.') }}</td>

                        <td>
                            Kecamatan: Rp {{ number_format($method->same_district_cost ?? $method->base_cost, 0, ',', '.') }}
                            <br>
                            Kota: Rp {{ number_format($method->same_city_cost ?? $method->base_cost, 0, ',', '.') }}
                            <br>
                            Provinsi: Rp {{ number_format($method->same_province_cost ?? $method->base_cost, 0, ',', '.') }}
                        </td>

                        <td>Rp {{ number_format($method->outside_province_cost ?? $method->base_cost, 0, ',', '.') }}</td>

                        <td>
                            @if($method->free_shipping_min)
                                Min. Rp {{ number_format($method->free_shipping_min, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>

                        <td>{{ $method->estimate ?? '-' }}</td>
                        <td>{{ $method->is_active ? 'Aktif' : 'Nonaktif' }}</td>

                        <td>
                            <button type="button" wire:click="edit({{ $method->id }})" class="admin-btn">
                                Edit
                            </button>

                            <button
                                type="button"
                                wire:click="delete({{ $method->id }})"
                                wire:confirm="Yakin hapus shipping method ini?"
                                class="admin-btn danger"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">Belum ada shipping method.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>