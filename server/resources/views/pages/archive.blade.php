@extends('layouts.app')

@section('title', 'Архив — Музей «Иркутское юнкерское училище»')

@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Архив', 'url' => null],
    ]" />
    <h2 class="page__title">Архив</h2>
    <div class="card-list">
        @forelse($items as $item)
        <div class="card">
            @if($item->image_url)
                <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="card__image" loading="lazy">
            @endif
            <div class="card__body">
                <h3 class="card__title">{{ $item->title }}</h3>
                <div class="card__text">{!! $item->description !!}</div>
                @if($item->link_url)
                    <a href="{{ $item->link_url }}" class="card__link">Подробнее &rarr;</a>
                @endif
            </div>
        </div>
        @empty
        <p>Раздел пуст.</p>
        @endforelse
    </div>
</div>
@endsection
