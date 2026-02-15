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
        <p><strong>Адрес:</strong> г. Иркутск, ул. Ярослава Гашека, д. 5</p>
        <p><strong>Телефон:</strong> +7 (3952) XX-XX-XX</p>
        <p><strong>Email:</strong> museum@example.ru</p>
        <h3 style="margin-top:20px">Режим работы</h3>
        <p>Понедельник – Пятница: 09:00 – 17:00</p>
        <p>Суббота: 10:00 – 15:00</p>
        <p>Воскресенье: выходной</p>
        <p><em>Экскурсии проводятся по предварительной записи</em></p>
    </div>
    <div class="contact-map">
        <script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A0882d0472dd33f77a1ebbf43d7c3768c4479d7029e56e57ce49536c1530ff6df&amp;width=100%25&amp;height=720&amp;lang=ru_RU&amp;scroll=true"></script>
    </div>
</div>
@endsection
