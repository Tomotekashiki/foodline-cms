<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'min_booking_days_ahead' => 'integer',
        'disabled_dates' => 'array',
    ];

    public static function getMinBookingDaysAhead(): int
    {
        return (int) (static::first()?->min_booking_days_ahead ?? 1);
    }

    public static function getDisabledDates(): array
    {
        return static::first()?->disabled_dates ?? [];
    }
}
