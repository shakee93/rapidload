<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_WP_Optimize_Compatible  extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'wp-optimize/wp-optimize.php';
        $this->catgeory = 'cache';
        $this->name = 'wp-optimize';
        $this->has_conflict = true;

        parent::__construct();
    }

    public function init_hooks(){

        add_action( 'uucss/cached', [$this, 'rapidload_handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'rapidload_handle'], 10, 2 );

    }

    public function rapidload_handle($args){

        if(class_exists('WPO_Page_Cache')){

            $url = null;

            if ( isset( $args['url'] ) ) {
                $url = $this->transform_url( $args['url'] );
            }

            if($url){

                WPO_Page_Cache::delete_cache_by_url($url);

            }

        }

    }

    public function is_mu_plugin()
    {
        return false;
    }
}