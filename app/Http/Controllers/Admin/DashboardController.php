<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PriceChangeStatus;
use App\Enums\ReviewStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PriceChangeRequest;
use App\Models\Review;
use App\Models\Sale;
use App\Models\Ticket;
use App\Services\SalesReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(private readonly SalesReportService $reports) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $branchScope = $user->branchScopeId();

        $from = Carbon::parse(today()->subDays(29)->toDateString());
        $to = Carbon::parse(today()->toDateString());

        $pendingReviewQuery = PriceChangeRequest::where('status', PriceChangeStatus::Pending->value);
        $openTicketQuery = Ticket::open();
        $flaggedReviewQuery = Review::where('is_flagged', true);

        if ($branchScope !== null) {
            $pendingReviewQuery->where('branch_id', $branchScope);
            $openTicketQuery->where('branch_id', $branchScope);
            $flaggedReviewQuery->where('branch_id', $branchScope);
        }

        return view('admin.dashboard', [
            'branchScope' => $branchScope,
            'managedBranch' => $branchScope !== null ? Branch::find($branchScope) : null,
            'summary' => $this->reports->summary($branchScope, $from, $to),
            'dailySeries' => $this->reports->dailySeries($branchScope, $from, $to),
            'monthlySeries' => $this->reports->monthlySeries($branchScope, 12),
            'productBreakdown' => $this->reports->productBreakdown($branchScope, $from, $to),
            'paymentSplit' => $this->reports->paymentSplit($branchScope, $from, $to),
            'topBranches' => $this->reports->topBranches($from, $to, $branchScope),
            'todayRevenue' => $this->reports->todayRevenue($branchScope),
            'monthToDateRevenue' => $this->reports->monthToDateRevenue($branchScope),
            'pendingPriceChanges' => $pendingReviewQuery->count(),
            'openTickets' => $openTicketQuery->count(),
            'flaggedReviews' => $flaggedReviewQuery->count(),
            'recentTickets' => Ticket::query()
                ->when($branchScope !== null, fn ($query) => $query->where('branch_id', $branchScope))
                ->with(['branch', 'customer'])
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [TicketStatus::Pending->value])
                ->latest('created_at')
                ->take(6)
                ->get(),
            'recentPriceChanges' => PriceChangeRequest::query()
                ->when($branchScope !== null, fn ($query) => $query->where('branch_id', $branchScope))
                ->with(['branch', 'catalogItem', 'requester', 'reviewer'])
                ->latest()
                ->take(6)
                ->get(),
            'todaySalesCount' => Sale::forBranchScope($branchScope)->onDate(today()->toDateString())->count(),
            'reviewCounts' => Review::query()
                ->when($branchScope !== null, fn ($query) => $query->where('branch_id', $branchScope))
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->map(fn ($value) => (int) $value)
                ->all(),
            'publishedReviewStatus' => ReviewStatus::Published->value,
        ]);
    }
}
