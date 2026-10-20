<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
        };
    }

    /**
     * The badge tone used by the `x-badge` component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::InProgress => 'sky',
            self::Resolved => 'emerald',
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
