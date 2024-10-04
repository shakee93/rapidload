// lib code
(function(global, factory) {
    if (typeof exports === "object" && typeof module !== "undefined") {
        factory(exports);
    } else if (typeof define === "function" && define.amd) {
        define(["exports"], factory);
    } else {
        factory(global.rapidloadsmartlink = {});
    }
}(this, function(exports) {

    function fetchViaXHR(url) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", url, xhr.withCredentials = true);
            xhr.onload = function() {
                xhr.status === 200 ? resolve() : reject();
            };
            xhr.send();
        });
    }

    var prefetchSupported = (function() {
        var link = document.createElement("link");
        return link.relList && link.relList.supports && link.relList.supports("prefetch");
    })();

    var prefetchMethod = prefetchSupported ? function(url) {
        return new Promise(function(resolve, reject) {
            var link = document.createElement("link");
            link.rel = "prefetch";
            link.href = url;
            link.onload = resolve;
            link.onerror = reject;
            document.head.appendChild(link);
        });
    } : fetchViaXHR;

    var requestIdleCallback = window.requestIdleCallback || function(callback) {
        var start = Date.now();
        return setTimeout(function() {
            callback({
                didTimeout: false,
                timeRemaining: function() {
                    return Math.max(0, 50 - (Date.now() - start));
                }
            });
        }, 1);
    };

    var prefetched = new Set();
    var prerendered = new Set();
    var isPrerendering = false;

    function isConnectionGood(connection) {
        if (connection) {
            if (connection.saveData) return new Error("Save-Data is enabled");
            if (/2g/.test(connection.effectiveType)) return new Error("Network conditions are poor");
        }
        return true;
    }

    function prefetch(urls, useFetch) {
        var connectionStatus = isConnectionGood(navigator.connection);
        if (connectionStatus instanceof Error) {
            return Promise.reject(new Error("Cannot prefetch, " + connectionStatus.message));
        }
        if (prerendered.size > 0 && !isPrerendering) {
            console.warn("[Warning] You are using both prefetching and prerendering on the same document");
        }
        return Promise.all([].concat(urls).map(function(url) {
            if (!prefetched.has(url)) {
                prefetched.add(url);
                var fetchMethod = useFetch ? (window.fetch ? fetch(url, { credentials: "include" }) : fetchViaXHR(url)) : prefetchMethod(new URL(url, location.href).toString());
                return fetchMethod;
            }
        }));
    }

    function prerender(urls) {
        var connectionStatus = isConnectionGood(navigator.connection);
        if (connectionStatus instanceof Error) {
            return Promise.reject(new Error("Cannot prerender, " + connectionStatus.message));
        }

        if (!HTMLScriptElement.supports("speculationrules")) {
            prefetch(urls);
            return Promise.reject(new Error("This browser does not support the speculation rules API. Falling back to prefetch."));
        }

        if (document.querySelector('script[type="speculationrules"]')) {
            return Promise.reject(new Error("Speculation Rules is already defined and cannot be altered."));
        }

        for (var i = 0; i < urls.length; i++) {
            var url = urls[i];
            if (window.location.origin !== new URL(url, window.location.href).origin) {
                return Promise.reject(new Error("Only same origin URLs are allowed: " + url));
            }
            prerendered.add(url);
        }

        if (prefetched.size > 0 && !isPrerendering) {
            console.warn("[Warning] You are using both prefetching and prerendering on the same document");
        }

        var result = (function(urlsSet) {
            var script = document.createElement("script");
            script.type = "speculationrules";
            script.text = '{"prerender":[{"source": "list","urls": ["' + Array.from(urlsSet).join('","') + '"]}]}';
            try {
                document.head.appendChild(script);
            } catch (error) {
                return error;
            }
            return true;
        })(prerendered);

        return result === true ? Promise.resolve() : Promise.reject(result);
    }

    exports.listen = function(options) {
        if (!options) options = {};

        if (window.IntersectionObserver) {
            var throttleControl = (function(limit) {
                limit = limit || 1;
                var queue = [];
                var active = 0;

                function processQueue() {
                    if (active < limit && queue.length > 0) {
                        queue.shift()();
                        active++;
                    }
                }

                return [function(task) {
                    queue.push(task);
                    if (active <= 1) {
                        processQueue();
                    }
                }, function() {
                    active--;
                    processQueue();
                }];
            })(options.throttle || Infinity);

            var enqueue = throttleControl[0];
            var dequeue = throttleControl[1];
            var limit = options.limit || Infinity;
            var origins = options.origins || [location.hostname];
            var ignores = options.ignores || [];
            var delay = options.delay || 0;
            var observedLinks = [];
            var timeoutFn = options.timeoutFn || requestIdleCallback;
            var hrefFn = typeof options.hrefFn === "function" && options.hrefFn;
            var usePrerender = options.prerender || false;

            isPrerendering = options.prerenderAndPrefetch || false;

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var link = entry.target.href;
                        observedLinks.push(link);

                        function processLink() {
                            if (observedLinks.indexOf(link) !== -1) {
                                observer.unobserve(entry.target);

                                if ((isPrerendering || usePrerender) && prerendered.size < 1) {
                                    prerender(hrefFn ? hrefFn(entry.target) : entry.target.href).catch(function(error) {
                                        if (!options.onError) throw error;
                                        options.onError(error);
                                    });
                                } else if (prefetched.size < limit && !usePrerender) {
                                    enqueue(function() {
                                        prefetch(hrefFn ? hrefFn(entry.target) : entry.target.href, options.priority)
                                            .then(dequeue)
                                            .catch(function(error) {
                                                dequeue();
                                                if (options.onError) options.onError(error);
                                            });
                                    });
                                }
                            }
                        }

                        delay ? setTimeout(processLink, delay) : processLink();
                    } else {
                        var index = observedLinks.indexOf(entry.target.href);
                        if (index > -1) {
                            observedLinks.splice(index);
                        }
                    }
                });
            }, { threshold: options.threshold || 0 });

            timeoutFn(function() {
                (options.el || document).querySelectorAll("a").forEach(function(link) {
                    if (!origins.length || origins.includes(link.hostname)) {
                        if (!ignores.some(function(ignore) {
                            return (Array.isArray(ignore) ? ignore.some(function(test) { return test(link.href); }) : ignore.test(link.href));
                        })) {
                            observer.observe(link);
                        }
                    }
                });
            }, { timeout: options.timeout || 2000 });

            return function() {
                prefetched.clear();
                observer.disconnect();
            };
        }
    };

    exports.prefetch = prefetch;
    exports.prerender = prerender;

}));
// rapidload preload code start
(function () {

    function isMobileDevice() {
        return /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent);
    }

    if (isMobileDevice()) {
        let options = {
            prerenderAndPrefetch: true,
            throttle: 1,
            origins: [location.hostname],
            onError: (error) => {},
        };

        rapidloadsmartlink.listen(options);
        return;
    }

    let link = document.createElement('link');
    let connection = (navigator.connection && (navigator.connection.saveData || navigator.connection.effectiveType === '2g'));
    let support_prefetch = link.relList && link.relList.supports && link.relList.supports('prefetch');

    if (connection || !support_prefetch) {
        return;
    }

    let load_link = (link) => {
        if (!link.includes('?') && link.startsWith(window.location.origin) && window.location.href !== link) {
            rapidloadsmartlink.prefetch([link]);
        }
    };

    let lastX = null,
        lastY = null,
        animationFrameId = null;

    let handleProximityPreload = (x, y) => {
        document.querySelectorAll('a[href]').forEach((anchor) => {
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

    window.requestAnimationFrame = window.requestAnimationFrame || function (callback) {
        return setTimeout(callback, 1000 / 60);
    };

})();
