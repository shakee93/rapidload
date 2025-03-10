<?php

class RapidLoad_Image
{
    use RapidLoad_Utils;

    public $options = [];

    public static $image_indpoint;

    public static $instance;

    public function __construct()
    {
        $this->options = RapidLoad_Base::get_merged_options();

        if(!isset($this->options['uucss_enable_image_delivery']) || $this->options['uucss_enable_image_delivery'] != "1"){
            return;
        }

        self::$image_indpoint = "https://images.rapidload-cdn.io/spai/";

        add_action('wp_footer', [$this, 'enqueue_frontend_js'], 90);

        /*add_filter('wp_calculate_image_srcset', function ($a, $b, $c, $d, $e){
            foreach ($a as $index => $src){
                if(isset($src['url']) && isset($src['value'])){
                    $a[$index]['url'] = self::get_replaced_url($src['url'],self::$image_indpoint, $src['value'], false, ['retina' => 'ret_img']);
                }
            }
            return $a;
        }, 10, 5);*/

        add_action('rapidload/job/handle', [$this, 'optimize_image'], 30, 2);

        if(isset($this->options['rapidload_disable_thumbnails']) && $this->options['rapidload_disable_thumbnails'] == "1"){
            add_filter('intermediate_image_sizes_advanced',function (){
                return [];
            }, 90);
        }

        add_filter('rapidload/cache_file_creating/css', [$this, 'optimize_css_file_images'], 10 , 1);

        self::$instance = $this;
    }

    public function optimize_css_file_images($css) {
        $regex = '/url\(\s*(["\']?)(.*?)\1\s*\)/i';
        $site_url = site_url();

        return preg_replace_callback($regex, function ($matches) use ($site_url) {
            $url = $matches[2];

            if (preg_match('/^(https?:\/\/|\/\/)/i', $url)) {
                if (strpos($url, '//') === 0) {
                    $url = 'https:' . $url;
                }
                if (strpos($url, $site_url) === 0) {
                    $urlExt = pathinfo($url, PATHINFO_EXTENSION);

                    if (in_array(strtolower($urlExt), ["jpg", "jpeg", "png", "webp"])) {
                        $replace_url = RapidLoad_Image::get_replaced_url($url, self::$image_indpoint);
                        return 'url("' . $replace_url . '")';
                    }
                }
            }

            return $matches[0];
        }, $css);
    }

    public function enqueue_frontend_js(){

        ?>
        <script id="rapidload-image-handler" type="text/javascript" norapidload>
            <?php
                $image_handler_script = <<<EOD
                    window.rapidload_replace_image_src=function(){var images=document.getElementsByTagName("img");for(var i=0;i<images.length;i++){var image=images[i];var url=image.getAttribute("data-rp-src");if(window.rapidload_io_data&&url){var options="ret_img";if(window.rapidload_io_data.optimize_level){options+=",q_"+window.rapidload_io_data.optimize_level}if(window.rapidload_io_data.support_next_gen_format){options+=",to_avif"}if(window.rapidload_io_data.adaptive_image_delivery){if(image.width!==0){options+=",w_"+image.width}else if(image.getAttribute("width")&&Number(image.getAttribute("width"))!==0){options+=",w_"+image.getAttribute("width")}}url=window.rapidload_io_data.image_endpoint+options+"/"+url;if(image.getAttribute("src")!==url){image.setAttribute("src",url)}}}};var targetNode=document.getElementsByTagName("body")[0];var config={attributes:false,childList:true,subtree:true};var callback=function(mutationList,observer){for(var i=0;i<mutationList.length;i++){var mutation=mutationList[i];if(mutation.type==="childList"){var addedNodes=mutation.addedNodes;for(var j=0;j<addedNodes.length;j++){var node=addedNodes[j];if(node.nodeName==="#text"){continue}try{var imageTags=node.getElementsByTagName("img");if(imageTags.length){for(var k=0;k<imageTags.length;k++){var img=imageTags[k];var url=img.getAttribute("data-rp-src");if(window.rapidload_io_data&&url){var options="ret_img";if(window.rapidload_io_data.optimize_level){options+=",q_"+window.rapidload_io_data.optimize_level}if(window.rapidload_io_data.support_next_gen_format){options+=",to_avif"}if(window.rapidload_io_data.adaptive_image_delivery){if(img.getBoundingClientRect().width!==0){options+=",w_"+Math.floor(img.getBoundingClientRect().width)}}img.setAttribute("src",window.rapidload_io_data.image_endpoint+options+"/"+url)}}}}catch(e){}}}}};var observer=new MutationObserver(callback);observer.observe(targetNode,config);var observer_bg=new IntersectionObserver(function(elements){elements.forEach(function(element){if(element.isIntersecting){observer_bg.unobserve(element.target);var attributes=element.target.getAttribute("data-rapidload-lazy-attributes").split(",");attributes.forEach(function(attribute){if(element.target.tagName==="IFRAME"){element.target.setAttribute(attribute,element.target.getAttribute("data-rapidload-lazy-"+attribute))}else{var value=element.target.getAttribute("data-rapidload-lazy-"+attribute);element.target.style.backgroundImage="url("+value.replace("ret_blank","ret_img")+")"}})}});window.dispatchEvent(new Event("resize"))},{rootMargin:"300px"});document.addEventListener("DOMContentLoaded",function(){if(window.rapidload_io_data.adaptive_image_delivery){window.rapidload_replace_image_src()}});window.onresize=function(event){window.rapidload_replace_image_src()};["mousemove","touchstart","keydown"].forEach(function(event){var user_interaction_listener=function(){window.rapidload_replace_image_src();removeEventListener(event,user_interaction_listener)};addEventListener(event,user_interaction_listener)});var lazyElements=document.querySelectorAll('[data-rapidload-lazy-method="viewport"]');if(lazyElements&&lazyElements.length){lazyElements.forEach(function(element){observer_bg.observe(element)})}var playButtons=document.querySelectorAll(".rapidload-yt-play-button");playButtons.forEach(function(playButton){var videoContainer=playButton.closest(".rapidload-yt-video-container");var videoId=videoContainer.querySelector("img").getAttribute("data-video-id");function loadPosterImage(){var posterImageUrl="https://i.ytimg.com/vi/"+videoId+"/";var posterImage=videoContainer.querySelector(".rapidload-yt-poster-image");if(window.rapidload_io_data&&window.rapidload_io_data.support_next_gen_format){var options="ret_img";if(window.rapidload_io_data.optimize_level){options+=",q_"+window.rapidload_io_data.optimize_level}if(window.rapidload_io_data.support_next_gen_format){options+=",to_avif"}if(window.rapidload_io_data.adaptive_image_delivery){if(posterImage.getBoundingClientRect().width!==0){options+=",w_"+Math.floor(posterImage.getBoundingClientRect().width)}}posterImageUrl=window.rapidload_io_data.image_endpoint+options+"/"+posterImageUrl}posterImage.src=posterImageUrl+"hqdefault.jpg"}loadPosterImage();playButton.addEventListener("click",function(){var parentElement=this.parentElement;this.style.display="none";var posterImage=parentElement.querySelector(".rapidload-yt-poster-image");if(posterImage){posterImage.style.display="none"}var noscriptTag=parentElement.querySelector("noscript");if(noscriptTag){noscriptTag.outerHTML=noscriptTag.innerHTML}})});
                EOD;
                if (defined('RAPIDLOAD_DEV_MODE') && RAPIDLOAD_DEV_MODE === true) {
                    $filePath = RAPIDLOAD_PLUGIN_DIR . '/assets/js/rapidload_images.js';

                    if (file_exists($filePath)) {
                        $image_handler_script = file_get_contents($filePath);
                    }
                }
            ?>
            (function(w, d){
                w.rapidload_io_data = {
                    nonce : "<?php echo esc_js(self::create_nonce('rapidload_image')) ?>",
                    image_endpoint : "<?php echo esc_js(RapidLoad_Image::$image_indpoint) ?>",
                    optimize_level : "<?php echo esc_js(isset($this->options['uucss_image_optimize_level']) ? $this->options['uucss_image_optimize_level'] : 'null') ?>" ,
                    adaptive_image_delivery : <?php echo esc_js(isset($this->options['uucss_adaptive_image_delivery']) && $this->options['uucss_adaptive_image_delivery'] == "1" ? 'true' : 'false') ?> ,
                    support_next_gen_format : <?php echo esc_js(isset($this->options['uucss_support_next_gen_formats']) && $this->options['uucss_support_next_gen_formats'] == "1" ? 'true' : 'false') ?>
                };
            }(window, document));

            <?php echo esc_js($image_handler_script) ?>
        </script>
        <?php

    }

