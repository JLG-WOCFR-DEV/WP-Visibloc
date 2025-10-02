(function () {
    'use strict';

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

        var toggleItems = function () {
            var query = input.value || '';
            var normalizedQuery = query.trim().toLowerCase();
            var visibleCount = 0;

            items.forEach(function (item) {
                var searchValue = item.getAttribute('data-visibloc-search-value') || '';
                var isVisible = !normalizedQuery || searchValue.indexOf(normalizedQuery) !== -1;

                item.style.display = isVisible ? '' : 'none';

                if (isVisible) {
                    visibleCount++;
                }
            });

            if (emptyMessage) {
                emptyMessage.hidden = visibleCount > 0;
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
