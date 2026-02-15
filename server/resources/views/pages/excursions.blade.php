@extends('layouts.app')

@section('title', 'Экскурсии — Музей «Иркутское юнкерское училище»')

@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Экскурсии', 'url' => null],
    ]" />
    <h2 class="page__title">Экскурсии</h2>
    <p style="margin-bottom:24px">При посещении музея доступны следующие экскурсии. Экскурсии проводятся по предварительной записи для групп от 5 человек.</p>

    <div class="excursion-list">
        <div class="excursion-card">
            <div class="excursion-card__image">Фото</div>
            <div class="excursion-card__body">
                <h3 class="excursion-card__title">Обзорная экскурсия (по всему музею)</h3>
                <p class="excursion-card__text">Знакомство со всеми экспозиционными залами музея: от истории юнкерского училища до современной топографической службы. Посетители получают целостное представление о военной истории здания и военного городка Иркутска за период с 1874 года по настоящее время.</p>
                <a href="{{ route('excursion-overview') }}" class="excursion-card__link">Подробнее</a>
            </div>
        </div>
        <div class="excursion-card">
            <div class="excursion-card__image">Фото</div>
            <div class="excursion-card__body">
                <h3 class="excursion-card__title">Иркутское военное (юнкерское) училище</h3>
                <p class="excursion-card__text">Экскурсия посвящена периоду 1874–1918 годов: создание училища, учебный процесс, быт юнкеров, судьбы выпускников. В экспозиции представлены документы, фотографии, военная форма и личные вещи воспитанников.</p>
                <a href="{{ route('excursion-junker') }}" class="excursion-card__link">Подробнее</a>
            </div>
        </div>
        <div class="excursion-card">
            <div class="excursion-card__image">Фото</div>
            <div class="excursion-card__body">
                <h3 class="excursion-card__title">Наградная система императорской России и СССР</h3>
                <p class="excursion-card__text">Обзор орденов, медалей и знаков отличия двух эпох. Экскурсанты узнают об истории наградной системы, символике и правилах ношения наград.</p>
                <a href="{{ route('excursion-awards') }}" class="excursion-card__link">Подробнее</a>
            </div>
        </div>
        <div class="excursion-card">
            <div class="excursion-card__image">Фото</div>
            <div class="excursion-card__body">
                <h3 class="excursion-card__title">Топографическая служба Армии России от 1812 г. до сегодняшнего дня</h3>
                <p class="excursion-card__text">История военной топографии в России: от создания Корпуса военных топографов в 1812 году до современных технологий космической съёмки.</p>
                <a href="{{ route('excursion-topographic-service') }}" class="excursion-card__link">Подробнее</a>
            </div>
        </div>
        <div class="excursion-card">
            <div class="excursion-card__image">Фото</div>
            <div class="excursion-card__body">
                <h3 class="excursion-card__title">Иркутский топографический отряд от формирования до сегодняшнего дня</h3>
                <p class="excursion-card__text">Локальная история топографического отряда, с 1934 года занимающего здание бывшего юнкерского училища.</p>
                <a href="{{ route('excursion-irkutsk-topographic') }}" class="excursion-card__link">Подробнее</a>
            </div>
        </div>
        <div class="excursion-card">
            <div class="excursion-card__image">Фото</div>
            <div class="excursion-card__body">
                <h3 class="excursion-card__title">Документы и нагрудные (памятные) знаки ушедшей эпохи</h3>
                <p class="excursion-card__text">Специализированная экскурсия по документам, свидетельствам и знакам эпохи Российской империи и раннего Советского периода.</p>
                <a href="{{ route('excursion-documents') }}" class="excursion-card__link">Подробнее</a>
            </div>
        </div>
    </div>
</div>
@endsection
