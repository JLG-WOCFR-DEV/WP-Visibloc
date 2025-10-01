(function () {
    function initRoleSwitcher() {
        var container = document.querySelector('[data-visibloc-role-switcher]');

        if (!container) {
            return;
        }

        var toggle = container.querySelector('.visibloc-mobile-role-switcher__toggle');
        var panel = container.querySelector('.visibloc-mobile-role-switcher__panel');
        var closeButtons = container.querySelectorAll('[data-visibloc-role-switcher-close]');
        var openClass = 'visibloc-mobile-role-switcher--open';

        if (!toggle || !panel) {
            return;
        }

        function openPanel() {
            container.classList.add(openClass);
            panel.removeAttribute('hidden');
            toggle.setAttribute('aria-expanded', 'true');

            var focusTarget = panel.querySelector('.visibloc-mobile-role-switcher__link, .visibloc-mobile-role-switcher__reset');

            if (focusTarget && typeof focusTarget.focus === 'function') {
                focusTarget.focus();
            }
        }

        function closePanel() {
            container.classList.remove(openClass);

            if (!panel.hasAttribute('hidden')) {
                panel.setAttribute('hidden', '');
            }

            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            if (container.classList.contains(openClass)) {
                closePanel();
            } else {
                openPanel();
            }
        });

        Array.prototype.forEach.call(closeButtons, function (button) {
            button.addEventListener('click', function () {
                closePanel();
                toggle.focus();
            });
        });

        document.addEventListener('click', function (event) {
            if (!container.classList.contains(openClass)) {
                return;
            }

            if (!container.contains(event.target)) {
                closePanel();
            }
        });

        container.addEventListener('keydown', function (event) {
            if ('Escape' === event.key || 'Esc' === event.key) {
                closePanel();
                toggle.focus();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRoleSwitcher);
    } else {
        initRoleSwitcher();
    }
})();
