@extends('layouts.admin')

@section('title', 'Редактировать ' . $labels['singular'])
@section('page-title', 'Редактировать ' . $labels['singular'])

@section('content')
    @include('admin.catalog._form', ['catalogItem' => $catalogItem])
@endsection
