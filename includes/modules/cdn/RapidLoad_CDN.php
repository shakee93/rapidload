<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_CDN
{
    use RapidLoad_Utils;

    public $options = [];

    public function __construct()
    {
        $this->options = RapidLoad_Base::rapidload_fetch_options();

        add_action('wp_ajax_validate_cdn', [$this, 'validate_cdn']);

        if (defined('RAPIDLOAD_DEV_MODE')) {
            add_action("wp_ajax_nopriv_validate_cdn", [$this, 'validate_cdn']);
        }

        add_action('rapidload/validate-cdn', [$this, 'validate_cdn']);

        if(!isset($this->options['uucss_enable_cdn']) || $this->options['uucss_enable_cdn'] !== "1" || !RapidLoad_Base::is_api_key_verified()){
            return;
        }

        //add_filter('uucss/enqueue/cache-file-url/cdn', [$this, 'replace_cdn'], 30);

        add_action('rapidload/job/handle', [$this, 'replace_cdn_html'], 40, 2);

        add_filter('uucss/enqueue/cdn', [$this, 'replace_cdn_url'], 30);

        add_action('rapidload/vanish', [ $this, 'vanish' ]);

        add_filter('rapidload/cdn/enabled', function (){
            return true;
        });

        add_filter('rapidload/cache_file_creating/css', [$this, 'replace_cdn_for_css_content']);
    }

    public function replace_cdn_for_css_content($css){

        if($this->is_cdn_enabled()){
            $css = str_replace(trailingslashit(site_url()), $this->options['uucss_cdn_url'], $css);
        }

        return $css;
    }

    public function validate_cdn($remove = false){

        $api = new RapidLoad_Api();

        if($remove){
            do_action('rapidload/cdn/validated', [
                'clear' => true,
                'cdn_url' => isset($this->options['uucss_cdn_url']) ? $this->options['uucss_cdn_url'] : null
            ]);
            unset($this->options['uucss_cdn_dns_id']);
            unset($this->options['uucss_cdn_zone_id']);
            unset($this->options['uucss_cdn_url']);
            unset($this->options['uucss_enable_cdn']);
            RapidLoad_Base::update_option('autoptimize_uucss_settings', $this->options);
            RapidLoad_Base::update_option('rapidload_module_cdn',"");
            return true;
        }

        $response = $api->rapidload_api_post('cdn',[
            'url' => trailingslashit(site_url()),
            'validate' => isset($this->options['uucss_cdn_dns_id']) && isset($this->options['uucss_cdn_zone_id']) && isset($this->options['uucss_cdn_url'])
        ]);

        if(is_wp_error($response)){
            if(wp_doing_ajax() && isset($_REQUEST['dashboard_cdn_validator'])){
                wp_send_json_error($api->extract_error($response));
            }
        }

        if(isset($response->zone_id) && isset($response->dns_id) && isset($response->cdn_url)){

            if(isset($this->options['uucss_cdn_zone_id']) && isset($this->options['uucss_cdn_dns_id'])){

                if($this->options['uucss_cdn_zone_id'] !== $response->zone_id){

                    $api->rapidload_api_post('delete-cdn',[
                        'dns_id' => $this->options['uucss_cdn_dns_id'],
                        'zone_id' => $this->options['uucss_cdn_zone_id']
                    ]);

                }

            }

            $this->options['uucss_cdn_zone_id'] = $response->zone_id;
            $this->options['uucss_cdn_dns_id'] = $response->dns_id;
            $this->options['uucss_cdn_url'] = $response->cdn_url;
            RapidLoad_Base::update_option('rapidload_module_cdn',"1");
            RapidLoad_Base::update_option('autoptimize_uucss_settings', $this->options);
            do_action('rapidload/cdn/validated', [
                'clear' => false,
                'cdn_url' => isset($this->options['uucss_cdn_url']) ? $this->options['uucss_cdn_url'] : null
            ]);
        }

        if(wp_doing_ajax() && isset($_REQUEST['dashboard_cdn_validator'])){
            wp_send_json_success([
                'uucss_cdn_zone_id' => $response->zone_id,
                'uucss_cdn_dns_id' => $response->dns_id,
                'uucss_cdn_url' => $response->cdn_url
            ]);
        }

        return true;
    }

    public function vanish(){

        $api = new RapidLoad_Api();

        if($this->options['uucss_cdn_zone_id'] && !empty($this->options['uucss_cdn_zone_id'])){
            $result = $api->rapidload_api_post('purge-cdn/' . $this->options['uucss_cdn_zone_id']);
        }

    }

    public function replace_cdn_url($url){

        $cdn = '';

        if($this->is_cdn_enabled()){
            $cdn = $this->options['uucss_cdn_url'];
        }

        $parsed_url = wp_parse_url($url);

        if($parsed_url['path'] && !empty($cdn)){
            return untrailingslashit($cdn) . $parsed_url['path'];
        }

        return $url;
    }

    public function replace_cdn($url){

        if($this->is_cdn_enabled()){
            return trailingslashit($this->options['uucss_cdn_url']);
        }

        return $url;
    }

    public function replace_cdn_html($job){

        if(!$job || !isset($job->id) || isset( $_REQUEST['no_rapidload_cdn'] )){
            return false;
        }

        if(!$this->is_cdn_enabled()){
            return false;
        }

        new RapidLoad_CDN_Enqueue($job);
    }

    public function is_cdn_enabled(){
        return isset($this->options['uucss_cdn_url']) && !empty($this->options['uucss_cdn_url'])
            && isset($this->options['uucss_cdn_dns_id']) && !empty($this->options['uucss_cdn_dns_id'])
            && isset($this->options['uucss_cdn_zone_id']) && !empty($this->options['uucss_cdn_zone_id']);
    }

    public static function update_cdn_url_in_cached_files($dir, $args) {
        if (!isset($args['cdn_url'])) {
            return;
        }
        $search_url = trailingslashit(site_url());
        $replace_url = trailingslashit($args['cdn_url']);
        if (isset($args['clear']) && boolval($args['clear'])) {
            $temp_url = $search_url;
            $search_url = $replace_url;
            $replace_url = $temp_url;
        }
        $files = self::get_files_in_dir($dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'css') {
                $content = file_get_contents($file);
                if (strpos($content, $search_url) !== false) {
                    $updated_content = str_replace($search_url, $replace_url, $content);
                    file_put_contents($file, $updated_content);
                }
            }
        }
    }
}