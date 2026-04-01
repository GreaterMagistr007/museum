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
            @foreach($formations as $rootArticle)
                <a href="{{ route('article.show', $rootArticle) }}" class="formations__item formations__item--main">
                    <span>{!! $rootArticle->short_title ?: e($rootArticle->title) !!}</span>
                    <span class="formations__triangle">►</span>
                </a>
                @foreach($rootArticle->children as $child)
                    <div class="formations__arrow">
                        <svg width="2" height="36" viewBox="0 0 2 36"><line x1="1" y1="0" x2="1" y2="30" stroke="#8B1A1A" stroke-width="2"/><polygon points="0,30 2,30 1,36" fill="#8B1A1A"/></svg>
                    </div>
                    <a href="{{ route('article.show', $child) }}" class="formations__item">
                        {!! $child->short_title ?: e($child->title) !!}
                    </a>
                @endforeach
            @endforeach
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
        @php
            $topExcursions = $excursions->take(3);
            $bottomExcursions = $excursions->slice(3)->take(3);
        @endphp
        <div class="excursions__buttons excursions__buttons--top">
            @foreach($topExcursions as $excursion)
                <a href="{{ route('excursion.show', $excursion) }}" class="excursions__btn">{!! $excursion->short_title ?: e($excursion->title) !!}</a>
            @endforeach
        </div>
        <div class="excursions__image">
            @php
                $buildingImage = $siteSettings['home.building_image'] ?? null;
                $buildingImageUrl = $buildingImage ? Storage::url($buildingImage) : asset('images/anfas.jpg');
            @endphp
            <img src="{{ $buildingImageUrl }}" alt="Здание юнкерского училища" class="excursions__building-img">
        </div>
        <div class="excursions__buttons excursions__buttons--bottom">
            @foreach($bottomExcursions as $excursion)
                <a href="{{ route('excursion.show', $excursion) }}" class="excursions__btn">{!! $excursion->short_title ?: e($excursion->title) !!}</a>
            @endforeach
        </div>
    </div>
</section>
@endsection

@section('modals')
    <x-modals />
@endsection
