<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('min_booking_days_ahead')
                    ->label('Minimum Booking Days Ahead (Lead Time)')
                    ->helperText('Number of days in advance required for bookings (e.g. 0 = same day allowed, 1 = starting tomorrow, 2 = 2 days in advance, etc.).')
                    ->numeric()
                    ->minValue(0)
                    ->default(1)
                    ->required(),

                ViewField::make('disabled_dates')
                    ->label('Blackout / Disabled Calendar Dates')
                    ->helperText('Click on any date on the calendar below to toggle it on/off. Red dates are disabled/blacked out.')
                    ->view('filament.forms.components.calendar-blackout')
                    ->columnSpanFull(),
            ]);
    }
}
