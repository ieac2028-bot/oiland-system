@extends('layouts.admin')

@section('title', 'Add a user')
@section('subtitle', 'Staff accounts can be signed into the console; customers can review and follow stations.')

@section('content')
    <nav class="text-sm text-slate-500">
        <a href="{{ route('admin.users.index') }}" class="hover:underline">Users</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">Add a user</span>
    </nav>

    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 max-w-3xl space-y-6">
        @csrf

        <x-card title="Who they are">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form-field name="name" label="Full name" required>
                    <x-input name="name" :value="old('name')" required maxlength="255" autocomplete="off" />
                </x-form-field>

                <x-form-field name="email" label="Email" required>
                    <x-input name="email" type="email" :value="old('email')" required maxlength="255" autocomplete="off" />
                </x-form-field>

                <x-form-field name="phone" label="Phone">
                    <x-input name="phone" :value="old('phone')" maxlength="40" placeholder="+971 50 000 0000" />
                </x-form-field>
            </div>
        </x-card>

        <x-card title="Access" subtitle="Branch roles must be attached to a station.">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form-field name="role" label="Role" required>
                    <x-select name="role" required>
                        <option value="">Choose a role</option>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-form-field>

                <x-form-field name="branch_id" label="Station" hint="Leave blank for head office and customer accounts.">
                    <x-select name="branch_id">
                        <option value="">No station</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) old('branch_id') === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-select>
                </x-form-field>
            </div>
        </x-card>

        <x-card title="Password" subtitle="They can change this from their profile after signing in.">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form-field name="password" label="Password" required hint="At least 8 characters.">
                    <x-input name="password" type="password" required autocomplete="new-password" />
                </x-form-field>

                <x-form-field name="password_confirmation" label="Confirm password" required>
                    <x-input name="password_confirmation" type="password" required autocomplete="new-password" />
                </x-form-field>
            </div>
        </x-card>

        <div class="flex items-center gap-2">
            <x-button type="submit">Create user</x-button>
            <x-button :href="route('admin.users.index')" variant="secondary">Cancel</x-button>
        </div>
    </form>
@endsection
