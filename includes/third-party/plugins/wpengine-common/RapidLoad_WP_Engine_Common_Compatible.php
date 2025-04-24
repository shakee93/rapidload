<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_WP_Engine_Common_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'mu-plugin.php';
        $this->catgeory = 'cache';
        $this->name = 'wpengine-common';

        parent::__construct();
    }

    public function rapidload_is_mu_plugin(){
        return class_exists('WpeCommon');
    }

    public function rapidload_init_hooks()
    {
        add_action( 'uucss/cached', [$this, 'rapidload_handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'rapidload_handle'], 10, 2 );
    }

    public function rapidload_handle($args)
    {
        if(class_exists('WpeCommon')){

            $url = null;

            if ( isset( $args['url'] ) ) {
                $url = $this->rapidload_util_transform_url( $args['url'] );
            }

            if($url){

                WpeCommon::purge_memcached();
                WpeCommon::clear_maxcdn_cache();
                WpeCommon::purge_varnish_cache();

            }

        }
    }
}