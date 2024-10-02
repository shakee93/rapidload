(function() {
    let link = document.createElement('link');
    let connection = (navigator.connection && (navigator.connection.saveData || navigator.connection.effectiveType === '2g'));
    let support_prefetch = link.relList && link.relList.supports && link.relList.supports('prefetch');
    let debugging = false; // Set this to true if you want to turn on debugging

    if (connection || !support_prefetch) {
        return;
    }

    let loaded_links = new Set();

    let load_link = (link) => {
        if (!loaded_links.has(link) && !link.includes('?') && link.startsWith(window.location.origin) && window.location.href !== link) {
            let new_link = document.createElement('link');
            new_link.rel = 'prefetch';
            new_link.href = link;
            document.head.appendChild(new_link);
            loaded_links.add(link);

            // Apply border style if debugging is turned on
            if (debugging) {
                let anchor = document.querySelector("a[href='" + link +"']");
                if (anchor) {
                    console.log(anchor)
                    anchor.style.border = '2px solid red';
                }
            }
        }
    };

    var lastX = null, lastY = null, animationFrameId = null;

    let handleProximityPreload = (x, y) => {
        document.querySelectorAll('a[href]').forEach(anchor => {
            let rect = anchor.getBoundingClientRect();
            let distanceX = Math.min(Math.abs(x - rect.left), Math.abs(x - rect.right));
            let distanceY = Math.min(Math.abs(y - rect.top), Math.abs(y - rect.bottom));
            let distance = Math.hypot(distanceX, distanceY);
            if (distance < 200) {
                load_link(anchor.href);
            }
        });
    };

    let throttleMouseMove = (event) => {
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
        }
        animationFrameId = requestAnimationFrame(() => {
            let x = event.clientX;
            let y = event.clientY;
            if (lastX === null || lastY === null || Math.hypot(x - lastX, y - lastY) > 100) {
                lastX = x;
                lastY = y;
                handleProximityPreload(x, y);
            }
        });
    };

    let handleTouchStart = (event) => {
        let anchor = event.target.closest('a');
        if (anchor && anchor.href) {
            load_link(anchor.href);
        }
    };

    let params = { capture: true, passive: true };
    document.addEventListener('mousemove', throttleMouseMove, params);
    document.addEventListener('touchstart', handleTouchStart, params);

    // Fallback for requestAnimationFrame in older browsers
    window.requestAnimationFrame = window.requestAnimationFrame || function(callback) {
        return setTimeout(callback, 1000 / 60);
    };

})();
