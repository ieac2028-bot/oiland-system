@extends('layouts.app')

@section('title', 'Create account')

@section('content')
    <div class="mx-auto flex max-w-md flex-col justify-center px-4 py-16">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Create your account</h1>
            <p class="mt-1 text-sm text-slate-500">
                Register to contact a branch, track replies, follow stations and leave reviews.
            </p>

            <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
                @csrf

                <x-form-field name="name" label="Full name" required>
                    <x-input id="name" name="name" :value="old('name')" required autofocus autocomplete="name" />
                </x-form-field>

                <x-form-field name="email" label="Email address" required>
                    <x-input id="email" name="email" type="email" :value="old('email')" required autocomplete="username" />
                </x-form-field>

                <x-form-field name="phone" label="Mobile number" hint="Optional. Used by the branch if they need to call you back.">
                    <x-input id="phone" name="phone" :value="old('phone')" autocomplete="tel" />
                </x-form-field>

                <x-form-field name="password" label="Password" required>
                    <x-input id="password" name="password" type="password" required autocomplete="new-password" />
                </x-form-field>

                <x-form-field name="password_confirmation" label="Confirm password" required>
                    <x-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                </x-form-field>

                <x-button type="submit" class="w-full">Create account</x-button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Already registered?
                <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Sign in</a>
            </p>
        </div>
    </div>
@endsection
