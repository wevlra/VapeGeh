// capacitor-bridge.js
// Registers our local Printer plugin so it's accessible as Capacitor.Plugins.Printer
// from any page loaded in the WebView (including remote Laravel pages).

(function () {
    if (typeof window.Capacitor === 'undefined') return;

    // Preferences fallback for the SPA settings page
    if (!window.VapeGehPrefs) {
        window.VapeGehPrefs = {
            get: function (opts) {
                return new Promise(function (resolve) {
                    try {
                        var val = localStorage.getItem('vapegeh_' + opts.key);
                        resolve({ value: val });
                    } catch (e) {
                        resolve({ value: null });
                    }
                });
            },
            set: function (opts) {
                return new Promise(function (resolve) {
                    try {
                        localStorage.setItem('vapegeh_' + opts.key, opts.value);
                    } catch (e) {}
                    resolve();
                });
            },
        };
    }

    // The Printer plugin is registered natively via @CapacitorPlugin annotation.
    // Capacitor automatically exposes native plugins as Capacitor.Plugins.<name>.
    // No manual registration needed in JS for locally-linked native plugins.
})();
