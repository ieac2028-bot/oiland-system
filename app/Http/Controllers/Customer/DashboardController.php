<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PriceChangeStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Branch;
use App\Models\Review;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * The customer home: open inquiries, followed branches and announcements.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $followedBranches = $user->followedBranches()
            ->active()
            ->withCount(['reviews as reviews_count' => fn ($query) => $query->published()])
            ->get();

        return view('customer.dashboard', [
            'openTickets' => Ticket::where('customer_id', $user->id)
                ->open()
                ->with('branch')
                ->latest()
                ->take(5)
                ->get(),
            'ticketCounts' => [
                'pending' => Ticket::where('customer_id', $user->id)->where('status', TicketStatus::Pending->value)->count(),
                'in_progress' => Ticket::where('customer_id', $user->id)->where('status', TicketStatus::InProgress->value)->count(),
                'resolved' => Ticket::where('customer_id', $user->id)->where('status', TicketStatus::Resolved->value)->count(),
            ],
            'followedBranches' => $followedBranches,
            'announcements' => Announcement::forBranch($user->branch_id)
                ->latest('publish_at')
                ->take(4)
                ->get(),
            'pendingReviewCount' => Review::where('user_id', $user->id)->count(),
            'pendingPriceRequests' => $user->isStaff()
                ? $user->requestedPriceChanges()->where('status', PriceChangeStatus::Pending->value)->count()
                : 0,
            'nearbyBranches' => Branch::active()->located()->orderBy('name')->take(4)->get(),
        ]);
    }
}
