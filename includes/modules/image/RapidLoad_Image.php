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

        if(!RapidLoad_Base::is_api_key_verified()){
            return;
        }

        ?>
        <script id="rapidload-image-handler" type="text/javascript" norapidload>
            <?php
                $image_handler_script = file_get_contents(RAPIDLOAD_PLUGIN_DIR . '/assets/js/rapidload_images.min.js');
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

            <?php echo $image_handler_script ?>
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

        if(!RapidLoad_Base::is_api_key_verified()){
            return $url;
        }

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