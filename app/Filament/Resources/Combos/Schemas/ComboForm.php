<?php

namespace App\Filament\Resources\Combos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ComboForm
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
                        TextInput::make('name.en')->label('Name'),
                        Textarea::make('description.en')->label('Description')->columnSpanFull(),
                    ]),
                    Tabs\Tab::make('Russian (RU)')->schema([
                        TextInput::make('name.ru')->label('Name'),
                        Textarea::make('description.ru')->label('Description')->columnSpanFull(),
                    ]),
                ])->columnSpanFull(),

                Select::make('menu_item_ids')
                    ->label('Included Menu Items')
                    ->helperText('Select the specific menu items that make up this combo package')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->optionsLimit(500)
                    ->options(function () {
                        return \App\Models\MenuItem::all()
                            ->mapWithKeys(function ($item) {
                                $ka = $item->getTranslation('name', 'ka');
                                $en = $item->getTranslation('name', 'en');
                                $label = $en && $en !== $ka ? "{$ka} ({$en})" : $ka;
                                return [(string) $item->id => $label];
                            })
                            ->toArray();
                    })
                    ->columnSpanFull(),
                
                TextInput::make('min_guests')
                    ->label('Guests Count')
                    ->helperText('Informational number of guests (e.g. 20, 50)')
                    ->required()
                    ->numeric()
                    ->default(10),
                TextInput::make('price_per_guest')
                    ->label('Package Price')
                    ->prefix('₾')
                    ->required()
                    ->numeric(),
                FileUpload::make('image_url')
                    ->label('Image')
                    ->disk(env('BLOB_READ_WRITE_TOKEN') ? 'vercel_blob' : 'static_images')
                    ->directory('combos')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '16:9',
                        '4:3',
                        '1:1',
                    ])
                    ->saveUploadedFileUsing(function ($component, $file) {
                        return \App\Services\Image\ImageOptimizer::optimizeAndStore($component, $file, maxDimension: 1000, quality: 80);
                    }),
                TextInput::make('badge')
                    ->label('Badge')
                    ->placeholder('e.g. Bestseller, Premium'),
                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_furshet')
                    ->label('Buffet menu')
                    ->default(false),
                Toggle::make('is_active')
                    ->label('Is active')
                    ->default(true)
                    ->required(),
            ]);
    }
}