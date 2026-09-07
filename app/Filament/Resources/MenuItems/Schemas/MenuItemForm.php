<?php

namespace App\Filament\Resources\MenuItems\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                                Tabs::make('Translations')->tabs([
                    Tabs\Tab::make('Georgian (KA)')->schema([
                        TextInput::make('name.ka')->label('Name')->required(),
                        Textarea::make('description.ka')->label('Description')->columnSpanFull(),
                    ]),
                    Tabs\Tab::make('English (EN)')->schema([
                        TextInput::make('name.en')->label('Name')->required(),
                        Textarea::make('description.en')->label('Description')->columnSpanFull(),
                    ]),
                    Tabs\Tab::make('Russian (RU)')->schema([
                        TextInput::make('name.ru')->label('Name')->required(),
                        Textarea::make('description.ru')->label('Description')->columnSpanFull(),
                    ]),
                ])->columnSpanFull(),
                
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                FileUpload::make('image_url')
                    ->disk(env('BLOB_READ_WRITE_TOKEN') ? 'vercel_blob' : 'static_images')
                    ->directory('menu-items')
                    ->image(),
                Select::make('category')
                    ->label('Category')
                    ->options(function () {
                        return \App\Models\Category::where('is_active', true)
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(function ($cat) {
                                $ka = $cat->getTranslation('name', 'ka');
                                $en = $cat->getTranslation('name', 'en');
                                $label = $en && $en !== $ka ? "{$ka} ({$en})" : $ka;
                                $value = $cat->getTranslation('name', 'en') ?: $cat->slug;
                                return [$value => $label];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('badge'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
