/*! yt-facade.js v6.0.1 */
(function () {
  'use strict';

  var SIZES = '(min-width:1440px) 1200px, (min-width:1024px) 960px, (min-width:700px) 720px, 92vw';
  var YTIMG = function (id, name) { return 'https://i.ytimg.com/vi/' + id + '/' + name; };

  function log() {
    if (location.search.indexOf('ytdebug=1') !== -1) {
      try { console.log.apply(console, ['[yt-facade]'].concat([].slice.call(arguments))); } catch (e) {}
    }
  }

  function buildJpgSrcset(id, includeMaxres) {
    var list = [
      { n: 'mqdefault.jpg', w: 320 },
      { n: 'hqdefault.jpg', w: 480 },
      { n: 'sddefault.jpg', w: 640 }
    ];
    if (includeMaxres !== false) list.push({ n: 'maxresdefault.jpg', w: 1280 });
    return list.map(function (c) { return YTIMG(id, c.n) + ' ' + c.w + 'w'; }).join(', ');
  }

  function ensureThumbElements(btn) {
    var box = btn.querySelector('.yt-facade__thumb');
    if (!box) {
      box = document.createElement('span');
      box.className = 'yt-facade__thumb';
      btn.insertBefore(box, btn.firstChild);
    }
    var img = box.querySelector('img');
    if (!img) {
      img = document.createElement('img');
      img.setAttribute('loading', 'lazy');
      img.setAttribute('decoding', 'async');
      img.setAttribute('fetchpriority', 'low');
      img.setAttribute('alt', '');
      img.setAttribute('aria-hidden', 'true');
      img.setAttribute('draggable', 'false');
      // tiny tweak #2: stricter referrer policy for cross-origin images
      img.referrerPolicy = 'strict-origin-when-cross-origin';
      box.appendChild(img);
    } else {
      // keep attributes consistent if markup already existed
      if (!img.hasAttribute('fetchpriority')) img.setAttribute('fetchpriority', 'low');
      if (!img.referrerPolicy) img.referrerPolicy = 'strict-origin-when-cross-origin';
      if (!img.hasAttribute('loading')) img.setAttribute('loading', 'lazy');
      if (!img.hasAttribute('decoding')) img.setAttribute('decoding', 'async');
    }
    return img;
  }

  function applySrcset(img, id) {
    // Default src + full srcset (incl. maxres)
    img.src = YTIMG(id, 'hqdefault.jpg');
    img.srcset = buildJpgSrcset(id, true);
    img.sizes = SIZES;
    img.width = 1280;  // hints only
    img.height = 720;

    // If browser picks an unavailable candidate (often maxres), fallback once.
    var triedFallback = false;
    function onError() {
      if (triedFallback) return;
      triedFallback = true;
      log('Image error, falling back to <= 640w set', id);
      img.removeEventListener('error', onError);
      img.src = YTIMG(id, 'sddefault.jpg');
      img.srcset = buildJpgSrcset(id, false); // no maxres
      img.sizes = SIZES;
    }
    img.addEventListener('error', onError, { once: true });
  }

  function preconnectOnce() {
    if (document.documentElement.hasAttribute('data-yt-preconnected')) return;
    document.documentElement.setAttribute('data-yt-preconnected', '1');

    var origins = [
      'https://www.youtube-nocookie.com',
      'https://www.youtube.com',
      'https://i.ytimg.com',
      'https://s.ytimg.com',
      'https://www.google.com'
    ];
    origins.forEach(function (href) {
      var l = document.createElement('link');
      l.rel = 'preconnect';
      l.href = href;
      l.crossOrigin = 'anonymous';
      document.head.appendChild(l);
    });
  }

  function swapToIframe(btn, id) {
    preconnectOnce();

    var r = btn.getBoundingClientRect();
    var w = Math.max(1, Math.round(r.width || 560));
    var h = Math.max(1, Math.round((w * 9) / 16));

    var iframe = document.createElement('iframe');
    iframe.width = String(w);
    iframe.height = String(h);
    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
    iframe.setAttribute('allowfullscreen', '');
    iframe.setAttribute('title', btn.getAttribute('aria-label') || 'YouTube video');
    iframe.setAttribute('loading', 'eager');
    iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
    iframe.setAttribute('src', 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0');
    iframe.style.width = '100%';
    iframe.style.height = '100%';
    iframe.style.border = '0';
    iframe.style.display = 'block';

    // Replace the button with the iframe
    btn.replaceWith(iframe);
  }

  function bindOne(btn) {
    if (!btn || btn.dataset.ytBound === '1') return;
    var id = (btn.getAttribute('data-video-id') || '').trim();
    if (!id) return;

    // Mark as bound first to avoid double work
    btn.dataset.ytBound = '1';

    // Ensure thumb exists and apply srcset
    var img = ensureThumbElements(btn);
    applySrcset(img, id);

    // Click -> swap to iframe
    btn.addEventListener('click', function (ev) {
      ev.preventDefault();
      ev.stopPropagation();
      swapToIframe(btn, id);
    }, { passive: false });

    log('bound', id, btn);
  }

  function scan(root) {
    var scope = root || document;
    var buttons = scope.querySelectorAll('.has-yt-facade .yt-facade:not([data-yt-bound="1"])');
    if (!buttons.length) return;
    buttons.forEach(bindOne);
  }

  function onReady() {
    scan(document);

    // Observe future embeds (pagination / block renders)
    var mo = new MutationObserver(function (list) {
      for (var i = 0; i < list.length; i++) {
        var m = list[i];
        if (m.type === 'childList' && m.addedNodes && m.addedNodes.length) {
          for (var j = 0; j < m.addedNodes.length; j++) {
            var node = m.addedNodes[j];
            if (!(node instanceof Element)) continue;
            if (node.matches && node.matches('.has-yt-facade, .yt-facade, .wp-block-embed-youtube')) {
              scan(node);
            } else {
              var inner = node.querySelectorAll
                ? node.querySelectorAll('.has-yt-facade, .yt-facade, .wp-block-embed-youtube')
                : [];
              if (inner.length) scan(node);
            }
          }
        }
      }
    });
    mo.observe(document.documentElement || document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady, { once: true });
  } else {
    onReady();
  }
})();
