(function () {
    function initRoleSwitcher() {
        var container = document.querySelector('[data-visibloc-role-switcher]');

        if (!container) {
            return;
        }

        var toggle = container.querySelector('.visibloc-mobile-role-switcher__toggle');
        var panel = container.querySelector('.visibloc-mobile-role-switcher__panel');
        var closeButtons = container.querySelectorAll('[data-visibloc-role-switcher-close]');
        var currentLabelElement = container.querySelector('[data-visibloc-role-switcher-label]');
        var prefixElement = container.querySelector('.visibloc-mobile-role-switcher__toggle-prefix');
        var availableLinks = container.querySelectorAll('.visibloc-mobile-role-switcher__link');
        var resetLink = container.querySelector('.visibloc-mobile-role-switcher__reset');
        var prefixDefault = prefixElement ? prefixElement.getAttribute('data-visibloc-role-switcher-prefix-default') : '';
        var prefixWithLabel = prefixElement ? prefixElement.getAttribute('data-visibloc-role-switcher-prefix-with-label') : '';
        var openClass = 'visibloc-mobile-role-switcher--open';
        var lockClass = 'visibloc-role-switcher--locked';
        var scrollLockTarget = document.documentElement || document.body;
        var bodyElement = document.body;
        var scrollBarWidthProperty = '--visibloc-role-switcher-scrollbar-width';
        var bodyPaddingProperty = '--visibloc-role-switcher-body-padding-right';
        var containerRemovalObserver = null;
        var attributeObserver = null;
        var focusableSelectors = [
            'a[href]:not([tabindex="-1"])',
            'area[href]',
            'button:not([disabled]):not([tabindex="-1"])',
            'input:not([type="hidden"]):not([disabled]):not([tabindex="-1"])',
            'select:not([disabled]):not([tabindex="-1"])',
            'textarea:not([disabled]):not([tabindex="-1"])',
            'iframe',
            'object',
            'embed',
            '[contenteditable="true"]',
            '[tabindex]:not([tabindex="-1"])'
        ].join(', ');
        var inertRestoreQueue = [];
        var managedInertAttribute = 'data-visibloc-role-switcher-inert';

        if (!toggle || !panel) {
            return;
        }

        function isFocusableElement(element) {
            if (!element || typeof element !== 'object') {
                return false;
            }

            if (element.hasAttribute('disabled')) {
                return false;
            }

            if ('true' === element.getAttribute('aria-hidden')) {
                return false;
            }

            if (element.closest('[hidden]')) {
                return false;
            }

            var computedStyle = window.getComputedStyle ? window.getComputedStyle(element) : null;

            if (computedStyle && ('none' === computedStyle.display || 'hidden' === computedStyle.visibility)) {
                return false;
            }

            if (element.offsetWidth <= 0 && element.offsetHeight <= 0 && element.getClientRects().length === 0) {
                return false;
            }

            return true;
        }

        function getPanelFocusableElements() {
            var elements = panel.querySelectorAll(focusableSelectors);

            return Array.prototype.filter.call(elements, function (element) {
                return panel.contains(element) && isFocusableElement(element);
            });
        }

        function applyPrefix(hasLabel) {
            if (!prefixElement) {
                return;
            }

            var targetText = hasLabel
                ? (prefixWithLabel || prefixElement.textContent || '')
                : (prefixDefault || prefixElement.textContent || '');

            if (targetText) {
                prefixElement.textContent = targetText;
            }
        }

        function applyLabel(labelText) {
            if (!currentLabelElement) {
                return;
            }

            var trimmed = '';

            if ('string' === typeof labelText) {
                trimmed = labelText.trim();
            }

            if (trimmed) {
                currentLabelElement.textContent = trimmed;
                applyPrefix(true);
            } else {
                currentLabelElement.textContent = '';
                applyPrefix(false);
            }
        }

        function setLabel(labelText) {
            applyLabel(labelText);

            if (!container) {
                return;
            }

            var trimmed = '';

            if ('string' === typeof labelText) {
                trimmed = labelText.trim();
            }

            if (trimmed) {
                container.setAttribute('data-visibloc-role-switcher-current-label', trimmed);
            } else {
                container.removeAttribute('data-visibloc-role-switcher-current-label');
            }
        }

        function toggleOutsideInert(shouldDisable) {
            if (!container.parentElement || !document || !document.body) {
                return;
            }

            if (shouldDisable) {
                var processed = [];
                inertRestoreQueue = [];
                var branch = container;
                var parent = branch.parentElement;

                while (parent) {
                    Array.prototype.forEach.call(parent.children, function (sibling) {
                        if (sibling === branch) {
                            return;
                        }

                        if (processed.indexOf(sibling) !== -1) {
                            return;
                        }

                        processed.push(sibling);

                        inertRestoreQueue.push({
                            element: sibling,
                            ariaHidden: sibling.getAttribute('aria-hidden'),
                            hadInert: sibling.hasAttribute('inert'),
                            inertValue: sibling.getAttribute('inert')
                        });

                        sibling.setAttribute('aria-hidden', 'true');

                        if (!sibling.hasAttribute('inert')) {
                            sibling.setAttribute('inert', '');
                        }

                        sibling.setAttribute(managedInertAttribute, 'true');
                    });

                    branch = parent;
                    parent = parent.parentElement;
                }
            } else if (inertRestoreQueue.length > 0) {
                inertRestoreQueue.forEach(function (state) {
                    var element = state.element;

                    if (!element) {
                        return;
                    }

                    element.removeAttribute(managedInertAttribute);

                    if (null === state.ariaHidden) {
                        element.removeAttribute('aria-hidden');
                    } else {
                        element.setAttribute('aria-hidden', state.ariaHidden);
                    }

                    if (state.hadInert) {
                        if (null === state.inertValue) {
                            element.setAttribute('inert', '');
                        } else {
                            element.setAttribute('inert', state.inertValue);
                        }
                    } else {
                        element.removeAttribute('inert');
                    }
                });

                inertRestoreQueue = [];
            }
        }

        function applyScrollLock() {
            if (!scrollLockTarget) {
                return;
            }

            var measurementElement = document.documentElement || scrollLockTarget;
            var scrollbarWidth = 0;

            if (typeof window !== 'undefined' && window.innerWidth && measurementElement) {
                scrollbarWidth = window.innerWidth - measurementElement.clientWidth;
            }

            if (scrollbarWidth > 0) {
                scrollLockTarget.style.setProperty(scrollBarWidthProperty, scrollbarWidth + 'px');
            } else {
                scrollLockTarget.style.removeProperty(scrollBarWidthProperty);
            }

            if (bodyElement) {
                var computedBodyPaddingRight = window.getComputedStyle
                    ? window.getComputedStyle(bodyElement).paddingRight
                    : '0px';

                if (!computedBodyPaddingRight) {
                    computedBodyPaddingRight = '0px';
                }

                bodyElement.style.setProperty(bodyPaddingProperty, computedBodyPaddingRight);
            }

            if (!scrollLockTarget.classList.contains(lockClass)) {
                scrollLockTarget.classList.add(lockClass);
            }
        }

        function removeScrollLock() {
            if (!scrollLockTarget) {
                return;
            }

            scrollLockTarget.classList.remove(lockClass);
            scrollLockTarget.style.removeProperty(scrollBarWidthProperty);

            if (bodyElement) {
                bodyElement.style.removeProperty(bodyPaddingProperty);
            }
        }

        function cleanupPanelState() {
            toggleOutsideInert(false);
            removeScrollLock();
        }

        if (currentLabelElement) {
            var initialLabel = container.getAttribute('data-visibloc-role-switcher-current-label');

            if (!initialLabel) {
                initialLabel = currentLabelElement.textContent || '';
            }

            if (initialLabel) {
                setLabel(initialLabel);
            } else {
                applyLabel('');
            }
        }

        if (availableLinks && availableLinks.length) {
            Array.prototype.forEach.call(availableLinks, function (link) {
                link.addEventListener('click', function () {
                    var linkLabel = link ? link.textContent : '';

                    setLabel(linkLabel || '');
                });
            });
        }

        if (resetLink) {
            resetLink.addEventListener('click', function () {
                setLabel('');
            });
        }

        function openPanel() {
            container.classList.add(openClass);
            panel.removeAttribute('hidden');
            panel.setAttribute('aria-hidden', 'false');
            toggle.setAttribute('aria-expanded', 'true');
            toggleOutsideInert(true);
            applyScrollLock();

            var focusTarget = panel.querySelector('.visibloc-mobile-role-switcher__link, .visibloc-mobile-role-switcher__reset');

            if (focusTarget && typeof focusTarget.focus === 'function') {
                focusTarget.focus();
            }
        }

        function closePanel() {
            container.classList.remove(openClass);
            toggleOutsideInert(false);
            removeScrollLock();

            if (!panel.hasAttribute('hidden')) {
                panel.setAttribute('hidden', '');
            }

            panel.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
        }

        function focusToggle() {
            if (toggle && typeof toggle.focus === 'function') {
                toggle.focus();
            }
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
                focusToggle();
            });
        });

        document.addEventListener('click', function (event) {
            if (!container.classList.contains(openClass)) {
                return;
            }

            if (!container.contains(event.target)) {
                closePanel();
                focusToggle();
            }
        });

        container.addEventListener('keydown', function (event) {
            if ('Escape' === event.key || 'Esc' === event.key) {
                closePanel();
                focusToggle();

                return;
            }

            if (!container.classList.contains(openClass)) {
                return;
            }

            if ('Tab' !== event.key && 9 !== event.keyCode) {
                return;
            }

            var focusableElements = getPanelFocusableElements();

            if (0 === focusableElements.length) {
                event.preventDefault();
                event.stopPropagation();

                if (typeof event.stopImmediatePropagation === 'function') {
                    event.stopImmediatePropagation();
                }

                return;
            }

            var firstElement = focusableElements[0];
            var lastElement = focusableElements[focusableElements.length - 1];
            var activeElement = document.activeElement;
            var nextElement = null;

            if (1 === focusableElements.length) {
                nextElement = firstElement;
            } else if (event.shiftKey) {
                if (activeElement === firstElement || !panel.contains(activeElement)) {
                    nextElement = lastElement;
                }
            } else if (activeElement === lastElement || !panel.contains(activeElement)) {
                nextElement = firstElement;
            }

            if (!nextElement) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (typeof event.stopImmediatePropagation === 'function') {
                event.stopImmediatePropagation();
            }

            if (typeof nextElement.focus === 'function') {
                nextElement.focus();
            }
        });

        if (typeof window !== 'undefined' && typeof window.addEventListener === 'function') {
            window.addEventListener('beforeunload', cleanupPanelState);
            window.addEventListener('pagehide', cleanupPanelState);
        }

        if (typeof window !== 'undefined' && 'MutationObserver' in window && document.body) {
            attributeObserver = new window.MutationObserver(function (mutations) {
                if (!mutations) {
                    return;
                }

                mutations.forEach(function (mutation) {
                    if (!mutation || mutation.type !== 'attributes' || mutation.attributeName !== 'data-visibloc-role-switcher-current-label') {
                        return;
                    }

                    var newLabel = container.getAttribute('data-visibloc-role-switcher-current-label') || '';

                    applyLabel(newLabel);
                });
            });

            attributeObserver.observe(container, {
                attributes: true,
                attributeFilter: ['data-visibloc-role-switcher-current-label']
            });

            containerRemovalObserver = new window.MutationObserver(function () {
                if (document.body && document.body.contains(container)) {
                    return;
                }

                cleanupPanelState();

                if (containerRemovalObserver) {
                    containerRemovalObserver.disconnect();
                    containerRemovalObserver = null;
                }

                if (attributeObserver) {
                    attributeObserver.disconnect();
                    attributeObserver = null;
                }
            });

            containerRemovalObserver.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRoleSwitcher);
    } else {
        initRoleSwitcher();
    }
})();
