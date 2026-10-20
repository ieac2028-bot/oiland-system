@php
    use App\Enums\PriceChangeStatus;
    use App\Enums\TicketStatus;
    use App\Models\PriceChangeRequest;
    use App\Models\Review;
    use App\Models\Ticket;
    use Illuminate\Support\Facades\Gate;

    $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;

    // Badge counters are scoped to the signed-in user so a branch manager only
    // ever sees their own branch's queue depth.
    $badges = [
        'pendingPriceChanges' => PriceChangeRequest::forBranch(auth()->user()->branch_id)
            ->pending()
            ->count(),
        'openTickets' => Ticket::forBranchScope(auth()->user()->branch_id)->open()->count(),
        'flaggedReviews' => Review::when(auth()->user()->branch_id !== null, fn ($query) => $query->where('branch_id', auth()->user()->branch_id))
            ->flagged()
            ->count(),
    ];

    $sections = [
    [
        'label' => 'Overview',
        'items' => [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
        ],
    ],
    [
        'label' => 'Network',
        'items' => [
            ['route' => 'admin.branches.index', 'label' => 'Branches', 'icon' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z', 'gate' => 'viewAny', 'model' => \App\Models\Branch::class],
            ['route' => 'admin.catalog.index', 'label' => 'Products & Services', 'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z', 'superAdminOnly' => true],
        ],
    ],
    [
        'label' => 'Pricing',
        'items' => [
            ['route' => 'admin.prices.index', 'label' => 'Price listing', 'icon' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            ['route' => 'admin.price-changes.index', 'label' => 'Approvals', 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'badge' => 'pendingPriceChanges'],
        ],
    ],
    [
        'label' => 'Customer engagement',
        'items' => [
            ['route' => 'admin.tickets.index', 'label' => 'Inquiries', 'icon' => 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z', 'badge' => 'openTickets'],
            ['route' => 'admin.reviews.index', 'label' => 'Reviews', 'icon' => 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z', 'badge' => 'flaggedReviews'],
        ],
    ],
    [
        'label' =>     'Sales',
        'items' => [
            ['route' => 'admin.sales.dashboard', 'label' => 'Analytics', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
            ['route' => 'admin.sales.index', 'label' => 'Sales ledger', 'icon' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12'],
            ['route' => 'admin.sales.import', 'label' => 'CSV import', 'icon' => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3'],
        ],
    ],
    [
        'label' => 'Governance',
        'items' => [
            ['route' => 'admin.announcements.index', 'label' => 'Announcements', 'icon' => 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397.94-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.203.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-.94.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z', 'superAdminOnly' => true],
            ['route' => 'admin.users.index', 'label' => 'Staff accounts', 'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z', 'superAdminOnly' => true],
            ['route' => 'admin.activity-log.index', 'label' => 'Activity log', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ],
    ],
];
@endphp

<aside class="fixed bottom-0 left-0 top-[100px] z-50 hidden w-72 -translate-x-full border-r border-slate-200 bg-white transition-transform lg:translate-x-0 lg:flex lg:flex-col" data-sidebar>
    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        @foreach ($sections as $section)
            @php
                $visibleItems = collect($section['items'])->reject(function (array $item) use ($isSuperAdmin): bool {
                    if (($item['superAdminOnly'] ?? false) && ! $isSuperAdmin) {
                        return true;
                    }

                    if (isset($item['gate'], $item['model'])) {
                        return ! Gate::allows($item['gate'], $item['model']);
                    }

                    return false;
                });
            @endphp

            @if ($visibleItems->isNotEmpty())
                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $section['label'] }}</p>
                    <ul class="space-y-0.5">
                        @foreach ($visibleItems as $item)
                            @php($badgeCount = isset($item['badge']) ? ($badges[$item['badge']] ?? 0) : 0)
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   @class([
                                       'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                       'bg-brand-50 text-brand-700' => request()->routeIs($item['route'].'*'),
                                       'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($item['route'].'*'),
                                   ])>
                                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                                    </svg>
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    @if ($badgeCount > 0)
                                        <span class="rounded-full bg-brand-600 px-2 py-0.5 text-[11px] font-bold text-white">{{ $badgeCount }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="shrink-0 border-t border-slate-200 p-4">
        <div class="rounded-lg bg-slate-50 p-3">
            <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
            <p class="text-xs text-slate-500">
                {{ auth()->user()->role?->label() }}
                @if (auth()->user()->branch)
                    · {{ auth()->user()->branch->name }}
                @else
                    · Head office
                @endif
            </p>
            <a href="{{ route('home') }}" class="mt-2 inline-block text-xs font-semibold text-brand-700 hover:underline">View public site</a>
        </div>
    </div>
</aside>

<div class="fixed inset-0 z-40 hidden bg-slate-900/50 lg:hidden" data-sidebar-overlay></div>
