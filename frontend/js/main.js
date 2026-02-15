/**
 * Музей «Иркутское юнкерское училище» — JavaScript
 * Функционал: бургер-меню, дропдаун, модальные окна, аккордеон формирований
 */

document.addEventListener('DOMContentLoaded', () => {
  initActiveNavLink();
  initBurgerMenu();
  initDropdown();
  initModals();
  initFormationsAccordion();
});

/* ============================================================
   Подсветка активного пункта меню (автоматически по URL)
   ============================================================ */
function initActiveNavLink() {
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  const navLinks = document.querySelectorAll('.nav__link');

  navLinks.forEach((link) => {
    link.classList.remove('nav__link--active');
    const href = link.getAttribute('href');

    if (href === currentPage) {
      link.classList.add('nav__link--active');
    }

    // Подсвечиваем «Военный городок» для вложенных страниц
    const militaryPages = ['junker-school.html', 'infantry-courses.html', 'topographic-unit.html', 'military-town.html'];
    if (militaryPages.includes(currentPage) && link.classList.contains('nav__link--has-dropdown')) {
      link.classList.add('nav__link--active');
    }

    // Подсвечиваем «Экскурсии» для страниц отдельных экскурсий
    if (currentPage.startsWith('excursion-') && href === 'excursions.html') {
      link.classList.add('nav__link--active');
    }
  });
}

/* ============================================================
   Бургер-меню (мобильная навигация)
   ============================================================ */
function initBurgerMenu() {
  const burger = document.getElementById('burgerBtn');
  const navList = document.getElementById('navList');

  if (!burger || !navList) return;

  burger.addEventListener('click', () => {
    const isOpen = navList.classList.toggle('is-open');
    burger.classList.toggle('is-active');
    burger.setAttribute('aria-expanded', isOpen);
  });

  // Закрытие меню при клике вне его
  document.addEventListener('click', (e) => {
    if (!burger.contains(e.target) && !navList.contains(e.target)) {
      navList.classList.remove('is-open');
      burger.classList.remove('is-active');
      burger.setAttribute('aria-expanded', 'false');
    }
  });
}

/* ============================================================
   Выпадающее подменю «Военный городок»
   ============================================================ */
function initDropdown() {
  const dropdownItems = document.querySelectorAll('.nav__item--dropdown');

  dropdownItems.forEach((item) => {
    const link = item.querySelector('.nav__link--has-dropdown');

    if (!link) return;

    link.addEventListener('click', (e) => {
      // На мобильных — toggle, на десктопе — hover работает через CSS
      if (window.innerWidth <= 768) {
        e.preventDefault();
        item.classList.toggle('is-open');
      }
    });
  });

  // Закрытие при клике вне
  document.addEventListener('click', (e) => {
    dropdownItems.forEach((item) => {
      if (!item.contains(e.target)) {
        item.classList.remove('is-open');
      }
    });
  });
}

/* ============================================================
   Модальные окна (левая колонка кнопок)
   ============================================================ */
function initModals() {
  const overlay = document.getElementById('modalOverlay');
  const modal = document.getElementById('modal');
  const content = document.getElementById('modalContent');
  const closeBtn = document.getElementById('modalClose');
  const triggers = document.querySelectorAll('[data-modal]');

  if (!overlay || !modal || !content || !closeBtn) return;

  // Маппинг data-modal -> id шаблона
  const templateMap = {
    about: 'tpl-about',
    schedule: 'tpl-schedule',
    location: 'tpl-location',
    contacts: 'tpl-contacts',
  };

  triggers.forEach((btn) => {
    btn.addEventListener('click', () => {
      const key = btn.getAttribute('data-modal');
      const templateId = templateMap[key];
      const template = document.getElementById(templateId);

      if (!template) return;

      content.innerHTML = template.innerHTML;
      openModal();
    });
  });

  // Закрытие
  closeBtn.addEventListener('click', closeModal);

  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeModal();
    }
  });

  function openModal() {
    overlay.classList.add('is-visible');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    overlay.classList.remove('is-visible');
    document.body.style.overflow = '';
  }
}

/* ============================================================
   Аккордеон «Воинские формирования» (мобильная версия)
   ============================================================ */
function initFormationsAccordion() {
  const toggle = document.getElementById('formationsToggle');
  const tree = document.getElementById('formationsTree');

  if (!toggle || !tree) return;

  toggle.addEventListener('click', () => {
    const isOpen = tree.classList.toggle('is-open');
    toggle.classList.toggle('is-active');
    toggle.setAttribute('aria-expanded', isOpen);
  });
}
