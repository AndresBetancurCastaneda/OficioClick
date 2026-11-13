document.addEventListener('DOMContentLoaded', function () {
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
