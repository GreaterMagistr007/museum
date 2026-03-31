---
name: integration-yandex-maps
description: Iframe Яндекс.Карт (Конструктор карт) на странице контактов и в модалке
type: project
---
# Интеграция: Яндекс.Карты
Встраиваемый виджет (Конструктор карт), без API-ключа. Клиентский JavaScript, нет серверных вызовов.
## Использование
1. Модальное окно "Как нас найти" — components/modals.blade.php, template #tpl-location. Карта пересоздаётся при открытии (initModals клонирует script из template).
2. Страница контактов — pages/contacts.blade.php, блок `.contact-map`.
## Параметры
um: конструктор ID, width: 100%, height: 720px (модалка) / 350px (контакты), lang: ru_RU, scroll: true.
Скрипт загружается async: `api-maps.yandex.ru/services/constructor/1.0/js/`.
## Проблема: расхождение адресов
Модалка (tpl-location): ул. Советская, 176. Контакты (contacts.blade.php): ул. Ярослава Гашека, 5.
Зафиксировано в tech_debt.md.
