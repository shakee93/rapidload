<?php

defined( 'ABSPATH' ) or die();
class RapidLoad_WP_Asset_Clean_Up_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'wp-asset-clean-up/wpacu.php';
        $this->catgeory = 'cache-bust';
        $this->name = 'wp-asset-clean-up';
        $this->has_conflict = true;

        parent::__construct();
    }

    public function rapidload_init_hooks()
    {
        add_filter('uucss/cache/bust', [$this, 'rapidload_handle']);
    }

    public function rapidload_handle($args)
    {
        $args[] = [
            'type' => 'query',
            'rule' => 'wpacu_no_load'
        ];
        return $args;
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}