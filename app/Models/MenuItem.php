<?php

namespace App\Models;

use App\Services\SortOrderStore;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class MenuItem extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $guarded = [];

    protected $appends = ['sort_order', 'min_qty'];

    protected $casts = [
        'is_furshet' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public ?int $pendingSortOrder = null;

    public ?int $pendingMinQty = null;

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (array_key_exists('sort_order', $model->attributes)) {
                $model->pendingSortOrder = (int) $model->attributes['sort_order'];
                unset($model->attributes['sort_order']);
            }

            if (array_key_exists('min_qty', $model->attributes)) {
                $model->pendingMinQty = max(1, (int) $model->attributes['min_qty']);
                unset($model->attributes['min_qty']);
            }

            if ($model->pendingSortOrder !== null && $model->exists && $model->getKey()) {
                SortOrderStore::set('menu_item', $model->getKey(), $model->pendingSortOrder);
            }

            if ($model->pendingMinQty !== null && $model->exists && $model->getKey()) {
                SortOrderStore::setMinQty($model->getKey(), $model->pendingMinQty);
            }
        });

        static::saved(function (self $model) {
            if ($model->pendingSortOrder !== null && $model->getKey()) {
                SortOrderStore::set('menu_item', $model->getKey(), $model->pendingSortOrder);
            }

            if ($model->pendingMinQty !== null && $model->getKey()) {
                SortOrderStore::setMinQty($model->getKey(), $model->pendingMinQty);
            }
        });
    }

    public function getSortOrderAttribute(): int
    {
        if ($this->pendingSortOrder !== null) {
            return $this->pendingSortOrder;
        }

        return SortOrderStore::get('menu_item', $this->getKey());
    }

    public function setSortOrderAttribute($value): void
    {
        $this->pendingSortOrder = (int) ($value ?? 0);
    }

    public function getMinQtyAttribute(): int
    {
        if ($this->pendingMinQty !== null) {
            return max(1, $this->pendingMinQty);
        }

        return SortOrderStore::getMinQty($this->getKey());
    }

    public function setMinQtyAttribute($value): void
    {
        $this->pendingMinQty = max(1, (int) ($value ?? 1));
    }
}
