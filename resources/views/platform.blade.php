@extends('layouts.app')

@section('title', 'How OiLand works')
@section('description', 'What customers, branch teams, Officers in Charge, and super admins can do on OiLand.')

@section('content')
    <section class="bg-slate-50 py-12">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold text-brand-700">OiLand platform overview</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Tools for every role</h1>
            <p class="mt-3 max-w-3xl text-slate-600">One station network with different tools for customers, branch teams, and head office.</p>

            <div class="mt-8 grid gap-5 lg:grid-cols-3">
                <x-card title="Customers">
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li>Find nearby stations, opening hours, locations, photos, and available fuel grades.</li>
                        <li>Send a complaint or question, follow replies, and receive email updates.</li>
                        <li>Read published reviews, post a review for moderation, and view current job openings.</li>
                    </ul>
                </x-card>
                <x-card title="Officers in Charge and branch staff">
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li>Review and answer customer inquiries for their assigned station.</li>
                        <li>Receive unread notifications and email alerts for new customer messages.</li>
                        <li>Manage branch availability and submit price changes for approval.</li>
                    </ul>
                </x-card>
                <x-card title="Super admins">
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li>Manage all stations, staff accounts, products, and network announcements.</li>
                        <li>Review price changes and moderate customer reviews across the network.</li>
                        <li>See network-wide notifications, unread counts, and activity history.</li>
                    </ul>
                </x-card>
            </div>
        </div>
    </section>
@endsection
