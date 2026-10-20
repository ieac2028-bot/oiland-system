<?php

namespace App\Models;

use App\Enums\CatalogItemType;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    /**
     * The days of the week used to key the `opening_hours` JSON column.
     *
     * @var array<int, string>
     */
    public const WEEK_DAYS = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];

    protected $fillable = [
        'name',
        'slug',
        'code',
        'address_line1',
        'address_line2',
        'city',
        'region',
        'postal_code',
        'country',
        'phone',
        'email',
        'latitude',
        'longitude',
        'is_24_hours',
        'opening_hours',
        'photos',
        'description',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_24_hours' => 'boolean',
            'is_active' => 'boolean',
            'opening_hours' => 'array',
            'photos' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $branch): void {
            if (blank($branch->slug)) {
                $branch->slug = static::uniqueSlug($branch->name);
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
     * @return BelongsToMany<CatalogItem, $this>
     */
    public function catalogItems(): BelongsToMany
    {
        return $this->belongsToMany(CatalogItem::class, 'branch_catalog_item')
            ->withPivot(['is_available', 'notes'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<CatalogItem, $this>
     */
    public function availableProducts(): BelongsToMany
    {
        return $this->catalogItems()
            ->where('catalog_items.type', CatalogItemType::Product->value)
            ->wherePivot('is_available', true)
            ->where('catalog_items.is_active', true);
    }

    /**
     * @return BelongsToMany<CatalogItem, $this>
     */
    public function availableServices(): BelongsToMany
    {
        return $this->catalogItems()
            ->where('catalog_items.type', CatalogItemType::Service->value)
            ->wherePivot('is_available', true)
            ->where('catalog_items.is_active', true);
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
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
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
        return $this->hasMany(Sale::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return BelongsToMany<Announcement, $this>
     */
    public function announcements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class, 'announcement_branch');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_followers')->withTimestamps();
    }

    /**
     * @return HasManyThrough<CatalogItem, Price, $this>
     */
    public function pricedCatalogItems(): HasManyThrough
    {
        return $this->hasManyThrough(CatalogItem::class, Price::class)
            ->where('prices.is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeLocated(Builder $query): void
    {
        $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('address_line1', 'like', $like)
                ->orWhere('city', 'like', $like)
                ->orWhere('region', 'like', $like);
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeInRegion(Builder $query, ?string $region): void
    {
        if (filled($region)) {
            $query->where('region', $region);
        }
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeInCity(Builder $query, ?string $city): void
    {
        if (filled($city)) {
            $query->where('city', $city);
        }
    }

    /**
     * Filter branches that currently offer a given catalog item.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOfferingCatalogItem(Builder $query, int $catalogItemId): void
    {
        $query->whereHas('catalogItems', fn (Builder $query) => $query
            ->where('catalog_items.id', $catalogItemId)
            ->wherePivot('is_available', true));
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function fullAddress(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->region,
            $this->postal_code,
            $this->country,
        ])->filter()->implode(', ');
    }

    /**
     * The operating hours for a given day, or null when the branch is closed.
     *
     * @return array{open: string|null, close: string|null}|null
     */
    public function hoursForDay(?string $day = null): ?array
    {
        $day ??= strtolower(now()->format('l'));
        $hours = $this->opening_hours[$day] ?? null;

        if (! is_array($hours) || blank($hours['open'] ?? null)) {
            return null;
        }

        return [
            'open' => $hours['open'] ?? null,
            'close' => $hours['close'] ?? null,
        ];
    }

    public function isOpenNow(): bool
    {
        if ($this->is_24_hours) {
            return true;
        }

        $hours = $this->hoursForDay();

        if ($hours === null) {
            return false;
        }

        $now = now()->format('H:i');
        $open = $hours['open'] ?? '00:00';
        $close = $hours['close'] ?? '23:59';

        if ($open <= $close) {
            return $now >= $open && $now <= $close;
        }

        return $now >= $open || $now <= $close;
    }

    /**
     * @return array<string, string>
     */
    public function hoursSummary(): array
    {
        return collect(self::WEEK_DAYS)->mapWithKeys(function (string $label, string $key): array {
            $hours = $this->opening_hours[$key] ?? null;
            $value = $this->is_24_hours
                ? 'Open 24 hours'
                : (is_array($hours) && filled($hours['open'] ?? null)
                    ? sprintf('%s - %s', $hours['open'], $hours['close'] ?? '')
                    : 'Closed');

            return [$label => $value];
        })->all();
    }

    /**
     * The public payload consumed by the Leaflet map and locator filters.
     *
     * @return array<string, mixed>
     */
    public function toMapPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'city' => $this->city,
            'region' => $this->region,
            'address' => $this->fullAddress(),
            'phone' => $this->phone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'url' => route('branches.show', $this),
            'average_rating' => round((float) ($this->reviews_avg_rating ?? 0), 2),
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'is_open_now' => $this->isOpenNow(),
            'is_24_hours' => $this->is_24_hours,
            'hours_today' => $this->hoursSummary()[now()->format('l')] ?? 'Closed',
        ];
    }

    /**
     * @param  Collection<int, self>  $branches
     * @return Collection<int, array<string, mixed>>
     */
    public static function mapPayloadFor(Collection $branches): Collection
    {
        return $branches->map(static fn (self $branch): array => $branch->toMapPayload())->values();
    }
}
