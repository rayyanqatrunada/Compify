<?php

use App\Models\EventSetting;
use App\Models\UniversalDiscountUsage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Diskon Pembelian - Compify')]
class extends Component
{
    use WithPagination;

    public ?EventSetting $event = null;

    public string $universal_discount_mode = 'off';
    public string $universal_discount_scope = 'exclude_flash_and_combo';
    public ?string $universal_discount_starts_at = null;
    public ?string $universal_discount_ends_at = null;
    public int $universal_discount_batch = 1;
    public ?string $universal_discount_campaign_key = null;

    public array $universal_discount_tiers = [];

    public string $search = '';
    public string $usageFilter = 'current';
    public int $perPage = 10;

    public function mount(): void
    {
        $this->event = EventSetting::query()->firstOrCreate([], [
            'title' => 'Flash Sale',
            'subtitle' => 'berakhir dalam',
            'is_active' => false,

            'show_hero_section' => true,
            'show_flash_sale_section' => true,
            'show_full_banner_section' => true,
            'show_combo_package_section' => true,

            'universal_discount_mode' => 'off',
            'universal_discount_scope' => 'exclude_flash_and_combo',
            'universal_discount_batch' => 1,
            'universal_discount_campaign_key' => 'universal-discount-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(5)),
        ]);

        $this->loadSetting();
    }

    private function loadSetting(): void
    {
        $this->event->refresh();

        $this->universal_discount_mode = $this->event->universal_discount_mode ?: 'off';
        $this->universal_discount_scope = $this->event->universal_discount_scope ?: 'exclude_flash_and_combo';

        $this->universal_discount_starts_at = $this->event->universal_discount_starts_at?->format('Y-m-d\TH:i');
        $this->universal_discount_ends_at = $this->event->universal_discount_ends_at?->format('Y-m-d\TH:i');

        $this->universal_discount_batch = (int) ($this->event->universal_discount_batch ?: 1);

        $this->universal_discount_campaign_key = $this->event->universal_discount_campaign_key
            ?: 'universal-discount-batch-' . $this->universal_discount_batch;

        $this->universal_discount_tiers = $this->event->universalDiscountTiers()
            ->get()
            ->map(fn ($tier) => [
                'min_purchase' => (string) (int) $tier->min_purchase,
                'discount_percent' => (string) (float) $tier->discount_percent,
                'is_active' => $tier->is_active ? '1' : '0',
                'sort_order' => (int) $tier->sort_order,
            ])
            ->values()
            ->all();

        if ($this->universal_discount_tiers === []) {
            $this->addUniversalDiscountTier();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedUsageFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function usages()
    {
        $search = trim($this->search);

        return UniversalDiscountUsage::query()
            ->with(['user', 'order'])
            ->when($this->usageFilter === 'current' && $this->universal_discount_campaign_key, function ($query) {
                $query->where('campaign_key', $this->universal_discount_campaign_key);
            })
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($q) use ($like) {
                    $q->where('campaign_key', 'like', $like)
                        ->orWhereHas('user', function ($userQuery) use ($like) {
                            $userQuery->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhere('phone', 'like', $like);
                        })
                        ->orWhereHas('order', function ($orderQuery) use ($like) {
                            $orderQuery->where('order_number', 'like', $like)
                                ->orWhere('customer_name', 'like', $like)
                                ->orWhere('customer_email', 'like', $like)
                                ->orWhere('customer_phone', 'like', $like);
                        });
                });
            })
            ->latest('used_at')
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function currentCampaignUsageCount(): int
    {
        if (! $this->universal_discount_campaign_key) {
            return 0;
        }

        return UniversalDiscountUsage::query()
            ->where('campaign_key', $this->universal_discount_campaign_key)
            ->count();
    }

    #[Computed]
    public function currentCampaignDiscountTotal(): int
    {
        if (! $this->universal_discount_campaign_key) {
            return 0;
        }

        return (int) UniversalDiscountUsage::query()
            ->where('campaign_key', $this->universal_discount_campaign_key)
            ->sum('discount_amount');
    }

