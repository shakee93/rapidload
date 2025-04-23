<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_WP_Fastest_Cache_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'wp-fastest-cache/wpFastestCache.php';
        $this->catgeory = 'cache';
        $this->name = 'wpFastestCache';

        parent::__construct();
    }

    public function rapidload_init_hooks()
    {
        add_action( 'uucss/cached', [$this, 'rapidload_handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'rapidload_handle'], 10, 2 );
    }

    public function rapidload_handle($args)
    {
        if(class_exists('WpFastestCache')){

            $url = null;

            if ( isset( $args['url'] ) ) {
                $url = $this->transform_url( $args['url'] );
            }

            $post_id = url_to_postid( $url );

            if($post_id){

                $wpfc = new WpFastestCache();
                $wpfc->singleDeleteCache(false, $post_id);

            }

        }
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}