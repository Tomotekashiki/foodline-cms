<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Translations')->tabs([
                    Tabs\Tab::make('Georgian (KA)')->schema([
                        TextInput::make('name.ka')->label('Category Name')->required(),
                    ]),
                    Tabs\Tab::make('English (EN)')->schema([
                        TextInput::make('name.en')->label('Category Name')->required(),
                    ]),
                    Tabs\Tab::make('Russian (RU)')->schema([
                        TextInput::make('name.ru')->label('Category Name')->required(),
                    ]),
                ])->columnSpanFull(),

                TextInput::make('slug')
                    ->label('Slug / Identifier')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->label('Is Active')
                    ->default(true)
                    ->required(),
            ]);
    }
}