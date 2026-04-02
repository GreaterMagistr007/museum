@extends('layouts.app')

@section('title', 'Контакты — Музей «Иркутское юнкерское училище»')

@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Контакты', 'url' => null],
    ]" />
    <h2 class="page__title">Контакты</h2>

    <div class="contact-info">
        <h3>Контактная информация</h3>
        @if(!empty($siteSettings['contacts.address']))
            <p><strong>Адрес:</strong> {{ $siteSettings['contacts.address'] }}</p>
        @endif
        @if(!empty($siteSettings['contacts.phone']))
            <p><strong>Телефон:</strong> {{ $siteSettings['contacts.phone'] }}</p>
        @endif
        @if(!empty($siteSettings['contacts.email']))
            <p><strong>Email:</strong> {{ $siteSettings['contacts.email'] }}</p>
        @endif
        <h3 style="margin-top:20px">Режим работы</h3>
        @if(!empty($siteSettings['schedule.weekdays']))
            <p>Понедельник – Пятница: {{ $siteSettings['schedule.weekdays'] }}</p>
        @endif
        @if(!empty($siteSettings['schedule.saturday']))
            <p>Суббота: {{ $siteSettings['schedule.saturday'] }}</p>
        @endif
        @if(!empty($siteSettings['schedule.sunday']))
            <p>Воскресенье: {{ $siteSettings['schedule.sunday'] }}</p>
        @endif
        @if(!empty($siteSettings['schedule.note']))
            <p><em>{{ $siteSettings['schedule.note'] }}</em></p>
        @else
            <p><em>Экскурсии проводятся по предварительной записи</em></p>
        @endif
    </div>
    @if(!empty($siteSettings['contacts.map_id']))
    <div class="contact-map">
        <script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A{{ $siteSettings['contacts.map_id'] }}&amp;width=100%25&amp;height=720&amp;lang=ru_RU&amp;scroll=true"></script>
    </div>
    @endif
</div>
@endsection
