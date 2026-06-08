<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class MidtransPaymentChannel
{
    public const CHANNELS = [
        'other_qris' => [
            'label' => 'QRIS',
            'group' => 'E-Wallet & QRIS',
            'description' => 'Scan QRIS melalui aplikasi bank atau e-wallet.',
        ],
        'bca_va' => [
            'label' => 'BCA Virtual Account',
            'group' => 'Virtual Account / Transfer',
            'description' => 'Bayar melalui ATM, m-Banking, atau internet banking BCA.',
        ],
        'bni_va' => [
            'label' => 'BNI Virtual Account',
            'group' => 'Virtual Account / Transfer',
            'description' => 'Bayar melalui ATM, m-Banking, atau internet banking BNI.',
        ],
        'bri_va' => [
            'label' => 'BRI Virtual Account',
            'group' => 'Virtual Account / Transfer',
            'description' => 'Bayar melalui ATM, BRImo, atau internet banking BRI.',
        ],
        'permata_va' => [
            'label' => 'Permata Virtual Account',
            'group' => 'Virtual Account / Transfer',
            'description' => 'Bayar melalui jaringan Permata Virtual Account.',
        ],
        'echannel' => [
            'label' => 'Mandiri Bill Payment',
            'group' => 'Virtual Account / Transfer',
            'description' => 'Bayar melalui Mandiri Bill Payment.',
        ],
        'gopay' => [
            'label' => 'GoPay',
            'group' => 'E-Wallet & QRIS',
            'description' => 'Bayar menggunakan GoPay melalui Midtrans.',
        ],
        'shopeepay' => [
            'label' => 'ShopeePay',
            'group' => 'E-Wallet & QRIS',
            'description' => 'Bayar menggunakan ShopeePay melalui Midtrans.',
        ],
        'dana' => [
            'label' => 'DANA',
            'group' => 'E-Wallet & QRIS',
            'description' => 'Bayar menggunakan DANA melalui Midtrans.',
        ],
        'credit_card' => [
            'label' => 'Kartu Kredit / Debit',
            'group' => 'Kartu Kredit / Debit',
            'description' => 'Bayar menggunakan kartu Visa, Mastercard, JCB, atau kartu lain yang aktif di Midtrans.',
        ],
        'indomaret' => [
            'label' => 'Indomaret',
            'group' => 'Retail / Minimarket',
            'description' => 'Bayar melalui gerai Indomaret.',
        ],
        'alfamart' => [
            'label' => 'Alfamart',
            'group' => 'Retail / Minimarket',
            'description' => 'Bayar melalui gerai Alfamart.',
        ],
    ];

    public static function options(): array
    {
        return self::CHANNELS;
    }

    public static function normalize(?string $code): ?string
    {
        $code = strtolower(trim((string) $code));

        if ($code === '') {
            return null;
        }

        return match ($code) {
            'qris', 'qr', 'gopay_qris' => 'other_qris',
            'bca', 'bank_transfer_bca' => 'bca_va',
            'bni', 'bank_transfer_bni' => 'bni_va',
            'bri', 'bank_transfer_bri' => 'bri_va',
            'permata' => 'permata_va',
            'mandiri', 'mandiri_bill' => 'echannel',
            'card', 'cards', 'credit', 'creditcard', 'credit_card', 'debit_card', 'visa', 'mastercard', 'jcb' => 'credit_card',
            default => $code,
        };
    }

    public static function label(?string $code): string
    {
        $code = self::normalize($code);

        return self::CHANNELS[$code]['label'] ?? ($code ? strtoupper(str_replace('_', ' ', $code)) : 'Midtrans');
    }

    public static function description(?string $code): string
    {
        $code = self::normalize($code);

        return self::CHANNELS[$code]['description'] ?? 'Diproses otomatis melalui Midtrans.';
    }

    public static function group(?string $code): string
    {
        $code = self::normalize($code);

        return self::CHANNELS[$code]['group'] ?? 'Midtrans';
    }

    public static function groupDescription(string $group): string
    {
        return match ($group) {
            'E-Wallet & QRIS' => 'Bayar cepat dengan QRIS, GoPay, ShopeePay, atau DANA.',
            'Virtual Account / Transfer' => 'Transfer otomatis lewat virtual account bank.',
            'Kartu Kredit / Debit' => 'Bayar menggunakan kartu Visa, Mastercard, JCB, atau kartu lain yang aktif.',
            'Retail / Minimarket' => 'Bayar melalui kasir minimarket yang tersedia.',
            default => 'Metode pembayaran otomatis melalui Midtrans.',
        };
    }

    public static function groupSort(string $group): int
    {
        return match ($group) {
            'E-Wallet & QRIS' => 10,
            'Virtual Account / Transfer' => 20,
            'Kartu Kredit / Debit' => 30,
            'Retail / Minimarket' => 40,
            default => 90,
        };
    }

    public static function groupForPaymentMethod(mixed $paymentMethod): string
    {
        if (
            $paymentMethod
            && ($paymentMethod->type ?? null) === 'api'
            && strtolower((string) ($paymentMethod->api_provider ?? '')) === 'midtrans'
        ) {
            return self::group($paymentMethod->midtrans_channel_code ?? null);
        }

        return match ($paymentMethod->type ?? 'manual') {
            'qr' => 'E-Wallet & QRIS',
            'manual' => 'Manual / Transfer',
            'url' => 'Payment Link',
            'api' => 'Payment Gateway',
            default => 'Lainnya',
        };
    }

    public static function groupDescriptionForPaymentMethodGroup(string $group): string
    {
        return match ($group) {
            'Manual / Transfer' => 'Transfer manual dan verifikasi oleh admin.',
            'Payment Link' => 'Pembayaran melalui link pihak ketiga.',
            'Payment Gateway' => 'Pembayaran otomatis dari gateway.',
            'Lainnya' => 'Metode pembayaran lainnya.',
            default => self::groupDescription($group),
        };
    }

    public static function enabledPayments(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                $value = array_filter(array_map('trim', explode(',', $value)));
            }
        }

        if (! is_array($value)) {
            return [];
        }

        $channels = [];

        foreach ($value as $item) {
            $channel = self::normalize(is_array($item) ? ($item['code'] ?? null) : (string) $item);

            if ($channel) {
                $channels[] = $channel;
            }
        }

        return array_values(array_unique($channels));
    }

    public static function selectedCode(mixed $value): ?string
    {
        return self::enabledPayments($value)[0] ?? null;
    }

    public static function actualCodeFromPayload(array $payload): ?string
    {
        $paymentType = self::normalize((string) Arr::get($payload, 'payment_type'));

        if ($paymentType === 'bank_transfer') {
            $bank = strtolower((string) Arr::get($payload, 'va_numbers.0.bank'));

            return match ($bank) {
                'bca' => 'bca_va',
                'bni' => 'bni_va',
                'bri' => 'bri_va',
                'permata' => 'permata_va',
                default => 'bank_transfer',
            };
        }

        if ($paymentType === 'echannel') {
            return 'echannel';
        }

        if ($paymentType === 'cstore') {
            $store = strtolower((string) (Arr::get($payload, 'store') ?: Arr::get($payload, 'payment_code')));

            return in_array($store, ['indomaret', 'alfamart'], true)
                ? $store
                : 'cstore';
        }

        if (in_array($paymentType, ['gopay', 'shopeepay', 'dana', 'other_qris', 'credit_card'], true)) {
            return $paymentType;
        }

        if ($paymentType === 'qris') {
            return 'other_qris';
        }

        return $paymentType ?: null;
    }

    public static function vaNumberFromPayload(array $payload): ?string
    {
        return Arr::get($payload, 'va_numbers.0.va_number')
            ?: Arr::get($payload, 'permata_va_number')
            ?: Arr::get($payload, 'bill_key')
            ?: Arr::get($payload, 'payment_code');
    }

    public static function bankFromPayload(array $payload): ?string
    {
        return Arr::get($payload, 'va_numbers.0.bank')
            ?: Arr::get($payload, 'bank')
            ?: (Arr::get($payload, 'bill_key') ? 'mandiri' : null);
    }
}
