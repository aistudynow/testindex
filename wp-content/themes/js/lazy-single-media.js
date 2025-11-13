// lazy-single-media.js
(function (win, doc) {
  'use strict';
  if (!win || !doc || !doc.querySelectorAll) return;

  const $$ = (sel, ctx) => Array.from((ctx || doc).querySelectorAll(sel));

  function loadMedia(el) {
    if (!el || el.dataset.wd4Loaded === '1') return;

    const tag = el.tagName;

    // ----------------
    // IMAGES
    // ----------------
    if (tag === 'IMG') {
      const src = el.getAttribute('data-wd4-src');
      const srcset = el.getAttribute('data-wd4-srcset');
      const sizes = el.getAttribute('data-wd4-sizes');

      if (src && !el.src) el.src = src;
      if (srcset && !el.srcset) el.srcset = srcset;
      if (sizes && !el.sizes) el.sizes = sizes;
      if (!el.loading) el.loading = 'lazy';
    }

    // ----------------
    // VIDEO / AUDIO
    // ----------------
    else if (tag === 'VIDEO' || tag === 'AUDIO') {
      const dataSrc = el.getAttribute('data-wd4-src');
      if (dataSrc && !el.getAttribute('src')) {
        el.setAttribute('src', dataSrc);
      }

      const preload = el.getAttribute('data-wd4-preload');
      if (preload) {
        el.preload = preload;
        el.setAttribute('preload', preload);
      }

      // Move data-wd4-src from <source> into src
      $$('source[data-wd4-src]', el).forEach((source) => {
        const s = source.getAttribute('data-wd4-src');
        if (s) source.setAttribute('src', s);
        source.removeAttribute('data-wd4-src');
      });

      if (typeof el.load === 'function') {
        try { el.load(); } catch (err) { /* ignore */ }
      }

      // Optional: autoplay after user gesture
      const auto = el.getAttribute('data-wd4-autoplay');
      if (auto === '1' || auto === 'true') {
        const tryPlay = () => {
          el.play && el.play().catch(() => {});
          el.removeEventListener('click', tryPlay);
          el.removeEventListener('pointerdown', tryPlay);
        };
        el.addEventListener('click', tryPlay, { once: true });
        el.addEventListener('pointerdown', tryPlay, { once: true });
      }
    }

    // ----------------
    // IFRAMES (e.g. YouTube)
    // ----------------
    else if (tag === 'IFRAME') {
      const src = el.getAttribute('data-wd4-src');
      if (src && !el.src) el.src = src;
      if (!el.loading) el.loading = 'lazy';

      if (!el.getAttribute('referrerpolicy')) {
        el.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
      }

      const currentSrc = src || el.src || '';
      if (/youtube\.com|youtu\.be/i.test(currentSrc)) {
        const allow = el.getAttribute('allow') || '';
        if (!/autoplay/i.test(allow)) {
          el.setAttribute('allow', (allow ? allow + '; ' : '') + 'autoplay');
        }
      }
    }

    el.dataset.wd4Loaded = '1';
    el.classList.add('wd4-lazy-loaded');
  }

  function init() {
    const nodes = $$('[data-wd4-lazy]');
    if (!nodes.length) return;

    if ('IntersectionObserver' in win) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting && entry.intersectionRatio <= 0) return;
          const target = entry.target;
          io.unobserve(target);
          loadMedia(target);
        });
      }, {
        rootMargin: '200px 0px 400px 0px',
        threshold: 0
      });

      nodes.forEach((el) => io.observe(el));
    } else {
      const onLoad = () => {
        nodes.forEach(loadMedia);
        win.removeEventListener('load', onLoad);
      };
      win.addEventListener('load', onLoad);
    }
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})(window, document);
