<?php

namespace App\Enums;

enum FuelType: string
{
    case Unleaded = 'unleaded';
    case Premium = 'premium';
    case Diesel = 'diesel';
    case Kerosene = 'kerosene';
    case Lpg = 'lpg';
    case Electric = 'electric';

    public function label(): string
    {
        return match ($this) {
            self::Unleaded => 'Green',
            self::Premium => 'Premium',
            self::Diesel => 'Diesel',
            self::Kerosene => 'Kerosene',
            self::Lpg => 'LPG (Autogas)',
            self::Electric => 'Electric Charging',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(self::values(), array_map(
            static fn (self $type): string => $type->label(),
            self::cases(),
        ));
    }
}
