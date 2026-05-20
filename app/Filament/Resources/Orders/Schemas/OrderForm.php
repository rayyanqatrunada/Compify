<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')
                    ->columns(3)
                    ->schema([
                        TextInput::make('order_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(60),
                        Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
                        TextInput::make('customer_name')->required(),
                        TextInput::make('customer_email')->email()->required(),
                        TextInput::make('customer_phone')->tel(),
                        Textarea::make('shipping_address')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('payment_method')
                            ->default('Bank Transfer'),
                        DateTimePicker::make('ordered_at')
                            ->seconds(false),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->columns(4)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('product_name')
                                    ->required(),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),
                                TextInput::make('price')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                                TextInput::make('total')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                            ]),
                    ]),
                Section::make('Totals')
                    ->columns(4)
                    ->schema([
                        TextInput::make('subtotal')->numeric()->prefix('Rp')->required(),
                        TextInput::make('shipping_cost')->numeric()->prefix('Rp')->default(0),
                        TextInput::make('discount')->numeric()->prefix('Rp')->default(0),
                        TextInput::make('total')->numeric()->prefix('Rp')->required(),
                    ]),
            ]);
    }
}
