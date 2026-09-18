<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sender Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required(),
                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel(),
                        TextInput::make('subject')
                            ->label('Subject'),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'new' => 'New',
                                'read' => 'Read',
                                'replied' => 'Replied',
                                'archived' => 'Archived',
                            ])
                            ->required()
                            ->default('new'),
                        TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),
                    ]),

                Section::make('Message')
                    ->schema([
                        Textarea::make('message')
                            ->label('Message Content')
                            ->rows(8)
                            ->columnSpanFull()
                            ->required(),
                    ]),
            ]);
    }
}
