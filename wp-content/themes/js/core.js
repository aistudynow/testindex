/**
 * FOXIZ_CORE_SCRIPT — lean core utilities without jQuery.
 */
var FOXIZ_CORE_SCRIPT = window.FOXIZ_CORE_SCRIPT || {};
(function (Module) {
  'use strict';

  const win = window;
  const doc = document;
  const toDOM = (html) => {
    const box = doc.createElement('div');
    box.innerHTML = html;
    return box;
  };

  Module.init = function () {
    this.themeSettings = win.foxizCoreParams || {};
    this.emailToDownload();
  };
  Module.shareTrigger = function () {
    const popupHandler = (event) => {
      event.preventDefault();
      event.stopPropagation();
      const link = event.currentTarget.getAttribute('href');
      if (link) win.open(link, '_blank', 'width=600,height=350,noopener,noreferrer');
      return false;
    };

    doc.querySelectorAll('a.share-trigger').forEach((anchor) => {
      if (anchor.dataset.boundShare) return;
      anchor.dataset.boundShare = '1';
      anchor.addEventListener('click', popupHandler);
    });

    const copyButtons = doc.querySelectorAll('a.copy-trigger');
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
      copyButtons.forEach((button) => {
        if (button.dataset.boundCopy) return;
        button.dataset.boundCopy = '1';
        button.addEventListener('click', (event) => {
          event.preventDefault();
          event.stopPropagation();
          const link = button.dataset.link;
          if (!link) return;
          const copiedText = button.dataset.copied || 'Copied!';
          navigator.clipboard.writeText(link).then(() => {
            const tip = doc.querySelector('.tipsy-inner');
            if (tip) tip.textContent = copiedText;
          }).catch(() => {});
        });
      });
    } else {
      copyButtons.forEach((button) => { button.style.display = 'none'; });
    }

    const shareButtons = doc.querySelectorAll('a.native-share-trigger');
    if (navigator.share) {
      let sharing = false;
      shareButtons.forEach((anchor) => {
        if (anchor.dataset.boundNativeShare) return;
        anchor.dataset.boundNativeShare = '1';
        anchor.addEventListener('click', (event) => {
          event.preventDefault();
          event.stopPropagation();
          if (sharing) return;

          const shareData = {
            title: anchor.dataset.ptitle || doc.title,
            url: anchor.dataset.link || win.location.href
          };

          if (navigator.canShare && !navigator.canShare(shareData)) {
            popupHandler(event);
            return;
          }

          sharing = true;
          anchor.setAttribute('aria-busy', 'true');
          anchor.classList.add('is-sharing');
          anchor.style.pointerEvents = 'none';

          navigator.share(shareData)
            .catch((error) => {
              if (!error || (error.name !== 'AbortError' && error.name !== 'InvalidStateError')) {
                console.error('Web Share failed:', error);
              }
            })
            .finally(() => {
              sharing = false;
              anchor.removeAttribute('aria-busy');
              anchor.classList.remove('is-sharing');
              anchor.style.pointerEvents = '';
            });
        });
      });
    } else {
      shareButtons.forEach((button) => { button.style.display = 'none'; });
    }
  };

  Module.loadGoogleAds = function (response) {
    const container = typeof response === 'string' ? toDOM(response) : response;
    if (!container) return;
    const adSlots = container.querySelectorAll('.adsbygoogle');
    if (typeof win.adsbygoogle === 'undefined' || !adSlots.length) return;
    adSlots.forEach(() => (win.adsbygoogle = win.adsbygoogle || []).push({}));
  };

  Module.loadInstagram = function (response) {
    const container = typeof response === 'string' ? toDOM(response) : response;
    const embeds = (container || doc).querySelectorAll('.instagram-media');
    if (!embeds.length) return;
    if (typeof win.instgrm !== 'undefined') {
      win.instgrm.Embeds.process();
      return;
    }
    if (typeof win.instgrm === 'undefined') {
      const script = doc.createElement('script');
      script.src = '//platform.instagram.com/en_US/embeds.js';
      script.onload = () => {
        if (win.instgrm && win.instgrm.Embeds) win.instgrm.Embeds.process();
      };
      doc.body.appendChild(script);
    }
  };

  Module.loadTwttr = function () {
    if (typeof win.twttr !== 'undefined' && win.twttr.widgets) {
      win.twttr.ready((twttr) => twttr.widgets.load());
    }
  };

  Module.updateGA = function (article) {
    if (!article || !article.postURL) return;
    const gaURL = article.postURL.replace(/https?:\/\/[^/]+/i, '');
    if (win._gaq) win._gaq.push(['_trackPageview', gaURL]);
    if (win.ga) win.ga('send', 'pageview', gaURL);
    if (win.__gaTracker) win.__gaTracker('send', 'pageview', gaURL);
    if (win.googletag && win.googletag.pubadsReady) win.googletag.pubads().refresh();
  };

  Module.emailToDownload = function () {
    const loggedIn = !!this.themeSettings.isLoggedIn;
    const storedEmail = this.themeSettings.currentUserEmail || '';

    doc.querySelectorAll('.download-form').forEach((form) => {
      const noticeText = form.querySelector('.notice-text');
      const emailInput = form.querySelector('input[name="EMAIL"]');
      const submitBtn = form.querySelector('input[type="submit"]');
      const acceptTerms = form.querySelector('input[name="acceptTerms"]');
      const wrapper = form.closest('.gb-download');
      const loginUrl = this.themeSettings.loginUrl || form.getAttribute('data-login-url') || '/login-3/';

      if (emailInput) {
        const currentAutocomplete = emailInput.getAttribute('autocomplete');
        if (!currentAutocomplete || currentAutocomplete.toLowerCase() === 'off') {
          emailInput.setAttribute('autocomplete', 'email');
        }
      }

      if (!loggedIn) {
        if (form.dataset.loginGateBound) return;
        form.dataset.loginGateBound = '1';

        if (noticeText) noticeText.textContent = 'Please log in to download this file.';

        if (emailInput) {
          emailInput.removeAttribute('required');
          emailInput.value = '';
          emailInput.setAttribute('type', 'hidden');
        }

        if (submitBtn) {
          const loginLabel = submitBtn.getAttribute('data-login-label') || 'Log in to Download';
          submitBtn.type = 'button';
          submitBtn.value = loginLabel;
          if (!submitBtn.dataset.loginClickBound) {
            submitBtn.dataset.loginClickBound = '1';
            submitBtn.addEventListener('click', () => {
              win.location.href = loginUrl;
            });
          }
        }

        if (wrapper) wrapper.classList.add('requires-login');

        return;
      }

      if (acceptTerms && submitBtn && !acceptTerms.dataset.boundChange) {
        acceptTerms.dataset.boundChange = '1';
        acceptTerms.addEventListener('change', (event) => {
          submitBtn.disabled = !event.currentTarget.checked;
        });
      }

      if (emailInput) {
        emailInput.removeAttribute('required');
        emailInput.setAttribute('type', 'hidden');
        if (!emailInput.value && storedEmail) {
          emailInput.value = storedEmail;
        }
      }

      form.classList.add('download-form-logged-in');

      const directUrl = form.getAttribute('data-direct-download-url') || '';
      const directFileName = form.getAttribute('data-direct-download-filename') || '';

      if (form.dataset.boundSubmit) return;
      form.dataset.boundSubmit = '1';

      form.addEventListener('submit', (event) => {
        event.preventDefault();

        const activeEmailInput = form.querySelector('input[name="EMAIL"]');
        let email = activeEmailInput ? activeEmailInput.value : '';

        if (!email && storedEmail) {
          email = storedEmail;
          if (activeEmailInput) activeEmailInput.value = email;
        }

        if (!email) {
          const host = (win.location && win.location.hostname)
            ? win.location.hostname.replace(/[^a-z0-9.-]/gi, '')
            : 'example.com';
          email = `member@${host}`;
          if (activeEmailInput) activeEmailInput.value = email;
        }

        const labelEl = form.querySelector('input[type="submit"]');
        const label = labelEl ? labelEl.value : 'Download';
        if (noticeText) noticeText.textContent = '';
        if (wrapper) wrapper.classList.add('submitting');

        if (directUrl) {
          const autoMessage = 'Your download will start automatically.';
          if (noticeText) noticeText.textContent = autoMessage;

          const tempLink = doc.createElement('a');
          tempLink.href = directUrl;
          tempLink.rel = 'nofollow';
          tempLink.style.display = 'none';
          if (directFileName) tempLink.setAttribute('download', directFileName);
          else tempLink.setAttribute('download', '');
          doc.body.appendChild(tempLink);
          tempLink.click();
          tempLink.remove();

          if (wrapper) wrapper.classList.remove('submitting');

          const safeFileName = directFileName.replace(/"/g, '&quot;');
          const downloadAttr = directFileName ? ` download="${safeFileName}"` : ' download';
          const fallbackHtml = `<div class="fallback-info">${autoMessage}</div>`
            + `<a href="${directUrl}"${downloadAttr} rel="nofollow" class="is-btn gb-download-btn fallback-download-btn">${label}</a>`;
          form.outerHTML = fallbackHtml;
          return;
        }

        const url = this.themeSettings.ajaxurl || null;
        if (!url) {
          if (noticeText) noticeText.textContent = 'Submission endpoint missing.';
          if (wrapper) wrapper.classList.remove('submitting');
          return;
        }

        const fd = new FormData(form);

        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then((res) => res.json())
          .then((response) => {
            const fileURL = response.file;
            if (fileURL) {
              const link = doc.createElement('a');
              link.href = fileURL;
              link.setAttribute('download', '');
              doc.body.appendChild(link);
              link.click();
              link.remove();

              const message = response.message || '';
              const fallbackHtml = `<div class="fallback-info">${message}</div>`
                + `<a href="${fileURL}" download="" rel="nofollow" class="is-btn gb-download-btn fallback-download-btn">${label}</a>`;
              form.outerHTML = fallbackHtml;
            } else {
              if (noticeText) noticeText.textContent = response.message || 'Something went wrong.';
              if (wrapper) wrapper.classList.remove('submitting');
            }
          })
          .catch(() => {
            if (noticeText) noticeText.textContent = 'Network error. Please try again.';
            if (wrapper) wrapper.classList.remove('submitting');
          })
          .finally(() => {
            if (wrapper) wrapper.classList.remove('submitting');
          });
      });
    });
  };

  return Module;
}(FOXIZ_CORE_SCRIPT));

/* init & load hooks (no jQuery) */
document.addEventListener('DOMContentLoaded', () => {
  FOXIZ_CORE_SCRIPT.init();
});
window.addEventListener('load', () => {
  FOXIZ_CORE_SCRIPT.shareTrigger();
});