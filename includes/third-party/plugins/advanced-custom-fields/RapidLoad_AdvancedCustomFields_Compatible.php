<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_AdvancedCustomFields_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'advanced-custom-fields/acf.php';
        $this->catgeory = 'custom-fields';
        $this->name = 'advanced-custom-fields';

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
            ( $this->str_contains($url_parts['query'], 'post_type=acf_field') )
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