<?php

defined( 'ABSPATH' ) or die();

class RapidLoadRestApi {

    public static $namespace = 'rapidload/v1';

    public function __construct()
    {

        add_action( 'rest_api_init', function () {

        });

    }

    public static function rest_url()
    {
        return rest_url(self::$namespace);
    }
}
