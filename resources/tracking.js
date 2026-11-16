/**
 * Ad Placr: reports an impression when an ad is at least half visible, and a click when it is clicked.
 * Each is sent once per ad on the page. No cookies, no personal data; a failed report is ignored.
 */
(function () {
    'use strict';

    var adplacrUrl = '/adplacr/track';
    var adplacrSelector = '.adplacr[data-ad-id][data-version-id]';

    function adplacrSend(event, el) {
        var ad = parseInt(el.getAttribute('data-ad-id') || '0', 10);
        var version = el.getAttribute('data-version-id') || '';

        if (!ad || !version) {
            return;
        }

        try {
            fetch(adplacrUrl, {
                method: 'POST',
                credentials: 'omit',
                keepalive: true,
                headers: { 'Content-Type': 'application/json', 'X-Adplacr': '1' },
                body: JSON.stringify({ event: event, ad: ad, version: version })
            }).catch(function () {});
        } catch (e) {
            /* tracking must never break the page */
        }
    }

    function adplacrWatchImpressions() {
        var nodes = document.querySelectorAll(adplacrSelector);

        if (!nodes.length || typeof IntersectionObserver === 'undefined') {
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var el = entry.target;

                if (!entry.isIntersecting || el.getAttribute('data-adplacr-seen') === '1') {
                    return;
                }

                el.setAttribute('data-adplacr-seen', '1');
                observer.unobserve(el);
                adplacrSend('impression', el);
            });
        }, { threshold: [0.5] });

        nodes.forEach(function (el) {
            observer.observe(el);
        });
    }

    function adplacrWatchClicks() {
        document.addEventListener('click', function (event) {
            var target = event.target;
            var el = target && target.closest ? target.closest(adplacrSelector) : null;

            if (!el || el.getAttribute('data-adplacr-clicked') === '1') {
                return;
            }

            el.setAttribute('data-adplacr-clicked', '1');
            adplacrSend('click', el);
        }, true);
    }

    function adplacrStart() {
        adplacrWatchImpressions();
        adplacrWatchClicks();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', adplacrStart);
    } else {
        adplacrStart();
    }
})();
