<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kategori')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->weight('bold'),
                        TextEntry::make('slug'),
                        ColorEntry::make('accent_color'),
                        IconEntry::make('is_active')->boolean(),
                        TextEntry::make('description')->columnSpanFull(),
                    ]),
            ]);
    }
}
