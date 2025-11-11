(function (global) {
    'use strict';

    const doc = global.document;
    if (!doc) return;

    const Lazy = global.FOXIZ_LAZY = global.FOXIZ_LAZY || {};
    const config = global.wd4LazyLoader || {};

    const $ = (sel, ctx = doc) => ctx.querySelector(sel);
    const $$ = (sel, ctx = doc) => Array.from(ctx.querySelectorAll(sel));
    const listen = (node, type, handler, opts) => node && node.addEventListener(type, handler, opts || false);
    const unlisten = (node, type, handler) => node && node.removeEventListener(type, handler);
    const runWhenReady = (fn) => (doc.readyState === 'loading' ? listen(doc, 'DOMContentLoaded', fn, { once: true }) : fn());
    const requestIdle = (fn, timeout) => {
        if ('requestIdleCallback' in global) {
            global.requestIdleCallback(fn, { timeout: timeout || 1200 });
        } else {
            global.setTimeout(fn, timeout || 120);
        }
    };

    const videoMimeLookup = {
        mp4: 'video/mp4',
        m4v: 'video/mp4',
        webm: 'video/webm',
        ogv: 'video/ogg',
        ogg: 'video/ogg',
        mov: 'video/quicktime'
    };

    const guessVideoMime = (url) => {
        if (!url) return '';
        const clean = url.split('#')[0].split('?')[0];
        const dot = clean.lastIndexOf('.');
        if (dot === -1) return '';
        const ext = clean.slice(dot + 1).toLowerCase();
        return videoMimeLookup[ext] || '';
    };

    const ytFacadeData = new WeakMap();
    let ytPreconnected = false;

    const ensureYoutubePreconnect = () => {
        if (ytPreconnected) return;
        const head = doc.head || doc.getElementsByTagName('head')[0];
        if (!head) return;
        [
            'https://www.youtube.com',
            'https://www.google.com',
            'https://i.ytimg.com',
            'https://s.ytimg.com'
        ].forEach((href) => {
            if (head.querySelector(`link[rel="preconnect"][href="${href}"]`)) return;
            const link = doc.createElement('link');
            link.rel = 'preconnect';
            link.href = href;
            link.crossOrigin = 'anonymous';
            head.appendChild(link);
        });
        ytPreconnected = true;
    };

    const extractYoutubeId = (url) => {
        if (!url) return '';
        try {
            const base = global.location && global.location.href ? global.location.href : undefined;
            const parsed = new URL(url, base);
            const host = (parsed.hostname || '').replace(/^www\./, '');
            if (host === 'youtu.be') {
                const parts = parsed.pathname.split('/').filter(Boolean);
                return parts[0] || '';
            }
            if (parsed.searchParams && parsed.searchParams.get('v')) {
                return parsed.searchParams.get('v');
            }
            const pathMatch = parsed.pathname.match(/\/(?:embed|shorts)\/([^/?]+)/);
            if (pathMatch && pathMatch[1]) return pathMatch[1];
        } catch (err) {
            /* ignore */
        }
        const fallback = url.match(/(?:youtube(?:-nocookie)?\.com\/(?:embed|shorts)\/|youtu\.be\/)([A-Za-z0-9_-]{6,})/);
        if (fallback && fallback[1]) return fallback[1];
        const idFromQuery = url.match(/[?&]v=([A-Za-z0-9_-]{6,})/);
        return idFromQuery ? idFromQuery[1] : '';
    };

    const buildYoutubeThumb = (id, fallback) => (id ? `https://i.ytimg.com/vi/${id}/${fallback ? 'hqdefault' : 'maxresdefault'}.jpg` : '');

    const addAutoplayParam = (url) => {
        if (!url) return '';
        if (/[?&]autoplay=1/i.test(url)) return url;
        const hashIndex = url.indexOf('#');
        const hash = hashIndex > -1 ? url.slice(hashIndex) : '';
        const base = hashIndex > -1 ? url.slice(0, hashIndex) : url;
        const joiner = base.indexOf('?') > -1 ? '&' : '?';
        return `${base}${joiner}autoplay=1${hash}`;
    };

    const loadIconFont = () => {
        const fontUrl = typeof config.iconFontUrl === 'string' ? config.iconFontUrl : '';
        if (!fontUrl) return;

        const fontFamily = typeof config.iconFontFamily === 'string' && config.iconFontFamily ? config.iconFontFamily : 'ruby-icon';
        const styleId = typeof config.styleId === 'string' && config.styleId ? config.styleId : 'wd4-icon-font-face';
        const timeout = typeof config.timeout === 'number' && config.timeout >= 0 ? config.timeout : 1200;
        const fallbackDelay = typeof config.fallbackDelay === 'number' && config.fallbackDelay >= 0 ? config.fallbackDelay : 120;

        const hasFont = () => {
            if (!doc.fonts || !doc.fonts.check) return false;
            try {
                return doc.fonts.check(`1em ${fontFamily}`);
            } catch (err) {
                return false;
            }
        };

        if (hasFont()) return;
        if (doc.getElementById(styleId)) return;

        const buildFontFace = (url) => `@font-face{font-family:'${fontFamily}';font-style:normal;font-weight:400;font-display:swap;src:url('${url}') format('woff2');}`;

        const inject = () => {
            if (doc.getElementById(styleId)) return;
            const style = doc.createElement('style');
            style.id = styleId;
            style.textContent = buildFontFace(fontUrl);
            (doc.head || doc.documentElement || doc.body || doc).appendChild(style);
            if (doc.fonts && doc.fonts.load) {
                doc.fonts.load(`1em ${fontFamily}`).catch(() => {});
            }
        };

        if ('requestIdleCallback' in global) {
            requestIdle(inject, timeout);
        } else {
            global.setTimeout(inject, fallbackDelay);
        }
    };

    Lazy.loadIconFont = loadIconFont;

    const buildVideoFallback = (video) => {
        const fallbackVideo = video.cloneNode(true);
        fallbackVideo.classList.remove('js-lazy-video');
        ['data-preload', 'data-loaded', 'data-lazy-prepared', 'data-src'].forEach((attr) => fallbackVideo.removeAttribute(attr));
        fallbackVideo.setAttribute('preload', 'metadata');
        fallbackVideo.setAttribute('playsinline', '');
        if (!fallbackVideo.hasAttribute('controls')) fallbackVideo.setAttribute('controls', '');
        fallbackVideo.querySelectorAll('source').forEach((sourceEl) => {
            const origSrc = sourceEl.getAttribute('src') || sourceEl.getAttribute('data-src');
            if (origSrc) sourceEl.setAttribute('src', origSrc);
            sourceEl.removeAttribute('data-src');
        });
        if (!fallbackVideo.getAttribute('src')) {
            const firstSource = fallbackVideo.querySelector('source[src]');
            if (firstSource) fallbackVideo.setAttribute('src', firstSource.getAttribute('src'));
        }
        return fallbackVideo;
    };

    Lazy.prepareInlineVideos = () => {
        $$('.wp-block-video').forEach((figure) => {
            const video = figure.querySelector('video');
            if (!video || video.classList.contains('js-lazy-video') || video.dataset.lazyPrepared === 'true') return;

            const preloadPref = video.getAttribute('data-preload') || video.getAttribute('preload') || 'metadata';
            const sources = $$('source', video);
            const directSrc = video.getAttribute('src');

            if (sources.length) {
                sources.forEach((sourceEl) => {
                    const srcVal = sourceEl.getAttribute('src') || sourceEl.getAttribute('data-src');
                    if (!srcVal) return;
                    sourceEl.setAttribute('data-src', srcVal);
                    sourceEl.removeAttribute('src');
                    if (!sourceEl.getAttribute('type')) {
                        const guessed = guessVideoMime(srcVal);
                        if (guessed) sourceEl.setAttribute('type', guessed);
                    }
                });
            } else if (directSrc) {
                while (video.firstChild) video.removeChild(video.firstChild);
                const source = doc.createElement('source');
                source.setAttribute('data-src', directSrc);
                const guessed = guessVideoMime(directSrc);
                if (guessed) source.setAttribute('type', guessed);
                video.appendChild(source);
            }

            video.removeAttribute('src');
            video.dataset.preload = preloadPref || 'metadata';
            video.setAttribute('preload', 'none');
            video.setAttribute('playsinline', '');
            if (!video.hasAttribute('controls')) video.setAttribute('controls', '');
            video.classList.add('js-lazy-video');
            video.dataset.lazyPrepared = 'true';
            video.dataset.lazyBound = 'false';

            const noscript = $('noscript', figure) || figure.appendChild(doc.createElement('noscript'));
            noscript.innerHTML = buildVideoFallback(video).outerHTML;
        });
    };

    const bindYoutubeThumbFallback = (img, fallbackUrl) => {
        if (!fallbackUrl || fallbackUrl === img.src) return;
        const switchToFallback = () => {
            if (img.dataset.ytFallback === '1') return;
            img.dataset.ytFallback = '1';
            img.src = fallbackUrl;
            unlisten(img, 'error', onError);
            unlisten(img, 'load', onLoad);
        };
        const onError = () => switchToFallback();
        const onLoad = () => {
            if (img.dataset.ytFallback === '1') {
                unlisten(img, 'load', onLoad);
                return;
            }
            if ((img.naturalHeight && img.naturalHeight <= 90) || (img.naturalWidth && img.naturalWidth <= 160)) {
                switchToFallback();
            }
        };
        listen(img, 'error', onError);
        listen(img, 'load', onLoad);
    };

    Lazy.prepareYoutubeEmbeds = () => {
        $$('.wp-block-embed.wp-block-embed-youtube').forEach((figure) => {
            if (figure.dataset.ytPrepared === 'true') return;
            const wrapper = figure.querySelector('.wp-block-embed__wrapper');
            const iframe = wrapper && wrapper.querySelector('iframe');
            if (!wrapper || !iframe) return;

            const src = iframe.getAttribute('src') || iframe.dataset.src || '';
            const videoId = extractYoutubeId(src);
            if (!videoId) {
                iframe.setAttribute('loading', 'lazy');
                return;
            }

            const title = iframe.getAttribute('title') || figure.getAttribute('data-title') || '';
            const iframeClone = iframe.cloneNode(true);
            const allowAttr = iframeClone.getAttribute('allow') || '';
            if (allowAttr) {
                if (!/autoplay/i.test(allowAttr)) iframeClone.setAttribute('allow', `${allowAttr}; autoplay`);
            } else {
                iframeClone.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
            }
            iframeClone.setAttribute('loading', 'lazy');
            if (!iframeClone.getAttribute('referrerpolicy')) {
                iframeClone.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            }
            if (!iframeClone.hasAttribute('allowfullscreen')) iframeClone.setAttribute('allowfullscreen', '');
            if (title && !iframeClone.getAttribute('title')) iframeClone.setAttribute('title', title);
            iframeClone.removeAttribute('src');
            iframeClone.removeAttribute('width');
            iframeClone.removeAttribute('height');

            const embedData = { iframe: iframeClone, src, title, id: videoId };

            try {
                iframe.src = 'about:blank';
            } catch (err) {
                /* ignore */
            }
            iframe.removeAttribute('src');
            iframe.remove();

            const facade = doc.createElement('button');
            facade.type = 'button';
            facade.className = 'yt-facade';
            facade.dataset.videoId = videoId;
            facade.setAttribute('aria-label', title ? `Play video: ${title}` : 'Play video');

            const thumbWrap = doc.createElement('span');
            thumbWrap.className = 'yt-facade__thumb';
            const thumbImg = doc.createElement('img');
            thumbImg.src = buildYoutubeThumb(videoId, false);
            thumbImg.loading = 'lazy';
            thumbImg.decoding = 'async';
            thumbImg.alt = '';
            thumbImg.setAttribute('aria-hidden', 'true');
            thumbImg.draggable = false;
            bindYoutubeThumbFallback(thumbImg, buildYoutubeThumb(videoId, true));
            thumbWrap.appendChild(thumbImg);

            const playIcon = doc.createElement('span');
            playIcon.className = 'yt-facade__icon';
            playIcon.setAttribute('aria-hidden', 'true');

            facade.appendChild(thumbWrap);
            facade.appendChild(playIcon);

            wrapper.innerHTML = '';
            wrapper.classList.add('has-yt-facade');
            wrapper.appendChild(facade);
            figure.classList.add('has-yt-facade');
            figure.dataset.ytPrepared = 'true';

            ytFacadeData.set(facade, embedData);
        });
    };

    const loadVideo = (video) => {
        if (!video || video.dataset.loaded === 'true') return;
        const dataPoster = video.dataset.poster;
        if (dataPoster && !video.poster) video.poster = dataPoster;
        const sources = $$('source[data-src]', video);
        if (sources.length) {
            sources.forEach((source) => {
                source.src = source.dataset.src || '';
                source.removeAttribute('data-src');
            });
        } else {
            const dataSrc = video.dataset.src;
            if (dataSrc) {
                video.src = dataSrc;
                video.removeAttribute('data-src');
            }
        }
        const preloadPref = video.dataset.preload || 'metadata';
        video.preload = preloadPref;
        video.setAttribute('preload', preloadPref);
        if (typeof video.load === 'function') video.load();
        video.dataset.loaded = 'true';
    };

    const armVideoIntent = (video) => {
        if (video.dataset.lazyBound === 'true') return;
        video.dataset.lazyBound = 'true';
        const trigger = () => loadVideo(video);
        ['play', 'pointerenter', 'touchstart', 'focus', 'click', 'mouseover'].forEach((evt) => listen(video, evt, trigger, { once: true }));
    };

    Lazy.lazyLoadVideos = () => {
        const videos = $$('.js-lazy-video');
        if (!videos.length) return;

        videos.forEach((video) => armVideoIntent(video));

        if ('IntersectionObserver' in global) {
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting || entry.intersectionRatio > 0) {
                        loadVideo(entry.target);
                        obs.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '200px 0px' });
            videos.forEach((video) => observer.observe(video));
        } else {
            videos.forEach((video) => loadVideo(video));
        }
    };

    const preconnectFacade = (facade) => {
        if (!facade || facade.dataset.preconnected === 'true') return;
        ensureYoutubePreconnect();
        facade.dataset.preconnected = 'true';
    };

    const loadYoutubeIframe = (facade, autoplay) => {
        if (!facade || facade.dataset.loaded === 'true') return;
        const data = ytFacadeData.get(facade);
        if (!data || !data.iframe) return;
        const wrapper = facade.parentElement;
        if (!wrapper) return;

        preconnectFacade(facade);
        const iframe = data.iframe;
        const src = autoplay ? addAutoplayParam(data.src) : data.src;
        if (src) iframe.setAttribute('src', src);

        wrapper.classList.remove('has-yt-facade');
        wrapper.innerHTML = '';
        wrapper.appendChild(iframe);
        wrapper.classList.add('yt-embed-loaded');
        facade.dataset.loaded = 'true';
        ytFacadeData.delete(facade);

        global.setTimeout(() => {
            try {
                iframe.focus();
            } catch (err) {
                /* ignore */
            }
        }, 30);
    };

    Lazy.lazyLoadYoutubeEmbeds = () => {
        const facades = $$('.yt-facade');
        if (!facades.length) return;

        const observer = 'IntersectionObserver' in global ? new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting || entry.intersectionRatio > 0) {
                    preconnectFacade(entry.target);
                    obs.unobserve(entry.target);
                }
            });
        }, { rootMargin: '400px 0px' }) : null;

        facades.forEach((facade) => {
            if (facade.dataset.ytBound === 'true') return;
            facade.dataset.ytBound = 'true';

            listen(facade, 'click', (event) => {
                event.preventDefault();
                loadYoutubeIframe(facade, true);
            });

            listen(facade, 'keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    loadYoutubeIframe(facade, true);
                }
            });

            ['pointerenter', 'touchstart', 'focus'].forEach((evt) => listen(facade, evt, () => preconnectFacade(facade), { once: true }));

            if (observer) observer.observe(facade); else preconnectFacade(facade);
        });
    };

    const scriptAttr = { loaded: 'lazyScriptLoaded', bound: 'lazyScriptBound' };

    const insertBeforeScript = (scriptEl, html) => {
        if (!scriptEl || !html) return;
        const parent = scriptEl.parentNode;
        if (!parent) return;
        const container = doc.createElement('div');
        container.innerHTML = html;
        while (container.firstChild) {
            const node = container.firstChild;
            container.removeChild(node);
            if (node.nodeType === 1 && node.tagName && node.tagName.toLowerCase() === 'script') {
                const replacement = doc.createElement('script');
                Array.from(node.attributes).forEach((attr) => replacement.setAttribute(attr.name, attr.value));
                if (node.textContent) replacement.text = node.textContent;
                if (replacement.src && !replacement.hasAttribute('async')) replacement.async = false;
                parent.insertBefore(replacement, scriptEl);
            } else {
                parent.insertBefore(node, scriptEl);
            }
        }
    };

    const createDocumentWriteProxy = (scriptEl) => {
        if (!doc || typeof doc.write !== 'function') return null;
        const originalWrite = doc.write;
        const originalWriteln = doc.writeln;
        const queue = [];

        const flushQueue = () => {
            if (!queue.length || !scriptEl || !scriptEl.parentNode) return;
            queue.splice(0).forEach((html) => insertBeforeScript(scriptEl, html));
        };

        const handleWrite = (content) => {
            if (!content) return;
            if (scriptEl && scriptEl.parentNode) {
                flushQueue();
                insertBeforeScript(scriptEl, content);
            } else {
                queue.push(content);
            }
        };

        doc.write = (content) => handleWrite(String(content));
        doc.writeln = (content) => handleWrite(`${content}\n`);

        return {
            flush: flushQueue,
            release: () => {
                flushQueue();
                doc.write = originalWrite;
                doc.writeln = originalWriteln;
            }
        };
    };

    const placeScript = (el, script) => {
        const mode = (el.getAttribute('data-lazy-script-insert') || '').toLowerCase();
        let anchor = null;
        const targetSelector = el.getAttribute('data-lazy-script-target');
        if (targetSelector) {
            try {
                anchor = doc.querySelector(targetSelector);
            } catch (err) {
                anchor = null;
            }
        }
        if (!anchor) anchor = el;
        let inserted = false;

        const insert = (parent, ref) => {
            parent.insertBefore(script, ref);
            inserted = true;
        };

        if (mode === 'append' && anchor && anchor.appendChild) {
            anchor.appendChild(script);
            inserted = true;
        } else if (mode === 'prepend' && anchor && anchor.insertBefore) {
            anchor.insertBefore(script, anchor.firstChild);
            inserted = true;
        } else if (mode === 'before' && anchor && anchor.parentNode) {
            insert(anchor.parentNode, anchor);
        } else if (mode === 'after' && anchor && anchor.parentNode) {
            insert(anchor.parentNode, anchor.nextSibling);
        } else if (mode === 'body' && doc.body) {
            doc.body.appendChild(script);
            inserted = true;
        } else if (mode === 'head' && doc.head) {
            doc.head.appendChild(script);
            inserted = true;
        }

        if (!inserted) {
            (doc.body || doc.head || doc.documentElement).appendChild(script);
        }
    };

    const loadScript = (el) => {
        if (!el || el.dataset[scriptAttr.loaded] === '1') return;
        const src = el.getAttribute('data-lazy-script-src');
        if (!src) return;

        el.dataset[scriptAttr.loaded] = '1';
        const script = doc.createElement('script');
        script.src = src;

        const asyncAttr = el.getAttribute('data-lazy-script-async');
        if (asyncAttr) {
            const val = asyncAttr.toLowerCase();
            script.async = !(val === 'false' || val === '0' || val === 'no');
        } else {
            script.async = true;
        }

        const deferAttr = el.getAttribute('data-lazy-script-defer');
        if (deferAttr) {
            const val = deferAttr.toLowerCase();
            if (val !== 'false' && val !== '0' && val !== 'no') script.defer = true;
        }

        const scriptId = el.getAttribute('data-lazy-script-id');
        if (scriptId && !doc.getElementById(scriptId)) script.id = scriptId;

        const proxy = createDocumentWriteProxy(script);
        const releaseProxy = () => {
            if (!proxy) return;
            if (typeof proxy.flush === 'function') proxy.flush();
            if (typeof proxy.release === 'function') proxy.release();
        };

        listen(script, 'load', releaseProxy);
        listen(script, 'error', releaseProxy);
        listen(script, 'abort', releaseProxy);

        placeScript(el, script);

        if (proxy && typeof proxy.flush === 'function') global.setTimeout(proxy.flush, 0);
        global.setTimeout(releaseProxy, 15000);
    };

    const scheduleScriptLoad = (el, delay) => {
        if (!isFinite(delay) || delay <= 0) {
            if ('requestIdleCallback' in global) {
                global.requestIdleCallback(() => loadScript(el), { timeout: 2000 });
            } else {
                global.setTimeout(() => loadScript(el), 100);
            }
        } else {
            global.setTimeout(() => loadScript(el), delay);
        }
    };

    const bindScriptPlaceholder = (observer, el) => {
        if (!el || el.dataset[scriptAttr.bound] === '1') return;
        el.dataset[scriptAttr.bound] = '1';

        const delayAttr = el.getAttribute('data-lazy-script-delay');
        const delay = parseInt(delayAttr, 10);

        if (observer) {
            observer.observe(el);
        } else {
            const onLoad = () => {
                unlisten(global, 'load', onLoad);
                scheduleScriptLoad(el, delay);
            };
            if (doc.readyState === 'complete') onLoad();
            else listen(global, 'load', onLoad);
        }

        const fallbackAttr = el.getAttribute('data-lazy-script-timeout');
        const fallbackDelay = parseInt(fallbackAttr, 10);
        const timeout = isNaN(fallbackDelay) || fallbackDelay < 0 ? 10000 : fallbackDelay;
        global.setTimeout(() => loadScript(el), timeout);
    };

    Lazy.lazyLoadScripts = () => {
        const placeholders = $$('[data-lazy-script-src]');
        if (!placeholders.length) return;

        const observer = 'IntersectionObserver' in global ? new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                observer.unobserve(entry.target);
                const delayAttr = entry.target.getAttribute('data-lazy-script-delay');
                const delay = parseInt(delayAttr, 10);
                scheduleScriptLoad(entry.target, delay);
            });
        }, { rootMargin: '200px 0px', threshold: 0 }) : null;

        placeholders.forEach((el) => bindScriptPlaceholder(observer, el));

        if ('MutationObserver' in global) {
            const mo = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (!node || node.nodeType !== 1) return;
                        if (node.hasAttribute && node.hasAttribute('data-lazy-script-src')) bindScriptPlaceholder(observer, node);
                        if (node.querySelectorAll) {
                            node.querySelectorAll('[data-lazy-script-src]').forEach((child) => bindScriptPlaceholder(observer, child));
                        }
                    });
                });
            });
            mo.observe(doc.body || doc.documentElement, { childList: true, subtree: true });
        }
    };

    Lazy.refreshMedia = () => {
        Lazy.prepareInlineVideos();
        Lazy.prepareYoutubeEmbeds();
        Lazy.lazyLoadVideos();
        Lazy.lazyLoadYoutubeEmbeds();
    };

    Lazy.init = () => {
        Lazy.refreshMedia();
        Lazy.lazyLoadScripts();
    };

    runWhenReady(() => Lazy.init());

    listen(doc, 'foxiz:lazy-refresh', () => Lazy.refreshMedia());
    listen(doc, 'foxiz:lazy-scripts', () => Lazy.lazyLoadScripts());

    loadIconFont();

    global.FOXIZ_LAZY = Lazy;

}(typeof window !== 'undefined' ? window : this));