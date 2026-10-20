<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Normal => 'Normal',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    /**
     * The badge tone used by the `x-badge` component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Low => 'slate',
            self::Normal => 'sky',
            self::High => 'amber',
            self::Urgent => 'rose',
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
            static fn (self $priority): string => $priority->label(),
            self::cases(),
        ));
    }
}
