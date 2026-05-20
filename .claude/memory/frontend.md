---
name: frontend
description: CSS (переменные, компоненты, breakpoints), JS (функции), Blade-шаблоны, изображения
type: project
---
# Frontend
## Стек
Blade SSR. Vanilla CSS (BEM) + vanilla JS. Vite настроен, не задействован — assets через `asset()` из `public/`.
resources/css/app.css пустой, resources/js/app.js + bootstrap.js не используются.
## CSS: style.css (public/css/style.css, 1359 строк)
### Custom Properties
Цвета: `--color-bg: #EDDCC8` (тёплый бежевый), `--color-primary: #8B1A1A` (тёмно-красный), `--color-primary-dark: #6B1010`, `--color-accent: #D4611E` (оранжевый), `--color-accent-light: #E8872E`, `--color-border: #A0522D`, `--color-text: #3E2218`, `--color-text-light: #5A3A2A`, `--color-btn-bg: #FFF8F0`, `--color-bg-light: #F5EDE2`, `--color-header-bg: #E8D1B8`, `--color-nav-bg: #8B1A1A`, `--color-excursion-bg: #F5D5C8`, `--color-excursion-border: #C4543A`.
Радиусы: sm 6px, md 10px, lg 16px, pill 50px. Тени: sm/md/lg. Transition: 0.25s ease. Max-width: 1200px.
### Основные компоненты
- `.header` (82-162) — flex, logo-group с флагом/фото/названием/эмблемой
- `.nav` (166-287) — #8B1A1A, flex nav__list, dropdown подменю, burger
- `.content` (291-299) — grid 3 колонки: 160px 1fr 160px
- `.sidebar` (310-340) — кнопки (about, schedule, location, contacts, exposition, news, archive)
- `.formations` (344-432) — дерево воинских формирований, аккордеон на мобиле
- `.excursions` (437-498) — grid 3 колонки с кнопками экскурсий, border #D4611E
- `.footer` (530-536) — #8B1A1A, белый текст
- `.modal-overlay` / `.modal` (541-652) — overlay + scale-анимация, max-width 560px
- `.page` (933+) — внутренние страницы, max-width 900px
- `.breadcrumbs` — навигационные хлебные крошки
- `.article` — контент с h3, figure, figcaption
- `.image-placeholder` — заглушки изображений (gradient bg, border dashed)
- `.news-card`, `.card`, `.excursion-card` — карточки контента
- `.contact-grid` — 2 колонки (info + map)
- `.schedule-table` — таблица расписания
### Breakpoints
- 1024px: grid 140px 1fr 140px, уменьшенные размеры хедера
- 768px: 1 колонка, burger-меню, sidebar horizontal, formations аккордеон, excursions 1 колонка
- 500px: уменьшенные отступы, шрифты, padding
## CSS: admin.css (public/css/admin.css, 339 строк)
Те же CSS Custom Properties. Компоненты: `.auth-card` (center, max-width 440px, padding 40px), `.auth-form` (input, label, button, error), `.auth-alert` (error/success), `.dashboard` (card, info-row, logout-btn). Input для кода: `--code` (center, 1.6rem, letter-spacing 8px). Breakpoint: 480px.
## JS: main.js (public/js/main.js, 184 строки)
5 функций, инициализация через DOMContentLoaded:
- `initActiveNavLink()` (17-46) — подсветка активной ссылки по data-page/pathname. Группы: военный городок, экскурсии.
- `initBurgerMenu()` (51-70) — toggle is-open/is-active, закрытие по клику вне.
- `initDropdown()` (75-98) — toggle dropdown на мобиле (preventDefault), desktop без блокировки.
- `initModals()` (103-167) — data-modal -> template (tpl-about/schedule/location/contacts), клонирование скриптов, close по кнопке/overlay/Escape. body overflow hidden.
- `initFormationsAccordion()` (172-183) — toggle аккордеон формирований на мобиле.
## Blade-шаблоны
### Layouts
- `layouts/app.blade.php` (25 строк) — lang=ru, `<x-header/>`, `@yield('content')`, `<x-footer/>`, `@yield('modals')`, `@stack('scripts')`. data-page на body.
- `layouts/admin.blade.php` (17 строк) — admin.css, `.admin-body > .admin-container > @yield('content')`.
### Components
- `header.blade.php` (69 строк) — logo-group (5 изображений), nav с burger, dropdown "Военный городок".
- `footer.blade.php` (3 строки) — copyright с `date('Y')`.
- `modals.blade.php` — overlay + 3 template: tpl-schedule, tpl-location (contacts.location_intro nl2br + адрес + Яндекс.Карты), tpl-contacts. Кнопка «О музее» на главной — обычная ссылка на route('about'), модалки нет.
- `breadcrumbs.blade.php` (12 строк) — props: items [{title, url}], последний без ссылки.
### Pages (12 файлов, все @extends('layouts.app'))
home — 3 колонки (sidebar/formations/sidebar), секция экскурсий с динамическими кнопками из БД + anfas.jpg. Данные через PageController::home.
about — история, миссия, расписание (таблица).
contacts — контактная информация + Яндекс.Карты.
news — динамические news-card из БД.
exposition — grid 6 card (6 залов экспозиции).
archive — grid 6 card (архивные материалы).
excursions — динамические excursion-card из БД (@forelse по $excursions).
excursion-show — детальная страница экскурсии (slug routing, description/what_you_see/interesting_facts через {!! !!}).
military-town, junker-school, infantry-courses, topographic-unit — исторические статьи.
Удалены: excursion-{overview,junker,awards,topographic-service,irkutsk-topographic,documents} — заменены на excursion-show + БД.
### Admin views (4 файла, все @extends('layouts.admin'))
login (67), register (78), verify (118, inline JS таймер), dashboard (30).
### Email
verification-code.blade.php (36) — inline-стили, Georgia, код 36px, 10 мин.
## Изображения (public/images/)
anfas.jpg (7.2 MB), ivu.jpg (668 KB), uu.jpg (184 KB), znak-ivu.jpg (28 KB), znam-irk-obl.jpg (19 KB).
Внешние: Wikimedia Commons (junker-school.blade.php). Большинство страниц используют `.image-placeholder`.
## Шрифты
Georgia, "Times New Roman", serif.
## HTML-прототип
`frontend/` — статические HTML до переноса на Laravel. 19 HTML-файлов + css/js/images. Не используется в production.