    public function addUniversalDiscountTier(): void
    {
        $this->universal_discount_tiers[] = [
            'min_purchase' => '',
            'discount_percent' => '',
            'is_active' => '1',
            'sort_order' => count($this->universal_discount_tiers),
        ];
    }

    public function removeUniversalDiscountTier(int $index): void
    {
        unset($this->universal_discount_tiers[$index]);

        $this->universal_discount_tiers = array_values($this->universal_discount_tiers);
    }

    public function save(): void
    {
        $this->validate([
            'universal_discount_mode' => ['required', 'in:off,event_only,always'],
            'universal_discount_scope' => ['required', 'in:regular_only,exclude_flash_and_combo,all_items'],
            'universal_discount_starts_at' => ['nullable', 'date'],
            'universal_discount_ends_at' => ['nullable', 'date', 'after_or_equal:universal_discount_starts_at'],
        ]);

        $tierRows = collect($this->universal_discount_tiers)
            ->filter(function ($tier) {
                return filled($tier['min_purchase'] ?? null)
                    || filled($tier['discount_percent'] ?? null);
            })
            ->values();

        if ($this->universal_discount_mode !== 'off' && $tierRows->isEmpty()) {
            $this->addError('universal_discount_tiers', 'Minimal tambahkan 1 tier diskon jika diskon pembelian aktif.');
            return;
        }

        $seenMinPurchases = [];

        foreach ($tierRows as $index => $tier) {
            $minPurchase = $tier['min_purchase'] ?? null;
            $discountPercent = $tier['discount_percent'] ?? null;

            if (! is_numeric($minPurchase) || (float) $minPurchase < 1) {
                $this->addError("universal_discount_tiers.$index.min_purchase", 'Minimal pembelian wajib berupa angka lebih dari 0.');
                return;
            }

            if (! is_numeric($discountPercent) || (float) $discountPercent < 0.01 || (float) $discountPercent > 100) {
                $this->addError("universal_discount_tiers.$index.discount_percent", 'Diskon persen harus antara 0.01 sampai 100.');
                return;
            }

            $minKey = (string) (int) $minPurchase;

            if (in_array($minKey, $seenMinPurchases, true)) {
                $this->addError("universal_discount_tiers.$index.min_purchase", 'Minimal pembelian tidak boleh duplikat.');
                return;
            }

            $seenMinPurchases[] = $minKey;
        }

        $campaignKey = $this->universal_discount_campaign_key;

        if (! $campaignKey) {
            $campaignKey = 'universal-discount-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(5));
        }

        $this->event->update([
            'universal_discount_mode' => $this->universal_discount_mode,
            'universal_discount_scope' => $this->universal_discount_scope,
            'universal_discount_starts_at' => $this->universal_discount_starts_at ?: null,
            'universal_discount_ends_at' => $this->universal_discount_ends_at ?: null,
            'universal_discount_batch' => max(1, (int) $this->universal_discount_batch),
            'universal_discount_campaign_key' => $campaignKey,
        ]);

        $this->event->universalDiscountTiers()->delete();

        foreach ($tierRows as $tier) {
            $this->event->universalDiscountTiers()->create([
                'min_purchase' => (float) $tier['min_purchase'],
                'discount_percent' => (float) $tier['discount_percent'],
                'is_active' => ($tier['is_active'] ?? '1') === '1',
                'sort_order' => (int) ($tier['sort_order'] ?? 0),
            ]);
        }

        $this->loadSetting();

        session()->flash('success', 'Pengaturan diskon pembelian berhasil disimpan.');
    }

    public function resetUniversalDiscountCampaign(): void
    {
        $nextBatch = max(1, (int) $this->event->universal_discount_batch) + 1;

        $this->event->update([
            'universal_discount_batch' => $nextBatch,
            'universal_discount_campaign_key' => 'universal-discount-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(5)),
        ]);

        $this->loadSetting();

        session()->flash('success', 'Campaign diskon berhasil direset. Campaign lama tidak dipakai lagi untuk checkout.');
    }

