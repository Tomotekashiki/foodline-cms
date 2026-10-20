<?php

namespace App\Filament\Resources\Combos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CombosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Image')
                    ->disk(env('BLOB_READ_WRITE_TOKEN') ? 'vercel_blob' : 'static_images')
                    ->checkFileExistence(false)
                    ->circular(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('min_guests')
                    ->label('Guests Count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price_per_guest')
                    ->label('Package Price')
                    ->prefix('₾ ')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('badge')
                    ->label('Badge')
                    ->badge()
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_furshet')
                    ->label('Buffet menu')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->filters([
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