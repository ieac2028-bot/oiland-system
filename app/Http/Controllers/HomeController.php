<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Review;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $branchCount = Branch::active()->count();
        $cityCount = Branch::active()->distinct()->count('city');
        $averageRating = round((float) Review::published()->avg('rating'), 1);

        // Featured announcements win, but fall back to the newest live posts so
        // the home page is never empty just because nothing is flagged yet.
        $announcements = Announcement::query()
            ->live()
            ->whereIn('type', ['notice', 'event'])
            ->where('is_featured', true)
            ->latest('publish_at')
            ->take(3)
            ->get();

        if ($announcements->count() < 3) {
            $announcements = Announcement::query()
                ->live()
                ->whereIn('type', ['notice', 'event'])
                ->whereNotIn('id', $announcements->pluck('id'))
                ->latest('publish_at')
                ->take(3 - $announcements->count())
                ->get()
                ->merge($announcements)
                ->sortByDesc('publish_at')
                ->values();
        }

        return view('home', [
            'branchCount' => $branchCount,
            'cityCount' => $cityCount,
            'averageRating' => $averageRating,
            'openTicketCount' => $request->user()
                ? Ticket::where('customer_id', $request->user()->id)->open()->count()
                : 0,
            'announcements' => $announcements,
            'popularBranches' => Branch::active()
                ->withAvg(['reviews as reviews_avg_rating' => fn ($query) => $query->published()], 'rating')
                ->withCount(['reviews as reviews_count' => fn ($query) => $query->published()])
                ->orderByDesc('reviews_avg_rating')
                ->take(3)
                ->get(),
            'regions' => Branch::active()->orderBy('region')->distinct()->pluck('region'),
            'cities' => Branch::active()->orderBy('city')->distinct()->pluck('city'),
            'quickFilters' => $this->quickFilters(),
            'fuels' => $this->fuels(),
        ]);
    }

    /**
     * Shortcut tiles for the station finder. These link straight into the
     * directory's existing filter parameters rather than a parallel search
     * path, so the finder and the directory can never disagree.
     *
     * @return Collection<int, array{label: string, query: array<string, int|string>, icon: string}>
     */
    protected function quickFilters(): Collection
    {
        return collect([
            [
                'label' => 'Open 24 hours',
                'query' => ['open_now' => 1],
                'icon' => 'M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z',
            ],
            [
                'label' => 'Not 24-hour stations',
                'query' => ['open_now' => 0],
                'icon' => 'M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z',
            ],
        ]);
    }

    /**
     * The three confirmed fuel grades for the home page.
     *
     * @return Collection<int, CatalogItem>
     */
    protected function fuels()
    {
        return CatalogItem::active()
            ->products()
            ->whereIn('sku', ['FUEL-DSL', 'FUEL-P95', 'FUEL-U91'])
            ->orderBy('name')
            ->get();
    }
}
