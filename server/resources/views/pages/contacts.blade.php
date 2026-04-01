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
        <p><strong>Адрес:</strong> {{ $siteSettings['contacts.address'] ?? 'г. Иркутск, ул. Ярослава Гашека, д. 5' }}</p>
        <p><strong>Телефон:</strong> {{ $siteSettings['contacts.phone'] ?? '+7 (3952) XX-XX-XX' }}</p>
        <p><strong>Email:</strong> {{ $siteSettings['contacts.email'] ?? 'museum@example.ru' }}</p>
        <h3 style="margin-top:20px">Режим работы</h3>
        <p>Понедельник – Пятница: {{ $siteSettings['schedule.weekdays'] ?? '09:00 – 17:00' }}</p>
        <p>Суббота: {{ $siteSettings['schedule.saturday'] ?? '10:00 – 15:00' }}</p>
        <p>Воскресенье: {{ $siteSettings['schedule.sunday'] ?? 'выходной' }}</p>
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
