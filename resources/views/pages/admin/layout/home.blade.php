<?php

use App\Models\Category;
use App\Models\HomeLayoutGroup;
use App\Models\HomeLayoutSlot;
use App\Models\HomeSection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Home Layout - Admin Compify')]
class extends Component
{
    public ?int $groupId = null;
    public string $newGroupName = '';

    public array $layoutSlots = [];

    public function mount(): void
    {
        $group = HomeLayoutGroup::current();

        $this->groupId = $group->id;

        $this->loadSlots();
    }

    #[Computed]
    public function groups()
    {
        return HomeLayoutGroup::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function typeOptions(): array
    {
        return HomeLayoutSlot::typeOptions();
    }

    #[Computed]
    public function productSourceOptions(): array
    {
        return HomeLayoutSlot::productSourceOptions();
    }

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->active()
            ->with('parent')
            ->orderByRaw('parent_id is not null')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function fullBanners()
    {
        return HomeSection::query()
            ->where('section_type', 'story')
            ->where('display_style', 'full_banner')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function splitBanners()
    {
        return HomeSection::query()
            ->where('section_type', 'story')
            ->where('display_style', 'split')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function galleries()
    {
        return HomeSection::query()
            ->where('section_type', 'gallery')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    public function updatedGroupId(): void
    {
        $this->loadSlots();
    }

    public function selectedGroup(): HomeLayoutGroup
    {
        $group = HomeLayoutGroup::query()->find($this->groupId);

        if (! $group) {
            $group = HomeLayoutGroup::current();
            $this->groupId = $group->id;
        }

        return $group->ensureDefaultSlots();
    }

    public function loadSlots(): void
    {
        $group = $this->selectedGroup();

        $this->layoutSlots = [];

        foreach ($group->slots as $slot) {
            $this->layoutSlots[$slot->slot_number] = [
                'id' => $slot->id,
                'slot_number' => $slot->slot_number,
                'slot_type' => $slot->slot_type,
                'product_source' => $slot->product_source ?? HomeLayoutSlot::SOURCE_CATEGORY,
                'category_id' => $slot->category_id,
                'home_section_id' => $slot->home_section_id,
                'title' => $slot->title ?? '',
                'subtitle' => $slot->subtitle ?? '',
                'is_active' => (bool) $slot->is_active,
            ];
        }

        if ($this->layoutSlots === []) {
            for ($number = 1; $number <= 6; $number++) {
                $this->layoutSlots[$number] = [
                    'id' => null,
                    'slot_number' => $number,
                    'slot_type' => HomeLayoutSlot::TYPE_NONE,
                    'product_source' => HomeLayoutSlot::SOURCE_CATEGORY,
                    'category_id' => null,
                    'home_section_id' => null,
                    'title' => '',
                    'subtitle' => '',
                    'is_active' => true,
                ];
            }
        }

        ksort($this->layoutSlots);
    }

    public function createGroup(): void
    {
        $this->validate([
            'newGroupName' => ['required', 'string', 'max:255'],
        ]);

        $group = HomeLayoutGroup::create([
            'name' => $this->newGroupName,
            'is_active' => false,
        ]);

        // Group baru sengaja dibuat kosong semua.
        // Default/base layout hanya dipakai untuk group bernama "Default Layout".
        $group->ensureBlankSlots(7);

        $this->groupId = $group->id;
        $this->newGroupName = '';

        $this->loadSlots();

        session()->flash('success', 'Layout group baru berhasil dibuat.');
    }

    public function activateGroup(): void
    {
        $this->selectedGroup()->activate();

        session()->flash('success', 'Layout group berhasil diaktifkan.');
    }

    public function deleteGroup(): void
    {
        $group = $this->selectedGroup();

        if (HomeLayoutGroup::count() <= 1) {
            session()->flash('error', 'Minimal harus ada satu layout group.');
            return;
        }

        $wasActive = $group->is_active;

        $group->delete();

        $nextGroup = HomeLayoutGroup::query()->oldest()->first();

        if ($nextGroup && $wasActive) {
            $nextGroup->activate();
        }

        $this->groupId = $nextGroup?->id;
        $this->loadSlots();

        session()->flash('success', 'Layout group berhasil dihapus.');
    }

    public function addSlot(): void
    {
        $group = $this->selectedGroup();

        $nextNumber = ((int) $group->slots()->max('slot_number')) + 1;

        $group->slots()->create([
            'slot_number' => max(1, $nextNumber),
            'slot_type' => HomeLayoutSlot::TYPE_NONE,
            'product_source' => HomeLayoutSlot::SOURCE_CATEGORY,
            'category_id' => null,
            'home_section_id' => null,
            'title' => null,
            'subtitle' => null,
            'is_active' => true,
        ]);

        $this->loadSlots();

        session()->flash('success', 'List baru berhasil ditambahkan.');
    }

    public function removeLastSlot(): void
    {
        $group = $this->selectedGroup();

        $lastSlot = $group->slots()
            ->orderByDesc('slot_number')
            ->first();

        if (! $lastSlot) {
            return;
        }

        if ((int) $lastSlot->slot_number <= 1) {
            session()->flash('error', 'Minimal harus ada 1 list layout.');
            return;
        }

        $lastSlot->delete();

        $this->loadSlots();

        session()->flash('success', 'List terakhir berhasil dihapus.');
    }

    public function saveLayout(): void
    {
        $this->validate([
            'layoutSlots' => ['array'],
            'layoutSlots.*.slot_type' => ['required', 'in:none,product_display,full_banner,split_banner,gallery'],
            'layoutSlots.*.product_source' => ['nullable', 'in:category,best_seller,latest'],
            'layoutSlots.*.title' => ['nullable', 'string', 'max:255'],
            'layoutSlots.*.subtitle' => ['nullable', 'string', 'max:255'],
            'layoutSlots.*.is_active' => ['boolean'],
        ]);

        $group = $this->selectedGroup();

        foreach ($this->layoutSlots as $number => $slotData) {
            $slotType = $slotData['slot_type'] ?? HomeLayoutSlot::TYPE_NONE;

            $payload = [
                'slot_type' => $slotType,
                'product_source' => HomeLayoutSlot::SOURCE_CATEGORY,
                'title' => $slotData['title'] ?: null,
                'subtitle' => $slotData['subtitle'] ?: null,
                'is_active' => (bool) ($slotData['is_active'] ?? false),
            ];

            if ($slotType === HomeLayoutSlot::TYPE_PRODUCT_DISPLAY) {
                $productSource = $slotData['product_source'] ?? HomeLayoutSlot::SOURCE_CATEGORY;

                $payload['product_source'] = $productSource;
                $payload['home_section_id'] = null;

                if ($productSource === HomeLayoutSlot::SOURCE_CATEGORY) {
                    $payload['category_id'] = $slotData['category_id'] ?: null;
                } else {
                    $payload['category_id'] = null;
                }
            } elseif (in_array($slotType, [
                HomeLayoutSlot::TYPE_FULL_BANNER,
                HomeLayoutSlot::TYPE_SPLIT_BANNER,
                HomeLayoutSlot::TYPE_GALLERY,
            ], true)) {
                $payload['category_id'] = null;
                $payload['home_section_id'] = $slotData['home_section_id'] ?: null;
                $payload['product_source'] = HomeLayoutSlot::SOURCE_CATEGORY;
            } else {
                $payload['category_id'] = null;
                $payload['home_section_id'] = null;
                $payload['product_source'] = HomeLayoutSlot::SOURCE_CATEGORY;
                $payload['title'] = null;
                $payload['subtitle'] = null;
            }

            $group->slots()->updateOrCreate(
                ['slot_number' => (int) $number],
                $payload
            );
        }

        $this->loadSlots();

        session()->flash('success', 'Home layout berhasil disimpan.');
    }

    public function sectionOptionsForSlot(string $slotType)
    {
        return match ($slotType) {
            HomeLayoutSlot::TYPE_FULL_BANNER => $this->fullBanners,
            HomeLayoutSlot::TYPE_SPLIT_BANNER => $this->splitBanners,
            HomeLayoutSlot::TYPE_GALLERY => $this->galleries,
            default => collect(),
        };
    }
};
?>

<div class="admin-page-v2 admin-home-layout-page-v2">
    <div class="admin-section-title-v2">
        <h2>Home Layout</h2>
        <p>Atur isi slot homepage. Urutan slot tetap 1 sampai 6, yang diubah hanya isi tiap slot.</p>
    </div>

    @if(session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="admin-alert-v2 admin-alert-v2--danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="admin-grid admin-grid-2">
        <div class="admin-panel-v2 admin-form">
            <h2>Layout Group</h2>

            <label>
                Pilih Group
                <select wire:model.live="groupId">
                    @foreach($this->groups as $group)
                        <option value="{{ $group->id }}">
                            {{ $group->name }}{{ $group->is_active ? ' — Aktif' : '' }}
                        </option>
                    @endforeach
                </select>
            </label>

            <div class="admin-actions">
                <button type="button" class="admin-btn" wire:click="activateGroup">
                    Aktifkan Group Ini
                </button>

                <button
                    type="button"
                    class="admin-btn danger"
                    wire:click="deleteGroup"
                    wire:confirm="Yakin hapus layout group ini?"
                >
                    Hapus Group
                </button>
            </div>
        </div>

        <form wire:submit="createGroup" class="admin-panel-v2 admin-form">
            <h2>Buat Group Baru</h2>

            <label>
                Nama Group
                <input type="text" wire:model="newGroupName" placeholder="Contoh: Layout Promo Bulanan">
            </label>

            <button type="submit" class="admin-btn">
                Tambah Group
            </button>
        </form>
    </div>

    <form wire:submit="saveLayout" class="admin-panel-v2 admin-form admin-home-layout-form-v2">
        <div class="admin-table-head">
            <h2>Slot Layout Homepage</h2>

            <div class="admin-actions">
                <button type="button" class="admin-btn secondary" wire:click="addSlot">
                    + Tambah List
                </button>

                <button
                    type="button"
                    class="admin-btn danger"
                    wire:click="removeLastSlot"
                    wire:confirm="Yakin hapus list terakhir?"
                >
                    Hapus List Terakhir
                </button>

                <button type="submit" class="admin-btn">
                    Simpan Layout
                </button>
            </div>
        </div>

        <div class="admin-home-layout-slot-list-v2">
            @foreach($layoutSlots as $number => $slotData)
                @php
                    $slot = $slotData ?? [];
                    $slotType = $slot['slot_type'] ?? HomeLayoutSlot::TYPE_NONE;
                    $productSource = $slot['product_source'] ?? HomeLayoutSlot::SOURCE_CATEGORY;
                    $sectionOptions = $this->sectionOptionsForSlot($slotType);
                @endphp

                <div class="admin-home-layout-slot-v2">
                    <div class="admin-home-layout-slot-v2__number">
                        <strong>List {{ $number }}</strong>
                        <span>{{ $this->typeOptions[$slotType] ?? 'Kosong' }}</span>
                    </div>

                    <div class="admin-home-layout-slot-v2__fields">
                        <label>
                            Isi Slot
                            <select wire:model.live="layoutSlots.{{ $number }}.slot_type">
                                @foreach($this->typeOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            Status
                            <select wire:model="layoutSlots.{{ $number }}.is_active">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </label>

                        @if($slotType === HomeLayoutSlot::TYPE_PRODUCT_DISPLAY)
                            <label>
                                Sumber Produk
                                <select wire:model.live="layoutSlots.{{ $number }}.product_source">
                                    @foreach($this->productSourceOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            @if($productSource === HomeLayoutSlot::SOURCE_CATEGORY)
                                <label>
                                    Kategori Produk
                                    <select wire:model="layoutSlots.{{ $number }}.category_id">
                                        <option value="">Pilih kategori</option>

                                        @foreach($this->categories as $category)
                                            <option value="{{ $category->id }}">
                                                {{ $category->parent ? $category->parent->name . ' / ' : '' }}{{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif

                            <label>
                                Judul Custom
                                <input
                                    type="text"
                                    wire:model="layoutSlots.{{ $number }}.title"
                                    placeholder="Kosongkan untuk judul otomatis"
                                >
                            </label>

                            <label>
                                Subtitle
                                <input
                                    type="text"
                                    wire:model="layoutSlots.{{ $number }}.subtitle"
                                    placeholder="Opsional"
                                >
                            </label>
                        @endif

                        @if(in_array($slotType, [
                            HomeLayoutSlot::TYPE_FULL_BANNER,
                            HomeLayoutSlot::TYPE_SPLIT_BANNER,
                            HomeLayoutSlot::TYPE_GALLERY,
                        ], true))
                            <label>
                                Pilih Data
                                <select wire:model="layoutSlots.{{ $number }}.home_section_id">
                                    <option value="">Pilih data</option>

                                    @foreach($sectionOptions as $section)
                                        <option value="{{ $section->id }}">
                                            #{{ $section->id }} — {{ $section->title ?: 'Tanpa judul' }} {{ $section->is_active ? '' : '(Nonaktif)' }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="admin-actions">
            <button type="submit" class="admin-btn">
                Simpan Layout
            </button>
        </div>
    </form>
</div>