<?php

namespace App\Filament\Resources\DeliveryPartners\Schemas;

use App\Enums\UserRole;
use App\Rules\IndianMobile;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class DeliveryPartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Hidden::make('role')->default(UserRole::Delivery->value),

            TextInput::make('name')->label('Name')->required()->maxLength(255),

            TextInput::make('phone')
                ->label('Mobile number')
                ->required()
                ->maxLength(15)
                ->rule(new IndianMobile)
                ->unique(ignoreRecord: true)
                ->helperText('This is how they sign in to the delivery panel.'),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->minLength(8)
                ->maxLength(72)
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->required(fn (string $operation): bool => $operation === 'create')
                ->helperText('Leave empty to keep the current password.'),

            Toggle::make('is_active')
                ->label('Can sign in')
                ->default(true)
                ->helperText('Switch off when someone leaves. Their past deliveries stay.'),
        ]);
    }
}
