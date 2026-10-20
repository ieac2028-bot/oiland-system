@extends('layouts.admin')

@section('title', 'Add catalog item')
@section('subtitle', 'Define a fuel or service that branches can price and sell.')

@section('content')
    <form method="POST" action="{{ route('admin.catalog.store') }}">
        @csrf
        @include('admin.catalog._form')
    </form>
@endsection
