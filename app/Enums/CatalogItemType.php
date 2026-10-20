<?php

namespace App\Enums;

enum CatalogItemType: string
{
    case Product = 'product';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Product',
            self::Service => 'Service',
        };
    }

    /**
     * The badge tone used by the `x-badge` component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Product => 'sky',
            self::Service => 'violet',
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
