@extends('layouts.admin')

@section('title', 'Настройки')
@section('page-title', 'Настройки сайта')

@section('content')
<div class="admin-tabs" id="settingsTabs">
    <button class="admin-tabs__btn admin-tabs__btn--active" data-tab="contacts">Контакты</button>
    <button class="admin-tabs__btn" data-tab="schedule">Расписание</button>
    <button class="admin-tabs__btn" data-tab="about">О музее</button>
    <button class="admin-tabs__btn" data-tab="general">Общие</button>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- Контакты --}}
    <div class="admin-tabs__panel admin-tabs__panel--active" id="tab-contacts">
        <div class="admin-card">
            <h2 class="admin-card__title">Контактная информация</h2>

            <div class="admin-form__group">
                <label class="admin-form__label" for="contacts_address">Адрес</label>
                <input class="admin-form__input" type="text" id="contacts_address" name="contacts[address]"
                       value="{{ $settings['contacts.address'] ?? '' }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="contacts_phone">Телефон</label>
                <input class="admin-form__input" type="text" id="contacts_phone" name="contacts[phone]"
                       value="{{ $settings['contacts.phone'] ?? '' }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="contacts_email">Email</label>
                <input class="admin-form__input" type="email" id="contacts_email" name="contacts[email]"
                       value="{{ $settings['contacts.email'] ?? '' }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="contacts_map_id">ID карты Яндекс</label>
                <input class="admin-form__input" type="text" id="contacts_map_id" name="contacts[map_id]"
                       value="{{ $settings['contacts.map_id'] ?? '' }}">
                <span class="admin-form__hint">ID конструктора Яндекс.Карт (hex-строка)</span>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="contacts_location_intro">Текст над адресом (модальное окно «Как нас найти»)</label>
                <textarea class="admin-form__textarea" id="contacts_location_intro" name="contacts[location_intro]"
                          rows="4">{{ $settings['contacts.location_intro'] ?? '' }}</textarea>
                <span class="admin-form__hint">Простой текст, переносы строк сохраняются.</span>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="contacts_intro">Текст в модальном окне «Контакты»</label>
                <textarea class="admin-form__textarea" id="contacts_intro" name="contacts[intro]"
                          rows="4">{{ $settings['contacts.intro'] ?? '' }}</textarea>
                <span class="admin-form__hint">Выводится над списком контактов. Простой текст, переносы строк сохраняются.</span>
            </div>
        </div>
    </div>

    {{-- Расписание --}}
    <div class="admin-tabs__panel" id="tab-schedule">
        <div class="admin-card">
            <h2 class="admin-card__title">Режим работы</h2>

            <div class="admin-form__group">
                <label class="admin-form__label" for="schedule_weekdays">Понедельник – Пятница</label>
                <input class="admin-form__input" type="text" id="schedule_weekdays" name="schedule[weekdays]"
                       value="{{ $settings['schedule.weekdays'] ?? '' }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="schedule_saturday">Суббота</label>
                <input class="admin-form__input" type="text" id="schedule_saturday" name="schedule[saturday]"
                       value="{{ $settings['schedule.saturday'] ?? '' }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="schedule_sunday">Воскресенье</label>
                <input class="admin-form__input" type="text" id="schedule_sunday" name="schedule[sunday]"
                       value="{{ $settings['schedule.sunday'] ?? '' }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="schedule_note">Примечание</label>
                <input class="admin-form__input" type="text" id="schedule_note" name="schedule[note]"
                       value="{{ $settings['schedule.note'] ?? '' }}">
            </div>
        </div>
    </div>

    {{-- О музее --}}
    <div class="admin-tabs__panel" id="tab-about">
        <div class="admin-card">
            <h2 class="admin-card__title">Страница «О музее»</h2>

            <div class="admin-form__group">
                <label class="admin-form__label" for="about_history">История музея</label>
                <textarea class="admin-form__textarea wysiwyg" id="about_history" name="about[history]"
                          rows="12">{{ $settings['about.history'] ?? '' }}</textarea>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="about_mission">Миссия и деятельность</label>
                <textarea class="admin-form__textarea wysiwyg" id="about_mission" name="about[mission]"
                          rows="12">{{ $settings['about.mission'] ?? '' }}</textarea>
            </div>
        </div>
    </div>

    {{-- Общие --}}
    <div class="admin-tabs__panel" id="tab-general">
        <div class="admin-card">
            <h2 class="admin-card__title">Общие настройки</h2>

            <div class="admin-form__group">
                <label class="admin-form__label" for="general_site_title">Название сайта</label>
                <input class="admin-form__input" type="text" id="general_site_title" name="general[site_title]"
                       value="{{ $settings['general.site_title'] ?? '' }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="general_site_subtitle">Подзаголовок</label>
                <input class="admin-form__input" type="text" id="general_site_subtitle" name="general[site_subtitle]"
                       value="{{ $settings['general.site_subtitle'] ?? '' }}">
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label" for="general_footer_text">Текст в подвале</label>
                <input class="admin-form__input" type="text" id="general_footer_text" name="general[footer_text]"
                       value="{{ $settings['general.footer_text'] ?? '' }}">
                <span class="admin-form__hint">Если не задан, используется текст по умолчанию</span>
            </div>

            <div class="admin-form__group">
                <label class="admin-form__label">Изображение здания (главная страница)</label>
                @php $buildingImage = $settings['home.building_image'] ?? null; @endphp
                @if($buildingImage)
                    <div class="admin-form__preview" style="margin-bottom: 0.5rem;">
                        <img src="{{ Storage::url($buildingImage) }}" alt="Здание" style="max-width: 300px; max-height: 200px;">
                    </div>
                    <label class="admin-form__checkbox">
                        <input type="checkbox" name="remove_building_image" value="1"> Удалить изображение
                    </label>
                @endif
                <input class="admin-form__input" type="file" name="home_building_image" accept="image/*">
                <span class="admin-form__hint">Рекомендуемый размер: 800x600 px. Если не задано, используется изображение по умолчанию.</span>
            </div>
        </div>
    </div>

    <div class="admin-form__actions">
        <button type="submit" class="admin-form__button admin-form__button--primary">Сохранить настройки</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function() {
    var tabs = document.querySelectorAll('.admin-tabs__btn');
    var panels = document.querySelectorAll('.admin-tabs__panel');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var target = this.getAttribute('data-tab');

            // Переключение активного таба
            tabs.forEach(function(t) { t.classList.remove('admin-tabs__btn--active'); });
            this.classList.add('admin-tabs__btn--active');

            // Переключение панели
            panels.forEach(function(p) { p.classList.remove('admin-tabs__panel--active'); });
            var panel = document.getElementById('tab-' + target);
            if (panel) panel.classList.add('admin-tabs__panel--active');
        });
    });
})();
</script>
@include('admin.partials.tinymce')
@endpush
