<?php

namespace App\Enums;

enum CalendarSource: string
{
    case Payment = 'payment';
    case Debt = 'debt';
    case Note = 'note';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Payment => 'Payment',
            self::Debt    => 'Debt',
            self::Note    => 'Note',
            self::Manual  => 'Manual',
        };
    }
}