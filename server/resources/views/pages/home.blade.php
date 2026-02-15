@extends('layouts.app')

@section('title', 'Музей «Иркутское юнкерское училище»')

@section('content')
<section class="content">
    <aside class="sidebar sidebar--left">
        <button class="sidebar__btn" data-modal="about">О музее</button>
        <button class="sidebar__btn" data-modal="schedule">Режим работы</button>
        <button class="sidebar__btn" data-modal="location">Как нас найти</button>
        <button class="sidebar__btn" data-modal="contacts">Контакты</button>
    </aside>

    <div class="formations">
        <button class="formations__accordion-btn" id="formationsToggle" aria-expanded="false">
            Воинские формирования
            <span class="formations__accordion-icon">▾</span>
        </button>
        <div class="formations__tree" id="formationsTree">
            <a href="{{ route('military-town') }}" class="formations__item formations__item--main">
                <span>Воинские формирования, занимавшие здания военного городка (1882&nbsp;г.&nbsp;–&nbsp;н.&nbsp;в.)</span>
                <span class="formations__triangle">►</span>
            </a>
            <div class="formations__arrow">
                <svg width="2" height="36" viewBox="0 0 2 36"><line x1="1" y1="0" x2="1" y2="30" stroke="#8B1A1A" stroke-width="2"/><polygon points="0,30 2,30 1,36" fill="#8B1A1A"/></svg>
            </div>
            <a href="{{ route('junker-school') }}" class="formations__item">
                Иркутское юнкерское (военное) училище<br>(1874 – 1918&nbsp;гг.)
            </a>
            <div class="formations__arrow">
                <svg width="2" height="36" viewBox="0 0 2 36"><line x1="1" y1="0" x2="1" y2="30" stroke="#8B1A1A" stroke-width="2"/><polygon points="0,30 2,30 1,36" fill="#8B1A1A"/></svg>
            </div>
            <a href="{{ route('infantry-courses') }}" class="formations__item">
                Пехотные курсы командиров РККА<br>(1920 – 1933&nbsp;гг.)
            </a>
            <div class="formations__arrow">
                <svg width="2" height="36" viewBox="0 0 2 36"><line x1="1" y1="0" x2="1" y2="30" stroke="#8B1A1A" stroke-width="2"/><polygon points="0,30 2,30 1,36" fill="#8B1A1A"/></svg>
            </div>
            <a href="{{ route('topographic-unit') }}" class="formations__item">
                Топографический отряд<br>(1934&nbsp;г.&nbsp;–&nbsp;н.&nbsp;в.)
            </a>
        </div>
    </div>

    <aside class="sidebar sidebar--right">
        <a href="{{ route('exposition') }}" class="sidebar__btn">Экспозиция музея</a>
        <a href="{{ route('news') }}" class="sidebar__btn">Новости</a>
        <a href="{{ route('archive') }}" class="sidebar__btn">Архив</a>
    </aside>
</section>

<section class="excursions">
    <div class="excursions__header">
        <h2 class="excursions__title">При посещении музея доступны экскурсии</h2>
    </div>
    <div class="excursions__grid">
        <div class="excursions__buttons excursions__buttons--top">
            <a href="{{ route('excursion-overview') }}" class="excursions__btn">Обзорная<br>(по всему музею)</a>
            <a href="{{ route('excursion-junker') }}" class="excursions__btn">Иркутское военное (юнкерское) училище</a>
            <a href="{{ route('excursion-awards') }}" class="excursions__btn">Наградная система императорской России и&nbsp;СССР</a>
        </div>
        <div class="excursions__image">
            <img src="{{ asset('images/anfas.jpg') }}" alt="Здание юнкерского училища" class="excursions__building-img">
        </div>
        <div class="excursions__buttons excursions__buttons--bottom">
            <a href="{{ route('excursion-topographic-service') }}" class="excursions__btn">Топографическая служба Армии России от 1812&nbsp;г. до сегодняшнего дня</a>
            <a href="{{ route('excursion-irkutsk-topographic') }}" class="excursions__btn">Иркутский топографический отряд от формирования до сегодняшнего дня</a>
            <a href="{{ route('excursion-documents') }}" class="excursions__btn">Документы и нагрудные (памятные) знаки ушедшей эпохи</a>
        </div>
    </div>
</section>
@endsection

@section('modals')
    <x-modals />
@endsection
