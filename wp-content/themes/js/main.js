/** FOXIZ_MAIN_SCRIPT — Vanilla (no jQuery) */
var FOXIZ_MAIN_SCRIPT = window.FOXIZ_MAIN_SCRIPT || {};
(function (Module) {
  'use strict';

  /* =========================================
   * CORE POLYFILLS & SAFE DELEGATION HELPERS
   * ========================================= */
  (function () {
    if (!Element.prototype.matches) {
      Element.prototype.matches =
        Element.prototype.msMatchesSelector ||
        Element.prototype.webkitMatchesSelector ||
        function (s) {
          const m = (this.document || this.ownerDocument).querySelectorAll(s);
          let i = m.length;
          while (--i >= 0 && m.item(i) !== this) {}
          return i > -1;
        };
    }
    if (!Element.prototype.closest) {
      Element.prototype.closest = function (s) {
        let el = this;
        while (el && el.nodeType === 1) {
          if (el.matches(s)) return el;
          el = el.parentElement || el.parentNode;
        }
        return null;
      };
    }
    if (window.Node && !Node.prototype.closest) {
      Node.prototype.closest = function (selector) {
        if (this.nodeType === 1 && Element.prototype.closest) {
          return Element.prototype.closest.call(this, selector);
        }
        return this.parentElement ? this.parentElement.closest(selector) : null;
      };
    }
    if (window.Node && !Node.prototype.matches) {
      Node.prototype.matches = function (selector) {
        return this.nodeType === 1 && Element.prototype.matches
          ? Element.prototype.matches.call(this, selector)
          : false;
      };
    }
  })();

  const $  = (sel, ctx) => (ctx || document).querySelector(sel);
  const $$ = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));
  // Replace your current `on()` with this:
const on = (el, ev, fn, opts) => {
  if (!el) return;
  const wantPassive = (opts == null) && (
    ev === 'scroll' || ev === 'touchstart' || ev === 'touchmove' || ev === 'wheel'
  );
  el.addEventListener(ev, fn, wantPassive ? { passive: true } : (opts || false));
};
  const off = (el, ev, fn) => el && el.removeEventListener(ev, fn);

  const safeClosest = (node, selector, stopAt = document) => {
    let n = node || null;
    if (!n) return null;
    if (n.nodeType && n.nodeType !== 1) n = n.parentElement || n.parentNode;
    while (n && n !== stopAt) {
      if (n.nodeType === 1 && n.matches && n.matches(selector)) return n;
      n = n.parentElement || n.parentNode;
    }
    return null;
  };

  const delegate = (root, sel, ev, fn) =>
    on(root, ev, (e) => {
      const match = safeClosest(e.target, sel, root);
      if (match && root.contains(match)) fn(e, match);
    });

  const toggle = (el, cls, force) => el && el.classList.toggle(cls, force);
  const show   = (el) => { if (el) el.style.display = ''; };
  const hide   = (el) => { if (el) el.style.display = 'none'; };

  const htmlEl = document.documentElement;
  const bodyEl = document.body;
  const smoothScrollTo = (y) => window.scrollTo({ top: y, behavior: 'smooth' });
  
  
  
  
  


 const scheduleIdle = (cb, opts) => {
    if (typeof window.requestIdleCallback === 'function') {
      return window.requestIdleCallback(cb, opts || { timeout: 160 });
    }

    const timeout = opts && typeof opts.timeout === 'number' ? opts.timeout : 0;

    return window.setTimeout(() => {
      cb({
        didTimeout: true,
        timeRemaining: () => 0,
      });
    }, timeout);
  };

  const now = () => (
    typeof window.performance !== 'undefined' &&
    typeof window.performance.now === 'function'
      ? window.performance.now()
      : Date.now()
  );







