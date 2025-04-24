<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Nitropack_Compatible extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin = 'nitropack/main.php';
        $this->catgeory = 'cache';
        $this->name = 'nitropack';
        $this->has_conflict = true;

        parent::__construct();
    }

    public function rapidload_init_hooks()
    {
        add_action( 'uucss/cached', [$this, 'rapidload_handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'rapidload_handle'], 10, 2 );
    }

    public function rapidload_handle($args)
    {
        if ( isset( $args['url'] ) ) {
            $url = $this->rapidload_util_transform_url( $args['url'] );

            if($url){
                do_action("nitropack_execute_purge_url", $url);
            }
        }
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}