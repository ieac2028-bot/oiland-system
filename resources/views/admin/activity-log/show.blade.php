@extends('layouts.admin')

@section('title', 'Activity entry')
@section('subtitle', $log->created_at->format('d M Y \a\t H:i'))

@section('content')
    <nav class="text-sm text-slate-500">
        <a href="{{ route('admin.activity-log.index') }}" class="hover:underline">Activity log</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">Entry #{{ $log->id }}</span>
    </nav>

    <x-card class="mt-6" padding="p-0">
        <div class="border-b border-slate-200 px-6 py-5">
            <div class="flex flex-wrap items-center gap-2">
                <x-badge :tone="match (Str::before($log->action, '.')) {
                    'created', 'published', 'approved' => 'emerald',
                    'updated', 'imported', 'recorded' => 'sky',
                    'deleted', 'rejected', 'hidden' => 'rose',
                    'requested', 'flagged', 'assigned', 'changed' => 'amber',
                    default => 'slate',
                }">{{ $actions[$log->action] ?? Str::headline($log->action) }}</x-badge>
                <x-badge tone="slate">{{ $log->subjectLabel() }}</x-badge>
            </div>

            <h1 class="mt-2 text-lg font-semibold text-slate-900">
                {{ $log->description ?: 'No description recorded' }}
            </h1>
            <p class="mt-1 text-xs text-slate-500">
                {{ $log->user_name }}<span class="text-slate-400"> · </span>{{ $log->created_at->format('d M Y H:i:s') }}
            </p>
        </div>

        <dl class="grid gap-px bg-slate-200 sm:grid-cols-2">
            <div class="bg-white px-6 py-4">
                <dt class="text-xs uppercase tracking-wide text-slate-400">Action key</dt>
                <dd class="mt-1 font-mono text-xs text-slate-700">{{ $log->action }}</dd>
            </div>

            <div class="bg-white px-6 py-4">
                <dt class="text-xs uppercase tracking-wide text-slate-400">Station</dt>
                <dd class="mt-1 text-sm text-slate-700">
                    @if ($log->branch)
                        <a href="{{ route('admin.branches.show', $log->branch) }}" class="hover:text-brand-700 hover:underline">
                            {{ $log->branch->name }}
                        </a>
                    @else
                        Network-wide
                    @endif
                </dd>
            </div>

            <div class="bg-white px-6 py-4">
                <dt class="text-xs uppercase tracking-wide text-slate-400">Person</dt>
                <dd class="mt-1 text-sm text-slate-700">
                    {{ $log->user?->name ?? $log->user_name }}
                    @if ($log->user_role)
                        <span class="text-xs text-slate-500">· {{ Str::headline($log->user_role) }}</span>
                    @endif
                </dd>
            </div>

            <div class="bg-white px-6 py-4">
                <dt class="text-xs uppercase tracking-wide text-slate-400">IP address</dt>
                <dd class="mt-1 font-mono text-xs text-slate-700">{{ $log->ip_address ?? 'Not recorded' }}</dd>
            </div>

            <div class="bg-white px-6 py-4 sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-slate-400">Browser</dt>
                <dd class="mt-1 break-words text-xs text-slate-600">{{ $log->user_agent ?? 'Not recorded' }}</dd>
            </div>
        </dl>
    </x-card>

    <x-card class="mt-6" title="Recorded details" subtitle="The extra context the action carried at the time.">
        @if (blank($log->properties))
            <p class="text-sm text-slate-500">No additional details were recorded for this action.</p>
        @else
            <dl class="divide-y divide-slate-100">
                @foreach ($log->properties as $key => $value)
                    <div class="grid gap-1 py-2.5 sm:grid-cols-[14rem_1fr] sm:gap-4">
                        <dt class="text-sm font-medium text-slate-700">{{ Str::headline((string) $key) }}</dt>
                        <dd class="text-sm text-slate-600">
                            @if (is_array($value))
                                @if (array_is_list($value))
                                    @if (filled($value))
                                        <ul class="space-y-0.5">
                                            @foreach ($value as $item)
                                                <li>{{ is_scalar($item) ? $item : json_encode($item) }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-slate-400">None</span>
                                    @endif
                                @else
                                    <dl class="space-y-0.5">
                                        @foreach ($value as $subKey => $subValue)
                                            <div class="flex gap-2">
                                                <dt class="text-slate-500">{{ Str::headline((string) $subKey) }}:</dt>
                                                <dd>{{ is_scalar($subValue) ? $subValue : json_encode($subValue) }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                            @elseif (is_bool($value))
                                {{ $value ? 'Yes' : 'No' }}
                            @elseif (is_scalar($value))
                                {{ $value }}
                            @else
                                <span class="font-mono text-xs">{{ json_encode($value) }}</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </x-card>
@endsection
