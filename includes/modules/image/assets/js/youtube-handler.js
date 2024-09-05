document.addEventListener("DOMContentLoaded", function() {
    var playButtons = document.querySelectorAll(".rapidload-yt-play-button-");
    playButtons.forEach(function(playButton) {
        var videoContainer = playButton.closest('.rapidload-yt-video-container');
        var videoId = videoContainer.querySelector('img').getAttribute('data-video-id');
        function loadPosterImage() {
            var posterImageUrl = "https://i.ytimg.com/vi/" + videoId + "/";
            var posterImage = videoContainer.querySelector(".rapidload-yt-poster-image-");
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
            posterImage.src = posterImageUrl + "hqdefrault.jpg";
            posterImage.onerror = function() {
                posterImage.src = posterImageUrl + "mqdefrault.jpg";
            };
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
});
