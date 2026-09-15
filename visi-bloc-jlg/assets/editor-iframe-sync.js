/**
 * Copy Visi-Bloc editor body classes into the Gutenberg canvas iframe.
 *
 * WordPress 7.1 always iframes the post editor. The inspector script runs in
 * the parent document; badges live in the canvas document. Syncing these
 * classes keeps high-visibility and compact-badge styles iframe-safe.
 */
(function () {
    var CLASS_NAMES = ['visibloc-high-visibility', 'visibloc-compact-badges'];

    function getCanvasIframe() {
        if (!document || typeof document.querySelector !== 'function') {
            return null;
        }

        return (
            document.querySelector('iframe[name="editor-canvas"]') ||
            document.querySelector('iframe.editor-canvas__iframe')
        );
    }

    function syncCanvasBodyClasses() {
        var parentBody = document.body;
        var iframe = getCanvasIframe();
        var canvasDocument;

        if (!parentBody || !parentBody.classList) {
            return;
        }

        try {
            canvasDocument = iframe ? iframe.contentDocument : null;
        } catch (error) {
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

    function bindCanvasIframe() {
        var iframe = getCanvasIframe();

        if (iframe && !iframe.getAttribute('data-visibloc-iframe-sync')) {
            iframe.setAttribute('data-visibloc-iframe-sync', '1');
            iframe.addEventListener('load', syncCanvasBodyClasses);
        }

        syncCanvasBodyClasses();
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
