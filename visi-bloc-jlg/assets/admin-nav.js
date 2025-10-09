(function () {
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

    var activateGroup = function (groupId, context, options) {
        if (!groupId || !context.tabs[groupId] || !context.panels[groupId]) {
            return;
        }

        var settings = options || {};
        var focusTab = Boolean(settings.focusTab);

        if (context.activeGroup === groupId) {
            if (focusTab && typeof context.tabs[groupId].focus === 'function') {
                context.tabs[groupId].focus();
            }

            return;
        }

        Object.keys(context.panels).forEach(function (id) {
            var panel = context.panels[id];
            var tab = context.tabs[id];
            var isActive = id === groupId;

            panel.classList.toggle('is-active', isActive);

            if (isActive) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', 'hidden');
            }

            if (tab) {
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                tab.setAttribute('tabindex', isActive ? '0' : '-1');
            }
        });

        context.activeGroup = groupId;

        if (context.select && context.select.value !== groupId) {
            context.select.value = groupId;
        }

        if (focusTab && typeof context.tabs[groupId].focus === 'function') {
            try {
                context.tabs[groupId].focus({ preventScroll: true });
            } catch (error) {
                context.tabs[groupId].focus();
            }
        }
    };

    ready(function () {
        var tabElements = Array.prototype.slice.call(
            document.querySelectorAll('[data-visibloc-group-tab]')
        );
        var panelElements = Array.prototype.slice.call(
            document.querySelectorAll('[data-visibloc-group-panel]')
        );
        var selectElement = document.querySelector('[data-visibloc-group-picker]');

        if (!tabElements.length || !panelElements.length) {
            return;
        }

        var context = {
            tabs: {},
            panels: {},
            select: selectElement,
            activeGroup: null,
        };

        var orderedIds = tabElements
            .map(function (element) {
                return element.getAttribute('data-visibloc-group-tab');
            })
            .filter(Boolean);

        tabElements.forEach(function (tab) {
            var groupId = tab.getAttribute('data-visibloc-group-tab');

            if (!groupId) {
                return;
            }

            context.tabs[groupId] = tab;
            tab.setAttribute('role', 'tab');

            tab.addEventListener('click', function (event) {
                event.preventDefault();
                activateGroup(groupId, context, { focusTab: true });
            });

            tab.addEventListener('keydown', function (event) {
                var key = event.key;

                if (!key) {
                    return;
                }

                var currentIndex = orderedIds.indexOf(groupId);

                if (currentIndex === -1) {
                    return;
                }

                if (key === 'ArrowRight' || key === 'ArrowDown') {
                    event.preventDefault();
                    var nextIndex = (currentIndex + 1) % orderedIds.length;
                    activateGroup(orderedIds[nextIndex], context, { focusTab: true });
                } else if (key === 'ArrowLeft' || key === 'ArrowUp') {
                    event.preventDefault();
                    var prevIndex = (currentIndex - 1 + orderedIds.length) % orderedIds.length;
                    activateGroup(orderedIds[prevIndex], context, { focusTab: true });
                } else if (key === 'Home') {
                    event.preventDefault();
                    activateGroup(orderedIds[0], context, { focusTab: true });
                } else if (key === 'End') {
                    event.preventDefault();
                    activateGroup(orderedIds[orderedIds.length - 1], context, { focusTab: true });
                }
            });
        });

        panelElements.forEach(function (panel) {
            var groupId = panel.getAttribute('data-visibloc-group-panel');

            if (!groupId) {
                return;
            }

            context.panels[groupId] = panel;
            panel.setAttribute('role', 'tabpanel');
        });

        if (selectElement) {
            selectElement.addEventListener('change', function (event) {
                var value = event.target && event.target.value ? event.target.value : '';

                if (!value) {
                    return;
                }

                activateGroup(value, context);
            });
        }

        var initialGroup = orderedIds[0] || null;

        if (initialGroup) {
            activateGroup(initialGroup, context);
        }
    });
})();
