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

    var formatCountMessage = function (template, visibleCount, selectedCount) {
        if (!template) {
            return '';
        }

        var output = template;

        output = output.replace(/%1\$d/g, String(visibleCount));
        output = output.replace(/%2\$d/g, String(selectedCount));

        if (/%d/.test(output) && !/%1\$d/.test(template)) {
            output = output.replace(/%d/g, String(visibleCount));
        }

        return output;
    };

    var containerContexts = new WeakMap();

    var getContainerContext = function (container) {
        var existing = containerContexts.get(container);
        if (existing) {
            return existing;
        }

        var context = {
            container: container,
            items: Array.prototype.slice.call(
                container.querySelectorAll('[data-visibloc-block]')
            ),
            emptyMessage: container.querySelector('[data-visibloc-blocks-empty]'),
            countMessage: container.querySelector('[data-visibloc-blocks-count]')
        };

        context.countTemplate = context.countMessage
            ? context.countMessage.getAttribute('data-visibloc-count-template') || ''
            : '';

        containerContexts.set(container, context);

        return context;
    };

    var updateEmptyState = function (context, visibleCount) {
        if (!context.emptyMessage) {
            return;
        }

        context.emptyMessage.hidden = visibleCount > 0;
    };

    var updateCountMessage = function (context, counts) {
        if (!context.countMessage || !context.countTemplate) {
            return;
        }

        var hasVisible = counts && typeof counts.visibleCount === 'number';
        var hasSelected = counts && typeof counts.selectedCount === 'number';
        var visibleCount = hasVisible ? counts.visibleCount : 0;
        var selectedCount = hasSelected ? counts.selectedCount : 0;

        if (!hasVisible || !hasSelected) {
            context.items.forEach(function (item) {
                var input = item.querySelector('input[type="checkbox"]');
                var isVisible = item.style.display !== 'none';

                if (!hasVisible && isVisible) {
                    visibleCount++;
                }

                if (!hasSelected && input && input.checked) {
                    selectedCount++;
                }
            });
        }

        var output = formatCountMessage(context.countTemplate, visibleCount, selectedCount);
        context.countMessage.textContent = output;
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

        var context = getContainerContext(container);
        if (!context.items.length) {
            return;
        }

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
            var selectedCount = 0;

            context.items.forEach(function (item) {
                var searchValue = getNormalizedSearchValue(item);
                var isVisible = !hasQuery || searchValue.indexOf(normalizedQuery) !== -1;

                item.style.display = isVisible ? '' : 'none';

                if (isVisible) {
                    visibleCount++;
                }

                var inputElement = item.querySelector('input[type="checkbox"]');
                if (inputElement && inputElement.checked) {
                    selectedCount++;
                }
            });

            updateEmptyState(context, visibleCount);
            updateCountMessage(context, {
                visibleCount: visibleCount,
                selectedCount: selectedCount
            });
        };

        input.addEventListener('input', toggleItems);
        input.addEventListener('search', toggleItems);

        toggleItems();
    };

    var initMassAction = function (button, shouldCheck) {
        var targetId = button.getAttribute('data-visibloc-blocks-target');
        if (!targetId) {
            return;
        }

        var container = document.getElementById(targetId);
        if (!container) {
            return;
        }

        var context = getContainerContext(container);
        if (!context.items.length) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();

            context.items.forEach(function (item) {
                if (item.style.display === 'none') {
                    return;
                }

                var inputElement = item.querySelector('input[type="checkbox"]');
                if (!inputElement || inputElement.disabled) {
                    return;
                }

                if (inputElement.checked === shouldCheck) {
                    return;
                }

                inputElement.checked = shouldCheck;
                var changeEvent = new window.Event('change', { bubbles: true });
                inputElement.dispatchEvent(changeEvent);
            });

            updateCountMessage(context);

            if (typeof button.focus === 'function') {
                try {
                    button.focus({ preventScroll: true });
                } catch (error) {
                    button.focus();
                }
            }
        });
    };

    var initPreset = function (button) {
        var targetId = button.getAttribute('data-visibloc-blocks-target');
        var presetRaw = button.getAttribute('data-visibloc-block-preset');

        if (!targetId || !presetRaw) {
            return;
        }

        var container = document.getElementById(targetId);

        if (!container) {
            return;
        }

        var context = getContainerContext(container);

        if (!context.items.length) {
            return;
        }

        var presetBlocks;

        try {
            presetBlocks = JSON.parse(presetRaw);
        } catch (error) {
            presetBlocks = [];
        }

        if (!Array.isArray(presetBlocks) || !presetBlocks.length) {
            return;
        }

        var normalizedMap = presetBlocks.reduce(function (accumulator, blockName) {
            if (typeof blockName === 'string' && blockName.trim()) {
                accumulator[blockName] = true;
            }

            return accumulator;
        }, {});

        button.addEventListener('click', function (event) {
            event.preventDefault();

            context.items.forEach(function (item) {
                var inputElement = item.querySelector('input[type="checkbox"]');

                if (!inputElement || inputElement.disabled) {
                    return;
                }

                var value = inputElement.value || '';

                if (!Object.prototype.hasOwnProperty.call(normalizedMap, value)) {
                    return;
                }

                if (!inputElement.checked) {
                    inputElement.checked = true;
                    var changeEvent = new window.Event('change', { bubbles: true });
                    inputElement.dispatchEvent(changeEvent);
                }
            });

            updateCountMessage(context);

            if (typeof button.focus === 'function') {
                try {
                    button.focus({ preventScroll: true });
                } catch (error) {
                    button.focus();
                }
            }
        });
    };

    var onReady = function () {
        var searchInputs = document.querySelectorAll('[data-visibloc-blocks-search]');
        Array.prototype.forEach.call(searchInputs, initSearch);

        var selectAllButtons = document.querySelectorAll('[data-visibloc-select-all]');
        Array.prototype.forEach.call(selectAllButtons, function (button) {
            initMassAction(button, true);
        });

        var selectNoneButtons = document.querySelectorAll('[data-visibloc-select-none]');
        Array.prototype.forEach.call(selectNoneButtons, function (button) {
            initMassAction(button, false);
        });

        var presetButtons = document.querySelectorAll('[data-visibloc-block-preset]');
        Array.prototype.forEach.call(presetButtons, initPreset);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady);
    } else {
        onReady();
    }
})();
