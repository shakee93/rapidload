<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_WpForms_Lite_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'wpforms-lite/wpforms.php';
        $this->catgeory = 'form-builder';
        $this->name = 'wpforms-lite';

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
            ( $this->rapidload_util_str_contains($url_parts['query'], 'post_type=wpforms') ||
                $this->rapidload_util_str_contains($url_parts['query'], 'wpforms_form_preview')
            )
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