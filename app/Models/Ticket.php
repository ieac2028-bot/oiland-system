<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable([
    'reference',
    'customer_id',
    'branch_id',
    'assigned_to',
    'subject',
    'message',
    'category',
    'priority',
    'status',
    'resolved_at',
    'resolved_by',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * The categories a customer can pick when opening an inquiry.
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'general' => 'General Question',
        'billing' => 'Billing & Invoicing',
        'fuel_quality' => 'Fuel Quality',
        'service_quality' => 'Service Quality',
        'lost_property' => 'Lost Property',
        'complaint' => 'Complaint',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            if (blank($ticket->reference)) {
                $ticket->reference = static::generateReference();
            }
        });
    }

    /**
     * Human friendly, unique, sequential-ish ticket reference such as TKT-2026-4F2A.
     */
    public static function generateReference(): string
    {
        do {
            $reference = sprintf('TKT-%s-%s', now()->format('Y'), strtoupper(Str::random(4)));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * @return HasMany<TicketReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    /**
     * Replies the customer is allowed to see.
     *
     * @return HasMany<TicketReply, $this>
     */
    public function publicReplies(): HasMany
    {
        return $this->replies()->where('is_internal_note', false)->oldest();
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForBranchScope(Builder $query, ?int $branchId): void
    {
        if ($branchId === null) {
            return;
        }

        $query->where('branch_id', $branchId);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeStatus(Builder $query, ?TicketStatus $status): void
    {
        if ($status !== null) {
            $query->where('status', $status->value);
        }
    }

    /**
     * Free-text search across the reference, subject, message and the customer's
     * name so the desk can find an inquiry by whatever the caller remembers.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('reference', 'like', $like)
                ->orWhere('subject', 'like', $like)
                ->orWhere('message', 'like', $like)
                ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $like))
                ->orWhereHas('branch', fn (Builder $branch) => $branch->where('name', 'like', $like));
        });
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [TicketStatus::Pending->value, TicketStatus::InProgress->value]);
    }

    public function isResolved(): bool
    {
        return $this->status === TicketStatus::Resolved;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', (string) $this->category));
    }

    public function lastActivityAt(): Carbon
    {
        $latestReply = $this->replies()->max('created_at');

        return $latestReply !== null
            ? Carbon::parse($latestReply)
            : $this->created_at;
    }
}
