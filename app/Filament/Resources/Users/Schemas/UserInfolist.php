<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('avatar')->circular(),
                        TextEntry::make('name')->weight('bold'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('role')->badge(),
                        TextEntry::make('phone'),
                        TextEntry::make('created_at')->dateTime('d M Y H:i'),
                    ]),
            ]);
    }
}
