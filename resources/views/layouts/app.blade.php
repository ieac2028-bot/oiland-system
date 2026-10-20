<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Find fuel stations, live prices, services and offers across the network.')">

    @include('layouts.partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="site-wallpaper flex min-h-full flex-col text-slate-800 antialiased">
    @include('layouts.partials.navbar')

    <main class="flex-1">
        @include('layouts.partials.flash')

        @if (request()->routeIs('timekeeping.*') && auth()->user()?->canManageTimekeeping())
            @include('timekeeping.partials.navigation')
        @endif

        @yield('content')
    </main>

    @include('layouts.partials.footer')

    @stack('modals')
    @stack('scripts')
</body>
</html>
