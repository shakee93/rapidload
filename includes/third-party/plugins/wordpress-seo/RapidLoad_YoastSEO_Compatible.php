<?php

defined( 'ABSPATH' ) || exit;

class RapidLoad_YoastSEO_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'wordpress-seo/wp-seo.php';
        $this->catgeory = 'seo';
        $this->name = 'yoast-seo';

        parent::__construct();
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }

    public function rapidload_init_hooks()
    {
        add_filter('uucss/sitemap-path', [$this, 'rapidload_handle'], 10, 1);
    }

    public function rapidload_handle($args)
    {
        $path = $args;
        if(class_exists('WPSEO_Options') && class_exists('WPSEO_Sitemaps_Router')){
            if(WPSEO_Options::get( 'enable_xml_sitemap' )){
                $path = esc_url( WPSEO_Sitemaps_Router::get_base_url( 'sitemap_index.xml' ) );
            }
        }
        return $path;
    }
}