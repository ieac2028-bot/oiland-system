@extends('layouts.admin')

@section('title', 'Add a station')
@section('subtitle', 'Create a station so you can publish prices and accept sales against it.')

@section('content')
    <nav class="text-sm text-slate-500">
        <a href="{{ route('admin.branches.index') }}" class="hover:underline">Stations</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">Add a station</span>
    </nav>

    <div class="mt-6 max-w-4xl">
        @include('admin.branches._form')
    </div>
@endsection
