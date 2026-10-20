@extends('layouts.admin')

@section('title', 'New announcement')
@section('subtitle', 'Publish a notice to the whole network or to selected stations.')

@section('content')
    <nav class="text-sm text-slate-500">
        <a href="{{ route('admin.announcements.index') }}" class="hover:underline">Announcements</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">New</span>
    </nav>

    <div class="mt-6 max-w-4xl">
        @include('admin.announcements._form')
    </div>
@endsection
