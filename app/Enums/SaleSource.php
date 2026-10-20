<?php

namespace App\Enums;

enum SaleSource: string
{
    case Manual = 'manual';
    case Import = 'import';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual Entry',
            self::Import => 'CSV Import',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
