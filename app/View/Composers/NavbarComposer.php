<?php

namespace App\View\Composers;

use App\Models\Branch;
use App\Models\CatalogItem;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewContract;

class NavbarComposer
{
    /**
     * Shared navigation data. The navbar renders on every page, so the lookups
     * live here as lazy collections: nothing queries until the view actually
     * iterates them, and each list is only ever requested once per request.
     */
    public function compose(ViewContract $view): void
    {
        $view->with([
            'navFuels' => CatalogItem::active()
                ->products()
                ->whereNotNull('fuel_type')
                ->orderBy('name')
                ->lazy(),
            'navServices' => CatalogItem::active()
                ->services()
                ->orderBy('name')
                ->lazy(),
            'navRegions' => Branch::active()
                ->orderBy('region')
                ->distinct()
                ->pluck('region')
                ->lazy(),
            'navBranchTotal' => Branch::active()->count(),
        ]);
    }
}
