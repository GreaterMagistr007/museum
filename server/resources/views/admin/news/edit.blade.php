@extends('layouts.admin')

@section('title', 'Редактировать новость')
@section('page-title', 'Редактировать новость')

@section('content')
    @include('admin.news._form', ['news' => $news])
@endsection
