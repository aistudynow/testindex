/*! tw-facade.js v2.1.1 — robust delegation + visible fallback */
(function () {
  'use strict';

  var DEBUG = location.search.indexOf('twdebug=1') !== -1;
  function log(){ if(DEBUG) try{ console.log.apply(console, ['[tw-facade]'].concat([].slice.call(arguments))); }catch(_){} }

  function closest(el, sel){
    while (el && el.nodeType === 1) {
      if (el.matches && el.matches(sel)) return el;
      el = el.parentNode;
    }
    return null;
  }

  // Preconnect once (top-level, not inside activateFigure)
  function preconnectTwOnce() {
    var root = document.documentElement;
    if (root.hasAttribute('data-tw-preconnected')) return;
    root.setAttribute('data-tw-preconnected', '1');

    ['https://platform.twitter.com', 'https://syndication.twitter.com'].forEach(function(href){
      var l = document.createElement('link');
      l.rel = 'preconnect';
      l.href = href;
      l.crossOrigin = 'anonymous';
      document.head.appendChild(l);
    });
  }

  function ensureSlot(fig){
    var slot = fig.querySelector('.tw-embed__slot');
    if (!slot){
      slot = document.createElement('div');
      slot.className = 'tw-embed__slot';
      (fig.querySelector('.wp-block-embed__wrapper') || fig).appendChild(slot);
    }
    return slot;
  }

  function injectIframe(fig, tweetId){
    var slot = ensureSlot(fig);
    if (!slot) return;
    slot.removeAttribute('aria-hidden');

    var url = 'https://platform.twitter.com/embed/Tweet.html'
      + '?id=' + encodeURIComponent(tweetId)
      + '&dnt=true&theme=light&hideThread=false&hideCard=false'
      + '&lang=' + encodeURIComponent(document.documentElement.lang || 'en');

    var ifr = document.createElement('iframe');
    ifr.src = url;
    ifr.title = 'X Post';
    ifr.setAttribute('allowfullscreen','true');
    ifr.setAttribute('allowtransparency','true');
    ifr.setAttribute('frameborder','0');
    ifr.setAttribute('scrolling','no');
    ifr.setAttribute('referrerpolicy','strict-origin-when-cross-origin');

    // Let CSS control the size
    ifr.style.width  = '100%';
    ifr.style.height = '100%';
    ifr.style.border = '0';
    ifr.style.display = 'block';

    slot.innerHTML = '';
    slot.appendChild(ifr);
  }

  function activateFigure(fig){
    if (!fig || fig.classList.contains('is-activated')) return;
    var tweetId = (fig.getAttribute('data-tweet-id') || '').trim();
    if (!tweetId) return;

    fig.classList.add('is-activated');

    // Remove the placeholder button (no focus, no screen-reader noise)
    var btn = fig.querySelector('.tw-facade');
    if (btn) btn.remove();

    // Just-in-time preconnect, then inject iframe
    preconnectTwOnce();
    injectIframe(fig, tweetId);
  }

  // Bind individual buttons (explicit accessibility)
  function bindButtons(root){
    (root || document).querySelectorAll('.has-tw-facade .tw-facade:not([data-tw-bound="1"])').forEach(function(btn){
      btn.dataset.twBound = '1';
      btn.setAttribute('role', 'button');
      btn.setAttribute('tabindex', '0');

      var go = function(ev){
        if (ev && ev.preventDefault) ev.preventDefault();
        var fig = closest(btn, '.wp-block-embed-twitter.has-tw-facade');
        activateFigure(fig);
      };

      btn.addEventListener('click', go, { passive:false, capture:true });
      btn.addEventListener('click', go, { passive:false });
      btn.addEventListener('pointerup', go, { passive:false, capture:true });
      btn.addEventListener('keyup', function(e){ if (e.key === 'Enter' || e.key === ' ') go(e); });
    });
  }

  // Document-level delegated listeners (capture) as a safety net
  function installDelegates(){
    function delegate(e){
      var wrapper = closest(e.target, '.wp-block-embed-twitter.has-tw-facade .wp-block-embed__wrapper');
      if (wrapper){
        var fig = closest(wrapper, '.wp-block-embed-twitter.has-tw-facade');
        if (fig){ e.preventDefault(); activateFigure(fig); return; }
      }
      var fig2 = closest(e.target, '.wp-block-embed-twitter.has-tw-facade');
      if (fig2){ e.preventDefault(); activateFigure(fig2); }
    }
    ['click','pointerup'].forEach(function(type){
      document.addEventListener(type, delegate, { capture:true, passive:false });
    });
    document.addEventListener('keyup', function(e){
      if (e.key !== 'Enter' && e.key !== ' ') return;
      var wrapper = closest(e.target, '.wp-block-embed-twitter.has-tw-facade .wp-block-embed__wrapper');
      if (wrapper){
        var fig = closest(wrapper, '.wp-block-embed-twitter.has-tw-facade');
        if (fig){ e.preventDefault(); activateFigure(fig); }
      }
    }, true);
  }

  // Auto-activate on visibility (covers odd cases where events are blocked)
  function installVisibilityFallback(){
    var figures = document.querySelectorAll('.wp-block-embed-twitter.has-tw-facade');
    if (!figures.length) return;
    if ('IntersectionObserver' in window){
      var io = new IntersectionObserver(function(entries){
        entries.forEach(function(en){
          if (en.isIntersecting){
            activateFigure(en.target);
            io.unobserve(en.target);
          }
        });
      }, { rootMargin: '200px 0px' });
      figures.forEach(function(f){ io.observe(f); });
    } else {
      setTimeout(function(){ figures.forEach(activateFigure); }, 1200);
    }
  }

  // Debug helper
  window.__twFacadeActivateAll = function(){
    document.querySelectorAll('.wp-block-embed-twitter.has-tw-facade').forEach(activateFigure);
  };

  function ready(){
    bindButtons(document);
    installDelegates();
    installVisibilityFallback();

    // Bind buttons for dynamically-added content
    var mo = new MutationObserver(function(ms){
      for (var i=0;i<ms.length;i++){
        var m = ms[i];
        if (m.type === 'childList' && m.addedNodes && m.addedNodes.length){
          for (var j=0;j<m.addedNodes.length;j++){
            var n = m.addedNodes[j];
            if (n.nodeType !== 1) continue;
            if (n.matches && n.matches('.has-tw-facade, .tw-facade, .wp-block-embed-twitter')){
              bindButtons(n);
            } else if (n.querySelectorAll){
              var inner = n.querySelectorAll('.has-tw-facade .tw-facade');
              if (inner.length) bindButtons(n);
            }
          }
        }
      }
    });
    mo.observe(document.documentElement || document.body, { childList:true, subtree:true });
  }

  if (document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', ready, { once:true });
  } else {
    ready();
  }
})();
