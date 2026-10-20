<?php

namespace App\Models;

use App\Services\SortOrderStore;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Combo extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description', 'inclusions'];

    protected $guarded = [];

    protected $appends = ['sort_order'];

    protected $casts = [
        'inclusions' => 'array',
        'menu_item_ids' => 'array',
        'is_furshet' => 'boolean',
    ];

    public ?int $pendingSortOrder = null;

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (array_key_exists('sort_order', $model->attributes)) {
                $model->pendingSortOrder = (int) $model->attributes['sort_order'];
                unset($model->attributes['sort_order']);
            }

            if ($model->pendingSortOrder !== null && $model->exists && $model->getKey()) {
                SortOrderStore::set('combo', $model->getKey(), $model->pendingSortOrder);
            }
        });

        static::saved(function (self $model) {
            if ($model->pendingSortOrder !== null && $model->getKey()) {
                SortOrderStore::set('combo', $model->getKey(), $model->pendingSortOrder);
            }
        });
    }

    public function getSortOrderAttribute(): int
    {
        if ($this->pendingSortOrder !== null) {
            return $this->pendingSortOrder;
        }

        return SortOrderStore::get('combo', $this->getKey());
    }

    public function setSortOrderAttribute($value): void
    {
        $this->pendingSortOrder = (int) ($value ?? 0);
    }

    public function menuItems()
    {
        $ids = $this->menu_item_ids ?? [];
        if (empty($ids)) {
            return collect();
        }

        return MenuItem::whereIn('id', $ids)->get();
    }
}
