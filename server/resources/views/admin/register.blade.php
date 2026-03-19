@extends('layouts.admin')

@section('title', 'Регистрация')

@section('content')
<div class="auth-card">
    <div class="auth-card__header">
        <h1 class="auth-card__title">Регистрация</h1>
        <p class="auth-card__subtitle">Музей «Иркутское юнкерское училище»</p>
    </div>

    <form class="auth-form" method="POST" action="{{ route('admin.register') }}">
        @csrf

        <div class="auth-form__group">
            <label class="auth-form__label" for="name">Имя</label>
            <input
                class="auth-form__input @error('name') auth-form__input--error @enderror"
                type="text"
                id="name"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
            >
            @error('name')
                <span class="auth-form__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="auth-form__group">
            <label class="auth-form__label" for="email">Email</label>
            <input
                class="auth-form__input @error('email') auth-form__input--error @enderror"
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
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

        <div class="auth-form__group">
            <label class="auth-form__label" for="password_confirmation">Подтверждение пароля</label>
            <input
                class="auth-form__input"
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
            >
        </div>

        <button class="auth-form__button" type="submit">Зарегистрироваться</button>
    </form>

    <div class="auth-card__footer">
        <a class="auth-card__link" href="{{ route('admin.login') }}">Уже есть аккаунт?</a>
    </div>
</div>
@endsection
