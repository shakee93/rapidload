<?php

defined( 'ABSPATH' ) || exit;

if(class_exists('RapidLoad_Elementor_Compatible')){
    return;
}

class RapidLoad_Elementor_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'elementor/elementor.php';
        $this->catgeory = 'theme-builder';
        $this->name = 'elementor';

        parent::__construct();
    }

    public function rapidload_init_hooks()
    {
        add_filter('uucss/url/exclude', [$this, 'rapidload_handle']);
    }

    public function rapidload_handle($args)
    {
        $url_parts = wp_parse_url( $args );

        if(isset($url_parts['query']) &&
            ( $this->rapidload_util_str_contains($url_parts['query'], 'elementor-preview') || 
                $this->rapidload_util_str_contains($url_parts['query'], 'preview_id') ||
                $this->rapidload_util_str_contains($url_parts['query'], 'elementor_library'))
        ){
            return false;
        }

        return $args;
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}