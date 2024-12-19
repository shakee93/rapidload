(function (){

    window.diagnose_data = {
        cache: {
            status: false,
            key: 'uucss_enable_cache',
        },
        cpcss: {
            status: false,
            key: 'uucss_enable_cpcss',
        },
        uucss: {
            non_optimized_css: [],
            key: 'uucss_enable_uucss',
        },
        css_minify: {
            non_minified_css: []    ,
            key: 'uucss_minify',
        },
        js_minify: {
            non_minified_js: [],
            key: 'minify_js',
        },
        js_defer: {
            non_deferred_js: [],
            key: 'uucss_load_js_method',
        },
        js_delay: {
            non_delayed_js: [],
            key: 'delay_javascript',
        },
        cdn: {
            status: false,
            key: 'uucss_enable_cdn',
        },
    };

    function is_rapidload_preview() {
        const urlParams = new URLSearchParams(window.location.search);
        const params = [];
        urlParams.forEach((value, key) => {
            params.push(key)
        });
        return params.includes("rapidload_preview");
    }

    document.addEventListener('DOMContentLoaded', function (){
        if (is_rapidload_preview()) {

            // check cache served
            const rapidload_cache_status_div_content = document.querySelector('#rapidload-cache-status');

            if (rapidload_cache_status_div_content) {
                window.diagnose_data.cache.status = true;
            }else{
                window.diagnose_data.cache.status = false;
            }

            // check cpcss

            const rapidload_cpcss_style_content = document.querySelector('#rapidload-critical-css');

            if(rapidload_cpcss_style_content){
                window.diagnose_data.cpcss.status = true;
            }else{
                window.diagnose_data.cpcss.status = false;
            }

            // check uucss

            const allStylesheets = document.querySelectorAll('link[type="text/css"]');
            const nonOptimizedStylesheets = Array.from(allStylesheets).filter(sheet => !sheet.hasAttribute('data-rpd-uucss'));

            if(nonOptimizedStylesheets.length > 0) {
                window.diagnose_data.uucss.non_optimized_css = nonOptimizedStylesheets.map(sheet => sheet.href);
            }else{
                window.diagnose_data.uucss.non_optimized_css = [];
            }

            // check minify

            const nonMinifiedStylesheets = Array.from(allStylesheets).filter(sheet => {
                const href = sheet.href || '';
                return !sheet.hasAttribute('data-rpd-minify') && !href.toString().includes('.min.css');
            });

            if(nonMinifiedStylesheets.length > 0) {
                window.diagnose_data.css_minify.non_minified_css = nonMinifiedStylesheets.map(sheet => sheet.href);
            }else{
                window.diagnose_data.css_minify.non_minified_css = [];
            }

            // check js minify

            const allScripts = document.querySelectorAll('script[src]');
            const nonMinifiedScripts = Array.from(allScripts).filter(script => {
                const src = script.src || '';
                return !script.hasAttribute('data-rpd-minify-js') && !src.toString().includes('.min.js');
            });

            if(nonMinifiedScripts.length > 0) {
                window.diagnose_data.js_minify.non_minified_js = nonMinifiedScripts.map(script => script.src);
            }else{
                window.diagnose_data.js_minify.non_minified_js = [];
            }

            // check non-deferred scripts

            const nonDeferredScripts = Array.from(allScripts).filter(script => {
                return !script.hasAttribute('data-rpd-strategy') && !script.hasAttribute('defer');
            });

            if(nonDeferredScripts.length > 0) {
                window.diagnose_data.js_defer.non_deferred_js = nonDeferredScripts.map(script => script.src);
            }else{
                window.diagnose_data.js_defer.non_deferred_js = [];
            }

            // check non-delayed scripts
            const nonDelayedScripts = Array.from(allScripts).filter(script => {
                return !script.hasAttribute('data-rpd-strategy') || script.getAttribute('data-rpd-strategy') !== 'delay';
            });

            if(nonDelayedScripts.length > 0) {
                window.diagnose_data.js_delay.non_delayed_js = nonDelayedScripts.map(script => script.src);
            }else{
                window.diagnose_data.js_delay.non_delayed_js = [];
            }

            // check cdn

            const preconnectLink = document.querySelector('link[rel="preconnect"][crossorigin][href*=".rapidload-cdn.io"]');
            if(preconnectLink) {
                window.diagnose_data.cdn.status = true;
            }else{
                window.diagnose_data.cdn.status = false;
            }

        }


        setTimeout(() => {

            window.parent.postMessage(
                {
                    type: "RAPIDLOAD_CHECK_RESULTS",
                    data: diagnose_data,
                },
                "*" 
            );
        }, 5000);
    })

})()