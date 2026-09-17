<?php

namespace App\Enums;

enum PrintDocument: string
{
    case Label = 'label';
    case Invoice = 'invoice';

    public function label(): string
    {
        return match ($this) {
            self::Label => 'Parcel label',
            self::Invoice => 'Invoice',
        };
    }
}
