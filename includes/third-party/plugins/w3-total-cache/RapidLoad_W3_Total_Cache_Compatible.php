<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_W3_Total_Cache_Compatible')){
    return;
}

class RapidLoad_W3_Total_Cache_Compatible extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin = 'w3-total-cache/w3-total-cache.php';
        $this->catgeory = 'cache';
        $this->name = 'w3-total-cache';
        $this->has_conflict = true;

        parent::__construct();
    }

    public function rapidload_init_hooks()
    {
        add_action( 'uucss/cached', [$this, 'rapidload_handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'rapidload_handle'], 10, 2 );
    }

    public function rapidload_handle($args)
    {
        if(function_exists('w3tc_flush_url')){

            $url = null;

            if ( isset( $args['url'] ) ) {
                $url = $this->rapidload_util_transform_url( $args['url'] );
            }

            if($url){

                w3tc_flush_url( $url );

            }

        }
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}