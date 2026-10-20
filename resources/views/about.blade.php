@extends('layouts.app')

@section('title', 'About Us')
@section('description', 'Learn about G’Little Trading Incorporated, the history and mission behind Oiland Gasoline Stations, and the company’s commitment to customers and communities.')

@section('content')
    <section class="bg-gradient-to-br from-deep-950 via-deep-900 to-brand-800">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-brand-200">About Us</p>
            <div class="mt-4 grid gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                <div>
                    <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-white sm:text-5xl">Oiland Gasoline Stations</h1>
                    <p class="mt-5 max-w-2xl text-lg leading-8 text-brand-100/90">
                        Fuel Your Journey. Power Your Day. We are part of G’Little Trading Incorporated, a Philippine petroleum company built around dependable products, customer service, and long-term partnerships.
                    </p>
                </div>
                <div class="rounded-2xl border border-white/15 bg-white/10 p-6 text-white backdrop-blur-sm">
                    <p class="text-sm font-medium text-brand-100">Oiland station network</p>
                    <p class="mt-2 text-4xl font-semibold">{{ number_format($branchCount) }}</p>
                    <p class="mt-1 text-sm text-brand-100/90">active locations across {{ number_format($cityCount) }} cities and municipalities</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-14 sm:py-16">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.7fr_1.3fr] lg:px-8">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Our story</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">A family enterprise with a growing reach</h2>
            </div>
            <div class="space-y-5 text-base leading-7 text-slate-600">
                <p>
                    G’Little Trading Incorporated traces its roots to a small family enterprise that grew through hard work, trusted relationships, and service to customers. The company was registered with the Securities and Exchange Commission on December 27, 1999, under its former name, O’Relyn Trading Inc.
                </p>
                <p>
                    In May 2000, the company began its petroleum marketing and distribution operations. Its work has since spanned marketing, trading, wholesale distribution, and retail-related activities. Oiland Gasoline Stations is the company’s retail network, bringing fuel access closer to motorists and communities across Luzon.
                </p>
                <p>
                    Today, Oiland stations offer Diesel, Premium, and Green. Customers can use the station directory to check branch locations, operating hours, and available fuels before they travel.
                </p>
                <div class="pt-1">
                    <a href="{{ route('branches.index') }}" class="inline-flex items-center gap-2 font-semibold text-brand-700 hover:underline">
                        Find an Oiland station <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-14 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Our direction</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">What guides our work</h2>
            </div>

            <div class="mt-8 grid gap-5 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Vision</p>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">A leading, customer-driven company</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">To lead through consistent competence in the industry, in partnership with our people and clients.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Mission</p>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">Competence and leadership</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">To maintain competence and leadership in our industry and contribute to the economic growth and development of the Philippines.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Objective</p>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">Earn trust through customer satisfaction</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">To meet customer requirements with care for cost, build trust, and form lasting partnerships for growth.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="bg-white py-14 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Our values</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">How we work with people</h2>
                <p class="mt-3 text-base leading-7 text-slate-600">These values shape how we serve customers, work with colleagues, and build partnerships.</p>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['Leadership', 'Lead with direction, responsibility, and purpose.'],
                    ['Integrity', 'Uphold honesty and ethical conduct in our work.'],
                    ['Accountability', 'Take ownership of responsibilities and results.'],
                    ['Passion', 'Bring commitment and enthusiasm to what we do.'],
                    ['Diversity', 'Value different people, perspectives, and contributions.'],
                    ['Quality', 'Strive for dependable products, service, and performance.'],
                ] as [$value, $description])
                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                        <h3 class="font-semibold text-slate-900">{{ $value }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-gradient-to-r from-brand-800 to-deep-900 py-14 text-white sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-brand-200">At our stations</p>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight">A dependable stop along the way</h2>
                    <p class="mt-4 leading-7 text-brand-100/90">Our station service commitment is to make every visit safe, convenient, and helpful for the communities we serve.</p>
                    <a href="{{ route('branches.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-brand-800 transition hover:bg-brand-50">
                        Explore station locations <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        'Quality fuels for a smoother ride',
                        'A safe and reliable fueling environment',
                        'Excellent and courteous customer service',
                        'Convenient locations serving local communities',
                    ] as $commitment)
                        <div class="flex items-start gap-3 rounded-xl border border-white/15 bg-white/10 p-4">
                            <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-300 text-deep-950" aria-hidden="true">✓</span>
                            <p class="text-sm font-medium leading-6">{{ $commitment }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-14 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Our people and resources</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">The team and capabilities behind the network</h2>
                <p class="mt-3 text-base leading-7 text-slate-600">The company profile describes a team and facilities supporting station operations, wholesale customers, and petroleum distribution.</p>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-3xl font-semibold text-slate-900">60</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">employees across management and staff</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-3xl font-semibold text-slate-900">2,000–30,000 L</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">tanker truck capacities listed in the company profile</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-3xl font-semibold text-slate-900">200,000 L</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">maximum stated petroleum storage capacity</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-3xl font-semibold text-slate-900">1,400 m²</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">company compound area stated in the profile</p>
                </article>
            </div>

            <div class="mt-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-900">Head office</h3>
                <address class="mt-2 text-sm not-italic leading-6 text-slate-600">
                    438 C3 Road, Kaunlaran Village, Dagat-dagatan,<br>
                    Caloocan City, Metro Manila, Philippines
                </address>
            </div>
        </div>
    </section>

    <section class="bg-white py-12">
        <div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Find an Oiland station near you</h2>
                <p class="mt-1 text-sm text-slate-600">Browse locations, check opening hours, and see the fuels each branch offers.</p>
            </div>
            <x-button :href="route('branches.index')" variant="accent">Find a station</x-button>
        </div>
    </section>
@endsection
