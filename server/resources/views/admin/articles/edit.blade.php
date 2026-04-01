@extends('layouts.admin')

@section('title', 'Редактировать статью')
@section('page-title', 'Редактировать: {{ $article->title }}')

@section('content')
    @include('admin.articles._form', ['article' => $article])
@endsection
