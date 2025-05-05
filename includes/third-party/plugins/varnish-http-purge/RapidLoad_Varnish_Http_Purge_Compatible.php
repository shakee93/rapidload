<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Varnish_Http_Purge_Compatible extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin = 'varnish-http-purge/varnish-http-purge.php';
        $this->catgeory = 'cache';
        $this->name = 'varnish-http-purge';

        parent::__construct();
    }

    public function init_hooks(){

        add_filter('uucss/cache/bust', [$this, 'add_cache_busting_params'], 10, 1);
        add_action( 'uucss/cached', [$this, 'handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'handle'], 10, 2 );

    }

    public function add_cache_busting_params($cacheBusting){

        array_push($cacheBusting, [
            'type' => 'query',
            'rule' => 'vhp-regex'
        ]);

        return $cacheBusting;
    }

    public function handle($args){

        if ( class_exists( 'VarnishPurger' ) ) {

            $url = null;

            if ( isset( $args['url'] ) ) {
                $url = $this->transform_url( $args['url'] );
            }

            if ( $url ) {

                VarnishPurger::purge_url($url);

            }

        }

    }

    public function is_mu_plugin()
    {
        return false;
    }
}