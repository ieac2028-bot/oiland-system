<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Mobile = 'mobile';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Mobile => 'Mobile Wallet',
            self::Credit => 'Fleet Credit',
        };
    }

    /**
     * The badge tone used by the `x-badge` component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Cash => 'emerald',
            self::Card => 'sky',
            self::Mobile => 'violet',
            self::Credit => 'amber',
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
            static fn (self $method): string => $method->label(),
            self::cases(),
        ));
    }
}
