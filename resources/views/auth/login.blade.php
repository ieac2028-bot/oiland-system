@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <div class="mx-auto flex max-w-md flex-col justify-center px-4 py-16">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Sign in</h1>
            <p class="mt-1 text-sm text-slate-500">Use your customer or staff account.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf

                <x-form-field name="email" label="Email address" required>
                    <x-input id="email" name="email" type="email" :value="old('email')" class="h-12 px-4 text-base" required autofocus autocomplete="username" />
                </x-form-field>

                <x-form-field name="password" label="Password" required>
                    <div class="relative">
                        <x-input id="password" name="password" type="password" class="h-12 px-4 pr-12 text-base" required autocomplete="current-password" />
                        <button id="toggle-password" type="button" class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-500 transition hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600" aria-label="Show password" aria-pressed="false" aria-controls="password">
                            <svg data-eye-icon xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-7 9.75-7 9.75 7 9.75 7-3.5 7-9.75 7-9.75-7-9.75-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg data-eye-off-icon xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" hidden><path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.5 10.5 0 0 1 12 5c6.25 0 9.75 7 9.75 7a16.4 16.4 0 0 1-3.06 3.8M6.23 6.23C3.66 7.92 2.25 12 2.25 12s3.5 7 9.75 7a10.6 10.6 0 0 0 4.09-.82"/></svg>
                        </button>
                    </div>
                </x-form-field>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Remember me
                </label>

                <x-button type="submit" class="w-full">Sign in</x-button>
            </form>

            <div class="mt-6 flex items-center justify-between text-sm">
                <a href="{{ route('password.request') }}" class="text-brand-700 hover:underline">Forgot your password?</a>
                <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">Create an account</a>
            </div>
        </div>
    </div>
    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('toggle-password');

        passwordToggle.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            passwordToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            passwordToggle.setAttribute('aria-pressed', String(isHidden));
            passwordToggle.querySelector('[data-eye-icon]').hidden = isHidden;
            passwordToggle.querySelector('[data-eye-off-icon]').hidden = !isHidden;
        });
    </script>
@endsection
