<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_AdvancedCustomFields_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'advanced-custom-fields/acf.php';
        $this->catgeory = 'custom-fields';
        $this->name = 'advanced-custom-fields';

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
            ( $this->rapidload_util_str_contains($url_parts['query'], 'post_type=acf_field') )
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