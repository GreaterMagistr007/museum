@extends('layouts.admin')

@section('title', 'Добавить новость')
@section('page-title', 'Добавить новость')

@section('content')
    @include('admin.news._form', ['news' => null])
@endsection
