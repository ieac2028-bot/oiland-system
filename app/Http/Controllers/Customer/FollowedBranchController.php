<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets a customer follow a branch so they are notified when its prices move.
 */
class FollowedBranchController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.followed-branches', [
            'branches' => $request->user()->followedBranches()
                ->withAvg(['reviews as reviews_avg_rating' => fn ($query) => $query->published()], 'rating')
                ->withCount(['reviews as reviews_count' => fn ($query) => $query->published()])
                ->orderBy('name')
                ->get(),
            'suggestions' => Branch::active()
                ->whereDoesntHave('followers', fn ($query) => $query->where('users.id', $request->user()->id))
                ->orderBy('name')
                ->take(6)
                ->get(),
        ]);
    }

    public function store(Request $request, Branch $branch): RedirectResponse
    {
        $request->user()->followedBranches()->syncWithoutDetaching([$branch->id]);

        return back()->with('status', "You will now receive price updates for {$branch->name}.");
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        $request->user()->followedBranches()->detach($branch->id);

        return back()->with('status', "You will no longer receive price updates for {$branch->name}.");
    }
}
