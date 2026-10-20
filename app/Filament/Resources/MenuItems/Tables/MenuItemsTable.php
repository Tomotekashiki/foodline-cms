<?php

namespace App\Filament\Resources\MenuItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MenuItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                ImageColumn::make('image_url')
                    ->label('Image')
                    ->disk(env('BLOB_READ_WRITE_TOKEN') ? 'vercel_blob' : 'static_images')
                    ->checkFileExistence(false),
                TextColumn::make('category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->sortable(query: fn ($query, string $direction) => \App\Services\SortOrderStore::applyOrder($query, 'menu_item', $direction)),
                TextColumn::make('badge')
                    ->searchable(),
                IconColumn::make('is_furshet')
                    ->label('Buffet menu')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn ($query) => \App\Services\SortOrderStore::applyOrder($query, 'menu_item'))
            ->filters([
                SelectFilter::make('category')
                    ->label('Category')
                    ->options(function () {
                        return \App\Models\Category::orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(function ($cat) {
                                $ka = $cat->getTranslation('name', 'ka');
                                $en = $cat->getTranslation('name', 'en');
                                $label = $en && $en !== $ka ? "{$ka} ({$en})" : $ka;
                                $value = $cat->getTranslation('name', 'en') ?: $cat->slug;
                                return [$value => $label];
                            })
                            ->toArray();
                    }),
                TernaryFilter::make('is_furshet')
                    ->label('Buffet menu'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
