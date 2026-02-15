@extends('layouts.app')

@section('title', 'Экспозиция музея — Музей «Иркутское юнкерское училище»')

@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Экспозиция', 'url' => null],
    ]" />
    <h2 class="page__title">Экспозиция музея</h2>
    <div class="card-list">
        <div class="card">
            <div class="card__image">Фото экспозиции</div>
            <div class="card__body">
                <h3 class="card__title">Иркутское юнкерское училище (1874–1918 гг.)</h3>
                <p class="card__text">Основная экспозиция, рассказывающая об истории создания и деятельности первого военного учебного заведения в Восточной Сибири. Учебные программы, быт юнкеров, судьбы выпускников.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
        <div class="card">
            <div class="card__image">Фото экспозиции</div>
            <div class="card__body">
                <h3 class="card__title">Военные награды и знаки отличия</h3>
                <p class="card__text">Коллекция орденов, медалей и знаков Российской империи и РСФСР. Награды выпускников училища и курсантов пехотных курсов. Георгиевские кресты, ордена Святой Анны и Станислава.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
        <div class="card">
            <div class="card__image">Фото экспозиции</div>
            <div class="card__body">
                <h3 class="card__title">Топографическая служба в Иркутске</h3>
                <p class="card__text">История топографического отряда с 1934 года по настоящее время. Картографические инструменты, аэрофотосъёмочное оборудование, образцы карт и планов города Иркутска.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
        <div class="card">
            <div class="card__image">Фото экспозиции</div>
            <div class="card__body">
                <h3 class="card__title">Оружие и снаряжение русской армии</h3>
                <p class="card__text">Образцы стрелкового и холодного оружия конца XIX — начала XX века. Винтовки Мосина, револьверы, сабли и шашки. Элементы солдатского и офицерского снаряжения.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
        <div class="card">
            <div class="card__image">Фото экспозиции</div>
            <div class="card__body">
                <h3 class="card__title">Военная форма и обмундирование</h3>
                <p class="card__text">Униформа юнкеров, офицеров русской армии и красноармейцев. Мундиры, шинели, головные уборы, погоны и знаки различия. Эволюция военной одежды с 1870-х по 1930-е годы.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
        <div class="card">
            <div class="card__image">Фото экспозиции</div>
            <div class="card__body">
                <h3 class="card__title">Пехотные курсы РККА (1920–1933 гг.)</h3>
                <p class="card__text">Раздел экспозиции, посвящённый Иркутским пехотным курсам командиров Красной армии. Учебные материалы, документы, личные вещи курсантов и преподавателей.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
    </div>
</div>
@endsection
