<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Музей «Иркутское юнкерское училище»')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body data-page="{{ request()->route() ? request()->route()->getName() : 'home' }}">
    <x-header />

    <main class="main">
        @yield('content')
    </main>

    <x-footer />

    @yield('modals')

    <script src="{{ asset('js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>
