<footer class="mt-16 bg-deep-950 text-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/oiland-logo-transparent.png') }}" alt="{{ config('app.name') }} logo" width="32" height="32" class="h-8 w-8 object-contain" />
                    <p class="text-sm font-semibold text-white">{{ config('app.name') }}</p>
                </div>
                <p class="mt-3 text-sm text-white/60">
                    Multi-branch fuel station information system with live prices, customer inquiries and network analytics.
                </p>
                <a href="{{ route('branches.index') }}"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-accent-500 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-accent-600">
                    Find a station
                </a>
            </div>

            <div>
                <p class="text-sm font-semibold text-white">Find fuel</p>
                <ul class="mt-3 space-y-1.5 text-sm text-white/60">
                    <li><a class="transition hover:text-brand-300" href="{{ route('branches.index') }}">Branch directory</a></li>
                    <li><a class="transition hover:text-brand-300" href="{{ route('branches.index') }}?open_now=1">Stations open now</a></li>
                    <li><a class="transition hover:text-brand-300" href="{{ route('branches.index') }}">Current prices</a></li>
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold text-white">My account</p>
                <ul class="mt-3 space-y-1.5 text-sm text-white/60">
                    <li><a class="transition hover:text-brand-300" href="{{ route('my.tickets.create') }}">Contact a branch</a></li>
                    <li><a class="transition hover:text-brand-300" href="{{ route('my.branches.index') }}">Followed stations</a></li>
                    <li><a class="transition hover:text-brand-300" href="{{ route('my.reviews.index') }}">My reviews</a></li>
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold text-white">Staff</p>
                <ul class="mt-3 space-y-1.5 text-sm text-white/60">
                    <li><a class="transition hover:text-brand-300" href="{{ route('admin.dashboard') }}">Staff console</a></li>
                    <li><a class="transition hover:text-brand-300" href="{{ route('admin.activity-log.index') }}">Activity log</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 border-t border-white/10 pt-6 text-xs text-white/50">
            <p>
                Map data &copy;
                <a class="underline" href="https://www.openstreetmap.org/copyright" rel="noopener noreferrer" target="_blank">OpenStreetMap</a>
                contributors. Geocoding by Nominatim, driving directions by OSRM.
            </p>
            <p class="mt-2">&copy; {{ now()->year }} {{ config('app.name') }}. Prices in {{ config('app.currency') }}.</p>
        </div>
    </div>
</footer>
