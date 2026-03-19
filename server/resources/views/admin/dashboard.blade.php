@extends('layouts.admin')

@section('title', 'Панель управления')

@section('content')
<div class="dashboard">
    <div class="dashboard__header">
        <h1 class="dashboard__title">Панель управления</h1>
    </div>

    <div class="dashboard__card">
        <h2 class="dashboard__card-title">Информация о пользователе</h2>
        <div class="dashboard__info">
            <div class="dashboard__info-row">
                <span class="dashboard__info-label">Имя:</span>
                <span class="dashboard__info-value">{{ Auth::user()->name }}</span>
            </div>
            <div class="dashboard__info-row">
                <span class="dashboard__info-label">Email:</span>
                <span class="dashboard__info-value">{{ Auth::user()->email }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="dashboard__logout-btn" type="submit">Выйти</button>
        </form>
    </div>
</div>
@endsection
