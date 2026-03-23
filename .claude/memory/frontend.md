---
name: frontend
description: CSS, JS, изображения, шрифты, HTML-прототип
type: project
---
# Frontend
## Стек
- Blade-шаблоны (SSR)
- Vanilla CSS (`server/public/css/style.css`, 1362 строки)
- Vanilla JS (`server/public/js/main.js`, 183 строки)
- Vite настроен но не задействован — assets через `asset()` из `public/`
## CSS
Методология: BEM. Цвета через CSS Custom Properties.
Ключевые: `--color-bg: #EDDCC8`, `--color-primary: #8B1A1A`, `--color-accent: #D4611E`, `--color-nav-bg: #8B1A1A`
Responsive breakpoints: 1024px, 768px, 500px.
## JS
Функции: initActiveNavLink, initBurgerMenu, initDropdown, initModals, initFormationsAccordion.
## Изображения
`server/public/images/`: anfas.jpg, ivu.jpg, uu.jpg, znak-ivu.jpg, znam-irk-obl.jpg
Внешние изображения (Wikimedia Commons). Плейсхолдеры на многих страницах.
## HTML-прототип
`frontend/` — статические HTML-файлы до переноса на Laravel.
## Шрифты
Georgia, "Times New Roman", serif.
