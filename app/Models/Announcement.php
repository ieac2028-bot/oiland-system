<?php

namespace App\Models;

use App\Enums\AnnouncementScope;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'slug',
    'body',
    'type',
    'image_path',
    'scope',
    'publish_at',
    'expires_at',
    'is_published',
    'is_featured',
    'author_id',
])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => AnnouncementScope::class,
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $announcement): void {
            if (blank($announcement->slug)) {
                $announcement->slug = static::uniqueSlug($announcement->title);
            }
        });
    }

    protected static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
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
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsToMany<Branch, $this>
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'announcement_branch');
    }

    /**
     * Announcements whose publish and expiry window contains the current time.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * Live announcements relevant to a given branch: either network wide or
     * explicitly targeted at that branch.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeForBranch(Builder $query, ?int $branchId): void
    {
        $query->live()->where(function (Builder $query) use ($branchId): void {
            $query->where('scope', AnnouncementScope::Global->value);

            if ($branchId !== null) {
                $query->orWhereHas('branches', fn (Builder $query) => $query->where('branches.id', $branchId));
            }
        });
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeExpired(Builder $query): void
    {
        $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }

    public function isLive(): bool
    {
        return $this->is_published
            && ($this->publish_at === null || $this->publish_at->lte(now()))
            && ($this->expires_at === null || $this->expires_at->gt(now()));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->publish_at !== null && $this->publish_at->gt(now());
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->isScheduled() => 'Scheduled',
            $this->isExpired() => 'Expired',
            ! $this->is_published => 'Unpublished',
            default => 'Live',
        };
    }

    public function summary(int $limit = 160): string
    {
        return Str::limit(strip_tags($this->body), $limit);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'job' => 'Job opening',
            'event' => 'Employee event',
            default => 'Notice',
        };
    }
}
