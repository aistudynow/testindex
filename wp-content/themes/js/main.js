var FOXIZ_MAIN_SCRIPT = window.FOXIZ_MAIN_SCRIPT || {};

(function (Module, win, doc) {
  'use strict';

  const docEl = doc.documentElement;
  let bodyEl = doc.body || doc.documentElement;

  const PASSIVE_EVENTS = new Set(['scroll', 'touchstart', 'touchmove', 'wheel']);

  const $ = (sel, ctx = doc) => ctx.querySelector(sel);
  const $$ = (sel, ctx = doc) => Array.from(ctx.querySelectorAll(sel));

  const safeError = (err) => {
    if (win.console && typeof win.console.error === 'function') {
      console.error(err);
    }
  };

  const on = (el, type, handler, opts) => {
    if (!el || !type || typeof handler !== 'function') return;
    const options = opts == null && PASSIVE_EVENTS.has(type) ? { passive: true } : (opts || false);
    el.addEventListener(type, handler, options);
  };

  const delegate = (root, selector, type, handler, opts) => {
    if (!root || !selector || typeof handler !== 'function') return;
    on(root, type, (event) => {
      const path = event.composedPath ? event.composedPath() : null;
      const target = path
        ? path.find((node) => node instanceof Element && node.matches(selector))
        : event.target;
      const match = target && target instanceof Element ? target.closest(selector) : null;
      if (match) handler(event, match);
    }, opts);
  };

  const toggle = (el, className, force) => {
    if (!el || !el.classList) return;
    el.classList.toggle(className, force);
  };

  const show = (el) => { if (el) el.style.display = ''; };
  const hide = (el) => { if (el) el.style.display = 'none'; };

  /* -------------------------
   * Storage helpers / prefs
   * ------------------------ */

  Module.isStorageAvailable = function () {
    try {
      localStorage.setItem('__rb__t', '1');
      localStorage.removeItem('__rb__t');
      return true;
    } catch (err) {
      return false;
    }
  };

  Module.setStorage = function (key, value) {
    if (!this.yesStorage) return;
    const payload = typeof value === 'string' ? value : JSON.stringify(value);
    try {
      localStorage.setItem(key, payload);
    } catch (err) {
      safeError(err);
    }
  };

  Module.getStorage = function (key, defaultValue) {
    const fallback = (typeof defaultValue === 'undefined') ? null : defaultValue;
    if (!this.yesStorage) return fallback;
    let raw;
    try {
      raw = localStorage.getItem(key);
    } catch (err) {
      safeError(err);
      return fallback;
    }
    if (raw == null) return fallback;
    try { return JSON.parse(raw); } catch (err) { return raw; }
  };

  Module.deleteStorage = function (key) {
    if (!this.yesStorage) return;
    try {
      localStorage.removeItem(key);
    } catch (err) {
      safeError(err);
    }
  };

  const applyStoredPreferencesJob = function (helpers) {
    if (!helpers || typeof helpers !== 'object') return;

    const consentReader = typeof helpers.readLocal === 'function' ? helpers.readLocal : null;
    const consent = consentReader ? consentReader('RubyPrivacyAllowed', '1') : '1';

    if (!consent) {
      const privacyBox = doc.getElementById('rb-privacy');
      if (privacyBox && privacyBox.classList && !privacyBox.classList.contains('activated')) {
        privacyBox.classList.add('activated');
      }
    }

    const node = bodyEl || doc.body || doc.documentElement;
    if (!node || !node.classList) return;

    const readingReader = typeof helpers.readSession === 'function' ? helpers.readSession : null;
    const reading = readingReader ? readingReader('rubyResizerStep', '1') : '1';

    node.classList.remove('medium-entry-size', 'big-entry-size');
    if (reading === '2') {
      node.classList.add('medium-entry-size');
    } else if (reading === '3') {
      node.classList.add('big-entry-size');
    }
  };
  applyStoredPreferencesJob.__rbPrefJob = true;

  Module.consumePreferenceQueue = function () {
    let queue = win.__rbPrefQueue;
    if (!Array.isArray(queue)) {
      queue = [];
      win.__rbPrefQueue = queue;
    }

    if (!queue.some((job) => job && job.__rbPrefJob)) {
      queue.unshift(applyStoredPreferencesJob);
    }

    if (!queue.length) return;

    let localStore = null;
    let sessionStore = null;
    let canUseLocal = false;
    let canUseSession = false;

    try {
      localStore = win.localStorage || null;
      if (localStore) {
        localStore.getItem('__foxiz_pref_probe__');
        canUseLocal = true;
      }
    } catch (err) {
      canUseLocal = false;
      localStore = null;
    }

    try {
      sessionStore = win.sessionStorage || null;
      if (sessionStore) {
        sessionStore.getItem('__foxiz_pref_probe__');
        canUseSession = true;
      }
    } catch (err) {
      canUseSession = false;
      sessionStore = null;
    }

    const helpers = {
      readLocal(key, fallback = null) {
        if (!canUseLocal || !localStore) return fallback;
        try {
          const value = localStore.getItem(key);
          return (value == null) ? fallback : value;
        } catch (err) {
          return fallback;
        }
      },
      readSession(key, fallback = null) {
        if (!canUseSession || !sessionStore) return fallback;
        try {
          const value = sessionStore.getItem(key);
          return (value == null) ? fallback : value;
        } catch (err) {
          return fallback;
        }
      }
    };

    while (queue.length) {
      const job = queue.shift();
      if (typeof job !== 'function') continue;
      try {
        job(helpers);
      } catch (err) {
        safeError(err);
      }
    }

    win.__rbPrefQueue = queue;
  };

  /* -------------------
   * Core init helpers
   * ------------------ */

  Module.initParams = function () {
    bodyEl = doc.body || doc.documentElement;
    this.body = bodyEl;
    this.yesStorage = this.isStorageAvailable();
    this.themeSettings = win.foxizParams || {};
    this.ajaxURL = (win.foxizCoreParams && win.foxizCoreParams.ajaxurl) || '/wp-admin/admin-ajax.php';
    this.ajaxData = {};
    this.readIndicator = $('#reading-progress');
    this.outerHTML = docEl;
    this.YTPlayers = this.YTPlayers || {};
  };

  /* ----------------------
   * Header dropdowns / nav
   * --------------------- */

  Module.headerDropdown = function () {
    const closeAll = () => {
      $$('.dropdown-activated').forEach((el) => {
        el.classList.remove('dropdown-activated');
        $$('[aria-expanded]', el).forEach((toggleEl) => toggleEl.setAttribute('aria-expanded', 'false'));
      });
    };

    const openHolder = (holder) => {
      if (!holder) return;
      if (!holder.classList.contains('dropdown-activated')) {
        closeAll();
        holder.classList.add('dropdown-activated');
      } else {
        holder.classList.remove('dropdown-activated');
      }
    };

    delegate(doc, '.more-trigger', 'click', (event, button) => {
      event.preventDefault();
      event.stopPropagation();
      this.queueMenuMeasure();
      const holder = button.closest('.header-wrap')?.querySelector('.more-section-outer') || null;
      openHolder(holder);
      if (button.classList.contains('search-btn') && holder) {
        const input = holder.querySelector('input[type="text"]');
        if (input && typeof input.focus === 'function') {
          setTimeout(() => input.focus(), 150);
        }
      }
    });

    delegate(doc, '.search-trigger', 'click', (event, button) => {
      event.preventDefault();
      event.stopPropagation();
      this.queueMenuMeasure();
      const holder = button.closest('.header-dropdown-outer');
      openHolder(holder);
      if (holder && holder.classList.contains('dropdown-activated')) {
        const input = holder.querySelector('input[type="text"]');
        if (input && typeof input.focus === 'function') {
          setTimeout(() => input.focus(), 150);
        }
      }
    });

    delegate(doc, '.dropdown-trigger', 'click', (event, button) => {
      event.preventDefault();
      event.stopPropagation();
      this.queueMenuMeasure();
      const holder = button.closest('.header-dropdown-outer');
      openHolder(holder);
      if (button.hasAttribute('aria-expanded') && holder) {
        button.setAttribute('aria-expanded', holder.classList.contains('dropdown-activated') ? 'true' : 'false');
      }
    });
  };

  Module._submenuPassQueued = false;

  Module.queueMenuMeasure = function () {
    if (this._submenuPassQueued) return;
    this._submenuPassQueued = true;
    win.requestAnimationFrame(() => this.calcSubMenuPos());
  };

  Module.calcSubMenuPos = function () {
    if (win.innerWidth < 1025) {
      this._submenuPassQueued = false;
      return;
    }

    const navRoot = $('#site-header') || doc;
    const scrollX = win.scrollX || win.pageXOffset || 0;
    const header = $('#site-header');
    const headerRect = header ? header.getBoundingClientRect() : { left: 0, width: bodyEl.clientWidth };
    const headerLeft = (headerRect.left || 0) + scrollX;
    const headerWidth = headerRect.width || bodyEl.clientWidth;
    const headerRight = headerLeft + headerWidth;

    const isVisible = (el) => el && el.offsetParent !== null;

    const megaItems = $$('.menu-has-child-mega', navRoot)
      .map((item) => {
        if (!isVisible(item)) return null;
        const mega = item.querySelector('.mega-dropdown');
        if (!mega) return null;
        const rect = item.getBoundingClientRect();
        return { item, mega, itemLeft: rect.left + scrollX };
      })
      .filter(Boolean);

    const subMenus = $$('ul.sub-menu', navRoot)
      .map((menu) => {
        if (!isVisible(menu)) return null;
        const rect = menu.getBoundingClientRect();
        const left = rect.left + scrollX;
        return { menu, right: left + menu.offsetWidth + 100 };
      })
      .filter(Boolean);

    const flexDropdowns = $$('.flex-dropdown', navRoot)
      .map((dropdown) => {
        if (!isVisible(dropdown)) return null;
        const parent = dropdown.parentElement;
        if (!parent || parent.classList.contains('is-child-wide') || dropdown.classList.contains('mega-has-left')) {
          return null;
        }
        const dropdownWidth = dropdown.offsetWidth;
        const half = dropdownWidth / 2;
        const parentRect = parent.getBoundingClientRect();
        const parentLeft = parentRect.left + scrollX;
        const parentHalf = (parent.offsetWidth || 0) / 2;
        const center = parentLeft + parentHalf;
        const rightSpace = headerRight - center;
        const leftSpace = center - headerLeft;
        return { dropdown, dropdownWidth, half, parentLeft, parentHalf, headerWidth, rightSpace, leftSpace };
      })
      .filter(Boolean);

    win.requestAnimationFrame(() => {
      megaItems.forEach(({ item, mega, itemLeft }) => {
        mega.style.width = `${headerWidth}px`;
        mega.style.left = `${-itemLeft}px`;
        item.classList.add('mega-menu-loaded');
      });

      subMenus.forEach(({ menu, right }) => {
        toggle(menu, 'left-direction', right > headerRight);
      });

      flexDropdowns.forEach((entry) => {
        const {
          dropdown,
          dropdownWidth,
          half,
          parentLeft,
          parentHalf,
          headerWidth: hdrWidth,
          rightSpace,
          leftSpace
        } = entry;

        if (dropdownWidth >= hdrWidth) {
          dropdown.style.width = `${hdrWidth - 2}px`;
          dropdown.style.left = `${-parentLeft}px`;
          dropdown.style.right = 'auto';
        } else if (half > rightSpace) {
          dropdown.style.right = `${-rightSpace + parentHalf + 1}px`;
          dropdown.style.left = 'auto';
        } else if (half > leftSpace) {
          dropdown.style.left = `${-leftSpace + parentHalf + 1}px`;
          dropdown.style.right = 'auto';
        } else {
          dropdown.style.left = `${-half + parentHalf}px`;
          dropdown.style.right = 'auto';
        }
      });

      this._submenuPassQueued = false;
    });
  };

  Module.documentClick = function () {
    on(doc, 'click', (event) => {
      const target = event.target;
      if (
        target instanceof Element &&
        target.closest('.mobile-menu-trigger, .mobile-collapse, .more-section-outer, .header-dropdown-outer, .mfp-wrap')
      ) {
        return;
      }
      $$('.dropdown-activated').forEach((el) => {
        el.classList.remove('dropdown-activated');
        $$('[aria-expanded]', el).forEach((toggleEl) => toggleEl.setAttribute('aria-expanded', 'false'));
      });
      docEl.classList.remove('collapse-activated');
      bodyEl.classList.remove('collapse-activated');
      $$('.is-form-layout .live-search-response').forEach((el) => hide(el));
    });
  };

  Module.mobileCollapse = function () {
    const toggleMenu = (button) => {
      const isOpen = docEl.classList.contains('collapse-activated');
      docEl.classList.toggle('collapse-activated', !isOpen);
      bodyEl.classList.toggle('collapse-activated', !isOpen);
      if (button) button.setAttribute('aria-expanded', String(!isOpen));
    };

    delegate(doc, '.mobile-menu-trigger', 'click', (event, button) => {
      event.preventDefault();
      event.stopPropagation();
      toggleMenu(button);
      if (button.classList.contains('mobile-search-icon')) {
        const input = doc.querySelector('.mobile-search-form input[type="text"]');
        if (input && typeof input.focus === 'function') {
          setTimeout(() => input.focus(), 100);
        }
      }
    });

    const panel = $('.mobile-collapse') || $('#mobile-menu') || $('#offcanvas');
    if (panel) {
      on(panel, 'click', (event) => event.stopPropagation());
    }
  };

  /* --------------
   * Public init()
   * ------------- */

  Module.init = function () {
    this.initParams();
    this.headerDropdown();
    this.initSubMenuPos && this.initSubMenuPos();
    this.documentClick();
    this.mobileCollapse();
  };

  Module.initSubMenuPos = Module.initSubMenuPos || function () {
    const desktopMQ = win.matchMedia('(min-width: 1025px)');
    const runInitial = () => {
      if (!desktopMQ.matches) return;
      this.queueMenuMeasure();
    };
    // simple timeout, no requestIdleCallback
    win.setTimeout(runInitial, 800);
    let triggered = false;
    $$('.menu-has-child-mega').forEach((el) => {
      on(el, 'mouseenter', () => {
        if (!desktopMQ.matches) return;
        if (!triggered) this.queueMenuMeasure();
        triggered = true;
      });
    });
  };

}(FOXIZ_MAIN_SCRIPT, window, document));

/* ----------
 * Boot strap
 * --------- */

(function () {
  const win = window;
  const doc = document;

  const boot = () => {
    if (win.__FOXIZ_BOOTED__) return;
    if (!win.FOXIZ_MAIN_SCRIPT || typeof FOXIZ_MAIN_SCRIPT.init !== 'function') return;

    if (typeof FOXIZ_MAIN_SCRIPT.consumePreferenceQueue === 'function') {
      try {
        FOXIZ_MAIN_SCRIPT.consumePreferenceQueue();
      } catch (err) {
        if (win.console && typeof win.console.error === 'function') {
          console.error(err);
        }
      }
    }

    win.__FOXIZ_BOOTED__ = true;

    win.requestAnimationFrame(() => {
      try {
        FOXIZ_MAIN_SCRIPT.init();
      } catch (err) {
        if (win.console && typeof win.console.error === 'function') {
          console.error(err);
        }
      }
    });
  };

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
}());
