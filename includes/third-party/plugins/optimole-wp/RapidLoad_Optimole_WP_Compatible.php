<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_Optimole_WP_Compatible')){
    return;
}

class RapidLoad_Optimole_WP_Compatible extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin = 'optimole-wp/optimole-wp.php';
        $this->catgeory = 'cdn';
        $this->name = 'optimole-wp';
        $this->has_conflict = true;

        parent::__construct();
    }

    public function rapidload_init_hooks()
    {
        add_filter( 'uucss/enqueue/cdn-url', [$this, 'rapidload_handle'], 10, 1 );
    }

    public function rapidload_handle($args)
    {
        $optml_option = get_option('optml_settings');

        if(!isset($optml_option) || !isset($optml_option['cdn']) || $optml_option['cdn'] !== 'enabled'){

            return $args;
        }

        $converted_url = apply_filters( 'optml_content_url', $args );

        $url = explode('?', $args);

        if(isset($url[1])){
            // add if there any query params
            return $converted_url . '?' . $url[1];
        }

        return $converted_url;
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}