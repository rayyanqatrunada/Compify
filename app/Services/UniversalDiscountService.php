<?php

namespace App\Services;

use App\Models\EventSetting;
use App\Models\Order;
use App\Models\UniversalDiscountUsage;
use Illuminate\Support\Collection;

class UniversalDiscountService
{
    public function calculateForCart(iterable $cartItems, ?int $userId): array
    {
        $setting = EventSetting::current();

        if (! $setting || ! $setting->is_universal_discount_active) {
            return $this->emptyResult('inactive');
        }

        $campaignKey = $setting->universal_discount_campaign_key_value;

        if (! $userId) {
            return $this->emptyResult('login_required', $campaignKey);
        }

        if ($this->hasUserUsedCampaign($userId, $campaignKey)) {
            return $this->emptyResult('already_used', $campaignKey);
        }

        $items = collect($cartItems);
        $eligibleSubtotal = $this->eligibleSubtotal($items, $setting->universal_discount_scope);

        if ($eligibleSubtotal < 1) {
            return $this->emptyResult('no_eligible_items', $campaignKey);
        }

        $tier = $setting->activeUniversalDiscountTiers()
            ->where('min_purchase', '<=', $eligibleSubtotal)
            ->orderByDesc('min_purchase')
            ->orderByDesc('discount_percent')
            ->first();

        if (! $tier) {
            return [
                ...$this->emptyResult('minimum_not_reached', $campaignKey),
                'eligible_subtotal' => $eligibleSubtotal,
            ];
        }

        $percent = min(100, max(0, (float) $tier->discount_percent));
        $amount = (int) round($eligibleSubtotal * ($percent / 100));
        $amount = min($amount, $eligibleSubtotal);

        return [
            'applicable' => $amount > 0,
            'reason' => null,

            'campaign_key' => $campaignKey,
            'scope' => $setting->universal_discount_scope,

            'eligible_subtotal' => $eligibleSubtotal,
            'min_purchase' => (int) round((float) $tier->min_purchase),

            'percent' => $percent,
            'amount' => $amount,

            'label' => 'Diskon Belanja ' . rtrim(rtrim(number_format($percent, 2, ',', '.'), '0'), ',') . '%',

            'tier_id' => $tier->id,
        ];
    }

    public function recordUsage(Order $order, array $discount): void
    {
        $amount = (int) ($discount['amount'] ?? 0);
        $campaignKey = $discount['campaign_key'] ?? null;

        if ($amount < 1 || ! $campaignKey || ! $order->user_id) {
            return;
        }

        UniversalDiscountUsage::firstOrCreate(
            [
                'user_id' => $order->user_id,
                'campaign_key' => $campaignKey,
            ],
            [
                'order_id' => $order->id,
                'eligible_subtotal' => $discount['eligible_subtotal'] ?? 0,
                'discount_percent' => $discount['percent'] ?? 0,
                'discount_amount' => $amount,
                'used_at' => now(),
            ]
        );
    }

    public function hasUserUsedCampaign(int $userId, string $campaignKey): bool
    {
        return UniversalDiscountUsage::query()
            ->where('user_id', $userId)
            ->where('campaign_key', $campaignKey)
            ->exists();
    }

    public function eligibleSubtotal(Collection $items, string $scope): int
    {
        return (int) $items->sum(function (array $item) use ($scope) {
            $type = $item['type'] ?? 'product';
            $isEventPrice = (bool) ($item['is_event_price'] ?? false);
            $discountAmount = (int) ($item['discount_amount'] ?? 0);
            $lineTotal = (int) ($item['line_total'] ?? 0);

            return match ($scope) {
                'regular_only' => (
                    $type === 'product'
                    && ! $isEventPrice
                    && $discountAmount <= 0
                ) ? $lineTotal : 0,

                'all_items' => $lineTotal,

                default => (
                    $type === 'product'
                    && ! $isEventPrice
                ) ? $lineTotal : 0,
            };
        });
    }

    private function emptyResult(string $reason, ?string $campaignKey = null): array
    {
        return [
            'applicable' => false,
            'reason' => $reason,

            'campaign_key' => $campaignKey,
            'scope' => null,

            'eligible_subtotal' => 0,
            'min_purchase' => 0,

            'percent' => 0,
            'amount' => 0,

            'label' => null,

            'tier_id' => null,
        ];
    }
}