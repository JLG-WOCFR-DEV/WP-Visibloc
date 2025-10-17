(function () {
    if (typeof window === 'undefined' || typeof window.EventTarget === 'undefined') {
        return;
    }

    var EventTargetPrototype = window.EventTarget && window.EventTarget.prototype;

    if (!EventTargetPrototype || EventTargetPrototype.__visiblocPassiveTouchListenersPatched) {
        return;
    }

    var originalAddEventListener = EventTargetPrototype.addEventListener;
    var passiveTouchEvents = { touchstart: true, touchmove: true };

    Object.defineProperty(EventTargetPrototype, '__visiblocPassiveTouchListenersPatched', {
        value: true,
        configurable: false,
        enumerable: false,
        writable: false
    });

    EventTargetPrototype.addEventListener = function (type, listener, options) {
        if (passiveTouchEvents[type]) {
            var normalizedOptions = options;
            var isBooleanOptions = 'boolean' === typeof options;
            var isObjectOptions = options && 'object' === typeof options;
            var capture = false;

            if (isBooleanOptions) {
                capture = options;
            } else if (isObjectOptions) {
                capture = Boolean(options.capture);
            }

            if (capture) {
                if (isBooleanOptions) {
                    normalizedOptions = { capture: options, passive: true };
                } else if (isObjectOptions && !('passive' in options)) {
                    normalizedOptions = Object.assign({}, options, { passive: true });
                }
            }

            return originalAddEventListener.call(this, type, listener, normalizedOptions);
        }

        return originalAddEventListener.call(this, type, listener, options);
    };
})();
