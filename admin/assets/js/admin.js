(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var sidebar = document.querySelector('[data-sidebar]');
    if (toggle && sidebar) {
      toggle.addEventListener('click', function () {
        sidebar.classList.toggle('open');
      });
    }

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        if (!confirm(el.getAttribute('data-confirm'))) {
          e.preventDefault();
        }
      });
    });
  });
})();
