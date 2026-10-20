@extends('layouts.app')

@section('title', 'Reset password')

@section('content')
    <div class="mx-auto flex max-w-md flex-col justify-center px-4 py-16">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Choose a new password</h1>

            <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <x-form-field name="email" label="Email address" required>
                    <x-input id="email" name="email" type="email" :value="old('email', $email)" required autofocus />
                </x-form-field>

                <x-form-field name="password" label="New password" required>
                    <x-input id="password" name="password" type="password" required autocomplete="new-password" />
                </x-form-field>

                <x-form-field name="password_confirmation" label="Confirm new password" required>
                    <x-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                </x-form-field>

                <x-button type="submit" class="w-full">Reset password</x-button>
            </form>
        </div>
    </div>
@endsection
