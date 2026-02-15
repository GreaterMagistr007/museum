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
   Подсветка активного пункта меню (по data-page с сервера или pathname)
   ============================================================ */
function initActiveNavLink() {
  const currentPage = document.body.getAttribute('data-page') || getCurrentPageFromPath();
  const navLinks = document.querySelectorAll('.nav__link');

  navLinks.forEach((link) => {
    link.classList.remove('nav__link--active');
    const routeName = link.getAttribute('data-route');

    if (routeName && routeName === currentPage) {
      link.classList.add('nav__link--active');
    }

    const militaryPages = ['junker-school', 'infantry-courses', 'topographic-unit', 'military-town'];
    if (militaryPages.includes(currentPage) && link.classList.contains('nav__link--has-dropdown')) {
      link.classList.add('nav__link--active');
    }

    const excursionPages = ['excursion-overview', 'excursion-junker', 'excursion-awards', 'excursion-topographic-service', 'excursion-irkutsk-topographic', 'excursion-documents'];
    if (excursionPages.includes(currentPage) && routeName === 'excursions') {
      link.classList.add('nav__link--active');
    }
  });
}

function getCurrentPageFromPath() {
  const path = window.location.pathname.replace(/^\//, '').replace(/\/$/, '') || 'home';
  const segment = path.split('/')[0];
  if (path === '' || path === 'home') return 'home';
  return path.indexOf('/') !== -1 ? path.replace(/\//g, '-') : segment;
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
      if (window.innerWidth <= 768) {
        e.preventDefault();
        item.classList.toggle('is-open');
      }
    });
  });

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

      content.querySelectorAll('script').forEach((oldScript) => {
        const newScript = document.createElement('script');
        Array.from(oldScript.attributes).forEach((attr) => {
          newScript.setAttribute(attr.name, attr.value);
        });
        if (oldScript.textContent) {
          newScript.textContent = oldScript.textContent;
        }
        oldScript.parentNode.replaceChild(newScript, oldScript);
      });

      openModal();
    });
  });

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
