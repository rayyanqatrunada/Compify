<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer testimonial')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('role')
                            ->maxLength(120),
                        TextInput::make('company')
                            ->maxLength(120),
                        TextInput::make('avatar')
                            ->label('Avatar URL')
                            ->url(),
                        Textarea::make('quote')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('rating')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->default(5),
                        Toggle::make('is_featured')
                            ->default(true),
                    ]),
            ]);
    }
}
