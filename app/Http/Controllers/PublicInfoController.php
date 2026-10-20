<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Branch;
use Illuminate\Contracts\View\View;

class PublicInfoController extends Controller
{
    public function about(): View
    {
        return view('about', [
            'branchCount' => Branch::active()->count(),
            'cityCount' => Branch::active()->distinct()->count('city'),
        ]);
    }

    public function careers(): View
    {
        return view('careers.index', [
            'openings' => Announcement::query()
                ->live()
                ->where('type', 'job')
                ->with('branches:id,name,city')
                ->latest('publish_at')
                ->paginate(12),
        ]);
    }
}
