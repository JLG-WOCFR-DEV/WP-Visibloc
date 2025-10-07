/* global wp */
(function () {
    'use strict';

    var i18n = (typeof wp !== 'undefined' && wp.i18n) ? wp.i18n : null;
    var __ = i18n && i18n.__ ? i18n.__ : function (text) { return text; };
    var sprintf = i18n && i18n.sprintf ? i18n.sprintf : function (template) {
        var args = Array.prototype.slice.call(arguments, 1);
        return template.replace(/%([0-9]+)\$s/g, function (_, index) {
            var position = parseInt(index, 10) - 1;
            return typeof args[position] !== 'undefined' ? args[position] : '';
        });
    };

    function getFocusableElements(container) {
        if (!container) {
            return [];
        }

        var selector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
        var elements = Array.prototype.slice.call(container.querySelectorAll(selector));

        return elements.filter(function (element) {
            if (element.hasAttribute('disabled')) {
                return false;
            }

            if (element.getAttribute('aria-hidden') === 'true') {
                return false;
            }

            if (element.closest('[hidden]')) {
                return false;
            }

            var rect = element.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        });
    }

    function parseBlocks(card) {
        if (!card) {
            return [];
        }

        var raw = card.getAttribute('data-recipe-blocks');

        if (!raw) {
            return [];
        }

        try {
            var decoded = JSON.parse(raw);
            if (Array.isArray(decoded)) {
                return decoded.filter(function (item) {
                    return typeof item === 'string' && item.trim() !== '';
                });
            }
        } catch (error) {
            // Ignore malformed JSON and fall back to an empty array.
        }

        return [];
    }

    function parseTemplateSteps(template) {
        if (!template) {
            return [];
        }

        var fragment;

        if (template.content) {
            fragment = template.content.cloneNode(true);
        } else {
            fragment = document.createDocumentFragment();
            var wrapper = document.createElement('div');
            wrapper.innerHTML = template.innerHTML;
            while (wrapper.firstChild) {
                fragment.appendChild(wrapper.firstChild);
            }
        }

        return Array.prototype.slice.call(fragment.querySelectorAll('[data-visibloc-recipe-step]'));
    }

    function createPanelStructure(stepNode, panelId, tabId) {
        var panel = document.createElement('div');
        panel.className = 'visibloc-guided-recipes__stepper-panel';
        panel.setAttribute('role', 'tabpanel');
        panel.setAttribute('id', panelId);
        panel.setAttribute('aria-labelledby', tabId);
        panel.tabIndex = 0;

        var title = stepNode.getAttribute('data-step-title') || __('Étape', 'visi-bloc-jlg');
        var summary = stepNode.getAttribute('data-step-summary') || '';

        var heading = document.createElement('h4');
        heading.className = 'visibloc-guided-recipes__step-title';
        heading.textContent = title;
        panel.appendChild(heading);

        if (summary) {
            var intro = document.createElement('p');
            intro.className = 'visibloc-guided-recipes__step-intro';
            intro.textContent = summary;
            panel.appendChild(intro);
        }

        while (stepNode.firstChild) {
            panel.appendChild(stepNode.firstChild);
        }

        return {
            panel: panel,
            title: title
        };
    }

    function normalizeCountMessage(count, emptyFallback) {
        if (count === 0) {
            return emptyFallback || '';
        }

        if (count === 1) {
            return __('1 recette disponible', 'visi-bloc-jlg');
        }

        return sprintf(__('%d recettes disponibles', 'visi-bloc-jlg'), count);
    }

    function initialize() {
        var container = document.querySelector('[data-visibloc-recipes]');

        if (!container) {
            return;
        }

        var cards = Array.prototype.slice.call(container.querySelectorAll('[data-visibloc-recipe-card]'));
        var filterSelect = container.querySelector('[data-visibloc-recipes-filter]');
        var emptyMessage = container.querySelector('[data-visibloc-recipes-empty]');
        var liveRegion = container.querySelector('[data-visibloc-recipes-live]');
        var dialog = container.querySelector('[data-visibloc-recipe-dialog]');

        var dialogWindow = dialog ? dialog.querySelector('[data-visibloc-recipe-dialog-window]') : null;
        var tabsContainer = dialog ? dialog.querySelector('[data-visibloc-recipe-tabs]') : null;
        var panelsContainer = dialog ? dialog.querySelector('[data-visibloc-recipe-panels]') : null;
        var progressElement = dialog ? dialog.querySelector('[data-visibloc-recipe-progress]') : null;
        var progressLabel = dialog ? dialog.querySelector('[data-visibloc-recipe-progress-label]') : null;
        var stepLiveRegion = dialog ? dialog.querySelector('[data-visibloc-recipe-step-live]') : null;
        var prevButton = dialog ? dialog.querySelector('[data-visibloc-recipe-prev]') : null;
        var nextButton = dialog ? dialog.querySelector('[data-visibloc-recipe-next]') : null;
        var titleElement = dialog ? dialog.querySelector('[data-visibloc-recipe-dialog-title]') : null;
        var descriptionElement = dialog ? dialog.querySelector('[data-visibloc-recipe-dialog-description]') : null;
        var metaContainer = dialog ? dialog.querySelector('[data-visibloc-recipe-dialog-meta]') : null;
        var blocksContainer = dialog ? dialog.querySelector('[data-visibloc-recipe-dialog-blocks]') : null;
        var blocksList = dialog ? dialog.querySelector('[data-visibloc-recipe-dialog-blocks-list]') : null;

        var activeTabs = [];
        var activePanels = [];
        var activeIndex = 0;
        var previousFocus = null;
        var dialogOpen = false;

        function announceToLiveRegion(element, message) {
            if (!element) {
                return;
            }

            element.textContent = message || '';
        }

        function updateFilter(value) {
            var normalized = (value || '').toLowerCase();
            var visibleCount = 0;

            cards.forEach(function (card) {
                var theme = (card.getAttribute('data-theme') || '').toLowerCase();
                var matches = !normalized || theme === normalized;

                card.hidden = !matches;

                if (matches) {
                    visibleCount++;
                }
            });

            if (emptyMessage) {
                emptyMessage.hidden = visibleCount > 0;
            }

            announceToLiveRegion(liveRegion, normalizeCountMessage(visibleCount, emptyMessage ? emptyMessage.textContent : ''));
        }

        function closeDialog() {
            if (!dialog || !dialogOpen) {
                return;
            }

            dialogOpen = false;
            dialog.setAttribute('hidden', 'hidden');
            document.body.classList.remove('visibloc-recipes-modal-open');
            if (tabsContainer) {
                tabsContainer.innerHTML = '';
            }

            if (panelsContainer) {
                panelsContainer.innerHTML = '';
            }
            activeTabs = [];
            activePanels = [];
            activeIndex = 0;
            document.removeEventListener('keydown', handleKeydown, true);

            if (previousFocus && typeof previousFocus.focus === 'function') {
                previousFocus.focus();
            }

            previousFocus = null;
        }

        function handleKeydown(event) {
            if (!dialogOpen) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeDialog();
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            var focusable = getFocusableElements(dialogWindow || dialog);

            if (focusable.length === 0) {
                event.preventDefault();
                return;
            }

            var currentIndex = focusable.indexOf(document.activeElement);

            if (event.shiftKey) {
                if (currentIndex <= 0) {
                    event.preventDefault();
                    focusable[focusable.length - 1].focus();
                }
            } else {
                if (currentIndex === -1 || currentIndex === focusable.length - 1) {
                    event.preventDefault();
                    focusable[0].focus();
                }
            }
        }

        function setMetaValue(slug, value) {
            if (!metaContainer) {
                return;
            }

            var target = metaContainer.querySelector('[data-visibloc-recipe-meta="' + slug + '"]');

            if (target) {
                target.textContent = value || '—';
            }
        }

        function updateBlocksList(blocks) {
            if (!blocksContainer || !blocksList) {
                return;
            }

            blocksList.innerHTML = '';

            if (!blocks || !blocks.length) {
                blocksContainer.hidden = true;
                return;
            }

            blocks.forEach(function (block) {
                var item = document.createElement('li');
                item.textContent = block;
                blocksList.appendChild(item);
            });

            blocksContainer.hidden = false;
        }

        function setActiveStep(index, focusTab) {
            if (index < 0 || index >= activeTabs.length) {
                return;
            }

            activeIndex = index;

            activeTabs.forEach(function (tab, tabIndex) {
                var isSelected = tabIndex === index;
                tab.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                tab.setAttribute('tabindex', isSelected ? '0' : '-1');

                if (isSelected && focusTab) {
                    tab.focus();
                }
            });

            activePanels.forEach(function (panel, panelIndex) {
                panel.hidden = panelIndex !== index;
            });

            if (progressElement) {
                progressElement.max = Math.max(activeTabs.length, 1);
                progressElement.value = index + 1;
            }

            if (progressLabel) {
                var template = progressLabel.getAttribute('data-visibloc-progress-template') || '';
                if (template && template.indexOf('%1$s') !== -1) {
                    progressLabel.textContent = sprintf(template, index + 1, activeTabs.length);
                } else if (template) {
                    progressLabel.textContent = template;
                } else {
                    progressLabel.textContent = (index + 1) + ' / ' + activeTabs.length;
                }
            }

            if (prevButton) {
                prevButton.disabled = index === 0;
            }

            if (nextButton) {
                var isLastStep = index === activeTabs.length - 1;
                var nextLabel = nextButton.getAttribute('data-visibloc-label-next') || __('Étape suivante', 'visi-bloc-jlg');
                var finishLabel = nextButton.getAttribute('data-visibloc-label-finish') || __('Terminer', 'visi-bloc-jlg');
                nextButton.textContent = isLastStep ? finishLabel : nextLabel;
                nextButton.setAttribute('aria-label', nextButton.textContent);
                nextButton.dataset.visiblocRecipeNextIsFinish = isLastStep ? 'true' : 'false';
            }

            if (stepLiveRegion && activeTabs[index]) {
                stepLiveRegion.textContent = activeTabs[index].textContent;
            }
        }

        function focusTabByOffset(currentIndex, offset) {
            if (!activeTabs.length) {
                return;
            }

            var nextIndex = (currentIndex + offset + activeTabs.length) % activeTabs.length;
            setActiveStep(nextIndex, true);
        }

        function handleTabKeydown(event) {
            var tab = event.currentTarget;
            var index = parseInt(tab.getAttribute('data-index'), 10);

            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                event.preventDefault();
                focusTabByOffset(index, 1);
            } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                event.preventDefault();
                focusTabByOffset(index, -1);
            } else if (event.key === 'Home') {
                event.preventDefault();
                setActiveStep(0, true);
            } else if (event.key === 'End') {
                event.preventDefault();
                setActiveStep(activeTabs.length - 1, true);
            }
        }

        function openRecipe(card) {
            if (!dialog || !tabsContainer || !panelsContainer) {
                return;
            }

            var templateId = card.getAttribute('data-recipe-template');
            var template = templateId ? document.getElementById(templateId) : null;

            if (!template) {
                template = card.querySelector('[data-visibloc-recipe-template]');
            }

            if (!template) {
                return;
            }

            var steps = parseTemplateSteps(template);

            if (!steps.length) {
                return;
            }

            tabsContainer.innerHTML = '';
            panelsContainer.innerHTML = '';
            activeTabs = [];
            activePanels = [];

            var recipeId = card.getAttribute('data-recipe-id') || 'recipe';

            steps.forEach(function (stepNode, index) {
                var tabId = 'visibloc-recipe-tab-' + recipeId + '-' + index;
                var panelId = 'visibloc-recipe-panel-' + recipeId + '-' + index;

                var tab = document.createElement('button');
                tab.type = 'button';
                tab.className = 'visibloc-guided-recipes__stepper-tab';
                tab.setAttribute('role', 'tab');
                tab.setAttribute('id', tabId);
                tab.setAttribute('aria-controls', panelId);
                tab.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
                tab.setAttribute('tabindex', index === 0 ? '0' : '-1');
                tab.setAttribute('data-index', String(index));

                var title = stepNode.getAttribute('data-step-title') || __('Étape', 'visi-bloc-jlg');
                tab.textContent = (index + 1) + '. ' + title;

                tab.addEventListener('click', function () {
                    setActiveStep(index, true);
                });
                tab.addEventListener('keydown', handleTabKeydown);

                tabsContainer.appendChild(tab);
                activeTabs.push(tab);

                var structure = createPanelStructure(stepNode, panelId, tabId);
                if (structure.panel) {
                    structure.panel.hidden = index !== 0;
                    panelsContainer.appendChild(structure.panel);
                    activePanels.push(structure.panel);
                }
            });

            if (titleElement) {
                titleElement.textContent = card.getAttribute('data-recipe-title') || '';
            }

            if (descriptionElement) {
                descriptionElement.textContent = card.getAttribute('data-recipe-description') || '';
            }

            setMetaValue('goal', card.getAttribute('data-recipe-goal') || '');
            setMetaValue('audience', card.getAttribute('data-recipe-audience') || '');
            setMetaValue('kpi', card.getAttribute('data-recipe-kpi') || '');
            setMetaValue('time', card.getAttribute('data-recipe-time') || __('Quelques minutes', 'visi-bloc-jlg'));

            updateBlocksList(parseBlocks(card));

            previousFocus = document.activeElement;
            dialog.removeAttribute('hidden');
            dialogOpen = true;
            document.body.classList.add('visibloc-recipes-modal-open');
            document.addEventListener('keydown', handleKeydown, true);

            setActiveStep(0, true);
        }

        if (filterSelect) {
            filterSelect.addEventListener('change', function () {
                updateFilter(filterSelect.value || '');
            });
        }

        container.addEventListener('click', function (event) {
            var target = event.target;

            if (target.matches('[data-visibloc-recipe-start]')) {
                event.preventDefault();
                var card = target.closest('[data-visibloc-recipe-card]');
                if (card) {
                    openRecipe(card);
                }
            }

            if (target.hasAttribute('data-visibloc-recipe-close')) {
                event.preventDefault();
                closeDialog();
            }
        });

        if (prevButton) {
            prevButton.addEventListener('click', function () {
                if (!dialogOpen) {
                    return;
                }

                var newIndex = Math.max(activeIndex - 1, 0);
                setActiveStep(newIndex, true);
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', function () {
                if (!dialogOpen) {
                    return;
                }

                if (nextButton.dataset.visiblocRecipeNextIsFinish === 'true') {
                    closeDialog();
                    return;
                }

                var newIndex = Math.min(activeIndex + 1, activeTabs.length - 1);
                setActiveStep(newIndex, true);
            });
        }

        updateFilter(filterSelect ? filterSelect.value || '' : '');
    }

    if (typeof wp !== 'undefined' && wp.domReady) {
        wp.domReady(initialize);
    } else {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initialize);
        } else {
            initialize();
        }
    }
})();
