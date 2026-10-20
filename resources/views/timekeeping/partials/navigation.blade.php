@php
    $sections = [
        ['label' => 'Employees', 'route' => 'timekeeping.employees.index', 'pattern' => 'timekeeping.employees.*', 'tab' => 'border-orange-200 bg-orange-50 text-orange-900 hover:bg-orange-100 dark:border-orange-800 dark:bg-orange-950 dark:text-orange-200 dark:hover:bg-orange-900'],
        ['label' => 'Attendance', 'route' => 'timekeeping.attendance.index', 'pattern' => 'timekeeping.attendance.*', 'tab' => 'border-green-200 bg-green-50 text-green-900 hover:bg-green-100 dark:border-green-800 dark:bg-green-950 dark:text-green-200 dark:hover:bg-green-900'],
        ['label' => 'Schedules', 'route' => 'timekeeping.schedules.index', 'pattern' => 'timekeeping.schedules.*', 'tab' => 'border-blue-200 bg-blue-50 text-blue-900 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200 dark:hover:bg-blue-900'],
        ['label' => 'Holidays', 'route' => 'timekeeping.holidays.index', 'pattern' => 'timekeeping.holidays.*', 'tab' => 'border-sky-200 bg-sky-50 text-sky-900 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-200 dark:hover:bg-sky-900'],
        ['label' => 'Leave', 'route' => 'timekeeping.leaves.index', 'pattern' => 'timekeeping.leaves.*', 'tab' => 'border-slate-200 bg-slate-50 text-slate-800 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700'],
        ['label' => 'Cutoff reports', 'route' => 'timekeeping.reports.cutoff', 'pattern' => 'timekeeping.reports.cutoff*', 'tab' => 'border-orange-200 bg-orange-50 text-orange-900 hover:bg-orange-100 dark:border-orange-800 dark:bg-orange-950 dark:text-orange-200 dark:hover:bg-orange-900'],
    ];
@endphp

<nav aria-label="Timekeeping sections" class="mx-auto mb-6 max-w-7xl px-4 pt-5 sm:px-6 lg:px-8">
    <div class="overflow-x-auto">
        <div class="flex w-max min-w-full items-end justify-end gap-1 border-b border-slate-300 dark:border-slate-600">
        @foreach ($sections as $section)
            @php($isActive = request()->routeIs($section['pattern']))
            <a href="{{ route($section['route']) }}"
                @class([
                    'relative -mb-px inline-flex min-h-11 items-center rounded-t-xl border-x border-t-4 px-4 py-3 text-sm transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-brand-600',
                    $section['tab'],
                    'z-10 -translate-y-0.5 font-semibold shadow-sm ring-1 ring-inset ring-white/70 dark:ring-white/10' => $isActive,
                    'font-medium opacity-80 hover:opacity-100' => ! $isActive,
                ])
                @if ($isActive) aria-current="page" @endif>
                {{ $section['label'] }}
            </a>
        @endforeach
        </div>
    </div>
</nav>