const nativeDocumentWrite = document.write;
  const nativeDocumentWriteln = document.writeln;
  
  
  
  
  const docWriteQueue = [];
  let docWriteFlushScheduled = false;

  const scheduleDocWriteFlush = () => {
    if (docWriteFlushScheduled) return;
    docWriteFlushScheduled = true;

    const runOnce = (cb) => {
      let done = false;
      const finish = () => {
        if (done) return;
        done = true;
        cb();
      };

      if (typeof window.requestAnimationFrame === 'function') {
        window.requestAnimationFrame(finish);
      }

      if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(finish, { timeout: 72 });
      }

      window.setTimeout(finish, 48);
    };
    
    
    
    
    
    
    
    

    const getFragment = (html, contextNode) => {
      if (!html) return null;

      if (document.createRange) {
        const range = document.createRange();
        const context = contextNode && contextNode.nodeType === 1
          ? contextNode
          : document.body || document.documentElement;

        if (context) {
          range.selectNodeContents(context);
          const frag = range.createContextualFragment(html);
          if (typeof range.detach === 'function') {
            range.detach();
          }
          return frag;
        }
      }

      const template = document.createElement('template');
      template.innerHTML = html;
      return template.content;
    };

    runOnce(() => {
      docWriteFlushScheduled = false;
      if (!docWriteQueue.length) return;

      const tasks = docWriteQueue.splice(0);

      const scriptBuckets = new Map();
      const targetBuckets = new Map();

      tasks.forEach(({ html, ref, target }) => {
        if (!html) return;

        if (ref && ref.parentNode) {
          const existing = scriptBuckets.get(ref);
          if (existing) {
            existing.html += html;
          } else {
            scriptBuckets.set(ref, { ref, html });
          }
          return;
        }

        const destination = (target && target.isConnected) ? target : (document.body || document.documentElement);
        if (!destination) return;

        const existing = targetBuckets.get(destination);
        if (existing) {
          existing.html += html;
        } else {
          targetBuckets.set(destination, { target: destination, html });
        }
      });

      scriptBuckets.forEach(({ ref, html }) => {
        const parent = ref.parentNode;
        if (!parent) return;
        const fragment = getFragment(html, parent);
        if (fragment) {
          parent.insertBefore(fragment, ref.nextSibling);
        }
      });

      targetBuckets.forEach(({ target, html }) => {
        const fragment = getFragment(html, target);
        if (fragment) {
          target.appendChild(fragment);
        }
      });
    });
  };
  
  
  
  
  
  

  const safeDocumentWriter = (chunks, addNewLine) => {
    const normalized = Array.isArray(chunks) && chunks.length ? chunks : [''];
    const markup = normalized
      .map((chunk) => (chunk == null ? '' : String(chunk)))
      .join('');

    if (document.readyState === 'loading') {
      if (addNewLine && typeof nativeDocumentWriteln === 'function') {
        nativeDocumentWriteln.call(document, markup);
      } else if (typeof nativeDocumentWrite === 'function') {
        nativeDocumentWrite.call(
          document,
          addNewLine && typeof nativeDocumentWriteln !== 'function' ? `${markup}\n` : markup
        );
      }
      return;
    }

    const html = addNewLine ? `${markup}\n` : markup;
    if (!html) return;

    const script = document.currentScript;
    if (script && script.parentNode) {
      docWriteQueue.push({ html, ref: script });
      scheduleDocWriteFlush();
      return;
    }

    if (document.body || document.documentElement) {
      docWriteQueue.push({ html, target: document.body || document.documentElement });
      scheduleDocWriteFlush();
    } else if (addNewLine && typeof nativeDocumentWriteln === 'function') {
      nativeDocumentWriteln.call(document, markup);
    } else if (typeof nativeDocumentWrite === 'function') {
      nativeDocumentWrite.call(document, html);
    }
  };

  document.write = function (...args) {
    safeDocumentWriter(args, false);
  };

  document.writeln = function (...args) {
    safeDocumentWriter(args, true);
  };



  
  
  
