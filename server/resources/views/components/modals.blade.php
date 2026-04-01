<div class="modal-overlay" id="modalOverlay">
    <div class="modal" id="modal">
        <button class="modal__close" id="modalClose" aria-label="Закрыть">&times;</button>
        <h3 class="modal__title" id="modalTitle"></h3>
        <div class="modal__content" id="modalContent"></div>
    </div>
</div>

<template id="tpl-about">
    {!! $siteSettings['modals.about'] ?? '<h4>О музее</h4><p>Информация о музее.</p>' !!}
</template>

<template id="tpl-schedule">
    <h4>Режим работы</h4>
    <p><strong>Понедельник – Пятница:</strong> {{ $siteSettings['schedule.weekdays'] ?? '09:00 – 17:00' }}</p>
    <p><strong>Суббота:</strong> {{ $siteSettings['schedule.saturday'] ?? '10:00 – 15:00' }}</p>
    <p><strong>Воскресенье:</strong> {{ $siteSettings['schedule.sunday'] ?? 'выходной' }}</p>
    @if(!empty($siteSettings['schedule.note']))
        <p><em>{{ $siteSettings['schedule.note'] }}.</em></p>
    @endif
</template>

<template id="tpl-location">
    <h4>Как нас найти</h4>
    <p><strong>Адрес:</strong> {{ $siteSettings['modals.location_address'] ?? 'г. Иркутск, ул. Советская, д. 176' }}</p>
    @if(!empty($siteSettings['contacts.map_id']))
    <div class="modal__map-placeholder">
        <script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A{{ $siteSettings['contacts.map_id'] }}&amp;width=100%25&amp;height=720&amp;lang=ru_RU&amp;scroll=true"></script>
    </div>
    @endif
</template>

<template id="tpl-contacts">
    <h4>Контакты</h4>
    <p><strong>Телефон:</strong> {{ $siteSettings['contacts.phone'] ?? '+7 (3952) XX-XX-XX' }}</p>
    <p><strong>Email:</strong> {{ $siteSettings['contacts.email'] ?? 'museum@example.ru' }}</p>
    <p><strong>Адрес:</strong> {{ $siteSettings['modals.location_address'] ?? 'г. Иркутск, ул. Советская, д. 176' }}</p>
</template>
