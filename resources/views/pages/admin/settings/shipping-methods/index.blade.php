<?php

use App\Models\ShippingMethod;
use App\Models\ShippingSetting;
use Illuminate\Support\Str;
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

    public function mount(): void
    {
        $setting = ShippingSetting::firstOrCreate(
            ['id' => 1],
            [
                'country' => 'Indonesia',
                'province' => 'Jawa Tengah',
                'city' => 'Jepara',
                'district' => 'Bangsri',
            ]
        );

        $this->country = $setting->country;
        $this->province = $setting->province;
        $this->city = $setting->city;
        $this->district = $setting->district;
        $this->postal_code = $setting->postal_code ?? '';
    }

    #[Computed]
    public function shippingMethods()
    {
        return ShippingMethod::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function saveSetting(): void
    {
        $this->validate([
            'country' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        ShippingSetting::updateOrCreate(
            ['id' => 1],
            [
                'country' => $this->country,
                'province' => $this->province,
                'city' => $this->city,
                'district' => $this->district,
                'postal_code' => $this->postal_code ?: null,
            ]
        );

        session()->flash('success', 'Base lokasi pengiriman berhasil disimpan.');
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

    <section class="admin-panel-v2 admin-form">
        <h2>Base Lokasi Toko</h2>

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

        <div class="admin-actions">
            <button type="button" wire:click="saveSetting" class="admin-btn">
                Simpan Base Lokasi
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