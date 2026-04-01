@extends('layouts.admin')

@section('title', 'Панель управления')
@section('page-title', 'Главная')

@section('content')
<div class="dashboard-stats">
    <a href="{{ route('admin.news.index') }}" class="dashboard-stats__card">
        <span class="dashboard-stats__count">{{ $stats['news'] }}</span>
        <span class="dashboard-stats__label">Новости</span>
    </a>
    <a href="{{ route('admin.excursions.index') }}" class="dashboard-stats__card">
        <span class="dashboard-stats__count">{{ $stats['excursions'] }}</span>
        <span class="dashboard-stats__label">Экскурсии</span>
    </a>
    <a href="{{ route('admin.articles.index') }}" class="dashboard-stats__card">
        <span class="dashboard-stats__count">{{ $stats['articles'] }}</span>
        <span class="dashboard-stats__label">Статьи</span>
    </a>
    <a href="{{ route('admin.catalog.index', 'exposition') }}" class="dashboard-stats__card">
        <span class="dashboard-stats__count">{{ $stats['exposition'] }}</span>
        <span class="dashboard-stats__label">Экспозиция</span>
    </a>
    <a href="{{ route('admin.catalog.index', 'archive') }}" class="dashboard-stats__card">
        <span class="dashboard-stats__count">{{ $stats['archive'] }}</span>
        <span class="dashboard-stats__label">Архив</span>
    </a>
</div>

<div class="admin-card">
    <h2 class="admin-card__title">Последние новости</h2>
    @if($latestNews->isEmpty())
        <p style="color: #999;">Новостей пока нет.</p>
    @else
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Заголовок</th>
                    <th>Дата</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                @foreach($latestNews as $item)
                <tr>
                    <td><a href="{{ route('admin.news.edit', $item) }}" style="color: var(--color-primary); font-weight: 600;">{{ Str::limit($item->title, 60) }}</a></td>
                    <td>{{ $item->formatted_date }}</td>
                    <td>
                        @if($item->is_published)
                            <span style="color: #2e7d32;">Опубликовано</span>
                        @else
                            <span style="color: #999;">Черновик</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="dashboard-grid">
    <a href="{{ route('admin.settings') }}" class="dashboard-grid__link">
        <div class="admin-card">
            <h2 class="admin-card__title">Настройки сайта</h2>
            <p>Контакты, расписание, общие настройки</p>
        </div>
    </a>
</div>

<div class="admin-card">
    <h2 class="admin-card__title">Информация о пользователе</h2>
    <div class="dashboard__info">
        <div class="dashboard__info-row">
            <span class="dashboard__info-label">Имя:</span>
            <span class="dashboard__info-value">{{ Auth::user()->name }}</span>
        </div>
        <div class="dashboard__info-row">
            <span class="dashboard__info-label">Email:</span>
            <span class="dashboard__info-value">{{ Auth::user()->email }}</span>
        </div>
    </div>
</div>
@endsection
