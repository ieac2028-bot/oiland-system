<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Pending = 'pending';
    case Published = 'published';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting Moderation',
            self::Published => 'Published',
            self::Hidden => 'Hidden',
        };
    }

    /**
     * The badge tone used by the `x-badge` component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Published => 'emerald',
            self::Hidden => 'slate',
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
            static fn (self $status): string => $status->label(),
            self::cases(),
        ));
    }
}
