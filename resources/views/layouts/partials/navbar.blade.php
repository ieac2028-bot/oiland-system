<nav class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="bg-deep-950 text-white">
        <div class="mx-auto flex h-9 max-w-7xl items-center justify-between gap-4 px-4 text-xs sm:px-6 lg:px-8">
            <p class="truncate text-brand-100/80">
                Station information for {{ number_format($navBranchTotal) }} locations nationwide
            </p>

            <div class="flex shrink-0 items-center gap-4">
                <a href="{{ route('branches.index') }}" class="font-medium text-white/80 transition hover:text-white">Find a station</a>
                <span class="hidden h-3 w-px bg-white/20 sm:block"></span>
                <span class="hidden text-white/60 sm:inline">PH &middot; {{ config('app.timezone') }}</span>
            </div>
        </div>
    </div>

    <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2">
            <img src="{{ asset('images/oiland-logo-transparent.png') }}" alt="{{ config('app.name') }} logo" width="56" height="56" class="h-14 w-14 object-contain" />
            <span class="text-base font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</span>
        </a>

        <div class="hidden items-center gap-1 md:flex">
            <x-dropdown align="left" width="80">
                <x-slot name="trigger">
                    <button type="button" class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Explore stations
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="p-2">
                        <x-dropdown-link :href="route('branches.index')" class="font-semibold">All stations</x-dropdown-link>

                        <p class="px-2 pb-1 pt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Fuel grades</p>
                        @foreach ($navFuels as $fuel)
                            <x-dropdown-link :href="route('branches.index', ['product' => $fuel->id])">{{ $fuel->name }}</x-dropdown-link>
                        @endforeach

                        <p class="px-2 pb-1 pt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Services</p>
                        @foreach ($navServices as $service)
                            <x-dropdown-link :href="route('branches.index', ['service' => $service->id])">{{ $service->name }}</x-dropdown-link>
                        @endforeach

                        <p class="px-2 pb-1 pt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Locations by region</p>
                        @foreach ($navRegions as $region)
                            <x-dropdown-link :href="route('branches.index', ['region' => $region])">{{ $region }}</x-dropdown-link>
                        @endforeach
                    </div>
                </x-slot>
            </x-dropdown>

            <x-nav-link :href="route('branches.index', ['open_now' => 1])" :active="false">Open now</x-nav-link>

            @auth
                @if (auth()->user()->role?->isTimekeepingUser())
                    <x-nav-link :href="route('timekeeping.dashboard')" :active="request()->routeIs('timekeeping.*')">Timekeeping</x-nav-link>
                    @if (auth()->user()->isStaff())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">Staff console</x-nav-link>
                    @endif
                @elseif (auth()->user()->isStaff())
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">Staff console</x-nav-link>
                @else
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">My dashboard</x-nav-link>
                    <x-nav-link :href="route('my.tickets.index')" :active="request()->routeIs('my.tickets.*')">Inquiries</x-nav-link>
                    <x-nav-link :href="route('my.branches.index')" :active="request()->routeIs('my.branches.*')">Followed</x-nav-link>
                @endif
            @endauth

            <x-nav-link :href="route('careers.index')" :active="request()->routeIs('careers.*')">Jobs</x-nav-link>
            <x-nav-link :href="route('about')" :active="request()->routeIs('about')">About Us</x-nav-link>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <button type="button" data-theme-toggle aria-label="Switch to dark mode" title="Switch to dark mode" class="rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                <svg data-theme-icon="moon" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 15.75A8.25 8.25 0 0 1 8.25 3.75a8.25 8.25 0 1 0 12 12Z" />
                </svg>
                <svg data-theme-icon="sun" class="hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5m-15 0H3m15.364 6.364-1.06-1.06M6.696 6.696l-1.06-1.06m12.728 0-1.06 1.06M6.696 17.304l-1.06 1.06M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                </svg>
            </button>
            @auth
                <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Notifications">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    @php($unread = auth()->user()->unreadNotifications()->count())
                    @if ($unread > 0)
                        <span class="absolute right-1 top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-bold text-white">
                            {{ $unread > 9 ? '9+' : $unread }}
                        </span>
                    @endif
                </a>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-100" type="button">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">
                                {{ auth()->user()->initials() }}
                            </span>
                            <span class="hidden text-sm font-medium text-slate-700 sm:inline">{{ auth()->user()->name }}</span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-slate-500">
                            <p class="font-semibold text-slate-700">{{ auth()->user()->role?->label() }}</p>
                            @if (auth()->user()->branch)
                                <p>{{ auth()->user()->branch->name }}</p>
                            @endif
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">Profile &amp; password</x-dropdown-link>
                        <x-dropdown-link :href="route('notifications.index')">Notifications</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Sign out</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            @else
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Sign in</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-accent-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-accent-600">Create account</a>
            @endauth
        </div>
    </div>
</nav>
