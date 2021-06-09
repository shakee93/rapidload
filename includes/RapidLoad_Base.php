<?php

defined( 'ABSPATH' ) or die();

class RapidLoad_Base
{
    public static function init(){

        global $uucss;

        $uucss = new UnusedCSS_RapidLoad();

        RapidLoad_ThirdParty::initialize();

    }

    public static function fetch_options()
    {
        if(is_multisite()){

            return get_blog_option(get_current_blog_id(), 'autoptimize_uucss_settings', false);

        }
        return get_site_option( 'autoptimize_uucss_settings', false );
    }
}