<?php

defined( 'ABSPATH' ) or die();

class WP_Super_Cache_Compatible extends RapidLoad_ThirdParty {

    function __construct(){

        $this->plugin = 'wp-super-cache/wp-cache.php';
        $this->catgeory = 'cache';
        $this->name = 'wp-super-cache';

        parent::__construct();
    }

    public function init_hooks(){

        add_action( 'uucss/cached', [$this, 'handle'], 10, 2 );
        add_action( 'uucss/cache_cleared', [$this, 'handle'], 10, 2 );
        add_filter('wpsc_protected_directories', [$this, 'add_css_files_to_protected_directories']);

    }

    public function handle($args){

        if ( function_exists( 'wpsc_delete_url_cache' ) ) {

            $url = null;

            if ( isset( $args['url'] ) ) {
                $url = $this->transform_url( $args['url'] );
            }

            if ( $url ) {

                wpsc_delete_url_cache($url);

            }

        }

    }

    public function is_mu_plugin()
    {
        return false;
    }

    public function add_css_files_to_protected_directories($args){

        $path = RapidLoad_UnusedCSS::$base_dir;

        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ('.' === $file) continue;
                if ('..' === $file) continue;

                $args[] = RapidLoad_UnusedCSS::$base_dir . '/' . $file;
            }
            closedir($handle);
        }

        return $args;

    }
}