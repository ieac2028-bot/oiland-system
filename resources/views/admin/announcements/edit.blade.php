@extends('layouts.admin')

@section('title', 'Edit announcement')
@section('subtitle', $announcement->title)

@section('content')
    <nav class="text-sm text-slate-500">
        <a href="{{ route('admin.announcements.index') }}" class="hover:underline">Announcements</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">{{ $announcement->title }}</span>
    </nav>

    <div class="mt-6 flex flex-wrap items-center gap-2">
        <x-badge :tone="match ($announcement->statusLabel()) {
            'Live' => 'emerald',
            'Scheduled' => 'sky',
            'Expired' => 'slate',
            default => 'amber',
        }">{{ $announcement->statusLabel() }}</x-badge>
        <x-badge tone="slate">{{ $announcement->scope->label() }}</x-badge>
    </div>

    <div class="mt-6 max-w-4xl">
        @include('admin.announcements._form')
    </div>

    <x-card class="mt-8 max-w-4xl" title="Danger zone">
        <p class="text-sm text-slate-600">Deleting an announcement removes it everywhere it was shown.</p>

        <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" class="mt-4"
              onsubmit="return confirm('Delete this announcement? This cannot be undone.');">
            @csrf
            @method('DELETE')

            <x-button type="submit" variant="danger">Delete announcement</x-button>
        </form>
    </x-card>
@endsection