// Heavy embeds (such as ad units) need to render immediately to keep vendor
  // scripts functional, so this hook intentionally does nothing.
  Module.deferHeavyEmbeds = function () {};

  const GRID_CHILD_SELECTOR = /\.grid-container\s*>\s*\*/g;
  const GRID_CHILD_RAW = '.grid-container > *';

  Module.normalizeInlineGridSelectors = function () {
    const styleEl = document.getElementById('single-inline');
    if (!styleEl || styleEl.getAttribute('data-grid-scope') === '1') return;

    const scopedSelector = '.grid-container > .s-ct, .grid-container > .sidebar-wrap';
    let mutated = false;

    const tryRuleList = (list) => {
      if (!list || typeof list.length !== 'number') return;
      for (let i = 0; i < list.length; i += 1) {
        const rule = list[i];
        if (!rule) continue;

        if (
          typeof CSSRule !== 'undefined' &&
          rule.type === CSSRule.STYLE_RULE &&
          rule.selectorText &&
          GRID_CHILD_SELECTOR.test(rule.selectorText)
        ) {
          GRID_CHILD_SELECTOR.lastIndex = 0;
          const updatedSelector = rule.selectorText.replace(GRID_CHILD_SELECTOR, scopedSelector);
          GRID_CHILD_SELECTOR.lastIndex = 0;
          if (updatedSelector !== rule.selectorText) {
            try {
              rule.selectorText = updatedSelector;
              mutated = true;
            } catch (err) {}
          }
        } else if (
          typeof CSSRule !== 'undefined' &&
          (rule.type === CSSRule.MEDIA_RULE || rule.type === CSSRule.SUPPORTS_RULE)
        ) {
          tryRuleList(rule.cssRules || rule.rules || null);
          if (mutated) return;
        }

        if (mutated) return;
      }
    };

    try {
      const sheet = styleEl.sheet || styleEl.styleSheet || null;
      if (sheet && (sheet.cssRules || sheet.rules)) {
        tryRuleList(sheet.cssRules || sheet.rules || null);
      }
    } catch (err) {}

    const source = styleEl.textContent || styleEl.innerHTML || '';
    if (mutated || source.indexOf(GRID_CHILD_RAW) === -1) {
      styleEl.setAttribute('data-grid-scope', '1');
    }
  };
    
    
    
    
    
    

  
  
  

  /* =================
   * SLIDE HELPERS
   * ================= */
  function slideUp(el, duration=200){
  if (!el) return;
  // READ frame
  requestAnimationFrame(() => {
    const height = el.offsetHeight; // read once

    // WRITE frame
    requestAnimationFrame(() => {
      el.style.height = height + 'px';
      el.style.transition = `height ${duration}ms ease, margin ${duration}ms ease, padding ${duration}ms ease`;
      el.style.overflow = 'hidden';

      // next frame to collapse
      requestAnimationFrame(() => {
        el.style.height = '0px';
        el.style.paddingTop = el.style.paddingBottom = el.style.marginTop = el.style.marginBottom = 0;
      });

      setTimeout(() => {
        el.style.display = 'none';
        ['height','paddingTop','paddingBottom','marginTop','marginBottom','overflow','transition']
          .forEach(p=>el.style.removeProperty(p));
      }, duration);
    });
  });
}

  function slideDown(el, duration=200){
  if (!el) return;
  el.style.removeProperty('display');
  const cs = getComputedStyle(el);
  el.style.display = cs.display === 'none' ? 'block' : cs.display;

  requestAnimationFrame(() => {                 // READ frame
    const height = el.scrollHeight;             // read once

    // prepare writes in next frame
    requestAnimationFrame(() => {               // WRITE frame
      el.style.overflow = 'hidden';
      el.style.height = '0px';
      el.style.paddingTop = el.style.paddingBottom = el.style.marginTop = el.style.marginBottom = 0;
      el.style.transition = `height ${duration}ms ease, margin ${duration}ms ease, padding ${duration}ms ease`;
      el.style.height = height + 'px';
      setTimeout(() => {
        ['height','overflow','transition','paddingTop','paddingBottom','marginTop','marginBottom']
          .forEach(p=>el.style.removeProperty(p));
      }, duration);
    });
  });
}

  function slideToggle(el, duration=200){
    if (!el) return;
    if (getComputedStyle(el).display === 'none') slideDown(el, duration);
    else slideUp(el, duration);
  }

  /* ===========================
   * MINIMAL STORAGE
   * =========================== */
  Module.isStorageAvailable = function () {
    try { localStorage.setItem('__rb__t', '1'); localStorage.removeItem('__rb__t'); return true; }
    catch (e) { return false; }
  };
  Module.setStorage = function (key, data) {
    if (!this.yesStorage) return;
    localStorage.setItem(key, typeof data === 'string' ? data : JSON.stringify(data));
  };
  Module.getStorage = function (key, defVal) {
    if (!this.yesStorage) return null;
    const raw = localStorage.getItem(key);
    if (raw === null) return defVal;
    try { return JSON.parse(raw); } catch { return raw; }
  };
  Module.deleteStorage = function (key) { if (this.yesStorage) localStorage.removeItem(key); };
  
  
  
  
  
  
  
  
  Module.runDeferredTasks = function (tasks, budget = 12) {
    if (!Array.isArray(tasks) || !tasks.length) return;

    const queue = tasks.filter((task) => typeof task === 'function');
    if (!queue.length) return;

    const timeout = Math.max(32, budget * 8);

    const runBatch = (deadline) => {
      const start = now();
      while (queue.length) {
        if (
          deadline &&
          typeof deadline.timeRemaining === 'function' &&
          deadline.timeRemaining() <= 0 &&
          !deadline.didTimeout
        ) {
          break;
        }
        const task = queue.shift();
        try { task(); } catch (err) {}
        if (now() - start >= budget) {
          break;
        }
      }

      if (queue.length) {
        scheduleIdle(runBatch, { timeout });
      }
    };

    scheduleIdle(runBatch, { timeout });
  };
  
  
  

  /* ===========================
   * INIT PARAMS
   * =========================== */
  Module.initParams = function () {
    this.yesStorage = this.isStorageAvailable();
    this.themeSettings = typeof window.foxizParams !== 'undefined' ? window.foxizParams : {};
    this.ajaxURL = (window.foxizCoreParams && window.foxizCoreParams.ajaxurl) || '/wp-admin/admin-ajax.php';
    this.ajaxData = {};
    this.readIndicator = $('#reading-progress');
    this.outerHTML = document.documentElement;
    this.YTPlayers = {};
  };

  /* ===========================
   * FONT RESIZER
   * =========================== */
  Module.fontResizer = function () {
    let size = this.yesStorage ? (sessionStorage.getItem('rubyResizerStep') || 1) : 1;
    delegate(document, '.font-resizer-trigger', 'click', (e) => {
      e.preventDefault(); e.stopPropagation();
      size = parseInt(size, 10) + 1;
      if (size > 3) {
        size = 1;
        bodyEl.classList.remove('medium-entry-size', 'big-entry-size');
      } else if (size === 2) {
        bodyEl.classList.add('medium-entry-size');
        bodyEl.classList.remove('big-entry-size');
      } else if (size === 3) {
        bodyEl.classList.add('big-entry-size');
        bodyEl.classList.remove('medium-entry-size');
      }
      if (this.yesStorage) sessionStorage.setItem('rubyResizerStep', size);
    });
  };

  /* ===========================
 * HOVER TIPS  (deferred & desktop-only)
 * =========================== */
