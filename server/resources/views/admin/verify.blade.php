@extends('layouts.admin')

@section('title', 'Подтверждение')

@section('content')
<div class="auth-card">
    <div class="auth-card__header">
        <h1 class="auth-card__title">Подтверждение входа</h1>
        <p class="auth-card__subtitle">Код отправлен на {{ $masked_email }}</p>
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

    <form class="auth-form" method="POST" action="{{ route('admin.verify') }}">
        @csrf

        <div class="auth-form__group">
            <label class="auth-form__label" for="code">Код подтверждения</label>
            <input
                class="auth-form__input auth-form__input--code @error('code') auth-form__input--error @enderror"
                type="text"
                id="code"
                name="code"
                maxlength="6"
                inputmode="numeric"
                pattern="[0-9]{6}"
                placeholder="000000"
                required
                autofocus
                autocomplete="one-time-code"
            >
            @error('code')
                <span class="auth-form__error">{{ $message }}</span>
            @enderror
        </div>

        <button class="auth-form__button" type="submit">Подтвердить</button>
    </form>

    <div class="auth-card__footer">
        <form method="POST" action="{{ route('admin.verify.resend') }}">
            @csrf
            <button
                class="auth-card__link auth-card__link--button"
                type="submit"
                id="resend-btn"
            >
                Отправить код повторно
            </button>
        </form>
        <p class="auth-card__timer" id="resend-timer" style="display: none;">
            Повторная отправка через <span id="resend-seconds">60</span> сек.
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var btn = document.getElementById('resend-btn');
    var timer = document.getElementById('resend-timer');
    var secondsEl = document.getElementById('resend-seconds');
    var COOLDOWN = 60;
    var storageKey = 'resend_timer_end';

    /**
     * Запускает обратный отсчёт и блокирует кнопку повторной отправки.
     */
    function startTimer(seconds) {
        var end = Date.now() + seconds * 1000;
        localStorage.setItem(storageKey, end);
        tick(end);
    }

    /**
     * Обновляет таймер каждую секунду.
     */
    function tick(end) {
        var remaining = Math.ceil((end - Date.now()) / 1000);
        if (remaining <= 0) {
            btn.disabled = false;
            btn.style.display = '';
            timer.style.display = 'none';
            localStorage.removeItem(storageKey);
            return;
        }
        btn.disabled = true;
        btn.style.display = 'none';
        timer.style.display = '';
        secondsEl.textContent = remaining;
        setTimeout(function () { tick(end); }, 1000);
    }

    // Проверяем, есть ли активный таймер
    var savedEnd = localStorage.getItem(storageKey);
    if (savedEnd && parseInt(savedEnd) > Date.now()) {
        tick(parseInt(savedEnd));
    }

    // При отправке формы запускаем таймер
    btn.closest('form').addEventListener('submit', function () {
        startTimer(COOLDOWN);
    });
})();
</script>
@endpush
