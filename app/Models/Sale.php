<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\SaleSource;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'branch_id',
    'catalog_item_id',
    'item_name',
    'sale_date',
    'quantity',
    'unit_price',
    'total_amount',
    'currency',
    'payment_method',
    'source',
    'notes',
    'recorded_by',
])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'source' => SaleSource::class,
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
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
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
    public function scopeBetweenDates(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('sale_date', [$from, $to]);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOnDate(Builder $query, string $date): void
    {
        $query->whereDate('sale_date', $date);
    }
}
