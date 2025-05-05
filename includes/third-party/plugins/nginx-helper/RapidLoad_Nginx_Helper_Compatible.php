<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Nginx_Helper_Compatible extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin = 'nginx-helper/nginx-helper.php';
        $this->catgeory = 'cache';
        $this->name = 'nginx-helper';

        parent::__construct();
    }

    public function init_hooks()
    {
        add_action( 'uucss/cached', [$this, 'handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'handle'], 10, 2 );
    }

    public function handle($args)
    {
        global $nginx_purger;

        if(isset($nginx_purger)){

            $url = null;

            if ( isset( $args['url'] ) ) {
                $url = $this->transform_url( $args['url'] );
            }

            if($url){

                $nginx_purger->purge_url( $url );

            }
        }
    }

    public function is_mu_plugin()
    {
        return false;
    }
}