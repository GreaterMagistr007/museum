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
    @if(!empty($siteSettings['schedule.weekdays']))
        <p><strong>Понедельник – Пятница:</strong> {{ $siteSettings['schedule.weekdays'] }}</p>
    @endif
    @if(!empty($siteSettings['schedule.saturday']))
        <p><strong>Суббота:</strong> {{ $siteSettings['schedule.saturday'] }}</p>
    @endif
    @if(!empty($siteSettings['schedule.sunday']))
        <p><strong>Воскресенье:</strong> {{ $siteSettings['schedule.sunday'] }}</p>
    @endif
    @if(!empty($siteSettings['schedule.note']))
        <p><em>{{ $siteSettings['schedule.note'] }}.</em></p>
    @endif
</template>

<template id="tpl-location">
    <h4>Как нас найти</h4>
    @if(!empty($siteSettings['contacts.address']))
        <p><strong>Адрес:</strong> {{ $siteSettings['contacts.address'] }}</p>
    @endif
    @if(!empty($siteSettings['contacts.map_id']))
    <div class="modal__map-placeholder">
        <script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A{{ $siteSettings['contacts.map_id'] }}&amp;width=100%25&amp;height=720&amp;lang=ru_RU&amp;scroll=true"></script>
    </div>
    @endif
</template>

<template id="tpl-contacts">
    <h4>Контакты</h4>
    @if(!empty($siteSettings['contacts.phone']))
        <p><strong>Телефон:</strong> {{ $siteSettings['contacts.phone'] }}</p>
    @endif
    @if(!empty($siteSettings['contacts.email']))
        <p><strong>Email:</strong> {{ $siteSettings['contacts.email'] }}</p>
    @endif
    @if(!empty($siteSettings['contacts.address']))
        <p><strong>Адрес:</strong> {{ $siteSettings['contacts.address'] }}</p>
    @endif
</template>
