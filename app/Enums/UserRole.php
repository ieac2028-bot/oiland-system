<?php

namespace App\Enums;

/**
 * The roles supported by the platform, ordered from the highest level of
 * head office oversight down to a public customer account.
 */
enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case BranchAdmin = 'branch_admin';
    case BranchStaff = 'branch_staff';
    case HrOfficer = 'hr_officer';
    case Employee = 'employee';
    case Customer = 'customer';

    /**
     * Head office roles may oversee every branch in the network.
     */
    public function isHeadOffice(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * Branch scoped roles are pinned to a single branch through the
     * `branch_id` column on the users table.
     */
    public function isBranchScoped(): bool
    {
        return in_array($this, [self::BranchAdmin, self::BranchStaff, self::HrOfficer, self::Employee], true);
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::SuperAdmin, self::BranchAdmin, self::BranchStaff], true);
    }

    public function isTimekeepingManager(): bool
    {
        return in_array($this, [self::SuperAdmin, self::BranchAdmin, self::HrOfficer], true);
    }

    public function isTimekeepingEmployee(): bool
    {
        return $this === self::Employee;
    }

    public function isTimekeepingUser(): bool
    {
        return $this->isTimekeepingManager() || $this->isTimekeepingEmployee();
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::BranchAdmin => 'Officer in Charge (OIC)',
            self::BranchStaff => 'Branch Staff',
            self::HrOfficer => 'HR Officer',
            self::Employee => 'Employee',
            self::Customer => 'Customer',
        };
    }

    /**
     * The badge tone used by the `x-badge` component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::SuperAdmin => 'violet',
            self::BranchAdmin => 'brand',
            self::BranchStaff => 'sky',
            self::HrOfficer => 'violet',
            self::Employee => 'emerald',
            self::Customer => 'slate',
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
            static fn (self $role): string => $role->label(),
            self::cases(),
        ));
    }
}
