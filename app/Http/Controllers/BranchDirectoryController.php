<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Branch;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BranchDirectoryController extends Controller
{
    /**
     * The public locator: searchable, filterable directory backed by a Leaflet
     * map of every active branch.
     */
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        // The search box is the customer's starting location, not a station
        // filter. Always provide the full active station directory for ranking.
        $branchesQuery = Branch::query()->active();
        $locationQuery = $filters['q'] ?? null;

        $branches = $branchesQuery
            ->withAvg(['reviews as reviews_avg_rating' => fn ($query) => $query->published()], 'rating')
            ->withCount(['reviews as reviews_count' => fn ($query) => $query->published()])
            ->with(['catalogItems' => fn ($query) => $query->wherePivot('is_available', true)])
            ->orderBy('name')
            ->get();

        return view('branches.index', [
            'branches' => $branches,
            'mapPayload' => Branch::mapPayloadFor($branches),
            'locationQuery' => $locationQuery,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, Branch $branch): View
    {
        $branch->load([
            'catalogItems' => fn ($query) => $query
                ->wherePivot('is_available', true)
                ->where('catalog_items.is_active', true)
                ->orderBy('catalog_items.type')
                ->orderBy('catalog_items.name'),
        ]);

        abort_unless($branch->is_active || $this->canSeeInactiveBranch($request, $branch), 404);

        $reviews = Review::query()
            ->where('branch_id', $branch->id)
            ->published()
            ->where('is_flagged', false)
            ->with('user')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $user = $request->user();
        $existingReview = $user !== null
            ? Review::where('branch_id', $branch->id)->where('user_id', $user->id)->first()
            : null;

        return view('branches.show', [
            'branch' => $branch,
            'reviews' => $reviews,
            'existingReview' => $existingReview,
            'ratingBreakdown' => $this->ratingBreakdown($branch),
            'averageRating' => round((float) Review::where('branch_id', $branch->id)->published()->avg('rating'), 2),
            'reviewCount' => Review::where('branch_id', $branch->id)->published()->count(),
            'announcements' => Announcement::forBranch($branch->id)
                ->latest('publish_at')
                ->take(3)
                ->get(),
            'isFollowing' => $user !== null
                && $user->followedBranches()->where('branches.id', $branch->id)->exists(),
            'products' => $branch->catalogItems->filter->isProduct()->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);
    }

    /**
     * @return array<int, array{rating: int, count: int, percentage: float}>
     */
    protected function ratingBreakdown(Branch $branch): array
    {
        $counts = Review::where('branch_id', $branch->id)
            ->published()
            ->selectRaw('rating, COUNT(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');

        $total = max(1, (int) $counts->sum());

        return collect(range(Review::MAX_RATING, 1))->map(function (int $rating) use ($counts, $total): array {
            $count = (int) ($counts[$rating] ?? 0);

            return [
                'rating' => $rating,
                'count' => $count,
                'percentage' => round(($count / $total) * 100),
            ];
        })->all();
    }

    protected function canSeeInactiveBranch(Request $request, Branch $branch): bool
    {
        $user = $request->user();

        return $user !== null && $user->isStaff() && $user->managesBranch($branch);
    }
}
