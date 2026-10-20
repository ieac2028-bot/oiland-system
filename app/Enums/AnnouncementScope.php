<?php

namespace App\Enums;

enum AnnouncementScope: string
{
    case Global = 'global';
    case Branches = 'branches';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'Entire Network',
            self::Branches => 'Selected Branches',
        };
    }

    /**
     * The badge tone used by the `x-badge` component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Global => 'brand',
            self::Branches => 'violet',
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
            static fn (self $scope): string => $scope->label(),
            self::cases(),
        ));
    }
}
