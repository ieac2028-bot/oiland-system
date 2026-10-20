<?php

namespace App\Models;

use App\Enums\PriceChangeStatus;
use Database\Factories\PriceChangeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'branch_id',
    'catalog_item_id',
    'old_price',
    'new_price',
    'note',
    'status',
    'requested_by',
    'reviewed_by',
    'reviewed_at',
    'review_note',
])]
class PriceChangeRequest extends Model
{
    /** @use HasFactory<PriceChangeRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PriceChangeStatus::class,
            'old_price' => 'decimal:2',
            'new_price' => 'decimal:2',
            'reviewed_at' => 'datetime',
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
     * @return BelongsTo<CatalogItem, $this>
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<PriceHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', PriceChangeStatus::Pending->value);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeStatus(Builder $query, ?PriceChangeStatus $status): void
    {
        if ($status === null) {
            return;
        }

        $query->where('status', $status->value);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeForBranch(Builder $query, ?int $branchId): void
    {
        if ($branchId === null) {
            return;
        }

        $query->where('branch_id', $branchId);
    }

    public function isPending(): bool
    {
        return $this->status === PriceChangeStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === PriceChangeStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === PriceChangeStatus::Rejected;
    }

    /**
     * Absolute and percentage movement between the old and the proposed price.
     *
     * @return array{difference: float, percentage: float|null, direction: string}
     */
    public function changeSummary(): array
    {
        $old = $this->old_price !== null ? (float) $this->old_price : null;
        $new = (float) $this->new_price;
        $difference = $old === null ? $new : round($new - $old, 2);

        return [
            'difference' => $difference,
            'percentage' => $old ? round((($new - $old) / $old) * 100, 2) : null,
            'direction' => $difference > 0 ? 'increase' : ($difference < 0 ? 'decrease' : 'unchanged'),
        ];
    }
}