    public function modeLabel(?string $mode): string
    {
        return match ($mode) {
            'event_only' => 'Saat event aktif',
            'always' => 'Selalu aktif',
            default => 'Nonaktif',
        };
    }

    public function scopeLabel(?string $scope): string
    {
        return match ($scope) {
            'regular_only' => 'Regular saja',
            'all_items' => 'Semua item',
            default => 'Exclude flash & paket',
        };
    }

    public function formatRupiah(int|float|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    public function formatPercent(int|float|null $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',') . '%';
    }
};
?>

<div class="admin-page-v2 admin-event-page-v2 admin-discount-page">
    <div class="admin-section-title-v2">
        <div>
            <h2>Diskon Pembelian</h2>
            <p>Atur diskon minimal pembelian untuk checkout.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save">
        <section class="admin-panel-v2 admin-discount-panel">
            <div class="admin-discount-panel__head">
                <div>
                    <h3>Pengaturan Diskon</h3>
                    <p>Tentukan status, scope item, dan periode aktif.</p>
                </div>

                <button type="submit" class="admin-btn-v2 admin-btn-v2--primary">
                    Simpan
                </button>
            </div>

            <div class="admin-discount-form-grid">
                <label>
                    <span>Status</span>
                    <select wire:model.live="universal_discount_mode">
                        <option value="off">Nonaktif</option>
                        <option value="event_only">Saat event aktif</option>
                        <option value="always">Selalu aktif</option>
                    </select>
                    @error('universal_discount_mode')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Scope</span>
                    <select wire:model="universal_discount_scope">
                        <option value="regular_only">Regular saja</option>
                        <option value="exclude_flash_and_combo">Exclude flash & paket</option>
                        <option value="all_items">Semua item</option>
                    </select>
                    @error('universal_discount_scope')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Mulai</span>
                    <input type="datetime-local" wire:model="universal_discount_starts_at">
                    @error('universal_discount_starts_at')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Berakhir</span>
                    <input type="datetime-local" wire:model="universal_discount_ends_at">
                    @error('universal_discount_ends_at')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
            </div>

            <div class="admin-discount-meta">
                <div>
                    <span>Status</span>
                    <strong>{{ $this->modeLabel($universal_discount_mode) }}</strong>
                </div>

                <div>
                    <span>Scope</span>
                    <strong>{{ $this->scopeLabel($universal_discount_scope) }}</strong>
                </div>

                <div>
                    <span>Batch</span>
                    <strong>{{ $universal_discount_batch }}</strong>
                </div>

                <div>
                    <span>Dipakai</span>
                    <strong>{{ $this->currentCampaignUsageCount }}x</strong>
                </div>

                <div>
                    <span>Total Diskon</span>
                    <strong>{{ $this->formatRupiah($this->currentCampaignDiscountTotal) }}</strong>
                </div>
            </div>

            <div class="admin-discount-campaign">
                <div>
                    <span>Campaign aktif</span>
                    <code>{{ $universal_discount_campaign_key ?: 'Belum dibuat' }}</code>
                </div>

                <button
                    type="button"
                    class="admin-btn-v2 admin-btn-v2--secondary"
                    wire:click="resetUniversalDiscountCampaign"
                    wire:confirm="Reset campaign diskon? Customer yang sudah pernah memakai diskon bisa mendapatkan diskon lagi di campaign baru."
                >
                    Reset Campaign
                </button>
            </div>
        </section>

        <section class="admin-panel-v2 admin-discount-panel">
            <div class="admin-discount-panel__head">
                <div>
                    <h3>Tier Diskon</h3>
                    <p>Tier tertinggi yang memenuhi minimal pembelian akan dipakai.</p>
                </div>

                <button type="button" wire:click="addUniversalDiscountTier" class="admin-btn-v2 admin-btn-v2--secondary">
                    + Tier
                </button>
            </div>

            @error('universal_discount_tiers')
                <small class="admin-discount-error">{{ $message }}</small>
            @enderror

            <div class="admin-discount-tier-table">
                <div class="admin-discount-tier-row admin-discount-tier-row--head">
                    <span>Minimal Pembelian</span>
                    <span>Diskon (%)</span>
                    <span>Status</span>
                    <span>Urutan</span>
                    <span>Aksi</span>
                </div>

                @foreach($universal_discount_tiers as $index => $tier)
                    <div class="admin-discount-tier-row" wire:key="universal-discount-tier-{{ $index }}">
                        <label>
                            <input
                                type="number"
                                min="1000"
                                step="1000"
                                wire:model="universal_discount_tiers.{{ $index }}.min_purchase"
                                placeholder="1000000"
                            >
                            @error('universal_discount_tiers.' . $index . '.min_purchase')
                                <small>{{ $message }}</small>
                            @enderror
                        </label>

                        <label>
                            <input
                                type="number"
                                min="0.01"
                                max="100"
                                step="0.01"
                                wire:model="universal_discount_tiers.{{ $index }}.discount_percent"
                                placeholder="5"
                            >
                            @error('universal_discount_tiers.' . $index . '.discount_percent')
                                <small>{{ $message }}</small>
                            @enderror
                        </label>

                        <label>
                            <select wire:model="universal_discount_tiers.{{ $index }}.is_active">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </label>

                        <label>
                            <input
                                type="number"
                                min="0"
                                wire:model="universal_discount_tiers.{{ $index }}.sort_order"
                            >
                        </label>

                        <button
                            type="button"
                            wire:click="removeUniversalDiscountTier({{ $index }})"
                            class="admin-btn-v2 admin-btn-v2--danger"
                        >
                            Hapus
                        </button>
                    </div>
                @endforeach
            </div>
        </section>
    </form>

    <section class="admin-panel-v2 admin-discount-panel">
        <div class="admin-discount-panel__head">
            <div>
                <h3>Riwayat Pemakaian</h3>
                <p>Default hanya menampilkan campaign aktif agar campaign lama tidak terlihat menumpuk.</p>
            </div>

            <div class="admin-discount-history-actions">
                <select wire:model.live="usageFilter">
                    <option value="current">Campaign aktif</option>
                    <option value="all">Semua campaign</option>
                </select>

                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Cari customer / order"
                >

                <select wire:model.live="perPage">
                    <option value="10">10 data</option>
                    <option value="20">20 data</option>
                    <option value="50">50 data</option>
                </select>
            </div>
        </div>

        <div class="admin-discount-table-wrap">
            <table class="admin-table-v2 admin-discount-history-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Customer</th>
                        <th>Order</th>
                        <th>Campaign</th>
                        <th>Diskon</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($this->usages as $usage)
                        <tr>
                            <td>
                                <small>{{ $usage->used_at?->format('d M Y H:i') ?? $usage->created_at?->format('d M Y H:i') }}</small>
                            </td>

                            <td>
                                <strong>{{ $usage->user?->name ?? 'Customer' }}</strong>
                                <small>{{ $usage->user?->email ?? '-' }}</small>
                            </td>

                            <td>
                                @if($usage->order)
                                    <a href="{{ route('admin.sales.orders.show', $usage->order) }}" wire:navigate>
                                        {{ $usage->order->order_number }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                <code>{{ $usage->campaign_key }}</code>
                            </td>

                            <td>
                                <strong>- {{ $this->formatRupiah($usage->discount_amount) }}</strong>
                                <small>{{ $this->formatPercent($usage->discount_percent) }} dari {{ $this->formatRupiah($usage->eligible_subtotal) }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">Belum ada pemakaian diskon untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $this->usages->links() }}
        </div>
    </section>
</div>