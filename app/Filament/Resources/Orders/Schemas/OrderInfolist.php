<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order detail')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('order_number')->copyable()->weight('bold'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('ordered_at')->dateTime('d M Y H:i'),
                        TextEntry::make('customer_name'),
                        TextEntry::make('customer_email')->copyable(),
                        TextEntry::make('customer_phone'),
                        TextEntry::make('shipping_address')->columnSpanFull(),
                    ]),
                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('product_name'),
                                TextEntry::make('quantity'),
                                TextEntry::make('price')->money('IDR', locale: 'id', decimalPlaces: 0),
                                TextEntry::make('total')->money('IDR', locale: 'id', decimalPlaces: 0),
                            ])
                            ->columns(4),
                    ]),
                Section::make('Totals')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('subtotal')->money('IDR', locale: 'id', decimalPlaces: 0),
                        TextEntry::make('shipping_cost')->money('IDR', locale: 'id', decimalPlaces: 0),
                        TextEntry::make('discount')->money('IDR', locale: 'id', decimalPlaces: 0),
                        TextEntry::make('total')->money('IDR', locale: 'id', decimalPlaces: 0)->weight('bold'),
                    ]),
            ]);
    }
}
