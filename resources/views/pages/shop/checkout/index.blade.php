<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingSetting;
use App\Services\CartService;
use App\Services\MidtransPaymentService;
use App\Services\FonnteMessageService;
use App\Services\OrderInventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Services\UniversalDiscountService;
use App\Support\MidtransPaymentChannel;

new
#[Layout('components.layouts.shop')]
#[Title('Pembayaran - Compify')]
class extends Component {
    public string $email = '';
    public bool $newsletter = false;
    public bool $save_to_profile = false;
    public ?string $autofill_source = null;

    public string $country = 'Indonesia';
    public string $first_name = '';
    public string $last_name = '';
    public string $company = '';
    public string $address = '';
    public string $district = '';
    public string $city = '';
    public string $province = 'Jawa Tengah';
    public string $postal_code = '';
    public string $phone = '';

    public ?int $shipping_method_id = null;
    public ?int $payment_method_id = null;

    public string $billing_type = 'same';

    public function mount(): void
    {
        $this->fillCheckoutFromCustomerData();

        if (empty(session('cart', []))) {
            $this->redirectRoute('cart.index', navigate: true);
        }
    }

    private function splitCustomerName(?string $name): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $firstName = array_shift($parts) ?: '';

        return [$firstName, trim(implode(' ', $parts))];
    }

    private function fillCheckoutFields(array $data, bool $overwrite = true): bool
    {
        $used = false;

        foreach ($data as $field => $value) {
            if (! property_exists($this, $field)) {
                continue;
            }

            $value = trim((string) ($value ?? ''));

            if ($value === '') {
                continue;
            }

            if (! $overwrite && trim((string) $this->{$field}) !== '') {
                continue;
            }

            $this->{$field} = $value;
            $used = true;
        }

        return $used;
    }

    private function profileNeedsCheckoutData($user): bool
    {
        if (! $user) {
            return false;
        }

        foreach (['phone', 'address', 'district', 'city', 'province', 'postal_code'] as $field) {
            if (blank($user->{$field} ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function fillCheckoutFromCustomerData(): void
    {
        $user = auth('customer')->user();
        $sources = [];

        $this->country = 'Indonesia';
        $this->province = 'Jawa Tengah';
        $this->save_to_profile = $this->profileNeedsCheckoutData($user);

        $lastOrder = $user
            ? Order::query()
                ->where('user_id', $user->id)
                ->where(function ($query) {
                    $query->whereNotNull('shipping_address')
                        ->orWhereNotNull('customer_phone')
                        ->orWhereNotNull('shipping_city');
                })
                ->latest()
                ->first()
            : null;

        if ($lastOrder) {
            [$firstName, $lastName] = $this->splitCustomerName($lastOrder->customer_name);

            if ($this->fillCheckoutFields([
                'email' => $lastOrder->customer_email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $lastOrder->customer_phone,
                'address' => $lastOrder->shipping_address,
                'district' => $lastOrder->shipping_district,
                'city' => $lastOrder->shipping_city,
                'province' => $lastOrder->shipping_province,
                'postal_code' => $lastOrder->shipping_postal_code,
            ])) {
                $sources[] = 'riwayat checkout terakhir';
            }
        }

        if ($user) {
            [$firstName, $lastName] = $this->splitCustomerName($user->name);

            if ($this->fillCheckoutFields([
                'email' => $user->email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $user->phone,
                'address' => $user->address,
                'district' => $user->district,
                'city' => $user->city,
                'province' => $user->province,
                'postal_code' => $user->postal_code,
            ])) {
                $sources[] = 'profil customer';
            }
        }

        $sources = array_values(array_unique($sources));
        $this->autofill_source = $sources ? implode(' dan ', $sources) : null;
    }

    #[Computed]
    public function cartItems()
    {
        return app(CartService::class)->items();
    }

    #[Computed]
    public function subtotal(): int
    {
        return app(CartService::class)->subtotal();
    }

    #[Computed]
    public function cartWeightGram(): int
    {
        return app(CartService::class)->totalWeightGram();
    }

    public function formatWeightGram(int|float|null $value): string
    {
        return app(CartService::class)->formatWeightGram($value);
    }

    #[Computed]
    public function shippingMethods()
    {
        return ShippingMethod::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->where('type', '!=', 'whatsapp')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function groupedPaymentMethods()
    {
        return $this->paymentMethods
            ->groupBy(fn ($method) => MidtransPaymentChannel::groupForPaymentMethod($method))
            ->sortBy(fn ($methods, $group) => MidtransPaymentChannel::groupSort((string) $group));
    }

    public function paymentGroupDescription(string $group): string
    {
        return MidtransPaymentChannel::groupDescriptionForPaymentMethodGroup($group);
    }

    #[Computed]
    public function selectedShippingMethod()
    {
        return $this->shipping_method_id
            ? ShippingMethod::find($this->shipping_method_id)
            : null;
    }

    #[Computed]
    public function selectedPaymentMethod()
    {
        return PaymentMethod::active()
            ->where('type', '!=', 'whatsapp')
            ->find($this->payment_method_id);
    }

    #[Computed]
    public function isAddressReady(): bool
    {
        return filled($this->province)
            && filled($this->city)
            && filled($this->district)
            && filled($this->address);
    }

    #[Computed]
    public function shippingCost(): ?int
    {
        if (! $this->selectedShippingMethod || ! $this->isAddressReady) {
            return null;
        }

        return $this->calculateShippingCost($this->selectedShippingMethod, $this->subtotal);
    }

    #[Computed]
    public function universalDiscount(): array
    {
        return app(UniversalDiscountService::class)
            ->calculateForCart($this->cartItems, auth('customer')->id());
    }

    #[Computed]
    public function universalDiscountAmount(): int
    {
        return (int) ($this->universalDiscount['amount'] ?? 0);
    }

    #[Computed]
    public function total(): int
    {
        return max(0, $this->subtotal + ($this->shippingCost ?? 0) - $this->universalDiscountAmount);
    }

    private function calculateShippingCost(ShippingMethod $method, int $subtotal): int
    {
        if ($method->free_shipping_min && $subtotal >= $method->free_shipping_min) {
            return 0;
        }

        $setting = ShippingSetting::first();

        $customerProvince = strtolower(trim($this->province));
        $customerCity = strtolower(trim($this->city));
        $customerDistrict = strtolower(trim($this->district));

        $baseProvince = strtolower(trim($setting?->province ?? 'Jawa Tengah'));
        $baseCity = strtolower(trim($setting?->city ?? 'Jepara'));
        $baseDistrict = strtolower(trim($setting?->district ?? 'Bangsri'));

        if (
            $customerProvince === $baseProvince &&
            $customerCity === $baseCity &&
            $customerDistrict === $baseDistrict
        ) {
            return $method->same_district_cost ?? $method->base_cost;
        }

        if (
            $customerProvince === $baseProvince &&
            $customerCity === $baseCity
        ) {
            return $method->same_city_cost ?? $method->base_cost;
        }

        if ($customerProvince === $baseProvince) {
            return $method->same_province_cost ?? $method->base_cost;
        }

        return $method->outside_province_cost ?? $method->base_cost;
    }

    private function cartDiscountTotal(): int
    {
        return $this->cartItems->sum(function (array $item) {
            return (int) ($item['discount_amount'] ?? 0) * (int) ($item['quantity'] ?? 1);
        });
    }

    private function isMidtransMethod($paymentMethod): bool
    {
        return $paymentMethod
            && $paymentMethod->type === 'api'
            && strtolower((string) $paymentMethod->api_provider) === 'midtrans';
    }

    private function paymentTypeForMethod($paymentMethod): string
    {
        if ($this->isMidtransMethod($paymentMethod)) {
            return 'midtrans_snap';
        }

        return $paymentMethod?->type ?? 'manual';
    }

    public function paymentMethodSubtitle($paymentMethod): string
    {
        if ($this->isMidtransMethod($paymentMethod)) {
            return $paymentMethod->description
                ?: MidtransPaymentChannel::description($paymentMethod->midtrans_channel_code);
        }

        return $paymentMethod?->description
            ?: $paymentMethod?->instructions
            ?: 'Pilih metode pembayaran.';
    }

    public function paymentMethodBadge($paymentMethod): string
    {
        if ($this->isMidtransMethod($paymentMethod)) {
            return 'via Midtrans';
        }

        return strtoupper((string) ($paymentMethod?->type ?: 'manual'));
    }

    private function paymentGatewayForMethod($paymentMethod): ?string
    {
        return $this->isMidtransMethod($paymentMethod) ? 'midtrans' : null;
    }

    private function paymentChannelForMethod($paymentMethod): ?string
    {
        if ($this->isMidtransMethod($paymentMethod)) {
            return $paymentMethod->midtrans_channel_code;
        }

        return null;
    }

    private function paymentChannelLabelForMethod($paymentMethod): ?string
    {
        if ($this->isMidtransMethod($paymentMethod)) {
            return $paymentMethod->midtrans_channel_label;
        }

        return $paymentMethod?->name;
    }

    private function createOrderItemSnapshot(Order $order, array $item): void
    {
        if ($item['type'] === 'product') {
            $itemType = ($item['is_event_price'] ?? false) ? 'event_flash_sale' : 'product';

            OrderItem::create([
                'order_id' => $order->id,
                'item_type' => $itemType,
                'product_id' => $item['product_id'],
                'combo_package_id' => null,
                'event_flash_sale_item_id' => $item['event_flash_sale_item_id'] ?? null,

                'product_name' => $item['name'],
                'product_slug' => $item['slug'] ?? null,
                'product_image' => $item['image'] ?? null,

                'price' => $item['unit_price'],
                'original_price' => $item['original_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'price_label' => $item['price_label'] ?? null,

                'quantity' => $item['quantity'],
                'weight_gram' => $item['weight_gram'] ?? 0,
                'line_weight_gram' => $item['line_weight_gram'] ?? 0,
                'total' => $item['line_total'],

                'snapshot_data' => [
                    'type' => $itemType,
                    'source' => $item['price_source'] ?? null,
                    'product_id' => $item['product_id'],
                    'slug' => $item['slug'] ?? null,
                    'brand_or_category' => $item['brand_or_category'] ?? null,
                    'is_event_price' => (bool) ($item['is_event_price'] ?? false),
                    'event_flash_sale_item_id' => $item['event_flash_sale_item_id'] ?? null,

                    'has_event_stock_limit' => $item['has_event_stock_limit'] ?? false,
                    'event_stock_limit' => $item['event_stock_limit'] ?? null,
                    'event_stock_reserved_before_order' => $item['event_stock_reserved'] ?? 0,
                    'event_stock_remaining_before_order' => $item['event_stock_remaining'] ?? null,

                    'unit_price' => $item['unit_price'],
                    'original_price' => $item['original_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'discount_percent' => $item['discount_percent'] ?? null,
                    'weight_gram' => $item['weight_gram'] ?? 0,
                    'line_weight_gram' => $item['line_weight_gram'] ?? 0,
                ],
            ]);

            app(OrderInventoryService::class)->reserveProductStock(
                productId: (int) $item['product_id'],
                quantity: (int) $item['quantity'],
                name: (string) $item['name'],
            );

            return;
        }

        if ($item['type'] === 'combo_package') {
            $children = collect($item['children'])->map(function ($child) use ($item) {
                return [
                    'product_id' => $child['product_id'],
                    'name' => $child['name'],
                    'image' => $child['image'] ?? null,
                    'brand_or_category' => $child['brand_or_category'] ?? null,
                    'quantity_per_package' => (int) $child['quantity'],
                    'package_quantity' => (int) $item['quantity'],
                    'total_quantity' => (int) $child['quantity'] * (int) $item['quantity'],
                    'unit_price' => (int) $child['unit_price'],
                    'line_total_per_package' => (int) $child['line_total'],
                    'line_total_all_package' => (int) $child['line_total'] * (int) $item['quantity'],
                    'weight_gram' => (int) ($child['weight_gram'] ?? 0),
                    'line_weight_gram_per_package' => (int) ($child['line_weight_gram'] ?? 0),
                    'line_weight_gram_all_package' => (int) ($child['line_weight_gram'] ?? 0) * (int) $item['quantity'],
                ];
            })->values()->all();

            OrderItem::create([
                'order_id' => $order->id,
                'item_type' => 'combo_package',
                'product_id' => null,
                'combo_package_id' => $item['combo_package_id'],
                'event_flash_sale_item_id' => null,

                'product_name' => $item['name'],
                'product_slug' => $item['slug'] ?? null,
                'product_image' => $item['image'] ?? null,

                'price' => $item['unit_price'],
                'original_price' => $item['original_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'price_label' => $item['price_label'] ?? 'Paket Bundling',

                'quantity' => $item['quantity'],
                'weight_gram' => $item['weight_gram'] ?? 0,
                'line_weight_gram' => $item['line_weight_gram'] ?? 0,
                'total' => $item['line_total'],

                'snapshot_data' => [
                    'type' => 'combo_package',
                    'combo_package_id' => $item['combo_package_id'],
                    'slug' => $item['slug'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'original_price' => $item['original_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'discount_percent' => $item['discount_percent'] ?? null,
                    'weight_gram' => $item['weight_gram'] ?? 0,
                    'line_weight_gram' => $item['line_weight_gram'] ?? 0,
                    'children' => $children,
                ],
            ]);

            foreach ($item['children'] as $child) {
                if (! $child['product_id']) {
                    continue;
                }

                app(OrderInventoryService::class)->reserveProductStock(
                    productId: (int) $child['product_id'],
                    quantity: (int) $child['quantity'] * (int) $item['quantity'],
                    name: (string) ($child['name'] ?? 'Produk paket'),
                );
            }
        }
    }

    private function subscribeToNewsletter(Order $order): void
    {
        if (! $this->newsletter || ! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => strtolower(trim($this->email))],
            [
                'customer_id' => $order->user_id,
                'source' => 'checkout',
                'status' => 'subscribed',
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'subscribed_at' => now(),
            ]
        );

        if (! $subscriber->wasRecentlyCreated) {
            $subscriber->update([
                'customer_id' => $subscriber->customer_id ?: $order->user_id,
                'status' => 'subscribed',
                'source' => $subscriber->source ?: 'checkout',
                'unsubscribed_at' => null,
                'subscribed_at' => $subscriber->subscribed_at ?: now(),
            ]);
        }
    }

    private function saveCheckoutDataToProfile(): void
    {
        if (! $this->save_to_profile) {
            return;
        }

        $user = auth('customer')->user();

        if (! $user) {
            return;
        }

        $email = strtolower(trim($this->email));
        $fullName = trim($this->first_name . ' ' . $this->last_name);

        $data = [
            'name' => $fullName ?: $user->name,
            'phone' => trim($this->phone) ?: null,
            'address' => trim($this->address) ?: null,
            'district' => trim($this->district) ?: null,
            'city' => trim($this->city) ?: null,
            'province' => trim($this->province) ?: null,
            'postal_code' => trim($this->postal_code) ?: null,
        ];

        if (
            filter_var($email, FILTER_VALIDATE_EMAIL)
            && $email !== strtolower((string) $user->email)
            && ! \App\Models\User::where('email', $email)->whereKeyNot($user->id)->exists()
        ) {
            $data['email'] = $email;
        }

        $user->update($data);
    }

    private function notifyOrderCreated(Order $order): void
    {
        try {
            app(FonnteMessageService::class)
                ->sendOrderCreatedNotifications(
                    $order->fresh()->load([
                        'items',
                        'paymentMethod',
                        'shippingMethod',
                    ])
                );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function placeOrder()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'country' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'district' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
            'shipping_method_id' => ['required', 'exists:shipping_methods,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'billing_type' => ['required', 'in:same'],
            'save_to_profile' => ['boolean'],
        ]);

        if ($this->cartItems->isEmpty()) {
            $this->redirectRoute('cart.index', navigate: true);
            return;
        }

        $invalidItem = $this->cartItems->first(fn (array $item) => ! $item['is_available']);

        if ($invalidItem) {
            $this->addError('cart', $invalidItem['message'] ?? 'Ada item keranjang yang tidak tersedia.');
            return;
        }

        $paymentMethod = $this->selectedPaymentMethod;

        if (! $paymentMethod) {
            $this->addError('payment_method_id', 'Metode pembayaran tidak valid.');
            return;
        }

        $universalDiscount = app(UniversalDiscountService::class)
            ->calculateForCart($this->cartItems, auth('customer')->id());

        $universalDiscountAmount = (int) ($universalDiscount['amount'] ?? 0);
        $shippingCost = $this->shippingCost ?? 0;
        $totalAmount = max(0, $this->subtotal + $shippingCost - $universalDiscountAmount);

        $order = DB::transaction(function () use (
            $paymentMethod,
            $universalDiscount,
            $universalDiscountAmount,
            $shippingCost,
            $totalAmount
        ) {
            $order = Order::create([
                'user_id' => auth('customer')->id(),
                'shipping_method_id' => $this->shipping_method_id,
                'payment_method_id' => $this->payment_method_id,

                'order_number' => Order::generateOrderNumber(),
                'customer_name' => trim($this->first_name . ' ' . $this->last_name),
                'customer_email' => $this->email,
                'customer_phone' => $this->phone,

                'shipping_address' => $this->address,
                'shipping_province' => $this->province,
                'shipping_city' => $this->city,
                'shipping_district' => $this->district,
                'shipping_postal_code' => $this->postal_code ?: null,

                'subtotal' => $this->subtotal,
                'shipping_cost' => $shippingCost,
                'total_weight_gram' => $this->cartWeightGram,
                'discount_amount' => $this->cartDiscountTotal(),

                'universal_discount_eligible_subtotal' => $universalDiscount['eligible_subtotal'] ?? 0,
                'universal_discount_amount' => $universalDiscountAmount,
                'universal_discount_percent' => $universalDiscount['percent'] ?? 0,
                'universal_discount_label' => $universalDiscount['label'] ?? null,
                'universal_discount_campaign_key' => $universalDiscount['campaign_key'] ?? null,
                'universal_discount_snapshot' => $universalDiscount,

                'total_amount' => $totalAmount,

                'payment_type' => $this->paymentTypeForMethod($paymentMethod),
                'payment_gateway' => $this->paymentGatewayForMethod($paymentMethod),
                'payment_channel' => $this->paymentChannelForMethod($paymentMethod),
                'payment_channel_label' => $this->paymentChannelLabelForMethod($paymentMethod),
                'payment_reference' => null,
                'payment_redirect_url' => null,

                'order_status' => 'pending',
                'payment_status' => 'pending',
            ]);

            foreach ($this->cartItems as $item) {
                if (! $item['is_available']) {
                    continue;
                }

                $this->createOrderItemSnapshot($order, $item);
            }

            app(OrderInventoryService::class)->markReserved($order);

            return $order;
        });

        $order->load([
            'items',
            'paymentMethod',
            'shippingMethod',
        ]);

        $this->subscribeToNewsletter($order);
        $this->saveCheckoutDataToProfile();

        $paymentMethod = $order->paymentMethod;

        if ($this->isMidtransMethod($paymentMethod)) {
            try {
                $snapTransaction = app(MidtransPaymentService::class)
                    ->createSnapTransaction($order);

                $redirectUrl = $snapTransaction->redirect_url ?? null;
                $snapToken = $snapTransaction->token ?? null;

                if (! $redirectUrl) {
                    throw new \RuntimeException('Midtrans tidak mengembalikan redirect URL.');
                }

                $order->update([
                    'payment_type' => 'midtrans_snap',
                    'payment_gateway' => 'midtrans',
                    'payment_channel' => $this->paymentChannelForMethod($paymentMethod),
                    'payment_channel_label' => $this->paymentChannelLabelForMethod($paymentMethod),
                    'payment_reference' => $snapToken ?: $order->order_number,
                    'payment_redirect_url' => $redirectUrl,
                ]);

                $this->notifyOrderCreated($order);

                app(CartService::class)->clear();

                return $this->redirect($redirectUrl, navigate: false);
            } catch (\Throwable $e) {
                report($e);

                $order->update([
                    'payment_type' => 'midtrans_snap',
                    'payment_gateway' => 'midtrans',
                    'payment_channel' => $this->paymentChannelForMethod($paymentMethod),
                    'payment_channel_label' => $this->paymentChannelLabelForMethod($paymentMethod),
                    'payment_reference' => $order->order_number,
                    'payment_redirect_url' => route('checkout.payment', $order),
                ]);

                $this->notifyOrderCreated($order);

                app(CartService::class)->clear();

                $message = 'Order berhasil dibuat, tapi koneksi ke Midtrans gagal. Silakan cek konfigurasi Midtrans atau lanjutkan proses manual dari admin.';

                if (config('app.debug')) {
                    $message .= ' Detail error: ' . $e->getMessage();
                }

                return redirect()
                    ->route('checkout.payment', $order)
                    ->with('error', $message);
            }
        }

        $order->update([
            'payment_redirect_url' => route('checkout.payment', $order),
        ]);

        $this->notifyOrderCreated($order);

        app(CartService::class)->clear();

        return redirect()->route('checkout.payment', $order);
    }
};
?>

<div class="checkout-page">
    <div class="checkout-shell">
        <section class="checkout-left">
            <div class="checkout-account-row">
                @php
                    $customer = auth('customer')->user();

                    $customerAvatar = $customer?->avatar
                        ? Storage::url($customer->avatar)
                        : asset('assets/user/default-avatar.svg');
                @endphp

                <div class="checkout-user">
                    <span class="checkout-user-avatar">
                        <img src="{{ $customerAvatar }}" alt="{{ $customer?->name ?? 'Customer' }}">
                    </span>

                    <div>
                        <strong>{{ $email ?: $customer?->email ?: 'Email@example.com' }}</strong>
                    </div>
                </div>

                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button type="submit" class="checkout-logout-btn">Keluar</button>
                </form>
            </div>

            <label class="checkout-check">
                <input type="checkbox" wire:model="newsletter">
                <span>Kirimi saya email berita dan penawaran</span>
            </label>

            <form wire:submit="placeOrder" class="checkout-form">
                <div class="checkout-field full floating">
                    <span>Negara/Wilayah</span>
                    <input type="text" wire:model.live="country">
                </div>

                <div class="checkout-grid-2">
                    <label class="checkout-field">
                        <input type="text" wire:model.live="first_name" placeholder="Nama depan">
                    </label>

                    <label class="checkout-field">
                        <input type="text" wire:model.live="last_name" placeholder="Nama belakang">
                    </label>
                </div>

                <label class="checkout-field full">
                    <input type="text" wire:model.live="company" placeholder="Perusahaan (opsional)">
                </label>

                <label class="checkout-field full">
                    <input type="text" wire:model.live="address" placeholder="Alamat">
                </label>

                <label class="checkout-field full">
                    <input type="text" wire:model.live.debounce.400ms="district" placeholder="Kecamatan">
                </label>

                <label class="checkout-field full">
                    <input type="text" wire:model.live.debounce.400ms="city" placeholder="Kota">
                </label>

                <div class="checkout-grid-2">
                    <label class="checkout-field">
                        <input type="text" wire:model.live.debounce.400ms="province" placeholder="Provinsi">
                    </label>

                    <label class="checkout-field">
                        <input type="text" wire:model.live="postal_code" placeholder="Kode Pos">
                    </label>
                </div>

                <label class="checkout-field full">
                    <input type="text" wire:model.live="phone" placeholder="Telepon">
                </label>

                <label class="checkout-check checkout-save-profile-check">
                    <input type="checkbox" wire:model="save_to_profile">
                    <span>Simpan kontak dan alamat ini ke profil saya untuk checkout berikutnya</span>
                </label>

                @error('cart')
                    <small class="checkout-error">{{ $message }}</small>
                @enderror

                <div class="checkout-section">
                    <h2>Metode Pengiriman</h2>

                    <div class="checkout-weight-box">
                        <div>
                            <strong>Total berat paket</strong>
                        </div>
                        <span>{{ $this->formatWeightGram($this->cartWeightGram) }}</span>
                    </div>

                    @if(! $this->isAddressReady)
                        <div class="checkout-muted-box">
                            Masukkan alamat pengiriman Anda untuk melihat metode pengiriman yang tersedia.
                        </div>
                    @else
                        <div class="checkout-method-list">
                            @forelse($this->shippingMethods as $method)
                                @php
                                    $cost = $this->calculateShippingCost($method, $this->subtotal);
                                @endphp

                                <label @class([
                                    'checkout-method-card',
                                    'active' => (int) $shipping_method_id === $method->id,
                                ])>
                                    <input type="radio" wire:model.live="shipping_method_id" value="{{ $method->id }}">

                                    <div>
                                        <strong>{{ $method->name }}</strong>
                                        <small>{{ $method->estimate ?: 'Estimasi menyesuaikan wilayah' }}</small>
                                        @if($method->description)
                                            <p>{{ $method->description }}</p>
                                        @endif
                                    </div>

                                    <b>
                                        @if($cost <= 0)
                                            Gratis
                                        @else
                                            Rp {{ number_format($cost, 0, ',', '.') }}
                                        @endif
                                    </b>
                                </label>
                            @empty
                                <div class="checkout-muted-box">
                                    Belum ada metode pengiriman aktif.
                                </div>
                            @endforelse
                        </div>
                    @endif

                    @error('shipping_method_id')
                        <small class="checkout-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="checkout-section checkout-payment-section">
                    <h2>Pembayaran</h2>
                    <p>Pilih kategori pembayaran yang paling nyaman. Semua channel otomatis tetap diproses melalui Midtrans.</p>

                    @if($this->groupedPaymentMethods->isNotEmpty())
                        <div class="checkout-payment-groups">
                            @foreach($this->groupedPaymentMethods as $group => $methods)
                                @php
                                    $isGroupActive = $methods->contains(fn ($method) => (int) $payment_method_id === $method->id);
                                    $shouldOpen = $isGroupActive || ($loop->first && blank($payment_method_id));
                                @endphp

                                <details class="checkout-payment-group checkout-payment-group--dropdown" @if($shouldOpen) open @endif>
                                    <summary class="checkout-payment-group-head">
                                        <div>
                                            <strong>{{ $group }}</strong>
                                            <small>
                                                @if($isGroupActive)
                                                    Dipilih: {{ optional($methods->first(fn ($method) => (int) $payment_method_id === $method->id))->name }}
                                                @else
                                                    {{ $this->paymentGroupDescription((string) $group) }}
                                                @endif
                                            </small>
                                        </div>

                                        <span>
                                            {{ $methods->count() }} opsi
                                            <b aria-hidden="true"></b>
                                        </span>
                                    </summary>

                                    <div class="checkout-method-list checkout-method-list--compact">
                                        @foreach($methods as $method)
                                            @php
                                                $logo = data_get($method, 'logo');
                                            @endphp

                                            <label @class([
                                                'checkout-method-card',
                                                'checkout-method-card--compact',
                                                'active' => (int) $payment_method_id === $method->id,
                                            ])>
                                                <input type="radio" wire:model.live="payment_method_id" value="{{ $method->id }}">

                                                <div>
                                                    <strong>{{ $method->name }}</strong>
                                                    <small>{{ $this->paymentMethodSubtitle($method) }}</small>
                                                </div>

                                                <span class="checkout-payment-pill">{{ $this->paymentMethodBadge($method) }}</span>

                                                @if($logo)
                                                    <img src="{{ Storage::url($logo) }}" alt="{{ $method->name }}">
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @else
                        <div class="checkout-muted-box">
                            Belum ada metode pembayaran aktif.
                        </div>
                    @endif

                    @error('payment_method_id')
                        <small class="checkout-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="checkout-section">
                    <h2>Alamat Penagihan</h2>

                    <div class="checkout-billing-box">
                        <label @class(['active' => $billing_type === 'same'])>
                            <input type="radio" wire:model="billing_type" value="same">
                            <span>Sama dengan alamat pengiriman</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="checkout-pay-button">
                    Bayar Sekarang
                </button>
            </form>
        </section>

        <aside class="checkout-right">
            <div class="checkout-summary">
                @foreach($this->cartItems as $item)
                    @php
                        $image = ($item['image'] ?? null) ? Storage::url($item['image']) : null;
                    @endphp

                    <div class="checkout-summary-product">
                        <div class="checkout-product-image">
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $item['name'] }}">
                            @else
                                <span>{{ substr($item['name'], 0, 2) }}</span>
                            @endif

                            <b>{{ $item['quantity'] }}</b>
                        </div>

                        <div>
                            <strong>{{ $item['name'] }}</strong>
                            <small>{{ $item['brand_or_category'] ?? ($item['type'] === 'combo_package' ? 'Paket Bundling' : 'Produk') }}</small>

                            @if($item['type'] === 'combo_package' && $item['children']->isNotEmpty())
                                <div class="checkout-combo-mini">
                                    @foreach($item['children']->take(3) as $child)
                                        <span>{{ $child['quantity'] }}x {{ $child['name'] }}</span>
                                    @endforeach

                                    @if($item['children']->count() > 3)
                                        <span>+{{ $item['children']->count() - 3 }} produk lain</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <p>Rp {{ number_format($item['line_total'], 0, ',', '.') }}</p>
                    </div>
                @endforeach

                @if(($this->universalDiscount['reason'] ?? null) === 'minimum_not_reached')
                    <div class="checkout-muted-box">
                        Diskon otomatis akan muncul jika minimum belanja terpenuhi.
                    </div>
                @endif

                <div class="checkout-price-list">
                    <div>
                        <span>Subtotal</span>
                        <strong>Rp {{ number_format($this->subtotal, 0, ',', '.') }}</strong>
                    </div>

                    <div class="checkout-price-list-muted">
                        <span>Berat paket</span>
                        <strong>{{ $this->formatWeightGram($this->cartWeightGram) }}</strong>
                    </div>

                    @if($this->universalDiscountAmount > 0)
                        <div>
                            <span>{{ $this->universalDiscount['label'] ?? 'Diskon Belanja' }}</span>
                            <strong>- Rp {{ number_format($this->universalDiscountAmount, 0, ',', '.') }}</strong>
                        </div>
                    @endif

                    <div>
                        <span>Pengiriman</span>

                        @if($this->shippingCost === null)
                            <strong class="muted">Masukkan alamat pengiriman</strong>
                        @elseif($this->shippingCost <= 0)
                            <strong>Gratis</strong>
                        @else
                            <strong>Rp {{ number_format($this->shippingCost, 0, ',', '.') }}</strong>
                        @endif
                    </div>
                </div>

                <div class="checkout-total-row">
                    <span>Total</span>
                    <strong>Rp {{ number_format($this->total, 0, ',', '.') }}</strong>
                </div>
            </div>
        </aside>
    </div>
</div>