<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Sale;
use App\Services\SalesReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Visual sales analytics. Head office compares the whole network, branch staff
 * are clamped to their own branch.
 */
class SalesDashboardController extends Controller
{
    public function __construct(private readonly SalesReportService $reports) {}

    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', Sale::class);

        $user = $request->user();
        $branchScope = $user->branch_id;
        $period = $this->resolvePeriod($request);

        return view('admin.sales.dashboard', [
            'period' => $period,
            'branchScope' => $branchScope,
            'managedBranch' => $branchScope !== null ? Branch::find($branchScope) : null,
            'branches' => $user->isSuperAdmin() ? Branch::orderBy('name')->get(['id', 'name']) : collect(),
            'summary' => $this->reports->summary($branchScope, $period['from'], $period['to']),
            'daily' => $this->reports->dailySeries($branchScope, $period['from'], $period['to']),
            'weekly' => $this->reports->dailySeries($branchScope, $period['from']->copy()->subDays(27), $period['to']),
            'monthly' => $this->reports->monthlySeries($branchScope, 12),
            'breakdown' => $this->reports->productBreakdown($branchScope, $period['from'], $period['to']),
            'payments' => $this->reports->paymentSplit($branchScope, $period['from'], $period['to']),
            'topBranches' => $this->reports->topBranches($period['from'], $period['to'], $branchScope),
            'compareBranches' => $this->reports->topBranches($period['from'], $period['to'], null, 50),
            'selectedBranchId' => $request->integer('branch_id') ?: null,
        ]);
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    protected function resolvePeriod(Request $request): array
    {
        $from = $request->date('from') ?? today()->subDays(29)->startOfDay();
        $to = $request->date('to') ?? today()->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return ['from' => $from->startOfDay(), 'to' => $to->startOfDay()];
    }
}
