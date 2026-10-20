@extends('layouts.admin')

@section('title', 'Edit ' . $item->displayName())
@section('subtitle', 'SKU ' . ($item->sku ?? 'not set') . ' · ' . $item->branches()->count() . ' branch(es) sell this')

@section('content')
    <form method="POST" action="{{ route('admin.catalog.update', $item) }}">
        @csrf
        @method('PUT')
        @include('admin.catalog._form')
    </form>
@endsection
