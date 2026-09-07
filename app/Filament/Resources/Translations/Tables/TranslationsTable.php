<?php

namespace App\Filament\Resources\Translations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TranslationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('text_ka')
                    ->label('Georgian (KA)')
                    ->getStateUsing(fn ($record) => $record->text['ka'] ?? '')
                    ->limit(40)
                    ->searchable(query: fn ($query, string $search) => $query->where('text->ka', 'like', "%{$search}%")),
                TextColumn::make('text_en')
                    ->label('English (EN)')
                    ->getStateUsing(fn ($record) => $record->text['en'] ?? '')
                    ->limit(40)
                    ->searchable(query: fn ($query, string $search) => $query->where('text->en', 'like', "%{$search}%")),
                TextColumn::make('text_ru')
                    ->label('Russian (RU)')
                    ->getStateUsing(fn ($record) => $record->text['ru'] ?? '')
                    ->limit(40)
                    ->searchable(query: fn ($query, string $search) => $query->where('text->ru', 'like', "%{$search}%")),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label('Filter by Group')
                    ->options([
                        'Navigation & General' => 'Navigation & General',
                        'Home Page' => 'Home Page',
                        'About Page' => 'About Page',
                        'Contact Page' => 'Contact Page',
                        'Menu & Services' => 'Menu & Services',
                        'Booking Page' => 'Booking Page',
                    ]),
            ])
            ->defaultSort('group', 'asc')
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