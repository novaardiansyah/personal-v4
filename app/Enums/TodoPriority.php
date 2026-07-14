<?php

namespace App\Enums;

enum TodoPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Low',
            self::Medium => 'Medium',
            self::High   => 'High',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low    => 'success',
            self::Medium => 'warning',
            self::High   => 'danger',
        };
    }
}