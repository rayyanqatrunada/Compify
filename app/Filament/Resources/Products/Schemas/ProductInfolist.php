<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Produk')
                    ->columns(3)
                    ->schema([
                        ImageEntry::make('thumbnail')
                            ->label('Thumbnail')
                            ->columnSpan(1),
                        TextEntry::make('name')
                            ->columnSpan(2)
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('category.name')
                            ->badge(),
                        TextEntry::make('price')
                            ->money('IDR', locale: 'id', decimalPlaces: 0),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                    ]),
                Section::make('Spesifikasi')
                    ->schema([
                        KeyValueEntry::make('specs'),
                    ]),
            ]);
    }
}
