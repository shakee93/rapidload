<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_WP_Hummingbird_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'wp-hummingbird/wp-hummingbird.php';
        $this->catgeory = 'cache';
        $this->name = 'wp-hummingbird';
        $this->has_conflict = true;

        parent::__construct();
    }

    public function rapidload_init_hooks()
    {
        add_action( 'uucss/cached', [$this, 'rapidload_handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'rapidload_handle'], 10, 2 );
        add_filter('uucss/url/exclude', [$this, 'rapidload_exclude']);
    }

    public function rapidload_handle($args)
    {
        if(class_exists('\Hummingbird\Core\Utils')){

            $url = null;

            if ( isset( $args['url'] ) ) {
                $url = $this->rapidload_util_transform_url( $args['url'] );
            }

            if ( $url ) {

                $pc_module = \Hummingbird\Core\Utils::get_module( 'page_cache' );

                if(isset($pc_module)){

                    $pc_module->clear_cache($url);

                }

            }

        }
    }

    public function rapidload_exclude($args)
    {
        $url_parts = wp_parse_url( $args );

        if(isset($url_parts['query']) &&
            ( $this->rapidload_util_str_contains($url_parts['query'], 'post_type=wphb_minify_group'))
        ){
            return false;
        }

        return $args;
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}