@extends('layouts.admin-auth')

@section('title', 'Вход')

@section('content')
<div class="auth-card">
    <div class="auth-card__header">
        <h1 class="auth-card__title">Вход в панель управления</h1>
        <p class="auth-card__subtitle">Музей «Иркутское юнкерское училище»</p>
    </div>

    {{-- Flash-сообщения --}}
    @if (session('error'))
        <div class="auth-alert auth-alert--error">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="auth-alert auth-alert--success">
            {{ session('success') }}
        </div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('admin.login') }}">
        @csrf

        <div class="auth-form__group">
            <label class="auth-form__label" for="email">Email</label>
            <input
                class="auth-form__input @error('email') auth-form__input--error @enderror"
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
            >
            @error('email')
                <span class="auth-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="auth-form__group">
            <label class="auth-form__label" for="password">Пароль</label>
            <input
                class="auth-form__input @error('password') auth-form__input--error @enderror"
                type="password"
                id="password"
                name="password"
                required
            >
            @error('password')
                <span class="auth-form__error">{{ $message }}</span>
            @enderror
        </div>

        <button class="auth-form__button" type="submit">Войти</button>
    </form>

    @if (config('app.admin_register_enable'))
        <div class="auth-card__footer">
            <a class="auth-card__link" href="{{ route('admin.register') }}">Создать аккаунт</a>
        </div>
    @endif
</div>
@endsection
