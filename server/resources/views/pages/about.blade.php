@extends('layouts.app')

@section('title', 'О музее — Музей «Иркутское юнкерское училище»')

@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'О музее', 'url' => null],
    ]" />
    <h2 class="page__title">О музее</h2>

    @if(!empty($siteSettings['about.history']))
        <div class="about-section">
            <h3>История музея</h3>
            {!! $siteSettings['about.history'] !!}
        </div>
    @endif

    @if(!empty($siteSettings['about.mission']))
        <div class="about-section">
            <h3>Миссия и деятельность</h3>
            {!! $siteSettings['about.mission'] !!}
        </div>
    @endif

    <div class="about-section">
        <h3>Режим работы</h3>
        <table class="schedule-table">
            <thead><tr><th>День недели</th><th>Время работы</th></tr></thead>
            <tbody>
                <tr><td>Пн – Пт</td><td>{{ $siteSettings['schedule.weekdays'] ?? '09:00 – 17:00' }}</td></tr>
                <tr><td>Суббота</td><td>{{ $siteSettings['schedule.saturday'] ?? '10:00 – 15:00' }}</td></tr>
                <tr><td>Воскресенье</td><td>{{ $siteSettings['schedule.sunday'] ?? 'Выходной' }}</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
