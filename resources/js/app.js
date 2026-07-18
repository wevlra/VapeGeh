import { Network } from "@capawesome/capacitor-network";

window.CapawesomeNetwork = Network;

// Prevent zooming (pinch-to-zoom, gesture zoom, touchpad/trackpad zoom, and keyboard shortcuts)
function disableZoom() {
    // 1. Force viewport meta tag settings
    let viewport = document.querySelector('meta[name="viewport"]');
    const viewportContent = "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no";

    if (viewport) {
        viewport.setAttribute("content", viewportContent);
    } else {
        viewport = document.createElement("meta");
        viewport.name = "viewport";
        viewport.content = viewportContent;
        document.getElementsByTagName("head")[0].appendChild(viewport);
    }

    // 2. Prevent pinch-to-zoom via touch events
    document.addEventListener(
        "touchstart",
        function (event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        },
        { passive: false }
    );

    document.addEventListener(
        "touchmove",
        function (event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        },
        { passive: false }
    );

    // 3. Prevent gesture zoom (Safari)
    document.addEventListener("gesturestart", function (event) {
        event.preventDefault();
    });

    // 4. Prevent touchpad/trackpad pinch-to-zoom (triggered as wheel with Ctrl key)
    document.addEventListener(
        "wheel",
        function (event) {
            if (event.ctrlKey) {
                event.preventDefault();
            }
        },
        { passive: false }
    );

    // 5. Prevent keyboard zoom shortcuts (Ctrl / Cmd + '+', '-', '0', '=')
    document.addEventListener("keydown", function (event) {
        const isZoomShortcut =
            (event.ctrlKey || event.metaKey) &&
            (event.key === "+" ||
                event.key === "-" ||
                event.key === "=" ||
                event.key === "0" ||
                event.keyCode === 187 ||
                event.keyCode === 189 ||
                event.keyCode === 48);
        if (isZoomShortcut) {
            event.preventDefault();
        }
    });
}

// Run the disableZoom function
if (typeof window !== "undefined") {
    disableZoom();
}
