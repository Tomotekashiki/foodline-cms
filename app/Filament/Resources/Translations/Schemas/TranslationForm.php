<?php

namespace App\Filament\Resources\Translations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class TranslationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('group')
                    ->label('Group / Section')
                    ->options([
                        'Navigation & General' => 'Navigation & General',
                        'Home Page' => 'Home Page',
                        'About Page' => 'About Page',
                        'Contact Page' => 'Contact Page',
                        'Menu & Services' => 'Menu & Services',
                        'Booking Page' => 'Booking Page',
                    ])
                    ->default('Navigation & General')
                    ->required(),

                TextInput::make('key')
                    ->label('Translation Key')
                    ->required()
                    ->unique(ignoreRecord: true),

                Tabs::make('Translations')->tabs([
                    Tabs\Tab::make('Georgian (KA)')->schema([
                        Textarea::make('text.ka')->label('Text (KA)')->required()->rows(3),
                    ]),
                    Tabs\Tab::make('English (EN)')->schema([
                        Textarea::make('text.en')->label('Text (EN)')->required()->rows(3),
                    ]),
                    Tabs\Tab::make('Russian (RU)')->schema([
                        Textarea::make('text.ru')->label('Text (RU)')->required()->rows(3),
                    ]),
                ])->columnSpanFull(),
            ]);
    }
}