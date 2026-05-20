<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi produk')
                    ->columns(2)
                    ->schema([
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama produk')
                            ->required()
                            ->maxLength(160)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(180),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(60),
                        TextInput::make('short_description')
                            ->label('Deskripsi singkat')
                            ->columnSpanFull()
                            ->maxLength(220),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(5)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Harga dan stok')
                    ->columns(3)
                    ->schema([
                        TextInput::make('price')
                            ->label('Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        TextInput::make('compare_price')
                            ->label('Harga coret')
                            ->numeric()
                            ->prefix('Rp'),
                        TextInput::make('stock')
                            ->label('Stok')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        TextInput::make('sold_count')
                            ->label('Terjual')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('rating')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.1)
                            ->default(4.8),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'archived' => 'Archived',
                            ])
                            ->default('active')
                            ->required(),
                        Toggle::make('is_featured')
                            ->label('Featured product'),
                    ]),
                Section::make('Media dan spesifikasi')
                    ->columns(2)
                    ->schema([
                        TextInput::make('thumbnail')
                            ->label('Thumbnail URL')
                            ->url()
                            ->required()
                            ->columnSpanFull(),
                        TagsInput::make('gallery')
                            ->label('Gallery image URLs')
                            ->placeholder('https://...')
                            ->columnSpanFull(),
                        KeyValue::make('specs')
                            ->label('Spesifikasi')
                            ->keyLabel('Nama')
                            ->valueLabel('Nilai')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
