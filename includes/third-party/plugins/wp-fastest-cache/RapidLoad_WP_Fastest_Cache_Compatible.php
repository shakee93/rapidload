<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_WP_Fastest_Cache_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'wp-fastest-cache/wpFastestCache.php';
        $this->catgeory = 'cache';
        $this->name = 'wpFastestCache';

        parent::__construct();
    }

    public function init_hooks()
    {
        add_action( 'uucss/cached', [$this, 'handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'handle'], 10, 2 );
    }

    public function handle($args)
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

    public function is_mu_plugin()
    {
        return false;
    }
}