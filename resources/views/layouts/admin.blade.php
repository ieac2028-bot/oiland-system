<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · {{ config('app.name') }} Admin</title>

    @include('layouts.partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="site-wallpaper flex min-h-full flex-col text-slate-800 antialiased">
    @include('layouts.partials.navbar')
    @include('layouts.partials.admin-sidebar')

    <div class="flex min-w-0 flex-1 flex-col lg:pl-72">
        <header class="sticky top-[100px] z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="flex h-16 items-center gap-4 px-4 sm:px-6">
                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" data-sidebar-toggle>
                    <span class="sr-only">Toggle navigation</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-lg font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                    @isset($subtitle)
                        <p class="truncate text-sm text-slate-500">{{ $subtitle }}</p>
                    @endisset
                </div>

            </div>

            @include('layouts.partials.flash')
        </header>

        <main class="flex-1 px-4 py-6 sm:px-6">
            @yield('content')
        </main>
    </div>

    @stack('modals')
    @stack('scripts')
</body>
</html>
