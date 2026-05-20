<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Banner promo')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(160),
                        TextInput::make('badge')
                            ->maxLength(60),
                        TextInput::make('subtitle')
                            ->columnSpanFull()
                            ->maxLength(220),
                        TextInput::make('image')
                            ->label('Image URL')
                            ->url()
                            ->columnSpanFull(),
                        TextInput::make('cta_label')
                            ->label('CTA label')
                            ->required()
                            ->default('Belanja sekarang'),
                        TextInput::make('cta_url')
                            ->label('CTA URL')
                            ->required()
                            ->default('/products'),
                        DateTimePicker::make('starts_at')
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->seconds(false),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }
}
