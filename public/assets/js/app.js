document.addEventListener('DOMContentLoaded', function () {
  const navToggle = document.querySelector('.nav-toggle');
  const mainNav = document.querySelector('.main-nav');

  if (navToggle && mainNav) {
    function closeNavigation() {
      navToggle.setAttribute('aria-expanded', 'false');
      navToggle.setAttribute('aria-label', 'Abrir menú de navegación');
      mainNav.classList.remove('is-open');
    }

    navToggle.setAttribute('aria-label', 'Abrir menú de navegación');
    navToggle.addEventListener('click', function () {
      const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', String(!isExpanded));
      navToggle.setAttribute(
        'aria-label',
        isExpanded ? 'Abrir menú de navegación' : 'Cerrar menú de navegación'
      );
      mainNav.classList.toggle('is-open', !isExpanded);
    });

    mainNav.addEventListener('click', function (event) {
      if (event.target instanceof Element && event.target.closest('a')) {
        closeNavigation();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && navToggle.getAttribute('aria-expanded') === 'true') {
        closeNavigation();
        navToggle.focus();
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 900) {
        closeNavigation();
      }
    });
  }

  const forms = document.querySelectorAll('form');
  forms.forEach(function (form) {
    form.addEventListener('submit', function () {
      const button = form.querySelector('button[type="submit"]');
      if (!button) return;
      const currentText = button.textContent.trim();
      button.disabled = true;
      button.dataset.originalText = currentText;
      button.textContent = 'Procesando...';
    });
  });
});
