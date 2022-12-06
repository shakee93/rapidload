<?php

class RapidLoad_CDN
{
    use RapidLoad_Utils;

    public $options = [];

    public function __construct()
    {
        $this->options = RapidLoad_Base::fetch_options();

        if(!isset($this->options['uucss_enable_cdn'])){
            return;
        }

        add_filter('uucss/enqueue/cache-file-url/cdn', [$this, 'replace_cdn'], 30);

        add_filter('uucss/enqueue/cdn', [$this, 'replace_cdn_url'], 30);

        add_action('rapidload/vanish', [ $this, 'vanish' ]);
    }

    public function vanish(){

        $api = new RapidLoad_Api();

        if($this->options['uucss_cdn_zone_id'] && !empty($this->options['uucss_cdn_zone_id'])){
            $result = $api->post('purge-cdn/' . $this->options['uucss_cdn_zone_id']);
            error_log(json_encode($result) . 'cdn purged');
        }

    }

    public function replace_cdn_url($url){

        $cdn = '';

        if(isset($this->options['uucss_cdn_url']) && !empty($this->options['uucss_cdn_url'])
            && isset($this->options['uucss_cdn_dns_id']) && !empty($this->options['uucss_cdn_dns_id'])
            && isset($this->options['uucss_cdn_zone_id']) && !empty($this->options['uucss_cdn_zone_id'])){
            $cdn = trailingslashit($this->options['uucss_cdn_url']);
        }

        $parsed_url = parse_url($url);

        if($parsed_url['path'] && !empty($cdn)){
            return $cdn . $parsed_url['path'];
        }

        return $url;
    }

    public function replace_cdn($url){

        if(isset($this->options['uucss_cdn_url']) && !empty($this->options['uucss_cdn_url'])
            && isset($this->options['uucss_cdn_dns_id']) && !empty($this->options['uucss_cdn_dns_id'])
            && isset($this->options['uucss_cdn_zone_id']) && !empty($this->options['uucss_cdn_zone_id'])){
            return trailingslashit($this->options['uucss_cdn_url']);
        }

        return $url;
    }


}