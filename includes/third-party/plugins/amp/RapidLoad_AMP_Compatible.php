<?php

defined( 'ABSPATH' ) || exit;

if(class_exists('RapidLoad_AMP_Compatible')){
    return;
}

class RapidLoad_AMP_Compatible extends RapidLoad_ThirdParty{

    function __construct(){

        $this->plugin = 'amp/amp.php';
        $this->catgeory = 'performance';
        $this->name = 'amp';

        parent::__construct();
    }

    public function rapidload_init_hooks()
    {
        add_filter('uucss/enabled', [$this, 'rapidload_handle']);
    }

    public function rapidload_handle($args)
    {
        if(function_exists('amp_is_request') && amp_is_request()){
            return false;
        }
        return $args;
    }

    public function rapidload_is_mu_plugin()
    {
        return false;
    }
}