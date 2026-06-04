<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomeLayoutGroup extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(HomeLayoutSlot::class)
            ->orderBy('slot_number');
    }

    public static function current(): self
    {
        $group = static::query()
            ->where('is_active', true)
            ->first();

        if (! $group) {
            $group = static::query()->oldest()->first();

            if (! $group) {
                $group = static::create([
                    'name' => 'Default Layout',
                    'is_active' => true,
                ]);
            }

            static::query()
                ->whereKeyNot($group->id)
                ->update([
                    'is_active' => false,
                ]);

            $group->update([
                'is_active' => true,
            ]);
        }

        return $group->ensureDefaultSlots();
    }

    public function activate(): void
    {
        static::query()
            ->whereKeyNot($this->id)
            ->update([
                'is_active' => false,
            ]);

        $this->update([
            'is_active' => true,
        ]);
    }

    public function ensureDefaultSlots(int $minimumSlotCount = 7): self
    {
        $existingSlots = $this->slots()
            ->orderBy('slot_number')
            ->get();

        if ($existingSlots->isEmpty()) {
            if ($this->isDefaultLayoutName()) {
                $this->createDefaultSlots($minimumSlotCount);
            } else {
                $this->createBlankSlots($minimumSlotCount);
            }

            return $this->fresh(['slots']);
        }

        if ($this->isDefaultLayoutName() && $this->isBlankLayout($existingSlots)) {
            $this->slots()->delete();

            $this->createDefaultSlots($minimumSlotCount);

            return $this->fresh(['slots']);
        }

        $existingNumbers = $existingSlots
            ->pluck('slot_number')
            ->map(fn ($number) => (int) $number)
            ->all();

        for ($number = 1; $number <= $minimumSlotCount; $number++) {
            if (in_array($number, $existingNumbers, true)) {
                continue;
            }

            $payload = $this->isDefaultLayoutName()
                ? ($this->defaultSlotPayloads()[$number] ?? $this->blankSlotPayload())
                : $this->blankSlotPayload();

            $this->slots()->create(array_merge($payload, [
                'slot_number' => $number,
            ]));
        }

        return $this->fresh(['slots']);
    }

    public function ensureBlankSlots(int $minimumSlotCount = 7): self
    {
        for ($number = 1; $number <= $minimumSlotCount; $number++) {
            $this->slots()->firstOrCreate(
                ['slot_number' => $number],
                $this->blankSlotPayload()
            );
        }

        return $this->fresh(['slots']);
    }

    private function createBlankSlots(int $minimumSlotCount = 7): void
    {
        for ($number = 1; $number <= $minimumSlotCount; $number++) {
            $this->slots()->create(array_merge($this->blankSlotPayload(), [
                'slot_number' => $number,
            ]));
        }
    }

    private function blankSlotPayload(): array
    {
        return [
            'slot_type' => HomeLayoutSlot::TYPE_NONE,
            'product_source' => HomeLayoutSlot::SOURCE_CATEGORY,
            'category_id' => null,
            'home_section_id' => null,
            'title' => null,
            'subtitle' => null,
            'is_active' => true,
        ];
    }

    private function createDefaultSlots(int $minimumSlotCount = 7): void
    {
        $defaultSlots = $this->defaultSlotPayloads();

        for ($number = 1; $number <= $minimumSlotCount; $number++) {
            $payload = $defaultSlots[$number] ?? [
                'slot_type' => HomeLayoutSlot::TYPE_NONE,
                'product_source' => HomeLayoutSlot::SOURCE_CATEGORY,
                'category_id' => null,
                'home_section_id' => null,
                'title' => null,
                'subtitle' => null,
                'is_active' => true,
            ];

            $this->slots()->create(array_merge($payload, [
                'slot_number' => $number,
            ]));
        }
    }

    private function defaultSlotPayloads(): array
    {
        $motherboardCategoryId = $this->findCategoryId([
            'motherboard',
            'mainboard',
            'mobo',
        ]);

        $ramCategoryId = $this->findCategoryId([
            'ram',
            'memory',
            'memori',
        ]);

        $monitorCategoryId = $this->findCategoryId([
            'monitor',
            'display',
            'screen',
        ]);

        $fullBannerId = HomeSection::query()
            ->where('section_type', 'story')
            ->where('display_style', 'full_banner')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->value('id');

        return [
            1 => [
                'slot_type' => HomeLayoutSlot::TYPE_PRODUCT_DISPLAY,
                'product_source' => HomeLayoutSlot::SOURCE_BEST_SELLER,
                'category_id' => null,
                'home_section_id' => null,
                'title' => '',
                'subtitle' => '',
                'is_active' => true,
            ],

            2 => [
                'slot_type' => HomeLayoutSlot::TYPE_PRODUCT_DISPLAY,
                'product_source' => HomeLayoutSlot::SOURCE_LATEST,
                'category_id' => null,
                'home_section_id' => null,
                'title' => '',
                'subtitle' => '',
                'is_active' => true,
            ],

            3 => [
                'slot_type' => HomeLayoutSlot::TYPE_FULL_BANNER,
                'product_source' => HomeLayoutSlot::SOURCE_CATEGORY,
                'category_id' => null,
                'home_section_id' => $fullBannerId,
                'title' => null,
                'subtitle' => null,
                'is_active' => true,
            ],

            4 => [
                'slot_type' => HomeLayoutSlot::TYPE_PRODUCT_DISPLAY,
                'product_source' => HomeLayoutSlot::SOURCE_CATEGORY,
                'category_id' => $motherboardCategoryId,
                'home_section_id' => null,
                'title' => '',
                'subtitle' => '',
                'is_active' => true,
            ],

            5 => [
                'slot_type' => HomeLayoutSlot::TYPE_PRODUCT_DISPLAY,
                'product_source' => HomeLayoutSlot::SOURCE_CATEGORY,
                'category_id' => $motherboardCategoryId,
                'home_section_id' => null,
                'title' => null,
                'subtitle' => null,
                'is_active' => true,
            ],

            6 => [
                'slot_type' => HomeLayoutSlot::TYPE_PRODUCT_DISPLAY,
                'product_source' => HomeLayoutSlot::SOURCE_CATEGORY,
                'category_id' => $ramCategoryId,
                'home_section_id' => null,
                'title' => '',
                'subtitle' => '',
                'is_active' => true,
            ],

            7 => [
                'slot_type' => HomeLayoutSlot::TYPE_PRODUCT_DISPLAY,
                'product_source' => HomeLayoutSlot::SOURCE_CATEGORY,
                'category_id' => $monitorCategoryId,
                'home_section_id' => null,
                'title' => '',
                'subtitle' => '',
                'is_active' => true,
            ],
        ];
    }

    private function findCategoryId(array $keywords): ?int
    {
        return Category::query()
            ->where('is_active', true)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('slug', 'like', '%' . $keyword . '%')
                        ->orWhere('name', 'like', '%' . $keyword . '%');
                }
            })
            ->orderByRaw('parent_id is null desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');
    }

    private function isDefaultLayoutName(): bool
    {
        return trim(strtolower($this->name)) === 'default layout';
    }

    private function isBlankLayout($slots): bool
    {
        return $slots->every(function (HomeLayoutSlot $slot) {
            return $slot->slot_type === HomeLayoutSlot::TYPE_NONE;
        });
    }
}