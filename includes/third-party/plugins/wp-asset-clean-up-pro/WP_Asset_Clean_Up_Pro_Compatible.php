<?php

defined( 'ABSPATH' ) or die();

class WP_Asset_Clean_Up_Pro_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'wp-asset-clean-up-pro/wpacu.php';
        $this->catgeory = 'cache-bust';
        $this->name = 'wp-asset-clean-up-pro';
        $this->has_conflict = true;

        parent::__construct();
    }

    public function init_hooks()
    {
        add_filter('uucss/cache/bust', [$this, 'handle']);
    }

    public function handle($args)
    {
        $args[] = [
            'type' => 'query',
            'rule' => 'wpacu_no_load'
        ];
        return $args;
    }

    public function is_mu_plugin()
    {
        return false;
    }
}