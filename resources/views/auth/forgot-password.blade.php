@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
    <div class="mx-auto flex max-w-md flex-col justify-center px-4 py-16">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Forgot your password?</h1>
            <p class="mt-1 text-sm text-slate-500">We will email you a link to choose a new one.</p>

            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
                @csrf

                <x-form-field name="email" label="Email address" required>
                    <x-input id="email" name="email" type="email" :value="old('email')" required autofocus />
                </x-form-field>

                <x-button type="submit" class="w-full">Email reset link</x-button>
            </form>

            <p class="mt-6 text-center text-sm">
                <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Back to sign in</a>
            </p>
        </div>
    </div>
@endsection
