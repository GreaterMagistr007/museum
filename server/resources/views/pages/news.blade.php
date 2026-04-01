@extends('layouts.app')

@section('title', 'Новости — Музей «Иркутское юнкерское училище»')

@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Новости', 'url' => null],
    ]" />
    <h2 class="page__title">Новости</h2>
    <div class="news-list">
        @forelse($news as $item)
        <article class="news-card">
            <div class="news-card__date">{{ $item->formatted_date }}</div>
            <h3 class="news-card__title">{{ $item->title }}</h3>
            <p class="news-card__text">{{ $item->text }}</p>
            @if($item->image_url)
                <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="news-card__image" loading="lazy">
            @endif
        </article>
        @empty
        <p>Новостей пока нет.</p>
        @endforelse
    </div>

    {{ $news->links() }}
</div>
@endsection
