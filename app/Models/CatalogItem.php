<?php

namespace App\Models;

use App\Enums\CatalogItemType;
use App\Enums\FuelType;
use Database\Factories\CatalogItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'type',
    'sku',
    'fuel_type',
    'unit',
    'short_description',
    'description',
    'image_path',
    'is_active',
])]
class CatalogItem extends Model
{
    /** @use HasFactory<CatalogItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CatalogItemType::class,
            'fuel_type' => FuelType::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            if (blank($item->slug)) {
                $item->slug = static::uniqueSlug($item->name);
            }
        });
    }

    protected static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsToMany<Branch, $this>
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_catalog_item')
            ->withPivot(['is_available', 'notes'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Price, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    /**
     * @return HasMany<PriceChangeRequest, $this>
     */
    public function priceChangeRequests(): HasMany
    {
        return $this->hasMany(PriceChangeRequest::class);
    }

    /**
     * @return HasMany<PriceHistory, $this>
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOfType(Builder $query, CatalogItemType $type): void
    {
        $query->where('type', $type->value);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeProducts(Builder $query): void
    {
        $query->ofType(CatalogItemType::Product);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeServices(Builder $query): void
    {
        $query->ofType(CatalogItemType::Service);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('short_description', 'like', $like);
        });
    }

    public function isProduct(): bool
    {
        return $this->type === CatalogItemType::Product;
    }

    public function isService(): bool
    {
        return $this->type === CatalogItemType::Service;
    }

    public function displayName(): string
    {
        return $this->fuel_type?->label() ?? $this->name;
    }
}
