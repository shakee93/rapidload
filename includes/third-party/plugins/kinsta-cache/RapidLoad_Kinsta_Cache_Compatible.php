<?php

defined( 'ABSPATH' ) || exit;

if(class_exists('RapidLoad_Kinsta_Cache_Compatible')){
    return;
}

class RapidLoad_Kinsta_Cache_Compatible extends RapidLoad_ThirdParty{

    public $kinsta_cache;

    function __construct(){

        $this->plugin = 'kinsta-mu-plugins.php';
        $this->catgeory = 'cache';
        $this->name = 'kinsta-cache';

        parent::__construct();

        add_action('kinsta_cache_init', function ($kinsta){
            $this->kinsta_cache = $kinsta->kinsta_cache_purge;
        },10,1);
    }

    public function rapidload_is_mu_plugin(){
        return class_exists('\Kinsta\Cache_Purge');
    }

    public function rapidload_init_hooks()
    {
        add_action( 'uucss/cached', [$this, 'rapidload_handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'rapidload_handle'], 10, 2 );
    }

    public function rapidload_handle($args)
    {
        if(class_exists('\Kinsta\Cache_Purge')){

            $url = null;

            if ( isset( $args['url'] ) ) {
                $url = $this->rapidload_util_transform_url( $args['url'] );
            }

            if($url && isset($this->kinsta_cache)){

                $this->kinsta_cache->purge_complete_caches();
            }

        }
    }
}