Module.hoverTipsy = function () {
  // disabled for performance; tooltips handled by browser defaults
};


  /* ===========================
   * HOVER EFFECTS
   * =========================== */
  Module.hoverEffects = function () {
    $$('.effect-fadeout').forEach(el => {
      on(el, 'mouseenter', (e) => { e.stopPropagation(); el.classList.add('activated'); });
      on(el, 'mouseleave', () => el.classList.remove('activated'));
    });
  };

  /* ===========================
   * VIDEO PREVIEW
   * =========================== */
  Module.videoPreview = function () {
    let playPromise;
    delegate(document, '.preview-trigger', 'mouseenter', (e, trigger) => {
      const wrap = trigger.querySelector('.preview-video');
      if (!wrap) return;
      if (!wrap.classList.contains('video-added')) {
        const video = document.createElement('video');
        video.preload = 'auto'; video.muted = true; video.loop = true;
        const src = document.createElement('source');
        src.src = wrap.dataset.source || ''; src.type = wrap.dataset.type || '';
        video.appendChild(src);
        wrap.appendChild(video);
        wrap.classList.add('video-added');
      }
      trigger.classList.add('show-preview');
      wrap.style.zIndex = 3;
      const el = wrap.querySelector('video');
      if (el) playPromise = el.play();
    });
    delegate(document, '.preview-trigger', 'mouseleave', (e, trigger) => {
      const el = trigger.querySelector('video');
      const wrap = trigger.querySelector('.preview-video');
      if (wrap) wrap.style.zIndex = 1;
      if (el && playPromise !== undefined) {
        playPromise.then(_ => el.pause()).catch(() => {});
      }
    });
  };

  /* ===========================
   * HEADER DROPDOWNS
   * =========================== */
   
   


   
   
   
  Module.headerDropdown = function () {
  const closeAll = () => $$('.dropdown-activated').forEach(el => el.classList.remove('dropdown-activated'));

  // already good: we keep rAF here
  delegate(document, '.more-trigger', 'click', (e, btn) => {
    e.preventDefault(); e.stopPropagation();
    
    this.queueMenuMeasure();

    const holder = safeClosest(btn, '.header-wrap')?.querySelector('.more-section-outer');
    if (!holder) return;
    if (!holder.classList.contains('dropdown-activated')) {
      closeAll(); holder.classList.add('dropdown-activated');
    } else {
      holder.classList.remove('dropdown-activated');
    }
    if (btn.classList.contains('search-btn')) {
      setTimeout(() => holder.querySelector('input[type="text"]')?.focus(), 150);
    }
  });

  // ⬇️ ADD this rAF line right after stopPropagation (this is the bit you asked about)
  delegate(document, '.search-trigger', 'click', (e, btn) => {
    e.preventDefault(); e.stopPropagation();
    
    this.queueMenuMeasure();

    const holder = safeClosest(btn, '.header-dropdown-outer');
    if (!holder) return;
    if (!holder.classList.contains('dropdown-activated')) {
      closeAll(); holder.classList.add('dropdown-activated');
      setTimeout(() => holder.querySelector('input[type="text"]')?.focus(), 150);
    } else {
      holder.classList.remove('dropdown-activated');
    }
  });

  // already added by you (good) — keep it
  delegate(document, '.dropdown-trigger', 'click', (e, btn) => {
    e.preventDefault(); e.stopPropagation();
    
    this.queueMenuMeasure();

    const holder = safeClosest(btn, '.header-dropdown-outer');
    if (!holder) return;
    if (!holder.classList.contains('dropdown-activated')) {
      closeAll(); holder.classList.add('dropdown-activated');
    } else {
      holder.classList.remove('dropdown-activated');
    }
  });
};


  /* ===========================
   * MEGA MENU POSITIONING
   * =========================== */
  Module.initSubMenuPos = function () {
  const desktopMQ = window.matchMedia('(min-width: 1025px)');

  // run once after load (desktop only)
  const runInitial = () => {
    if (!desktopMQ.matches) return;
    this.queueMenuMeasure();
  };

  if ('requestIdleCallback' in window) {
    requestIdleCallback(runInitial, { timeout: 1500 });
  } else {
    setTimeout(runInitial, 1200);
  }

  let triggered = false;
  $$('.menu-has-child-mega').forEach(el => {
    on(el, 'mouseenter', () => {
      if (!desktopMQ.matches) return;
      if (!triggered) this.queueMenuMeasure();
      triggered = true;
    });
  });


};




