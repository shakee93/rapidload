<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Varnish_Http_Purge_Compatible extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin = 'varnish-http-purge/varnish-http-purge.php';
        $this->catgeory = 'cache';
        $this->name = 'varnish-http-purge';

        parent::__construct();
    }

    public function rapidload_init_hooks(){

        add_filter('uucss/cache/bust', [$this, 'rapidload_add_cache_busting_params'], 10, 1);
        add_action( 'uucss/cached', [$this, 'rapidload_handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'rapidload_handle'], 10, 2 );

    }

    public function rapidload_add_cache_busting_params($cacheBusting){

        array_push($cacheBusting, [
            'type' => 'query',
            'rule' => 'vhp-regex'
        ]);

        return $cacheBusting;
    }

    public function rapidload_handle($args){

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

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}