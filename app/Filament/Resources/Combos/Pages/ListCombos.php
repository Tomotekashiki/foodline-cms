<?php

namespace App\Filament\Resources\Combos\Pages;

use App\Filament\Resources\Combos\ComboResource;
use App\Services\SortOrderStore;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCombos extends ListRecords
{
    protected static string $resource = ComboResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function applySortingToTableQuery(Builder $query): Builder
    {
        if ($this->isTableReordering() || ! $this->getTableSortColumn()) {
            return SortOrderStore::applyOrder($query, 'combo', $this->getTable()->getReorderDirection());
        }

        return parent::applySortingToTableQuery($query);
    }

    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        if (! $this->getTable()->isReorderable()) {
            return;
        }

        $this->getTable()->callBeforeReordering($order);

        SortOrderStore::reorder('combo', $order);

        $this->getTable()->callAfterReordering($order);
    }
}
