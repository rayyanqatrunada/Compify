<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\MidtransPaymentChannel;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'logo',
        'qr_image',
        'payment_url',
        'description',

        'whatsapp_number',
        'whatsapp_template',
        'auto_redirect',

        'api_provider',
        'midtrans_enabled_payments',
        'api_endpoint',
        'instructions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_redirect' => 'boolean',
        'midtrans_enabled_payments' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getIsWhatsappAttribute(): bool
    {
        return $this->type === 'whatsapp';
    }

    public function getCleanWhatsappNumberAttribute(): ?string
    {
        if (! $this->whatsapp_number) {
            return null;
        }

        $number = preg_replace('/[^0-9]/', '', $this->whatsapp_number);

        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }

        return $number ?: null;
    }

    public function getIsMidtransAttribute(): bool
    {
        return $this->type === 'api'
            && strtolower((string) $this->api_provider) === 'midtrans';
    }

    public function getMidtransChannelCodeAttribute(): ?string
    {
        return MidtransPaymentChannel::selectedCode($this->midtrans_enabled_payments);
    }

    public function getMidtransChannelLabelAttribute(): string
    {
        return MidtransPaymentChannel::label($this->midtrans_channel_code);
    }

    public function getPaymentDescriptionAttribute(): string
    {
        if ($this->is_midtrans) {
            return $this->description
                ?: MidtransPaymentChannel::description($this->midtrans_channel_code);
        }

        return $this->description ?: $this->instructions ?: 'Pilih metode pembayaran.';
    }

}
