window.rapidload_replace_image_src = function () {
    var images = document.getElementsByTagName('img');
    for (var i = 0; i < images.length; i++) {
        var image = images[i];
        var url = image.getAttribute('data-original-src');
        if (window.rapidload_io_data && url) {
            var options = "ret_img";
            if (window.rapidload_io_data.optimize_level) {
                options += ",q_" + window.rapidload_io_data.optimize_level;
            }
            if (window.rapidload_io_data.support_next_gen_format) {
                options += ",to_auto";
            }
            if (image.width !== 0) {
                options += ",w_" + image.width;
            } else if (image.getAttribute("width") && Number(image.getAttribute("width")) !== 0) {
                options += ",w_" + image.getAttribute("width");
            }
            url = window.rapidload_io_data.image_endpoint + options + "/" + url;
            if (image.getAttribute("src") !== url) {
                image.setAttribute("src", url);
            }
        }
    }
};
var targetNode = document.getElementsByTagName("body")[0];
var config = {
    attributes: false,
    childList: true,
    subtree: true
};
var callback = function (mutationList, observer) {
    for (var i = 0; i < mutationList.length; i++) {
        var mutation = mutationList[i];
        if (mutation.type === "childList") {
            var addedNodes = mutation.addedNodes;
            for (var j = 0; j < addedNodes.length; j++) {
                var node = addedNodes[j];
                if (node.nodeName === "#text") {
                    continue;
                }
                try {
                    var imageTags = node.getElementsByTagName("img");
                    if (imageTags.length) {
                        for (var k = 0; k < imageTags.length; k++) {
                            var img = imageTags[k];
                            var url = img.getAttribute("data-original-src");
                            if (window.rapidload_io_data && url) {
                                var options = "ret_img";
                                if (window.rapidload_io_data.optimize_level) {
                                    options += ",q_" + window.rapidload_io_data.optimize_level;
                                }
                                if (window.rapidload_io_data.support_next_gen_format) {
                                    options += ",to_auto";
                                }
                                if(img.getBoundingClientRect().width !== 0){
                                    options += ",w_" + img.getBoundingClientRect().width;
                                }
                                img.setAttribute("src", window.rapidload_io_data.image_endpoint + options + "/" + url);
                            }
                        }
                    }
                } catch (e) {
                }
            }
        }
    }
};
var observer = new MutationObserver(callback);
observer.observe(targetNode, config);
var observer_bg = new IntersectionObserver(function (elements) {
        elements.forEach(function (element) {
            if (element.isIntersecting) {
                observer_bg.unobserve(element.target);
                var attributes = element.target.getAttribute("data-rapidload-lazy-attributes").split(",");
                attributes.forEach(function (attribute) {
                    var value = element.target.getAttribute("data-rapidload-lazy-" + attribute);
                    element.target.style.backgroundImage = 'url(' + value.replace("ret_blank", "ret_img") + ')';
                });
            }
        });
        window.dispatchEvent(new Event("resize"));
    },
    {
        rootMargin: "300px"
    }
);
document.addEventListener("DOMContentLoaded", function () {
    window.rapidload_replace_image_src();
});
window.onresize = function (event) {
    window.rapidload_replace_image_src();
};
var lazyElements = document.querySelectorAll('[data-rapidload-lazy-method="viewport"]');
if(lazyElements && lazyElements.length){
    lazyElements.forEach(function (element) {
        observer_bg.observe(element);
    });
}
window.rapidload_replace_image_src();
window.rapidload_lcp_images = function (){

    var isChrome = /Chrome/.test(navigator.userAgent);

    var lcp_image_urls = []

    if (isChrome && 'PerformanceObserver' in window) {
        new PerformanceObserver((entryList) => {
            for (const entry of entryList.getEntries()) {
                entry.url && lcp_image_urls.push(entry.url);
            }
        }).observe({type: 'largest-contentful-paint', buffered: true});
    }

    /*var xhr = new XMLHttpRequest();

    xhr.open('POST', window.rapidload_io_data.ajax_url, true);

    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.responseType = 'json';

    var requestData = {

    };*/

}
