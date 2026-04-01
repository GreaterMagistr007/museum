@extends('layouts.admin')

@section('title', 'Добавить экскурсию')
@section('page-title', 'Добавить экскурсию')

@section('content')
    @include('admin.excursions._form', ['excursion' => null])
@endsection
