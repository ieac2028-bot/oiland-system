@extends('layouts.admin')

@section('title', 'Edit '.$branch->name)
@section('subtitle', $branch->code.' · '.$branch->fullAddress())

@section('content')
    <nav class="text-sm text-slate-500">
        <a href="{{ route('admin.branches.index') }}" class="hover:underline">Stations</a>
        <span class="mx-2">/</span>
        <a href="{{ route('admin.branches.show', $branch) }}" class="hover:underline">{{ $branch->name }}</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">Edit</span>
    </nav>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Changes go live on the public station page as soon as you save.</p>

        <x-button :href="route('admin.branches.availability.edit', $branch)" variant="secondary">
            Edit product availability
        </x-button>
    </div>

    <div class="mt-6 max-w-4xl">
        @include('admin.branches._form')
    </div>

    <x-card class="mt-8 max-w-4xl" title="Danger zone">
        <p class="text-sm text-slate-600">
            A station can only be deleted while it has no sales history. To close a station that has traded,
            deactivate it instead so the records stay intact.
        </p>

        <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" class="mt-4"
              onsubmit="return confirm('Delete this station permanently? This cannot be undone.');">
            @csrf
            @method('DELETE')

            <x-button type="submit" variant="danger">Delete station</x-button>
        </form>
    </x-card>
@endsection
