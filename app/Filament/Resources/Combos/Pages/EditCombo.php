<?php

namespace App\Filament\Resources\Combos\Pages;

use App\Filament\Resources\Combos\ComboResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCombo extends EditRecord
{
    protected static string $resource = ComboResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['sort_order'] = $this->getRecord()->sort_order;

        if (empty($data['description']['ka']) && ! empty($data['description']['ru']) && preg_match('/[\x{10A0}-\x{10FF}]/u', (string) $data['description']['ru'])) {
            $data['description']['ka'] = $data['description']['ru'];
        }

        return $data;
    }
}