Module._submenuPassQueued = false;

Module.queueMenuMeasure = function () {
  if (this._submenuPassQueued) return;
  this._submenuPassQueued = true;
  requestAnimationFrame(() => {
    requestAnimationFrame(() => this.calcSubMenuPos());
  });
};

Module.calcSubMenuPos = function () {
  if (window.innerWidth < 1025) {
    this._submenuPassQueued = false;
    return false;
  }

  const hasTargets = document.querySelector('.menu-has-child-mega, ul.sub-menu, .flex-dropdown');
  if (!hasTargets) {
    this._submenuPassQueued = false;
    return;
  }

  const navRoot = $('#site-header') || document;
  const isVisible = (el) => el && el.offsetParent !== null;

  requestAnimationFrame(() => {
    // READS...
    const scrollX = window.scrollX || window.pageXOffset || 0;
    const bodyWidth = bodyEl.clientWidth;
    const header = $('#site-header');
    const hRect = header ? header.getBoundingClientRect() : { left: 0, width: bodyWidth };
    const headerLeft  = (hRect.left || 0) + scrollX;
    const headerRight = headerLeft + (hRect.width || bodyWidth);
    const headerWidth = (hRect.width || bodyWidth);

    const megaReads = $$('.menu-has-child-mega', navRoot).map(item => {
      if (!isVisible(item)) return null;
      const mega = item.querySelector('.mega-dropdown');
      if (!mega) return null;
      const rect = item.getBoundingClientRect();
      return { item, mega, bodyWidth, itemLeft: rect.left + scrollX };
    }).filter(Boolean);

    const subReads = $$('ul.sub-menu', navRoot).map(item => {
      if (!isVisible(item)) return null;
      const r = item.getBoundingClientRect();
      const left = r.left + scrollX;
      const width = item.offsetWidth;
      return { item, right: left + width + 100, headerRight };
    }).filter(Boolean);

    const flexReads = $$('.flex-dropdown', navRoot).map(item => {
      if (!isVisible(item)) return null;
      const parent = item.parentElement;
      if (!parent || parent.classList.contains('is-child-wide') || item.classList.contains('mega-has-left')) return null;
      const itemWidth = item.offsetWidth;
      const iHalf = itemWidth / 2;
      const pRect = parent.getBoundingClientRect();
      const pLeft = pRect.left + scrollX;
      const pHalf = (parent.offsetWidth || 0) / 2;
      const pCenter = pLeft + pHalf;
      const rightSpace = headerRight - pCenter;
      const leftSpace  = pCenter - headerLeft;
      return { item, itemWidth, iHalf, pLeft, pHalf, headerWidth, rightSpace, leftSpace };
    }).filter(Boolean);

    requestAnimationFrame(() => {
      // WRITES...
      megaReads.forEach(m => {
        m.mega.style.width = m.bodyWidth + 'px';
        m.mega.style.left  = (-m.itemLeft) + 'px';
        m.item.classList.add('mega-menu-loaded');
      });

      subReads.forEach(s => {
        if (s.right > s.headerRight) s.item.classList.add('left-direction');
        else s.item.classList.remove('left-direction');
      });

      flexReads.forEach(f => {
        if (f.itemWidth >= f.headerWidth) {
          f.item.style.width = (f.headerWidth - 2) + 'px';
          f.item.style.left  = (-f.pLeft) + 'px';
          f.item.style.right = 'auto';
        } else if (f.iHalf > f.rightSpace) {
          f.item.style.right = (-f.rightSpace + f.pHalf + 1) + 'px';
          f.item.style.left  = 'auto';
        } else if (f.iHalf > f.leftSpace) {
          f.item.style.left  = (-f.leftSpace + f.pHalf + 1) + 'px';
          f.item.style.right = 'auto';
        } else {
          f.item.style.left  = (-f.iHalf + f.pHalf) + 'px';
          f.item.style.right = 'auto';
        }
      });

      this._submenuPassQueued = false;
    });
  });
};


  /* ===========================
   * OUTSIDE CLICK CLOSES
   * =========================== */
  Module.documentClick = function () {
    document.addEventListener('click', function (e) {
      if (safeClosest(e.target, '.mobile-menu-trigger, .mobile-collapse, .more-section-outer, .header-dropdown-outer, .mfp-wrap', document)) {
        return;
      }
      document.querySelectorAll('.dropdown-activated').forEach(el => el.classList.remove('dropdown-activated'));
      document.documentElement.classList.remove('collapse-activated');
      document.body.classList.remove('collapse-activated');
      document.querySelectorAll('.is-form-layout .live-search-response').forEach(el => { el.style.display = 'none'; });
    });
  };

  /* ===========================
   * MOBILE COLLAPSE
   * =========================== */
  Module.mobileCollapse = function () {
    const root = document.documentElement;
    const rootBody = document.body;

    document.addEventListener('click', function (e) {
      const btn = safeClosest(e.target, '.mobile-menu-trigger', document);
      if (!btn) return;

      e.preventDefault();
      e.stopPropagation();

      const isOpen = root.classList.contains('collapse-activated') || rootBody.classList.contains('collapse-activated');

      root.classList.toggle('collapse-activated', !isOpen);
      rootBody.classList.toggle('collapse-activated', !isOpen);

      btn.setAttribute('aria-expanded', String(!isOpen));

      if (btn.classList.contains('mobile-search-icon')) {
        setTimeout(() => {
          const input = document.querySelector('.mobile-search-form input[type="text"]');
          if (input) input.focus();
        }, 100);
      }
    });

    const panel = document.querySelector('.mobile-collapse') || document.querySelector('#mobile-menu') || document.querySelector('#offcanvas');
    if (panel) {
      panel.addEventListener('click', (e) => e.stopPropagation());
    }
  };

  /* ===========================
   * PRIVACY TRIGGER
   * =========================== */
  Module.privacyTrigger = function () {
    const trigger = $('#privacy-trigger');
    on(trigger, 'click', (e) => {
      e.preventDefault(); e.stopPropagation();
      this.setStorage('RubyPrivacyAllowed', '1');
      const bar = $('#rb-privacy');
      if (!bar) return false;
      bar.style.transition = 'height .2s ease, opacity .2s ease';
      bar.style.overflow = 'hidden';
      bar.style.opacity = '0';
      bar.style.height = '0px';
      setTimeout(() => bar.remove(), 220);
      return false;
    });
  };

  /* ===========================
   * TOC TOGGLE
   * =========================== */
  Module.tocToggle = function () {
    document.addEventListener('click', function (e) {
      const btn = safeClosest(e.target, '.toc-toggle', document);
      if (!btn) return;

      e.preventDefault();
      e.stopPropagation();

      let content = null;
      const targetSel = btn.getAttribute('data-target') || btn.getAttribute('aria-controls');
      if (targetSel) {
        content = document.querySelector(targetSel.startsWith('#') ? targetSel : `#${targetSel}`);
      }
      if (!content) {
        const holder = safeClosest(btn, '.ruby-table-contents', document) || btn.parentElement || document;
        content = holder.querySelector('.toc-content');
      }
      if (!content) return;

      const willOpen = getComputedStyle(content).display === 'none';
      slideToggle(content, 200);
      btn.classList.toggle('activate', willOpen);
      btn.setAttribute('aria-expanded', String(willOpen));
    });
  };

  /* ===========================
   * LOGIN POPUP
   * =========================== */
  Module.loginPopup = function () {
    const form = $('#rb-user-popup-form');
    if (!form) return;

    const ensureModal = () => {
      let overlay = $('#rb-login-overlay');
      if (overlay) return overlay;
      overlay = document.createElement('div');
      overlay.id = 'rb-login-overlay';
      overlay.innerHTML = `
        <div class="rb-modal">
          <button class="close-popup-btn" aria-label="Close"><span class="close-icon"></span></button>
          <div class="rb-modal-body"></div>
        </div>`;
      overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;opacity:0;z-index:99999;';
      const modal = overlay.querySelector('.rb-modal');
      modal.style.cssText = 'position:relative;margin:5vh auto;max-width:520px;background:#fff;border-radius:8px;padding:20px;';
      document.body.appendChild(overlay);
      return overlay;
    };

    const open = () => {
      const overlay = ensureModal();
      const body = overlay.querySelector('.rb-modal-body');
      body.innerHTML = '';
      body.appendChild(form);
      overlay.style.display = 'block';
      requestAnimationFrame(() => overlay.style.opacity = '1');

      try { if (typeof turnstile !== 'undefined') turnstile.reset(); } catch {}
      try { if (typeof grecaptcha !== 'undefined') grecaptcha.reset(); } catch {}
    };
    const close = () => {
      const overlay = $('#rb-login-overlay');
      if (!overlay) return;
      overlay.style.opacity = '0';
      setTimeout(() => overlay.style.display = 'none', 150);
    };

    delegate(document, '.login-toggle', 'click', (e) => { e.preventDefault(); e.stopPropagation(); open(); });
    delegate(document, '#rb-login-overlay .close-popup-btn', 'click', (e) => { e.preventDefault(); close(); });
    on(document, 'keydown', (e) => { if (e.key === 'Escape') close(); });
    delegate(document, '#rb-login-overlay', 'click', (e, ov) => { if (e.target === ov) close(); });
  };

  /* ===========================
   * YOUTUBE IFRAME
   * =========================== */
  Module.loadYoutubeIframe = function () {
    const playlists = $$('.yt-playlist');
    if (!playlists.length) return;

    if (!document.getElementById('yt-iframe-api')) {
      const tag = document.createElement('script');
      tag.src = 'https://www.youtube.com/iframe_api';
      tag.id = 'yt-iframe-api';
      const first = document.getElementsByTagName('script')[0];
      first.parentNode.insertBefore(tag, first);
    }

    window.onYouTubeIframeAPIReady = () => {
      playlists.forEach(pl => {
        const iframe = pl.querySelector('.yt-player');
        const videoID = pl.dataset.id;
        const blockID = pl.dataset.block;
        if (!iframe || !videoID || !blockID) return;
        this.YTPlayers[blockID] = new YT.Player(iframe, {
          height: '540', width: '960', videoId: videoID,
          events: { 'onReady': this.videoPlayToggle.bind(this), 'onStateChange': this.videoPlayToggle.bind(this) }
        });
      });

      delegate(document, '.plist-item', 'click', (e, item) => {
        e.preventDefault(); e.stopPropagation();
        const wrapper = safeClosest(item, '.yt-playlist', document);
        if (!wrapper) return;
        const currentBlockID = wrapper.dataset.block;
        const videoID = item.dataset.id;
        const title = item.querySelector('.plist-item-title')?.textContent || '';
        const meta = item.dataset.index || '';
        Object.values(this.YTPlayers).forEach(p => p.pauseVideo && p.pauseVideo());
        this.YTPlayers[currentBlockID]?.loadVideoById({ videoId: videoID });
        wrapper.querySelector('.yt-trigger')?.classList.add('is-playing');
        const titleEl = wrapper.querySelector('.play-title');
        if (titleEl) { hide(titleEl); titleEl.textContent = title; show(titleEl); }
        const idxEl = wrapper.querySelector('.video-index'); if (idxEl) idxEl.textContent = meta;
      });
    };
  };
  Module.videoPlayToggle = function () {
    const players = this.YTPlayers;
    delegate(document, '.yt-trigger', 'click', (e, trg) => {
      e.preventDefault(); e.stopPropagation();
      const pl = safeClosest(trg, '.yt-playlist', document);
      const blockID = pl && pl.dataset.block;
      const p = blockID && players[blockID];
      if (!p) return;
      const state = p.getPlayerState();
      const isPlaying = [1,3].includes(state);
      if (!isPlaying) { p.playVideo(); trg.classList.add('is-playing'); }
      else { p.pauseVideo(); trg.classList.remove('is-playing'); }
    });
  };

  /* ===========================
   * COMMENTS HELPERS
   * =========================== */
  Module.showPostComment = function () {
    delegate(document, '.smeta-sec .meta-comment', 'click', (e) => {
      const btn = $('.show-post-comment'); if (!btn) return;
      smoothScrollTo(btn.getBoundingClientRect().top + window.scrollY);
      btn.click();
    });

    delegate(document, '.show-post-comment', 'click', (e, btn) => {
      e.preventDefault(); e.stopPropagation();
      const wrap = btn.parentElement;
      hide(btn); btn.remove();
      wrap.querySelectorAll('.is-invisible').forEach(el => el.classList.remove('is-invisible'));
      const holder = wrap.nextElementSibling?.classList.contains('comment-holder') ? wrap.nextElementSibling : null;
      if (holder) holder.classList.remove('is-hidden');
    });
  };

  Module.scrollToComment = function () {
    const h = window.location.hash || '';
    if (h === '#respond' || h.startsWith('#comment')) {
      const btn = $('.show-post-comment');
      if (!btn) return;
      smoothScrollTo(btn.getBoundingClientRect().top + window.scrollY - 200);
      btn.click();
    }
  };



