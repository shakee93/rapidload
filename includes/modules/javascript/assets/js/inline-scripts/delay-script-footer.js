//!injected by RapidLoad \n
(function () {
    var totalScripts = prepareScripts();
    function rpDebug(method = 'log', ...args) {
        if (window.location.search.includes('rapidload_debug_js_scripts')) {
            console[method](...args);
        }
    }

    if (window.RAPIDLOAD_EXPERIMENT__DELAY_PREFETCH) {
        document.addEventListener('DOMContentLoaded:norapidload', () => {
            totalScripts.forEach((script, index) => {
                setTimeout(() => {
                    fetch(script.scriptElement.getAttribute('data-rapidload-src')).then(() => {
                        script.asyncLoaded = true
                        script.success = true
                        totalScripts[index] = script
                    }).catch(() => {
                        script.asyncLoaded = true
                        script.success = falsej
                        totalScripts[index] = script
                    }).finally(() => {
                        onScriptAsyncLoad(script)
                    });
                }, 10)
            })
        });
    }


    function createBatches(scripts) {
        // Create a map for easy access to scripts by id
        const scriptMap = new Map(scripts.map(script => [script.id, { ...script, batch: null }]));

        // Helper function to visit a script and assign a batch
        function assignBatch(scriptId, stackSet = new Set()) {
            let script = scriptMap.get(scriptId);

            // If already visited, return the known batch
            if (script.batch !== null) return script.batch;

            // Detect circular dependencies
            if (stackSet.has(scriptId)) {
                throw new Error('RapidLoad: Circular dependency detected at script: ' + scriptId);
            }

            stackSet.add(scriptId);

            // Recursively assign batch numbers based on dependencies
            let maxBatch = 0;
            script.dependencies.forEach(depId => {
                const depBatch = assignBatch(depId, stackSet);
                maxBatch = Math.max(maxBatch, depBatch);
            });

            // Assign the current script to the next batch after the highest dependency batch
            script.batch = maxBatch + 1;
            stackSet.delete(scriptId);
            return script;
        }

        // Assign batches
        return scripts.map(script => assignBatch(script.id));
    }


    function onScriptLoad(script, success = true) {

        totalScripts = totalScripts.map(s => s.id === script.id && script.loaded === null ? {
            ...script,
            loaded: true,
            success: success
        } : s)

        if(totalScripts.filter(s => s.batch === script.batch).length ===
            totalScripts.filter(s => s.batch === script.batch && s.loaded ).length) {
            var batchLoadedEvent = new CustomEvent('RapidLoad:DelayedScriptBatchLoaded', {
                detail: { batch: script.batch },
                bubbles: true,
                cancelable: true
            });

            document.dispatchEvent(batchLoadedEvent);
            rpDebug('info', 'fired: RapidLoad:DelayedScriptBatchLoaded : ' + script.batch )
            rpDebug('table', totalScripts.filter(s => s.batch === script.batch))
        }

        if (totalScripts.filter(s => s.loaded).length === totalScripts.length) {

            var allScriptsLoadedEvent = new CustomEvent('RapidLoad:DelayedScriptsLoaded', {
                bubbles: true,
                cancelable: true
            });

            document.dispatchEvent(allScriptsLoadedEvent);
            rpDebug('info', 'fired: RapidLoad:DelayedScriptsLoaded')
            rpDebug('table', totalScripts)
        }
    }

    function prepareScripts() {
        var scripts = Array.from(document.querySelectorAll('[data-rapidload-src]'));

        // Parse and store script dependencies
        mappedScripts = scripts.map(function (script) {
            var scriptId = script.getAttribute('id');
            var depsAttribute = script.getAttribute('data-js-deps');

            return {
                id: scriptId,
                scriptElement: script, dependencies: parseDependencies(depsAttribute, scripts, scriptId),
                loaded: null,
                asyncLoaded: null,
                success: false
            }
        });

        return createBatches(mappedScripts);
    }

    function loadScript(script) {
        var scriptElement = script.scriptElement;
        scriptElement.addEventListener('load', () => onScriptLoad(script));
        scriptElement.addEventListener('error', () => onScriptLoad(script, false)); // Handle script load errors

        let rapidLoadSrc = scriptElement.getAttribute('data-rapidload-src');

        if (rapidLoadSrc) {
            scriptElement.setAttribute('src', scriptElement.getAttribute('data-rapidload-src'));
            scriptElement.removeAttribute('data-rapidload-src');
        }
    }
    function loadScriptsInDependencyOrder() {

        totalScripts.filter(s => s.batch === 1).forEach(function (script) {
            loadScript(script)
        });

        document.addEventListener('RapidLoad:DelayedScriptBatchLoaded', (event) => {

            var batch = Number(event.detail.batch) + 1;

            if (batch > totalScripts.filter(s => s.batch).length) {
                return;
            }

            totalScripts.filter(s => s.batch === batch).forEach(function (script) {
                loadScript(script)
            });
        })
    }

    function parseDependencies(depsAttribute, scriptMap, scriptId) {
        let deps = []

        let depAttributes = depsAttribute ? depsAttribute.split(', ') : [];

        deps = depAttributes.map(function (dep) {
            // Check if the dependency matches a script ID without '-js' suffix
            var matchingScript = scriptMap.find(function (script) {
                return script.id === dep + '-js'
                    || (dep === 'jquery' && script.id === 'jquery-core-js')
                    || (dep.startsWith('jquery-ui-') && script.id === 'jquery-ui-core-js');
            });

            if (matchingScript) {
                return matchingScript.id;
            } else {
                console.warn('Dependency not found for:', dep);
                return null;
            }
        }).filter(Boolean); // Remove null values

        // try to find any jquery dep through its file id
        if (scriptId !== 'jquery-core-js' && scriptId && scriptId.includes('jquery-')) {
            deps.push('jquery-core-js')
        }

        return deps;
    }


    ['mousemove', 'touchstart', 'keydown'].forEach(function (event) {
        var listener = function () {
            removeEventListener(event, listener);
            loadScriptsInDependencyOrder();

            // Check if there are no scripts to load
            if (totalScripts === 0) {
                var allScriptsLoadedEvent = new CustomEvent('RapidLoad:DelayedScriptsLoaded', {
                    bubbles: true,
                    cancelable: true
                });

                document.dispatchEvent(allScriptsLoadedEvent);
            }
        };
        addEventListener(event, listener);
    });
})();
