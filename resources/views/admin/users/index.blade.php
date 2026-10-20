@extends('layouts.admin')

@section('title', 'Users')
@section('subtitle', $users->total().' account(s)')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid flex-1 gap-3 sm:grid-cols-2 lg:max-w-2xl lg:grid-cols-3">
            <x-form-field name="q" label="Search">
                <x-input name="q" :value="$filters['q'] ?? null" placeholder="Name or email" />
            </x-form-field>

            <x-form-field name="role" label="Role">
                <x-select name="role">
                    <option value="">All roles</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['role'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-form-field>

            <div class="flex items-end gap-2">
                <x-button type="submit">Filter</x-button>
                @if (array_filter($filters))
                    <x-button :href="route('admin.users.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>

        @can('create', App\Models\User::class)
            <x-button :href="route('admin.users.create')">Add a user</x-button>
        @endcan
    </div>

    <x-card class="mt-6" padding="p-0">
        @if ($users->isEmpty())
            <div class="p-6">
                <x-empty-state title="No users" description="Nothing matches these filters." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3 font-medium">Name</th>
                            <th class="px-6 py-3 font-medium">Role</th>
                            <th class="px-6 py-3 font-medium">Station</th>
                            <th class="px-6 py-3 font-medium">Verified</th>
                            <th class="px-6 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $row)
                            <tr>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600">
                                            {{ Str::upper(Str::substr($row->name, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-800">
                                                {{ $row->name }}
                                                @if ($row->id === auth()->id())
                                                    <span class="text-xs font-normal text-slate-400">(you)</span>
                                                @endif
                                            </p>
                                            <p class="truncate text-xs text-slate-500">{{ $row->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <x-badge :tone="$row->role->tone()">{{ $row->role->label() }}</x-badge>
                                </td>
                                <td class="px-6 py-3 text-slate-600">
                                    {{ $row->branch?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-3">
                                    <x-badge :tone="$row->email_verified_at ? 'emerald' : 'amber'">
                                        {{ $row->email_verified_at ? 'Verified' : 'Unverified' }}
                                    </x-badge>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($row->id !== auth()->id())
                                            <a href="{{ route('admin.users.edit', $row) }}"
                                               class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Edit</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-slate-200 px-6 py-3">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </x-card>

    @unless ($canManage)
        <p class="mt-4 text-xs text-slate-500">
            You are seeing colleagues on your own station. Only head office can change a role or move someone.
        </p>
    @endunless
@endsection
