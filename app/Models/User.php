<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'branch_id', 'phone', 'avatar_path', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasOne<Employee, $this>
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    /**
     * @return HasMany<TicketReply, $this>
     */
    public function ticketReplies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'recorded_by');
    }

    /**
     * @return BelongsToMany<Branch, $this>
     */
    public function followedBranches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_followers')->withTimestamps();
    }

    /**
     * @return HasMany<PriceChangeRequest, $this>
     */
    public function requestedPriceChanges(): HasMany
    {
        return $this->hasMany(PriceChangeRequest::class, 'requested_by');
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeWithRole(Builder $query, UserRole $role): void
    {
        $query->where('role', $role->value);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeHeadOffice(Builder $query): void
    {
        $query->where('role', UserRole::SuperAdmin->value);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeBranchScoped(Builder $query): void
    {
        $query->whereIn('role', [UserRole::BranchAdmin->value, UserRole::BranchStaff->value]);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeCustomers(Builder $query): void
    {
        $query->where('role', UserRole::Customer->value);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isStaff(): bool
    {
        return $this->role?->isStaff() ?? false;
    }

    public function canManageTimekeeping(): bool
    {
        return $this->role?->isTimekeepingManager() ?? false;
    }

    public function isTimekeepingEmployee(): bool
    {
        return $this->role?->isTimekeepingEmployee() ?? false;
    }

    /**
     * Head office staff oversee the whole network; branch staff are pinned to
     * the branch they were created for.
     */
    public function managesBranch(?Branch $branch): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($branch === null || $this->branch_id === null) {
            return false;
        }

        return $this->branch_id === $branch->id;
    }

    public function managesBranchId(?int $branchId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $branchId !== null && $this->branch_id === $branchId;
    }

    /**
     * The identifier used to scope every branch-owned query for this user.
     *
     * @return int|null Null for head office, meaning "all branches".
     */
    public function branchScopeId(): ?int
    {
        return $this->isSuperAdmin() ? null : $this->branch_id;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return collect($parts)
            ->filter()
            ->take(2)
            ->map(static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
