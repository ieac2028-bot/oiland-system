@extends('layouts.app')

@section('title', 'My profile')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-center gap-4">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-100 text-lg font-bold text-brand-700">
                {{ $user->initials() }}
            </span>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $user->name }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $user->role?->label() }}
                    @if ($user->branch)
                        · {{ $user->branch->name }}
                    @endif
                    · joined {{ $user->created_at->format('F Y') }}
                </p>
            </div>

            <x-badge :tone="$user->role->tone()" class="ml-auto">{{ $user->email }}</x-badge>
        </header>

        <x-card title="Profile details" class="mt-6">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('PATCH')

                <x-form-field name="name" label="Full name" required>
                    <x-input id="name" name="name" :value="old('name', $user->name)" required autocomplete="name" />
                </x-form-field>

                <x-form-field name="email" label="Email address" required
                              hint="Changing your email address means you have to verify it again.">
                    <x-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
                </x-form-field>

                <x-form-field name="phone" label="Mobile number" hint="Optional. Shown to branch staff if you open an inquiry.">
                    <x-input id="phone" name="phone" :value="old('phone', $user->phone)" autocomplete="tel" />
                </x-form-field>

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                    <x-button type="submit">Save changes</x-button>

                    @unless ($user->hasVerifiedEmail())
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <x-button type="submit" variant="secondary">Resend verification email</x-button>
                        </form>
                    @endunless
                </div>
            </form>
        </x-card>

        <x-card title="Password" class="mt-6">
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-form-field name="current_password" label="Current password" required>
                    <x-input id="current_password" name="current_password" type="password" required autocomplete="current-password" />
                </x-form-field>

                <x-form-field name="password" label="New password" required>
                    <x-input id="new_password" name="password" type="password" required autocomplete="new-password" />
                </x-form-field>

                <x-form-field name="password_confirmation" label="Confirm new password" required>
                    <x-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                </x-form-field>

                <div class="border-t border-slate-100 pt-5">
                    <x-button type="submit">Update password</x-button>
                </div>
            </form>
        </x-card>

        @unless ($user->isStaff())
            <x-card title="Delete account" class="mt-6 border-rose-200">
                <p class="text-sm text-slate-600">
                    This permanently removes your account. Your inquiries and reviews are deleted with it, so download
                    anything you need first.
                </p>

                <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4 space-y-3"
                      data-confirm="Really delete your account? This cannot be undone.">
                    @csrf
                    @method('DELETE')

                    <x-form-field name="password" label="Confirm with your password" required>
                        <x-input id="delete_password" name="password" type="password" required autocomplete="current-password" />
                    </x-form-field>

                    <x-button type="submit" variant="danger">Delete my account</x-button>
                </form>
            </x-card>
        @endunless
    </div>
@endsection
