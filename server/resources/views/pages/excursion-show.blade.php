@extends('layouts.app')

@section('title', ($excursion->meta_title ?: $excursion->title) . ' — Музей «Иркутское юнкерское училище»')

@if($excursion->meta_description)
@section('meta_description', $excursion->meta_description)
@endif

@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Экскурсии', 'url' => route('excursions')],
        ['title' => $excursion->title, 'url' => null],
    ]" />
    <h2 class="page__title">{{ $excursion->title }}</h2>
    <div class="article">
        @if($excursion->duration)
            <p><strong>Продолжительность:</strong> {{ $excursion->duration }}</p>
        @endif
        <p><strong>Размер группы:</strong> от {{ $excursion->group_size_min }} до {{ $excursion->group_size_max }} человек</p>

        @if($excursion->image_url)
            <div style="margin: 16px 0;">
                <img src="{{ $excursion->image_url }}" alt="{{ $excursion->title }}" style="max-width: 100%; height: auto; border-radius: 6px;">
            </div>
        @endif

        <h3>Описание экскурсии</h3>
        {!! $excursion->description !!}

        @if($excursion->what_you_see)
            <h3>Что вы увидите</h3>
            {!! $excursion->what_you_see !!}
        @endif

        @if($excursion->interesting_facts)
            <h3>Интересные факты</h3>
            {!! $excursion->interesting_facts !!}
        @endif

        <p style="margin-top:24px"><a href="{{ route('excursions') }}" style="color:#D4611E;font-weight:600">← Вернуться к списку экскурсий</a></p>
    </div>
</div>
@endsection