/* ===========================
   * MASTER INIT (NO PAGINATION)
   * =========================== */
  Module.ensureSearchAutocomplete = function () {
    const selectors = [
      '#wpadminbar form#adminbarsearch input.adminbar-input',
      'form.rb-search-form.live-search-form input.field[name="s"]',
      'form.rb-search-form input.field[name="s"]'
    ];
    const seen = [];
    selectors.forEach((selector) => {
      $$(selector).forEach((input) => {
        if (!input || input.tagName !== 'INPUT' || seen.indexOf(input) !== -1) return;
        if (input.getAttribute('autocomplete') !== 'search') {
          input.setAttribute('autocomplete', 'search');
        }
        seen.push(input);
      });
    });
  };
  

  /* ===========================
   * MASTER INIT (NO PAGINATION)
   * =========================== */
    Module.init = function () {
    this.initParams();

    const deferredTasks = [
      () => this.normalizeInlineGridSelectors(),
      () => this.tocToggle(),
      () => this.deferHeavyEmbeds(),
      () => this.ensureSearchAutocomplete(),
      // this.hoverTipsy();  // disabled to avoid big style recalculation
      () => this.hoverEffects(),
      () => this.videoPreview(),
      () => this.headerDropdown(),
      () => this.initSubMenuPos(),
      () => this.documentClick(),
      () => this.mobileCollapse(),
      () => this.privacyTrigger(),
      () => this.loginPopup(),
      () => this.loadYoutubeIframe(),
      () => this.showPostComment(),
      () => this.scrollToComment(),
    ];

    this.runDeferredTasks(deferredTasks, 14);
  };

}(FOXIZ_MAIN_SCRIPT));

/* Boot */
(function () {
  function boot() {
    if (window.__FOXIZ_BOOTED__) return;
    if (window.FOXIZ_MAIN_SCRIPT && typeof FOXIZ_MAIN_SCRIPT.init === 'function') {
      window.__FOXIZ_BOOTED__ = true;
      const startInit = () => {
        try { FOXIZ_MAIN_SCRIPT.init(); } catch (e) {}
      };

      if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(startInit, { timeout: 200 });
      } else {
        window.setTimeout(startInit, 0);
      }
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();