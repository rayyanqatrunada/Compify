<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestimonialInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Testimonial')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('avatar')->circular(),
                        TextEntry::make('name')->weight('bold'),
                        TextEntry::make('role'),
                        TextEntry::make('company'),
                        TextEntry::make('rating')->badge(),
                        IconEntry::make('is_featured')->boolean(),
                        TextEntry::make('quote')->columnSpanFull(),
                    ]),
            ]);
    }
}
