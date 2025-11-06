(function () {
  'use strict';

  function mountTemplate(button) {
    var targetId = button.getAttribute('data-comment-target');
    if (!targetId) {
      return;
    }

    var slot = document.getElementById(targetId);
    var template = document.getElementById(targetId + '-tpl');
    if (!slot || !template || slot.childElementCount) {
      return;
    }

    var content = template.content ? template.content.cloneNode(true) : null;
    if (!content) {
      return;
    }

    slot.appendChild(content);
    slot.removeAttribute('hidden');
    button.setAttribute('aria-expanded', 'true');
    button.setAttribute('hidden', 'hidden');
  }

  function init() {
    var buttons = document.querySelectorAll('.has-lazy-comments .comment-toggle');
    if (!buttons.length) {
      return;
    }

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        mountTemplate(button);
      }, { once: true });

      button.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          mountTemplate(button);
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();