<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Services\SortOrderStore;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListMenuItems extends ListRecords
{
    protected static string $resource = MenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function applySortingToTableQuery(Builder $query): Builder
    {
        if ($this->isTableReordering() || ! $this->getTableSortColumn()) {
            return SortOrderStore::applyOrder($query, 'menu_item', $this->getTable()->getReorderDirection());
        }

        return parent::applySortingToTableQuery($query);
    }

    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        if (! $this->getTable()->isReorderable()) {
            return;
        }

        $this->getTable()->callBeforeReordering($order);

        SortOrderStore::reorder('menu_item', $order);

        $this->getTable()->callAfterReordering($order);
    }
}
