(function () {
    'use strict';

    var normalizeText = function (value) {
        if (value === null || value === undefined) {
            return '';
        }

        var normalized = String(value).trim().toLowerCase();

        if (normalized && typeof normalized.normalize === 'function') {
            normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        return normalized;
    };

    var initSearch = function (input) {
        var targetId = input.getAttribute('data-visibloc-blocks-target');
        if (!targetId) {
            return;
        }

        var container = document.getElementById(targetId);
        if (!container) {
            return;
        }

        var items = Array.prototype.slice.call(
            container.querySelectorAll('[data-visibloc-block]')
        );
        if (!items.length) {
            return;
        }

        var emptyMessage = container.querySelector('[data-visibloc-blocks-empty]');
        var countMessage = container.querySelector('[data-visibloc-blocks-count]');
        var countTemplate = countMessage ? countMessage.getAttribute('data-visibloc-count-template') : '';

        var getNormalizedSearchValue = function (item) {
            var cached = item.getAttribute('data-visibloc-search-cache');

            if (cached !== null) {
                return cached;
            }

            var normalized = normalizeText(item.getAttribute('data-visibloc-search-value') || '');
            item.setAttribute('data-visibloc-search-cache', normalized);

            return normalized;
        };

        var toggleItems = function () {
            var normalizedQuery = normalizeText(input.value || '');
            var hasQuery = normalizedQuery.length > 0;
            var visibleCount = 0;

            items.forEach(function (item) {
                var searchValue = getNormalizedSearchValue(item);
                var isVisible = !hasQuery || searchValue.indexOf(normalizedQuery) !== -1;

                item.style.display = isVisible ? '' : 'none';

                if (isVisible) {
                    visibleCount++;
                }
            });

            if (emptyMessage) {
                emptyMessage.hidden = visibleCount > 0;
            }

            if (countMessage && countTemplate) {
                var output = countTemplate.replace('%d', visibleCount);
                countMessage.textContent = output;
            }
        };

        input.addEventListener('input', toggleItems);
        input.addEventListener('search', toggleItems);

        toggleItems();
    };

    var onReady = function () {
        var searchInputs = document.querySelectorAll('[data-visibloc-blocks-search]');
        if (!searchInputs.length) {
            return;
        }

        Array.prototype.forEach.call(searchInputs, function (input) {
            initSearch(input);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady);
    } else {
        onReady();
    }
})();
