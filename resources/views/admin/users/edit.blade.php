@extends('layouts.admin')

@section('title', 'Edit '.$managedUser->name)
@section('subtitle', $managedUser->email)

@section('content')
    <nav class="text-sm text-slate-500">
        <a href="{{ route('admin.users.index') }}" class="hover:underline">Users</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">{{ $managedUser->name }}</span>
    </nav>

    <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="mt-6 max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <x-card title="Who they are">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form-field name="name" label="Full name" required>
                    <x-input name="name" :value="old('name', $managedUser->name)" required maxlength="255" />
                </x-form-field>

                <x-form-field name="email" label="Email" hint="The email address cannot be changed here.">
                    <x-input :value="$managedUser->email" disabled />
                </x-form-field>

                <x-form-field name="phone" label="Phone">
                    <x-input name="phone" :value="old('phone', $managedUser->phone)" maxlength="40" />
                </x-form-field>
            </div>
        </x-card>

        <x-card title="Access" :subtitle="$canManage ? 'You can change this role and station.' : 'Only head office can change this role and station.'">
            @if ($canManage)
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form-field name="role" label="Role" required>
                        <x-select name="role" required>
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', $managedUser->role->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </x-form-field>

                    <x-form-field name="branch_id" label="Station" hint="Leave blank for head office and customer accounts.">
                        <x-select name="branch_id">
                            <option value="">No station</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $managedUser->branch_id) === $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </x-select>
                    </x-form-field>
                </div>
            @else
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge :tone="$managedUser->role->tone()">{{ $managedUser->role->label() }}</x-badge>
                    @if ($managedUser->branch)
                        <x-badge tone="slate">{{ $managedUser->branch->name }}</x-badge>
                    @endif
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    These fields are hidden so an Officer in Charge cannot escalate their own access.
                </p>
            @endif
        </x-card>

        <div class="flex items-center gap-2">
            <x-button type="submit">Save changes</x-button>
            <x-button :href="route('admin.users.index')" variant="secondary">Cancel</x-button>
        </div>
    </form>

    @can('delete', $managedUser)
        <x-card class="mt-8 max-w-3xl" title="Danger zone">
            <p class="text-sm text-slate-600">
                Deleting a user removes their access permanently. Sales, prices, and tickets they recorded are kept.
            </p>

            <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}" class="mt-4"
                  onsubmit="return confirm('Delete this user account? This cannot be undone.');">
                @csrf
                @method('DELETE')

                <x-button type="submit" variant="danger">Delete user</x-button>
            </form>
        </x-card>
    @endcan
@endsection
