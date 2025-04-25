<?php

defined( 'ABSPATH' ) or die();

if(class_exists('RapidLoad_Rest_Api')){
    return;
}

/**
 * Class RapidLoad_Rest_Api
 */
class RapidLoad_Rest_Api {

    public static $namespace = 'rapidload/v1';

    public function __construct()
    {

        add_action( 'rest_api_init', function () {

        });

    }

    public static function rapidload_rest_url()
    {
        return rest_url(self::$namespace);
    }
}
