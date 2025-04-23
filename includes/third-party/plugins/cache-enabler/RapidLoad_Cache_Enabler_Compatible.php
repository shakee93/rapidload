<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Cache_Enabler_Compatible  extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin = 'cache-enabler/cache-enabler.php';
        $this->catgeory = 'cache';
        $this->name = 'cache-enabler';

        parent::__construct();
    }

    public function rapidload_init_hooks(){

        add_action( 'uucss/cached', [$this, 'rapidload_handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'rapidload_handle'], 10, 2 );

    }

    public function rapidload_handle($args){

        if(class_exists('Cache_Enabler')){

            $url = null;

            if ( isset( $args['url'] ) ) {
                $url = $this->transform_url( $args['url'] );
            }

            if($url){

                Cache_Enabler::clear_page_cache_by_url( $url );

            }

        }

    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}



