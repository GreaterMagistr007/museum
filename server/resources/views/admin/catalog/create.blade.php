@extends('layouts.admin')

@section('title', 'Добавить ' . $labels['singular'])
@section('page-title', 'Добавить ' . $labels['singular'])

@section('content')
    @include('admin.catalog._form', ['catalogItem' => null])
@endsection
