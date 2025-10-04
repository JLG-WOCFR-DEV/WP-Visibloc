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

    var getRowSearchValue = function (row) {
        var cached = row.getAttribute('data-search-cache');

        if (cached !== null) {
            return cached;
        }

        var raw = row.getAttribute('data-search-value');

        if (!raw) {
            raw = row.textContent || '';
        }

        var normalized = normalizeText(raw);
        row.setAttribute('data-search-cache', normalized);

        return normalized;
    };

    var getRowTitleValue = function (row) {
        var title = row.getAttribute('data-title');

        if (!title) {
            title = row.textContent || '';
        }

        return normalizeText(title);
    };

    var getRowBlockCount = function (row) {
        var value = parseInt(row.getAttribute('data-block-count') || '', 10);

        return isNaN(value) ? 0 : value;
    };

    var getRowDeadline = function (row) {
        var value = parseInt(row.getAttribute('data-next-deadline') || '', 10);

        return isNaN(value) ? Number.POSITIVE_INFINITY : value;
    };

    var updateCount = function (countNode, count) {
        if (!countNode) {
            return;
        }

        var template = countNode.getAttribute('data-count-template') || '';

        if (template) {
            countNode.textContent = template.replace('%d', String(count));
        } else {
            countNode.textContent = String(count);
        }
    };

    var initDashboard = function (container) {
        var rows = Array.prototype.slice.call(
            container.querySelectorAll('[data-visibloc-dashboard-row]')
        );

        if (!rows.length) {
            return;
        }

        var controls = container.querySelector('[data-visibloc-dashboard-controls]');
        var typeSelect = controls ? controls.querySelector('[data-visibloc-filter="post-type"]') : null;
        var searchInput = controls ? controls.querySelector('[data-visibloc-filter="search"]') : null;
        var sortSelect = controls ? controls.querySelector('[data-visibloc-filter="sort"]') : null;
        var countNode = controls ? controls.querySelector('[data-visibloc-dashboard-count]') : null;
        var tbody = container.querySelector('tbody');
        var emptyMessage = container.querySelector('[data-visibloc-dashboard-empty]');

        if (!tbody) {
            return;
        }

        var applyFilters = function () {
            var selectedType = typeSelect ? typeSelect.value : '';
            var normalizedSearch = searchInput ? normalizeText(searchInput.value || '') : '';
            var sortValue = sortSelect ? sortSelect.value : 'title';

            var visibleRows = [];

            rows.forEach(function (row) {
                var matchesType = true;

                if (selectedType) {
                    matchesType = row.getAttribute('data-post-type') === selectedType;
                }

                var matchesSearch = true;

                if (matchesType && normalizedSearch) {
                    matchesSearch = getRowSearchValue(row).indexOf(normalizedSearch) !== -1;
                }

                var isVisible = matchesType && matchesSearch;
                row.hidden = !isVisible;

                if (isVisible) {
                    visibleRows.push(row);
                }
            });

            var compareTitle = function (a, b) {
                var titleA = getRowTitleValue(a);
                var titleB = getRowTitleValue(b);

                if (titleA < titleB) {
                    return -1;
                }

                if (titleA > titleB) {
                    return 1;
                }

                return 0;
            };

            if ('blocks-desc' === sortValue) {
                visibleRows.sort(function (a, b) {
                    var diff = getRowBlockCount(b) - getRowBlockCount(a);

                    if (diff !== 0) {
                        return diff;
                    }

                    return compareTitle(a, b);
                });
            } else if ('deadline' === sortValue) {
                visibleRows.sort(function (a, b) {
                    var deadlineDiff = getRowDeadline(a) - getRowDeadline(b);

                    if (deadlineDiff !== 0) {
                        return deadlineDiff;
                    }

                    return compareTitle(a, b);
                });
            } else {
                visibleRows.sort(compareTitle);
            }

            var fragment = document.createDocumentFragment();

            visibleRows.forEach(function (row) {
                fragment.appendChild(row);
            });

            tbody.appendChild(fragment);

            updateCount(countNode, visibleRows.length);

            if (emptyMessage) {
                emptyMessage.hidden = visibleRows.length > 0;
            }
        };

        if (typeSelect) {
            typeSelect.addEventListener('change', applyFilters);
        }

        if (sortSelect) {
            sortSelect.addEventListener('change', applyFilters);
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
            searchInput.addEventListener('search', applyFilters);
        }

        applyFilters();
    };

    var onReady = function () {
        var dashboards = document.querySelectorAll('[data-visibloc-dashboard]');

        if (!dashboards.length) {
            return;
        }

        Array.prototype.forEach.call(dashboards, function (dashboard) {
            initDashboard(dashboard);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady);
    } else {
        onReady();
    }
})();
