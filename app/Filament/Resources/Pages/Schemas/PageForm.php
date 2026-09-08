<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                Tabs::make('Translations')->tabs([
                    Tabs\Tab::make('Georgian (KA)')->schema([
                        TextInput::make('title.ka')->label('Title')->required(),
                        TextInput::make('seo_title.ka')->label('SEO Title'),
                        Textarea::make('seo_description.ka')->label('SEO Description')->columnSpanFull(),
                    ]),
                    Tabs\Tab::make('English (EN)')->schema([
                        TextInput::make('title.en')->label('Title')->required(),
                        TextInput::make('seo_title.en')->label('SEO Title'),
                        Textarea::make('seo_description.en')->label('SEO Description')->columnSpanFull(),
                    ]),
                    Tabs\Tab::make('Russian (RU)')->schema([
                        TextInput::make('title.ru')->label('Title')->required(),
                        TextInput::make('seo_title.ru')->label('SEO Title'),
                        Textarea::make('seo_description.ru')->label('SEO Description')->columnSpanFull(),
                    ]),
                ]),
                TextInput::make('slug')
                    ->required(),
                
                CheckboxList::make('configs')
                    ->label('Configurations / Placements')
                    ->options([
                        'header-menu' => 'Header Menu (header-menu)',
                        'footer-menu' => 'Footer Menu (footer-menu)',
                    ])
                    ->columns(2),
                
                FileUpload::make('seo_image')
                    ->disk(env('BLOB_READ_WRITE_TOKEN') ? 'vercel_blob' : 'static_images')
                    ->directory('pages')
                    ->image()
                    ->saveUploadedFileUsing(function ($component, $file) {
                        return \App\Services\Image\ImageOptimizer::optimizeAndStore($component, $file);
                    }),
            ]);
    }
}
