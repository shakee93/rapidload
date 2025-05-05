<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_LiteSpeed_Cache_Compatible  extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'litespeed-cache/litespeed-cache.php';
        $this->catgeory = 'cache';
        $this->name = 'lightspeed-cache';
        $this->has_conflict = true;

        parent::__construct();
    }

    public function init_hooks(){

        add_action( 'uucss/cached', [$this, 'handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'handle'], 10, 2 );
        add_filter('uucss/cache/bust', [$this, 'add_cache_busting_params'], 10, 1);

    }

    public function handle($args){

        if(class_exists('\LiteSpeed\Purge')){

            $url = null;

            if ( isset( $args['url'] ) ) {
                $url = $this->transform_url( $args['url'] );
            }

            if($url){

                \LiteSpeed\Purge::cls()->purge_url($url);

            }

        }

    }

    public function add_cache_busting_params($cacheBusting){

        array_push($cacheBusting, [
            'type' => 'query',
            'rule' => 'LSCWP_CTRL=before_optm'
        ]);

        return $cacheBusting;
    }

    public function is_mu_plugin()
    {
        return false;
    }
}
