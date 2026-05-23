@extends('layouts.app')

@section('title', 'Экскурсии — Музей «Иркутское юнкерское училище»')

@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Экскурсии', 'url' => null],
    ]" />
    <h2 class="page__title">Экскурсии</h2>
    <p style="margin-bottom:24px">При посещении музея доступны следующие экскурсии. Экскурсии проводятся по предварительной записи для групп от 10 человек.</p>

    <div class="excursion-list">
        @forelse($excursions as $excursion)
        <div class="excursion-card">
            @if($excursion->image_url)
            <div class="excursion-card__image">
                <img src="{{ $excursion->image_url }}" alt="{{ $excursion->title }}" style="width:100%;height:100%;object-fit:cover;">
            </div>
            @endif
            <div class="excursion-card__body">
                <h3 class="excursion-card__title">{{ $excursion->title }}</h3>
                <p class="excursion-card__text">{{ $excursion->short_description }}</p>
                <a href="{{ route('excursion.show', $excursion) }}" class="excursion-card__link">Подробнее</a>
            </div>
        </div>
        @empty
        <p>Экскурсий пока нет.</p>
        @endforelse
    </div>
</div>
@endsection
