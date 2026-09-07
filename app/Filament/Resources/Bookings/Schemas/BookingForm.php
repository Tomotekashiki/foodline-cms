<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer & Event Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Full Name')
                            ->required(),
                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->required(),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email(),
                        DatePicker::make('event_date')
                            ->label('Delivery Date')
                            ->required(),
                        TextInput::make('address')
                            ->label('Delivery Address')
                            ->columnSpanFull()
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                        TextInput::make('total_estimate')
                            ->label('Total Estimate (₾)')
                            ->prefix('₾')
                            ->numeric(),
                        TextInput::make('order_type')
                            ->label('Order Type')
                            ->disabled(),
                        Textarea::make('notes')
                            ->label('Special Notes / Requests')
                            ->columnSpanFull(),
                    ]),

                Section::make('Ordered Products & Cart Details')
                    ->schema([
                        ViewField::make('order_details')
                            ->label('')
                            ->view('filament.forms.components.booking-order-details')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}