    public function optimize_image($job, $args){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_rapidload_image'] )){
            return false;
        }

        new RapidLoad_Image_Enqueue($job);

    }

    public static function get_replaced_url($url, $cdn = null, $width = false, $height = false, $args = [])
    {
        if(strpos( $url, self::$image_indpoint ) !== false){
            return $url;
        }

        if(!$cdn){
            $cdn = self::$image_indpoint;
        }

        $options = 'ret_blank';

        if(isset($args['retina'])){
            $options = $args['retina'];
        }

        $enamble_blurry_place_holder = isset(self::$instance->options['uucss_generate_blurry_place_holder']) && self::$instance->options['uucss_generate_blurry_place_holder'] == "1";

        if($enamble_blurry_place_holder){
            $options = 'ret_img';
        }

        if(isset($args['optimize_level']) && $enamble_blurry_place_holder){
            $options .= ',q_' . $args['optimize_level'];
        }else if(isset(self::$instance->options['uucss_image_optimize_level'])){
            $options .= ',q_' . self::$instance->options['uucss_image_optimize_level'];
        }

        if(isset(self::$instance->options['uucss_support_next_gen_formats']) && self::$instance->options['uucss_support_next_gen_formats'] == "1"){
            $options .= ',to_avif';
        }

        if(isset(self::$instance->options['uucss_adaptive_image_delivery']) && self::$instance->options['uucss_adaptive_image_delivery'] == "1"){
            if($width){

                $options .= ',w_' . str_replace("px", "", $width);
            }

            if($height){

                $options .=  ',h_' . str_replace("px", "", $height);
            }
        }

        return $cdn . $options . '/' . $url;
    }

    public function extractUrl($url){

        if(!$this->isAbsolute($url)){
            $url = $this->makeURLAbsolute($url, site_url());
        }

        return $url;
    }

    function isAbsolute($url) {
        return isset(parse_url($url)['host']);
    }

    function makeURLAbsolute($relative_url, $base_url) {

        $parsed_base_url = parse_url($base_url);

        if (strpos($relative_url, '/') !== 0) {
            $relative_url = '/' . $relative_url;
        }

        $absolute_url = $parsed_base_url['scheme'] . '://';
        $absolute_url .= $parsed_base_url['host'];
        $absolute_url .= (isset($parsed_base_url['port'])) ? ':' . $parsed_base_url['port'] : '';
        $absolute_url .= $relative_url;

        return $absolute_url;
    }

}