/**
 * Copy Visi-Bloc editor body classes and styles into the Gutenberg canvas iframe.
 *
 * WordPress 7.1 always iframes the post editor. The inspector script runs in
 * the parent document; badges live in the canvas document. Syncing classes and
 * stylesheets keeps high-visibility rules iframe-safe even when
 * enqueue_block_assets is not copied into the iframe document.
 */
(function () {
    var CLASS_NAMES = ['visibloc-high-visibility', 'visibloc-compact-badges'];
    var STYLE_HREF_MATCH = /visi-bloc|visibloc/i;

    function getCanvasIframe() {
        if (!document || typeof document.querySelector !== 'function') {
            return null;
        }

        return (
            document.querySelector('iframe[name="editor-canvas"]') ||
            document.querySelector('iframe.editor-canvas__iframe')
        );
    }

    function getCanvasDocument(iframe) {
        if (!iframe) {
            return null;
        }

        try {
            return iframe.contentDocument || null;
        } catch (error) {
            return null;
        }
    }

    function syncCanvasBodyClasses() {
        var parentBody = document.body;
        var canvasDocument = getCanvasDocument(getCanvasIframe());

        if (!parentBody || !parentBody.classList) {
            return;
        }

        if (!canvasDocument || !canvasDocument.body || !canvasDocument.body.classList) {
            return;
        }

        CLASS_NAMES.forEach(function (className) {
            canvasDocument.body.classList.toggle(
                className,
                parentBody.classList.contains(className)
            );
        });
    }

    function isVisiblocStylesheet(node) {
        if (!node || !node.tagName) {
            return false;
        }

        var id = node.id || '';
        var href = node.href || node.getAttribute('href') || '';
        var rel = (node.rel || '').toLowerCase();

        if (node.tagName === 'LINK') {
            return (
                rel.indexOf('stylesheet') !== -1 &&
                (STYLE_HREF_MATCH.test(href) || STYLE_HREF_MATCH.test(id))
            );
        }

        if (node.tagName === 'STYLE') {
            var text = node.textContent || '';
            return STYLE_HREF_MATCH.test(id) || text.indexOf('visibloc') !== -1;
        }

        return false;
    }

    function syncCanvasStyles() {
        var canvasDocument = getCanvasDocument(getCanvasIframe());
        var canvasHead;
        var parentNodes;

        if (!canvasDocument || !canvasDocument.head) {
            return;
        }

        canvasHead = canvasDocument.head;
        parentNodes = document.querySelectorAll('link[rel="stylesheet"], style');

        Array.prototype.forEach.call(parentNodes, function (node) {
            var cloneId = node.id || '';
            var clone;

            if (!isVisiblocStylesheet(node)) {
                return;
            }

            if (cloneId && canvasDocument.getElementById(cloneId)) {
                return;
            }

            if (!cloneId) {
                cloneId = 'visibloc-jlg-editor-canvas-css';
                if (canvasDocument.getElementById(cloneId)) {
                    return;
                }
            }

            clone = node.cloneNode(true);

            if (!clone.id) {
                clone.id = cloneId;
            }

            canvasHead.appendChild(clone);
        });
    }

    function syncCanvas() {
        syncCanvasBodyClasses();
        syncCanvasStyles();
    }

    function bindCanvasIframe() {
        var iframe = getCanvasIframe();

        if (iframe && !iframe.getAttribute('data-visibloc-iframe-sync')) {
            iframe.setAttribute('data-visibloc-iframe-sync', '1');
            iframe.addEventListener('load', syncCanvas);
        }

        syncCanvas();
    }

    if (document.body && typeof MutationObserver === 'function') {
        new MutationObserver(syncCanvasBodyClasses).observe(document.body, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }

    if (document.documentElement && typeof MutationObserver === 'function') {
        new MutationObserver(bindCanvasIframe).observe(document.documentElement, {
            childList: true,
            subtree: true,
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindCanvasIframe);
    } else {
        bindCanvasIframe();
    }
})();
