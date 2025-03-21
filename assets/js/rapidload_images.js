window.rapidload_replace_image_src = function () {
    var images = document.getElementsByTagName("img");
    for (var i = 0; i < images.length; i++) {
        var image = images[i];
        var url = image.getAttribute("data-rp-src");
        if (window.rapidload_io_data && url) {
            var options = "ret_img";
            if (window.rapidload_io_data.optimize_level) {
                options += ",q_" + window.rapidload_io_data.optimize_level;
            }
            if (window.rapidload_io_data.support_next_gen_format) {
                options += ",to_avif";
            }
            if(window.rapidload_io_data.adaptive_image_delivery){
                if (image.width !== 0) {
                    options += ",w_" + image.width;
                } else if (image.getAttribute("width") && Number(image.getAttribute("width")) !== 0) {
                    options += ",w_" + image.getAttribute("width");
                }
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
var rapidload_image_callback = function (mutationList, observer) {
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
                            var url = img.getAttribute("data-rp-src");
                            if (window.rapidload_io_data && url) {
                                var options = "ret_img";
                                if (window.rapidload_io_data.optimize_level) {
                                    options += ",q_" + window.rapidload_io_data.optimize_level;
                                }
                                if (window.rapidload_io_data.support_next_gen_format) {
                                    options += ",to_avif";
                                }
                                if(window.rapidload_io_data.adaptive_image_delivery){
                                    if(img.getBoundingClientRect().width !== 0){
                                        options += ",w_" + Math.floor(img.getBoundingClientRect().width);
                                    }
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
var rapidload_image_observer = new MutationObserver(rapidload_image_callback);
rapidload_image_observer.observe(targetNode, config);
var rapidload_image_observer_bg = new IntersectionObserver(function (elements) {
        elements.forEach(function (element) {
            if (element.isIntersecting) {
                rapidload_image_observer_bg.unobserve(element.target);
                var attributes = element.target.getAttribute("data-rapidload-lazy-attributes").split(",");
                attributes.forEach(function (attribute) {
                    if(element.target.tagName === "IFRAME"){
                        element.target.setAttribute(attribute, element.target.getAttribute("data-rapidload-lazy-" + attribute))
                    }else{
                        var value = element.target.getAttribute("data-rapidload-lazy-" + attribute);
                        element.target.style.backgroundImage = "url(" + value.replace("ret_blank", "ret_img") + ")";
                    }
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
    if(window.rapidload_io_data.adaptive_image_delivery){
        window.rapidload_replace_image_src();
    }
});
window.onresize = function (event) {
    window.rapidload_replace_image_src();
};
["mousemove", "touchstart", "keydown"].forEach(function (event) {
    var user_interaction_listener = function () {
        window.rapidload_replace_image_src();
        removeEventListener(event, user_interaction_listener);
    }
    addEventListener(event, user_interaction_listener);
});
var lazyElements = document.querySelectorAll("[data-rapidload-lazy-method=\"viewport\"]");
if(lazyElements && lazyElements.length){
    lazyElements.forEach(function (element) {
        rapidload_image_observer_bg.observe(element);
    });
}
// youtube handler part
var playButtons = document.querySelectorAll(".rapidload-yt-play-button");
playButtons.forEach(function(playButton) {
    var videoContainer = playButton.closest(".rapidload-yt-video-container");
    var videoId = videoContainer.querySelector("img").getAttribute("data-video-id");
    function loadPosterImage() {
        var posterImageUrl = "https://i.ytimg.com/vi/" + videoId + "/";
        var posterImage = videoContainer.querySelector(".rapidload-yt-poster-image");
        if (window.rapidload_io_data && window.rapidload_io_data.support_next_gen_format) {
            var options = "ret_img";
            if (window.rapidload_io_data.optimize_level) {
                options += ",q_" + window.rapidload_io_data.optimize_level;
            }
            if (window.rapidload_io_data.support_next_gen_format) {
                options += ",to_avif";
            }
            if(window.rapidload_io_data.adaptive_image_delivery){
                if(posterImage.getBoundingClientRect().width !== 0){
                    options += ",w_" + Math.floor(posterImage.getBoundingClientRect().width);
                }
            }
            posterImageUrl = window.rapidload_io_data.image_endpoint + options + "/" + posterImageUrl
        }
        posterImage.src = posterImageUrl + "hqdefault.jpg";
    }
    loadPosterImage();
    playButton.addEventListener("click", function() {
        var parentElement = this.parentElement;
        this.style.display = "none";
        var posterImage = parentElement.querySelector(".rapidload-yt-poster-image");
        if (posterImage) {
            posterImage.style.display = "none";
        }
        var noscriptTag = parentElement.querySelector("noscript");
        if (noscriptTag) {
            noscriptTag.outerHTML = noscriptTag.innerHTML;
        }
    });
});
