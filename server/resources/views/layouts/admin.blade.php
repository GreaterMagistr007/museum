<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <title>@yield('title', 'Админ-панель') — Музей</title>
    @stack('head-scripts')
</head>
<body class="admin-body admin-body--panel">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar__header">
            <span class="admin-sidebar__logo">Панель управления</span>
            <button class="admin-sidebar__close" id="sidebarClose" aria-label="Закрыть меню">&times;</button>
        </div>
        <nav class="admin-sidebar__nav">
            <a href="{{ route('admin.dashboard') }}" class="admin-sidebar__link {{ request()->routeIs('admin.dashboard') ? 'admin-sidebar__link--active' : '' }}">Главная</a>
            <a href="{{ route('admin.news.index') }}" class="admin-sidebar__link {{ request()->routeIs('admin.news.*') ? 'admin-sidebar__link--active' : '' }}">Новости</a>
            <a href="{{ route('admin.excursions.index') }}" class="admin-sidebar__link {{ request()->routeIs('admin.excursions.*') ? 'admin-sidebar__link--active' : '' }}">Экскурсии</a>
            <a href="{{ route('admin.articles.index') }}" class="admin-sidebar__link {{ request()->routeIs('admin.articles.*') ? 'admin-sidebar__link--active' : '' }}">Статьи</a>
            <a href="{{ route('admin.catalog.index', 'exposition') }}" class="admin-sidebar__link {{ request()->is('admin/catalog/exposition*') ? 'admin-sidebar__link--active' : '' }}">Экспозиция</a>
            <a href="{{ route('admin.catalog.index', 'archive') }}" class="admin-sidebar__link {{ request()->is('admin/catalog/archive*') ? 'admin-sidebar__link--active' : '' }}">Архив</a>
            <a href="{{ route('admin.settings') }}" class="admin-sidebar__link {{ request()->routeIs('admin.settings*') ? 'admin-sidebar__link--active' : '' }}">Настройки</a>
        </nav>
        <div class="admin-sidebar__footer">
            <div class="admin-sidebar__user">{{ Auth::user()->name }}</div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="admin-sidebar__logout-btn">Выйти</button>
            </form>
        </div>
    </aside>
    <main class="admin-main">
        <div class="admin-topbar">
            <button class="admin-topbar__burger" id="sidebarToggle" aria-label="Открыть меню">
                <span></span><span></span><span></span>
            </button>
            <h1 class="admin-topbar__title">@yield('page-title', 'Панель управления')</h1>
        </div>
        <div class="admin-content">
            @if(session('success'))
                <div class="admin-alert admin-alert--success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="admin-alert admin-alert--error">{{ session('error') }}</div>
            @endif
            @yield('content')
        </div>
    </main>
    <script>
        // Sidebar toggle для мобильных
        document.addEventListener('DOMContentLoaded', function() {
            var sidebar = document.getElementById('adminSidebar');
            var toggle = document.getElementById('sidebarToggle');
            var close = document.getElementById('sidebarClose');
            if (toggle) toggle.addEventListener('click', function() { sidebar.classList.toggle('is-open'); });
            if (close) close.addEventListener('click', function() { sidebar.classList.remove('is-open'); });

            // Автоскрытие алертов
            document.querySelectorAll('.admin-alert').forEach(function(el) {
                setTimeout(function() { el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 300); }, 5000);
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
