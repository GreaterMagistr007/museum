@extends('layouts.app')

@section('title', 'Экспозиция музея — Музей «Иркутское юнкерское училище»')

@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Экспозиция', 'url' => null],
    ]" />
    <h2 class="page__title">Экспозиция музея</h2>
    <div class="gallery">
        @forelse($items as $item)
        <figure class="gallery__item">
            @if($item->image_url)
                <img src="{{ $item->image_url }}" alt="" class="gallery__image" loading="lazy" data-zoom>
            @endif
            @if(!empty($item->description))
                <figcaption class="gallery__text">{!! nl2br(e(strip_tags((string) $item->description))) !!}</figcaption>
            @endif
        </figure>
        @empty
        <p>Раздел пуст.</p>
        @endforelse
    </div>
</div>
@endsection
