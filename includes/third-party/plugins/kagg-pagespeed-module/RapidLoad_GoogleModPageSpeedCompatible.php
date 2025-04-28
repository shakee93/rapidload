<?php

defined( 'ABSPATH' ) || exit;

if(class_exists('RapidLoad_GoogleModPageSpeedCompatible')){
    return;
}

class RapidLoad_GoogleModPageSpeedCompatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'kagg-pagespeed-module/mod-pagespeed.php';
        $this->catgeory = 'cache-bust';
        $this->name = 'mod-pagespeed';

        parent::__construct();
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }

    public function rapidload_init_hooks()
    {
        add_filter('uucss/cache/bust', [$this, 'rapidload_handle']);
    }

    public function rapidload_handle($args)
    {
        if(class_exists('Mod_PageSpeed')){
            $args[] = [
                'type' => 'query',
                'rule' => 'ModPagespeed=off'
            ];
        }
        return $args;
    }
}