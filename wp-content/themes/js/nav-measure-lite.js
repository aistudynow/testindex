(function () {
  'use strict';

  var NAV_SELECTOR = '#site-header';
  var HOVER_SELECTOR = '.menu-has-child-mega';
  var TARGET_SELECTOR = '.menu-has-child-mega, ul.sub-menu, .flex-dropdown';

  function getModule() {
    return window.FOXIZ_MAIN_SCRIPT || null;
  }

  function getNavRoot() {
    return document.querySelector(NAV_SELECTOR);
  }

  function hasMenuTargets(root) {
    var scope = root || getNavRoot();
    return !!(scope && scope.querySelector(TARGET_SELECTOR));
  }

  function onReady(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback, { once: true });
    } else {
      callback();
    }
  }

  onReady(function () {
    var Module = getModule();
    if (!Module || typeof Module.initSubMenuPos !== 'function' || typeof Module.queueMenuMeasure !== 'function') {
      return;
    }

    var originalCalc = typeof Module.calcSubMenuPos === 'function' ? Module.calcSubMenuPos : null;
    var originalQueue = Module.queueMenuMeasure.bind(Module);

    Module.queueMenuMeasure = function () {
      if (this._submenuPassQueued) {
        return;
      }
      if (!hasMenuTargets()) {
        this._submenuPassQueued = false;
        return;
      }
      originalQueue();
    };

    Module.calcSubMenuPos = function () {
      var root = getNavRoot();
      if (!hasMenuTargets(root)) {
        this._submenuPassQueued = false;
        return false;
      }
      if (originalCalc) {
        return originalCalc.call(this);
      }
      return false;
    };

    Module.initSubMenuPos = function () {
      var self = this;
      var desktopMQ = window.matchMedia('(min-width: 1025px)');
      var triggeredOnce = false;

      function queueMeasure() {
        if (!desktopMQ.matches) {
          self._submenuPassQueued = false;
          return;
        }
        if (!hasMenuTargets()) {
          self._submenuPassQueued = false;
          return;
        }
        self.queueMenuMeasure();
      }

      function bindHoverListeners() {
        var root = getNavRoot();
        if (!root) {
          return;
        }
        var items = root.querySelectorAll(HOVER_SELECTOR);
        if (!items.length) {
          return;
        }
        items.forEach(function (item) {
          item.addEventListener('mouseenter', function () {
            if (!desktopMQ.matches) {
              return;
            }
            if (!triggeredOnce) {
              queueMeasure();
              triggeredOnce = true;
            }
          }, { passive: true });
        });
      }

      var start = function () {
        requestAnimationFrame(queueMeasure);
        bindHoverListeners();
      };

      if (document.readyState === 'complete') {
        start();
      } else {
        window.addEventListener('load', function handleLoad() {
          window.removeEventListener('load', handleLoad);
          start();
        });
      }

      var mqListener = function (event) {
        if (event.matches) {
          triggeredOnce = false;
          requestAnimationFrame(queueMeasure);
        }
      };

      if (typeof desktopMQ.addEventListener === 'function') {
        desktopMQ.addEventListener('change', mqListener);
      } else if (typeof desktopMQ.addListener === 'function') {
        desktopMQ.addListener(mqListener);
      }

      var resizeTimer = null;
      window.addEventListener('resize', function () {
        if (!desktopMQ.matches) {
          return;
        }
        if (resizeTimer) {
          clearTimeout(resizeTimer);
        }
        resizeTimer = setTimeout(function () {
          triggeredOnce = false;
          queueMeasure();
        }, 150);
      }, { passive: true });
    };

    if (window.__FOXIZ_BOOTED__) {
      try {
        Module.initSubMenuPos();
      } catch (err) {
        /* noop */
      }
    }
  });
})();