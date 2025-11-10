(() => {
  const container = document.querySelector('.wd4-category');
  const feed = document.querySelector('#wd4-category-feed');
  const sentinel = document.querySelector('.wd4-category .pagination-infinite');

  if (!container || !feed || !sentinel) {
    return;
  }

  const toPositiveInt = (value, fallback) => {
    const parsed = Number.parseInt(value, 10);
    if (Number.isFinite(parsed) && parsed > 0) {
      return parsed;
    }
    return fallback;
  };

  const feedSignature = sentinel.dataset.feedSignature || '';
  const cacheKeyBase = sentinel.dataset.cacheKey || '';
  const cacheLimit = toPositiveInt(sentinel.dataset.cacheLimit, 6);
  const cacheTtl = toPositiveInt(sentinel.dataset.cacheTtl, 6 * 60 * 60 * 1000);

  const CACHE_VERSION = '2';
  const CACHE_MAX_ENTRIES = cacheLimit > 0 ? cacheLimit : 6;
  const CACHE_MAX_AGE = cacheTtl > 0 ? cacheTtl : 6 * 60 * 60 * 1000;
  const storageKey = cacheKeyBase ? `wd4Category:${CACHE_VERSION}:${cacheKeyBase}` : '';

  const parser = new DOMParser();

  const getStorage = () => {
    try {
      const testKey = '__wd4CategoryCache__';
      window.localStorage.setItem(testKey, '1');
      window.localStorage.removeItem(testKey);
      return window.localStorage;
    } catch (err) {
      return null;
    }
  };

  const storage = getStorage();
  const pageCache = new Map();

  let persistedCache = null;
  let nextUrl = sentinel.getAttribute('data-next') || '';
  let observer = null;
  let isLoading = false;
  let isComplete = false;

  if (!nextUrl) {
    sentinel.remove();
    return;
  }

  const purgePersistedState = () => {
    if (storage && storageKey) {
      try {
        storage.removeItem(storageKey);
      } catch (err) {
        // Ignore storage errors and continue without persistence.
      }
    }
    persistedCache = null;
  };

  const loadPersistedState = () => {
    if (!storage || !storageKey) {
      return;
    }

    try {
      const raw = storage.getItem(storageKey);
      if (!raw) {
        return;
      }

      const parsed = JSON.parse(raw);
      if (!parsed || typeof parsed !== 'object') {
        purgePersistedState();
        return;
      }

      if (parsed.signature !== feedSignature) {
        purgePersistedState();
        return;
      }

      if (!parsed.pages || typeof parsed.pages !== 'object') {
        purgePersistedState();
        return;
      }

      if (!Array.isArray(parsed.order)) {
        parsed.order = Object.keys(parsed.pages);
      }

      persistedCache = parsed;
    } catch (err) {
      purgePersistedState();
    }
  };

  const ensurePersistedState = () => {
    if (!storage || !storageKey || !feedSignature) {
      return false;
    }

    if (!persistedCache || persistedCache.signature !== feedSignature) {
      persistedCache = {
        signature: feedSignature,
        updated: Date.now(),
        order: [],
        pages: {},
      };
    }

    return true;
  };

  const persistState = () => {
    if (!storage || !storageKey || !persistedCache) {
      return;
    }

    try {
      storage.setItem(storageKey, JSON.stringify(persistedCache));
    } catch (err) {
      purgePersistedState();
    }
  };

  const dropPersistedEntry = (url) => {
    if (!persistedCache) {
      return;
    }

    if (persistedCache.pages && persistedCache.pages[url]) {
      delete persistedCache.pages[url];
    }

    if (Array.isArray(persistedCache.order)) {
      const index = persistedCache.order.indexOf(url);
      if (index !== -1) {
        persistedCache.order.splice(index, 1);
      }
    }
  };

  const getPersistedEntry = (url) => {
    if (!persistedCache || !persistedCache.pages) {
      return null;
    }

    const entry = persistedCache.pages[url];
    if (!entry || typeof entry.html !== 'string') {
      return null;
    }

    const fetchedAt = typeof entry.fetchedAt === 'number' ? entry.fetchedAt : 0;
    if (CACHE_MAX_AGE > 0 && fetchedAt > 0 && Date.now() - fetchedAt > CACHE_MAX_AGE) {
      dropPersistedEntry(url);
      persistState();
      return null;
    }

    return entry;
  };

  const storePersistedEntry = (url, html) => {
    if (!html || !ensurePersistedState() || !persistedCache) {
      return;
    }

    persistedCache.updated = Date.now();
    if (!persistedCache.pages) {
      persistedCache.pages = {};
    }

    persistedCache.pages[url] = {
      html,
      fetchedAt: Date.now(),
    };

    if (!Array.isArray(persistedCache.order)) {
      persistedCache.order = [];
    }

    const existingIndex = persistedCache.order.indexOf(url);
    if (existingIndex !== -1) {
      persistedCache.order.splice(existingIndex, 1);
    }

    persistedCache.order.push(url);

    while (persistedCache.order.length > CACHE_MAX_ENTRIES) {
      const oldest = persistedCache.order.shift();
      if (oldest) {
        dropPersistedEntry(oldest);
      }
    }

    persistState();
  };

  loadPersistedState();

  const retryButton = sentinel.querySelector('.pagination-infinite__retry');
  const setManualMode = () => {
    sentinel.classList.add('is-manual');
    if (retryButton) {
      retryButton.disabled = false;
      const defaultLabel = retryButton.dataset.defaultLabel || retryButton.textContent;
      if (defaultLabel) {
        retryButton.textContent = defaultLabel;
      }
    }
  };

  const detachObserver = () => {
    if (observer) {
      observer.disconnect();
      observer = null;
    }
  };

  const finish = () => {
    isComplete = true;
    detachObserver();
    sentinel.remove();
  };

  const clearErrorState = () => {
    sentinel.classList.remove('has-error');
    if (retryButton) {
      retryButton.disabled = true;
      const defaultLabel = retryButton.dataset.defaultLabel;
      if (defaultLabel) {
        retryButton.textContent = defaultLabel;
      }
    }
  };

  const showErrorState = () => {
    sentinel.classList.add('has-error', 'is-manual');
    if (retryButton) {
      retryButton.disabled = false;
      const retryLabel = retryButton.dataset.retryLabel;
      if (retryLabel) {
        retryButton.textContent = retryLabel;
      }
    }
  };

  const appendNewPosts = (doc) => {
    const incomingFeed = doc.querySelector('#wd4-category-feed');
    if (!incomingFeed) {
      return;
    }
    const newNodes = Array.from(incomingFeed.children);
    newNodes.forEach((node) => {
      feed.appendChild(node.cloneNode(true));
    });
  };

  const updateNextLink = (doc) => {
    const nextSentinel = doc.querySelector('.wd4-category .pagination-infinite');
    if (!nextSentinel) {
      nextUrl = '';
      finish();
      return;
    }

    const candidate = nextSentinel.getAttribute('data-next');
    if (!candidate) {
      nextUrl = '';
      finish();
      return;
    }

    nextUrl = candidate;
    sentinel.setAttribute('data-next', nextUrl);
    sentinel.classList.remove('is-manual');
    if (retryButton) {
      retryButton.disabled = false;
      const defaultLabel = retryButton.dataset.defaultLabel;
      if (defaultLabel) {
        retryButton.textContent = defaultLabel;
      }
    }
  };

  const parseHtml = (html) => parser.parseFromString(html, 'text/html');

  const fetchPage = async (url) => {
    if (pageCache.has(url)) {
      return pageCache.get(url);
    }

    const cachedEntry = getPersistedEntry(url);
    if (cachedEntry) {
      const cachedDoc = parseHtml(cachedEntry.html);
      const cachedPromise = Promise.resolve(cachedDoc);
      pageCache.set(url, cachedPromise);
      return cachedPromise;
    }

    const pendingRequest = (async () => {
      const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        throw new Error('Request failed');
      }

      const text = await response.text();
      const doc = parseHtml(text);
      storePersistedEntry(url, text);
      return doc;
    })();

    pageCache.set(url, pendingRequest);
    return pendingRequest;
  };

  const maybePrefetch = (url) => {
    if (!url || pageCache.has(url)) {
      return;
    }

    fetchPage(url).catch(() => {
      pageCache.delete(url);
    });
  };

  const requestNextPage = async () => {
    if (!nextUrl || isLoading || isComplete) {
      return;
    }

    isLoading = true;
    sentinel.classList.add('is-loading');
    clearErrorState();

    const requestUrl = nextUrl;

    try {
      const doc = await fetchPage(requestUrl);
      appendNewPosts(doc);
      updateNextLink(doc);

      if (nextUrl) {
        maybePrefetch(nextUrl);
      }
    } catch (err) {
      console.error('wd4 category infinite scroll error', err);
      pageCache.delete(requestUrl);
      dropPersistedEntry(requestUrl);
      persistState();
      showErrorState();
    } finally {
      isLoading = false;
      sentinel.classList.remove('is-loading');
    }
  };

  if (retryButton) {
    retryButton.dataset.defaultLabel = retryButton.textContent;
    retryButton.dataset.retryLabel = retryButton.dataset.retryLabel || retryButton.textContent || 'Retry';
    retryButton.disabled = true;
    retryButton.addEventListener('click', () => {
      if (!nextUrl) {
        return;
      }
      sentinel.classList.remove('has-error');
      sentinel.classList.remove('is-manual');
      requestNextPage();
    });
  }

  if ('IntersectionObserver' in window) {
    observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          requestNextPage();
        }
      });
    }, { rootMargin: '0px 0px 320px 0px' });

    observer.observe(sentinel);
  } else {
    setManualMode();
  }

  maybePrefetch(nextUrl);
})();