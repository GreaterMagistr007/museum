@extends('layouts.admin')

@section('title', 'Добавить статью')
@section('page-title', 'Добавить статью')

@section('content')
    @include('admin.articles._form', ['article' => null])
@endsection
