@extends('layouts.admin')

@section('title', 'Редактировать экскурсию')
@section('page-title', 'Редактировать экскурсию')

@section('content')
    @include('admin.excursions._form', ['excursion' => $excursion])
@endsection
