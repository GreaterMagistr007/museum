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

    @if(!empty($siteSettings['schedule.weekdays']) || !empty($siteSettings['schedule.saturday']) || !empty($siteSettings['schedule.sunday']))
    <div class="about-section">
        <h3>Режим работы</h3>
        <table class="schedule-table">
            <thead><tr><th>День недели</th><th>Время работы</th></tr></thead>
            <tbody>
                @if(!empty($siteSettings['schedule.weekdays']))
                    <tr><td>Пн – Пт</td><td>{{ $siteSettings['schedule.weekdays'] }}</td></tr>
                @endif
                @if(!empty($siteSettings['schedule.saturday']))
                    <tr><td>Суббота</td><td>{{ $siteSettings['schedule.saturday'] }}</td></tr>
                @endif
                @if(!empty($siteSettings['schedule.sunday']))
                    <tr><td>Воскресенье</td><td>{{ $siteSettings['schedule.sunday'] }}</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
