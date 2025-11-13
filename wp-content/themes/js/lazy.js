// lazy-index.js
(function (win, doc) {
  'use strict';
  if (!win || !doc || !doc.querySelectorAll) return;

  const $$ = (sel, ctx) => Array.from((ctx || doc).querySelectorAll(sel));

  function loadImg(img) {
    if (!img || img.dataset.wd4Loaded === '1') return;

    const src = img.getAttribute('data-wd4-src');
    const srcset = img.getAttribute('data-wd4-srcset');
    const sizes = img.getAttribute('data-wd4-sizes');

    if (src && !img.src) img.src = src;
    if (srcset && !img.srcset) img.srcset = srcset;
    if (sizes && !img.sizes) img.sizes = sizes;

    if (!img.loading) img.loading = 'lazy';

    img.dataset.wd4Loaded = '1';
    img.classList.add('wd4-lazy-loaded');
  }

  function init() {
    const images = $$('img[data-wd4-lazy]');
    if (!images.length) return;

    if ('IntersectionObserver' in win) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting && entry.intersectionRatio <= 0) return;
          const img = entry.target;
          io.unobserve(img);
          loadImg(img);
        });
      }, {
        rootMargin: '200px 0px 400px 0px',
        threshold: 0
      });

      images.forEach((img) => io.observe(img));
    } else {
      const onLoad = () => {
        images.forEach(loadImg);
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
