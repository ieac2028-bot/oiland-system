@extends('layouts.app')

@section('title', 'Confirm password')

@section('content')
    <div class="mx-auto flex max-w-md flex-col justify-center px-4 py-16">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Confirm your password</h1>
            <p class="mt-1 text-sm text-slate-500">This is a protected area. Please confirm your password to continue.</p>

            <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
                @csrf

                <x-form-field name="password" label="Password" required>
                    <x-input id="password" name="password" type="password" required autofocus autocomplete="current-password" />
                </x-form-field>

                <x-button type="submit" class="w-full">Confirm</x-button>
            </form>
        </div>
    </div>
@endsection
