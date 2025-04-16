<?php

defined( 'ABSPATH' ) or die();

class WpForms_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'wpforms/wpforms.php';
        $this->catgeory = 'form-builder';
        $this->name = 'wpforms';

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
            ( $this->str_contains($url_parts['query'], 'post_type=wpforms') ||
                $this->str_contains($url_parts['query'], 'wpforms_form_preview')
            )
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