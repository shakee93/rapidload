<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_Elementor_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'elementor/elementor.php';
        $this->catgeory = 'theme-builder';
        $this->name = 'elementor';

        parent::__construct();
    }

    public function init_hooks()
    {
        add_filter('uucss/url/exclude', [$this, 'handle']);
    }

    public function handle($args)
    {
        $url_parts = wp_parse_url( $args );

        if(isset($url_parts['query']) &&
            ( $this->str_contains($url_parts['query'], 'elementor-preview') || 
                $this->str_contains($url_parts['query'], 'preview_id') ||
                $this->str_contains($url_parts['query'], 'elementor_library'))
        ){
            return false;
        }

        return $args;
    }

    public function is_mu_plugin()
    {
        return false;
    }
}