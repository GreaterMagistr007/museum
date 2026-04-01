@extends('layouts.app')

@section('title', ($article->meta_title ?: $article->title) . ' — Музей «Иркутское юнкерское училище»')

@if($article->meta_description)
@section('meta_description', $article->meta_description)
@endif

@section('content')
<div class="page">
    {{-- Хлебные крошки: Главная → [Родитель →] Статья --}}
    @php
        $breadcrumbs = [['title' => 'Главная', 'url' => route('home')]];
        if ($article->parent) {
            $breadcrumbs[] = ['title' => $article->parent->title, 'url' => route('article.show', $article->parent)];
        }
        $breadcrumbs[] = ['title' => $article->title, 'url' => null];
    @endphp
    <x-breadcrumbs :items="$breadcrumbs" />

    <h2 class="page__title">{{ $article->title }}</h2>

    @if($article->image_url)
        <div style="margin: 16px 0;">
            <img src="{{ $article->image_url }}" alt="{{ $article->title }}" style="max-width: 100%; height: auto; border-radius: 6px;">
        </div>
    @endif

    <div class="article">
        {!! $article->content !!}
    </div>

    {{-- Дочерние статьи (если есть) --}}
    @if($article->children->isNotEmpty())
        <nav class="article-children" style="margin-top: 32px;">
            <h3 style="margin-bottom: 12px;">В этом разделе</h3>
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px;">
                @foreach($article->children as $child)
                    <li>
                        <a href="{{ route('article.show', $child) }}" style="color: #D4611E; font-weight: 600; text-decoration: none;">
                            {{ $child->title }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    {{-- Ссылка "Вернуться к родителю" для дочерних статей --}}
    @if($article->parent)
        <p style="margin-top: 24px;">
            <a href="{{ route('article.show', $article->parent) }}" style="color: #D4611E; font-weight: 600;">
                ← {{ $article->parent->title }}
            </a>
        </p>
    @endif
</div>
@endsection
