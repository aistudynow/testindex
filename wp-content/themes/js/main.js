/**
 * FOXIZ_MAIN_SCRIPT — modern, fast execution helpers
 */
var FOXIZ_MAIN_SCRIPT = window.FOXIZ_MAIN_SCRIPT || {};
(function (Module) {
  'use strict';

  const win = window;
  const doc = document;
  const docEl = doc.documentElement;
  const bodyEl = doc.body;

  const passiveEvents = new Set(['scroll', 'touchstart', 'touchmove', 'wheel']);

  const $ = (sel, ctx = doc) => ctx.querySelector(sel);
  const $$ = (sel, ctx = doc) => Array.from(ctx.querySelectorAll(sel));

  const on = (el, type, handler, opts) => {
    if (!el || !type || typeof handler !== 'function') return;
    const options = opts == null && passiveEvents.has(type) ? { passive: true } : opts || false;
    el.addEventListener(type, handler, options);
  };

  const delegate = (root, selector, type, handler, opts) => {
    if (!root || !selector || typeof handler !== 'function') return;
    on(root, type, (event) => {
      const path = event.composedPath ? event.composedPath() : null;
      const target = path ? path.find((node) => node instanceof Element && node.matches(selector)) : event.target;
      const match = target && target instanceof Element ? target.closest(selector) : null;
      if (match) handler(event, match);
    }, opts);
  };

  const toggle = (el, className, force) => el && el.classList.toggle(className, force);
  const show = (el) => { if (el) el.style.display = ''; };
  const hide = (el) => { if (el) el.style.display = 'none'; };

  const smoothScrollTo = (y) => win.scrollTo({ top: y, behavior: 'smooth' });

  const scheduleIdle = (cb, timeout = 160) => {
    if (typeof win.requestIdleCallback === 'function') {
      return win.requestIdleCallback(cb, { timeout });
    }
    return win.setTimeout(() => cb({ didTimeout: true, timeRemaining: () => 0 }), timeout);
  };

  const now = () => (
    win.performance && typeof win.performance.now === 'function'
      ? win.performance.now()
      : Date.now()
  );

  const raf = (cb) => win.requestAnimationFrame(() => win.requestAnimationFrame(cb));

  const slideUp = (el, duration = 200) => {
    if (!el) return;
    const style = el.style;
    const initialDisplay = getComputedStyle(el).display;
    if (initialDisplay === 'none') return;

    const height = el.offsetHeight;
    style.transition = `height ${duration}ms ease, margin ${duration}ms ease, padding ${duration}ms ease`;
    style.overflow = 'hidden';
    style.height = `${height}px`;
    style.boxSizing = 'border-box';

    raf(() => {
      style.height = '0px';
      style.paddingTop = '0px';
      style.paddingBottom = '0px';
      style.marginTop = '0px';
      style.marginBottom = '0px';
    });

    win.setTimeout(() => {
      hide(el);
      ['height', 'paddingTop', 'paddingBottom', 'marginTop', 'marginBottom', 'overflow', 'transition', 'boxSizing']
        .forEach((prop) => style.removeProperty(prop));
    }, duration);
  };

  const slideDown = (el, duration = 200) => {
    if (!el) return;
    const style = el.style;
    const computed = getComputedStyle(el);
    if (computed.display !== 'none') return;

    style.removeProperty('display');
    const display = computed.display === 'none' ? 'block' : computed.display;
    style.display = display;
    const height = el.scrollHeight;

    style.height = '0px';
    style.overflow = 'hidden';
    style.boxSizing = 'border-box';
    style.transition = `height ${duration}ms ease, margin ${duration}ms ease, padding ${duration}ms ease`;

    raf(() => {
      style.height = `${height}px`;
      style.paddingTop = '';
      style.paddingBottom = '';
      style.marginTop = '';
      style.marginBottom = '';
    });

    win.setTimeout(() => {
      ['height', 'overflow', 'transition', 'boxSizing'].forEach((prop) => style.removeProperty(prop));
    }, duration);
  };

  const slideToggle = (el, duration = 200) => {
    if (!el) return;
    if (getComputedStyle(el).display === 'none') slideDown(el, duration);
    else slideUp(el, duration);
  };

  Module.deferHeavyEmbeds = function () {};

  Module.normalizeInlineGridSelectors = function () {
    const styleEl = doc.getElementById('single-inline');
    if (!styleEl || styleEl.getAttribute('data-grid-scope') === '1') return;

    const source = styleEl.textContent || styleEl.innerHTML || '';
    if (!source || source.indexOf('.grid-container') === -1) {
      styleEl.setAttribute('data-grid-scope', '1');
      return;
    }

    const updated = source.replace(/\.grid-container\s*>\s*\*/g, '.grid-container > .s-ct, .grid-container > .sidebar-wrap');
    if (updated !== source) {
      styleEl.textContent = updated;
    }
    styleEl.setAttribute('data-grid-scope', '1');
  };

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
    localStorage.setItem(key, payload);
  };

  Module.getStorage = function (key, defaultValue) {
    if (!this.yesStorage) return defaultValue ?? null;
    const raw = localStorage.getItem(key);
    if (raw == null) return defaultValue ?? null;
    try { return JSON.parse(raw); } catch (err) { return raw; }
  };

  Module.deleteStorage = function (key) {
    if (this.yesStorage) localStorage.removeItem(key);
  };

  Module.runDeferredTasks = function (tasks, budget = 12) {
    if (!Array.isArray(tasks) || !tasks.length) return;
    const queue = tasks.filter((task) => typeof task === 'function');
    if (!queue.length) return;

    const execute = (deadline) => {
      const start = now();
      while (queue.length) {
        if (deadline && typeof deadline.timeRemaining === 'function' && !deadline.didTimeout && deadline.timeRemaining() <= 0) {
          break;
        }
        const job = queue.shift();
        try {
          job();
        } catch (err) {
          if (win.console && typeof win.console.error === 'function') {
            console.error(err);
          }
        }
        if (now() - start >= budget) break;
      }
      if (queue.length) scheduleIdle(execute, Math.max(48, budget * 8));
    };

    scheduleIdle(execute, Math.max(48, budget * 8));
  };

  Module.initParams = function () {
    this.yesStorage = this.isStorageAvailable();
    this.themeSettings = win.foxizParams || {};
    this.ajaxURL = (win.foxizCoreParams && win.foxizCoreParams.ajaxurl) || '/wp-admin/admin-ajax.php';
    this.ajaxData = {};
    this.readIndicator = $('#reading-progress');
    this.outerHTML = docEl;
    this.YTPlayers = {};
  };

  Module.fontResizer = function () {
    let step = this.yesStorage ? Number(sessionStorage.getItem('rubyResizerStep') || 1) : 1;
    delegate(doc, '.font-resizer-trigger', 'click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      step = step + 1;
      if (step > 3) {
        step = 1;
        bodyEl.classList.remove('medium-entry-size', 'big-entry-size');
      } else if (step === 2) {
        bodyEl.classList.add('medium-entry-size');
        bodyEl.classList.remove('big-entry-size');
      } else {
        bodyEl.classList.add('big-entry-size');
        bodyEl.classList.remove('medium-entry-size');
      }
      if (this.yesStorage) sessionStorage.setItem('rubyResizerStep', String(step));
    });
  };

  Module.hoverTipsy = function () {};

  Module.hoverEffects = function () {
    $$('.effect-fadeout').forEach((el) => {
      on(el, 'mouseenter', () => el.classList.add('activated'));
      on(el, 'mouseleave', () => el.classList.remove('activated'));
    });
  };

  Module.videoPreview = function () {
    let playPromise;
    delegate(doc, '.preview-trigger', 'mouseenter', (event, trigger) => {
      const wrap = trigger.querySelector('.preview-video');
      if (!wrap) return;
      if (!wrap.classList.contains('video-added')) {
        const video = doc.createElement('video');
        video.preload = 'auto';
        video.muted = true;
        video.loop = true;
        const source = doc.createElement('source');
        source.src = wrap.dataset.source || '';
        source.type = wrap.dataset.type || '';
        video.appendChild(source);
        wrap.appendChild(video);
        wrap.classList.add('video-added');
      }
      trigger.classList.add('show-preview');
      wrap.style.zIndex = '3';
      const videoEl = wrap.querySelector('video');
      if (videoEl) playPromise = videoEl.play();
    });

    delegate(doc, '.preview-trigger', 'mouseleave', (_, trigger) => {
      const wrap = trigger.querySelector('.preview-video');
      const videoEl = wrap ? wrap.querySelector('video') : null;
      if (wrap) wrap.style.zIndex = '1';
      if (videoEl && playPromise) {
        playPromise.then(() => videoEl.pause()).catch(() => {});
      }
    });
  };

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
      const holder = button.closest('.header-wrap')?.querySelector('.more-section-outer');
      openHolder(holder);
      if (button.classList.contains('search-btn')) {
        win.setTimeout(() => holder?.querySelector('input[type="text"]')?.focus(), 150);
      }
    });

    delegate(doc, '.search-trigger', 'click', (event, button) => {
      event.preventDefault();
      event.stopPropagation();
      this.queueMenuMeasure();
      const holder = button.closest('.header-dropdown-outer');
      openHolder(holder);
      if (holder?.classList.contains('dropdown-activated')) {
        win.setTimeout(() => holder.querySelector('input[type="text"]')?.focus(), 150);
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
    raf(() => this.calcSubMenuPos());
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

    const megaItems = $$('.menu-has-child-mega', navRoot).map((item) => {
      if (!isVisible(item)) return null;
      const mega = item.querySelector('.mega-dropdown');
      if (!mega) return null;
      const rect = item.getBoundingClientRect();
      return { item, mega, itemLeft: rect.left + scrollX };
    }).filter(Boolean);

    const subMenus = $$('ul.sub-menu', navRoot).map((menu) => {
      if (!isVisible(menu)) return null;
      const rect = menu.getBoundingClientRect();
      const left = rect.left + scrollX;
      return { menu, right: left + menu.offsetWidth + 100 };
    }).filter(Boolean);

    const flexDropdowns = $$('.flex-dropdown', navRoot).map((dropdown) => {
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
    }).filter(Boolean);

    raf(() => {
      megaItems.forEach(({ item, mega, itemLeft }) => {
        mega.style.width = `${headerWidth}px`;
        mega.style.left = `${-itemLeft}px`;
        item.classList.add('mega-menu-loaded');
      });

      subMenus.forEach(({ menu, right }) => {
        toggle(menu, 'left-direction', right > headerRight);
      });

      flexDropdowns.forEach((entry) => {
        const { dropdown, dropdownWidth, half, parentLeft, parentHalf, headerWidth: hdrWidth, rightSpace, leftSpace } = entry;
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
      if ((event.target instanceof Element) && event.target.closest('.mobile-menu-trigger, .mobile-collapse, .more-section-outer, .header-dropdown-outer, .mfp-wrap')) {
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
        win.setTimeout(() => $('.mobile-search-form input[type="text"]')?.focus(), 100);
      }
    });

    const panel = $('.mobile-collapse') || $('#mobile-menu') || $('#offcanvas');
    if (panel) on(panel, 'click', (event) => event.stopPropagation());
  };

  Module.tocToggle = function () {
    delegate(doc, '.toc-toggle', 'click', (event, button) => {
      event.preventDefault();
      event.stopPropagation();
      const targetId = button.getAttribute('data-target') || button.getAttribute('aria-controls') || '';
      let content = null;
      if (targetId) {
        const selector = targetId.startsWith('#') ? targetId : `#${targetId}`;
        content = $(selector);
      }
      if (!content) {
        const holder = button.closest('.ruby-table-contents') || button.parentElement;
        content = holder ? holder.querySelector('.toc-content') : null;
      }
      if (!content) return;
      const willOpen = getComputedStyle(content).display === 'none';
      slideToggle(content, 200);
      toggle(button, 'activate', willOpen);
      button.setAttribute('aria-expanded', String(willOpen));
    });
  };

  Module.loginPopup = function () {
    const form = $('#rb-user-popup-form');
    if (!form) return;

    const ensureModal = () => {
      let overlay = $('#rb-login-overlay');
      if (overlay) return overlay;
      overlay = doc.createElement('div');
      overlay.id = 'rb-login-overlay';
      overlay.innerHTML = `
        <div class="rb-modal">
          <button class="close-popup-btn" aria-label="Close"><span class="close-icon"></span></button>
          <div class="rb-modal-body"></div>
        </div>`;
      overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;opacity:0;z-index:99999;';
      const modal = overlay.querySelector('.rb-modal');
      modal.style.cssText = 'position:relative;margin:5vh auto;max-width:520px;background:#fff;border-radius:8px;padding:20px;';
      bodyEl.appendChild(overlay);
      return overlay;
    };

    const open = () => {
      const overlay = ensureModal();
      const body = overlay.querySelector('.rb-modal-body');
      body.innerHTML = '';
      body.appendChild(form);
      overlay.style.display = 'block';
      raf(() => { overlay.style.opacity = '1'; });
      try { if (typeof win.turnstile !== 'undefined') win.turnstile.reset(); } catch (err) {}
      try { if (typeof win.grecaptcha !== 'undefined') win.grecaptcha.reset(); } catch (err) {}
    };

    const close = () => {
      const overlay = $('#rb-login-overlay');
      if (!overlay) return;
      overlay.style.opacity = '0';
      win.setTimeout(() => { overlay.style.display = 'none'; }, 150);
    };

    delegate(doc, '.login-toggle', 'click', (event) => { event.preventDefault(); event.stopPropagation(); open(); });
    delegate(doc, '#rb-login-overlay .close-popup-btn', 'click', (event) => { event.preventDefault(); close(); });
    on(doc, 'keydown', (event) => { if (event.key === 'Escape') close(); });
    delegate(doc, '#rb-login-overlay', 'click', (event, overlay) => { if (event.target === overlay) close(); });
  };

  Module.loadYoutubeIframe = function () {
    const playlists = $$('.yt-playlist');
    if (!playlists.length) return;

    if (!doc.getElementById('yt-iframe-api')) {
      const script = doc.createElement('script');
      script.src = 'https://www.youtube.com/iframe_api';
      script.id = 'yt-iframe-api';
      const first = doc.getElementsByTagName('script')[0];
      first.parentNode.insertBefore(script, first);
    }

    win.onYouTubeIframeAPIReady = () => {
      playlists.forEach((playlist) => {
        const iframe = playlist.querySelector('.yt-player');
        const videoId = playlist.dataset.id;
        const blockId = playlist.dataset.block;
        if (!iframe || !videoId || !blockId) return;
        this.YTPlayers[blockId] = new YT.Player(iframe, {
          height: '540',
          width: '960',
          videoId,
          events: {
            onReady: () => {},
            onStateChange: () => {}
          }
        });
      });

      delegate(doc, '.plist-item', 'click', (event, item) => {
        event.preventDefault();
        event.stopPropagation();
        const wrapper = item.closest('.yt-playlist');
        if (!wrapper) return;
        const blockId = wrapper.dataset.block;
        const targetVideo = item.dataset.id;
        const title = item.querySelector('.plist-item-title')?.textContent || '';
        const meta = item.dataset.index || '';
        Object.values(this.YTPlayers).forEach((player) => player.pauseVideo && player.pauseVideo());
        const player = this.YTPlayers[blockId];
        if (player && targetVideo) {
          player.loadVideoById({ videoId: targetVideo });
        }
        wrapper.querySelector('.yt-trigger')?.classList.add('is-playing');
        const titleEl = wrapper.querySelector('.play-title');
        if (titleEl) {
          hide(titleEl);
          titleEl.textContent = title;
          show(titleEl);
        }
        const indexEl = wrapper.querySelector('.video-index');
        if (indexEl) indexEl.textContent = meta;
      });
    };
  };

  Module.videoPlayToggle = function () {
    const players = this.YTPlayers;
    delegate(doc, '.yt-trigger', 'click', (event, trigger) => {
      event.preventDefault();
      event.stopPropagation();
      const wrapper = trigger.closest('.yt-playlist');
      const blockId = wrapper ? wrapper.dataset.block : null;
      const player = blockId ? players[blockId] : null;
      if (!player) return;
      const state = player.getPlayerState ? player.getPlayerState() : -1;
      const playing = state === 1 || state === 3;
      if (playing) {
        player.pauseVideo();
        trigger.classList.remove('is-playing');
      } else {
        player.playVideo();
        trigger.classList.add('is-playing');
      }
    });
  };

  Module.showPostComment = function () {
    delegate(doc, '.smeta-sec .meta-comment', 'click', (event) => {
      const button = $('.show-post-comment');
      if (!button) return;
      const offset = button.getBoundingClientRect().top + win.scrollY;
      smoothScrollTo(offset);
      button.click();
    });

    delegate(doc, '.show-post-comment', 'click', (event, button) => {
      event.preventDefault();
      event.stopPropagation();
      const wrapper = button.parentElement;
      hide(button);
      button.remove();
      wrapper?.querySelectorAll('.is-invisible').forEach((el) => el.classList.remove('is-invisible'));
      const holder = wrapper?.nextElementSibling?.classList.contains('comment-holder') ? wrapper.nextElementSibling : null;
      if (holder) holder.classList.remove('is-hidden');
    });
  };

  Module.scrollToComment = function () {
    const hash = win.location.hash || '';
    if (hash === '#respond' || hash.startsWith('#comment')) {
      const button = $('.show-post-comment');
      if (!button) return;
      const offset = button.getBoundingClientRect().top + win.scrollY - 200;
      smoothScrollTo(offset);
      button.click();
    }
  };

  Module.ensureSearchAutocomplete = function () {
    const selectors = [
      '#wpadminbar form#adminbarsearch input.adminbar-input',
      'form.rb-search-form.live-search-form input.field[name="s"]',
      'form.rb-search-form input.field[name="s"]'
    ];
    const seen = new Set();
    selectors.forEach((selector) => {
      $$(selector).forEach((input) => {
        if (!input || input.tagName !== 'INPUT' || seen.has(input)) return;
        if (input.autocomplete !== 'search') {
          input.setAttribute('autocomplete', 'search');
        }
        seen.add(input);
      });
    });
  };

  Module.init = function () {
    this.initParams();

    const tasks = [
      () => this.normalizeInlineGridSelectors(),
      () => this.tocToggle(),
      () => this.deferHeavyEmbeds(),
      () => this.ensureSearchAutocomplete(),
      () => this.hoverEffects(),
      () => this.videoPreview(),
      () => this.headerDropdown(),
      () => this.initSubMenuPos && this.initSubMenuPos(),
      () => this.documentClick(),
      () => this.mobileCollapse(),
      () => this.loginPopup(),
      () => this.loadYoutubeIframe(),
      () => this.videoPlayToggle(),
      () => this.showPostComment(),
      () => this.scrollToComment(),
      () => this.fontResizer()
    ];

    this.runDeferredTasks(tasks, 14);
  };

  Module.initSubMenuPos = Module.initSubMenuPos || function () {
    const desktopMQ = win.matchMedia('(min-width: 1025px)');
    const runInitial = () => {
      if (!desktopMQ.matches) return;
      this.queueMenuMeasure();
    };
    if ('requestIdleCallback' in win) {
      win.requestIdleCallback(runInitial, { timeout: 1500 });
    } else {
      win.setTimeout(runInitial, 1200);
    }
    let triggered = false;
    $$('.menu-has-child-mega').forEach((el) => {
      on(el, 'mouseenter', () => {
        if (!desktopMQ.matches) return;
        if (!triggered) this.queueMenuMeasure();
        triggered = true;
      });
    });
  };

}(FOXIZ_MAIN_SCRIPT));

(function () {
  const win = window;
  const doc = document;

  const boot = () => {
    if (win.__FOXIZ_BOOTED__) return;
    if (win.FOXIZ_MAIN_SCRIPT && typeof FOXIZ_MAIN_SCRIPT.init === 'function') {
      win.__FOXIZ_BOOTED__ = true;
      const start = () => {
        try {
          FOXIZ_MAIN_SCRIPT.init();
        } catch (err) {
          if (win.console && typeof win.console.error === 'function') {
            console.error(err);
          }
        }
      };
      if (typeof win.requestIdleCallback === 'function') {
        win.requestIdleCallback(start, { timeout: 200 });
      } else {
        win.setTimeout(start, 0);
      }
    }
  };

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();