(function () {
  'use strict';

  var DATA_ATTR = 'data-defer-style';

  function promote(link) {
    if (!link || link.dataset.wd4Promoted === '1') {
      return;
    }

    var media = link.getAttribute('data-media');

    link.rel = 'stylesheet';
    link.removeAttribute('as');

    if (media) {
      link.setAttribute('media', media);
    } else {
      link.setAttribute('media', 'all');
    }

    link.removeAttribute(DATA_ATTR);
    link.removeAttribute('data-media');
    link.dataset.wd4Promoted = '1';
  }

  function monitor(link) {
    if (!link || link.dataset.wd4Observed === '1') {
      return;
    }

    link.dataset.wd4Observed = '1';

    var done = false;
    var finalize = function () {
      if (done) {
        return;
      }
      done = true;
      promote(link);
    };

    link.addEventListener('load', finalize, { once: true });
    link.addEventListener('error', finalize, { once: true });

    if (link.rel === 'preload' && link.as === 'style') {
      setTimeout(finalize, 2000);
    } else if ('requestAnimationFrame' in window) {
      requestAnimationFrame(finalize);
    } else {
      setTimeout(finalize, 0);
    }
  }

  function init() {
    var links = document.querySelectorAll('link[' + DATA_ATTR + ']');
    if (!links.length) {
      return;
    }

    Array.prototype.forEach.call(links, monitor);
  }

  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    init();
  } else {
    document.addEventListener('DOMContentLoaded', init);
  }
})();