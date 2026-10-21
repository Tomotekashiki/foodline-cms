<?php

namespace App\Models;

use App\Services\SortOrderStore;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];

    protected $appends = ['min_order_amount'];

    protected $casts = [
        'min_booking_days_ahead' => 'integer',
        'disabled_dates' => 'array',
    ];

    public ?float $pendingMinOrderAmount = null;

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (array_key_exists('min_order_amount', $model->attributes)) {
                $model->pendingMinOrderAmount = max(0, (float) $model->attributes['min_order_amount']);
                unset($model->attributes['min_order_amount']);
            }

            if ($model->pendingMinOrderAmount !== null) {
                SortOrderStore::setMinOrderAmount($model->pendingMinOrderAmount);
            }
        });

        static::saved(function (self $model) {
            if ($model->pendingMinOrderAmount !== null) {
                SortOrderStore::setMinOrderAmount($model->pendingMinOrderAmount);
            }
        });
    }

    public function getMinOrderAmountAttribute(): float
    {
        if ($this->pendingMinOrderAmount !== null) {
            return max(0, $this->pendingMinOrderAmount);
        }

        return SortOrderStore::getMinOrderAmount();
    }

    public function setMinOrderAmountAttribute($value): void
    {
        $this->pendingMinOrderAmount = max(0, (float) ($value ?? 0));
    }

    public static function getMinBookingDaysAhead(): int
    {
        return (int) (static::first()?->min_booking_days_ahead ?? 1);
    }

    public static function getDisabledDates(): array
    {
        return static::first()?->disabled_dates ?? [];
    }

    public static function getMinOrderAmount(): float
    {
        return SortOrderStore::getMinOrderAmount();
    }
}
