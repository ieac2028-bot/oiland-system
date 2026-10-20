<nav class="site-header sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
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
            <img src="{{ asset('images/oiland-logo-transparent.png') }}" alt="Oiland logo" width="56" height="56" class="h-14 w-14 object-contain" />
            <span class="header-choice text-base font-bold tracking-tight text-black dark:text-white">Oiland</span>
        </a>

        <div class="hidden items-center gap-1 md:flex">
            <x-dropdown align="left" width="80">
                <x-slot name="trigger">
                    <button type="button" class="header-choice flex items-center gap-1 px-2 py-2 text-sm font-bold text-black transition hover:text-brand-700 focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-600 dark:text-white dark:hover:text-brand-200">
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
            <button type="button" data-theme-toggle aria-label="Switch to dark mode" title="Switch to dark mode" class="header-choice inline-flex h-11 w-11 items-center justify-center rounded-full text-black transition hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:text-white dark:hover:text-brand-200">
                <svg data-theme-icon="moon" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 15.75A8.25 8.25 0 0 1 8.25 3.75a8.25 8.25 0 1 0 12 12Z" />
                </svg>
                <svg data-theme-icon="sun" class="hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5m-15 0H3m15.364 6.364-1.06-1.06M6.696 6.696l-1.06-1.06m12.728 0-1.06 1.06M6.696 17.304l-1.06 1.06M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                </svg>
            </button>
            @auth
                <a href="{{ route('notifications.index') }}" class="header-choice relative p-2.5 text-black transition hover:text-brand-700 focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:text-white dark:hover:text-brand-200" aria-label="Notifications">
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
                        <button class="header-choice flex items-center gap-2 px-2 py-1.5 text-black focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:text-white" type="button">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">
                                {{ auth()->user()->initials() }}
                            </span>
                            <span class="hidden text-sm font-medium sm:inline">{{ auth()->user()->name }}</span>
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
                <a href="{{ route('login') }}" class="header-choice px-2 py-2 text-sm font-semibold text-black transition hover:text-brand-700 focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-600 dark:text-white dark:hover:text-brand-200">Sign in</a>
                <a href="{{ route('register') }}" class="header-choice px-2 py-2 text-sm font-bold text-black transition hover:text-brand-700 focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-600 dark:text-white dark:hover:text-brand-200">Create account</a>
            @endauth
        </div>
    </div>
</nav>

<svg class="ambient-lines" data-ambient-lines viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
    <path d="M-60 190 C20 80 170 25 300 75 C440 128 470 255 370 315 C275 372 120 336 45 260 C8 222 -25 204 -60 190 Z" />
    <path d="M510 -45 C640 10 760 15 830 100 C910 197 860 302 735 330 C615 357 470 300 450 205 C432 125 492 55 510 -45 Z" />
    <path d="M1015 45 C1135 -10 1290 10 1410 78 C1518 140 1490 265 1380 320 C1260 380 1090 335 1040 245 C1005 180 1034 105 1105 75 C1175 45 1250 73 1265 130 C1280 187 1235 225 1185 214 C1115 200 1100 130 1150 95 C1195 65 1255 85 1265 130 Z" />
    <path d="M260 520 C345 435 490 415 600 470 C720 530 760 680 680 775 C600 870 430 870 325 810 C215 748 175 635 220 565 C265 495 360 485 420 530 C480 575 475 650 425 680 C380 707 325 680 320 635" />
    <path d="M870 520 C955 420 1125 405 1240 470 C1365 540 1405 690 1320 780 C1230 875 1060 865 950 800 C840 735 805 625 850 560 C895 495 985 485 1045 530 C1105 575 1100 650 1050 680 C990 710 925 685 930 625 C935 565 1000 540 1045 575 C1080 602 1070 645 1040 655 Z" />
</svg>

<aside class="accessibility" data-accessibility>
    <section id="accessibility-panel" data-accessibility-panel class="accessibility-panel" role="dialog" aria-labelledby="accessibility-title" aria-modal="false" hidden>
        <div class="accessibility-head">
            <div><h2 id="accessibility-title">Accessibility</h2><span>Choose what works for you</span></div>
            <button type="button" data-accessibility-close aria-label="Close accessibility settings">×</button>
        </div>
        <div class="accessibility-row"><span>Text size</span><div class="accessibility-size-options" role="group" aria-label="Text size">
            <button type="button" data-a11y-size="default" aria-label="Default text size">A</button>
            <button type="button" data-a11y-size="large" aria-label="Larger text">A<sup>+</sup></button>
            <button type="button" data-a11y-size="largest" aria-label="Largest text">A<sup>++</sup></button>
        </div></div>
        <button class="accessibility-option" type="button" data-a11y-toggle="contrast" aria-pressed="false"><span><strong>Higher contrast</strong><small>Make text and borders clearer</small></span><i aria-hidden="true"></i></button>
        <button class="accessibility-option" type="button" data-a11y-toggle="motion" aria-pressed="false"><span><strong>Reduce motion</strong><small>Limit moving effects</small></span><i aria-hidden="true"></i></button>
        <button class="accessibility-option" type="button" data-a11y-toggle="links" aria-pressed="false"><span><strong>Underline links</strong><small>Make links easier to spot</small></span><i aria-hidden="true"></i></button>
        <button class="accessibility-option" type="button" data-a11y-toggle="spacing" aria-pressed="false"><span><strong>More text spacing</strong><small>Increase letter, word, and line spacing</small></span><i aria-hidden="true"></i></button>
        <button class="accessibility-option" type="button" data-a11y-toggle="font" aria-pressed="false"><span><strong>Plain reading font</strong><small>Use familiar system fonts</small></span><i aria-hidden="true"></i></button>
        <button class="accessibility-reset" type="button" data-a11y-reset>Reset options</button>
    </section>
    <button type="button" data-accessibility-toggle aria-expanded="false" aria-controls="accessibility-panel" class="accessibility-launcher" aria-label="Open accessibility settings">
        <span class="accessibility-icon" aria-hidden="true"><svg viewBox="0 0 32 32"><circle cx="16" cy="5.5" r="3"/><path d="M4 11h24M16 9v11m0-8-8 5m8-5 8 5m-8 3-6 9m6-9 6 9"/></svg></span>
        <span class="accessibility-copy"><strong>Accessibility</strong><small>Text, contrast &amp; more</small></span>
    </button>
</aside>
