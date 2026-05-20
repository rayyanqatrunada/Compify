<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BannerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Banner')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('image')->columnSpanFull(),
                        TextEntry::make('title')->weight('bold'),
                        TextEntry::make('badge')->badge(),
                        TextEntry::make('subtitle')->columnSpanFull(),
                        TextEntry::make('cta_url')->label('CTA URL'),
                        IconEntry::make('is_active')->boolean(),
                    ]),
            ]);
    }
}
