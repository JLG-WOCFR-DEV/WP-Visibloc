( function () {
    var ready = function (callback) {
        if (window.wp && typeof window.wp.domReady === 'function') {
            window.wp.domReady(callback);
            return;
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    };

    ready(function () {
        var sectionElements = Array.prototype.slice.call(
            document.querySelectorAll('[data-visibloc-section]')
        );
        var navLinks = Array.prototype.slice.call(
            document.querySelectorAll('.visibloc-help-nav__link')
        );

        if (!sectionElements.length || !navLinks.length) {
            return;
        }

        var sectionByKey = {};
        var sectionOrder = [];

        sectionElements.forEach(function (section) {
            var key = section.getAttribute('data-visibloc-section') || section.id;

            if (!key || Object.prototype.hasOwnProperty.call(sectionByKey, key)) {
                return;
            }

            sectionByKey[key] = section;
            sectionOrder.push(key);
        });

        if (!sectionOrder.length) {
            return;
        }

        var linkBySection = {};

        navLinks.forEach(function (link) {
            var hash = link.hash ? decodeURIComponent(link.hash.substring(1)) : '';

            if (
                !hash ||
                !Object.prototype.hasOwnProperty.call(sectionByKey, hash) ||
                Object.prototype.hasOwnProperty.call(linkBySection, hash)
            ) {
                return;
            }

            linkBySection[hash] = link;
        });

        var observedSectionKeys = sectionOrder.filter(function (key) {
            return Object.prototype.hasOwnProperty.call(linkBySection, key);
        });

        if (!observedSectionKeys.length) {
            return;
        }

        var ratioBySection = {};
        observedSectionKeys.forEach(function (key) {
            ratioBySection[key] = 0;
        });

        var activeClass = 'visibloc-help-nav__link--active';
        var currentActiveKey = null;
        var currentActiveLink = null;

        var setActiveLink = function (sectionKey) {
            if (currentActiveKey === sectionKey) {
                return;
            }

            if (currentActiveLink) {
                currentActiveLink.classList.remove(activeClass);
                currentActiveLink.removeAttribute('aria-current');
            }

            var nextLink = linkBySection[sectionKey];

            if (!nextLink) {
                currentActiveKey = null;
                currentActiveLink = null;
                return;
            }

            nextLink.classList.add(activeClass);
            nextLink.setAttribute('aria-current', 'page');
            currentActiveKey = sectionKey;
            currentActiveLink = nextLink;
        };

        var fallbackKey = observedSectionKeys[0];
        if (fallbackKey) {
            setActiveLink(fallbackKey);
        }

        navLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                var hash = link.hash ? decodeURIComponent(link.hash.substring(1)) : '';

                if (hash && Object.prototype.hasOwnProperty.call(linkBySection, hash)) {
                    setActiveLink(hash);
                }
            });
        });

        if (typeof window.IntersectionObserver === 'function') {
            var updateActiveFromRatios = function () {
                var bestKey = null;
                var bestRatio = -1;

                observedSectionKeys.forEach(function (key) {
                    var ratio = ratioBySection[key] || 0;

                    if (ratio > bestRatio + 0.0001) {
                        bestRatio = ratio;
                        bestKey = key;
                    }
                });

                if (bestKey && bestRatio > 0) {
                    setActiveLink(bestKey);
                }
            };

            var observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach(function (entry) {
                        var target = entry.target;
                        var key = target.getAttribute('data-visibloc-section') || target.id;

                        if (!key || !Object.prototype.hasOwnProperty.call(ratioBySection, key)) {
                            return;
                        }

                        var ratio = entry.isIntersecting ? entry.intersectionRatio : 0;
                        ratioBySection[key] = ratio;
                    });

                    updateActiveFromRatios();
                },
                {
                    threshold: [0, 0.1, 0.25, 0.5, 0.75, 1],
                    rootMargin: '0px 0px -40% 0px',
                }
            );

            observedSectionKeys.forEach(function (key) {
                var section = sectionByKey[key];

                if (section) {
                    observer.observe(section);
                }
            });

            return;
        }

        var fallbackAnimationId = null;
        var requestFallbackUpdate = function () {
            if (fallbackAnimationId) {
                return;
            }

            var raf = window.requestAnimationFrame || function (callback) {
                return window.setTimeout(callback, 16);
            };

            fallbackAnimationId = raf(function () {
                fallbackAnimationId = null;

                var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                var bestKey = null;
                var bestVisible = -1;

                observedSectionKeys.forEach(function (key) {
                    var section = sectionByKey[key];

                    if (!section) {
                        return;
                    }

                    var rect = section.getBoundingClientRect();
                    var top = rect.top;
                    var bottom = rect.bottom;

                    if (viewportHeight <= 0) {
                        return;
                    }

                    var visible = Math.min(bottom, viewportHeight) - Math.max(top, 0);
                    visible = visible > 0 ? visible : 0;

                    if (visible > bestVisible + 0.5) {
                        bestVisible = visible;
                        bestKey = key;
                    }
                });

                if (bestKey && bestVisible > 0) {
                    setActiveLink(bestKey);
                }
            });
        };

        requestFallbackUpdate();
        window.addEventListener('scroll', requestFallbackUpdate, { passive: true });
        window.addEventListener('resize', requestFallbackUpdate);
    });
})();
